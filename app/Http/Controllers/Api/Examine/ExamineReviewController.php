<?php

namespace App\Http\Controllers\Api\Examine;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Api\Market\MarketContract;
use App\Models\Api\Market\MarketCustomer;
use App\Models\Api\Market\MarketChange;
use App\Models\Api\Examine\ExamineProject;
use App\Models\Api\Examine\ExamineSystem;
use App\Models\Api\Examine\ExamineRecord;
use App\Models\Api\Examine\ExamineNames;
use App\Models\Api\Inspect\InspectPlan;
use App\Models\Api\System\SystemMajor;
use App\Models\Api\System\SystemEconomy;
use App\Models\Api\System\SystemChange;
use App\Models\Api\System\SystemUnion;
use App\Models\Api\System\SystemRisk;
use Illuminate\Support\Facades\DB;

class ExamineReviewController extends Controller
{
    /**未评审项目**/
    public function ReviewIndex(Request $request){
        $where  = array(
            ['department','=',1],
            ['pt_m','=',0],
        );
        $file = array(
            'khxx.id as id',
            'qyht.id as hid',
            'qyht_htrz.id as xid',
            'qymc',
            'bgdz',
            'fz_at',
            'rztx',
            'm_rzly',
            'stage',
            'rzfw',
            'user_id',
            'qyht.tj_time',
            'pt_m',
        );
        switch ($request->sort)
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
        $flighs = MarketContract::NotreviewContract($file,$where,$request->limit,'qyht.tj_time',$sort);
        if($flighs->isEmpty()){
            return response()->json(['status'=>101,'msg'=>'无数据']);
        }
        return response()->json(['status'=>100,'msg'=>'请求成功','data'=>$flighs]);
    }

    /**未评审项目查询**/
    public function ReviewQuery(Request $request){
        $where  = array(
            ['department','=',1],
            ['pt_m','=',0],
        );
        if(!empty($request->name)){
            $name = array(
                'khxx.qymc', 'like', '%'.$request->name.'%',
            );
            array_unshift($where,$name);
        }
        $file = array(
            'khxx.id as id',
            'qyht.id as hid',
            'qyht_htrz.id as xid',
            'qymc',
            'bgdz',
            'fz_at',
            'rztx',
            'm_rzly',
            'stage',
            'rzfw',
            'user_id',
            'qyht.tj_time',
        );
        switch ($request->sort)
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
        $flighs = MarketContract::NotreviewContract($file,$where,$request->limit,'qyht.tj_time',$sort);
        if($flighs->isEmpty()){
            return response()->json(['status'=>101,'msg'=>'无数据']);
        }
        return response()->json(['status'=>100,'msg'=>'请求成功','data'=>$flighs]);
    }

    /**已评审项目**/
    public function ReviewAdopt(Request $request){
        $where  = array(
            ['department','=',1],
            ['pt_m','=',1],
        );
        $flighs = $this->ReviewShare($where,$request->limit,$request->field,$request->sort);
        return($flighs);
    }

