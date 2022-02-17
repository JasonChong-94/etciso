<?php

namespace App\Http\Controllers\Api\Inspect;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\Api\Market\MarketCustomer;
use App\Models\Api\Market\MarketContacts;
use App\Models\Api\Market\MarketContract;
use App\Models\Api\Inspect\InspectAuditTeam;
use App\Models\Api\Inspect\InspectPlan;
use App\Models\Api\User\UserBasic;
use App\Models\Api\User\UserMajor;
use App\Models\Api\User\UserType;
use App\Models\Api\System\SystemState;
use App\Models\Api\System\SystemUnion;
use App\Models\Api\Examine\ExamineSystem;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\TemplateProcessor;

class InspectDispatchController extends Controller
{
    /**项目计划列表**/
    public function DispatchIndex(Request $request){
        $where  = array(
            ['department','=',1],
            ['pt_m','=',1],
            ['plan_m','=',1],
        );
        $flighs = $this->DispatchShare($where,$request->limit,$request->field,$request->sort);
        return($flighs);
    }

    /**项目计划查询**/
    public function DispatchQuery(Request $request){
        $where  = array(
            ['department','=',1],
            ['pt_m','=',1],
            ['plan_m','=',1],
        );
        //$orWhere = array();
        if($request->name){
            $where[] = ['khxx.qymc', 'like', '%'.$request->name.'%'];
        }

        if($request->xmbh){
            $where[] = ['xmbh', '=',$request->xmbh];
        }

        if($request->htbh){
            $where[] = ['htbh', '=',$request->htbh];
        }

        if($request->project){
            $where[] = ['rztx', '=',$request->project];
        }
        if($request->sphase){
            $where[] = ['audit_phase', '=',$request->sphase];
        }
        if($request->obtain_time){
            $where[] = ['obtain_time', '<>',null];
            $where[] = ['obtain_time', '<>',''];
        }
        if($request->stage){
            switch ($request->stage)
            {
                case 1:
                    $where[] = ['rept_sbmt', '=',1];
                    break;
                case 2:
                    $where[] = ['result_sbmt', '=',1];
                    break;
                case 3:
                    $where[] = ['print_sbmt', '=',1];
                    break;
            }
        }
        $orWhere = array();
        if($request->code){
            $orWhere[] = array(
                ['p_major_code','like','%;'.$request->code.'%']
            );
            $orWhere[] = array(
                ['major_code','like','%;'.$request->code.'%']
            );
        }
        if($request->cer_type){
            $orWhere[] = array(
                ['p_cer_type','=',$request->cer_type]
            );
            $orWhere[] = array(
                ['cer_type','=',$request->cer_type]
            );
        }
        if($request->multi){
            $where[] = ['m_rzly', '=',$request->multi];
        }
        if($request->region){
            $where[] = ['fzjg', '=',$request->region];
        }
        if($request->office){
            $where[] = ['bgdz', 'like','%'.$request->office.'%'];
        }
        if($request->zcdz){
            $where[] = ['zcdz', 'like','%'.$request->zcdz.'%'];
        }
        if($request->range){//审核范围
            $orWhere[] = array(
                ['p_rev_range','like','%'.$request->range.'%']
            );
            $orWhere[] = array(
                ['rev_range','like','%'.$request->range.'%']
            );
        }
/*        if(!empty($request->plst) && !empty($request->plet)){
            $where[] = ['start_time', '>=',$request->plst];
            $where[] = ['end_time', '<=',$request->plet];
        }*/
        $time = array();
        if($request->plst && $request->plet){
            $time['start_time'] = [$request->plst,$request->plet];
        }
        if($request->sbst && $request->sbet){//提交时间
            $time['plan_time'] = [$request->sbst,$request->sbet];
        }
        if($request->rwst && $request->rwet){//复核时间
            $time['dp_time'] = [$request->rwst,$request->rwet];
        }
        if($request->elst && $request->elet){//评定时间
            $time['evte_time'] = [$request->elst,$request->elet];
        }
        if($request->ostime){
            $time['obtain_time'] = [$request->ostime, $request->oetime];
        }
        $flighs = $this->DispatchShare($where,$request->limit,$request->field,$request->sort,$orWhere,$time);
        return($flighs);
    }

    /**查询函数**/
    public function DispatchShare($where,$limit,$sortField,$sort,$orWhere='',$time=''){
        $file = array(
            'khxx.id as id',
            'qyht.id as hid',
            'qyht_htrz.id as xid',
            'qyht_htrza.id as did',
            'xmbh',
            'htbh',
            'qymc',
            'bgdz',
            'zcdz',
            'bgdz',
            'd_patch',
            'm_rzly',
            'rztx',
            'rzbz',
            'major_code',
            'rev_range',
            'audit_phase',
            'cbt_type',
            'bd_degree',
            'one_trial_day',
            'supervise_day',
            'last_trial_day',
            'field_day',
            'repor_day',
            'start_time',
            'end_time',
            'actual_s',
            'actual_e',
            'rwmas_t',
            'plan_time',
            'union_cmpy',
            'union_time',
            'dp_time',
            'evte_time',
            'one_mode',
            'cer_type',
            'regt_numb',
            //'stage',
            'ji_time',
            //'fz_at',
            'p_rzbz',
            'p_major_code',
            'p_rev_range',
            'p_cbt_type',
            'p_bd_degree',
            'p_cer_type',
            'p_zs_m',
            'p_regt_numb',
            'p_zs_nb',
            'p_zs_etime',
            'user_state',
            'rept_sbmt',
            'result_sbmt',
            'obtain_time',
        );
        switch ($sortField)
        {
            case 1:
                $sortField = 'xmbh';
                break;
            case 2:
                $sortField = 'qymc';
                break;
            case 3:
                $sortField = 'plan_time';
                break;
            case 4:
                $sortField = 'evte_time';
                break;
            case 5:
                $sortField = 'regt_numb';
            case 7:
                $sortField = 'obtain_time';
                break;
            default:
                $sortField = 'xmbh';
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
                $sort = 'desc';
        }
        //DB::connection()->enableQueryLog();#开启执行日志
        $flighs = MarketContract::DispatchContract($file,$where,$limit,$sortField,$sort,$orWhere,$time);
        //return(DB::getQueryLog());
        if($flighs->isEmpty()){
            return response()->json(['status'=>101,'msg'=>'无数据']);
        }
        foreach ($flighs as $data){
/*            $stateWhere = array(
                ['code','=',$data->audit_phase],
                ['state','=',1]
            );
            $state = SystemState::IndexState('activity',$stateWhere);*/
            if($data->obtain_time !='' && $data->obtain_time != null){
                $start= strtotime(date('Y-m-d'));
                $end  = strtotime($data->obtain_time);
                $strto= $end-$start;
                $days = intval($strto/86400);
                $data->days = $days;
            }
            $where = array(
                ['ap_id','=',$data->did]
            );
            $file = array(
                'name',
                'role',
                'mjexm'
            );
            $teamUser = InspectAuditTeam::IndexTeam($file,$where);
            if($teamUser->isEmpty()){
                $data->groud  = '';
                $data->peple  = '';
                $data->major  = '';
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
                $data->groud  = implode(";",array_column($groud, 'name'));
                $data->peple  = implode(";",array_column($teamUser->toArray(), 'name'));
                $data->major  = implode(";",array_column($major, 'name'));
            }
            $data->d_patch   = $data->d_patch == 1?'是':'否';
            $data->one_mode  = $data->one_mode == '01'?'非现场':'现场';
            //$data->activity  = $data->audit_phase?$state->first()['activity']:null;
            $data->activity  = $data->audit_phase?$data->audit_phase:null;
            $data->rzbz      = $data->p_rzbz?$data->p_rzbz:$data->rzbz;
            $data->major_code= $data->p_major_code?$data->p_major_code:$data->major_code;
            $data->rev_range = $data->p_rev_range?$data->p_rev_range:$data->rev_range;
            $data->cbt_type  = $data->p_cbt_type?$data->p_cbt_type:$data->cbt_type;
            $data->bd_degree = $data->p_bd_degree?$data->p_bd_degree:$data->bd_degree;
            $data->cer_type  = $data->p_cer_type?$data->p_cer_type:$data->cer_type;
            $data->regt_numb = $data->p_regt_numb?$data->p_regt_numb:$data->regt_numb;
            switch($data->cer_type){
                case '01':
                    $data->cer_type = 'CNAS' ;
                    break;
                case '02':
                    $data->cer_type = 'UKAS' ;
                    break;
                case '03':
                    $data->cer_type = 'JAS-ANS' ;
                    break;
                case '00':
                    $data->cer_type = 'ETC' ;
                    break;
            }
/*            $unionWhere = array(
                ['code','=',$data->cbt_type],
                ['state','=',1]
            );
            $union = SystemUnion::IndexUnion('union',$unionWhere);
            $data->union = $union->first()['union'];*/
        }
        return response()->json(['status'=>100,'msg'=>'请求成功','data'=>$flighs]);
    }

    /**计划导出**/
    public function DispatchExport(){
        return response()->json(['status'=>100,'msg'=>'请求成功']);
    }

    /**阶段添加**/
    public function DispatchStage(Request $request){
        $stage = ['04','08'];
        if(!in_array($request->code,$stage)){
            $where = array(
                ['xm_id','=',$request->id],
                ['audit_phase','=',$request->code],
            );
            $count =  InspectPlan::where($where)->count();
            if($count != 0){
                return response()->json(['status'=>101,'msg'=>'该阶段已存在']);
            }
        }
        $flights = new InspectPlan;
        $flights->xm_id  = $request->id;
        $flights->audit_phase  = $request->code;
        $flights->plan_m  = 1;
        if(!$flights->save()){
            return response()->json(['status'=>101,'msg'=>'保存失败']);
        }
        return response()->json(['status'=>100,'msg'=>'添加成功']);
    }

    /**结合审核**/
    public function DispatchUnion(Request $request){
        $uniId = explode(";",$request->union_cmpy);
        $where[][] = ['rept_sbmt','=',0];
        $where[][] = ['rept_sbmt','=',3];
        $where[][] = ['dp_sbmt','=',0];
        $count = InspectPlan::whereIn('id',$uniId)
            ->where(function ($query) use ($where) {
                foreach($where as $vel){
                    $query->orWhere($vel);
                }
            })
            ->count();
        if($count == 0){
            return response()->json(['status'=>101,'msg'=>'所选项目中有已提交/已上报']);
        }
        $data = array(
            'union_cmpy' => $request->union_cmpy,
            'union_time' => date('Y-m-d')
        );
        $flight= InspectPlan::whereIn('id',$uniId)
            ->update($data);
        if($flight == 0){
            return response()->json(['status'=>101,'msg'=>'结合审核添加失败']);
        }
        return response()->json(['status'=>100,'msg'=>'结合审核添加成功']);
    }

    /**结合取消**/
    public function DispatchCancel(Request $request){
        $uniId = explode(";",$request->union_cmpy);
        $where[][] = ['rept_sbmt','=',0];
        $where[][] = ['rept_sbmt','=',3];
        $where[][] = ['dp_sbmt','=',0];
        $count = InspectPlan::whereIn('id',$uniId)
            ->where(function ($query) use ($where) {
                foreach($where as $vel){
                    $query->orWhere($vel);
                }
            })
            ->count();
        if($count == 0){
            return response()->json(['status'=>101,'msg'=>'所选项目中有已提交/已上报']);
        }
        $key = array_search($request->union_id, $uniId);
        if ($key !== false){
            unset($uniId[$key]);
        }
        $union_cmpy = implode(",", $uniId);
        $data = array(
            'union_cmpy' => $union_cmpy,
        );
        DB::beginTransaction();
        try {
            $flight = InspectPlan::whereIn('id',$uniId)
                ->update($data);
            if($flight == 0){
                return response()->json(['status'=>101,'msg'=>'取消结合审核失败']);
            }
            $flighs = InspectPlan::find($request->union_id);
            $flighs->union_cmpy = null;
            $flighs->union_time = null;
            if(!$flighs->save()){
                return response()->json(['status'=>101,'msg'=>'取消结合审核失败']);
            }
            DB::commit();
        }catch (\Exception $e){
            DB::rollback();
            return response()->json(['status'=>101,'msg'=>'取消结合审核失败']);
        }
        return response()->json(['status'=>100,'msg'=>'取消结合审核成功']);
    }

    /**项目安排**/
    public function DispatchProject(Request $request){
        $file = array(
            'qyht_htrz.id as xid',
            'qyht_htrza.id as did',
            'xmbh',
            'rztx',
            'rzbz',
            'p_rzbz',
            'activity',
            'major_code',
            'p_major_code',
            'cer_type',
            'p_cer_type',
            'field_day',
            'start_time',
            'end_time',
            'rept_sbmt',
            'user_state',
            'rept_sbmt',
            'dp_jdct',
        );
        if(!$request->union_cmpy){
            $where = array(
                ['ht_id','=',$request->hid],
                ['audit_phase','=',$request->code],
            );
            $flighs = InspectPlan::PhasePlan($file,$where);
        }else{
            $cmpy = explode(";",$request->union_cmpy);
            $flighs = InspectPlan::UnionPlan($file,$cmpy);
        }
        if($flighs->isEmpty()){
            return response()->json(['status'=>101,'msg'=>'无数据']);
        }
        $fligh = $flighs->toArray();
        array_walk($fligh, function (&$value,$key) {
            $value['rzbz'] = $value['p_rzbz']?$value['p_rzbz']:$value['rzbz'];
            $value['major_code'] = $value['p_major_code']?$value['p_major_code']:$value['major_code'];
            $value['cer_type'] = $value['p_cer_type']?$value['p_cer_type']:$value['cer_type'];
        });
        return response()->json(['status'=>100,'msg'=>'请求成功','data'=>$fligh]);
    }

