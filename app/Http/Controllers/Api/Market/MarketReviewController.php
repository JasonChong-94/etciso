<?php

namespace App\Http\Controllers\Api\Market;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Api\Market\MarketContract;
use App\Models\Api\Market\MarketType;
use App\Models\Api\Market\MarketRegion;
use App\Models\Api\Inspect\InspectPlan;
use App\Models\Api\Examine\SystemRegion;
use App\Models\Api\Examine\ExamineProject;
use App\Models\Api\User\UserBasic;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class MarketReviewController extends Controller
{
    /**我的年审复评**/
    public function ReviewIndex(Request $request){
        $userId = Auth::guard('api')->id();
        $where = array(
            ['khxx.scjl','like','%;'.$userId.';%'],
            ['ji_shlx','!=','01'],
            ['ji_shlx','!=','00'],
        );
        $file = array(
            'khxx.id as id',
            'qyht.id as hid',
            'qyht_htrz.id as xid',
            'htbh',
            'qymc',
            'bgdz',
            'fz_at',
            'rztx',
            'shlx',
            'rzbz',
            'yxrs',
            'rev_range',
            'one_mode',
            'ji_shlx',
            'stage',
            'regt_numb',
            'sh_time',
            'ji_time',
            'dl_time',
            'zs_m',
            'zs_nb',
            'zs_ftime',
            'zs_etime',
            'scjl',
        );
        switch ($request->field)
        {
            case 1:
                $sortField = 'ji_time';
                break;
            case 2:
                $sortField = 'dl_time';
                break;
            default:
                $sortField = 'ji_time';
        }

        switch ($request->sort)
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
        $flighs = MarketContract::ReviewContract($file,$where,$request->limit,$sortField,$sort);
        if($flighs->isEmpty()){
            return response()->json(['status'=>101,'msg'=>'无数据']);
        }
        $fligh = $flighs->toArray();
        array_walk($fligh['data'], function (&$value,$key) {
            switch ($value['zs_m'])
            {
                case 1:
                    $value['zs_m'] = '有效';
                    break;
                case 2:
                    $value['zs_m'] = '暂停';
                    break;
                case 3:
                    $value['zs_m'] = '撤销';
            }
            $value['scjl'] = substr($value['scjl'],1,-1);
            $clientUser = explode(";",$value['scjl']);
            $UserArray  = UserBasic::IndexBasic('name','id',$clientUser);
            $UserArray  = $UserArray->toArray();
            $value['scjl'] = implode(";",array_column($UserArray,'name'));
        });
        return response()->json(['status'=>100,'msg'=>'请求成功','data'=>$fligh]);
    }

    /**全部年审复评**/
    public function ReviewAll(Request $request){
        $where = array(
            ['ji_shlx','!=','01'],
            ['ji_shlx','!=','00'],
        );
        $file = array(
            'khxx.id as id',
            'qyht.id as hid',
            'qyht_htrz.id as xid',
            'htbh',
            'qymc',
            'bgdz',
            'fz_at',
            'rztx',
            'shlx',
            'rzbz',
            'yxrs',
            'rev_range',
            'one_mode',
            'ji_shlx',
            'stage',
            'regt_numb',
            'sh_time',
            'ji_time',
            'dl_time',
            'zs_m',
            'zs_nb',
            'zs_ftime',
            'zs_etime',
            'scjl',
        );
        switch ($request->field)
        {
            case 1:
                $sortField = 'ji_time';
                break;
            case 2:
                $sortField = 'dl_time';
                break;
            default:
                $sortField = 'ji_time';
        }

        switch ($request->sort)
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
        $flighs = MarketContract::ReviewContract($file,$where,$request->limit,$sortField,$sort);
        if($flighs->isEmpty()){
            return response()->json(['status'=>101,'msg'=>'无数据']);
        }
        $fligh = $flighs->toArray();
        array_walk($fligh['data'], function (&$value,$key) {
            switch ($value['zs_m'])
            {
                case 1:
                    $value['zs_m'] = '有效';
                    break;
                case 2:
                    $value['zs_m'] = '暂停';
                    break;
                case 3:
                    $value['zs_m'] = '撤销';
            }
            $value['scjl'] = substr($value['scjl'],1,-1);
            $clientUser = explode(";",$value['scjl']);
            $UserArray  = UserBasic::IndexBasic('name','id',$clientUser);
            $UserArray  = $UserArray->toArray();
            $value['scjl'] = implode(";",array_column($UserArray,'name'));
        });
        return response()->json(['status'=>100,'msg'=>'请求成功','data'=>$fligh]);
    }

    /**年审复评类型**/
    public function ReviewType(Request $request){
        $region = MarketRegion::
            where('state', '=', 1)
            ->get();
        $typeWhere= array(
            ['xm_id','=',0],
        );
        $typeFile = array(
            'id',
            'xiangmu',
        );
        $project = ExamineProject::IndexProject($typeFile,$typeWhere);
        if($region->isEmpty() || $project->isEmpty()){
            return response()->json(['status'=>101,'msg'=>'无数据']);
        }else{
            $data['region'] = $region;
            $data['project']= $project;
            return response()->json(['status'=>100,'msg'=>'请求成功','data'=>$data]);
        }
    }

    /**年审复评查询**/
    public function ReviewQuery(Request $request){
        $file = array(
            'khxx.id as id',
            'qyht.id as hid',
            'qyht_htrz.id as xid',
            'htbh',
            'qymc',
            'bgdz',
            'fz_at',
            'rztx',
            'shlx',
            'rzbz',
            'yxrs',
            'rev_range',
            'one_mode',
            'ji_shlx',
            'stage',
            'regt_numb',
            'sh_time',
            'ji_time',
            'dl_time',
            'zs_m',
            'zs_nb',
            'zs_ftime',
            'zs_etime',
            'scjl',
        );
        switch ($request->field)
        {
            case 1:
                $sortField = 'ji_time';
                break;
            case 2:
                $sortField = 'dl_time';
                break;
            default:
                $sortField = 'ji_time';
        }
        switch ($request->sort)
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
        $where = array(
            ['ji_shlx','!=','01'],
            ['ji_shlx','!=','00'],
        );
        switch ($request->scene)
        {
            case 1:
                $userId = Auth::guard('api')->id();
                $where[] = ['scjl','like','%;'.$userId.';%'];
                break;
            case 2:
                if($request->userid){
                    $where[] = ['scjl','like','%;'.$request->userid.';%'];
                }
                break;
            default:
                return response()->json(['status'=>101,'msg'=>'筛选场景错误']);
        };
        if($request->name){
            $where[] = ['khxx.qymc','like', '%'.$request->name.'%'];
        }
        if($request->ji_shlx){
            $where[] = ['ji_shlx','=',$request->ji_shlx];
        }
        if($request->system){
            $where[] = ['rztx','=',$request->system];
        }
        if($request->region){
            $where[] = ['fzjg','=',$request->region];
        }
        $time = array();
        if($request->detime){
            $time['dl_time'] = [$request->detime.'-01',$request->detime.'-31 24:00:00'];
        }
        if($request->pstime){
            $time['ji_time'] = [$request->pstime,$request->petime];
        }
        $flighs= MarketContract::ReviewContract($file,$where,$request->limit,$sortField,$sort,$time);
        if($flighs->isEmpty()){
            return response()->json(['status'=>101,'msg'=>'无数据']);
        }
        $fligh = $flighs->toArray();
        array_walk($fligh['data'], function (&$value,$key) {
            switch ($value['zs_m'])
            {
                case 1:
                    $value['zs_m'] = '有效';
                    break;
                case 2:
                    $value['zs_m'] = '暂停';
                    break;
                case 3:
                    $value['zs_m'] = '撤销';
            }
            $value['scjl'] = substr($value['scjl'],1,-1);
            $clientUser = explode(";",$value['scjl']);
            $UserArray  = UserBasic::IndexBasic('name','id',$clientUser);
            $UserArray  = $UserArray->toArray();
            $value['scjl'] = implode(";",array_column($UserArray,'name'));
        });
        return response()->json(['status'=>100,'msg'=>'请求成功','data'=>$fligh]);
    }

    /**年审复评审核信息**/
    public function ReviewDetails(Request $request){
        $a_time = '';
        switch($request->ji_shlx)
        {
            case '02':
                $audit = '07';
                break;
            case '07':
                $audit = '03';
                break;
            case '03':
                if($request->shlx == '02'){
                    $audit = '0202';
                    $where = array(
                        ['audit_phase','=','0201'],
                        ['xm_id','=',$request->id],
                    );
                }else{
                    $audit = '0102';
                    $where = array(
                        ['audit_phase','=','0101'],
                        ['xm_id','=',$request->id],
                    );
                }
                if($request->one_mode == '02'){
                    $flight = InspectPlan::where($where)
                        ->select('actual_s','actual_e')
                        ->first();
                    if(!$flight){
                        return response()->json(['status'=>101,'msg'=>'无数据']);
                    }
                    $a_time = $flight['actual_s'].'至'.$flight['actual_e'];
                }
                break;
        }
        $where = array(
            ['audit_phase','=',$audit],
            ['xm_id','=',$request->id],
        );
        $file = array(
            'actual_s',
            'actual_e',
            'name',
            'role',
        );
        $testUser = InspectPlan::IndexPlan($file,$where);
        if($testUser->isEmpty()){
            return response()->json(['status'=>101,'msg'=>'无数据']);
        }
        $testUser = $testUser->toArray();
        $data = array();
        $data['stime'] = $a_time;
        $data['etime'] = $testUser[0]['actual_s'].'至'.$testUser[0]['actual_e'];
        foreach ($testUser as $user){
            switch($user['role']){
                case '01':
                    $data['leader'] = $user['name'];
                    break;
                case '02':
                    $data['member'] = $user['name'];
                    break;
                case '03':
                    $data['expert'] = $user['name'];
                    break;
            }
        }
        return response()->json(['status'=>100,'msg'=>'请求成功','data'=>$data]);
    }

}