    /**已评审项目查询**/
    public function ReviewSelect(Request $request){
        $where  = array(
            ['department','=',1],
            ['pt_m','=',1],
        );
        if(!empty($request->name)){
            $name = array(
                'khxx.qymc', 'like', '%'.$request->name.'%',
            );
            array_unshift($where,$name);
        }

        if(!empty($request->xmbh)){
            $xmbh = array(
                'xmbh', '=',$request->xmbh,
            );
            array_unshift($where,$xmbh);
        }

        if(!empty($request->htbh)){
            $htbh = array(
                'htbh', '=',$request->htbh,
            );
            array_unshift($where,$htbh);
        }

        if(!empty($request->system)){
            $system = array(
                'rztx', '=',$request->system,
            );
            array_unshift($where,$system);
        }

        if(!empty($request->cer_type)){
            $cerType = array(
                'cer_type', '=',$request->cer_type,
            );
            array_unshift($where,$cerType);
        }

        if(!empty($request->domain)){
            $domain = array(
                'm_rzly', '=',$request->domain,
            );
            array_unshift($where,$domain);
        }

        if(!empty($request->region)){
            $region = array(
                'khxx.fzjg', '=',$request->region,
            );
            array_unshift($where,$region);
        }

        if(!empty($request->major)){
            $major = array(
                'major_code','like', '%;'.$request->major.'%',
            );
            array_unshift($where,$major);
        }

        if(!empty($request->number)){
            $number = array(
                'rz_nub','=',$request->number,
            );
            array_unshift($where,$number);
        }

        if(!empty($request->user)){
            $user = array(
                'review_user','like','%'.$request->user.'%',
            );
            array_unshift($where,$user);
        }

        if(!empty($request->supervise)){
            $supervise = array(
                'supervise_day','=',$request->supervise,
            );
            array_unshift($where,$supervise);
        }

        if(!empty($request->expand)){
            $expand = array(
                'exps_day','=',$request->expand,
            );
            array_unshift($where,$expand);
        }

        if(!empty($request->repeat)){
            $repeat = array(
                'last_trial_day','=',$request->repeat,
            );
            array_unshift($where,$repeat);
        }

        if(!empty($request->range)){
            $range = array(
                'rev_range','like','%'.$request->range.'%',
            );
            array_unshift($where,$range);
        }

        if(!empty($request->clause)){
            $clause = array(
                'cut_clause','like','%'.$request->clause.'%',
            );
            array_unshift($where,$clause);
        }

        if(!empty($request->basis)){
            $basis = array(
                'inde_basis','like','%'.$request->basis.'%',
            );
            array_unshift($where,$basis);
        }

        if(!empty($request->crux)){
            $crux = array(
                'etps_qlfct','like','%'.$request->crux.'%',
            );
            array_unshift($where,$crux);
        }

        $time = array();

        if(!empty($request->rstime)){
            $time['review_time'] = [$request->rstime,$request->retime];
        }

        if(!empty($request->fstime)){
            $time['one_expect'] = [$request->fstime,$request->fetime];
        }

        if(!empty($request->sstime)){
            $time['two_expect'] = [$request->sstime,$request->setime];
        }

        $flighs = $this->ReviewShare($where,$request->limit,$request->field,$request->sort,$time);

        return($flighs);
    }