    /**项目人员**/
    public function DispatchUser(Request $request){
        //DB::connection()->enableQueryLog();#开启执行日志
        $rztx = $request->rztx;
        $flighs = InspectAuditTeam::with([
                'userBasic' => function ($query){
                    $query->select('id','name','nmbe_et','telephone','type','region');
                },
                'userCtfcate' => function ($query){
                    $query->select('code','qlfts');
                },
                'userBasic.userType' => function ($query) use ($rztx){
                    $query->where('rgt_type', '=',$rztx);
                    $query->select('us_id','regter_et','group_abty','witn_abty','turn_version');
                },
                'userBasic.marketRegion' => function ($query){
                $query->select('id','fz_at');
                }])
            ->where([
                ['ap_id','=',$request->id],
            ])
            ->select('id as aid','us_id','type_qlfts','role','trial','witic','mjexm','witic_type','witic_ctgy','m_code','clause','zynl','shtd','contents')
            ->get();
        //dump(DB::getQueryLog());
        if($flighs->isEmpty()){
            return response()->json(['status'=>101,'msg'=>'无数据']);
        }
        $flighs = $flighs->toArray();
        array_walk($flighs, function ($value,$key) use (&$flight){
            $data['aid']    = $value['aid'];
            $data['us_id']  = $value['us_id'];
            $data['type_qlfts'] = $value['type_qlfts'];
            $data['role']   = $value['role'];
            $data['trial']  = $value['trial'];
            $data['witic']  = $value['witic'];
            $data['mjexm']  = $value['mjexm'];
            $data['witic_type']  = $value['witic_type'];
            $data['witic_ctgy']  = $value['witic_ctgy'];
            $data['m_code'] = $value['m_code'];
            $data['clause'] = json_decode($value['clause'],true);
            $data['zynl'] = $value['zynl'];
            $data['shtd'] = $value['shtd'];
            $data['contents'] = $value['contents'];
            $data['name']   = $value['user_basic']['name'];
            $data['nmbe_et'] = $value['user_basic']['nmbe_et'];
            $data['telephone'] = $value['user_basic']['telephone'];
            $data['type'] = $value['user_basic']['type'];
            $data['regter_et'] = $value['user_basic']['user_type'][0]['regter_et'];
            $data['group_abty']= $value['user_basic']['user_type'][0]['group_abty'];
            $data['witn_abty'] = $value['user_basic']['user_type'][0]['witn_abty'];
            $data['turn_version'] = $value['user_basic']['user_type'][0]['turn_version'];
            $data['fz_at'] = $value['user_basic']['market_region']['fz_at'];
            $data['qlfts'] = $value['user_ctfcate']['qlfts'];
            $data = UserBasic::codeSwitch($data);
            $flight[] = $data;
        });
        $flighs = $flight;
        return response()->json(['status'=>100,'msg'=>'请求成功','data'=>$flighs]);
    }

    /**人员修改**/
    public function UserEdit(Request $request){
        if($request->rept_sbmt ==0 || $request->dp_jdct == 1){
            $flights = InspectAuditTeam::find($request->id);
            $flights->role  = $request->role;
            $flights->trial = $request->trial;
            $flights->witic = $request->witic;
            $flights->mjexm = $request->mjexm;
            $flights->witic_type = $request->type;
            $flights->witic_ctgy = $request->ctgy;
            if($request->dp_jdct == 1){
                $flights->dp_jdct    = 0;
                $flighs = ExamineSystem::find($flights->xm_id);
                if($flights->audit_phase == '0101' || $flights->audit_phase == '0201'){
                    if($flighs->one_mode == '01'){
                        $flights->rept_sbmt  = 1;
                    }else{
                        $flights->rept_sbmt  = 2;
                    }
                }else{
                    $flights->rept_sbmt  = 2;
                }
            }
            if($flights->save()){
                return response()->json(['status'=>100,'msg'=>'修改成功','data'=>$flights]);
            }else{
                return response()->json(['status'=>101,'msg'=>'修改失败']);
            }
        }else{
            return response()->json(['status'=>101,'msg'=>'该项目已上报，不能修改']);
        }
    }

    /**人员删除**/
    public function UserDelete(Request $request){
        $flights = InspectAuditTeam::find($request->id);
        $flight  = InspectPlan::find($flights->ap_id);
        if($flight->rept_sbmt == 0 || $flight->dp_jdct == 1){
            if($flights->delete()){
                return response()->json(['status'=>100,'msg'=>'删除成功']);
            }else{
                return response()->json(['status'=>101,'msg'=>'删除失败']);
            }
        }else{
            return response()->json(['status'=>101,'msg'=>'该项目已上报，不能删除']);
        }
    }

    /**组长匹配**/
    public function DispatchGroup(Request $request){
        switch($request->type){
            case '01':
                $type = 1;
                break;
            case '02':
                $type = 2;
                break;
            case '03':
                $type = 3;
                break;
            case '00':
                $type = 1;
                break;
        }
        switch($request->rztx){
            case 'QMS':
                $where = array(
                    ['rgt_type','=',$request->rztx],
                    ['major_m','=',$type],
                    ['stop','=',1],
                    ['group_abty','=','1'],
                    ['qlfts_state','=','01'],
                    ['major_statee','=','01']
                );
                if(!$request->major){
                    return response()->json(['status'=>101,'msg'=>'专业代码为空']);
                }
                $major  = explode(";",$request->major);
                $flight = UserBasic::RoutineGroup($where,$major);
                $flight = $this->RoutineGroup($flight);
                break;
            case 'ISMS':
                $where = array(
                    ['rgt_type','=',$request->rztx],
                    ['major_m','=',$type],
                    ['stop','=',1],
                    ['group_abty','=','1'],
                    ['qlfts_state','=','01'],
                    ['major_statee','=','01']
                );
                if(!$request->major){
                    return response()->json(['status'=>101,'msg'=>'专业代码为空']);
                }
                $major  = explode(";",$request->major);
                $flight = UserBasic::RoutineGroup($where,$major);
                $flight = $this->RoutineGroup($flight);
                break;
            case 'EMS':
                $where = array(
                    ['rgt_type','=',$request->rztx],
                    ['major_m','=',$type],
                    ['stop','=',1],
                    ['group_abty','=','1'],
                    ['qlfts_state','=','01'],
                    ['major_statee','=','01']
                );
                if(!$request->major){
                    return response()->json(['status'=>101,'msg'=>'专业代码为空']);
                }
                $major  = explode(";",$request->major);
                $flight = UserBasic::RoutineGroup($where,$major);
                $flight = $this->RoutineGroup($flight);
                break;
            case 'EC':
                $where = array(
                    ['rgt_type','=',$request->rztx],
                    ['major_m','=',$type],
                    ['stop','=',1],
                    ['group_abty','=','1'],
                    ['qlfts_state','=','01'],
                    ['major_statee','=','01']
                );
                if(!$request->major){
                    return response()->json(['status'=>101,'msg'=>'专业代码为空']);
                }
                $major  = explode(";",$request->major);
                $flight = UserBasic::RoutineGroup($where,$major);
                $flight = $this->RoutineGroup($flight);;
                break;
            case 'OHSMS':
                $where = array(
                    ['rgt_type','=',$request->rztx],
                    ['major_m','=',$type],
                    ['stop','=',1],
                    ['group_abty','=','1'],
                    ['qlfts_state','=','01'],
                    ['major_statee','=','01']
                );
                if(!$request->major){
                    return response()->json(['status'=>101,'msg'=>'专业代码为空']);
                }
                $major  = explode(";",$request->major);
                $flight = UserBasic::RoutineGroup($where,$major);
                $flight = $this->RoutineGroup($flight);
                break;
            case 'BCMS':
                $where = array(
                    ['rgt_type','=',$request->rztx],
                    ['major_m','=',$type],
                    ['stop','=',1],
                    ['group_abty','=','1'],
                    ['qlfts_state','=','01'],
                    ['major_statee','=','01']
                );
                if(!$request->major){
                    return response()->json(['status'=>101,'msg'=>'专业代码为空']);
                }
                $major  = explode(";",$request->major);
                $flight = UserBasic::RoutineGroup($where,$major);
                $flight = $this->RoutineGroup($flight);
                break;
            case 'SCSMS':
                $where = array(
                    ['rgt_type','=',$request->rztx],
                    ['major_m','=',$type],
                    ['stop','=',1],
                    ['group_abty','=','1'],
                    ['qlfts_state','=','01'],
                    ['major_statee','=','01']
                );
                if(!$request->major){
                    return response()->json(['status'=>101,'msg'=>'专业代码为空']);
                }
                $major  = explode(";",$request->major);
                $flight = UserBasic::RoutineGroup($where,$major);
                $flight = $this->RoutineGroup($flight);
                break;
            case 'ECPSC':
                $where = array(
                    ['rgt_type','=',$request->rztx],
                    ['major_m','=',$type],
                    ['stop','=',1],
                    ['group_abty','=','1'],
                    ['qlfts_state','=','01'],
                    ['major_statee','=','01']
                );
                if(!$request->major){
                    return response()->json(['status'=>101,'msg'=>'专业代码为空']);
                }
                $major  = explode(";",$request->major);
                $flight = UserBasic::RoutineGroup($where,$major);
                $flight = $this->RoutineGroup($flight);
                break;
/*                $where = array(
                    ['rgt_type','=',$request->rztx],
                    ['stop','<>',3],
                    ['group_abty','=','1'],
                    ['qlfts_state','=','01'],
                );
                $flight = UserBasic::ServiceGroup($where);
                $flight = $this->ServiceGroup($flight);
                break;*/
            case '养老服务':
                $where = array(
                    ['rgt_type','=',$request->rztx],
                    ['major_m','=',$type],
                    ['stop','<>',3],
                    ['group_abty','=','1'],
                    ['qlfts_state','=','01'],
                    ['major_statee','=','01']
                );
                if(!$request->major){
                    return response()->json(['status'=>101,'msg'=>'专业代码为空']);
                }
                $major  = explode(";",$request->major);
                $flight = UserBasic::RoutineGroup($where,$major);
                $flight = $this->RoutineGroup($flight);
                break;
            case '物业服务':
                $where = array(
                    ['rgt_type','=',$request->rztx],
                    ['major_m','=',$type],
                    ['stop','=',1],
                    ['group_abty','=','1'],
                    ['qlfts_state','=','01'],
                    ['major_statee','=','01']
                );
                if(!$request->major){
                    return response()->json(['status'=>101,'msg'=>'专业代码为空']);
                }
                $major  = explode(";",$request->major);
                $flight = UserBasic::RoutineGroup($where,$major);
                $flight = $this->RoutineGroup($flight);
                break;
            case '收费或合同基础上的生产服务':
                $where = array(
                    ['rgt_type','=',$request->rztx],
                    ['major_m','=',$type],
                    ['stop','=',1],
                    ['group_abty','=','1'],
                    ['qlfts_state','=','01'],
                    ['major_statee','=','01']
                );
                if(!$request->major){
                    return response()->json(['status'=>101,'msg'=>'专业代码为空']);
                }
                $major  = explode(";",$request->major);
                $flight = UserBasic::RoutineGroup($where,$major);
                $flight = $this->RoutineGroup($flight);
                break;
            case 'IPT':
                $where = array(
                    ['rgt_type','=',$request->rztx],
                    ['stop','=',1],
                    ['group_abty','=','1'],
                    ['qlfts_state','=','01'],
                );
                $flight = UserBasic::ServiceGroup($where);
                $flight = $this->ServiceGroup($flight);
                break;
            case 'EQC':
                $where = array(
                    ['rgt_type','=',$request->rztx],
                    ['stop','=',1],
                    ['group_abty','=','1'],
                    ['qlfts_state','=','01'],
                );
                $flight = UserBasic::ServiceGroup($where);
                $flight = $this->ServiceGroup($flight);
                break;
            case 'CMS':
                $where = array(
                    ['rgt_type','=',$request->rztx],
                    ['stop','=',1],
                    ['group_abty','=','1'],
                    ['qlfts_state','=','01'],
                );
                $flight = UserBasic::ServiceGroup($where);
                $flight = $this->ServiceGroup($flight);
                break;
            case 'AMS':
                $where = array(
                    ['rgt_type','=',$request->rztx],
                    ['stop','=',1],
                    ['group_abty','=','1'],
                    ['qlfts_state','=','01'],
                );
                $flight = UserBasic::ServiceGroup($where);
                $flight = $this->ServiceGroup($flight);
                break;
            case 'IECE':
                $where = array(
                    ['rgt_type','=',$request->rztx],
                    ['stop','<>',3],
                    ['group_abty','=','1'],
                    ['qlfts_state','=','01'],
                );
                $flight = UserBasic::ServiceGroup($where);
                $flight = $this->ServiceGroup($flight);
                break;
            case 'EIMS':
                $where = array(
                    ['rgt_type','=',$request->rztx],
                    ['stop','<>',3],
                    ['group_abty','=','1'],
                    ['qlfts_state','=','01'],
                );
                $flight = UserBasic::ServiceGroup($where);
                $flight = $this->ServiceGroup($flight);
                break;
            case 'SA8000':
                $where = array(
                    ['rgt_type','=',$request->rztx],
                    ['stop','<>',3],
                    ['group_abty','=','1'],
                    ['qlfts_state','=','01'],
                );
                $flight = UserBasic::ServiceGroup($where);
                $flight = $this->ServiceGroup($flight);
                break;
            case 'HSE':
                $where = array(
                    ['rgt_type','=',$request->rztx],
                    ['major_m','=',$type],
                    ['stop','<>',3],
                    ['group_abty','=','1'],
                    ['qlfts_state','=','01'],
                    ['major_statee','=','01']
                );
                if(!$request->major){
                    return response()->json(['status'=>101,'msg'=>'专业代码为空']);
                }
                $major  = explode(";",$request->major);
                $flight = UserBasic::RoutineGroup($where,$major);
                $flight = $this->RoutineGroup($flight);
                break;
            case 'YY':
                $where = array(
                    ['rgt_type','=',$request->rztx],
                    ['major_m','=',$type],
                    ['stop','<>',3],
                    ['group_abty','=','1'],
                    ['qlfts_state','=','01'],
                    ['major_statee','=','01']
                );
                if(!$request->major){
                    return response()->json(['status'=>101,'msg'=>'专业代码为空']);
                }
                $major  = explode(";",$request->major);
                $flight = UserBasic::RoutineGroup($where,$major);
                $flight = $this->RoutineGroup($flight);
                break;
            case 'HACCP':
                $where = array(
                    ['rgt_type','=',$request->rztx],
                    ['major_m','=',$type],
                    ['stop','<>',3],
                    ['group_abty','=','1'],
                    ['qlfts_state','=','01'],
                    ['major_statee','=','01']
                );
                if(!$request->major){
                    return response()->json(['status'=>101,'msg'=>'专业代码为空']);
                }
                $major  = explode(";",$request->major);
                $flight = UserBasic::RoutineGroup($where,$major);
                $flight = $this->RoutineGroup($flight);
                break;
            case 'FSMS':
                $where = array(
                    ['rgt_type','=',$request->rztx],
                    ['major_m','=',$type],
                    ['stop','<>',3],
                    ['group_abty','=','1'],
                    ['qlfts_state','=','01'],
                    ['major_statee','=','01']
                );
                if(!$request->major){
                    return response()->json(['status'=>101,'msg'=>'专业代码为空']);
                }
                $major  = explode(";",$request->major);
                $flight = UserBasic::RoutineGroup($where,$major);
                $flight = $this->RoutineGroup($flight);
                break;
        }
        return($flight);
    }

