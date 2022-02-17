<?php

namespace App\Http\Controllers\Api\Inspect;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Api\Market\MarketContract;
use App\Models\Api\Market\MarketCustomer;
use App\Models\Api\Inspect\InspectPlan;
use App\Models\Api\Inspect\InspectReport;
use App\Models\Api\Inspect\InspectReason;
use App\Models\Api\Examine\ExamineSystem;
use App\Models\Api\System\SystemSuspend;
use App\Models\Api\System\SystemRevoke;
use App\Models\Api\System\SystemTemplate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Contracts\Encryption\DecryptException;

class InspectPrintController extends Controller
{
    /**证书打印列表**/
    public function PrintIndex(Request $request){
        $where  = array(
            ['adopt_sbmt','=',1],
            ['change_type','=',0],
            ['audit_phase','<>','0101'],
            ['audit_phase','<>','0201'],
        );
        $flighs = $this->PrintShare($where,$request->limit,$request->field,$request->sort);
        return($flighs);
    }

    /**证书打印查询**/
    public function PrintQuery(Request $request){
        $where  = array(
            ['adopt_sbmt','=',1],
            ['change_type','=',0],
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

        if($request->user){
            $where[] = ['review_user', 'like','%'.$request->user.'%'];
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
            $where[] = ['audit_phase', '=',$request->type];
        }

        if($request->state){
            $where[] = ['p_zs_m', '=',$request->state];
        }

        if($request->region){
            $where[] = ['khxx.fzjg', '=',$request->region];
        }

        $time = array();

        if($request->adst && $request->adet){//批准时间
            $time['adopt_time'] = [$request->adst,$request->adet];
        }

        if($request->cfst && $request->cfet){//上报时间
            $time['p_zs_ftime'] = [$request->cfst,$request->cfet];
        }

        $flighs = $this->PrintShare($where,$request->limit,$request->field,$request->sort,$time);
        return($flighs);
    }

    /**查询函数**/
    public function PrintShare($where,$limit,$sortField,$sort,$time=''){
        $file = array(
            'khxx.id as id',
            'qyht.id as hid',
            'qyht_htrz.id as xid',
            'qyht_htrza.id as did',
            'xmbh',
            'htbh',
            'qymc',
            'rztx',
            'review_user',
            'audit_phase',
            'adopt_user',
            'adopt_time',
            'cer_type',
            'p_zs_m',
            'p_zs_nb',
            'first_time',
            'p_zs_ftime',
            'p_zs_etime',
            'fz_at',
            'union_cmpy',
            'print_sbmt',
        );
        switch ($sortField)
        {
            case 1:
                $sortField = 'adopt_time';
                break;
            case 2:
                $sortField = 'first_time';
                break;
            case 3:
                $sortField = 'p_zs_ftime';
                break;
            default:
                $sortField = 'adopt_time';
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
            switch ($value['cer_type'])
            {
                case '01':
                    $value['cer_type'] = 'CNAS';
                    break;
                case '02':
                    $value['cer_type'] = 'UKAS';
                    break;
                case '03':
                    $value['cer_type'] = 'JAS-ANS';
                    break;
                case '00':
                    $value['cer_type'] = 'ETC';
                    break;
            }
            switch ($value['p_zs_m'])
            {
                case '01':
                    $value['p_zs_m'] = '有效';
                    break;
                case '02':
                    $value['p_zs_m'] = '暂停';
                    break;
                case '03':
                    $value['p_zs_m'] = '撤销';
                    break;
                case '05':
                    $value['p_zs_m'] = '过期失效';
                    break;
                default:
                    $value['p_zs_m'] = '未出证';
            }
        });
        return response()->json(['status'=>100,'msg'=>'请求成功','data'=>$flighs]);
    }

    /**证书打印导出**/
    public function PrintExport(){
        return response()->json(['status'=>100,'msg'=>'请求成功']);
    }

