<?php

namespace App\Http\Controllers\Api\Examine;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Api\Market\MarketCustomer;
use App\Models\Api\Market\MarketContract;
use App\Models\Api\Examine\ExamineSystem;
use App\Models\Api\Inspect\InspectPlan;
use App\Models\Api\Inspect\InspectAuditTeam;
use App\Models\Api\User\UserBasic;
use App\Models\Api\User\UserMajor;
use App\Models\Api\System\ApprovalGroup;
use App\Models\Api\System\SystemUnion;
use Illuminate\Support\Facades\DB;

class ExamineEvaluateController extends Controller
{
    /**评定列表**/
    public function EvaluateIndex(Request $request)
    {
        $where = array(
            ['evte_sbmt', '=', 1],
            ['audit_phase', '<>', '0101'],
            ['audit_phase', '<>', '0201'],
            ['evte_gp', '=', Auth::guard('api')->user()->id]
        );
        $flighs = $this->EvaluateShare($where, $request->limit, $request->field, $request->sort);
        return ($flighs);
    }

    /**评定查询**/
    public function EvaluateSelect(Request $request)
    {
        $where = array(
            ['evte_sbmt', '=', 1],
            ['audit_phase', '<>', '0101'],
            ['audit_phase', '<>', '0201'],
            ['evte_gp', '=', Auth::guard('api')->user()->id]
        );
        if (!empty($request->name)) {
            $name = array(
                'khxx.qymc', 'like', '%' . $request->name . '%',
            );
            array_unshift($where, $name);
        }

        if (!empty($request->xmbh)) {
            $xmbh = array(
                'xmbh', '=', $request->xmbh,
            );
            array_unshift($where, $xmbh);
        }

        if (!empty($request->htbh)) {
            $htbh = array(
                'htbh', '=', $request->htbh,
            );
            array_unshift($where, $htbh);
        }

        if (!empty($request->system)) {
            $system = array(
                'rztx', '=', $request->system,
            );
            array_unshift($where, $system);
        }

        if(!empty($request->type)){
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
            $type = array(//审核阶段
                'audit_phase', '=',$request->type,
            );
            array_push($where,$type);
        }

        if (!empty($request->region)) {
            $region = array(
                'khxx.fzjg', '=', $request->region,
            );
            array_unshift($where, $region);
        }

        $orWhere = array();

        if (!empty($request->code)) {
            $orWhere[] = ['p_major_code', 'like', '%;' . $request->code . '%'];
            $orWhere[] = ['major_code', 'like', '%;' . $request->code . '%'];
        }

        if (!empty($request->range)) {//审核范围
            $orWhere[] = ['p_rev_range', 'like', '%' . $request->range . '%'];
            $orWhere[] = ['rev_range', 'like', '%' . $request->range . '%'];
        }

        if (!empty($request->user)) {
            $userWhere = array(
                ['name', 'like', '%' . $request->user . '%'],
            );
            $userFile = array(
                'users.id as id',
            );
            $userId = UserBasic::SearchUser($userFile, $userWhere);
            $user = array(
                'evte_cw', 'like', '%,' . $userId->first()->id . ',%',
            );
            array_unshift($where, $user);
        }

        $time = array();

        if (!empty($request->astime)) {
            $time['arge_time'] = [$request->astime, $request->aetime];
        }

        if (!empty($request->estime)) {
            $time['evte_time'] = [$request->estime, $request->eetime];
        }

        $flighs = $this->EvaluateShare($where, $request->limit, $request->field, $request->sort, $time, $orWhere);

        return ($flighs);
    }

