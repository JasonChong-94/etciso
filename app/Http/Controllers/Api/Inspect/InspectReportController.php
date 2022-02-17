<?php

namespace App\Http\Controllers\Api\Inspect;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Api\Market\MarketContract;
use App\Models\Api\Market\MarketContacts;
use App\Models\Api\Inspect\InspectAuditTeam;
use App\Models\Api\Inspect\InspectPlan;
use App\Models\Api\User\UserBasic;
use App\Models\Api\Examine\ExamineMoney;
use App\Models\Api\Examine\ExamineSystem;
use App\Models\Api\System\SystemUnion;
use App\Models\Api\System\ApprovalGroup;
use App\Http\Controllers\Api\Inspect\InspectExtensionController;
use Illuminate\Support\Facades\DB;

class InspectReportController extends Controller
{
    /**计划上报列表**/
    public function ReportPlan(Request $request){
        $where  = array(
            ['dp_pret','=',1],
            ['user_state','=',1],
            ['audit_phase','<>','0101'],
            ['audit_phase','<>','0201'],
        );
        $flighs = $this->ReportShare($where,$request->limit,$request->field,$request->sort);
        return($flighs);
    }

    /**计划上报查询**/
    public function PlanQuery(Request $request){
        $where  = array(
            ['dp_pret','=',1],
            ['user_state','=',1],
            ['audit_phase','<>','0101'],
            ['audit_phase','<>','0201'],
        );

        if ($request->name) {
            $where[] = ['khxx.qymc', 'like','%'.$request->name.'%'];
        }

        if ($request->xmbh) {
            $where[] = ['xmbh', '=',$request->xmbh];
        }

        if($request->htbh){
            $where[] = ['htbh', '=',$request->htbh];
        }

        if($request->project){
            $where[] = ['rztx', '=',$request->project];
        }

        if($request->stage){
            $where[] = ['audit_phase', '=',$request->stage];
        }

        if($request->region){
            $where[] = ['fzjg', '=',$request->stage];
        }

        $time = array();

        if($request->sbst && $request->sbet){//提交时间
            $time['plan_time'] = [$request->sbst,$request->sbet];
        }

        if($request->rpst && $request->rpet){//上报时间
            $time['report_time'] = [$request->rpst,$request->rpet];
        }

        $flighs = $this->ReportShare($where,$request->limit,$request->field,$request->sort,$time);
        return($flighs);
    }