    /**证书打印项目**/
    public function PrintProject(Request $request)
    {
        $file = array(
            'qyht_htrz.id as xid',
            'qyht_htrza.id as did',
            'xmbh',
            'rztx',
            'audit_phase',
            'rzbz',
            'shlx',
            'jd_nub',
            'p_rzbz',
            'yxrs',
            'p_yxrs',
            'rev_range',
            'p_rev_range',
            'regt_numb',
            'p_regt_numb',
            'cer_type',
            'p_cer_type',
            'dp_jdct',
            'print_sbmt',
        );
        if (!$request->union_cmpy) {
            $where = array(
                ['ht_id', '=', $request->hid],
                ['audit_phase', '=', $request->code],
                ['adopt_sbmt','=',1],
            );
            $flighs = InspectPlan::PhasePlan($file, $where);
        } else {
            $where = array(
                ['adopt_sbmt','=',1],
            );
            $cmpy = explode(";", $request->union_cmpy);
            $flighs = InspectPlan::UnionPlan($file, $cmpy,$where);
        }
        if ($flighs->isEmpty()) {
            return response()->json(['status' => 101, 'msg' => '无数据']);
        }
        $flighs = $flighs->toArray();
        array_walk($flighs, function (&$value, $key) {
            $value['rzbz'] = $value['p_rzbz'] ? $value['p_rzbz'] : $value['rzbz'];
            $value['p_cer_type'] = $value['p_cer_type'] ? $value['p_cer_type'] : $value['cer_type'];
            $value['yxrs'] = $value['p_yxrs'] ? $value['p_yxrs'] : $value['yxrs'];
            $value['rev_range']= $value['p_rev_range'] ? $value['p_rev_range'] : $value['rev_range'];
            $value['regt_numb']= $value['p_regt_numb'] ? $value['p_regt_numb'] : $value['regt_numb'];
            switch ($value['audit_phase']) {
                case '0102':
                    $value['audit_type'] = '初审';
                    break;
                case '0202':
                    $value['audit_type'] = '再认证';
                    break;
                case '03':
                    $value['audit_type'] = '监督1';
                    break;
                case '07':
                    $value['audit_type'] = '监督2';
                    break;
                case '04':
                    $value['audit_type'] = '特殊审核';
                    break;
                case '05':
                    $value['audit_type'] = '变更审核';
                    break;
            }
            switch ($value['p_cer_type']) {
                case '00':
                    $value['cer_type'] = 'Etc';
                    break;
                case '01':
                    $value['cer_type'] = 'CNAS';
                    break;
                case '02':
                    $value['cer_type'] = 'UKAS';
                    break;
                case '03':
                    $value['cer_type'] = 'JAS-ANS';
                    break;
            }
        });
        return response()->json(['status' => 100, 'msg' => '请求成功', 'data' => $flighs]);
    }

    /**证书信息详情**/
    public function PrintDetail(Request $request)
    {
        $file = array(
            'qyht_htrza.id as did',
            'zs_nb',
            'first_time',
            'zs_etime',
            'p_zs_nb',
            'p_zs_ftime',
            'p_zs_etime',
            'report_type',
            'print_time',
            'replace_state',
            'replace_time',
            'replace_reason',
            'sub_state',
            'p_sub_nb',
        );
        $where = array(
            ['qyht_htrza.id', '=', $request->did],
            ['adopt_sbmt','=',1],
        );
        $flighs = InspectPlan::PhasePlan($file, $where);
        if ($flighs->isEmpty()) {
            return response()->json(['status' => 101, 'msg' => '无数据']);
        }
        $flighs->first()->p_zs_nb = $flighs->first()->p_zs_nb?$flighs->first()->p_zs_nb:$flighs->first()->zs_nb;
        $flighs->first()->p_zs_etime = $flighs->first()->p_zs_etime?$flighs->first()->p_zs_etime:$flighs->first()->zs_etime;
        $flighs->first()->p_sub_nb= json_decode($flighs->first()->p_sub_nb,TRUE);
        return response()->json(['status' => 100, 'msg' => '请求成功', 'data' => $flighs]);
    }