    /**查询函数**/
    public function ReviewShare($where,$limit,$sortField,$sort,$time=''){
        $file = array(
            'khxx.id as id',
            'qyht.id as hid',
            'qyht_htrz.id as xid',
            'xmbh',
            'htbh',
            'qymc',
            'fz_at',
            'stage',
            'rztx',
            'm_rzly',
            'rzbz',
            'major_code',
            'rev_range',
            'qyrs',
            'yxrs',
            'rz_nub',
            'rot_mech',
            'rece_time',
            'one_expect',
            'two_expect',
            'review_user',
            'review_time',
            'cer_type',
            'zs_nb',
            'zs_m',
            'cbt_type',
            'bd_degree',
            'regt_numb',
            'pt_m',
        );
        switch ($sortField)
        {
            case 1:
                $sortField = 'qyht_htrz.xmbh';
                break;
            case 2:
                $sortField = 'review_time';
                break;
            case 3:
                $sortField = 'shlx';
                break;
            case 4:
                $sortField = 'rz_nub';
                break;
            case 5:
                $sortField = 'cer_type';
                break;
            default:
                $sortField = 'qyht_htrz.xmbh';
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
        $count  = MarketContract::NumberContract($where);
        $flighs = MarketContract::NotreviewContract($file,$where,$limit,$sortField,$sort,$time);
        if($flighs->isEmpty()){
            return response()->json(['status'=>101,'msg'=>'无数据']);
        }
        return response()->json(['status'=>100,'msg'=>'请求成功','data'=>$flighs,'number'=>$count]);
    }

    /**多领域类别**/
    public function ReviewNames(Request $request){
        $flighs = ExamineNames::where('state',1)
            ->select('name')
            ->get();
        if($flighs->isEmpty()){
            return response()->json(['status'=>101,'msg'=>'无数据']);
        }
        return response()->json(['status'=>100,'msg'=>'请求成功','data'=>$flighs]);
    }

    /**保存企业信息**/
    public function ReviewCustomer(Request $request){
        $data = MarketCustomer::FormValidation($request);
        if($data['type'] == false){
            return response()->json(['status'=>101,'msg'=>$data['error']]);
        }
        if(!$request->area_code){
            return response()->json(['status'=>101,'msg'=>'地区代码为空']);
        }
        DB::beginTransaction();
        try {
            $flights = MarketCustomer::find($request->id);
            $flights->qymc  = $request->name;
            $flights->frdb  = $request->legal;
            $flights->xydm  = $request->code;
            $flights->qyxz  = $request->nature;
            $flights->zczb_my = $request->money;
            $flights->zczb_bz = $request->currency;
            $flights->khwd  = $request->bank;
            $flights->yhzh  = $request->account;
            $flights->zcdz  = $request->register;
            $flights->zc_code= $request->register_code;
            $flights->bgdz  = $request->office;
            $flights->bg_code= $request->office_code;
            $flights->scdz  = $request->product;
            $flights->scdz_code= $request->product_code;
            $flights->postal= $request->postal;
            $flights->postal_code = $request->postal_code;
            $flights->gsdh  = $request->tell;
            $flights->gswz  = $request->site;
            $flights->qyrs  = $request->number;
            $flights->khlx  = $request->type;
            $flights->fzjg  = $request->region;
            $flights->khjl  = 1;
            $flights->dqdm  = $request->area_code;
            $flights->bz    = $request->remark;
            $flights->gx_time = date('Y-m-d');
            $flights->save();
            $flight = ExamineSystem::find($request->xid);
            $flight->qy_m = 1;
            $flight->save();
            DB::commit();
        }catch (\Exception $e){
            DB::rollback();
            return response()->json(['status'=>101,'msg'=>'修改失败']);
        }
        return response()->json(['status'=>100,'msg'=>'修改成功']);
    }

    /**评审信息详情**/
    public function ReviewDetail(Request $request){
        $file = array(
            'qyht_htrz.id as id',
            'xiangmu.id as sid',
            'ht_id',
            'shlx',
            'xmbh',
            'htbh',
            'yxrs',
            'rece_time',
            'one_expect',
            'two_expect',
            'rz_nub',
            'sys_file',
            'exs_stem',
            'm_sites',
            'rztx',
            'rule_code',
            'rzbz',
            'rzfw',
            'shlx',
            'rev_range',
            'scope',
            'etps_qlfct',
            'cer_type',
            'natl_code',
            'major_code',
            'code',
            'risk_level',
            'one_mode',
            'regt_numb',
            'trial_day',
            'one_trial_day',
            'supervise_day',
            'last_trial_day',
            'exps_day',
            'cut_clause',
            'inde_basis',
            'cbt_type',
            'bd_degree',
            'rot_mech',
            'fmech_numb',
            'class_type',
            'fcert_numb',
            'flssg_time',
            'rot_reason',
            'tmpy_site',
            'rz_bz',
            'review_time',
            'review_user',
            'qyht_htrz.ps_m',
            'pt_m',
            'review_time',
        );
        $where  = array(
            ['qyht_htrz.id','=',$request->id],
        );
        $flights = ExamineSystem::DetailSystem($file,$where);
        if($flights->isEmpty()){
            return response()->json(['status'=>101,'msg'=>'无数据']);
        }
        if(!$flights->first()->major_code || $flights->first()->major_code == ';'){
            $flights->first()->major_code = null;
        }else{
            switch ($flights->first()->cer_type)
            {
                case '01':
                    $flights = SystemMajor::CnasMajor($flights);
                    break;
                case '00':
                    $flights = SystemMajor::CnasMajor($flights);
                    break;
            }
        }
        $flights->first()->rzbz = explode(';',$flights->first()->rzbz);
        return response()->json(['status'=>100,'msg'=>'请求成功','data'=>$flights]);
    }

    /**评审信息保存**/
    public function ReviewAdd(Request $request)
    {
        DB::beginTransaction();
        try {
            $request->major_code?';'.$request->major_code:null;
            $data = array(
                'rzbz'  => $request->rule,
                'rule_code'  => $request->rule_code,
                'xmbh'  => $request->xmbh,
                'yxrs'  => $request->yxrs,
                'rece_time' => $request->cljs,
                'one_expect'=> $request->yjyq,
                'two_expect'=> $request->ejyq,
                'rz_nub'=> $request->rz_nub,
                'sys_file'  => $request->sys_file,
                'exs_stem'  => $request->exs_stem,
                'm_sites'   => $request->m_sites,
                'rev_range' => $request->psfw,
                'etps_qlfct'=> $request->gjzz,
                'cer_type'  => $request->cer_type,
                'natl_code' => $request->natl_code,
                'major_code'=> $request->major_code,
                'risk_level'=> $request->fxdj,
                'one_mode'  => $request->yjxc,
                'regt_numb' => $request->regt_numb,
                'trial_day' => $request->shrr,
                'one_trial_day' => $request->csrr,
                'supervise_day' => $request->jdrr,
                'last_trial_day'=> $request->zrzrr,
                'exps_day'  => $request->kxrr,
                'cut_clause'=> $request->sjtk,
                'inde_basis'=> $request->sjyj,
                'cbt_type'  => $request->cbt_type,
                'bd_degree' => $request->xjhd,
                'rot_mech'  => $request->zjgxm,
                'fmech_numb'=> $request->yjgpz,
                'class_type'=> $request->xmflc,
                'fcert_numb'=> $request->yrzzs,
                'flssg_time'=> $request->yfzr,
                'rot_reason'=> $request->zjgyy,
                'tmpy_site' => $request->site,
                'rz_bz'     => $request->remark,
                'review_time'=>$request->review,
                'ps_m'=>1,
            );
            $where = array(
                ['id','=',$request->id],
                ['ps_m','=',0],
            );
            $flighs = ExamineSystem::EditSystem($where,$data);
            if($flighs == 0){
                return response()->json(['status'=>101,'msg'=>'保存失败']);
            }
            $flight = MarketContract::find($request->hid);
            if($flight->htbh !== $request->htbh){
                $flight->htbh = $request->htbh;
                $flight->ps_m = 1;
                $flight->save();
            }
            DB::commit();
        }catch (\Exception $e){
            DB::rollback();
            return response()->json(['status'=>101,'msg'=>'保存失败']);
        }
        return response()->json(['status'=>100,'msg'=>'保存成功']);
    }

    /**评审信息修改**/
    public function ReviewEdit(Request $request)
    {
        DB::beginTransaction();
        try {
            $request->major_code?';'.$request->major_code:null;
            $flighs = ExamineSystem::find($request->id);
            $flighs->rzbz  = $request->rule;
            $flighs->rule_code = $request->rule_code;
            $flighs->xmbh  = $request->xmbh;
            $flighs->yxrs  = $request->yxrs;
            $flighs->rece_time = $request->cljs;
            $flighs->one_expect= $request->yjyq;
            $flighs->two_expect= $request->ejyq;
            $flighs->rz_nub = $request->rz_nub;
            $flighs->sys_file  = $request->sys_file;
            $flighs->exs_stem  = $request->exs_stem;
            $flighs->m_sites   = $request->m_sites;
            $flighs->rev_range = $request->psfw;
            $flighs->etps_qlfct= $request->gjzz;
            $flighs->cer_type  = $request->cer_type;
            $flighs->natl_code = $request->natl_code;
            $flighs->major_code= $request->major_code;
            $flighs->risk_level= $request->fxdj;
            $flighs->one_mode  = $request->yjxc;
            $flighs->regt_numb = $request->regt_numb;
            $flighs->trial_day = $request->shrr;
            $flighs->one_trial_day = $request->csrr;
            $flighs->supervise_day = $request->jdrr;
            $flighs->last_trial_day= $request->zrzrr;
            $flighs->exps_day  = $request->kxrr;
            $flighs->cut_clause= $request->sjtk;
            $flighs->inde_basis= $request->sjyj;
            $flighs->cbt_type  = $request->cbt_type;
            $flighs->bd_degree = $request->xjhd;
            $flighs->rot_mech  = $request->zjgxm;
            $flighs->fmech_numb= $request->yjgpz;
            $flighs->class_type= $request->xmflc;
            $flighs->fcert_numb= $request->yrzzs;
            $flighs->flssg_time= $request->yfzr;
            $flighs->rot_reason= $request->zjgyy;
            $flighs->tmpy_site = $request->site;
            $flighs->rz_bz      = $request->remark;
            $flighs->review_time=$request->review;
            if(!$flighs->save()){
                return response()->json(['status'=>101,'msg'=>'修改失败']);
            };
            $flight = MarketContract::find($request->hid);
            if($flight->htbh !== $request->htbh){
                $flight->htbh = $request->htbh;
                $flight->save();
            }
            DB::commit();
        }catch (\Exception $e){
            DB::rollback();
            return response()->json(['status'=>101,'msg'=>'修改失败']);
        }
        /*        $record = [
                    'userName'=>Auth::guard('api')->user()->name,
                    'customer'=>$data,
                ];
                Log::channel('customer_edit')->info(response()->json($record)->setEncodingOptions(JSON_UNESCAPED_UNICODE));*/
        return response()->json(['status'=>100,'msg'=>'修改成功']);
    }

    /**评审提交**/
    public function ReviewSubmit(Request $request)
    {
        DB::beginTransaction();
        try {
            $data = array(
                'scope' => $request->psfw,
                'code'  => $request->major_code,
                'pt_m'  => 1,
                'review_user' => Auth::guard('api')->user()->name,
            );
            $where = array(
                ['id','=',$request->id],
                ['qy_m','=',1],
                ['ps_m','=',1],
            );
            $flighs = ExamineSystem::EditSystem($where,$data);
            if($flighs == 0){
                return response()->json(['status'=>101,'msg'=>'企业信息/评审信息未保存']);
            }
            switch ($request->test_type)
            {
                case '01':
                    $stage = array(
                        ['xm_id' => $request->id, 'audit_phase' => '0101','plan_m'=>1],
                        ['xm_id' => $request->id, 'audit_phase' => '0102','plan_m'=>1]
                    );
                break;
                case '02':
                    $stage = array(
                        ['xm_id' => $request->id, 'audit_phase' => '0202','plan_m'=>1],
                    );
                break;
            }
            InspectPlan::insert($stage);
            DB::commit();
        }catch (\Exception $e){
            DB::rollback();
            return response()->json(['status'=>101,'msg'=>'提交失败'.$e->getMessage()]);
        }
        return response()->json(['status'=>100,'msg'=>'提交成功']);
    }

    /**项目编号验证**/
    public function ProjectCode(Request $request)
    {
        $where = array(
            ['xmbh','like','%'.$request->code.'%'],
        );
        //DB::connection()->enableQueryLog();#开启执行日志
        $project =  ExamineSystem::where($where)
            ->select('ht_id')
            ->get();
        //dump(DB::getQueryLog());
        if($project->isEmpty()){
            return response()->json(['status'=>101,'msg'=>'无数据']);
        }
        return response()->json(['status'=>100,'msg'=>'请求成功','data'=>$project]);
    }

    /**注册号验证**/
    public function RegisterCode(Request $request)
    {
        switch($request->rztx){
            case 'QMS':
                $rztx = array(
                    'QMS',
                    'EMS',
                    'OHSMS',
                    'EC',
                    'YY'
                );
                break;
            case 'EMS':
                $rztx = array(
                    'QMS',
                    'EMS',
                    'OHSMS',
                    'EC',
                    'YY'
                );
                break;
            case 'EC':
                $rztx = array(
                    'QMS',
                    'EMS',
                    'OHSMS',
                    'EC',
                    'YY'
                );
                break;
            case 'OHSMS':
                $rztx = array(
                    'QMS',
                    'EMS',
                    'OHSMS',
                    'EC',
                    'YY'
                );
                break;
            case 'ECPSC':
                $rztx = array(
                    'ECPSC',
                    '养老服务',
                    '物业服务'
                );
                break;
            case '养老服务':
                $rztx = array(
                    'ECPSC',
                    '养老服务',
                    '物业服务'
                );
                break;
            case '物业服务':
                $rztx = array(
                    'ECPSC',
                    '养老服务',
                    '物业服务'
                );
                break;
            case 'IPT':
                $rztx = array(
                    'IPT'
                );
                break;
            case 'IECE':
                $rztx = array(
                    'IECE'
                );
                break;
            case 'EIMS':
                $rztx = array(
                    'EIMS'
                );
                break;
            case 'YY':
                $rztx = array(
                    'QMS',
                    'EMS',
                    'OHSMS',
                    'EC',
                    'YY'
                );
                break;
            case 'HACCP':
                $rztx = array(
                    'HACCP',
                    'FSMS'
                );
                break;
            case 'FSMS':
                $rztx = array(
                    'HACCP',
                    'FSMS'
                );
                break;
            case 'SA8000':
                $rztx = array(
                    'SA8000'
                );
                break;
        }

        $project =  ExamineSystem::join('qyht', 'qyht.id', '=', 'qyht_htrz.ht_id')
            ->join('khxx', 'khxx.id', '=', 'qyht.kh_id')
            ->where('regt_numb', $request->code)
            ->whereIn('rztx',$rztx)
            ->select('khxx.id','rztx')
            ->get();
        if($project->isEmpty()){
            return response()->json(['status'=>100,'msg'=>'注册码正常']);
        }
        $project = $project->toArray();
        $project = array_unique($project);
        $count   = count($project);
        if($count>1){
            return response()->json(['status'=>101,'msg'=>'该注册码已经用于同类别多个体系']);
        }
        $data = array(
            'id'   => $request->qid,
            'rztx' => $request->rztx
        );
        $flighs = array_diff($data,$project[0]);
        if(!empty($flighs)){
            return response()->json(['status'=>101,'msg'=>'注册码已存在']);
        }
        return response()->json(['status'=>100,'msg'=>'注册码正常']);
    }

    /**认证标准**/
    public function ReviewRule(Request $request)
    {
        $typeWhere = array(
            ['xm_id', '=',$request->id],
            ['xm_state', '=',1]
        );
        $typeFile = array(
            'id',
            'xiangmu',
            'xm_code'
        );
        $project = ExamineProject::IndexProject($typeFile, $typeWhere);
        if($project->isEmpty()){
            return response()->json(['status'=>101,'msg'=>'无数据']);
        }
        return response()->json(['status'=>100,'msg'=>'请求成功','data'=>$project]);
    }

    /**专业代码**/
    public function ReviewMajor(Request $request)
    {
        $majorField = array(
            'b_code',
            'b_range',
            'n_old',
        );
        $majorWhere  = array(
            ['e_name','=',$request->project],
            ['b_m','=',1],
        );
        switch ($request->cer_type)
        {
            case '01':
                $flights = SystemMajor::IndexMajor($majorField,$majorWhere);
                break;
            case '00':
                $flights = SystemMajor::IndexMajor($majorField,$majorWhere);
                break;
            default:
                return response()->json(['status'=>101,'msg'=>'无数据']);
        }
        if($flights->isEmpty()){
            return response()->json(['status'=>101,'msg'=>'无数据']);
        }
        return response()->json(['status'=>100,'msg'=>'请求成功','data'=>$flights]);
    }

    /**经济代码**/
    public function ReviewEconomy(Request $request)
    {
        $majorField = array(
            'id',
            'n_code',
            'n_name',
        );
        $majorWhere  = array(
            ['n_m','=',1],
        );
         $flights = SystemEconomy::IndexEconomy($majorField,$majorWhere);
        if($flights->isEmpty()){
            return response()->json(['status'=>101,'msg'=>'无数据']);
        }
        return response()->json(['status'=>100,'msg'=>'请求成功','data'=>$flights]);
    }

    /**风险等级**/
    public function ReviewRisk(Request $request)
    {
        $riskField = array(
            'id',
            'code',
            'risk',
        );
        $riskWhere  = array(
            ['state','=',1],
        );
        $flights = SystemRisk::IndexRisk($riskField,$riskWhere);
        if($flights->isEmpty()){
            return response()->json(['status'=>101,'msg'=>'无数据']);
        }
        return response()->json(['status'=>100,'msg'=>'请求成功','data'=>$flights]);
    }

    /**结合类型**/
    public function ReviewUnion(Request $request)
    {
        $unionField = array(
            'id',
            'code',
            'union',
        );
        $unionWhere  = array(
            ['state','=',1],
        );
        $flights = SystemUnion::IndexUnion($unionField,$unionWhere);
        if($flights->isEmpty()){
            return response()->json(['status'=>101,'msg'=>'无数据']);
        }
        return response()->json(['status'=>100,'msg'=>'请求成功','data'=>$flights]);
    }

    /**变更类型**/
    public function ChangeType(Request $request)
    {
        $changeField = array(
            'id',
            'code',
            'type',
        );
        $changeWhere  = array(
            ['state','=',1],
        );
        $flights['type'] = SystemChange::IndexChange($changeField,$changeWhere);
        if($request->has('id')){
            $change = ExamineSystem::where('id', $request->id)
                ->select('change_code')
                ->first();
            if($change->change_code){
                $flights['change'] = explode(';',$change->change_code);
            }
        }
        if($flights['type']->isEmpty()){
            return response()->json(['status'=>101,'msg'=>'无数据']);
        }
        return response()->json(['status'=>100,'msg'=>'请求成功','data'=>$flights]);
    }

    /**企业信息变更**/
    public function BasicChange(Request $request)
    {
        DB::beginTransaction();
        try {
            $flighs = MarketCustomer::find($request->id);
            if(!empty($request->name)){
                $data['old_name'] = $flighs->qymc;
                $flighs->qymc = $request->name;
            }
            if(!empty($request->code)){
                $data['old_code'] = $flighs->xydm;
                $flighs->xydm = $request->code;
            }

            if(!empty($request->legal)){
                $data['old_legal'] = $flighs->frdb;
                $flighs->frdb = $request->legal;
            }

            if(!empty($request->number)){
                $data['old_number'] = $flighs->qyrs;
                $flighs->qyrs = $request->number;
            }

            if(!empty($request->register)){
                $data['old_register'] = $flighs->zcdz;
                $flighs->zcdz = $request->register;
            }

            if(!empty($request->office)){
                $data['old_office'] = $flighs->bgdz;
                $flighs->bgdz = $request->office;
            }

            if(!empty($request->postal)){
                $data['old_postal'] = $flighs->postal;
                $flighs->postal= $request->postal;
            }

            if(!empty($request->product)){
                $data['old_product'] = $flighs->scdz;
                $flighs->scdz = $request->product;
            }
            $flighs->save();
            $flight = ExamineSystem::find($request->xid);
            $flight->change_code = $request->change;
            $flight->save();
            $data['qy_id'] = $request->id;
            $data['edit_user'] = Auth::guard('api')->user()->name;
            $data['edit_time'] = date('Y-m-d');
            $flight = MarketChange::insert($data);
            if($flight == 0){
                return response()->json(['status'=>101,'msg'=>'变更失败']);
            }
            DB::commit();
        }catch (\Exception $e){
            DB::rollback();
            return response()->json(['status'=>101,'msg'=>'变更失败']);
        }
        return response()->json(['status'=>100,'msg'=>'变更成功']);
    }

    /**企业信息变更列表**/
    public function BasicList(Request $request)
    {
        $where  = array(
            ['qy_id','=',$request->qid],
        );
        switch ($request->sort)
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
        $flighs = MarketChange::IndexChange($where,$request->limit,'edit_time',$sort);
        if($flighs->isEmpty()){
            return response()->json(['status'=>101,'msg'=>'无数据']);
        }
        return response()->json(['status'=>100,'msg'=>'请求成功','data'=>$flighs]);
    }

    /**审核信息变更**/
    public function ReviewChange(Request $request)
    {
        DB::beginTransaction();
        try {
            $flight = ExamineSystem::find($request->xid);
            if(!empty($request->rule)){
                $data['old_rule'] = $flight->rzbz;
                $flight->rzbz = $request->rule;
            }
            if(!empty($request->rule_code)){
                $flight->rule_code = $request->rule_code;
            }

            if(!empty($request->people)){
                $data['old_number'] = $flight->yxrs;
                $flight->yxrs = $request->people;
            }

            if(!empty($request->range)){
                $data['old_range'] = $flight->rev_range;
                $flight->rev_range = $request->range;
            }

            if(!empty($request->sign)){
                $data['old_sign'] = $flight->cer_type;
                $flight->cer_type = $request->sign;
            }

            if(!empty($request->cold)){
                $data['old_cold']  = $flight->major_code;
                $flight->major_code= $request->cold;
            }

            if(!empty($request->risk)){
                $data['old_risk']  = $flight->risk_level;
                $flight->risk_level= $request->risk;
            }

            if(!empty($request->union)){
                $data['old_union']= $flight->cbt_type;
                $flight->cbt_type = $request->union;
            }

            if(!empty($request->degree)){
                $data['old_degree']= $flight->bd_degree;
                $flight->bd_degree = $request->degree;
            }
            $flight->save();
            $data['xm_id'] = $request->xid;
            $data['edit_user'] = Auth::guard('api')->user()->name;
            $data['edit_time'] = date('Y-m-d');
            $flight = ExamineRecord::insert($data);
            if($flight == 0){
                return response()->json(['status'=>101,'msg'=>'变更失败']);
            }
            DB::commit();
        }catch (\Exception $e){
            DB::rollback();
            return response()->json(['status'=>101,'msg'=>'变更失败']);
        }
        return response()->json(['status'=>100,'msg'=>'变更成功']);
    }

    /**审核信息变更列表**/
    public function ReviewList(Request $request)
    {
        $where  = array(
            ['xm_id','=',$request->xid]
        );
        switch ($request->sort)
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
        $flighs = ExamineRecord::IndexRecord($where,$request->limit,'edit_time',$sort);
        if($flighs->isEmpty()){
            return response()->json(['status'=>101,'msg'=>'无数据']);
        }
        return response()->json(['status'=>100,'msg'=>'请求成功','data'=>$flighs]);
    }

    /**审核变更提交**/
    public function ChangeSubmit(Request $request)
    {
        $where = array(
            ['id','=',$request->xid],
        );
        //DB::connection()->enableQueryLog();#开启执行日志
        $project =  ExamineSystem::where($where)
            ->select('change_code')
            ->get();
        //dump(DB::getQueryLog());
        if($project->isEmpty()){
            return response()->json(['status'=>101,'msg'=>'变更类型为空']);
        }
        $flight = InspectPlan::where([
            ['xm_id',$request->xid],
            ['audit_phase','<>','0101'],
            ['audit_phase','<>','0201'],
            ['cert_sbmt',1],
            ['rept_sbmt',2],
        ])
            ->select('id','audit_phase','evte_cw','evte_time')
            ->orderBy('id','desc')
            ->first();
        if(!$flight){
            return response()->json(['status'=>101,'msg'=>'该项目没有一个完整的审核阶段不能进行变更审核']);
        }
        $flighs = new InspectPlan;
        switch ($request->state)
        {
            case 0:
                $flighs->xm_id  = $request->xid;
                $flighs->audit_phase = '05';
                $flighs->plan_m = 0;
                $flighs->dp_time= date('Y-m-d');;
                $flighs->dp_sbmt= 1;
                break;
            case 1:
                $flighs->xm_id = $request->xid;
                $flighs->audit_phase = '05';
                $flighs->plan_m= 1;
                break;
        }
        if($flighs->save()){
            return response()->json(['status'=>100,'msg'=>'提交成功']);
        }else{
            return response()->json(['status'=>101,'msg'=>'提交失败']);
        }
    }
}