    /**查询函数**/
    public function ReportShare($where,$limit,$sortField,$sort,$time=''){
        $file = array(
            'khxx.id as id',
            'qyht.id as hid',
            'qyht_htrz.id as xid',
            'qyht_htrza.id as did',
            'xmbh',
            'htbh',
            'qymc',
            'bgdz',
            'rztx',
            'audit_phase',
            'cbt_type',
            'p_cbt_type',
            'start_time',
            'end_time',
            'plan_time',
            'plan_user',
            'report_user',
            'report_time',
            'fz_at',
            'rept_sbmt',
            'union_cmpy',
        );
        switch ($sortField)
        {
            case 1:
                $sortField = 'start_time';
                break;
            case 2:
                $sortField = 'report_time';
                break;
            default:
                $sortField = 'plan_time';
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
        $flighs = MarketContract::ReportPlan($file,$where,$limit,$sortField,$sort,$time,'');
        //dump(DB::getQueryLog());
        if($flighs->isEmpty()){
            return response()->json(['status'=>101,'msg'=>'无数据']);
        }
        foreach ($flighs as $data){
            switch ($data->audit_phase)
            {
                case '0102':
                    $data->activity = '初审';
                    break;
                case '0202':
                    $data->activity = '再认证';
                    break;
                case '03':
                    $data->activity = '监督1';
                    break;
                case '07':
                    $data->activity = '监督2';
                    break;
                case '04':
                    $data->activity = '特殊审核';
                    break;
                case '05':
                    $data->activity = '变更审核';
                    break;
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
            }else{
                $groud = array_filter($teamUser->toArray(), function ($value) {
                    if($value['role'] == '01'){
                        return($value);
                    }
                });
                $data->groud  = implode(";",array_column($groud, 'name'));
                $data->peple  = implode(";",array_column($teamUser->toArray(), 'name'));
            }
            $data->cbt_type  = $data->p_cbt_type?$data->p_cbt_type:$data->cbt_type;
            $unionWhere = array(
                ['code','=',$data->cbt_type],
                ['state','=',1]
            );
            $union = SystemUnion::IndexUnion('union',$unionWhere);
            $data->cbt_type = $union->first()->union;
            switch ($data->rept_sbmt)
            {
                case 0:
                    $data->report_state = '未上报';
                    break;
                case 1:
                    $data->report_state = '已上报';
                    break;
                case 2:
                    $data->report_state = '已修改';
                    break;
                case 3:
                    $data->report_state = '修改中';
                    break;
            }
        }
        return response()->json(['status'=>100,'msg'=>'请求成功','data'=>$flighs]);
    }

    /**计划上报详情**/
    public function PlanDetail(Request $request){
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
            'report_time',
            'result_time',
            'rept_sbmt',
            'result_sbmt',
        );
        if(!$request->union_cmpy){
            $where = array(
                ['ht_id','=',$request->hid],
                ['audit_phase','=',$request->code],
                ['audit_phase','=',$request->code],
                ['dp_pret','=',1],
                ['user_state','=',1],
            );
            $flighs = InspectPlan::PhasePlan($file,$where);
        }else{
            $where = array(
                ['dp_pret','=',1],
                ['user_state','=',1],
            );
            $cmpy = explode(";",$request->union_cmpy);
            $flighs = InspectPlan::UnionPlan($file,$cmpy,$where);
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

    /**计划/结果状态修改**/
    public function PlanState(Request $request){
        $flighs = InspectPlan::find($request->did);
        switch ($request->type)
        {
            case '0':
                if(!$flighs->report_time){
                    return response()->json(['status'=>101,'msg'=>'该项目计划未保存']);
                }
                if($flighs->rept_sbmt != 0 && $flighs->rept_sbmt != 2){
                    return response()->json(['status'=>101,'msg'=>'该项目已完上报或正在修改']);
                }
                $flight = $this->PlanReport($flighs);
                break;
            case '1':
                if(!$flighs->result_time){
                    return response()->json(['status'=>101,'msg'=>'该项目结果未保存']);
                }
                if($flighs->result_sbmt != 0 && $flighs->result_sbmt != 2){
                    return response()->json(['status'=>101,'msg'=>'该项目已完上报或正在修改']);
                }
/*                if($flighs->result_sbmt == 0){
                    $fligha =  new InspectExtensionController;
                    $flight = $fligha->add($request->did);
                }*/
                $flight = $this->ResultReport($flighs);
                break;
            case '2':
                if(!$flighs->result_time){
                    return response()->json(['status'=>101,'msg'=>'该项目暂停撤销未保存']);
                }
                if($flighs->adopt_sbmt != 1){
                    return response()->json(['status'=>101,'msg'=>'该项目暂停撤销未审批']);
                }
               if($flighs->result_sbmt != 0 && $flighs->result_sbmt != 2){
                    return response()->json(['status'=>101,'msg'=>'该项目已完上报或正在修改']);
                }
                $flight = $this->RevokeReport($flighs);
                break;
        }
        return ($flight);
    }

    /**计划状态修改**/
    protected function PlanReport($request){
        if($request->audit_phase == '0102' || $request->audit_phase == '0202'){
            switch ($request->audit_phase)
            {
                case '0102':
                    if($request->rept_sbmt == 0){
                        $flighs = InspectPlan::where([
                            ['xm_id','=',$request->xm_id],
                            ['audit_phase','=','0101'],
                        ])
                            ->update(['rept_sbmt' => 1]);
                        if($flighs == 0){
                            return response()->json(['status'=>101,'msg'=>'初审一阶段状态修改失败']);
                        }
                    }
                    break;
                case '0202':
                    if($request->rept_sbmt == 0){
                        $flighs = InspectPlan::where([
                            ['xm_id','=',$request->xm_id],
                            ['audit_phase','=','0201'],
                        ])
                            ->get();
                        if(!$flighs->isEmpty()){
                            $flighs = InspectPlan::find($flighs->first()->id);
                            $flighs->rept_sbmt  = 1;
                            if(!$flighs->save()){
                                return response()->json(['status'=>101,'msg'=>'再认证一阶段状态修改失败']);
                            }
                        }
                    }
                    break;
            }
        }
        $request->rept_sbmt  = 1;
        if($request->save()){
            return response()->json(['status'=>100,'msg'=>'状态修改成功']);
        }
        return response()->json(['status' => 101, 'msg' => '状态修改失败']);

    }

    /**结果状态修改**/
    protected function ResultReport($request){
        DB::beginTransaction();
        try {
            $flighs = ExamineSystem::find($request->xm_id);
            switch ($request->audit_phase)
            {
                case '0102':
                    if($request->result_sbmt == 0){
                        $data = array(
                            'p_rzbz'  => $flighs->rzbz,
                            'p_yxrs'  => $flighs->yxrs,
                            'p_rev_range'   => $flighs->rev_range,
                            'p_rev_range_e' => $flighs->rev_range_e,
                            'p_major_code'  => $flighs->major_code,
                            'p_risk_level'  => $flighs->risk_level
                        );
                        $flight = InspectPlan::where([
                            ['xm_id','=',$request->xm_id],
                            ['audit_phase','=','0101'],
                        ])
                            ->update($data);
                        if($flight == 0){
                            return response()->json(['status'=>101,'msg'=>'初审一阶段信息修改失败']);
                        }
                    }
                    $stage = array(
                        ['xm_id' => $request->xm_id, 'audit_phase' => '03','plan_m'=>1]
                    );
                    $flighs->ji_shlx  = '03';
                    $flighs->sh_time  = $request->evte_time;
                    $flighs->dl_time  = date("Y-m-d", strtotime("$flighs->sh_time 1 year"));
                    break;
                case '03':
                    $stage = array(
                        ['xm_id' => $request->xm_id, 'audit_phase' => '07','plan_m'=>1]
                    );
                    $flighs->ji_shlx  = '07';
                    $flighs->shlx     = '03';
                    $flighs->dl_time  = date("Y-m-d", strtotime("$flighs->sh_time 2 year"));
                    break;
                case '07':
                    $flighs->ji_shlx  = '02';
                    $flighs->shlx     = '07';
                    $flighs->dl_time  = date("Y-m-d", strtotime("$flighs->sh_time 3 year"));
                    break;
                case '0202':
                    if($request->result_sbmt == 0) {
                        $flight = InspectPlan::where([
                            ['xm_id', '=', $request->xm_id],
                            ['audit_phase', '=', '0201'],
                        ])
                            ->get();
                        if (!$flight->isEmpty()) {
                            $flight = InspectPlan::find($flight->first()->id);
                            $flight->p_rzbz = $flighs->rzbz;
                            $flight->p_yxrs = $flighs->yxrs;
                            $flight->p_rev_range = $flighs->rev_range;
                            $flight->p_rev_range_e = $flighs->rev_range_e;
                            $flight->p_major_code = $flighs->major_code;
                            $flight->p_risk_level = $flighs->risk_level;
                            if (!$flight->save()) {
                                return response()->json(['status' => 101, 'msg' => '再认证一阶段信息修改失败']);
                            }
                        }
                    }
                    $stage = array(
                        ['xm_id' => $request->id, 'audit_phase' => '03','plan_m'=>1],
                    );
                    $flighs->ji_shlx  = '03';
                    $flighs->sh_time  = $request->evte_time;
                    $flighs->dl_time  = date("Y-m-d", strtotime("$flighs->sh_time 1 year"));
                    break;
            }
            $flighs->ji_time  = date("Y-m-d", strtotime("$flighs->dl_time -3 month"));
            $flighs->zs_m     = $request->p_zs_m;
            $flighs->zs_nb    = $request->p_zs_nb;
            $flighs->zs_ftime = $request->p_zs_ftime;
            $flighs->zs_etime = $request->p_zs_etime;
            $flighs->subset   = $request->sub_state;
            $flighs->sub_nb   = $request->p_sub_nb;
            if(!$flighs->save()){
                return response()->json(['status'=>101,'msg'=>'证书信息修改失败']);
            };
            $request->p_rzbz  = $flighs->rzbz;
            $request->p_yxrs  = $flighs->yxrs;
            $request->p_rev_range   = $flighs->rev_range;
            $request->p_rev_range_e = $flighs->rev_range_e;
            $request->p_major_code  = $flighs->major_code;
            $request->p_risk_level  = $flighs->risk_level;
            $request->result_sbmt   = 1;
            if(!$request->save()){
                return response()->json(['status'=>101,'msg'=>'阶段信息修改失败']);
            };
            if(!empty($stage)){
                InspectPlan::insert($stage);
            }
            DB::commit();
        }catch (\Exception $e){
            DB::rollback();
            return response()->json(['status'=>101,'msg'=>'状态修改失败'.$e->getMessage()]);
        }
        return response()->json(['status'=>100,'msg'=>'状态修改成功']);
    }

    /**暂停状态修改**/
    protected function RevokeReport($request){
        DB::beginTransaction();
        try {
            $flighs = ExamineSystem::find($request->xm_id);
            $flighs->zs_m = $request->p_zs_m;
            if(!$flighs->save()){
                return response()->json(['status'=>101,'msg'=>'证书状态修改失败']);
            };
            $request->result_sbmt   = 1;
            if(!$request->save()){
                return response()->json(['status'=>101,'msg'=>'暂停撤销修改失败']);
            };
            DB::commit();
        }catch (\Exception $e){
            DB::rollback();
            return response()->json(['status'=>101,'msg'=>'状态修改失败'.$e->getMessage()]);
        }
        return response()->json(['status'=>100,'msg'=>'状态修改成功']);
    }

    /**计划上报保存**/
    public function PlanSubmit(Request $request){
        if($request->rept_sbmt == 0 || $request->rept_sbmt == 2){
            $flighs = InspectPlan::find($request->did);
            $flighs->report_time= $request->report_time;
            $flighs->report_user= Auth::guard('api')->user()->name;
            if(!$flighs->save()){
                return response()->json(['status'=>101,'msg'=>'计划上报保存失败']);
            }
            return response()->json(['status' => 100, 'msg' => '计划上报保存成功']);
        }else{
            return response()->json(['status'=>101,'msg'=>'该项目已完上报或正在修改']);
        }
    }

    /**计划上报导出**/
    public function PlanExport(Request $request){
        $file = array(
            'khxx.id as id',
            'qyht_htrza.id as did',
            'qymc',
            'xydm',
            'bgdz',
            'dqdm',
            'rztx',
            'regt_numb',
            'zs_nb',
            'rule_code',
            'fmech_numb',
            'audit_phase',
            'start_time',
            'end_time',
        );
        if(!$request->union_did){
            return response()->json(['status'=>101,'msg'=>'请选择要上报的项目']);
        }
        $cmpy = explode(";",$request->union_did);
        $flighs = MarketContract::UnionPlan($file,$cmpy);
        if($flighs->isEmpty()){
            return response()->json(['status'=>101,'msg'=>'无数据']);
        }
        $flight = array();
        $flighs = $flighs->toArray();
        try{
            array_walk($flighs,function ($value,$key)  use (&$flight){
                $file = array(
                    'ap_id',
                    'users.id',
                    'role',
                    'name',
                    'telephone',
                    'cft_type',
                    'cft_numb',
                    'rgt_numb',
                );
                $where = array(
                    ['qyht_htrzu.ap_id','=',$value['did']],
                    ['rgt_type','=',$value['rztx']]
                );
                $userTeam = InspectAuditTeam::PlanTeam($file,$where);
                if($userTeam->isEmpty()){
                    throw new Exception('没有添加审核人员');
                }
                $userTeam = $userTeam->toArray();
                $userPlan = array();
                array_walk($userTeam,function ($value,$key)  use (&$userPlan){
                    if($value['role'] == '03'){
                        if(!isset($userPlan['expert'])){
                            $userPlan['expert'] = $value['name'].','.$value['cft_type'].','.$value['cft_numb'].','.$value['telephone'];
                        }else{
                            $userPlan['expert'].= ';'.$value['name'].','.$value['cft_type'].','.$value['cft_numb'].','.$value['telephone'];
                        }
                    }else{
                        if(!isset($userPlan['auditor'])){
                            $userPlan['auditor'] = $value['rgt_numb'].','.$value['name'].','.$value['role'].','.$value['telephone'];
                        }else{
                            $userPlan['auditor'].= ';'.$value['rgt_numb'].','.$value['name'].','.$value['role'].','.$value['telephone'];
                        }
                    }
                });
                if(isset($userPlan['expert'])){
                    $userPlan['expert'] = $userPlan['expert'];
                }else{
                    $userPlan['expert'] = null;
                }
                $value['expert'] = $userPlan['expert'];
                $value['auditor']= $userPlan['auditor'];
                $fileTell = array(
                    'name',
                    'phone',
                    'tell',
                );
                $whereTell = array(
                    ['kh_id','=',$value['id']],
                    ['contacts.state','=',1]
                );
                $userTell = MarketContacts::IndexContacts($fileTell,$whereTell);
                if($userTell->isEmpty()){
                    throw new Exception('没有默认的联系人，请联系项目负责人');
                }
                if(!isset($flight[$value['start_time'].'-'.$value['end_time']])){
                    $flight[$value['start_time'].'-'.$value['end_time']] = $value;
                    $flight[$value['start_time'].'-'.$value['end_time']]['code'] = date('ymdHis').$value['audit_phase'];
                    $flight[$value['start_time'].'-'.$value['end_time']]['country'] = 156;
                    $flight[$value['start_time'].'-'.$value['end_time']]['adopt'] = 'R-2017-318';
                    $flight[$value['start_time'].'-'.$value['end_time']]['contacts'] = $userTell->first()->name;
                    $flight[$value['start_time'].'-'.$value['end_time']]['tell'] = $userTell->first()->phone?$userTell->first()->phone:$userTell->first()->tell;
                }else{
                    if(!$flight[$value['start_time'].'-'.$value['end_time']]['audit_phase']){
                        $flight[$value['start_time'].'-'.$value['end_time']]['audit_phase'] = $value['audit_phase'];
                    }else{
                        $flight[$value['start_time'].'-'.$value['end_time']]['audit_phase'].= ';'.$value['audit_phase'];
                    }
                    if(!$flight[$value['start_time'].'-'.$value['end_time']]['auditor']){
                        $flight[$value['start_time'].'-'.$value['end_time']]['auditor'] = $value['auditor'];
                    }else{
                        $flight[$value['start_time'].'-'.$value['end_time']]['auditor'].= ';'.$value['auditor'];
                    }
                    if(!$flight[$value['start_time'].'-'.$value['end_time']]['expert']){
                        $flight[$value['start_time'].'-'.$value['end_time']]['expert'] = $value['expert'];
                    }else{
                        $flight[$value['start_time'].'-'.$value['end_time']]['expert'].= ';'.$value['expert'];
                    }
                    if(!$flight[$value['start_time'].'-'.$value['end_time']]['regt_numb']){
                        $flight[$value['start_time'].'-'.$value['end_time']]['regt_numb'] = $value['regt_numb'];
                    }else{
                        $flight[$value['start_time'].'-'.$value['end_time']]['regt_numb'].= ';'.$value['regt_numb'];
                    }
                    if(!$flight[$value['start_time'].'-'.$value['end_time']]['zs_nb']){
                        $flight[$value['start_time'].'-'.$value['end_time']]['zs_nb'] = $value['zs_nb'];
                    }else{
                        $flight[$value['start_time'].'-'.$value['end_time']]['zs_nb'].= ';'.$value['zs_nb'];
                    }
                    if(!$flight[$value['start_time'].'-'.$value['end_time']]['rule_code']){
                        $flight[$value['start_time'].'-'.$value['end_time']]['rule_code'] = $value['rule_code'];
                    }else{
                        $flight[$value['start_time'].'-'.$value['end_time']]['rule_code'].= ';'.$value['rule_code'];
                    }
                }
            });
        }catch (\Exception $e){
            return response()->json(['status'=>101,'msg'=>$e->getMessage()]);
        }
        return response()->json(['status'=>100,'msg'=>'请求成功','data'=>array_values($flight)]);
    }

    /**暂停撤销列表**/
    public function ReportRevoke(Request $request){
        $where  = array(
            ['change_type','=',1],
            ['audit_phase','05'],
        );
        $flighs = $this->RevokeShare($where,$request->limit,$request->field,$request->sort);
        return($flighs);
    }

    /**暂停撤销查询**/
    public function RevokeQuery(Request $request){
        $where  = array(
            ['change_type','=',1],
            ['audit_phase','05'],
        );
        if($request->name){
            $where[] = ['khxx.qymc','like', '%'.$request->name.'%'];
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

        if($request->state){
            $where[] = ['p_zs_m', '=',$request->state];
        }
        if($request->region){
            $where[] = ['fzjg', '=',$request->region];
        }

        if($request->spst && $request->spet){//暂停时间
            $where[] = ['suspend_start', '>=',$request->spst];
            $where[] = ['suspend_end', '<=',$request->spet];
        }

        $time = array();

        if($request->frst && $request->fret){//首次发证
            $time['first_time'] = [$request->frst,$request->fret];
        }

        if($request->zfst && $request->zfet){//颁证时间
            $time['p_zs_ftime'] = [$request->zfst,$request->zfet];
        }

        if($request->cgst && $request->cget){//变更时间
            $time['change_time'] = [$request->cgst,$request->cget];
        }

        if($request->rkst && $request->rket){//撤销时间
            $time['revoke_time'] = [$request->rkst,$request->rket];
        }

        if($request->rpst && $request->rpet){//上报时间
            $time['report_time'] = [$request->rpst,$request->rpet];
        }

        $flighs = $this->RevokeShare($where,$request->limit,$request->field,$request->sort,$time);
        return($flighs);
    }

    /**查询函数**/
    public function RevokeShare($where,$limit,$sortField,$sort,$time=''){
        $file = array(
            'khxx.id as id',
            'qyht.id as hid',
            'qyht_htrz.id as xid',
            'qyht_htrza.id as did',
            'xmbh',
            'htbh',
            'qymc',
            'rztx',
            'audit_phase',
            'adopt_user',
            'adopt_time',
            'p_cer_type',
            'p_zs_m',
            'p_zs_nb',
            'first_time',
            'p_zs_ftime',
            'p_zs_etime',
            'fz_at',
            'adopt_sbmt',
            'result_user',
            'result_time',
            'result_sbmt',
        );
        switch ($sortField)
        {
            case 1:
                $sortField = 'first_time';
                break;
            case 2:
                $sortField = 'p_zs_ftime';
                break;
            case 3:
                $sortField = 'adopt_time';
            default:
                $sortField = 'qyht_htrza.id';
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
        $flighs = MarketContract::ReportPlan($file,$where,$limit,$sortField,$sort,$time,'');
        //dump(DB::getQueryLog());
        if($flighs->isEmpty()){
            return response()->json(['status'=>101,'msg'=>'无数据']);
        }
        $flighs = $flighs->toArray();
        array_walk($flighs['data'], function (&$value,$key){
            switch ($value['audit_phase'])
            {
                case '0102':
                    $value['audit_phase'] = '初审';
                    break;
                case '0202':
                    $value['audit_phase'] = '再认证';
                    break;
                case '03':
                    $value['audit_phase'] = '监督1';
                    break;
                case '07':
                    $value['audit_phase'] = '监督2';
                    break;
                case '04':
                    $value['audit_phase'] = '特殊审核';
                    break;
                case '05':
                    $value['audit_phase'] = '变更审核';
                    break;
            }
            switch ($value['p_cer_type'])
            {
                case '01':
                    $value['p_cer_type'] = 'CNAS';
                    break;
                case '02':
                    $value['p_cer_type'] = 'UKAS';
                    break;
                case '03':
                    $value['p_cer_type'] = 'JAS-ANS';
                    break;
                case '00':
                    $value['p_cer_type'] = 'ETC';
                    break;
            }
            switch ($value['p_zs_m'])
            {
                case '01':
                    $value['state'] = '有效';
                    break;
                case '02':
                    $value['state'] = '暂停';
                    break;
                case '03':
                    $value['state'] = '撤销';
                    break;
                case '05':
                    $value['state'] = '过期失效';
                    break;
            }
            switch ($value['adopt_sbmt'])
            {
                case 1:
                    $value['adopt_name'] = '通过';
                    break;
                case 2:
                    $value['adopt_name'] = '拒绝';
                    break;
                case 3:
                    $value['adopt_name'] = '已申请';
                    break;
                default:
                    $value['adopt_name'] = '未申请';
            }
            switch ($value['result_sbmt'])
            {
                case 0:
                    $value['result_state'] = '未上报';
                    break;
                case 1:
                    $value['result_state'] = '已上报';
                    break;
                case 2:
                    $value['result_state'] = '已修改';
                    break;
                case 3:
                    $value['result_state'] = '修改中';
                    break;
            }
        });
        return response()->json(['status'=>100,'msg'=>'请求成功','data'=>$flighs]);
    }

    /**证书暂停撤销详情**/
    public function RevokeDetail(Request $request){
        $file = array(
            'qyht_htrza.id as did',
            'xmbh',
            'rztx',
            'audit_phase',
            'activity',
            'p_rzbz',
            'p_rev_range',
            'p_cer_type',
            'p_zs_nb',
            'first_time',
            'p_zs_ftime',
            'p_zs_etime',
            'p_zs_m',
            'evte_cw',
            'evte_time',
            'report_type',
            'change',
            'change_time',
            'suspend',
            'suspend_start',
            'suspend_end',
            'revoke',
            'revoke_time',
            'adopt_sbmt',
            'result_sbmt',
            'result_time',
        );
        $where= array(
            ['qyht_htrza.id','=',$request->did],
        );
        $flighs = InspectPlan::PhasePlan($file,$where);
        if($flighs->isEmpty()){
            return response()->json(['status'=>101,'msg'=>'无数据']);
        }
        $flighs = $flighs->first();
        if($flighs->change){
            $flighs->change = explode(";",$flighs->change);
        }
        $userId = substr($flighs->evte_cw, 1, -1);
        $cltUser = explode(";", $userId);
        $file = array(
            'id',
            'name'
        );
        $evteUser = UserBasic::IndexBasic($file, 'id', $cltUser);
        if($evteUser->isEmpty()){
            return response()->json(['status'=>101,'msg'=>'该项目未发现认证决定人员']);
        }else{
            $evteUser = $evteUser->toArray();
            $flighs->evte_cw = implode(";", array_column($evteUser, 'name'));
        }
        switch ($flighs->p_cer_type)
        {
            case '01':
                $flighs->cer_type = 'CNAS';
                break;
            case '02':
                $flighs->cer_type = 'UKAS';
                break;
            case '03':
                $flighs->cer_type = 'JAS-ANS';
                break;
            case '00':
                $flighs->cer_type = 'ETC';
                break;
        }
        $flight[] = $flighs;
        return response()->json(['status'=>100,'msg'=>'请求成功','data'=>$flight]);
    }

    /**证书暂停撤销保存**/
    public function RevokeAdd(Request $request){
        if($request->result_sbmt == 0 || $request->result_sbmt == 2) {
            $flighs = InspectPlan::find($request->did);
            $flighs->report_type = $request->report_type;
            $flighs->change = $request->change;
            $flighs->change_time = $request->change_time;
            $flighs->result_time = $request->report_time;
            $flighs->result_user = Auth::guard('api')->user()->name;
            switch ($request->p_zs_m) {
                case '02':
                    if (!$request->suspend || !$request->suspend_start || !$request->suspend_end) {
                        return response()->json(['status' => 101, 'msg' => '证书暂停上报数据不完整']);
                    }
                    $flighs->suspend = $request->suspend;
                    $flighs->suspend_start = $request->suspend_start;
                    $flighs->suspend_end = $request->suspend_end;
                    break;
                case '03':
                    if (!$request->revoke || !$request->revoke_time) {
                        return response()->json(['status' => 101, 'msg' => '证书撤销上报数据不完整']);
                    }
                    $flighs->revoke = $request->revoke;
                    $flighs->revoke_time = $request->revoke_time;
                    break;
            }
            if (!$flighs->save()) {
                return response()->json(['status' => 101, 'msg' => '暂停撤销信息保存失败']);
            }
            return response()->json(['status' => 100, 'msg' => '暂停撤销信息保存成功']);
        }else{
            return response()->json(['status'=>101,'msg'=>'该项目已完上报或正在修改']);
        }
    }
    /**暂停撤销删除**/
    public function RevokeDelt(Request $request){
        $flighs = InspectPlan::find($request->did);
        if($flighs->result_time){
            return response()->json(['status'=>101,'msg'=>'该项目暂停撤销已保存，不能删除']);
        }
        if($flighs->adopt_sbmt == 1){
            return response()->json(['status'=>101,'msg'=>'该项目暂停撤销已审批，不能删除']);
        }
        if($flighs->result_sbmt == 1){
            return response()->json(['status'=>101,'msg'=>'该项目已完上报，不能删除']);
        }
        if($flighs->delete()){
            return response()->json(['status'=>100,'msg'=>'暂停撤销删除成功']);
        };
        return response()->json(['status'=>101,'msg'=>'暂停撤销删除失败']);
    }

    /**证书暂停撤销导出**/
    public function RevokeExport(Request $request){
        $file = array(
            'khxx.id as id',
            'qymc',
            'qymc',
            'xydm',
            'natl_code',
            'dqdm',
            'zcdz',
            'postal_code',
            'frdb',
            'qyxz',
            'zczb_my',
            'zczb_bz',
            'qyrs',
            'p_yxrs',
            'first_time',
            'p_zs_nb',
            'rule_code',
            'p_major_code',
            'p_rzbz',
            'm_sites',
            'tmpy_site',
            'p_rev_range',
            'audit_phase',
            'rz_nub',
            'jd_nub',
            'p_cbt_type',
            'evte_cw',
            'evte_time',
            'p_zs_ftime',
            'p_zs_etime',
            'p_zs_m',
            'suspend',
            'suspend_start',
            'suspend_end',
            'revoke',
            'revoke_time',
            'sub_state',
            'p_zs_nb',
            'change_time',
            'change',
            'p_cer_type',
            'p_risk_level',
            'report_type',
        );
        if(!$request->union_did){
            return response()->json(['status'=>101,'msg'=>'请选择要上报的项目']);
        }
        $unionId = explode(";",$request->union_did);
        //DB::connection()->enableQueryLog();#开启执行日志
        $flighs = MarketContract::UnionPlan($file,$unionId);
        //dump(DB::getQueryLog());
        if($flighs->isEmpty()){
            return response()->json(['status'=>101,'msg'=>'无数据']);
        }
        $flighs = $flighs->toArray();
        $userRevoke = array();
        try {
            array_walk($flighs,function ($value,$key)  use (&$userRevoke){
                $value['etc_code'] = 'CNCA-R-2017-318';
                $value['etc_name'] = '亿信标准认证集团有限公司';
                $value['country'] = '156';
                $fileTell = array(
                    'phone',
                    'tell',
                );
                $whereTell = array(
                    ['kh_id','=',$value['id']],
                    ['contacts.state','=',1]
                );
                $userTell = MarketContacts::IndexContacts($fileTell,$whereTell);
                if($userTell->isEmpty()){
                    throw new Exception('没有默认的联系人，请联系项目负责人');
                }
                $value['tell'] = $userTell->first()->phone?$userTell->first()->phone:$userTell->first()->tell;
                $userId = substr($value['evte_cw'], 1, -1);
                $cltUser = explode(";", $userId);
                $file = array(
                    'id',
                    'name'
                );
                $evteUser = UserBasic::IndexBasic($file, 'id', $cltUser);
                if($evteUser->isEmpty()){
                    throw new Exception('该项目未发现认证决定人员');
                }
                $evteUser = $evteUser->toArray();
                $value['evte_cw'] = implode(";", array_column($evteUser, 'name'));
                $value['p_cer_type'] = $value['p_cer_type'] == '00'?'99':$value['p_cer_type'];
                $userRevoke[] = $value;
            });
        } catch (\Exception $e) {
            return response()->json(['status'=>101,'msg'=>$e->getMessage()]);
        }
        return response()->json(['status'=>100,'msg'=>'请求成功','data'=>array_values($userRevoke)]);
    }

    /**证书结果列表**/
    public function ReportResult(Request $request){
        $where  = array(
            ['print_sbmt','=',1],
            ['audit_phase','<>','0201'],
            ['audit_phase','<>','0101'],
        );
        $flighs = $this->ResultShare($where,$request->limit,$request->field,$request->sort);
        return($flighs);
    }

    /**证书结果查询**/
    public function ResultQuery(Request $request){
        $where  = array(
            ['print_sbmt','=',1],
            ['audit_phase','<>','0201'],
            ['audit_phase','<>','0101'],
        );
        if($request->name){
            $where[] = ['khxx.qymc','like', '%'.$request->name.'%'];
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

        if($request->type){
            switch ($request->type) {
                case '01':
                    $request->type = '0102';
                    break;
                case '02':
                    $request->type = '0202';
                    break;
                case '03':
                    $request->type = '03';
                    break;
                case '07':
                    $request->type = '07';
                    break;
                case '04':
                    $request->type = '04';
                    break;
                case '05':
                    $request->type = '05';
                    break;
            }
            $where[] = ['audit_phase', '=',$request->$request->typ];
        }

        if($request->state){
            $where[] = ['p_zs_m', '=',$request->state];
        }
        if($request->region){
            $where[] = ['fzjg', '=',$request->region];
        }

        $time = array();

        if($request->frst && $request->fret){//首次发证
            $time['first_time'] = [$request->frst,$request->fret];
        }

        if($request->zfst && $request->zfet){//颁证时间
            $time['p_zs_ftime'] = [$request->zfst,$request->zfet];
        }

        if($request->rpst && $request->rpet){//上报时间
            $time['report_time'] = [$request->rpst,$request->rpet];
        }

        $flighs = $this->ResultShare($where,$request->limit,$request->field,$request->sort,$time);
        return($flighs);
    }

    /**查询函数**/
    public function ResultShare($where,$limit,$sortField,$sort,$time=''){
        $file = array(
            'khxx.id as id',
            'qyht.id as hid',
            'qyht_htrz.id as xid',
            'qyht_htrza.id as did',
            'xmbh',
            'htbh',
            'qymc',
            'rztx',
            'audit_phase',
            'rzbz',
            'rev_range',
            'p_rev_range',
            'print_user',
            'print_time',
            'p_cer_type',
            'p_zs_m',
            'p_zs_nb',
            'first_time',
            'p_zs_ftime',
            'p_zs_etime',
            'fz_at',
            'result_user',
            'result_time',
            'result_sbmt',
        );
        switch ($sortField)
        {
            case 1:
                $sortField = 'print_time';
                break;
            case 2:
                $sortField = 'first_time';
                break;
            case 3:
                $sortField = 'p_zs_ftime';
                break;
            default:
                $sortField = 'print_time';
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
        $flighs = MarketContract::ReportPlan($file,$where,$limit,$sortField,$sort,$time,'');
        //dump(DB::getQueryLog());
        if($flighs->isEmpty()){
            return response()->json(['status'=>101,'msg'=>'无数据']);
        }
        $flighs = $flighs->toArray();
        array_walk($flighs['data'], function (&$value,$key){
            switch ($value['audit_phase'])
            {
                case '0102':
                    $value['activity'] = '初审';
                    break;
                case '0202':
                    $value['activity'] = '再认证';
                    break;
                case '03':
                    $value['activity'] = '监督1';
                    break;
                case '07':
                    $value['activity'] = '监督2';
                    break;
                case '04':
                    $value['activity'] = '特殊审核';
                    break;
                case '05':
                    $value['activity'] = '变更审核';
                    break;
            }
            switch ($value['p_cer_type'])
            {
                case '01':
                    $value['p_cer_type'] = 'CNAS';
                    break;
                case '02':
                    $value['p_cer_type'] = 'UKAS';
                    break;
                case '03':
                    $value['p_cer_type'] = 'JAS-ANS';
                    break;
                case '00':
                    $value['p_cer_type'] = 'ETC';
                    break;
            }
            switch ($value['p_zs_m'])
            {
                case '01':
                    $value['state'] = '有效';
                    break;
                case '02':
                    $value['state'] = '暂停';
                    break;
                case '03':
                    $value['state'] = '撤销';
                    break;
                case '05':
                    $value['state'] = '过期失效';
                    break;
            }
            switch ($value['result_sbmt'])
            {
                case 0:
                    $value['result_state'] = '未上报';
                    break;
                case 1:
                    $value['result_state'] = '已出证';
                    break;
                case 2:
                    $value['result_state'] = '已修改';
                    break;
                case 3:
                    $value['result_state'] = '修改中';
                    break;
            }
            $value['rev_range'] = $value['p_rev_range']?$value['p_rev_range']:$value['rev_range'];
        });
        return response()->json(['status'=>100,'msg'=>'请求成功','data'=>$flighs]);
    }

    /**证书结果详情**/
    public function ResultDetail(Request $request){
        $file = array(
            'qyht_htrza.id as did',
            'xmbh',
            'rztx',
            'audit_phase',
            'activity',
            'rzbz',
            'p_rzbz',
            'rev_range',
            'p_rev_range',
            'p_zs_nb',
            'first_time',
            'p_zs_ftime',
            'p_zs_etime',
            'p_zs_m',
            'evte_cw',
            'evte_time',
            'report_type',
            'change',
            'change_time',
            'adopt_sbmt',
            'result_sbmt',
            'result_time',
        );
        $where = array(
            ['ht_id','=',$request->hid],
            ['audit_phase','=',$request->code],
            ['print_sbmt','=',1],
        );
        $flighs = InspectPlan::PhasePlan($file,$where);
        if($flighs->isEmpty()){
            return response()->json(['status'=>101,'msg'=>'无数据']);
        }
        $fligh = $flighs->toArray();
        array_walk($fligh, function (&$value,$key) {
            $userId = substr($value['evte_cw'], 1, -1);
            $cltUser = explode(";", $userId);
            $file = array(
                'id',
                'name'
            );
            $evteUser = UserBasic::IndexBasic($file, 'id', $cltUser);
            if($evteUser->isEmpty()){
                $value['evte_cw'] = '';
            }else{
                $evteUser = $evteUser->toArray();
                $value['evte_cw'] = implode(";", array_column($evteUser, 'name'));
            }
            $value['p_rzbz'] = $value['p_rzbz']?$value['p_rzbz']:$value['rzbz'];
            $value['p_rev_range'] = $value['p_rev_range']?$value['p_rev_range']:$value['rev_range'];
            if($value['change']){
                $value['change'] = explode(";", $value['change']);
            }
        });
        return response()->json(['status'=>100,'msg'=>'请求成功','data'=>$fligh]);
    }

    /**证书结果保存**/
    public function ResultSubmit(Request $request){
        $flighs = InspectPlan::find($request->did);
        if($flighs->result_sbmt == 0 || $flighs->result_sbmt == 2){
            $flighs->report_type = $request->report_type;
            $flighs->change = $request->change;
            $flighs->change_time = $request->change_time;
            $flighs->result_time = $request->report_time;
            $flighs->result_user = Auth::guard('api')->user()->name;
            if(!$flighs->save()){
                return response()->json(['status'=>101,'msg'=>'证书结果保存失败']);
            }
            return response()->json(['status' => 100, 'msg' => '证书结果保存成功']);
        }else{
            return response()->json(['status'=>101,'msg'=>'该项目已完上报或正在修改']);
        }
    }

    /**证书结果导出(证书信息)**/
    public function ResultExport(Request $request){
        DB::beginTransaction();
        try {
            $file = array(
                'khxx.id as id',
                'qyht.id as hid',
                'qyht_htrz.id as xid',
                'qymc',
                'xydm',
                'natl_code',
                'dqdm',
                'zcdz',
                'postal',
                'bgdz',
                'scdz',
                'postal_code',
                'frdb',
                'qyxz',
                'zczb_my',
                'zczb_bz',
                'qyrs',
                'yxrs',
                'first_time',
                'p_zs_nb',
                'shlx',
                'rule_code',
                'major_code',
                'rzbz',
                'm_sites',
                'tmpy_site',
                'rev_range',
                'p_rev_range',
                'audit_phase',
                'rz_nub',
                'cbt_type',
                'actual_s',
                'actual_e',
                'field_day',
                'evte_cw',
                'evte_time',
                'p_zs_ftime',
                'p_zs_etime',
                'p_zs_m',
                'sub_state',
                'change_time',
                'change',
                'replace_state',
                'replace_time',
                'replace_reason',
                'zs_nb',
                'cer_type',
                'p_risk_level',
                'htbh',
                'report_type',
            );
            if(!$request->union_did){
                return response()->json(['status'=>101,'msg'=>'请选择要上报的项目']);
            }
            $unionId = explode(";",$request->union_did);
            //DB::connection()->enableQueryLog();#开启执行日志
            $flighs = MarketContract::UnionPlan($file,$unionId);
            //dump(DB::getQueryLog());
            if($flighs->isEmpty()){
                return response()->json(['status'=>101,'msg'=>'无数据']);
            }
            $flighs = $flighs->toArray();
            $userResult = array();
            array_walk($flighs,function ($value,$key)  use (&$userResult){
                switch ($value['shlx'])
                {
                    case '01':
                        $value['jd_nub']= 0;
                        break;
                    case '03':
                        $value['jd_nub']= 1;
                        break;
                    case '07':
                        $value['jd_nub']= 2;
                        break;
                    case '02':
                        $value['jd_nub']= 0;
                        break;
                }
                switch ($value['audit_phase'])
                {
                    case '0202':
                        $value['audit_phase'] = '02';
                        $where  = array(
                            ['xm_id','=',$value['xid']],
                            ['audit_phase','0201'],
                        );
                        $flight = InspectPlan::where($where)
                            ->select('actual_s','field_day')
                            ->get();
                        if(!$flight->isEmpty()){
                            $value['actual_s'] = $flight->first()->actual_s;
                            $value['repor_day']= $value['field_day']+$flight->first()->field_day;
                        }
                        break;
                    case '0102':
                        $value['audit_phase'] = '01';
                        $where  = array(
                            ['xm_id','=',$value['xid']],
                            ['audit_phase','0101'],
                        );
                        $flight = InspectPlan::where($where)
                            ->select('actual_s','field_day')
                            ->get();
                            if(!$flight->isEmpty()){
                                $value['actual_s'] = $flight->first()->actual_s;
                                $value['repor_day']= $value['field_day']+$flight->first()->field_day;
                            }
                        break;
                    case '03':
                        $value['audit_phase'] = '03';
                        $value['repor_day']= $value['field_day'];
                        break;
                    case '07':
                        $value['audit_phase'] = '03';
                        $value['repor_day']= $value['field_day'];
                        break;
                    case '05':
                        $value['audit_phase'] = '05';
                        $value['repor_day']= $value['field_day'];
                        break;
                    case '04':
                        $value['audit_phase'] = '04';
                        $value['repor_day']= $value['field_day'];
                        break;
                }
                $value['rev_range'] = $value['p_rev_range']?$value['p_rev_range']:$value['rev_range'];
                $where  = array(
                    ['ht_id',$value['hid']],
                    ['fylx',$value['shlx']],
                );
                $money = ExamineMoney::where($where)
                    ->select('htfy','htbz')
                    ->get();
                if($money->isEmpty()){
                    $value['money'] = '';
                    $value['mtype'] = '';
                }else{
                    $value['money'] = $money->first()->htfy;
                    $value['mtype'] = $money->first()->htbz;
                }
                $value['etc_code'] = 'CNCA-R-2017-318';
                $value['etc_name'] = '亿信标准认证集团有限公司';
                $value['country'] = '156';
                $fileTell = array(
                    'phone',
                    'tell',
                );
                $whereTell = array(
                    ['kh_id','=',$value['id']],
                    ['contacts.state','=',1]
                );
                $userTell = MarketContacts::IndexContacts($fileTell,$whereTell);
                if($userTell->isEmpty()){
                    throw new Exception('没有默认的联系人，请联系项目负责人');
                }
                $site = array($value['zcdz'],$value['postal'],$value['bgdz'],$value['scdz']);
                $value['tell'] = $userTell->first()->phone?$userTell->first()->phone:$userTell->first()->tell;
                $value['zcdz'] = implode(';',$site);
                $userId = substr($value['evte_cw'], 1, -1);
                $cltUser = explode(";", $userId);
                $file = array(
                    'id',
                    'name'
                );
                $evteUser = UserBasic::IndexBasic($file, 'id', $cltUser);
                if($evteUser->isEmpty()){
                    throw new Exception('该项目未发现认证决定人员');
                }
                $evteUser = $evteUser->toArray();
                $value['evte_cw'] = implode(";", array_column($evteUser, 'name'));
                if($value['report_type'] == '02'){
                    $value['old_code'] = 'CNCA-R-2017-318';
                }else{
                    $value['old_code'] = '';
                    $value['zs_nb']    = '';
                }
                $value['cer_type'] = $value['cer_type'] == '00'?'99':$value['cer_type'];
                $userResult[] = $value;
            });
            DB::commit();
        }catch (\Exception $e){
            DB::rollback();
            return response()->json(['status'=>101,'msg'=>$e->getMessage()]);
        }
        return response()->json(['status'=>100,'msg'=>'请求成功','data'=>array_values($userResult)]);
    }

    /**证书结果导出(审核结果)**/
    public function ResultUser(Request $request){
        $file = array(
            'qyht_htrza.id as did',
            'xm_id',
            'p_zs_nb',
            'rztx',
            'audit_phase',
            'actual_s',
            'actual_e',
        );
        if(!$request->union_did){
            return response()->json(['status'=>101,'msg'=>'请选择要上报的项目']);
        }
        $cmpy = explode(";",$request->union_did);
        $flighs = InspectPlan::UnionPlan($file,$cmpy);
        if($flighs->isEmpty()){
            return response()->json(['status'=>101,'msg'=>'无数据']);
        }
        $flighs = $flighs->toArray();
        $userResult = array();
        try{
            array_walk($flighs,function ($value,$key)  use (&$userResult){
                $userId[] = $value['did'];
                $file = array(
                    'qyht_htrza.id as did',
                    'audit_phase',
                    'actual_s',
                    'actual_e',
                );
                switch ($value['audit_phase'])
                {
                    case '0202':
                        $where  = array(
                            ['xm_id','=',$value['xm_id']],
                            ['audit_phase','0201'],
                        );
                        $flighs = InspectPlan::PhasePlan($file,$where);
                        if(!$flighs->isEmpty()){
                            $userId[] =  $flighs->first()->did;
                            $flighs->first()->p_zs_nb = $value['p_zs_nb'];
                            $userAp[$flighs->first()->did] =  $flighs->first()->toArray();
                        }
                        break;
                    case '0102':
                        $where  = array(
                            ['xm_id','=',$value['xm_id']],
                            ['audit_phase','0101'],
                        );
                        $flighs = InspectPlan::PhasePlan($file,$where);
                        if(!$flighs->isEmpty()){
                            $userId[] =  $flighs->first()->did;
                            $flighs->first()->p_zs_nb = $value['p_zs_nb'];
                            $userAp[$flighs->first()->did] =  $flighs->first()->toArray();
                        }
                        break;
                }
                $userAp[$value['did']] = $value;
                $file = array(
                    'ap_id',
                    'users.id',
                    'role',
                    'name',
                    'me_code',
                    'telephone',
                    'cft_type',
                    'cft_numb',
                    'type_qlfts',
                    'rgt_numb',
                    'mjexm',
                    'type',
                    'witic',
                );
                $where = array(
                    ['rgt_type','=',$value['rztx']]
                );
                $userTeam = InspectAuditTeam::PlanTeam($file,$where,$userId);
                if($userTeam->isEmpty()){
                    throw new Exception('没有添加审核人员');
                }
                $userTeam = $userTeam->toArray();
                array_walk($userTeam,function ($value,$key,$userAp)  use (&$userResult){
                    $userResult[] = array(
                        'etc_code'=> 'CNCA-R-2017-318',
                        'zs_nb'   => $userAp[$value['ap_id']]['p_zs_nb'],
                        'stage'   => $userAp[$value['ap_id']]['audit_phase'],
                        'start'   => $userAp[$value['ap_id']]['actual_s'],
                        'end'     => $userAp[$value['ap_id']]['actual_e'],
                        'name'    => $value['name'],
                        'type'    => $value['cft_type'],
                        'numb'    => $value['cft_numb'],
                        'role'    => $value['role'],
                        'qlfts'   => $value['type_qlfts'],
                        'regst'   => $value['role'] == '03'?$value['me_code']:$value['rgt_numb'],
                        'mjexm'   => $value['mjexm'],
                        'categ'   => $value['type'],
                        'witic'   => $value['witic']=='00'?'':$value['witic'],
                    );
                },$userAp);
            });
        }catch (\Exception $e){
            return response()->json(['status'=>101,'msg'=>$e->getMessage()]);
        }
        return response()->json(['status'=>100,'msg'=>'请求成功','data'=>array_values($userResult)]);
    }
}