    /**体系子证书**/
    public function PrintSub(Request $request)
    {
        if($request->sub_state != 1){
            return response()->json(['status' => 101, 'msg' => '没有申请子证书']);
        }
        $flighs = MarketCustomer::select('qymc')
        ->where('id',$request->id)
        ->get();
        if ($flighs->isEmpty()) {
            return response()->json(['status' => 101, 'msg' => '无数据']);
        }
        $flight = explode("/", $flighs->first()->qymc);
        array_walk($flight, function ($value, $key)  use (&$flights){
            $flights[] = array(
                'qymc' => $value,
                'zsbh' => ''
            );
        });
        return response()->json(['status' => 100, 'msg' => '请求成功', 'data' => array_values($flights)]);
    }

    /**上报类型**/
    public function PrintType(Request $request)
    {
        $flighs = InspectReport::select('code','report')
            ->where('state',1)
            ->get();
        if ($flighs->isEmpty()) {
            return response()->json(['status' => 101, 'msg' => '无数据']);
        }
        return response()->json(['status' => 100, 'msg' => '请求成功', 'data' => $flighs]);
    }

    /**换证原因**/
    public function PrintReason(Request $request)
    {
        if($request->code != '02'){
            return response()->json(['status' => 101, 'msg' => '上报类型不是换发证书']);
        }
        $flighs = InspectReason::select('code','reason')
            ->where('state',1)
            ->get();
        if ($flighs->isEmpty()) {
            return response()->json(['status' => 101, 'msg' => '无数据']);
        }
        return response()->json(['status' => 100, 'msg' => '请求成功', 'data' => $flighs]);
    }

    /**证书信息保存**/
    public function PrintAdd(Request $request)
    {
        DB::beginTransaction();
        try {
            $flight = InspectPlan::find($request->did);
            if($flight->print_sbmt != 0){
                return response()->json(['status' => 101, 'msg' => '该项目已完成证书打印/如需修改请联系管理者赋予权限']);
            }
            if($request->replace_state == 1 && $request->report_type == '02'){
                if($request->revoke_state == 1){
                    $flights = $this->AddState($request->xid,'03');
                    if($flights->original['status'] != 100){
                        return ($flights);
                    }
                }
                $flight->replace_state = $request->replace_state;
                $flight->replace_time  = $request->replace_time;
                $flight->replace_reason= $request->replace_reason;
            }
            $flight->p_cer_type = $request->cer_type;
            $flight->p_zs_m     = '01';
            $flight->p_zs_nb    = $request->p_zs_nb;
            $flight->p_zs_ftime = $request->p_zs_ftime;
            $flight->p_zs_etime = $request->p_zs_etime;
            $flight->report_type= $request->report_type;
            $flight->print_user = Auth::guard('api')->user()->name;
            $flight->print_time = $request->print_time;
            if($request->sub_state == 1){
                $flight->sub_state  = $request->sub_state;
                $flight->p_sub_nb   = $request->p_sub_nb;
            }
            $flight->print_sbmt = 1;
            if(!$flight->save()){
                return response()->json(['status'=>101,'msg'=>'证书信息保存失败']);
            };
            $flighst = ExamineSystem::find($request->xid);
            $flighst->first_time = $request->first_time;
            if(!$flighst->save()){
                return response()->json(['status'=>101,'msg'=>'证书信息保存失败']);
            };
            DB::commit();
       }catch (\Exception $e){
            DB::rollback();
            return response()->json(['status'=>101,'msg'=>'证书信息保存失败']);
        }
        return response()->json(['status'=>100,'msg'=>'证书信息保存成功']);
    }

