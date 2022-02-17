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

class ExaminePersonnelController extends Controller
{
    /**评定安排列表**/
    public function PersonnelIndex(Request $request)
    {
        $where = array(
            ['dp_sbmt', '=', 1],
            ['audit_phase', '<>', '0101'],
            ['audit_phase', '<>', '0201']
        );
        $flighs = $this->PersonnelShare($where, $request->limit, $request->field, $request->sort);
        return ($flighs);
    }

    /**评定安排查询**/
    public function PersonnelSelect(Request $request)
    {
        $where = array(
            ['dp_sbmt', '=', 1],
            ['audit_phase', '<>', '0101'],
            ['audit_phase', '<>', '0201']
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

        if($request->system){
            $where[] = ['rztx', '=',$request->system];
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

        if($request->region){
            $where[] = ['khxx.fzjg', '=',$request->region];
        }

        if($request->obtain_time){
            $where[] = ['obtain_time', '<>',null];
            $where[] = ['obtain_time', '<>',''];
        }

        $orWhere = array();

        if ($request->code) {
/*            $orWhere[] = ['p_major_code', 'like',$request->code . '%'];
            $orWhere[] = ['major_code', 'like',$request->code . '%'];*/
            $orWhere[] = array(
                ['p_major_code','like',$request->code.'%']
            );
            $orWhere[] = array(
                ['major_code','like',$request->code.'%']
            );
        }

        if ($request->range) {//审核范围
/*            $orWhere[] = ['p_rev_range', 'like', '%' . $request->range . '%'];
            $orWhere[] = ['rev_range', 'like', '%' . $request->range . '%'];*/
            $orWhere[] = array(
                ['p_rev_range','like','%'.$request->range.'%']
            );
            $orWhere[] = array(
                ['rev_range','like','%'.$request->range.'%']
            );
        }

        if ($request->user) {
            $userWhere = array(
                ['name', 'like', '%' . $request->user . '%'],
            );
            $userFile = array(
                'users.id as id',
            );
            $userId = UserBasic::SearchUser($userFile, $userWhere);
            $where[] = ['evte_cw', 'like', '%,'.$userId->first()->id.',%'];
        }

        $time = array();
        if ($request->dstime){
            $time['dp_time'] = [$request->dstime, $request->detime];
        }

        if ($request->astime){
            $time['arge_time'] = [$request->astime, $request->aetime];
        }

        if ($request->estime){
            $time['evte_time'] = [$request->estime, $request->eetime];
        }

        if ($request->ostime){
            $time['obtain_time'] = [$request->ostime, $request->oetime];
        }

        $flighs = $this->PersonnelShare($where, $request->limit, $request->field, $request->sort, $time, $orWhere);

        return ($flighs);
    }

    /**查询函数**/
    public function PersonnelShare($where, $limit, $sortField, $sort, $time = '', $orWhere = '')
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
            'regt_numb',
            'major_code',
            'p_major_code',
            'rev_range',
            'p_rev_range',
            'audit_phase',
            'cer_type',
            'cbt_type',
            'p_cbt_type',
            'union_cmpy',
            'dp_user',
            'dp_time',
            'arge_user',
            'arge_time',
            'evte_gp',
            'evte_cw',
            'evte_time',
            'evte_sbmt',
            'cert_sbmt',
            'dp_jdct',
            'zs_m',
            'obtain_time',
        );
        switch ($sortField) {
            case 1:
                $sortField = 'dp_time';
                break;
            case 2:
                $sortField = 'arge_time';
                break;
            case 3:
                $sortField = 'evte_time';
                break;
            case 7:
                $sortField = 'obtain_time';
                break;
            default:
                $sortField = 'dp_time';
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
            if($value['obtain_time'] != '' || $value['obtain_time'] != null){
                $start= strtotime(date('Y-m-d'));
                $end  = strtotime($value['obtain_time']);
                $strto= $end-$start;
                $days = intval($strto/86400);
                $value['days'] = $days;
            }
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
            if ($value['evte_sbmt'] == 1 && $value['cert_sbmt'] == 0) {
                $value['state'] = '评定中';
            }
            if ($value['evte_sbmt'] == 0) {
                $value['state'] = '未提交';
            }
            if ($value['cert_sbmt'] == 1) {
                $value['state'] = '已评定';
            }
            $userPlan[] = $value;
        });
        $flighs['data'] = $userPlan;
        return response()->json(['status' => 100, 'msg' => '请求成功', 'data' => $flighs]);
    }

    /**评定导出**/
    public function PersonnelExport(){
        return response()->json(['status'=>100,'msg'=>'请求成功']);
    }

    /**评定安排项目**/
    public function PersonnelProject(Request $request)
    {
        $file = array(
            'qyht_htrz.id as xid',
            'qyht_htrza.id as did',
            'xmbh',
            'rztx',
            'audit_phase',
            'major_code',
            'p_major_code',
            'cer_type',
            'p_cer_type',
            'risk_level',
            'p_risk_level',
            'base',
            'evte_gp',
            'evte_cw',
            'evte_major',
            'evte_sbmt',
            'dp_jdct',
            'evte_state',
            'cert_sbmt'
        );
        if (!$request->union_cmpy) {
            $where = array(
                ['ht_id', '=', $request->hid],
                ['audit_phase', '=', $request->code],
                ['dp_sbmt', '=', 1],
            );
            //DB::connection()->enableQueryLog();#开启执行日志
            $flighs = InspectPlan::PhasePlan($file,$where);
            //dump(DB::getQueryLog());
        } else {
            $where = array(
                ['dp_sbmt', '=', 1],
            );
            $cmpy = explode(";", $request->union_cmpy);
            $flighs = InspectPlan::UnionPlan($file,$cmpy,$where);
        }
        if ($flighs->isEmpty()) {
            return response()->json(['status' => 101, 'msg' => '无数据']);
        }
        $flighs = $flighs->toArray();
        array_walk($flighs, function (&$value, $key) {
            $value['major_code'] = $value['p_major_code'] ? $value['p_major_code'] : $value['major_code'];
            $rztx = array(
                'QMS',
                'EMS',
                'EC',
                'OHSMS',
                'YY',
            );
            if($value['major_code']){
                if(in_array($value['rztx'],$rztx)){
                    $major = explode(";", $value['major_code']);
                    array_walk($major, function (&$value, $key){
                        $value =  substr($value,0,2);
                    });
                    $major = array_unique($major);
                    $value['major_code'] = implode(';',$major);
                }
            }
            $value['p_cer_type'] = $value['p_cer_type'] ? $value['p_cer_type'] : $value['cer_type'];
            $value['risk_level'] = $value['p_risk_level'] ? $value['p_risk_level'] : $value['risk_level'];
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
                    $value['p_cer_type'] = 'Etc';
                    break;
                case '01':
                    $value['p_cer_type'] = 'CNAS';
                    break;
                case '02':
                    $value['p_cer_type'] = 'UKAS';
                    break;
                case '03':
                    $value['p_cer_type'] = 'JAS-ANS';
                    break;
            }
            switch ($value['risk_level']) {
                case '01':
                    $value['risk_level'] = '高风险';
                    break;
                case '02':
                    $value['risk_level'] = '中风险';
                    break;
                case '03':
                    $value['risk_level'] = '低风险';
                    break;
            }
            if($value['base'] != null){
                $value['base'] = json_decode($value['base'],true);
            }
            $where = array(
                ['ap_id', '=', $value['did']]
            );
            $file = array(
                'us_id',
            );
            $teamUser = InspectAuditTeam::IndexTeam($file, $where);
            $value['user_id'] = implode(";", array_column($teamUser->toArray(), 'us_id'));
        });
        return response()->json(['status' => 100, 'msg' => '请求成功', 'data' => $flighs]);
    }

    /**评定项目人员**/
    public function PersonnelUser(Request $request)
    {
        $file = array(
            'users.id as id',
            'name',
            'type',
            'us_qlfts',
            'group_abty',
            'ealte_abty',
            'telephone',
            'fz_at',
        );
        $where = array(
        ['rgt_type', '=', $request->rztx],
    );
        $request->evte_cw = substr($request->evte_cw, 1, -1);
        $whereIn = explode(";", $request->evte_cw);
        $flighs = UserBasic::ReviewGroup($file, $where, $whereIn);
        if ($flighs->isEmpty()) {
            return response()->json(['status' => 101, 'msg' => '无数据']);
        }
        $flighs = $flighs->toArray();
        if(!$request->evte_major){
            $eMajor = array();
        }else{
            $eMajor = json_decode($request->evte_major,TRUE);
        }
        $flight = array_merge($flighs,$eMajor);
        array_walk($flight, function ($value, $key,$id)  use (&$flights){
            //dump($value['type']);
            $value = UserMajor::UserType( $value);
            if ($id == $value['id']) {
                $value['role_code'] = '01';
                $value['role'] = '组长';
            } else {
                $value['role_code'] = '02';
                $value['role'] = '组员';
            }
            if (!isset($flights[$value['id']])) {
                $flights[$value['id']] = $value;
                if (!isset($flights[$value['id']]['major'])) {
                    $flights[$value['id']]['major'] = '';
                }
            }elseif(isset($value['major'])){
                $flights[$value['id']]['major'] = $value['major'];
            }
        },$request->evte_gp);
        return response()->json(['status' => 100, 'msg' => '请求成功', 'data' => array_values($flights)]);
    }

    /**评定组长匹配**/
    public function PersonnelLeader(Request $request)
    {
        if (!$request->user) {
            return response()->json(['status' => 101, 'msg' => '没有审核组人员']);
        }
        $user = explode(";", $request->user);
        switch ($request->rztx) {
            case 'QMS':
                if (!$request->major) {
                    return response()->json(['status' => 101, 'msg' => '专业代码为空']);
                }
                $where = array(
                    ['rgt_type', '=', $request->rztx],
                    ['stop','=',1],
                    ['ealte_abty', '=', 1],
                    ['qlfts_state', '=', '01'],
                    ['nmbe_et', '>', date("Y-m-d")],
                );
                $flight = $this->RoutineLeader($where, $user, $request->major, $request->type);
                return ($flight);
                break;
            case 'EMS':
                if (!$request->major) {
                    return response()->json(['status' => 101, 'msg' => '专业代码为空']);
                }
                $where = array(
                    ['rgt_type', '=', $request->rztx],
                    ['stop','=',1],
                    ['ealte_abty', '=', 1],
                    ['qlfts_state', '=', '01'],
                    ['nmbe_et', '>', date("Y-m-d")],
                );
                $flight = $this->RoutineLeader($where, $user, $request->major, $request->type);
                return ($flight);
                break;
            case 'EC':
                if (!$request->major) {
                    return response()->json(['status' => 101, 'msg' => '专业代码为空']);
                }
                $where = array(
                    ['rgt_type', '=', $request->rztx],
                    ['stop','=',1],
                    ['ealte_abty', '=', 1],
                    ['qlfts_state', '=', '01'],
                    ['nmbe_et', '>', date("Y-m-d")],
                );
                $flight = $this->RoutineLeader($where, $user, $request->major, $request->type);
                return ($flight);
                break;
            case 'OHSMS':
                if (!$request->major) {
                    return response()->json(['status' => 101, 'msg' => '专业代码为空']);
                }
                $where = array(
                    ['rgt_type', '=', $request->rztx],
                    ['stop','=',1],
                    ['ealte_abty', '=', 1],
                    ['qlfts_state', '=', '01'],
                    ['nmbe_et', '>', date("Y-m-d")],
                );
                $flight = $this->RoutineLeader($where, $user, $request->major, $request->type);
                return ($flight);
                break;
            case 'SCSMS':
                if (!$request->major) {
                    return response()->json(['status' => 101, 'msg' => '专业代码为空']);
                }
                $where = array(
                    ['rgt_type', '=', $request->rztx],
                    ['stop','=',1],
                    ['ealte_abty', '=', 1],
                    ['qlfts_state', '=', '01'],
                    ['nmbe_et', '>', date("Y-m-d")],
                );
                $flight = $this->RoutineLeader($where, $user, $request->major, $request->type);
                return ($flight);
                break;
            case 'ECPSC':
                if (!$request->major) {
                    return response()->json(['status' => 101, 'msg' => '专业代码为空']);
                }
                $where = array(
                    ['rgt_type', '=', $request->rztx],
                    ['stop','=',1],
                    ['ealte_abty', '=', 1],
                    ['qlfts_state', '=', '01'],
                    ['nmbe_et', '>', date("Y-m-d")],
                );
                $flight = $this->RoutineLeader($where, $user, $request->major, $request->type);
                return ($flight);
                break;
            case '养老服务':
                if (!$request->major) {
                    return response()->json(['status' => 101, 'msg' => '专业代码为空']);
                }
                $where = array(
                    ['rgt_type', '=', $request->rztx],
                    ['stop','=',1],
                    ['ealte_abty', '=', 1],
                    ['qlfts_state', '=', '01'],
                    ['nmbe_et', '>', date("Y-m-d")],
                );
                $flight = $this->RoutineLeader($where, $user, $request->major, $request->type);
                return ($flight);
                break;
            case '物业服务':
                if (!$request->major) {
                    return response()->json(['status' => 101, 'msg' => '专业代码为空']);
                }
                $where = array(
                    ['rgt_type', '=', $request->rztx],
                    ['stop','=',1],
                    ['ealte_abty', '=', 1],
                    ['qlfts_state', '=', '01'],
                    ['nmbe_et', '>', date("Y-m-d")],
                );
                $flight = $this->RoutineLeader($where, $user, $request->major, $request->type);
                return ($flight);
                break;
            case 'IPT':
                $where = array(
                    ['rgt_type', '=', $request->rztx],
                    ['stop','=',1],
                    ['ealte_abty', '=', 1],
                    ['qlfts_state', '=', '01'],
                    ['nmbe_et', '>', date("Y-m-d")],
                );
                $flight = $this->ServiceLeader($where, $user);
                return ($flight);
                break;
            case 'EQC':
                $where = array(
                    ['rgt_type', '=', $request->rztx],
                    ['stop','=',1],
                    ['ealte_abty', '=', 1],
                    ['qlfts_state', '=', '01'],
                    ['nmbe_et', '>', date("Y-m-d")],
                );
                $flight = $this->ServiceLeader($where, $user);
                return ($flight);
                break;
            case 'CMS':
                $where = array(
                    ['rgt_type', '=', $request->rztx],
                    ['stop','=',1],
                    ['ealte_abty', '=', 1],
                    ['qlfts_state', '=', '01'],
                    ['nmbe_et', '>', date("Y-m-d")],
                );
                $flight = $this->ServiceLeader($where, $user);
                return ($flight);
                break;
            case 'AMS':
                $where = array(
                    ['rgt_type', '=', $request->rztx],
                    ['stop','=',1],
                    ['ealte_abty', '=', 1],
                    ['qlfts_state', '=', '01'],
                    ['nmbe_et', '>', date("Y-m-d")],
                );
                $flight = $this->ServiceLeader($where, $user);
                return ($flight);
                break;
            case 'IECE':
                $where = array(
                    ['rgt_type', '=', $request->rztx],
                    ['stop','=',1],
                    ['ealte_abty', '=', 1],
                    ['qlfts_state', '=', '01'],
                    ['nmbe_et', '>', date("Y-m-d")],
                );
                $flight = $this->ServiceLeader($where, $user);
                return ($flight);
                break;
            case 'EIMS':
                $where = array(
                    ['rgt_type', '=', $request->rztx],
                    ['stop','=',1],
                    ['ealte_abty', '=', 1],
                    ['qlfts_state', '=', '01'],
                    ['nmbe_et', '>', date("Y-m-d")],
                );
                $flight = $this->ServiceLeader($where, $user);
                return ($flight);
                break;
            case 'SA8000':
                $where = array(
                    ['rgt_type', '=', $request->rztx],
                    ['stop','=',1],
                    ['ealte_abty', '=', 1],
                    ['qlfts_state', '=', '01'],
                    ['nmbe_et', '>', date("Y-m-d")],
                );
                $flight = $this->ServiceLeader($where, $user);
                return ($flight);
                break;
            case 'HSE':
                if (!$request->major) {
                    return response()->json(['status' => 101, 'msg' => '专业代码为空']);
                }
                $where = array(
                    ['rgt_type', '=', $request->rztx],
                    ['stop','=',1],
                    ['ealte_abty', '=', 1],
                    ['qlfts_state', '=', '01'],
                    ['nmbe_et', '>', date("Y-m-d")],
                );
                $flight = $this->RoutineLeader($where, $user, $request->major, $request->type);
                return ($flight);
                break;
            case 'YY':
                if (!$request->major) {
                    return response()->json(['status' => 101, 'msg' => '专业代码为空']);
                }
                $where = array(
                    ['rgt_type', '=', $request->rztx],
                    ['stop','=',1],
                    ['ealte_abty', '=', 1],
                    ['qlfts_state', '=', '01'],
                    ['nmbe_et', '>', date("Y-m-d")],
                );
                $flight = $this->RoutineLeader($where, $user, $request->major, $request->type);
                return ($flight);
                break;
            case 'HACCP':
                if (!$request->major) {
                    return response()->json(['status' => 101, 'msg' => '专业代码为空']);
                }
                $where = array(
                    ['rgt_type', '=', $request->rztx],
                    ['stop','=',1],
                    ['ealte_abty', '=', 1],
                    ['qlfts_state', '=', '01'],
                    ['nmbe_et', '>', date("Y-m-d")],
                );
                $flight = $this->RoutineLeader($where, $user, $request->major, $request->type);
                return ($flight);
                break;
            case 'FSMS':
                if (!$request->major) {
                    return response()->json(['status' => 101, 'msg' => '专业代码为空']);
                }
                $where = array(
                    ['rgt_type', '=', $request->rztx],
                    ['stop','=',1],
                    ['ealte_abty', '=', 1],
                    ['qlfts_state', '=', '01'],
                    ['nmbe_et', '>', date("Y-m-d")],
                );
                $flight = $this->RoutineLeader($where, $user, $request->major, $request->type);
                return ($flight);
                break;
            case 'OGA':
                if (!$request->major) {
                    return response()->json(['status' => 101, 'msg' => '专业代码为空']);
                }
                $where = array(
                    ['rgt_type', '=', $request->rztx],
                    ['stop','=',1],
                    ['ealte_abty', '=', 1],
                    ['qlfts_state', '=', '01'],
                    ['nmbe_et', '>', date("Y-m-d")],
                );
                $flight = $this->RoutineLeader($where, $user, $request->major, $request->type);
                return ($flight);
                break;
            case 'BCMS':
                if (!$request->major) {
                    return response()->json(['status' => 101, 'msg' => '专业代码为空']);
                }
                $where = array(
                    ['rgt_type', '=', $request->rztx],
                    ['stop','=',1],
                    ['ealte_abty', '=', 1],
                    ['qlfts_state', '=', '01'],
                    ['nmbe_et', '>', date("Y-m-d")],
                );
                $flight = $this->RoutineLeader($where, $user, $request->major, $request->type);
                return ($flight);
                break;
            default:
                return response()->json(['status' => 101, 'msg' => '该体系未匹配路径，请联系开发人员。']);
                break;
        }
    }

    /**评定组长匹配(常规体系QES/EMS/OHSMS/EC)**/
    public function RoutineLeader($where, $user, $major, $type)
    {

        $flight = UserBasic::ServiceGroup($where, $user);
        if ($flight->isEmpty()) {
            return response()->json(['status' => 101, 'msg' => '无数据']);
        }
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

        $major = explode(";", $major);
        $judge = array(
            'major' => $major,
            'type' => $type
        );
        $flight = $flight->toArray();
        array_walk($flight, function (&$value, $key, $judge){
            $major_code = array();
            foreach ($judge['major'] as $valuee){
                $where = array(
                    ['m_id', '=', $value['mid']],
                    ['major_m', '=', $judge['type']],
                    ['major_statee', '=', '01'],
                    ['major_code', 'like', $valuee.'%']
                );
                $count = UserMajor::where( $where)
                    ->count();
                if ($count != 0) {
                    $major_code[] = $valuee;
                }
            }
            $value['major_code'] = implode(";", $major_code);
            $value = UserMajor::UserType( $value);
        }, $judge);
        return response()->json(['status' => 100, 'msg' => '请求成功', 'data' => $flight]);
    }

    /**评定组长匹配(服务认证ECPSC/养老服务)**/
    public function ServiceLeader($where, $user)
    {   //DB::connection()->enableQueryLog();#开启执行日志
        $flight = UserBasic::ServiceGroup($where, $user);
        //dump(DB::getQueryLog());
        if ($flight->isEmpty()) {
            return response()->json(['status' => 101, 'msg' => '无数据']);
        }
        $flight = $flight->toArray();
        array_walk($flight, function (&$value, $key){
            $value = UserMajor::UserType( $value);
        });
        return response()->json(['status' => 100, 'msg' => '请求成功', 'data' => $flight]);
    }

    /**评定组员匹配**/
    public function PersonnelMember(Request $request)
    {
        if (!$request->user) {
            return response()->json(['status' => 101, 'msg' => '没有审核组人员']);
        }
        $user = explode(";", $request->user);
        switch ($request->rztx) {
            case 'QMS':
                if (!$request->major) {
                    return response()->json(['status' => 101, 'msg' => '专业代码为空']);
                }
                $flight = $this->RoutineMember($request->rztx, $user, $request->major, $request->type);
                return ($flight);
                break;
            case 'EMS':
                if (!$request->major) {
                    return response()->json(['status' => 101, 'msg' => '专业代码为空']);
                }
                $flight = $this->RoutineMember($request->rztx, $user, $request->major, $request->type);
                return ($flight);
                break;
            case 'EC':
                if (!$request->major) {
                    return response()->json(['status' => 101, 'msg' => '专业代码为空']);
                }
                $flight = $this->RoutineMember($request->rztx, $user, $request->major, $request->type);
                return ($flight);
                break;
            case 'OHSMS':
                if (!$request->major) {
                    return response()->json(['status' => 101, 'msg' => '专业代码为空']);
                }
                $flight = $this->RoutineMember($request->rztx, $user, $request->major, $request->type);
                return ($flight);
                break;
            case 'SCSMS':
                if (!$request->major) {
                    return response()->json(['status' => 101, 'msg' => '专业代码为空']);
                }
                $flight = $this->RoutineMember($request->rztx, $user, $request->major, $request->type);
                return ($flight);
                break;
            case 'ECPSC':
                if (!$request->major) {
                    return response()->json(['status' => 101, 'msg' => '专业代码为空']);
                }
                $flight = $this->RoutineMember($request->rztx, $user, $request->major, $request->type);
                return ($flight);
                break;
            case '养老服务':
                if (!$request->major) {
                    return response()->json(['status' => 101, 'msg' => '专业代码为空']);
                }
                $flight = $this->RoutineMember($request->rztx, $user, $request->major, $request->type);
                return ($flight);
                break;
            case '物业服务':
                if (!$request->major) {
                    return response()->json(['status' => 101, 'msg' => '专业代码为空']);
                }
                $flight = $this->RoutineMember($request->rztx, $user, $request->major, $request->type);
                return ($flight);
                break;
            case 'IPT':
                $flight = $this->ServiceMember($request->rztx, $user);
                return ($flight);
                break;
            case 'EQC':
                $flight = $this->ServiceMember($request->rztx, $user);
                return ($flight);
                break;
            case 'CMS':
                $flight = $this->ServiceMember($request->rztx, $user);
                return ($flight);
                break;
            case 'AMS':
                $flight = $this->ServiceMember($request->rztx, $user);
                return ($flight);
                break;
            case 'IECE':
                $flight = $this->ServiceMember($request->rztx, $user);
                return ($flight);
                break;
            case 'EIMS':
                $flight = $this->ServiceMember($request->rztx, $user);
                return ($flight);
                break;
            case 'SA8000':
                $flight = $this->ServiceMember($request->rztx, $user);
                return ($flight);
                break;
            case 'HSE':
                if (!$request->major) {
                    return response()->json(['status' => 101, 'msg' => '专业代码为空']);
                }
                $flight = $this->RoutineMember($request->rztx, $user, $request->major, $request->type);
                return ($flight);
                break;
            case 'YY':
                if (!$request->major) {
                    return response()->json(['status' => 101, 'msg' => '专业代码为空']);
                }
                $flight = $this->RoutineMember($request->rztx, $user, $request->major, $request->type);
                return ($flight);
                break;
            case 'HACCP':
                if (!$request->major) {
                    return response()->json(['status' => 101, 'msg' => '专业代码为空']);
                }
                $flight = $this->RoutineMember($request->rztx, $user, $request->major, $request->type);
                return ($flight);
                break;
            case 'FSMS':
                if (!$request->major) {
                    return response()->json(['status' => 101, 'msg' => '专业代码为空']);
                }
                $flight = $this->RoutineMember($request->rztx, $user, $request->major, $request->type);
                return ($flight);
                break;
            case 'OGA':
                if (!$request->major) {
                    return response()->json(['status' => 101, 'msg' => '专业代码为空']);
                }
                $flight = $this->RoutineMember($request->rztx, $user, $request->major, $request->type);
                return ($flight);
                break;
            case 'BCMS':
                if (!$request->major) {
                    return response()->json(['status' => 101, 'msg' => '专业代码为空']);
                }
                $flight = $this->RoutineMember($request->rztx, $user, $request->major, $request->type);
                return ($flight);
                break;
            default:
                return response()->json(['status' => 101, 'msg' => '该体系未匹配路径，请联系开发人员。']);
                break;
        }
    }

    /**评定组员匹配(常规体系QES/EMS/OHSMS/EC)**/
    public function RoutineMember($system, $user, $major, $type)
    {
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
        $major = explode(";", $major);
        $where = array(
            ['rgt_type', '=', $system],
            ['major_m', '=', $type],
            ['stop','=',1],
            ['ealte_abty', '=', 0],
            ['qlfts_state', '=', '01'],
            ['major_statee', '=', '01'],
            ['nmbe_et', '>', date("Y-m-d")]
        );
        array_walk($major, function ($value, $key) use (&$orWhere){
            if($value){
                $orWhere[] = array(
                    ['major_code','like',$value.'%']
                );
            }
        });
        //DB::connection()->enableQueryLog();#开启执行日志
        $flight = UserBasic::RoutineGroup($where, '', $user,$orWhere);
        //dump(DB::getQueryLog());
        if ($flight->isEmpty()) {
            return response()->json(['status' => 101, 'msg' => '无数据']);
        }
        $flight = $flight->toArray();
        $flighs = array();
        array_walk($flight, function ($value,$key ) use (&$flighs) {
            $rztx = array(
                'QMS',
                'EMS',
                'EC',
                'OHSMS',
                'YY',
            );
            if(in_array($value['rgt_type'],$rztx)) {
                $value['major_code'] = substr($value['major_code'], 0, 2);
            }
            $value = UserMajor::UserType( $value);
            if(!isset($flighs[$value['uid']])){
                $flighs[$value['uid']]=$value;
                $flighs[$value['uid']]['major_code'] = array($value['major_code']);
            }else{
                if(!in_array($value['major_code'],$flighs[$value['uid']]['major_code'])){
                    $flighs[$value['uid']]['major_code'][] = $value['major_code'];
                }
            }
        });
        return response()->json(['status' => 100, 'msg' => '请求成功', 'data' => array_values($flighs)]);
    }

    /**评定组员匹配(服务认证ECPSC/养老服务)**/
    public function ServiceMember($system, $user)
    {
        $where = array(
            ['rgt_type', '=', $system],
            ['stop','=',1],
            ['ealte_abty', '=', 0],
            ['qlfts_state', '=', '01'],
            ['nmbe_et', '>', date("Y-m-d")]
        );
        $flight = UserBasic::ServiceGroup($where, $user);
        if ($flight->isEmpty()) {
            return response()->json(['status' => 101, 'msg' => '无数据']);
        }
        $flight = $flight->toArray();
        array_walk($flight, function (&$value, $key){
            $value = UserMajor::UserType( $value);
        });
        return response()->json(['status' => 100, 'msg' => '请求成功', 'data' => $flight]);
    }

    /**评定人员查询**/
    public function UserQuery(Request $request)
    {
        if (!$request->user) {
            return response()->json(['status' => 101, 'msg' => '没有审核组人员']);
        }
        $user = explode(";", $request->user);
        switch ($request->rztx) {
            case 'QMS':
                if (!$request->major) {
                    return response()->json(['status' => 101, 'msg' => '专业代码为空']);
                }
                $where = array(
                    ['na_code', '=', $request->code],
                    ['rgt_type', '=', $request->rztx],
                    ['stop','=',1],
                    ['qlfts_state', '=', '01'],
                    ['nmbe_et', '>', date("Y-m-d")]
                );
                $flight = $this->RoutineLeader($where, $user, $request->major, $request->type);
                return ($flight);
                break;
            case 'EMS':
                if (!$request->major) {
                    return response()->json(['status' => 101, 'msg' => '专业代码为空']);
                }
                $where = array(
                    ['na_code', '=', $request->code],
                    ['rgt_type', '=', $request->rztx],
                    ['stop','=',1],
                    ['qlfts_state', '=', '01'],
                    ['nmbe_et', '>', date("Y-m-d")]
                );
                $flight = $this->RoutineLeader($where, $user, $request->major, $request->type);
                return ($flight);
                break;
            case 'EC':
                if (!$request->major) {
                    return response()->json(['status' => 101, 'msg' => '专业代码为空']);
                }
                $where = array(
                    ['na_code', '=', $request->code],
                    ['rgt_type', '=', $request->rztx],
                    ['stop','=',1],
                    ['qlfts_state', '=', '01'],
                    ['nmbe_et', '>', date("Y-m-d")]
                );
                $flight = $this->RoutineLeader($where, $user, $request->major, $request->type);
                return ($flight);
                break;
            case 'OHSMS':
                if (!$request->major) {
                    return response()->json(['status' => 101, 'msg' => '专业代码为空']);
                }
                $where = array(
                    ['na_code', '=', $request->code],
                    ['rgt_type', '=', $request->rztx],
                    ['stop','=',1],
                    ['qlfts_state', '=', '01'],
                    ['nmbe_et', '>', date("Y-m-d")]
                );
                $flight = $this->RoutineLeader($where, $user, $request->major, $request->type);
                return ($flight);
                break;
            case 'SCSMS':
                if (!$request->major) {
                    return response()->json(['status' => 101, 'msg' => '专业代码为空']);
                }
                $where = array(
                    ['na_code', '=', $request->code],
                    ['rgt_type', '=', $request->rztx],
                    ['stop','=',1],
                    ['qlfts_state', '=', '01'],
                    ['nmbe_et', '>', date("Y-m-d")]
                );
                $flight = $this->RoutineLeader($where, $user, $request->major, $request->type);
                return ($flight);
                break;
            case 'HSE':
                if (!$request->major) {
                    return response()->json(['status' => 101, 'msg' => '专业代码为空']);
                }
                $where = array(
                    ['na_code', '=', $request->code],
                    ['rgt_type', '=', $request->rztx],
                    ['stop','=',1],
                    ['qlfts_state', '=', '01'],
                    ['nmbe_et', '>', date("Y-m-d")]
                );
                $flight = $this->RoutineLeader($where, $user, $request->major, $request->type);
                return ($flight);
                break;
            case 'ECPSC':
                if (!$request->major) {
                    return response()->json(['status' => 101, 'msg' => '专业代码为空']);
                }
                $where = array(
                    ['na_code', '=', $request->code],
                    ['rgt_type', '=', $request->rztx],
                    ['stop','=',1],
                    ['qlfts_state', '=', '01'],
                    ['nmbe_et', '>', date("Y-m-d")]
                );
                $flight = $this->RoutineLeader($where, $user, $request->major, $request->type);
                return ($flight);
                break;
            case '养老服务':
                if (!$request->major) {
                    return response()->json(['status' => 101, 'msg' => '专业代码为空']);
                }
                $where = array(
                    ['na_code', '=', $request->code],
                    ['rgt_type', '=', $request->rztx],
                    ['stop','=',1],
                    ['qlfts_state', '=', '01'],
                    ['nmbe_et', '>', date("Y-m-d")]
                );
                $flight = $this->RoutineLeader($where, $user, $request->major, $request->type);
                return ($flight);
                break;
            case '物业服务':
                if (!$request->major) {
                    return response()->json(['status' => 101, 'msg' => '专业代码为空']);
                }
                $where = array(
                    ['na_code', '=', $request->code],
                    ['rgt_type', '=', $request->rztx],
                    ['stop','=',1],
                    ['qlfts_state', '=', '01'],
                    ['nmbe_et', '>', date("Y-m-d")]
                );
                $flight = $this->RoutineLeader($where, $user, $request->major, $request->type);
                return ($flight);
                break;
            case 'IPT':
                $where = array(
                    ['na_code', '=', $request->code],
                    ['rgt_type', '=', $request->rztx],
                    ['stop','=',1],
                    ['qlfts_state', '=', '01'],
                );
                $flight = $this->ServiceLeader($where, $user);
                return ($flight);
                break;
            case 'EQC':
                $where = array(
                    ['na_code', '=', $request->code],
                    ['rgt_type', '=', $request->rztx],
                    ['stop','=',1],
                    ['qlfts_state', '=', '01'],
                );
                $flight = $this->ServiceLeader($where, $user);
                return ($flight);
                break;
            case 'CMS':
                $where = array(
                    ['na_code', '=', $request->code],
                    ['rgt_type', '=', $request->rztx],
                    ['stop','=',1],
                    ['qlfts_state', '=', '01'],
                );
                $flight = $this->ServiceLeader($where, $user);
                return ($flight);
                break;
            case 'AMS':
                $where = array(
                    ['na_code', '=', $request->code],
                    ['rgt_type', '=', $request->rztx],
                    ['stop','=',1],
                    ['qlfts_state', '=', '01'],
                );
                $flight = $this->ServiceLeader($where, $user);
                return ($flight);
                break;
            case 'IECE':
                $where = array(
                    ['na_code', '=', $request->code],
                    ['rgt_type', '=', $request->rztx],
                    ['stop','=',1],
                    ['qlfts_state', '=', '01'],
                );
                $flight = $this->ServiceLeader($where, $user);
                return ($flight);
                break;
            case 'EIMS':
                $where = array(
                    ['na_code', '=', $request->code],
                    ['rgt_type', '=', $request->rztx],
                    ['stop','=',1],
                    ['qlfts_state', '=', '01'],
                );
                $flight = $this->ServiceLeader($where, $user);
                return ($flight);
                break;
            case 'SA8000':
                $where = array(
                    ['na_code', '=', $request->code],
                    ['rgt_type', '=', $request->rztx],
                    ['stop','=',1],
                    ['qlfts_state', '=', '01'],
                );
                $flight = $this->ServiceLeader($where, $user);
                return ($flight);
                break;
            case 'YY':
                if (!$request->major) {
                    return response()->json(['status' => 101, 'msg' => '专业代码为空']);
                }
                $where = array(
                    ['na_code', '=', $request->code],
                    ['rgt_type', '=', $request->rztx],
                    ['stop','=',1],
                    ['qlfts_state', '=', '01'],
                    ['nmbe_et', '>', date("Y-m-d")]
                );
                $flight = $this->RoutineLeader($where, $user, $request->major, $request->type);
                return ($flight);
                break;
            case 'HACCP':
                if (!$request->major) {
                    return response()->json(['status' => 101, 'msg' => '专业代码为空']);
                }
                $where = array(
                    ['na_code', '=', $request->code],
                    ['rgt_type', '=', $request->rztx],
                    ['stop','=',1],
                    ['qlfts_state', '=', '01'],
                    ['nmbe_et', '>', date("Y-m-d")]
                );
                $flight = $this->RoutineLeader($where, $user, $request->major, $request->type);
                return ($flight);
                break;
            case 'FSMS':
                if (!$request->major) {
                    return response()->json(['status' => 101, 'msg' => '专业代码为空']);
                }
                $where = array(
                    ['na_code', '=', $request->code],
                    ['rgt_type', '=', $request->rztx],
                    ['stop','=',1],
                    ['qlfts_state', '=', '01'],
                    ['nmbe_et', '>', date("Y-m-d")]
                );
                $flight = $this->RoutineLeader($where, $user, $request->major, $request->type);
                return ($flight);
                break;
            case 'OGA':
                if (!$request->major) {
                    return response()->json(['status' => 101, 'msg' => '专业代码为空']);
                }
                $where = array(
                    ['na_code', '=', $request->code],
                    ['rgt_type', '=', $request->rztx],
                    ['stop','=',1],
                    ['qlfts_state', '=', '01'],
                    ['nmbe_et', '>', date("Y-m-d")]
                );
                $flight = $this->RoutineLeader($where, $user, $request->major, $request->type);
                return ($flight);
                break;
            case 'BCMS':
                if (!$request->major) {
                    return response()->json(['status' => 101, 'msg' => '专业代码为空']);
                }
                $where = array(
                    ['na_code', '=', $request->code],
                    ['rgt_type', '=', $request->rztx],
                    ['stop','=',1],
                    ['qlfts_state', '=', '01'],
                    ['nmbe_et', '>', date("Y-m-d")]
                );
                $flight = $this->RoutineLeader($where, $user, $request->major, $request->type);
                return ($flight);
                break;
        }
    }

    /**评定人员删除**/
    public function UserDelete(Request $request)
    {
        if (!$request->has(['evte_sbmt', 'dp_jdct'])) {
            return response()->json(['status' => 101, 'msg' => '参数不完整，不能进行删除']);
        }
        if ($request->evte_sbmt == 0 || $request->dp_jdct == 3) {
            $flights = InspectPlan::find($request->did);
            if($request->role == '01'){
                $flights->evte_gp = '';
            }
            $flights->evte_cw = substr($flights->evte_cw, 1, -1);
            $evteId = explode(";", $flights->evte_cw);
            $key = array_search($request->uid, $evteId);
            if ($key !== false) {
                unset($evteId[$key]);
            }
            if(!empty($evteId)){
                $flights->evte_cw = ';' . implode(";", $evteId) . ';';
            }else{
                $flights->evte_cw = '';
            }
            $eMajor = json_decode($flights->evte_major,TRUE);
            if(!empty($eMajor)){
                foreach ($eMajor as $key => $value){
                    if(in_array($request->uid,$value)){
                        unset($eMajor[$key]);
                        break;
                    }
                }
                if(!empty($eMajor)){
                    $flights->evte_major = json_encode($eMajor);
                }else{
                    $flights->evte_major = '';
                }
            }
            if ($flights->save()) {
                return response()->json(['status' => 100, 'msg' => '删除成功']);
            } else {
                return response()->json(['status' => 101, 'msg' => '删除失败']);
            }
        } else {
            return response()->json(['status' => 101, 'msg' => '没有权限，请申请权限']);
        }
    }

    /**评定人员保存**/
    public function UserAdd(Request $request)
    {
        $flights = InspectPlan::find($request->did);
        if ($flights->evte_sbmt == 0 || $flights->dp_jdct == 3) {
            $flights->evte_gp    = $request->leader;
            $flights->evte_cw    = ';'.$request->member.';';
            $flights->evte_major = $request->major;
            $flights->evte_state = 1;
            if($flights->dp_jdct == 3){
                if($flights->result_sbmt == 3){
                    $flights->result_sbmt = 2;
                }
                $flights->dp_jdct = 0;
            }
            if ($flights->save()) {
                return response()->json(['status' => 100, 'msg' => '保存成功']);
            } else {
                return response()->json(['status' => 101, 'msg' => '保存失败']);
            }
        }else{
            return response()->json(['status' => 101, 'msg' => '没有权限，请申请权限']);
        }
    }

    /**评定安排提交**/
    public function PersonnelSubmit(Request $request)
    {
        $flight = InspectPlan::find($request->did);
        switch ($request->pdap) {
            case 0:
                if($flight->evte_sbmt == 1){
                    return response()->json(['status' => 101, 'msg' => '该项目已提交']);
                }
                $where = array(
                    ['ap_id', '=', $request->did],
                    ['role', '=', '01']
                );
                $teamUser = InspectAuditTeam::where($where)
                    ->select('us_id')
                    ->get();
                if ($teamUser->isEmpty()) {
                    return response()->json(['status' => 101, 'msg' => '未查询到该项目组长']);
                }
                $flight->evte_gp    = $teamUser->first()->us_id;
                $flight->evte_cw    = ';'.$teamUser->first()->us_id.';';
                $flight->evte_major = '';
                break;
            case 1:
                if($flight->evte_state == 0){
                    return response()->json(['status' => 101, 'msg' => '该项目未安排评定人员']);
                }
                if($flight->evte_sbmt == 1){
                    return response()->json(['status' => 101, 'msg' => '该项目已提交']);
                }
                break;
        }
        $flight->arge_user = Auth::guard('api')->user()->name;
        $flight->arge_time = $request->arge_time;
        $flight->evte_sbmt = 1;
        if ($flight->save()) {
            return response()->json(['status' => 100, 'msg' => '评定安排提交成功']);
        } else {
            return response()->json(['status' => 101, 'msg' => '评定安排提交失败']);
        }
    }

    /**评定安排修改**/
    public function PersonnelEdit(Request $request)
    {
        $flight = InspectPlan::find($request->did);
        if ($flight->dp_jdct != 3) {
            return response()->json(['status' => 101, 'msg' => '没有权限，请申请权限']);
        }
        if (!$request->has('pdap')) {
            return response()->json(['status' => 101, 'msg' => '参数不完整']);
        }
        if ($request->pdap == 0) {
            $where = array(
                ['ap_id', '=', $request->did],
                ['role', '=', '01']
            );
            $teamUser = InspectAuditTeam::where($where)
                ->select('us_id')
                ->get();
            if ($teamUser->isEmpty()) {
                return response()->json(['status' => 101, 'msg' => '未查询到该项目组长']);
            }
            $flight->evte_gp    = $teamUser->first()->us_id;
            $flight->evte_cw    = ';'.$teamUser->first()->us_id.';';
            $flight->evte_major = '';
            $flight->evte_state = 0;
        }
        if($flight->result_sbmt == 3){
            $flight->result_sbmt = 2;
        }
        $flight->arge_time = $request->arge_time;
        $flight->dp_jdct = 0;
        if (!$flight->save()) {
            return response()->json(['status' => 101, 'msg' => '评定安排修改失败']);
        };
        return response()->json(['status' => 100, 'msg' => '评定安排修改成功']);

    }

    /**评定安排详情**/
    public function PersonnelEvte(Request $request)
    {
        $file = array(
            'rev_range',
            'rev_range_e',
            'p_rev_range',
            'p_rev_range_e',
            'arge_time',
            'category',
            'product',
            'evte_time',
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
        if($flighs->first()->product != null){
            $flighs->first()->product = json_decode($flighs->first()->product,true);
        }
        return response()->json(['status' => 100, 'msg' => '请求成功', 'data' => $flighs]);
    }

    /**评定批准**/
    public function PersonnelAdopt(Request $request)
    {
        if($request->cert_sbmt == 1){
            $flight = InspectPlan::find($request->did);
            if($flight->adopt_sbmt == 0){
                $flight->adopt_user = Auth::guard('api')->user()->name;
                $flight->adopt_time = $request->adopt_time;
                $flight->adopt_sbmt = 1;
                if($flight->save()){
                    return response()->json(['status'=>100,'msg'=>'评定批准成功']);
                }
                return response()->json(['status'=>101,'msg'=>'评定批准失败']);
            }else{
                return response()->json(['status'=>101,'msg'=>'该项目已经通过批准了，无需多次提交']);
            }
        }else{
            return response()->json(['status'=>101,'msg'=>'该项目还未通过技术评定']);
        }
    }
}