    /**组员匹配**/
    public function DispatchMember(Request $request){
        switch($request->type){
            case '01':
                $type = 1;
                break;
            case '02':
                $type = 2;
                break;
            case '03':
                $type = 3;
                break;
            case '00':
                $type = 1;
                break;
        }
        switch($request->rztx){
            case 'QMS':
                $where = array(
                    ['rgt_type','=',$request->rztx],
                    ['major_m','=',$type],
                    ['stop','<>',3],
                    ['group_abty','=','0'],
                    ['qlfts_state','=','01'],
                    ['qlfts_type','=',1],
                    ['major_statee','=','01']
                );
                if(!$request->major){
                    return response()->json(['status'=>101,'msg'=>'专业代码为空']);
                }
                $major  = explode(";",$request->major);
                $flight = UserBasic::RoutineGroup($where,$major);
                $flight = $this->RoutineGroup($flight);
                break;
            case 'EMS':
                $where = array(
                    ['rgt_type','=',$request->rztx],
                    ['major_m','=',$type],
                    ['stop','<>',3],
                    ['group_abty','=','0'],
                    ['qlfts_state','=','01'],
                    ['qlfts_type','=',1],
                    ['major_statee','=','01']
                );
                if(!$request->major){
                    return response()->json(['status'=>101,'msg'=>'专业代码为空']);
                }
                $major  = explode(";",$request->major);
                $flight = UserBasic::RoutineGroup($where,$major);
                $flight = $this->RoutineGroup($flight);
                break;
            case 'EC':
                $where = array(
                    ['rgt_type','=',$request->rztx],
                    ['major_m','=',$type],
                    ['stop','<>',3],
                    ['group_abty','=','0'],
                    ['qlfts_state','=','01'],
                    ['qlfts_type','=',1],
                    ['major_statee','=','01']
                );
                if(!$request->major){
                    return response()->json(['status'=>101,'msg'=>'专业代码为空']);
                }
                $major  = explode(";",$request->major);
                $flight = UserBasic::RoutineGroup($where,$major);
                $flight = $this->RoutineGroup($flight);;
                break;
            case 'OHSMS':
                $where = array(
                    ['rgt_type','=',$request->rztx],
                    ['major_m','=',$type],
                    ['stop','<>',3],
                    ['group_abty','=','0'],
                    ['qlfts_state','=','01'],
                    ['qlfts_type','=',1],
                    ['major_statee','=','01']
                );
                if(!$request->major){
                    return response()->json(['status'=>101,'msg'=>'专业代码为空']);
                }
                $major  = explode(";",$request->major);
                $flight = UserBasic::RoutineGroup($where,$major);
                $flight = $this->RoutineGroup($flight);
                break;
            case 'SCSMS':
                $where = array(
                    ['rgt_type','=',$request->rztx],
                    ['major_m','=',$type],
                    ['stop','<>',3],
                    ['group_abty','=','0'],
                    ['qlfts_state','=','01'],
                    ['qlfts_type','=',1],
                    ['major_statee','=','01']
                );
                if(!$request->major){
                    return response()->json(['status'=>101,'msg'=>'专业代码为空']);
                }
                $major  = explode(";",$request->major);
                $flight = UserBasic::RoutineGroup($where,$major);
                $flight = $this->RoutineGroup($flight);
                break;
            case 'ECPSC':
                $where = array(
                    ['rgt_type','=',$request->rztx],
                    ['major_m','=',$type],
                    ['stop','<>',3],
                    ['group_abty','=','0'],
                    ['qlfts_state','=','01'],
                    ['qlfts_type','=',1],
                    ['major_statee','=','01']
                );
                if(!$request->major){
                    return response()->json(['status'=>101,'msg'=>'专业代码为空']);
                }
                $major  = explode(";",$request->major);
                $flight = UserBasic::RoutineGroup($where,$major);
                $flight = $this->RoutineGroup($flight);
                break;
            case '养老服务':
                $where = array(
                    ['rgt_type','=',$request->rztx],
                    ['major_m','=',$type],
                    ['stop','<>',3],
                    ['group_abty','=','0'],
                    ['qlfts_state','=','01'],
                    ['qlfts_type','=',1],
                    ['major_statee','=','01']
                );
                if(!$request->major){
                    return response()->json(['status'=>101,'msg'=>'专业代码为空']);
                }
                $major  = explode(";",$request->major);
                $flight = UserBasic::RoutineGroup($where,$major);
                $flight = $this->RoutineGroup($flight);
                break;
            case '物业服务':
                $where = array(
                    ['rgt_type','=',$request->rztx],
                    ['major_m','=',$type],
                    ['stop','<>',3],
                    ['group_abty','=','0'],
                    ['qlfts_state','=','01'],
                    ['qlfts_type','=',1],
                    ['major_statee','=','01']
                );
                if(!$request->major){
                    return response()->json(['status'=>101,'msg'=>'专业代码为空']);
                }
                $major  = explode(";",$request->major);
                $flight = UserBasic::RoutineGroup($where,$major);
                $flight = $this->RoutineGroup($flight);
                break;
            case 'IPT':
                $where = array(
                    ['rgt_type','=',$request->rztx],
                    ['stop','<>',3],
                    ['group_abty','=','0'],
                    ['qlfts_state','=','01'],
                    ['qlfts_type','=',1],
                );
                $flight = UserBasic::ServiceGroup($where);
                $flight = $this->ServiceGroup($flight);
                break;
            case 'CMS':
                $where = array(
                    ['rgt_type','=',$request->rztx],
                    ['stop','<>',3],
                    ['group_abty','=','0'],
                    ['qlfts_state','=','01'],
                    ['qlfts_type','=',1],
                );
                $flight = UserBasic::ServiceGroup($where);
                $flight = $this->ServiceGroup($flight);
                break;
            case 'IECE':
                $where = array(
                    ['rgt_type','=',$request->rztx],
                    ['stop','<>',3],
                    ['group_abty','=','0'],
                    ['qlfts_state','=','01'],
                    ['qlfts_type','=',1],
                );
                $flight = UserBasic::ServiceGroup($where);
                $flight = $this->ServiceGroup($flight);
                break;
            case 'EIMS':
                $where = array(
                    ['rgt_type','=',$request->rztx],
                    ['stop','<>',3],
                    ['group_abty','=','0'],
                    ['qlfts_state','=','01'],
                    ['qlfts_type','=',1],
                );
                $flight = UserBasic::ServiceGroup($where);
                $flight = $this->ServiceGroup($flight);
                break;
            case 'SA8000':
                $where = array(
                    ['rgt_type','=',$request->rztx],
                    ['stop','<>',3],
                    ['group_abty','=','0'],
                    ['qlfts_state','=','01'],
                    ['qlfts_type','=',1],
                );
                $flight = UserBasic::ServiceGroup($where);
                $flight = $this->ServiceGroup($flight);
                break;
            case 'HSE':
                $where = array(
                    ['rgt_type','=',$request->rztx],
                    ['stop','<>',3],
                    ['group_abty','=','0'],
                    ['qlfts_state','=','01'],
                    ['qlfts_type','=',1],
                    ['major_statee','=','01']
                );
                if(!$request->major){
                    return response()->json(['status'=>101,'msg'=>'专业代码为空']);
                }
                $major  = explode(";",$request->major);
                $flight = UserBasic::RoutineGroup($where,$major);
                $flight = $this->RoutineGroup($flight);
                break;
            case 'YY':
                $where = array(
                    ['rgt_type','=',$request->rztx],
                    ['major_m','=',$type],
                    ['stop','<>',3],
                    ['group_abty','=','0'],
                    ['qlfts_state','=','01'],
                    ['qlfts_type','=',1],
                    ['major_statee','=','01']
                );
                if(!$request->major){
                    return response()->json(['status'=>101,'msg'=>'专业代码为空']);
                }
                $major  = explode(";",$request->major);
                $flight = UserBasic::RoutineGroup($where,$major);
                $flight = $this->RoutineGroup($flight);
                break;
            case 'HACCP':
                $where = array(
                    ['rgt_type','=',$request->rztx],
                    ['major_m','=',$type],
                    ['stop','<>',3],
                    ['group_abty','=','0'],
                    ['qlfts_state','=','01'],
                    ['qlfts_type','=',1],
                    ['major_statee','=','01']
                );
                if(!$request->major){
                    return response()->json(['status'=>101,'msg'=>'专业代码为空']);
                }
                $major  = explode(";",$request->major);
                $flight = UserBasic::RoutineGroup($where,$major);
                $flight = $this->RoutineGroup($flight);
                break;
            case 'FSMS':
                $where = array(
                    ['rgt_type','=',$request->rztx],
                    ['major_m','=',$type],
                    ['stop','<>',3],
                    ['group_abty','=','0'],
                    ['qlfts_state','=','01'],
                    ['qlfts_type','=',1],
                    ['major_statee','=','01']
                );
                if(!$request->major){
                    return response()->json(['status'=>101,'msg'=>'专业代码为空']);
                }
                $major  = explode(";",$request->major);
                $flight = UserBasic::RoutineGroup($where,$major);
                $flight = $this->RoutineGroup($flight);
                break;
        }
        return($flight);
    }

    /**人员匹配(常规体系QES/EMS/OHSMS/EC)**/
    public function RoutineGroup($flight)
    {
        if ($flight->isEmpty()) {
            return response()->json(['status' => 101, 'msg' => '无数据']);
        }
        $flight = $flight->toArray();
        array_walk($flight, function (&$value, $key) use (&$flighs){
            $value = UserBasic::codeSwitch($value);
            if(!isset($flighs[$value['uid']])){
                $flighs[$value['uid']]=$value;
            }else{
                $flighs[$value['uid']]['major_code'].=';'.$value['major_code'];
            }

        });
        return response()->json(['status' => 100, 'msg' => '请求成功', 'data' => array_values($flighs)]);
    }

    /**人员匹配(服务认证ECPSC/养老服务)**/
    public function ServiceGroup($flight)
    {
        if ($flight->isEmpty()) {
            return response()->json(['status' => 101, 'msg' => '无数据']);
        }
        $flight = $flight->toArray();
        array_walk($flight, function (&$value, $key){
            $value = UserBasic::codeSwitch($value);
        });
        return response()->json(['status' => 100, 'msg' => '请求成功', 'data' => $flight]);
    }

    /**时间验证**/
    public function TimeMatch(Request $request){
        if(!$request->uid){
            return response()->json(['status'=>101,'msg'=>'人员ID为空']);
        }
        if(!$request->stime){
            return response()->json(['status'=>101,'msg'=>'开始时间为空']);
        }
        if(!$request->etime){
            return response()->json(['status'=>101,'msg'=>'结束时间为空']);
        }
        $where  = array(
            ['us_id','=',$request->uid],
        );
        $orWhere[] = array(
            ['start_time','>=',$request->stime],
            ['start_time','<=',$request->etime]
        );
        $orWhere[] = array(
            ['end_time','>=',$request->stime],
            ['end_time','<=',$request->etime]
        );
        $orWhere[] = array(
            ['start_time','<=',$request->stime],
            ['end_time','>=',$request->etime]
        );
        //DB::enableQueryLog();
        $flighs = InspectPlan::TimePlan($where,$orWhere);
        //dump(DB::getQueryLog());die;
        if($flighs->isEmpty()){
            return response()->json(['status'=>100,'msg'=>'匹配成功']);
        }
        if($flighs->first()->id != $request->id){
            return response()->json(['status'=>101,'msg'=>'该人员已安排其他项目']);
        }
        return response()->json(['status'=>100,'msg'=>'匹配成功']);
    }