    /**证书信息修改**/
    public function PrintEdit(Request $request)
    {
        DB::beginTransaction();
        try {
            $flight = InspectPlan::find($request->did);
            if($flight->dp_jdct != 5){
               return response()->json(['status' => 101, 'msg' => '该项目没有修改权限,如需修改请联系管理者赋予权限']);
            }
            switch ($request->shlx)
            {
                case '01':
                    $jd_nub = 0;
                    break;
                case '03':
                    $jd_nub = 1;
                    break;
                case '07':
                    $jd_nub = 2;
                    break;
                case '02':
                    $jd_nub = 0;
                    break;
            }
            if($request->replace_state == 1 && $request->report_type == '02'){
                if($request->revoke_state == 1){
                    $flights = $this->AddState($request->xid,'03');
                    return ($flights);
                }
                $flight->replace_state = $request->replace_state;
                $flight->replace_time  = $request->replace_time;
                $flight->replace_reason= $request->replace_reason;
            }else{
                $count = InspectPlan::where([
                    ['xm_id',$request->xid],
                    ['audit_phase','05'],
                    ['change_type',1],
                    ['jd_nub',$jd_nub],
                    ['p_zs_m','03'],
                ])->count();
                if($count != 0){
                    return response()->json(['status'=>101,'msg'=>'该项目已存在撤销阶段，请先处理此撤销阶段']);
                }
                $flight->replace_state = 0;
                $flight->replace_time  = '';
                $flight->replace_reason= '';
            }
            $flight->p_cer_type = $request->cer_type;
            $flight->p_zs_m     = '01';
            $flight->p_zs_nb    = $request->p_zs_nb;
            $flight->p_zs_ftime = $request->p_zs_ftime;
            $flight->p_zs_etime = $request->p_zs_etime;
            $flight->report_type= $request->report_type;
            $flight->print_user = Auth::guard('api')->user()->name;
            $flight->print_time = $request->print_time;
            if($request->sub_state == 1){
                $flight->sub_state  = $request->sub_state;
                $flight->p_sub_nb   = $request->p_sub_nb;
            }else{
                $flight->sub_state  = 0;
                $flight->p_sub_nb   = '';
            }
            $flight->print_sbmt = 1;
            if($flight->result_sbmt == 3){
                $flight->result_sbmt = 2;
            }
            $flight->dp_jdct = 0;
            if(!$flight->save()){
                return response()->json(['status'=>101,'msg'=>'证书信息修改失败']);
            };
            $flighst = ExamineSystem::find($request->xid);
            $flighst->first_time = $request->first_time;
            if(!$flighst->save()){
                return response()->json(['status'=>101,'msg'=>'证书信息修改失败']);
            };
            DB::commit();
        }catch (\Exception $e){
            DB::rollback();
            return response()->json(['status'=>101,'msg'=>'证书信息修改失败']);
        }
        return response()->json(['status'=>100,'msg'=>'证书信息修改成功']);
    }

    /**证书打印样本**/
    public function PrintSample(Request $request){
        $file = array(
            'attribute',
            'image',
        );
        $where = array(
            ['system','=', $request->rztx],
            ['type','=',$request->type],
        );
        $flighs = SystemTemplate::TemplateIndex($file, $where);
        if ($flighs->isEmpty()) {
            return response()->json(['status' => 101, 'msg' => '没有对应的证书模板，请先添加相对应的证书模板。']);
        }
        $data['attribute'] = json_decode($flighs->first()->attribute,TRUE);
        $mime = Storage::disk('template')->getMimeType($flighs->first()->image);
        $file = Storage::disk('template')->get($flighs->first()->image);
        $base64_data = base64_encode($file);
        $base64_file = 'data:'.$mime.';base64,'.$base64_data;
        //$url = Storage::disk('template')->url($flighs->first()->image);
        //$contents = Storage::disk('template')->get($flighs->first()->image);
        //dump($contents);die;
        $data['image'] = $base64_file;
        //dump($data);die;
        switch ($request->type)
        {
            case 1:
                $flight = $this->ChinaSample($request->did,$data);
                break;
            case 2:
                $flight = $this->EnglishSample($request->did,$data);
                break;
        }
        return ($flight);
    }