    /**查询函数**/
    public function EvaluateShare($where, $limit, $sortField, $sort, $time = '', $orWhere = '')
    {
        $file = array(
            'khxx.id as id',
            'qyht.id as hid',
            'qyht_htrz.id as xid',
            'qyht_htrza.id as did',
            'xmbh',
            'htbh',
            'qymc',
            'fz_at',
            'rztx',
            'major_code',
            'p_major_code',
            'rev_range',
            'p_rev_range',
            'audit_phase',
            'cer_type',
            'cbt_type',
            'p_cbt_type',
            'union_cmpy',
            'arge_user',
            'arge_time',
            'evte_gp',
            'evte_cw',
            'evte_time',
            'evte_sbmt',
            'cert_sbmt',
            'dp_jdct'
        );
        switch ($sortField) {
            case 1:
                $sortField = 'arge_time';
                break;
            case 2:
                $sortField = 'evte_time';
                break;
            default:
                $sortField = 'arge_time';
        }
        switch ($sort) {
            case 1:
                $sort = 'desc';
                break;
            case 2:
                $sort = 'asc';
                break;
            default:
                $sort = 'desc';
        }
        $flighs = MarketContract::ReportPlan($file, $where, $limit, $sortField, $sort, $time, $orWhere);
        if ($flighs->isEmpty()) {
            return response()->json(['status' => 101, 'msg' => '无数据']);
        }
        $flighs = $flighs->toArray();
        array_walk($flighs['data'], function ($value, $key) use (&$userPlan) {
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
            $where = array(
                ['ap_id', '=', $value['did']]
            );
            $file = array(
                'name',
                'role',
            );
            $teamUser = InspectAuditTeam::IndexTeam($file, $where);
            $groud = array_filter($teamUser->toArray(), function ($value) {
                if ($value['role'] == '01') {
                    return ($value);
                }
            });
            $value['cbt_type'] = $value['p_cbt_type'] ? $value['p_cbt_type'] : $value['cbt_type'];
            $value['major_code'] = $value['p_major_code'] ? $value['p_major_code'] : $value['major_code'];
            $value['rev_range'] = $value['p_rev_range'] ? $value['p_rev_range'] : $value['rev_range'];
            $value['groud'] = implode(";", array_column($groud, 'name'));
            $value['peple'] = implode(";", array_column($teamUser->toArray(), 'name'));
            $unionWhere = array(
                ['code', '=', $value['cbt_type']],
                ['state', '=', 1]
            );
            $union = SystemUnion::IndexUnion('union', $unionWhere);
            $value['cbt_type'] = $union->first()->union;
            $userId = substr($value['evte_cw'], 1, -1);
            $cltUser = explode(";", $userId);
            $file = array(
                'id',
                'name'
            );
            $evteUser = UserBasic::IndexBasic($file, 'id', $cltUser);
            if($evteUser->isEmpty()){
                $value['evte_gp'] = '';
                $value['evte_cw'] = '';
            }else{
                $evteUser = $evteUser->toArray();
                array_walk($evteUser, function ($value, $key, $id) use (&$evte) {
                    if ($id == $value['id']) {
                        $evte = $value['name'];
                    }
                }, $value['evte_gp']);
                $value['evte_gp'] = $evte;
                $value['evte_cw'] = implode(";", array_column($evteUser, 'name'));
            }
            switch ($value['cert_sbmt']) {
                case 0:
                    $value['state'] = '评定中';
                    break;
                case 1:
                    $value['state'] = '已评定';
                    break;
            }
            $userPlan[] = $value;
        });
        $flighs['data'] = $userPlan;
        return response()->json(['status' => 100, 'msg' => '请求成功', 'data' => $flighs]);
    }

    /**企业英文**/
    public function EvaluateEnglish(Request $request)
    {
        DB::beginTransaction();
        try {
            $flight = InspectPlan::find($request->did);
            if($flight->cert_sbmt == 0 || $flight->dp_jdct == 4){
                $flights = MarketCustomer::find($request->id);
                $flights->qymc_e  = $request->e_name;
                $flights->zcdz_e  = $request->e_register;
                $flights->bgdz_e  = $request->e_office;
                $flights->scdz_e  = $request->e_product;
                $flights->postal_e= $request->e_postal;
                $flights->gx_time = date('Y-m-d');
                if(!$flights->save()){
                    return response()->json(['status'=>100,'msg'=>'企业信息录入失败1']);
                }
                if($flight->dp_jdct == 3){
                    if($flight->result_sbmt == 3){
                        $flights->result_sbmt == 2;
                    }
                    $flight->dp_jdct = 0;
                    if(!$flight->save()){
                        DB::rollback();
                        return response()->json(['status'=>101,'msg'=>'企业信息录入失败2']);
                    };
                }
                DB::commit();
            }else{
                return response()->json(['status' => 101, 'msg' => '该项目已完成技术评定/如需修改请联系管理者赋予权限']);
            }
        }catch (\Exception $e){
            DB::rollback();
            return response()->json(['status'=>101,'msg'=>'企业信息录入失败']);
        }
        return response()->json(['status'=>100,'msg'=>'企业信息录入成功']);
    }