    /**人员查询**/
    public function UserQuery(Request $request){
        $where = array(
            ['na_code','=',$request->code],
            ['rgt_type','=',$request->rztx],
            ['stop','<>',3],
            ['qlfts_state','=','01'],
            ['qlfts_type','=',1]
        );
        $flight = UserBasic::ServiceGroup($where);
        if($flight->isEmpty()){
            return response()->json(['status'=>101,'msg'=>'无数据']);
        }
        $flight = $flight->toArray();
        array_walk($flight, function (&$value, $key){
            $value = UserBasic::codeSwitch($value);
        });
        switch($request->rztx){
            case 'QMS':
                if(!$request->major){
                    $flight = response()->json(['status' => 100, 'msg' => '请求成功', 'data' => $flight]);
                }else{
                    $flight = $this->RoutineQuery($request->major,$flight,$request->type);
                }
                break;
            case 'EMS':
                if(!$request->major){
                    $flight = response()->json(['status' => 100, 'msg' => '请求成功', 'data' => $flight]);
                }else{
                    $flight = $this->RoutineQuery($request->major,$flight,$request->type);
                }
                break;
            case 'EC':
                if(!$request->major){
                    $flight = response()->json(['status' => 100, 'msg' => '请求成功', 'data' => $flight]);
                }else{
                    $flight = $this->RoutineQuery($request->major,$flight,$request->type);
                }
                break;
            case 'OHSMS':
                if(!$request->major){
                    $flight = response()->json(['status' => 100, 'msg' => '请求成功', 'data' => $flight]);
                }else{
                    $flight = $this->RoutineQuery($request->major,$flight,$request->type);
                }
                break;
            case 'SCSMS':
                if(!$request->major){
                    $flight = response()->json(['status' => 100, 'msg' => '请求成功', 'data' => $flight]);
                }else{
                    $flight = $this->RoutineQuery($request->major,$flight,$request->type);
                }
                break;
            case 'ECPSC':
                if(!$request->major){
                    $flight = response()->json(['status' => 100, 'msg' => '请求成功', 'data' => $flight]);
                }else{
                    $flight = $this->RoutineQuery($request->major,$flight,$request->type);
                }
                break;
            case '养老服务':
                if(!$request->major){
                    $flight = response()->json(['status' => 100, 'msg' => '请求成功', 'data' => $flight]);
                }else{
                    $flight = $this->RoutineQuery($request->major,$flight,$request->type);
                }
                break;
            case '物业服务':
                if(!$request->major){
                    $flight = response()->json(['status' => 100, 'msg' => '请求成功', 'data' => $flight]);
                }else{
                    $flight = $this->RoutineQuery($request->major,$flight,$request->type);
                }
                break;
            case 'IPT':
                $flight = response()->json(['status' => 100, 'msg' => '请求成功', 'data' => $flight]);
                break;
            case 'CMS':
                $flight = response()->json(['status' => 100, 'msg' => '请求成功', 'data' => $flight]);
                break;
            case 'IECE':
                $flight = response()->json(['status' => 100, 'msg' => '请求成功', 'data' => $flight]);
                break;
            case 'EIMS':
                $flight = response()->json(['status' => 100, 'msg' => '请求成功', 'data' => $flight]);
                break;
            case 'SA8000':
                $flight = response()->json(['status' => 100, 'msg' => '请求成功', 'data' => $flight]);
                break;
            case 'HSE':
                if(!$request->major){
                    $flight = response()->json(['status' => 100, 'msg' => '请求成功', 'data' => $flight]);
                }else{
                    $flight = $this->RoutineQuery($request->major,$flight,$request->type);
                }
                break;
            case 'YY':
                if(!$request->major){
                    $flight = response()->json(['status' => 100, 'msg' => '请求成功', 'data' => $flight]);
                }else{
                    $flight = $this->RoutineQuery($request->major,$flight,$request->type);
                }
                break;
            case 'HACCP':
                if(!$request->major){
                    $flight = response()->json(['status' => 100, 'msg' => '请求成功', 'data' => $flight]);
                }else{
                    $flight = $this->RoutineQuery($request->major,$flight,$request->type);
                }
                break;
            case 'FSMS':
                if(!$request->major){
                    $flight = response()->json(['status' => 100, 'msg' => '请求成功', 'data' => $flight]);
                }else{
                    $flight = $this->RoutineQuery($request->major,$flight,$request->type);
                }
                break;
        }
        return($flight);
    }

    /**评定人员匹配(常规体系QES/EMS/OHSMS/EC)**/
    protected function RoutineQuery($major,$flight,$type)
    {
        $major = explode(";", $major);
        switch ($type) {
            case '01':
                $type = 1;
                break;
            case '02':
                $type = 2;
                break;
            case '03':
                $type = 3;
                break;
            case '00':
                $type = 1;
                break;
        }
        $data = array(
            'major'=> $major,
            'type' => $type
        );
        //$flight = $flight->toArray();
        array_walk($flight, function (&$value, $key,$data){
            $where = array(
                ['m_id', '=',$value['mid']],
                ['major_m', '=',$data['type']],
                ['major_statee', '=', '01']
            );
            $file = array(
                'major_code',
            );
            $userMajor = UserMajor::UserMajor($file, $where,$data['major']);
            if ($userMajor->isEmpty()){
                $value['major_code'] = '';
            }else{
                $value['major_code'] = implode(";", array_column($userMajor->toArray(), 'major_code'));
            }
        },$data);

        return response()->json(['status' => 100, 'msg' => '请求成功', 'data' => $flight]);
    }

    /**人员保存**/
    public function UserAdd(Request $request){
        $flights = InspectPlan::find($request->did);
        if($flights->rept_sbmt == 0 || $flights->dp_jdct == 1){
            $data = json_decode($request->data,true );
            $flight = array();
            array_walk($data, function ($value,$key,$id) use (&$flight) {
                if(!isset($value['code'])){
                    $value['code'] = '';
                }
                if($value['aid'] == 0){
                    $flight['add'][] = array(
                        'ap_id' => $id,
                        'us_id' => $value['uid'],
                        'role'  => $value['role'],
                        'trial' => $value['trial'],
                        'witic' => $value['witic'],
                        'mjexm' => $value['mjexm'],
                        'witic_type' => $value['type'],
                        'witic_ctgy' => $value['ctgy'],
                        'm_code'=> $value['code'],
                        'type_qlfts' => $value['qlfts'],
                    );
                }else{
                    $flight['edit'][] = array(
                        'id'    => $value['aid'],
                        'ap_id' => $id,
                        'us_id' => $value['uid'],
                        'role'  => $value['role'],
                        'trial' => $value['trial'],
                        'witic' => $value['witic'],
                        'mjexm' => $value['mjexm'],
                        'witic_type' => $value['type'],
                        'witic_ctgy' => $value['ctgy'],
                        'm_code'=> $value['code'],
                        'type_qlfts' => $value['qlfts'],
                    );
                }
            },$request->did);
            DB::beginTransaction();
            try {
                if(!empty($flight['add'])){
                    $flighta = InspectAuditTeam::insert($flight['add']);
                }
                if(!empty($flight['edit'])){
                    $flighte = InspectAuditTeam::UpdateBatch($flight['edit']);
                }
                if($flighta == 0 && $flighte == 0){
                    return response()->json(['status'=>101,'msg'=>'人员安排失败']);
                }
                //$flights = InspectPlan::find($request->did);
                $flights->user_state = 1;
                if($request->dp_jdct == 1){
                    $flights->dp_jdct    = 0;
                    $flights->rept_sbmt  = 2;
                }
                if(!$flights->save()){
                    return response()->json(['status' => 101, 'msg' => '人员安排失败']);
                }
                DB::commit();
            }catch (\Exception $e){
                DB::rollback();
                return response()->json(['status'=>101,'msg'=>'人员安排失败']);
            }
            return response()->json(['status'=>100,'msg'=>'人员安排成功']);
        }else{
            return response()->json(['status'=>101,'msg'=>'该项目已上报，如需修改请发起审批申请权限']);
        }
    }

    /**项目计划详情**/
    public function DispatchDetail(Request $request){
        $where  = array(
            ['qyht_htrza.id','=',$request->id],
        );
        $file = array(
            'qyht.kh_id as id',
            'qyht.id as hid',
            'qyht_htrz.id as xid',
            'qyht_htrza.id as did',
            'xmbh',
            'htbh',
            'audit_phase',
            'cbt_type',
            'p_cbt_type',
            'bd_degree',
            'p_bd_degree',
            'field_day',
            'repor_day',
            'start_time',
            'end_time',
            'plan_time',
            'tmpy_site',
            'p_tmpy_site',
            'actual_s',
            'actual_e',
            'rwmas_t',
            'not_serious',
            'serious',
            'dp_time',
            'rz_bz',
            'p_rz_bz',
            'rept_sbmt',
            'dp_jdct',
            'dp_result',
            'dp_sbmt',
            'user_state',
            'dp_pret',
        );
        $flighs = MarketContract::DispatchDetail($file,$where);
        if($flighs->isEmpty()){
            return response()->json(['status'=>101,'msg'=>'无数据']);
        }
        $stateWhere = array(
            ['code','=',$flighs->first()->audit_phase],
            ['state','=',1]
        );
        $state = SystemState::IndexState('activity',$stateWhere);
        $flighs->first()->audit_phase= $state->first()->activity;
        $flighs->first()->cbt_type  = $flighs->first()->p_cbt_type?$flighs->first()->p_cbt_type:$flighs->first()->cbt_type;
        $flighs->first()->bd_degree = $flighs->first()->p_bd_degree?$flighs->first()->p_bd_degree:$flighs->first()->bd_degree;
        $flighs->first()->actual_s  = $flighs->first()->actual_s?$flighs->first()->actual_s:$flighs->first()->start_time;
        $flighs->first()->actual_e  = $flighs->first()->actual_e?$flighs->first()->actual_e:$flighs->first()->end_time;
        return response()->json(['status'=>100,'msg'=>'请求成功','data'=>$flighs]);
    }

    /**计划保存**/
    public function DispatchAdd(Request $request){
        $flights = InspectPlan::find($request->id);
        switch ($request->rept_sbmt)
        {
            case 0:
/*                if($flights->dp_pret == 1){
                    return response()->json(['status'=>101,'msg'=>'该计划已提交，如需修改请发起计划修改审批申请']);
                }*/
                $flights->field_day  = $request->scene;
                $flights->repor_day  = $request->report;
                $flights->p_cbt_type = $request->union;
                $flights->p_bd_degree= $request->degree;
                $flights->start_time = $request->start;
                $flights->end_time   = $request->end;
                $flights->plan_time  = $request->plan;
                $flights->p_tmpy_site= $request->address;
                $flights->p_rz_bz    = $request->remark;
                $flights->dp_user = Auth::guard('api')->user()->name;
                    $flights->dp_pret = 1;
                $tips = '计划安排';
                break;
            case 1:
                if($flights->dp_sbmt == 1 ){
                    return response()->json(['status'=>101,'msg'=>'该项目已提交，如需修改请发起审批申请权限']);
                }
                $flights->actual_s = $request->actual_s;
                $flights->actual_e = $request->actual_e;
                $flights->rwmas_t  = $request->rwmas_t;
                $flights->not_serious = $request->conform;
                $flights->serious  = $request->serious;
                $flights->p_rz_bz  = $request->remark;
                $flights->dp_time  = $request->time;
                $flights->dp_result= 1;
                $tips = '复核结果';
                break;
            default:
                return response()->json(['status'=>101,'msg'=>'保存失败']);
        }
        if($flights->save()){
            return response()->json(['status'=>100,'msg'=>$tips.'保存成功']);
        }else{
            return response()->json(['status'=>101,'msg'=>$tips.'保存失败']);
        }
    }

    /**计划修改**/
    public function DispatchEdit(Request $request){
        $flights = InspectPlan::find($request->id);
        switch ($flights->dp_jdct)
        {
            case 2://复核修改
                $flights->actual_s = $request->actual_s;
                $flights->actual_e = $request->actual_e;
                $flights->rwmas_t  = $request->rwmas_t;
                $flights->not_serious = $request->conform;
                $flights->serious  = $request->serious;
                $flights->p_rz_bz  = $request->remark;
                $flights->dp_time  = $request->time;
                $flights->dp_jdct  = 0;
                if($flights->audit_phase == '0101' || $flights->audit_phase == '0201'){
                    $flights->result_sbmt  = 1;
                }elseif($flights->result_sbmt == 3){
                    $flights->result_sbmt  = 2;
                }
                $tips = '复核结果';
                break;
            case 1://计划修改
                $flights->field_day  = $request->scene;
                $flights->repor_day  = $request->report;
                $flights->p_cbt_type = $request->union;
                $flights->p_bd_degree= $request->degree;
                $flights->start_time = $request->start;
                $flights->end_time   = $request->end;
                $flights->plan_time  = $request->plan;
                $flights->p_tmpy_site= $request->address;
                $flights->p_rz_bz    = $request->remark;
                $flights->dp_jdct    = 0;
                if($flights->audit_phase == '0101' || $flights->audit_phase == '0201'){
                    $flights->rept_sbmt  = 1;
                }else{
                    $flights->rept_sbmt  = 2;
                }
                $tips = '计划安排';
                break;
        }
        if($flights->save()){
            return response()->json(['status'=>100,'msg'=>$tips.'修改成功']);
        }
        return response()->json(['status'=>101,'msg'=>$tips.'修改失败']);
    }