    /**证书打印样本(中文)**/
    protected function ChinaSample($id,$data){
        $file = array(
            'qyht_htrz.id as xid',
            'qyht_htrza.id as did',
            'qymc',
            'xydm',
            'zcdz',
            'postal',
            'bgdz',
            'scdz',
            'rzbz',
            'rev_range',
            'p_rzbz',
            'p_rev_range',
            'p_zs_nb',
            'first_time',
            'p_zs_ftime',
            'p_zs_etime',
        );
        $where = array(
            ['qyht_htrza.id','=',$id]
        );
        $flighs = MarketContract::SamplePlan($file, $where);

        if($flighs->isEmpty()){
            return response()->json(['status' => 101, 'msg' => '该项目无数据!']);
        }
        $flighs = $flighs->first()->toArray();
        array_walk($data['attribute'], function (&$value, $key, $flighs){
            if($value['cx'] == 1){
                switch ($value['type'])
                {
                    case 'qymc':
                        $value['value'] = $flighs['qymc'];
                        break;
                    case 'xydm':
                        $value['value'] = $flighs['xydm'];
                        break;
                    case 'zcdz':
                        $value['value'] = $flighs['zcdz'];
                        break;
                    case 'bgdz':
                        $value['value'] = $flighs['bgdz'];
                        break;
                    case 'scdz':
                        $value['value'] = $flighs['scdz'];
                        break;
                    case 'postal':
                        $value['value'] = $flighs['postal'];
                        break;
                    case 'rzbz':
                        $value['value'] = $flighs['p_rzbz']?$flighs['p_rzbz']:$flighs['rzbz'];
                        break;
                    case 'rzfw':
                        $value['value'] = $flighs['p_rev_range']?$flighs['p_rev_range']:$flighs['rev_range'];
                        break;
                    case 'p_zs_nb':
                        $value['value'] = $flighs['p_zs_nb'];
                        break;
                    case 'first_time':
                        $time = strtotime(date($flighs['first_time']));
                        $value['value'] = date('Y年m月d日',$time);
                        break;
                    case 'p_zs_ftime':
                        $time = strtotime(date($flighs['p_zs_ftime']));
                        $value['value'] = date('Y年m月d日',$time);
                        break;
                    case 'p_zs_etime':
                        $time = strtotime(date($flighs['p_zs_etime']));
                        $value['value'] = date('Y年m月d日',$time);
                        break;
                    case 'ewm':
                        //$value['value'] = '=?GBK?B?'.base64_encode($flighs['xid']).'?=';
                        $value['value'] = 2;
                        break;
                }
            }else{
                if($value['type'] == 'stata'){
                    $value['value'] = 5;
                }else{
                    $value['value'] = '';
                }
            }
        },$flighs);
        return response()->json(['status' => 100, 'msg' => '请求成功', 'data' => $data]);
    }

    /**证书打印样本(英文)**/
    protected function EnglishSample($id,$data){
        $file = array(
            'qyht_htrz.id as xid',
            'qymc_e',
            'xydm',
            'zcdz_e',
            'postal_e',
            'bgdz_e',
            'scdz_e',
            'rzbz',
            'rev_range_e',
            'p_rzbz',
            'p_rev_range_e',
            'p_zs_nb',
            'first_time',
            'p_zs_ftime',
            'p_zs_etime',
        );
        $where = array(
            ['qyht_htrza.id','=',$id]
        );
        $flighs = MarketContract::SamplePlan($file, $where);
        if($flighs->isEmpty()){
            return response()->json(['status' => 101, 'msg' => '该项目无数据!']);
        }
        $flighs = $flighs->first()->toArray();
        array_walk($data['attribute'], function (&$value, $key, $flighs){
            if($value['cx'] == 1){
                switch ($value['type'])
                {
                    case 'qymc':
                        $value['value'] = $flighs['qymc_e'];
                        break;
                    case 'xydm':
                        $value['value'] = $flighs['xydm'];
                        break;
                    case 'zcdz':
                        $value['value'] = $flighs['zcdz_e'];
                        break;
                    case 'bgdz':
                        $value['value'] = $flighs['bgdz_e'];
                        break;
                    case 'scdz':
                        $value['value'] = $flighs['scdz_e'];
                        break;
                    case 'postal':
                        $value['value'] = $flighs['postal_e'];
                        break;
                    case 'rzbz':
                        $value['value'] = $flighs['p_rzbz']?$flighs['p_rzbz']:$flighs['rzbz'];
                        break;
                    case 'rzfw':
                        $value['value'] = $flighs['p_rev_range_e']?$flighs['p_rev_range_e']:$flighs['rev_range_e'];
                        break;
                    case 'p_zs_nb':
                        $value['value'] = $flighs['p_zs_nb'];
                        break;
                    case 'first_time':
                        $value['value'] = $flighs['first_time'];
                        break;
                    case 'p_zs_ftime':
                        $value['value'] = $flighs['p_zs_ftime'];
                        break;
                    case 'p_zs_etime':
                        $value['value'] = $flighs['p_zs_etime'];
                        break;
                    case 'ewm':
                        //$value['value'] = '=?GBK?B?'.base64_encode($flighs['xid']).'?=';
                        $value['value'] = 2;
                        break;
                }
            }else{
                if($value['type'] == 'stata'){
                    $value['value'] = 5;
                }else{
                    $value['value'] = '';
                }
            }
        },$flighs);
        return response()->json(['status' => 100, 'msg' => '请求成功', 'data' => $data]);
    }

