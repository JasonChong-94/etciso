<?php

namespace App\Http\Controllers\Api\Inspect;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use App\Models\Api\Market\MarketContract;
use App\Models\Api\Inspect\InspectAuditTeam;
use App\Models\Api\User\UserCode;
use App\Models\Api\System\ApprovalGroup;
use App\Models\Api\System\SystemState;
use Illuminate\Support\Facades\DB;

class InspectCodeController extends Controller
{
    /**人员审核代码列表**/
    public function CodeIndex(Request $request){
        $flighs = $this->CodeShare('',$request->limit,$request->field,$request->sort);
        return($flighs);
    }

    /**人员审核代码查询**/
    public function CodeQuery(Request $request){
        $where = array();
        if(!empty($request->name)){
            $where[] = ['name','=',$request->name];

        }
        if(!empty($request->na_code)){
            $where[] = ['na_code','=',$request->na_code];
        }
        if(!empty($request->region)){
            $where[] = ['region','=',$request->region];
        }
        if(!empty($request->major)){
            $where[] = ['major','=',$request->major];
        }
        $flighs = $this->CodeShare($where,$request->limit,$request->field,$request->sort);
        return($flighs);
    }

    /**查询函数**/
    public function CodeShare($where,$limit,$sortField,$sort){
        $file = array(
            'name',
            'type',
            'fz_at',
            'system',
            'major',
            'number',
            'stage_id',
        );
        switch ($sortField)
        {
            case 1:
                $sortField = 'system';
                break;
            case 2:
                $sortField = 'major';
                break;
            case 3:
                $sortField = 'number';
                break;
            case 4:
                $sortField = 'us_id';
                break;
            default:
                $sortField = 'us_id';
        }
        switch ($sort)
        {
            case 1:
                $sort = 'desc';
                break;
            case 2:
                $sort = 'asc';
                break;
            default:
                $sort = 'asc';
        }
        //DB::connection()->enableQueryLog();#开启执行日志
        $flighs = UserCode::UserCode($file,$where,$limit,$sortField,$sort);
        //dump(DB::getQueryLog());
        if($flighs->isEmpty()){
            return response()->json(['status'=>101,'msg'=>'无数据']);
        }
        $flighs = $flighs->toArray();
        array_walk($flighs['data'], function (&$value,$key){
            switch ($value['type']) {
                case 0:
                    $value['type'] = '兼职';
                    break;
                case 1:
                    $value['type'] = '专职';
                    break;
            }
        });
        return response()->json(['status'=>100,'msg'=>'请求成功','data'=>$flighs]);
    }

    /**人员审核代码详情**/
    public function CodeDetails(Request $request){
        $file = array(
            'khxx.id as id',
            'qyht_htrza.id as did',
            'qymc',
            'xmbh',
            'rztx',
            'audit_phase',
            'start_time',
            'end_time',
            'p_major_code',
        );
        $whereIn= explode(";",$request->stage_id);
        $flighs = MarketContract::UnionPlan($file,$whereIn);
        if($flighs->isEmpty()){
            return response()->json(['status'=>101,'msg'=>'没有添加认证项目']);
        }
        $flighs = $flighs->sort();
        $flighs = $flighs->toArray();
        array_walk($flighs, function (&$value,$key){
            $stateWhere = array(
                ['code','=',$value['audit_phase']],
            );
            $state = SystemState::IndexState('activity',$stateWhere);
            $value['audit_phase'] = $state->first()->activity;
            $where = array(
                ['ap_id','=',$value['did']]
            );
            $file = array(
                'name',
                'role',
                'mjexm'
            );
            $teamUser = InspectAuditTeam::IndexTeam($file,$where);
            if($teamUser->isEmpty()){
                $value['groud']  = '';
                $value['peple']  = '';
                $value['major']  = '';
            }else{
                $groud = array_filter($teamUser->toArray(), function ($value) {
                    if($value['role'] == '01'){
                        return($value);
                    }
                });
                $major = array_filter($teamUser->toArray(), function ($value) {
                    if($value['mjexm'] == '1'){
                        return($value);
                    }
                });
                $value['groud'] = implode(";",array_column($groud, 'name'));
                $value['peple']  = implode(";",array_column($teamUser->toArray(), 'name'));
                $value['major']  = implode(";",array_column($major, 'name'));
            }
        });
        return response()->json(['status'=>100,'msg'=>'请求成功','data'=>array_values($flighs)]);
    }

    /**导入人员审核代码**/
    public function CodeImport(Request $request){
        $file = array(
            'us_id',
            'ap_id',
            'p_major_code',
            'rztx',
        );
        $where= array(
            ['cert_sbmt','=',1],
            ['audit_phase','<>','0101'],
            ['audit_phase','<>','0201'],
        );
        //DB::connection()->enableQueryLog();#开启执行日志
        $flighs = InspectAuditTeam::TeamMajor($file,$where);
        //dump(DB::getQueryLog());
        if($flighs->isEmpty()){
            return response()->json(['status'=>101,'msg'=>'无数据']);
        }
        $flighs = $flighs->toArray();
        $major = array();

        foreach($flighs as $pro){
            $pos = strpos($pro['p_major_code'], ";");
            if ($pos === false) {
                $pro['numb'] = 1;
                $major[] = $pro;
            }else{
                $ma_ay = explode(";",$pro['p_major_code']);
                foreach($ma_ay as $ay){
                    $pro['p_major_code'] = $ay;
                    $pro['numb'] = 1;
                    $major[] = $pro;
                }
            }
        }
        $item=array();
        foreach($major as $k=>$v){
            if(!isset($item[$v['us_id'].'-'.$v['p_major_code'].'-'.$v['rztx']])){
                $item[$v['us_id'].'-'.$v['p_major_code'].'-'.$v['rztx']]['us_id']=$v['us_id'];
                $item[$v['us_id'].'-'.$v['p_major_code'].'-'.$v['rztx']]['system']=$v['rztx'];
                $item[$v['us_id'].'-'.$v['p_major_code'].'-'.$v['rztx']]['major']=$v['p_major_code'];
                $item[$v['us_id'].'-'.$v['p_major_code'].'-'.$v['rztx']]['number']=$v['numb'];
                $item[$v['us_id'].'-'.$v['p_major_code'].'-'.$v['rztx']]['stage_id']=$v['ap_id'];
            }else{
                $item[$v['us_id'].'-'.$v['p_major_code'].'-'.$v['rztx']]['stage_id'] .= ';'.$v['ap_id'];
                $item[$v['us_id'].'-'.$v['p_major_code'].'-'.$v['rztx']]['number'] += $v['numb'];
            }
        }
        $item = array_values($item);
        $fligha = UserCode::insert($item);
        if($fligha == 0){
            return response()->json(['status'=>101,'msg'=>'人员审核代码添加失败']);
        }
        return response()->json(['status'=>100,'msg'=>'人员审核代码添加成功']);
    }
}