    /**计划提交**/
    public function DispatchSubmit(Request $request){
        $flights = InspectPlan::find($request->id);
        switch ($flights->rept_sbmt)
        {
/*            case 0:
                if($flights->user_state != 1){
                    return response()->json(['status'=>101,'msg'=>'计划人员未保存']);
                }
                if($flights->dp_pret == 1){
                    return response()->json(['status'=>101,'msg'=>'该计划已提交，如需修改请发起计划修改审批申请']);
                }
                $flights->dp_user = Auth::guard('api')->user()->name;
                $flights->dp_pret = 1;
                $tips = '计划提交';
                break;*/
            case 1:
                if($flights->dp_result != 1){
                    return response()->json(['status'=>101,'msg'=>'复核结果未保存']);
                }
                if($flights->dp_sbmt == 1){
                    return response()->json(['status'=>101,'msg'=>'该项目已提交，如需修改请发起审批申请权限']);
                }
                $flights->dp_user  = Auth::guard('api')->user()->name;
                $flights->dp_sbmt = 1;
                $tips = '复核提交';
                break;
            default:
                return response()->json(['status'=>101,'msg'=>'参数缺失，保存失败']);
        }
        if($flights->save()){
            return response()->json(['status'=>100,'msg'=>$tips.'成功']);
        }else{
            return response()->json(['status'=>101,'msg'=>$tips.'失败']);
        }
    }


    /**模板匹配**/
    public function TemplateNumber(Request $request){
        $data = array();
        switch($request->rztx) {
            case 'QMS':
                if($request->stage == '0101' || $request->stage == '0201'){
                    $data[] = 'A-0101P';
                    $data[] = 'A-0201N';
                }else{
                    $data[] = 'A-0201P';
                    $data[] = 'A-0201N';
                }
                break;
            case 'EMS':
                if($request->stage == '0101' || $request->stage == '0201'){
                    $data[] = 'A-0101P';
                    $data[] = 'A-0201N';
                }else{
                    $data[] = 'A-0201P';
                    $data[] = 'A-0201N';
                }
                break;
            case 'EC':
                if($request->stage == '0101' || $request->stage == '0201'){
                    $data[] = 'A-0101P';
                    $data[] = 'A-0201N';
                }else{
                    $data[] = 'A-0201P';
                    $data[] = 'A-0201N';
                }
                break;
            case 'OHSMS':
                if($request->stage == '0101' || $request->stage == '0201'){
                    $data[] = 'A-0101P';
                    $data[] = 'A-0201N';
                }else{
                    $data[] = 'A-0201P';
                    $data[] = 'A-0201N';
                }
                break;
            case 'SA8000':
                if($request->stage == '0101' || $request->stage == '0201'){
                    $data[] = 'A-0101P';
                    $data[] = 'A-0201N';
                }else{
                    $data[] = 'A-0201P';
                    $data[] = 'A-0201N';
                }
                break;
            case 'EIMS':
                if($request->stage == '0101' || $request->stage == '0201'){
                    $data[] = 'A-0101P';
                    $data[] = 'A-0201N';
                }else{
                    $data[] = 'A-0201P';
                    $data[] = 'A-0201N';
                }
                break;
            case 'IECE':
                if($request->stage == '0101' || $request->stage == '0201'){
                    $data[] = 'A-0101P';
                    $data[] = 'A-0201N';
                }else{
                    $data[] = 'A-0201P';
                    $data[] = 'A-0201N';
                }
                break;
            case 'ECPSC':
                if($request->stage == '0101' || $request->stage == '0201'){
                    $data[] = 'B-0101P';
                    $data[] = 'B-0101N';
                }else{
                    $data[] = 'B-0201P';
                    $data[] = 'B-0201N';
                }
                break;
            case '养老服务':
                if($request->stage == '0101' || $request->stage == '0201'){
                    $data[] = 'B-0101P';
                    $data[] = 'B-0101N';
                }else{
                    $data[] = 'B-0201P';
                    $data[] = 'B-0201N';
                }
                break;
            case '物业服务':
                if($request->stage == '0101' || $request->stage == '0201'){
                    $data[] = 'B-0101P';
                    $data[] = 'B-0101N';
                }else{
                    $data[] = 'B-0201P';
                    $data[] = 'B-0201N';
                }
                break;
            case '家政服务':
                if($request->stage == '0101' || $request->stage == '0201'){
                    $data[] = 'B-0101P';
                    $data[] = 'B-0101N';
                }else{
                    $data[] = 'B-0201P';
                    $data[] = 'B-0201N';
                }
                break;
            case 'IPT':
                if($request->stage == '0101' || $request->stage == '0201'){
                    $data[] = 'B-0101P';
                    $data[] = 'B-0101N';
                }else{
                    $data[] = 'B-0201P';
                    $data[] = 'B-0201N';
                }
                break;
            case 'EIMS':
                if($request->stage == '0101' || $request->stage == '0201'){
                    $data[] = 'B-0101P';
                    $data[] = 'B-0101N';
                }else{
                    $data[] = 'B-0201P';
                    $data[] = 'B-0201N';
                }
                break;
            case 'FSMS':
                if($request->stage == '0101' || $request->stage == '0201'){
                    $data[] = 'A-0101P';
                    $data[] = 'A-0201N';
                }else{
                    $data[] = 'A-0201P';
                    $data[] = 'A-0201N';
                }
                break;
            case 'HACCP':
                if($request->stage == '0101' || $request->stage == '0201'){
                    $data[] = 'A-0101P';
                    $data[] = 'A-0201N';
                }else{
                    $data[] = 'A-0201P';
                    $data[] = 'A-0201N';
                }
                break;
        }
        if(empty($data)){
            return response()->json(['status'=>101,'msg'=>'无数据']);
        }else{
            return response()->json(['status'=>100,'msg'=>'保存成功','data'=>$data]);
        }

    }

    /**管理体系计划书**/
    public function TemplatePlan(Request $request){
        switch($request->rztx){
            case 'QMS':
                $data = $this->PlanShare($request);
                if($data['status'] == 101){
                    return response()->json($data);
                }
                if($request->stage == '0101' || $request->stage == '0201'){
                    $site = $this->PlanExport($data['data'],"admin/ETC-SHB-JL-004-B0.docx");
                }else{
                    $site = $this->PlanExport($data['data'],"admin/ETC-SHB-JL-017-B1.docx");
                }
                break;
            case 'EMS':
                $data = $this->PlanShare($request);
                if($data['status'] == 101){
                    return response()->json($data);
                }
                if($request->stage == '0101' || $request->stage == '0201'){
                    $site = $this->PlanExport($data['data'],"admin/ETC-SHB-JL-004-B0.docx");
                }else{
                    $site = $this->PlanExport($data['data'],"admin/ETC-SHB-JL-017-B1.docx");
                }
                break;
            case 'EC':
                $data = $this->PlanShare($request);
                if($data['status'] == 101){
                    return response()->json($data);
                }
                if($request->stage == '0101' || $request->stage == '0201'){
                    $site = $this->PlanExport($data['data'],"admin/ETC-SHB-JL-004-B0.docx");
                }else{
                    $site = $this->PlanExport($data['data'],"admin/ETC-SHB-JL-017-B1.docx");
                }
                break;
            case 'OHSMS':
                $data = $this->PlanShare($request);
                if($data['status'] == 101){
                    return response()->json($data);
                }
                if($request->stage == '0101' || $request->stage == '0201'){
                    $site = $this->PlanExport($data['data'],"admin/ETC-SHB-JL-004-B0.docx");
                }else{
                    $site = $this->PlanExport($data['data'],"admin/ETC-SHB-JL-017-B1.docx");
                }
                break;
            case 'SA8000':
                $data = $this->PlanShare($request);
                if($data['status'] == 101){
                    return response()->json($data);
                }
                if($request->stage == '0101' || $request->stage == '0201'){
                    $site = $this->PlanExport($data['data'],"admin/ETC-SHB-JL-004-B0.docx");
                }else{
                    $site = $this->PlanExport($data['data'],"admin/ETC-SHB-SA-017-B1.docx");
                }
                break;
            case 'EIMS':
                $data = $this->PlanShare($request);
                if($data['status'] == 101){
                    return response()->json($data);
                }
                if($request->stage == '0101' || $request->stage == '0201'){
                    $site = $this->PlanExport($data['data'],"admin/ETC-SHB-JL-004-B0.docx");
                }else{
                    $site = $this->PlanExport($data['data'],"admin/ETC-SHB-SA-017-B1.docx");
                }
                break;
            case 'IECE':
                $data = $this->PlanShare($request);
                if($data['status'] == 101){
                    return response()->json($data);
                }
                if($request->stage == '0101' || $request->stage == '0201'){
                    $site = $this->PlanExport($data['data'],"admin/ETC-SHB-IECE-003-B0.docx");
                }else{
                    $site = $this->PlanExport($data['data'],"admin/ETC-SHB-IECE-004-B1.docx");
                }
                break;
            case 'ECPSC':
                $data = $this->ServiceShare($request);
                if($data['status'] == 101){
                    return response()->json($data);
                }
                if($request->stage == '0101' || $request->stage == '0201'){
                    $site = $this->PlanExport($data['data'],"admin/ETC-SHB-FW-001-B0.docx");
                }else{
                    $site = $this->PlanExport($data['data'],"admin/ETC-SHB-FW-004-B0.docx");
                }
                break;
            case '养老服务':
                $data = $this->ServiceShare($request);
                if($data['status'] == 101){
                    return response()->json($data);
                }
                if($request->stage == '0101' || $request->stage == '0201'){
                    $site = $this->PlanExport($data['data'],"admin/ETC-SHB-FW-001-B0.docx");
                }else{
                    $site = $this->PlanExport($data['data'],"admin/ETC-SHB-FW-004-B0.docx");
                }
                break;
            case '家政服务':
                $data = $this->ServiceShare($request);
                if($data['status'] == 101){
                    return response()->json($data);
                }
                if($request->stage == '0101' || $request->stage == '0201'){
                    $site = $this->PlanExport($data['data'],"admin/ETC-SHB-FW-001-B0.docx");
                }else{
                    $site = $this->PlanExport($data['data'],"admin/ETC-SHB-FW-004-B0.docx");
                }
                break;
            case '物业服务':
                $data = $this->ServiceShare($request);
                if($data['status'] == 101){
                    return response()->json($data);
                }
                if($request->stage == '0101' || $request->stage == '0201'){
                    $site = $this->PlanExport($data['data'],"admin/ETC-SHB-FW-001-B0.docx");
                }else{
                    $site = $this->PlanExport($data['data'],"admin/ETC-SHB-FW-004-B0.docx");
                }
                break;
            case 'YY':
                $data = $this->PlanShare($request);
                if($data['status'] == 101){
                    return response()->json($data);
                }
                if($request->stage == '0101' || $request->stage == '0201'){
                    $site = $this->PlanExport($data['data'],"admin/ETC-SHB-JL-004-B0.docx");
                }else{
                    $site = $this->PlanExport($data['data'],"admin/ETC-SHB-JL-017-B1.docx");
                }
                break;
            case 'FSMS':
                $data = $this->PlanShare($request);
                if($data['status'] == 101){
                    return response()->json($data);
                }
                if($request->stage == '0101' || $request->stage == '0201'){
                    $site = $this->PlanExport($data['data'],"admin/ETC-SHB-FH-002-B0.docx");
                }else{
                    $site = $this->PlanExport($data['data'],"admin/ETC-SHB-FH-004-B0.docx");
                }
                break;
            case 'HACCP':
                $data = $this->PlanShare($request);
                if($data['status'] == 101){
                    return response()->json($data);
                }
                if($request->stage == '0101' || $request->stage == '0201'){
                    $site = $this->PlanExport($data['data'],"admin/ETC-SHB-FH-002-B0.docx");
                }else{
                    $site = $this->PlanExport($data['data'],"admin/ETC-SHB-FH-004-B0.docx");
                }
                break;
        }
        if($site == null){
            return response()->json(['status'=>101,'msg'=>'模板导出无数据']);
        }
        return response()->json(['status'=>100,'msg'=>'请求成功','data'=>$site]);
    }