    /**生成nfc证书**/
    public function sampleCopy(Request $request){
        DB::beginTransaction();
        try {
            $flight = InspectPlan::find($request->did);
            if($request->style == 1){
                if($flight->m_url != null){
                    $m_url = json_decode($flight->m_url,TRUE);
                    if($request->type == 1){
                        if(!empty($m_url['c']))
                            Storage::disk('certificate')->delete($m_url['c']);
                    }else{
                        if(!empty($m_url['e']))
                            Storage::disk('certificate')->delete($m_url['e']);
                    }
                }
            }else{
                if($flight->s_url != null){
                    $s_url = json_decode($flight->s_url,TRUE);
                    if($request->type == 1){
                        if(!empty($s_url[$request->name]['c']))
                            Storage::disk('certificate')->delete($s_url[$request->name]['c']);
                    }else{
                        if(!empty($s_url[$request->name]['e']))
                            Storage::disk('certificate')->delete($s_url[$request->name]['e']);
                    }
                }
            }
            if(!$request->has('file')){
                return response()->json(['status'=>101,'msg'=>'数字证书为空']);
            }
            preg_match('/^(data:\s*image\/(\w+);base64,)/',$request->file,$res);
            if (!isset($res[2])) {
                return response()->json(['status'=>101,'msg'=>'上传失败']);
            }
            $base64_img = base64_decode(str_replace($res[1],'', $request->file));
            $new_file   = $request->name.'/'.$request->system.'/'.date("YmdHis").'.'.$res[2];
            $path = Storage::disk('certificate')->put($new_file, $base64_img);
            //$path = $request->file('file')->storeAs($request->name.'/'.$request->system,date("YmdHis").'.'.$request->file('file')->getClientOriginalExtension(),'certificate');
            //dump($path);
            if($path != true){
                return(['status'=>101,'msg'=>'图片保存失败']);
            }
            if($request->style == 1){
                if($request->type == 1){
                    $m_url['c'] = $new_file;
                }else{
                    $m_url['e'] = $new_file;
                }
                $flight->m_url = json_encode($m_url);
                $data = array(
                    'did'=>$request->did,
                    'type'=>$request->type,
                    'style'=>$request->style,
                );
            }else{
                if($request->type == 1){
                    $s_url[$request->name]['c'] = $new_file;
                }else{
                    $s_url[$request->name]['e'] = $new_file;
                }
                $flight->s_url = json_encode($s_url);
                $data = array(
                    'did'=>$request->did,
                    'name'=>$request->name,
                    'type'=>$request->type,
                    'style'=>$request->style,
                );

            }
            if(!$flight->save()){
                return response()->json(['status'=>101,'msg'=>'数字证书保存失败']);
            };
            DB::commit();
        }catch (\Exception $e){
            DB::rollback();
            return response()->json(['status'=>101,'msg'=>'数字证书保存失败']);
        }
        $data = json_encode($data);
        $data = base64_encode($data);
        return response()->json(['status'=>100,'msg'=>'数字证书保存成功','data'=>$data]);
    }

    /**获取上次完整阶段**/
    protected function OldState($xid){
        $flights = InspectPlan::where([
            ['xm_id',$xid],
            ['audit_phase','<>','0101'],
            ['audit_phase','<>','0201'],
            ['audit_phase','<>','04'],
            ['audit_phase','<>','05'],
            ['cert_sbmt',1],
            ['result_sbmt',1],
        ])
            ->select('id','audit_phase','p_rzbz','p_yxrs','p_rev_range','p_major_code','p_risk_level','p_cbt_type','evte_cw','evte_time','sub_state','p_cer_type','p_zs_nb','p_zs_ftime','p_zs_etime')
            ->orderBy('id','desc')
            ->get();
        return $flights;
    }