    /**评定提交**/
    public function EvaluateSubmit(Request $request)
    {
        DB::beginTransaction();
        try {
            if($request->cert_sbmt == 0){
                $flighs = ExamineSystem::find($request->xid);
                $flighs->rev_range    = $request->range;
                $flighs->rev_range_e  = $request->e_range;
                if(!$flighs->save()){
                    return response()->json(['status'=>101,'msg'=>'评定提交失败']);
                };
                $flight = InspectPlan::find($request->did);
                $flight->evte_time = $request->evte_time;
                $flight->cert_sbmt = 1;
                if(!$flight->save()){
                    DB::rollback();
                    return response()->json(['status'=>101,'msg'=>'评定提交失败']);
                };
                DB::commit();
            }else{
                return response()->json(['status' => 101, 'msg' => '该项目已完成技术评定/如需修改请联系管理者赋予权限']);
            }
        }catch (\Exception $e){
            DB::rollback();
            return response()->json(['status'=>101,'msg'=>'评定提交失败']);
        }
        return response()->json(['status'=>100,'msg'=>'评定提交成功']);
    }

    /**评定修改**/
    public function EvaluateEdit(Request $request)
    {
        DB::beginTransaction();
        try {
            $flight = InspectPlan::find($request->did);
            if ($flight->dp_jdct != 4) {
                return response()->json(['status' => 101, 'msg' => '没有权限，请申请权限']);
            }
            if(!$request->range || !$request->e_range){
                return response()->json(['status'=>101,'msg'=>'数据不能为空']);
            }
            $flighs = ExamineSystem::find($request->xid);
            $flighs->rev_range    = $request->range;
            $flighs->rev_range_e  = $request->e_range;
            if(!$flighs->save()){
                return response()->json(['status'=>101,'msg'=>'评定修改失败']);
            };
            if($flight->result_sbmt == 3){
                $flight->result_sbmt == 2;
            }
            $flight->evte_time = $request->evte_time;
            $flight->dp_jdct   = 0;
            if(!$flight->save()){
                DB::rollback();
                return response()->json(['status'=>101,'msg'=>'评定修改失败']);
            };
            DB::commit();
        }catch (\Exception $e){
            DB::rollback();
            return response()->json(['status'=>101,'msg'=>'评定修改失败']);
        }
        return response()->json(['status'=>100,'msg'=>'评定修改成功']);
    }

    /**评定详情**/
    public function EvaluateDetail(Request $request)
    {
        $file = array(
            'rev_range',
            'rev_range_e',
            'p_rev_range',
            'p_rev_range_e',
            'evte_time',
            'cert_sbmt'
        );
        $where = array(
            ['qyht_htrza.id', '=', $request->did],
        );
        $flighs = InspectPlan::PhasePlan($file, $where);
        if ($flighs->isEmpty()) {
            return response()->json(['status' => 101, 'msg' => '无数据']);
        }
        $flighs->first()->rev_range   = $flighs->first()->p_rev_range ? $flighs->first()->p_rev_range : $flighs->first()->rev_range;
        $flighs->first()->rev_range_e = $flighs->first()->p_rev_range_e ? $flighs->first()->p_rev_range_e : $flighs->first()->rev_range_e;
        return response()->json(['status' => 100, 'msg' => '请求成功', 'data' => $flighs]);
    }
}