    /**管理体系计划书函数**/
    public function PlanShare($request){
        $cmpyData = MarketCustomer::where('id',$request->id)//企业信息
        ->select('qymc','zcdz','bgdz','scdz','postal')
            ->get();
        $data['qymc'] = $cmpyData->first()->qymc;
        $data['site'] = $cmpyData->first()->zcdz?'注册地址:'.$cmpyData->first()->zcdz:'';
        $data['site'].= InspectPlan::sitePlan($cmpyData->first()->bgdz,$request->rztx,'办公地址');
        $data['site'].= InspectPlan::sitePlan($cmpyData->first()->scdz,$request->rztx,'生产地址');
        //$data['site'].= InspectPlan::sitePlan($cmpyData->first()->postal,$request->rztx,'通讯地址');
        $cntsData = MarketContacts::where([//联系人
            ['kh_id',$request->id],
            ['state',1]])//联系人信息
        ->select('name','phone','tell','mail')
            ->get();
        if($cntsData->isEmpty()){
            return (['status'=>101,'msg'=>'请设置默认联系人']);
        }
        $data['name'] = $cntsData->first()->name;
        $data['tell'] = $cntsData->first()->phone?$cntsData->first()->phone:$cntsData->first()->tell;
        $data['mail'] = $cntsData->first()->mail;
        $file = array(
            'qyht_htrza.id as did',
            'rztx',
            'regt_numb',
            'rev_range',
            'rzbz',
            'm_sites',
            'cer_type',
            'one_mode',
            'rz_nub',
            'audit_phase',
            'field_day',
            'start_time',
            'end_time',
        );
        if(!$request->union_cmpy){
            $where  = array(
                ['ht_id',$request->hid],
                ['audit_phase',$request->stage],
                ['rztx',$request->rztx]
            );
            $sytmData = MarketContract::DispatchDetail($file,$where);
        }else{
            $whereIn = explode(";",$request->union_cmpy);
            $sytmData = MarketContract::DispatchDetail($file,'',$whereIn);
        }
        if($sytmData->isEmpty()){
            return (['status'=>101,'msg'=>'没有添加认证项目']);
        }
        $sytmData = $sytmData->toArray();
        try {
            array_walk($sytmData,function ($value,$key)  use (&$data){
                switch($value['rztx']){
                    case 'QMS':
                        $rgstCode = 'Q:';
                        break;
                    case 'EMS':
                        $rgstCode = 'E:';
                        break;
                    case 'EC':
                        $rgstCode = 'EC:';
                        break;
                    case 'OHSMS':
                        $rgstCode = 'S:';
                        break;
                    case 'SA8000':
                        $rgstCode = 'SA:';
                        break;
                    case 'EIMS':
                        $rgstCode = 'EIMS:';
                        break;
                    case 'IECE':
                        $rgstCode = 'IECE:';
                        break;
                    case 'YY':
                        $rgstCode = 'YY:';
                        break;
                    case 'FSMS':
                        $rgstCode = 'FSMS:';
                        break;
                    case 'HACCP':
                        $rgstCode = 'HACCP:';
                        break;
                }
                if(!isset($data['rgst'])){
                    $data['rgst'] = $rgstCode.$value['regt_numb'];
                }else{
                    $data['rgst'].= ' '.$rgstCode.$value['regt_numb'];
                }
                if(!isset($data['start'])){
                    $data['start']= $value['start_time'];
                }
                if(!isset($data['end'])){
                    $data['end']  = $value['end_time'];
                }
                if(!isset($data['days'])){
                    $start= strtotime($value['start_time']);
                    $end  = strtotime($value['end_time']);
                    $strto= $end-$start;
                    $days = intval($strto/86400);
                    $remd = $strto/86400-$days;
                    $time = intval(round($remd*24));
                    switch($time){
                        case 2:
                            $data['days'] = $days+0.25;
                            break;
                        case 4:
                            $data['days'] = $days+0.5;
                            break;
                        case 7:
                            $data['days'] = $days+0.75;
                            break;
                        case 9:
                            $data['days'] = $days+1;
                            break;
                    }
                }
                if(!isset($data['part'])){
                    $data['part'] = $rgstCode.$value['field_day'];
                }else{
                    $data['part'].= ' '.$rgstCode.$value['field_day'];
                }
                if(!isset($data['type'])){
                    $data['type'] = $value['audit_phase'];
                }else{
                    $data['type'] = $value['audit_phase'];
                }
                if(!isset($data['mode'])){
                    $data['mode'] = $value['one_mode'] == '01'?'■非现场审核':'■现场审核';
                }
                if(!isset($data['review'])){
                    switch($value['audit_phase']){
                        case '03':
                            $data['review'][] = 1;
                            break;
                        case '07':
                            $data['review'][] = 2;
                            break;
                        default:
                            $data['review'][] = 0;
                    }
                }
                if(!isset($data['nmbr'])){
                    $data['nmbr'][] = $value['rz_nub'];
                }else{
                    $data['nmbr'][] = $value['rz_nub'];
                }
                if(!isset($data['range'])){
                    $data['range'] = $rgstCode.$value['rev_range'];
                }else{
                    $data['range'] .= '<w:br/>'.$rgstCode.$value['rev_range'];
                }
                if(!isset($data['other'])){
                    $data['other'] = $value['m_sites'] == 0?'■否   □有，详见：多名称/多场所/在建（施）清单 ':'□否   ■有，详见：多名称/多场所/在建（施）清单 ';
                }
                if(!isset($data['basis'])){
                    $data['basis'] = '■'.$value['rzbz'];
                }else{
                    $data['basis'] .= '<w:br/>'.'■'.$value['rzbz'];
                }
                if(!isset($data['sign'])){
                    switch($value['cer_type']){
                        case '01':
                            $data['sign'] = '■CNAS' ;
                            break;
                        case '02':
                            $data['sign'] = '■UKAS' ;
                            break;
                        case '03':
                            $data['sign'] = '■JAS-ANS' ;
                            break;
                        case '00':
                            $data['sign'] = '■ETC' ;
                            break;
                    }
                }
                $file = array(
                    'users.id as id',
                    'name',
                    'sex',
                    'me_code',
                    'rgt_numb',
                    'us_qlfts',
                    'title',
                    'work_unit',
                    'role',
                    'm_code',
                    'telephone',
                );
                $where = array(
                    ['qyht_htrzu.ap_id',$value['did']],
                    ['rgt_type',$value['rztx']]
                );
                $userData = InspectAuditTeam::PlanTeam($file,$where);
                if($userData->isEmpty()){
                    throw new Exception('没有添加审核人员');
                }
                $userData = $userData->toArray();
                array_walk($userData,function ($value,$key,$rgstCode)  use (&$data){
                    if(!isset($user['user'][$value['id']])){
                        switch($value['role']){
                            case '01':
                                $role =$rgstCode.'组长';
                                break;
                            case '02':
                                $role = $rgstCode.'组员';
                                break;
                            case '03':
                                $role = $rgstCode.'技术专家';
                                break;
                        }
                        if(empty($data['user'][$value['id']])){
                            $data['user'][$value['id']]['name'] = $value['name'];
                            $data['user'][$value['id']]['sex']  = $value['sex'] == 1?'女':'男';
                            switch ($value['us_qlfts']) {
                                case '01':
                                    $data['user'][$value['id']]['qfctn'] = $rgstCode.'高级审核员';
                                    break;
                                case '02':
                                    $data['user'][$value['id']]['qfctn'] = $rgstCode.'审核员';
                                    break;
                                case '03':
                                    $data['user'][$value['id']]['qfctn'] = $rgstCode.'实习审核员';
                                    break;
                                case '04':
                                    $data['user'][$value['id']]['qfctn'] = $rgstCode.'技术专家';
                                    break;
                                case '05':
                                    $data['user'][$value['id']]['qfctn'] = $rgstCode.'高级审查员';
                                    break;
                                case '06':
                                    $data['user'][$value['id']]['qfctn'] = $rgstCode.'审查员';
                                    break;
                                case '07':
                                    $data['user'][$value['id']]['qfctn'] = $rgstCode.'主任审核员';
                                    break;
                                default:
                                    $data['user'][$value['id']]['qfctn'] = $rgstCode.'其他';
                            };
                            if($value['role'] == '03'){
                                $data['user'][$value['id']]['code'] = $value['me_code']?$rgstCode.$value['me_code']:'';
                                $data['user'][$value['id']]['title']= $value['title']?$value['title']:'';
                                $data['user'][$value['id']]['work'] = $value['work_unit']?$value['work_unit']:'';
                            }else{
                                $data['user'][$value['id']]['code'] = $value['rgt_numb']?$rgstCode.$value['rgt_numb']:'';
                                $data['user'][$value['id']]['title']= '';
                                $data['user'][$value['id']]['work'] = '';
                            }
                            $data['user'][$value['id']]['role'] = $role;
                            $data['user'][$value['id']]['major']= $value['m_code']?$rgstCode.$value['m_code']:'';
                            $data['user'][$value['id']]['tell'] = $value['telephone'];
                        }else{
                            if($value['role'] == '03'){
                                $data['user'][$value['id']]['code'] .= $value['me_code']?$rgstCode.$value['me_code']:'';
                            }else{
                                $data['user'][$value['id']]['code'] .= $value['rgt_numb']?$rgstCode.$value['rgt_numb']:'';
                            }
                            switch ($value['us_qlfts']) {
                                case '01':
                                    $data['user'][$value['id']]['qfctn'] .= $rgstCode.'高级审核员';
                                    break;
                                case '02':
                                    $data['user'][$value['id']]['qfctn'] .= $rgstCode.'审核员';
                                    break;
                                case '03':
                                    $data['user'][$value['id']]['qfctn'] .= $rgstCode.'实习审核员';
                                    break;
                                case '04':
                                    $data['user'][$value['id']]['qfctn'] .= $rgstCode.'技术专家';
                                    break;
                                case '05':
                                    $data['user'][$value['id']]['qfctn'] .= $rgstCode.'高级审查员';
                                    break;
                                case '06':
                                    $data['user'][$value['id']]['qfctn'] .= $rgstCode.'审查员';
                                    break;
                                case '07':
                                    $data['user'][$value['id']]['qfctn'] .= $rgstCode.'主任审核员';
                                    break;
                                default:
                                    $data['user'][$value['id']]['qfctn'] .= $rgstCode.'其他';
                            };
                            $data['user'][$value['id']]['role'] .= $value['role']?' '.$role:'';
                            $data['user'][$value['id']]['major'].= $value['m_code']?$rgstCode.$value['m_code']:'';
                        }
                    }
                },$rgstCode);
            });
        }catch (\Exception $e){
            return (['status'=>101,'msg'=>$e->getMessage()]);
        }
        //$data['type']  = array_unique($data['type']);
        $data['review']= max($data['review']);
        $data['nmbr']  = max($data['nmbr']);
        $data['user']  = array_values($data['user']);
        $data['plan']  = Auth::guard('api')->user()->name;
        $data['time']  = date('Y-m-d');
        return (['status'=>100,'data'=>$data]);
    }

    /**常规管理体系计划导出**/
    public function PlanExport($data,$site){
        //实例化 phpword 类
        $PHPWord = new PhpWord();
        //指定模板文件
        $templateProcessor = new TemplateProcessor(public_path($site));
        //通过setValue 方法给模板赋值
        foreach ($data as $key=>$value){
            if($key == 'user'){
                $templateProcessor->cloneRow('key',count($value));
                foreach ($value as $key=>$value){
                    $key = $key*1+1;
                    $templateProcessor->setValue('key#'.$key,$key);
                    $templateProcessor->setValue('user#'.$key,$value['name']);
                    $templateProcessor->setValue('sex#'.$key,$value['sex']);
                    $templateProcessor->setValue('code#'.$key,$value['code']);
                    $templateProcessor->setValue('qfctn#'.$key,$value['qfctn']);
                    $templateProcessor->setValue('work#'.$key,$value['work']);
                    $templateProcessor->setValue('title#'.$key,$value['title']);
                    $templateProcessor->setValue('role#'.$key,$value['role']);
                    $templateProcessor->setValue('major#'.$key,$value['major']);
                    $templateProcessor->setValue('phone#'.$key,$value['tell']);
                }
            }else{
                $templateProcessor->setValue($key,$value);
            }
        }
        //保存新word文档
        $path = time().".docx";
        if (!is_dir(public_path("export"))) {
            mkdir(public_path("export"), 0755, true);
        }
        $export = $templateProcessor->saveAs(public_path("export/".$path ));
        if($export == true){
            return($path);
        }else{
            return(null);
        }
    }