    /**生成暂停撤销阶段**/
    protected function AddState($xid,$zs_m){
        $flights = $this->OldState($xid);
        if($flights->isEmpty()){
            return response()->json(['status' => 101, 'msg' => '该项目没有完整的上报阶段']);
        }
        switch ($flights->first()->audit_phase)
        {
            case '0102':
                $jd_nub= 0;
                break;
            case '03':
                $jd_nub= 1;
                break;
            case '07':
                $jd_nub= 2;
                break;
            case '0202':
                $jd_nub= 0;
                break;
        }
        $count = InspectPlan::where([
            ['xm_id',$xid],
            ['audit_phase','05'],
            ['change_type',1],
            ['jd_nub',$jd_nub],
            ['p_zs_m',$zs_m],
        ])->count();
        if($count != 0){
            return response()->json(['status'=>101,'msg'=>'该项目已有撤销阶段，不能再次生成撤销阶段']);
        }
        $flighs = new InspectPlan;
        $flighs->xm_id  = $xid;
        $flighs->audit_phase= '05';
        $flighs->jd_nub = $jd_nub;
        $flighs->p_rzbz = $flights->first()->p_rzbz;
        $flighs->p_yxrs = $flights->first()->p_yxrs;
        $flighs->p_rev_range = $flights->first()->p_rev_range;
        $flighs->p_major_code= $flights->first()->p_major_code;
        $flighs->p_risk_level= $flights->first()->p_risk_level;
        $flighs->p_cbt_type= $flights->first()->p_cbt_type;
        $flighs->evte_cw   = $flights->first()->evte_cw;
        $flighs->evte_time = $flights->first()->evte_time;
        $flighs->change_type= 1;
        $flighs->sub_state  = $flights->first()->sub_state;
        $flighs->p_cer_type = $flights->first()->p_cer_type;
        $flighs->p_zs_m     = $zs_m;
        $flighs->p_zs_nb    = $flights->first()->p_zs_nb;
        $flighs->p_zs_ftime = $flights->first()->p_zs_ftime;
        $flighs->p_zs_etime = $flights->first()->p_zs_etime;
        if(!$flighs->save()){
            return response()->json(['status'=>101,'msg'=>'暂停撤销撤销阶段生成失败']);
        }
        return response()->json(['status' => 100, 'msg' => '暂停撤销撤销阶段生成成功，请在数据上报暂停撤销板块查看详情']);
    }

    /**证书暂停撤销列表**/
    public function PrintSuspend(Request $request){
        $time = date('Y-m-d');
        $where= array(
            ['dl_time','<=',$time],
            ['zs_m','=','01'],
        );
        $flighs = $this->StateShare($where,$request->limit,$request->field,$request->sort,'');
        return($flighs);
    }

    /**证书暂停撤销查询**/
    public function SuspendQuery(Request $request){
        $time = $request->dl_time?$request->dl_time:date('Y-m-d');
        switch ($request->scene)
        {
            case '02':
                $where= array(
                    ['dl_time','<=',$time],
                    ['zs_m','=','01'],
                );
                break;
            case '03':
                $time = date("Y-m-d", strtotime("$time -6 month"));
                $where= array(
                    ['dl_time','<=',$time],
                    ['zs_m','=','02'],
                );
                break;
            case '05':
                $where= array(
                    ['zs_etime','<',$time],
                    ['zs_m','=','01'],
                );
                break;
            default :
                $where= array(
                    ['dl_time','<=',$time],
                    ['zs_m','<>','00'],
                );
        };

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
            $where[] = ['shlx', '=',$request->type];
        }

        if($request->state){
            $where[] = ['zs_m', '=',$request->state];
        }
        if($request->region){
            $where[] = ['fzjg', '=',$request->region];
        }
        $time = array();
        if($request->stime && $request->etime){//首次发证时间
            $time['first_time'] = [$request->stime,$request->etime];
        }
        if($request->zstime && $request->zetime){//颁证时间
            $time['zs_ftime'] = [$request->zstime,$request->zetime];
        }
        $flighs = $this->StateShare($where,$request->limit,$request->field,$request->sort,$time);
        return($flighs);
    }

    /**证书暂停导出**/
    public function SuspendExport(){
        return response()->json(['status'=>100,'msg'=>'请求成功']);
    }

    /**证书状态函数**/
    protected function StateShare($where,$limit,$sortField,$sort,$time){
        $file = array(
            'khxx.id as id',
            'qyht.id as hid',
            'qyht_htrz.id as xid',
            'xmbh',
            'htbh',
            'qymc',
            'rztx',
            'stage',
            'cer_type',
            'zs_m',
            'zs_nb',
            'dl_time',
            'first_time',
            'zs_ftime',
            'zs_etime',
            'fz_at',
        );
        switch ($sortField)
        {
            case 1:
                $sortField = 'dl_time';
                break;
            case 2:
                $sortField = 'first_time';
                break;
            case 3:
                $sortField = 'zs_ftime';
                break;
            default:
                $sortField = 'dl_time';
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
        $flighs = MarketContract::NotreviewContract($file,$where,$limit,$sortField,$sort,$time,'');
        //dump(DB::getQueryLog());
        if($flighs->isEmpty()){
            return response()->json(['status'=>101,'msg'=>'无数据']);
        }
        $flighs = $flighs->toArray();
        array_walk($flighs['data'], function (&$value,$key){
            switch ($value['cer_type'])
            {
                case '01':
                    $value['cer_type'] = 'CNAS';
                    break;
                case '02':
                    $value['cer_type'] = 'UKAS';
                    break;
                case '03':
                    $value['cer_type'] = 'JAS-ANS';
                    break;
                case '00':
                    $value['cer_type'] = 'ETC';
                    break;
            }
            switch ($value['zs_m'])
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
                default:
                    $value['state'] = '未出证';
            }
        });
        return response()->json(['status'=>100,'msg'=>'请求成功','data'=>$flighs]);
    }

    /**证书暂停撤销详情**/
    public function SuspendDetail(Request $request){
        $file = array(
            'qyht_htrz.id as xid',
            'xmbh',
            'rztx',
            'shlx',
            'stage',
            'cer_type',
            'zs_m',
            'zs_nb',
            'dl_time',
            'first_time',
            'zs_ftime',
            'zs_etime',
        );
        $time = date('Y-m-d');
        $where= array(
            ['ht_id','=',$request->hid],
            ['dl_time','<=',$time],
            ['zs_m','<>','03'],
            ['zs_m','<>','00'],
            ['zs_m','<>','05'],
        );
        $flighs = ExamineSystem::IndexSystem($file,$where);
        if($flighs->isEmpty()){
            return response()->json(['status'=>101,'msg'=>'无数据']);
        }
        $flighs = $flighs->toArray();
        array_walk($flighs, function (&$value,$key){
            switch ($value['cer_type'])
            {
                case '01':
                    $value['cer_type'] = 'CNAS';
                    break;
                case '02':
                    $value['cer_type'] = 'UKAS';
                    break;
                case '03':
                    $value['cer_type'] = 'JAS-ANS';
                    break;
                case '00':
                    $value['cer_type'] = 'ETC';
                    break;
            }
            switch ($value['zs_m'])
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
                default:
                    $value['state'] = '未出证';
            }
        });
        return response()->json(['status'=>100,'msg'=>'请求成功','data'=>$flighs]);
    }

    /**证书暂停撤销原因**/
    public function SuspendReason(Request $request){
        switch ($request->zs_m)
        {
            case '02':
                $flighs = SystemSuspend::select('code','type')
                    ->where('state',1)
                    ->get();
                break;
            case '03':
                $flighs = SystemRevoke::select('code','type')
                    ->where('state',1)
                    ->get();
                break;
        }
        if ($flighs->isEmpty()) {
            return response()->json(['status' => 101, 'msg' => '无数据']);
        }
        return response()->json(['status' => 100, 'msg' => '请求成功','data'=>$flighs]);
    }

    /**证书暂停撤销保存**/
    public function SuspendAdd(Request $request){
        $flights = $this->AddState($request->xid,$request->p_zs_m);
        return ($flights);
    }
}