    /**服务认证计划书函数**/
    public function ServiceShare($request){
        $cmpyData = MarketCustomer::where('id',$request->id)//企业信息
        ->select('qymc','zcdz','bgdz','scdz','postal')
            ->get();
        $data['qymc'] = $cmpyData->first()->qymc;
        $data['site'] = $cmpyData->first()->zcdz?'注册地址:'.$cmpyData->first()->zcdz:'';
/*        $data['site'].= $cmpyData->first()->bgdz?' 办公地址:'.$cmpyData->first()->bgdz:'';
        $data['site'].= $cmpyData->first()->scdz?' 生产地址:'.$cmpyData->first()->scdz:'';*/
        $data['site'].= InspectPlan::sitePlan($cmpyData->first()->bgdz,$request->rztx,'办公地址');
        $data['site'].= InspectPlan::sitePlan($cmpyData->first()->scdz,$request->rztx,'生产地址');
        //$data['site'].= $cmpyData->first()->postal?' 通讯地址:'.$cmpyData->first()->postal:'';

        $cntsData = MarketContacts::where([//联系人
            ['kh_id',$request->id],
            ['state',1]])//联系人信息
        ->select('name','phone','tell','mail')
            ->get();
        if($cntsData->isEmpty()){
            return (['status'=>101,'msg'=>'请设置默认联系人']);
        }
        $data['name'] = $cntsData->first()->name;
        $data['tell'] = $cntsData->first()->phone?$cntsData->first()->phone:$cntsData->first()->tell;
        $data['mail'] = $cntsData->first()->mail;
        $file = array(
            'qyht_htrza.id as did',
            'rztx',
            'regt_numb',
            'rev_range',
            'rzbz',
            'm_sites',
            'cer_type',
            'one_mode',
            'rz_nub',
            'audit_phase',
            'field_day',
            'start_time',
            'end_time',
        );
        if(!$request->union_cmpy){
            $where  = array(
                ['ht_id',$request->hid],
                ['audit_phase',$request->stage],
                ['rztx',$request->rztx]
            );
            $sytmData = MarketContract::DispatchDetail($file,$where);
        }else{
            $whereIn = explode(";",$request->union_cmpy);
            $sytmData = MarketContract::DispatchDetail($file,'',$whereIn);
        }
        if($sytmData->isEmpty()){
            return (['status'=>101,'msg'=>'没有添加认证项目']);
        }
        $sytmData = $sytmData->toArray();
        try {
            array_walk($sytmData,function ($value,$key)  use (&$data){
                switch($value['rztx']){
                    case 'ECPSC':
                        $rgstCode = 'F:';
                        break;
                    case '养老服务':
                        $rgstCode = 'F:';
                        break;
                    case '物业服务':
                        $rgstCode = 'F:';
                        break;
                    case '家政服务':
                        $rgstCode = 'F:';
                        break;
                }
                if(!isset($data['rgst'])){
                    $data['rgst'] = $rgstCode.$value['regt_numb'];
                }else{
                    $data['rgst'].= ' '.$rgstCode.$value['regt_numb'];
                }
                if(!isset($data['start'])){
                    $data['start']= $value['start_time'];
                }
                if(!isset($data['end'])){
                    $data['end']  = $value['end_time'];
                }
                if(!isset($data['days'])){
                    $start= strtotime($value['start_time']);
                    $end  = strtotime($value['end_time']);
                    $strto= $end-$start;
                    $days = intval($strto/86400);
                    $remd = $strto/86400-$days;
                    $time = intval(round($remd*24));
                    switch($time){
                        case 2:
                            $data['days'] = $days+0.25;
                            break;
                        case 4:
                            $data['days'] = $days+0.5;
                            break;
                        case 7:
                            $data['days'] = $days+0.75;
                            break;
                        case 9:
                            $data['days'] = $days+1;
                            break;
                    }
                }
                if(!isset($data['part'])){
                    $data['part'] = $rgstCode.$value['field_day'];
                }else{
                    $data['part'].= ' '.$rgstCode.$value['field_day'];
                }
                if(!isset($data['type'])){
                    $data['type'] = $value['audit_phase'];
                }else{
                    $data['type'] = $value['audit_phase'];
                }
                if(!isset($data['mode'])){
                    $data['mode'] = $value['one_mode'] == '01'?'■非现场审核':'■现场审核';
                }
                if(!isset($data['review'])){
                    switch($value['audit_phase']){
                        case '03':
                            $data['review'][] = 1;
                            break;
                        case '07':
                            $data['review'][] = 2;
                            break;
                        default:
                            $data['review'][] = 0;
                    }
                }
                if(!isset($data['nmbr'])){
                    $data['nmbr'][] = $value['rz_nub'];
                }else{
                    $data['nmbr'][] = $value['rz_nub'];
                }
                if(!isset($data['range'])){
                    $data['range'] = $rgstCode.$value['rev_range'];
                }else{
                    $data['range'] .= '<w:br/>'.$rgstCode.$value['rev_range'];
                }
                if(!isset($data['other'])){
                    $data['other'] = $value['m_sites'] == 0?'■否':'■有，详见：多名称/多场所/在建（施）清单 ';
                }
                if(!isset($data['basis'])){
                    $data['basis'] = '■'.$value['rzbz'];
                }else{
                    $data['basis'] .= '<w:br/>'.'■'.$value['rzbz'];
                }
                if(!isset($data['sign'])){
                    switch($value['cer_type']){
                        case '01':
                            $data['sign'] = '■CNAS' ;
                            break;
                        case '02':
                            $data['sign'] = '■UKAS';
                            break;
                        case '03':
                            $data['sign'] = '■JAS-ANS';
                            break;
                        case '00':
                            $data['sign'] = '■ETC';
                            break;
                    }
                }
                $file = array(
                    'users.id as id',
                    'name',
                    'sex',
                    'me_code',
                    'rgt_numb',
                    'us_qlfts',
                    'title',
                    'work_unit',
                    'role',
                    'm_code',
                    'telephone',
                );
                $where = array(
                    ['qyht_htrzu.ap_id',$value['did']],
                    ['rgt_type',$value['rztx']]
                );
                $userData = InspectAuditTeam::PlanTeam($file,$where);
                if($userData->isEmpty()){
                    throw new Exception('没有添加审核人员');
                }
                $userData = $userData->toArray();
                array_walk($userData,function ($value,$key,$rgstCode)  use (&$data){
                    if(!isset($user['user'][$value['id']])){
                        switch($value['role']){
                            case '01':
                                $role =$rgstCode.'组长';
                                break;
                            case '02':
                                $role = $rgstCode.'组员';
                                break;
                            case '03':
                                $role = $rgstCode.'技术专家';
                                break;
                        }
                        if(strpos($value['m_code'],'N') == false){
                            $value['m_code'] = $value['m_code'];
                        }else{
                            $value['m_code'] = str_replace('N','',$value['m_code']);
                        }
                        if(empty($data['user'][$value['id']])){
                            $data['user'][$value['id']]['name'] = $value['name'];
                            $data['user'][$value['id']]['sex']  = $value['sex'] == 1?'女':'男';
                            switch ($value['us_qlfts']) {
                                case '01':
                                    $data['user'][$value['id']]['qfctn'] = $rgstCode.'高级审核员';
                                    break;
                                case '02':
                                    $data['user'][$value['id']]['qfctn'] = $rgstCode.'审核员';
                                    break;
                                case '03':
                                    $data['user'][$value['id']]['qfctn'] = $rgstCode.'实习审核员';
                                    break;
                                case '04':
                                    $data['user'][$value['id']]['qfctn'] = $rgstCode.'技术专家';
                                    break;
                                case '05':
                                    $data['user'][$value['id']]['qfctn'] = $rgstCode.'高级审查员';
                                    break;
                                case '06':
                                    $data['user'][$value['id']]['qfctn'] = $rgstCode.'审查员';
                                    break;
                                case '07':
                                    $data['user'][$value['id']]['qfctn'] = $rgstCode.'主任审核员';
                                    break;
                                default:
                                    $data['user'][$value['id']]['qfctn'] = $rgstCode.'其他';
                            };
                            if($value['role'] == '03'){
                                $data['user'][$value['id']]['code'] = $value['me_code']?$rgstCode.$value['me_code']:'';
                                $data['user'][$value['id']]['title']= $value['title']?$value['title']:'';
                                $data['user'][$value['id']]['work'] = $value['work_unit']?$value['work_unit']:'';
                            }else{
                                $data['user'][$value['id']]['code'] = $value['rgt_numb']?$rgstCode.$value['rgt_numb']:'';
                                $data['user'][$value['id']]['title']= '';
                                $data['user'][$value['id']]['work'] = '';
                            }
                            $data['user'][$value['id']]['role'] = $role;
                            $data['user'][$value['id']]['major']= $value['m_code']?$rgstCode.$value['m_code']:'';
                            $data['user'][$value['id']]['tell'] = $value['telephone'];
                        }else{
                            if($value['role'] == '03'){
                                $data['user'][$value['id']]['code'] .= $value['me_code']?$rgstCode.$value['me_code']:'';
                            }else{
                                $data['user'][$value['id']]['code'] .= $value['rgt_numb']?$rgstCode.$value['rgt_numb']:'';
                            }
                            switch ($value['us_qlfts']) {
                                case '01':
                                    $data['user'][$value['id']]['qfctn'] .= $rgstCode.'高级审核员';
                                    break;
                                case '02':
                                    $data['user'][$value['id']]['qfctn'] .= $rgstCode.'审核员';
                                    break;
                                case '03':
                                    $data['user'][$value['id']]['qfctn'] .= $rgstCode.'实习审核员';
                                    break;
                                case '04':
                                    $data['user'][$value['id']]['qfctn'] .= $rgstCode.'技术专家';
                                    break;
                                case '05':
                                    $data['user'][$value['id']]['qfctn'] .= $rgstCode.'高级审查员';
                                    break;
                                case '06':
                                    $data['user'][$value['id']]['qfctn'] .= $rgstCode.'审查员';
                                    break;
                                case '07':
                                    $data['user'][$value['id']]['qfctn'] .= $rgstCode.'主任审核员';
                                    break;
                                default:
                                    $data['user'][$value['id']]['qfctn'] .= $rgstCode.'其他';
                            };
                            $data['user'][$value['id']]['role'] .= $value['role']?' '.$role:'';
                            $data['user'][$value['id']]['major'].= $value['m_code']?' '.$rgstCode.$value['m_code']:'';
                        }
                    }
                },$rgstCode);
            });
        }catch (\Exception $e){
            return (['status'=>101,'msg'=>$e->getMessage()]);
        }
        //$data['type']  = array_unique($data['type']);
        $data['review']= max($data['review']);
        $data['nmbr']  = max($data['nmbr']);
        $data['user']  = array_values($data['user']);
        $data['plan']  = Auth::guard('api')->user()->name;
        $data['time']  = date('Y-m-d');
        return (['status'=>100,'data'=>$data]);
    }

    /**服务认证计划导出**/
    public function ServiceExport($data,$site){
        //实例化 phpword 类
        $PHPWord = new PhpWord();
        //指定模板文件
        $templateProcessor = new TemplateProcessor(public_path($site));
        //通过setValue 方法给模板赋值
        foreach ($data as $key=>$value){
            if($key == 'user'){
                $templateProcessor->cloneRow('key',count($value));
                foreach ($value as $key=>$value){
                    $key = $key*1+1;
                    if($key<=26){
                        $keyy = chr($key+64);
                    }else{
                        $keyy = $key;
                    }
                    $templateProcessor->setValue('key#'.$key,$keyy);
                    $templateProcessor->setValue('user#'.$key,$value['name']);
                    $templateProcessor->setValue('sex#'.$key,$value['sex']);
                    $templateProcessor->setValue('code#'.$key,$value['code']);
                    $templateProcessor->setValue('qfctn#'.$key,$value['qfctn']);
                    $templateProcessor->setValue('work#'.$key,$value['work']);
                    $templateProcessor->setValue('title#'.$key,$value['title']);
                    $templateProcessor->setValue('role#'.$key,$value['role']);
                    $templateProcessor->setValue('major#'.$key,$value['major']);
                    $templateProcessor->setValue('phone#'.$key,$value['tell']);
                }
            }else{
                $templateProcessor->setValue($key,$value);
            }
        }
        //保存新word文档
        $path = time().".docx";
        if (!is_dir(public_path("export"))) {
            mkdir(public_path("export"), 0755, true);
        }
        $export = $templateProcessor->saveAs(public_path("export/".$path ));
        if($export == true){
            return($path);
        }else{
            return(null);
        }
    }

    /**管理体系通知书**/
    public function TemplateNotice(Request $request){
        switch($request->rztx){
            case 'QMS':
                $data = $this->NoticeShare($request);
                if($data['status'] == 101){
                    return response()->json($data);
                }
                $site = $this->NoticeExport($data['data'],"admin/ETC-SHB-JL-003-B0.docx");
                break;
            case 'EMS':
                $data = $this->NoticeShare($request);
                if($data['status'] == 101){
                    return response()->json($data);
                }
                $site = $this->NoticeExport($data['data'],"admin/ETC-SHB-JL-003-B0.docx");
                break;
            case 'EC':
                $data = $this->NoticeShare($request);
                if($data['status'] == 101){
                    return response()->json($data);
                }
                $site = $this->NoticeExport($data['data'],"admin/ETC-SHB-JL-003-B0.docx");
                break;
            case 'OHSMS':
                $data = $this->NoticeShare($request);
                if($data['status'] == 101){
                    return response()->json($data);
                }
                $site = $this->NoticeExport($data['data'],"admin/ETC-SHB-JL-003-B0.docx");
                break;
            case 'SA8000':
                $data = $this->NoticeShare($request);
                if($data['status'] == 101){
                    return response()->json($data);
                }
                if($request->stage == '0101' || $request->stage == '0201'){
                    $site = $this->NoticeExport($data['data'],"admin/ETC-SHB-SA-001-B0.docx");
                }else{
                    $site = $this->NoticeExport($data['data'],"admin/ETC-SHB-JL-003-B0.docx");
                }
                break;
            case 'EIMS':
                $data = $this->NoticeShare($request);
                if($data['status'] == 101){
                    return response()->json($data);
                }
                if($request->stage == '0101' || $request->stage == '0201'){
                    $site = $this->NoticeExport($data['data'],"admin/ETC-SHB-EIMS-001-B0.docx");
                }else{
                    $site = $this->NoticeExport($data['data'],"admin/ETC-SHB-EIMS-002-B0.docx");
                }
                break;
            case 'IECE':
                $data = $this->NoticeShare($request);
                if($data['status'] == 101){
                    return response()->json($data);
                }
                if($request->stage == '0101' || $request->stage == '0201'){
                    $site = $this->NoticeExport($data['data'],"admin/ETC-SHB-IECE-001-B0.docx");
                }else{
                    $site = $this->NoticeExport($data['data'],"admin/ETC-SHB-IECE-002-B0.docx");
                }
                break;
            case 'ECPSC':
                $data = $this->NoticeShare($request);
                if($data['status'] == 101){
                    return response()->json($data);
                }
                if($request->stage == '0101' || $request->stage == '0201'){
                    $site = $this->NoticeExport($data['data'],"admin/ETC-SHB-FW-002-B0.docx");
                }else{
                    $site = $this->NoticeExport($data['data'],"admin/ETC-SHB-FW-003-B0.docx");
                }
                break;
            case '养老服务':
                $data = $this->NoticeShare($request);
                if($data['status'] == 101){
                    return response()->json($data);
                }
                if($request->stage == '0101' || $request->stage == '0201'){
                    $site = $this->PlanExport($data['data'],"admin/ETC-SHB-FW-002-B0.docx");
                }else{
                    $site = $this->PlanExport($data['data'],"admin/ETC-SHB-FW-003-B0.docx");
                }
                break;
            case '物业服务':
                $data = $this->NoticeShare($request);
                if($data['status'] == 101){
                    return response()->json($data);
                }
                if($request->stage == '0101' || $request->stage == '0201'){
                    $site = $this->PlanExport($data['data'],"admin/ETC-SHB-FW-002-B0.docx");
                }else{
                    $site = $this->PlanExport($data['data'],"admin/ETC-SHB-FW-003-B0.docx");
                }
                break;
            case '家政服务':
                $data = $this->NoticeShare($request);
                if($data['status'] == 101){
                    return response()->json($data);
                }
                if($request->stage == '0101' || $request->stage == '0201'){
                    $site = $this->PlanExport($data['data'],"admin/ETC-SHB-FW-002-B0.docx");
                }else{
                    $site = $this->PlanExport($data['data'],"admin/ETC-SHB-FW-003-B0.docx");
                }
                break;
            case 'YY':
                $data = $this->NoticeShare($request);
                if($data['status'] == 101){
                    return response()->json($data);
                }
                $site = $this->NoticeExport($data['data'],"admin/ETC-SHB-JL-003-B0.docx");
                break;
            case 'FSMS':
                $data = $this->NoticeShare($request);
                if($data['status'] == 101){
                    return response()->json($data);
                }
                if($request->stage == '0101' || $request->stage == '0201'){
                    $site = $this->NoticeExport($data['data'],"admin/ETC-SHB-FH-001-B0.docx");
                }else{
                    $site = $this->NoticeExport($data['data'],"admin/ETC-SHB-FH-003-B0.docx");
                }
                break;
            case 'HACCP':
                $data = $this->NoticeShare($request);
                if($data['status'] == 101){
                    return response()->json($data);
                }
                if($request->stage == '0101' || $request->stage == '0201'){
                    $site = $this->NoticeExport($data['data'],"admin/ETC-SHB-FH-001-B0.docx");
                }else{
                    $site = $this->NoticeExport($data['data'],"admin/ETC-SHB-FH-003-B0.docx");
                }
                break;
        }
        if($site == null){
            return response()->json(['status'=>101,'msg'=>'模板导出无数据']);
        }
        return response()->json(['status'=>100,'msg'=>'请求成功','data'=>$site]);
    }

    /**管理体系通知书函数**/
    public function NoticeShare($request){
        $cmpyData = MarketCustomer::where('id',$request->id)//企业信息
        ->select('qymc')
            ->get();
        $data['qymc'] = $cmpyData->first()->qymc;

        $file = array(
            'qyht_htrza.id as did',
            'rztx',
            'one_mode',
            'audit_phase',
            'start_time',
            'end_time',
        );
        if(!$request->union_cmpy){
            $where  = array(
                ['ht_id',$request->hid],
                ['audit_phase',$request->stage],
                ['rztx',$request->rztx]
            );
            $sytmData = MarketContract::DispatchDetail($file,$where);
        }else{
            $whereIn = explode(";",$request->union_cmpy);
            $sytmData = MarketContract::DispatchDetail($file,'',$whereIn);
        }
        if($sytmData->isEmpty()){
            return (['status'=>101,'msg'=>'没有添加认证项目']);
        }
        $sytmData = $sytmData->toArray();
        try {
            array_walk($sytmData,function ($value,$key)  use (&$data){
                switch($value['rztx']){
                    case 'QMS':
                        $rgstCode = 'Q:';
                        break;
                    case 'EMS':
                        $rgstCode = 'E:';
                        break;
                    case 'EC':
                        $rgstCode = 'EC:';
                        break;
                    case 'OHSMS':
                        $rgstCode = 'S:';
                        break;
                    case 'SA8000':
                        $rgstCode = 'SA:';
                        break;
                    case 'EIMS':
                        $rgstCode = 'EIMS:';
                        break;
                    case 'IECE':
                        $rgstCode = 'IECE:';
                        break;
                    case 'YY':
                        $rgstCode = 'YY:';
                        break;
                    case 'ECPSC':
                        $rgstCode = 'F:';
                        break;
                    case '养老服务':
                        $rgstCode = 'F:';
                        break;
                    case '物业服务':
                        $rgstCode = 'F:';
                        break;
                    case '家政服务':
                        $rgstCode = 'F:';
                        break;
                    case 'FSMS':
                        $rgstCode = 'FSMS:';
                        break;
                    case 'HACCP':
                        $rgstCode = 'HACCP:';
                        break;
                }
                if(!isset($data['start'])){
                    //$start= strtotime($value['start_time']);
                    $data['start']= $value['start_time'];
                    //$data['start']= date("Y年m月d日",$start);
                }
                if(!isset($data['end'])){
                    $data['end']= $value['end_time'];
                    /*$end  = strtotime($value['end_time']);
                    $data['end']  = date("Y年m月d日",$end);*/
                }
/*                if(!isset($data['audit'])){
                    $audit['type'] = $value['audit_phase'];
                    $audit['mode'] = $value['one_mode'];
                    $data['audit'][] = $audit;
                }else{
                    $audit['type'] = $value['audit_phase'];
                    $audit['mode'] = $value['one_mode'];
                    $data['audit'][] = $audit;
                }*/
/*                if(!isset($data['review'])){
                    switch($value['audit_phase']){
                        case '03':
                            $data['review'][] = 1;
                            break;
                        case '07':
                            $data['review'][] = 2;
                            break;
                        default:
                            $data['review'][] = 0;
                    }
                }*/
                $file = array(
                    'users.id as id',
                    'name',
                    'sex',
                    'rgt_numb',
                    'us_qlfts',
                    'title',
                    'work_unit',
                    'role',
                    'm_code',
                    'telephone',
                );
                $where = array(
                    ['qyht_htrzu.ap_id',$value['did']],
                    ['rgt_type',$value['rztx']]
                );
                $userData = InspectAuditTeam::PlanTeam($file,$where);
                if($userData->isEmpty()){
                    throw new Exception('没有添加审核人员');
                }
                $userData = $userData->toArray();
                array_walk($userData,function ($value,$key,$rgstCode)  use (&$data){
                    switch($value['role']){
                        case '01':
                            $role =$rgstCode.'组长';
                            break;
                        case '02':
                            $role = $rgstCode.'组员';
                            break;
                        case '03':
                            $role = $rgstCode.'技术专家';
                            break;
                    }
                    if(strpos($value['m_code'],'N') == false){
                        $value['m_code'] = $value['m_code'];
                    }else{
                        $value['m_code'] = str_replace('N','',$value['m_code']);
                    }
                    if(!isset($user['user'][$value['id']])){
                        if(empty($data['user'][$value['id']])){
                            $data['user'][$value['id']]['name'] = $value['name'];
                            $data['user'][$value['id']]['sex']  = $value['sex'] == 1?'女':'男';
                            $data['user'][$value['id']]['code'] = $rgstCode.$value['rgt_numb'];
                            switch ($value['us_qlfts']) {
                                case '01':
                                    $data['user'][$value['id']]['qfctn'] = $rgstCode.'高级审核员';
                                    break;
                                case '02':
                                    $data['user'][$value['id']]['qfctn'] = $rgstCode.'审核员';
                                    break;
                                case '03':
                                    $data['user'][$value['id']]['qfctn'] = $rgstCode.'实习审核员';
                                    break;
                                case '04':
                                    $data['user'][$value['id']]['qfctn'] = $rgstCode.'技术专家';
                                    break;
                                case '05':
                                    $data['user'][$value['id']]['qfctn'] = $rgstCode.'高级审查员';
                                    break;
                                case '06':
                                    $data['user'][$value['id']]['qfctn'] = $rgstCode.'审查员';
                                    break;
                                case '07':
                                    $data['user'][$value['id']]['qfctn'] = $rgstCode.'主任审核员';
                                    break;
                                default:
                                    $data['user'][$value['id']]['qfctn'] = $rgstCode.'其他';
                            };
                            $data['user'][$value['id']]['title']= $value['title']?$value['title']:'';
                            $data['user'][$value['id']]['work'] = $value['work_unit']?$value['work_unit']:'';
                            $data['user'][$value['id']]['role'] = $role;
                            $data['user'][$value['id']]['major']= $value['m_code']?$rgstCode.$value['m_code']:'';
                            $data['user'][$value['id']]['tell'] = $value['telephone'];
                        }else{
                            $data['user'][$value['id']]['role'] .= $rgstCode.$value['role']?' '.$role:'';
                            $data['user'][$value['id']]['code'] .= $rgstCode.$value['rgt_numb']?' '.$rgstCode.$value['rgt_numb']:'';
                            switch ($value['us_qlfts']) {
                                case '01':
                                    $data['user'][$value['id']]['qfctn'] .= $rgstCode.'高级审核员';
                                    break;
                                case '02':
                                    $data['user'][$value['id']]['qfctn'] .= $rgstCode.'审核员';
                                    break;
                                case '03':
                                    $data['user'][$value['id']]['qfctn'] .= $rgstCode.'实习审核员';
                                    break;
                                case '04':
                                    $data['user'][$value['id']]['qfctn'] .= $rgstCode.'技术专家';
                                    break;
                                case '05':
                                    $data['user'][$value['id']]['qfctn'] .= $rgstCode.'高级审查员';
                                    break;
                                case '06':
                                    $data['user'][$value['id']]['qfctn'] .= $rgstCode.'审查员';
                                    break;
                                case '07':
                                    $data['user'][$value['id']]['qfctn'] .= $rgstCode.'主任审核员';
                                    break;
                                default:
                                    $data['user'][$value['id']]['qfctn'] .= $rgstCode.'其他';
                            };
                            $data['user'][$value['id']]['major'].= $value['m_code']?' '.$rgstCode.$value['m_code']:'';
                        }
                    }
                },$rgstCode);
            });
        }catch (\Exception $e){
            return (['status'=>101,'msg'=>$e->getMessage()]);
        }
        $data['user']  = array_values($data['user']);
        $data['plan']  = Auth::guard('api')->user()->name;
        $data['time']  = date('Y-m-d');
        return (['status'=>100,'msg'=>'请求成功','data'=>$data]);
    }

    /**常规管理体系计划导出**/
    public function NoticeExport($data,$site){
        //实例化 phpword 类
        $PHPWord = new PhpWord();
        //指定模板文件
        $templateProcessor = new TemplateProcessor(public_path($site));
        //通过setValue 方法给模板赋值
        foreach ($data as $key=>$value){
            if($key == 'user'){
                $templateProcessor->cloneRow('key',count($value));
                //return($value);die;
                foreach ($value as $key=>$value){
                    $key = $key*1+1;
                    if($key<=26){
                        $keyy = chr($key+64);
                    }else{
                        $keyy = $key;
                    }
                    $templateProcessor->setValue('key#'.$key,$keyy);
                    $templateProcessor->setValue('user#'.$key,$value['name']);
                    $templateProcessor->setValue('sex#'.$key,$value['sex']);
                    $templateProcessor->setValue('qfctn#'.$key,$value['qfctn']);
                    $templateProcessor->setValue('role#'.$key,$value['role']);
                    $templateProcessor->setValue('major#'.$key,$value['major']);
                    $templateProcessor->setValue('phone#'.$key,$value['tell']);
                }
            }else{
                $templateProcessor->setValue($key,$value);
            }
        }
        //保存新word文档
        $path = time().".docx";
        //$path = '质量管理体系'.".docx";
        if (!is_dir(public_path("export"))) {
            mkdir(public_path("export"), 0755, true);
        }
        $export = $templateProcessor->saveAs(public_path("export/".$path ));
        if($export == true){
            return($path);
        }else{
            return(null);
        }
    }

    /**模板导出删除**/
    public function TemplateDelete(Request $request){
/*        if($request->boolean != 'true'){
            return response()->json(['status'=>101,'msg'=>'模板文件未下载完成不能删除']);
        }
        $exists = Storage::disk('export')->exists($request->site);
        if($exists != true){
            return response()->json(['status'=>101,'msg'=>'模板文件不存在或已删除']);
        }
        Storage::disk('export')->delete($request->site);*/
        return response()->json(['status'=>100,'msg'=>'模板文件删除成功']);
    }

    public function TemplateState(Request $request){
        $where  = array(
            ['result_sbmt','=',1],
            ['audit_phase','=','0101'],
        );
        $flights = InspectPlan::select('audit_phase','xm_id','result_sbmt')
            ->where($where)
            ->get();
        if($flights->isEmpty()){
            return response()->json(['status'=>101,'msg'=>'无数据']);
        }
        $flights = $flights->toArray();
        $audit =  array_column($flights, 'xm_id');
        $where = array(
            ['audit_phase', '=','0101'],
        );
        $data = array(
            'result_sbmt' => 1,
        );
        $count = InspectPlan::where($where)
            ->whereIn('xm_id',$audit)
            ->update($data);
        if($count == 0){
            return response()->json(['status'=>101,'msg'=>'一阶段修改失败']);
        }
        return response()->json(['status'=>100,'msg'=>'一阶段修改成功'.$count]);
    }
}
