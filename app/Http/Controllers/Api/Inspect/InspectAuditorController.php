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
use App\Models\Api\User\UserCtfcate;
use App\Models\Api\User\UserType;
use App\Models\Api\User\UserStudy;
use App\Models\Api\User\UserWork;
use App\Models\Api\User\UserTrain;
use App\Models\Api\User\UserMajor;
use App\Models\Api\Market\MarketChange;
use App\Models\Api\Examine\ExamineProject;
use App\Models\Api\Examine\ExamineSystem;
use App\Models\Api\System\SystemUpdate;
use App\Models\Api\System\SystemUnion;
use App\Models\Api\System\ApprovalGroup;
use App\Models\Api\System\SystemMajor;
use Illuminate\Support\Facades\DB;

class InspectAuditorController extends Controller
{
    /**审核人员列表**/
    public function AuditorIndex(Request $request){
        $where  = array(
            ['us_type','=',1],
/*            ['qlfts_type','=',1],*/
        );
        $flighs = $this->UserQuery($where,$request->limit,$request->field,$request->sort);
        return($flighs);
    }

    /**审核人员查询**/
    public function AuditorQuery(Request $request){
        $where  = array(
            ['us_type','=',1],
/*            ['qlfts_type','=',1],*/
        );
        if(!empty($request->name)){
            $where[] = ['name','=',$request->name];

        }
        if(!empty($request->na_code)){
            $where[] = ['na_code','=',$request->na_code];
        }
        if(!empty($request->me_code)){
            $where[] = ['me_code','=',$request->me_code];
        }
        if(!empty($request->us_qlfts)){
            $where[] = ['us_qlfts','=',$request->us_qlfts];
        }
        if(!empty($request->rgt_type)){
            $where[] = ['rgt_type','=',$request->rgt_type];
        }
        if(!empty($request->nmbe_et)){
            $where[] = ['nmbe_et','<=',$request->nmbe_et.'-31'];
        }
        if(!empty($request->major_code)){
            $where[] = ['major_code','like',$request->major_code.'%'];
        }
        if(!empty($request->major_rang)){
            $where[] = ['major_rang','like','%'.$request->major_rang.'%'];
        }
        if(!$request->major_code && !$request->major_rang){
            $flighs = $this->UserQuery($where,$request->limit,$request->field,$request->sort);
        }else{
            $flighs = $this->TypeQuery($where,$request->limit,$request->field,$request->sort);
        }
        return ($flighs);
    }

    /**查询人员函数**/
    public function UserQuery($where,$limit,$sortField,$sort){
        $file = array(
            'users.id as id',
            'name',
            'na_code',
            'me_code',
            'nmbe_et',
            'sex',
            'pp_edct',
            'pp_major',
            'school',
            'e_mail',
            'telephone',
            'type',
            'fz_at',
            'stop',
            'us_qlfts',
            'rgt_type',
            'rgt_numb',
            'regter_st',
            'regter_et',
            'year_ct',
            'group_abty',
            'witn_abty',
            'ealte_abty',
            'qlfts_type',
        );
        switch ($sortField)
        {
            case 1:
                $sortField = 'users.id';
                break;
            case 2:
                $sortField = 'regter_st';
                break;
            case 3:
                $sortField = 'regter_et';
                break;
            case 4:
                $sortField = 'year_ct';
                break;
            default:
                $sortField = 'users.id';
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
        $flighs = UserBasic::UserBasic($file,$where,$limit,$sortField,$sort);
        //dump(DB::getQueryLog());die;
        if($flighs->isEmpty()){
            return response()->json(['status'=>101,'msg'=>'无数据']);
        }
        $flighs = $flighs->toArray();
        array_walk($flighs['data'], function (&$value,$key){
            $value['sindex'] = $value['id'].$value['rgt_type'];
            switch ($value['sex'])
            {
                case 0:
                    $value['sex'] = '男';
                    break;
                case 1:
                    $value['sex'] = '女';
                    break;
            }
            switch ($value['type'])
            {
                case 0:
                    $value['type'] = '兼职';
                    break;
                case 1:
                    $value['type'] = '专职';
                    break;
            }
            switch ($value['stop'])
            {
                case 1:
                    $value['stop'] = '在职';
                    break;
                case 2:
                    $value['stop'] = '离职';
                    break;
                case 3:
                    $value['stop'] = '禁用';
                    break;
            }
            switch ($value['group_abty'])
            {
                case 1:
                    $value['group_abty'] = '是';
                    break;
                case 2:
                    $value['group_abty'] = '否';
                    break;
            }
            switch ($value['witn_abty'])
            {
                case 1:
                    $value['witn_abty'] = '是';
                    break;
                case 2:
                    $value['witn_abty'] = '否';
                    break;
            }
            switch ($value['ealte_abty'])
            {
                case 1:
                    $value['ealte_abty'] = '是';
                    break;
                case 2:
                    $value['ealte_abty'] = '否';
                    break;
            }
        });
        return response()->json(['status'=>100,'msg'=>'请求成功','data'=>$flighs]);
    }

    /**人员资质代码列表**/
    public function TypeMajor(Request $request){
        $where  = array(
            ['users.id','=',$request->id],
            ['rgt_type','=',$request->rgt_type],
        );
        $flighs = $this->TypeQuery($where);
        return($flighs);
    }

    /**查询人员函数**/
    public function TypeQuery($where,$limit='',$sortField='',$sort=''){
        $file = array(
            'users.id as id',
            'major_userm.id as mid',
            'name',
            'na_code',
            'me_code',
            'nmbe_et',
            'sex',
            'pp_edct',
            'pp_major',
            'school',
            'e_mail',
            'telephone',
            'type',
            'fz_at',
            'stop',
            'us_qlfts',
            'rgt_type',
            'rgt_numb',
            'regter_st',
            'regter_et',
            'year_ct',
            'group_abty',
            'witn_abty',
            'ealte_abty',
            'qlfts_type',
            'major_code',
            'major_rang',
            'major_source',
            'major_statee',
        );
        switch ($sortField)
        {
            case 1:
                $sortField = 'users.id';
                break;
            case 2:
                $sortField = 'regter_st';
                break;
            case 3:
                $sortField = 'regter_et';
                break;
            case 4:
                $sortField = 'year_ct';
                break;
            default:
                $sortField = 'users.id';
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
        $flighs = UserBasic::TypeMajor($file,$where,$limit,$sortField,$sort);
        //dump(DB::getQueryLog());
        if($flighs->isEmpty()){
            return response()->json(['status'=>101,'msg'=>'无数据']);
        }
        $flighs = $flighs->toArray();
        if(isset($flighs['data'])){
            $flight = $flighs['data'];
        }else{
            $flight = $flighs;
        }
        array_walk($flight, function ($value,$key) use (&$flights){
            $value['sindex'] = $value['id'].$value['rgt_type'].$value['mid'];
            switch ($value['sex'])
            {
                case 0:
                    $value['sex'] = '男';
                    break;
                case 1:
                    $value['sex'] = '女';
                    break;
            }
            switch ($value['type'])
            {
                case 0:
                    $value['type'] = '兼职';
                    break;
                case 1:
                    $value['type'] = '专职';
                    break;
            }
            switch ($value['stop'])
            {
                case 1:
                    $value['stop'] = '在职';
                    break;
                case 2:
                    $value['stop'] = '离职';
                    break;
                case 3:
                    $value['stop'] = '禁用';
                    break;
            }
            switch ($value['group_abty'])
            {
                case 1:
                    $value['group_abty'] = '是';
                    break;
                case 2:
                    $value['group_abty'] = '否';
                    break;
            }
            switch ($value['witn_abty'])
            {
                case 1:
                    $value['witn_abty'] = '是';
                    break;
                case 2:
                    $value['witn_abty'] = '否';
                    break;
            }
            switch ($value['ealte_abty'])
            {
                case 1:
                    $value['ealte_abty'] = '是';
                    break;
                case 2:
                    $value['ealte_abty'] = '否';
                    break;
            }
            switch ($value['major_statee'])
            {
                case '01':
                    $value['major_statee'] = '正常';
                    break;
                case '02':
                    $value['major_statee'] = '暂停';
                    break;
                case '03':
                    $value['major_statee'] = '撤销';
                    break;
            }
            $flights[] = $value;
        });
        if(isset($flighs['data'])){
            $flighs['data'] = $flights;
        }else{
            $flighs = $flights;
        }
        return response()->json(['status'=>100,'msg'=>'请求成功','data'=>$flighs]);
    }

    /**人员基本信息**/
    public function AuditorUser(Request $request){
        $file = array(
            'users.id as id',
            'name',
            'na_code',
            'me_code',
            'nmbe',
            'nmbe_st',
            'nmbe_et',
            'sex',
            'cft_type',
            'cft_numb',
            'pp_edct',
            'pp_major',
            'school',
            'politics',
            'title',
            'work_unit',
            'telephone',
            'e_mail',
            'postal_site',
            'type',
            'region',
            'open_point',
            'bank_acunt',
            'bm_id',
            'zw_id',
            'bumen',
            /* 'zhiwu',*/
            'stop',
        );
        $where = array(
            ['users.id',$request->id]
        );
        //DB::connection()->enableQueryLog();#开启执行日志
        $flighs = UserBasic::UserIndex($file,$where,'');
        //dump(DB::getQueryLog());
        if($flighs->isEmpty()){
            return response()->json(['status'=>101,'msg'=>'无数据']);
        }
        $flighs->first()->bumen = $flighs->first()->bumen?$flighs->first()->bumen:'';
        $flighs->first()->zhiwu = $flighs->first()->zhiwu?$flighs->first()->zhiwu:'';
        return response()->json(['status'=>100,'msg'=>'请求成功','data'=>$flighs]);
    }

    /**人员添加/修改**/
    public function UserAdd(Request $request){
        if(!$request->id){
            $flighs = UserBasic::where([
                ['cft_type',$request->cft_type],
                ['cft_numb',$request->cft_numb],
                ])
                ->get();
            if($flighs->isEmpty()){
                $flights = new UserBasic;
                $flights->username= time();
            }else{
                $flights = UserBasic::find($flighs->first()->id);
            }
        }else{
            $flights = UserBasic::find($request->id);
        }
        //DB::connection()->enableQueryLog();#开启执行日志
        $flights->name    = $request->name;
        $flights->na_code = $request->na_code;
        $flights->me_code = $request->me_code;
        $flights->nmbe    = $request->nmbe;
        $flights->nmbe_st = $request->nmbe_st;
        $flights->nmbe_et = $request->nmbe_et;
        $flights->sex     = $request->sex;
        $flights->cft_type= $request->cft_type;
        $flights->cft_numb= $request->cft_numb;
        $flights->pp_edct = $request->pp_edct;
        $flights->pp_major= $request->pp_major;
        $flights->school  = $request->school;
        $flights->politics= $request->politics;
        $flights->title   = $request->title;
        $flights->work_unit  = $request->work_unit;
        $flights->telephone  = $request->telephone;
        $flights->e_mail     = $request->e_mail;
        $flights->postal_site= $request->postal_site;
        $flights->type        = $request->type;
        $flights->region      = $request->region;
        $flights->open_point = $request->open_point;
        $flights->bank_acunt = $request->bank_acunt;
        $flights->bm_id       = $request->bm_id;
        $flights->zw_id       = $request->zw_id;
        $flights->us_type     = 1;
        $flights->stop        = $request->stop;
        if(!$flights->save()){
            return response()->json(['status'=>101,'msg'=>'数据更新失败']);
        }
        return response()->json(['status'=>100,'msg'=>'数据更新成功','data'=>$flights->id]);
        //dump(DB::getQueryLog());
    }

    /**人员资质信息**/
    public function AuditorType(Request $request){
        $where  = array(
            ['us_id','=',$request->id],
            ['qlfts_type','=',1],
        );
        $flighs = UserType::where($where)
            ->get();
        if($flighs->isEmpty()){
            return response()->json(['status'=>101,'msg'=>'无数据']);
        }
        return response()->json(['status'=>100,'msg'=>'请求成功','data'=>$flighs]);
    }

    /**人员资质添加/修改**/
    public function TypeAdd(Request $request){
        $data   = json_decode($request->data,true );
        $flight = array();
        array_walk($data, function ($value,$key) use (&$flight){
            if($value['id'] == 0){
                $flight['add'][] = array(
                    'us_id'     => $value['us_id'],
                    'us_qlfts'  => $value['us_qlfts'],
                    'rgt_type'  => $value['rgt_type'],
                    'rgt_numb'  => $value['rgt_numb'],
                    'regter_st' => $value['regter_st'],
                    'regter_et' => $value['regter_et'],
                    'year_ct'   => $value['year_ct'],
                    'group_abty'=> $value['group_abty'],
                    'group_open'=> $value['group_open'],
                    'witn_abty' => $value['witn_abty'],
                    'witn_st'   => $value['witn_st'],
                    'witn_open' => $value['witn_open'],
                    'ealte_abty'=> $value['ealte_abty'],
                    'ealte_open'=> $value['ealte_open'],
                    'turn_version'=> $value['turn_version'],
                    'qlfts_state' => $value['qlfts_state'],
                );
            }else{
                $flight['edit'][] = array(
                    'id'        => $value['id'],
                    'us_id'     => $value['us_id'],
                    'us_qlfts'  => $value['us_qlfts'],
                    'rgt_type'  => $value['rgt_type'],
                    'rgt_numb'  => $value['rgt_numb'],
                    'regter_st' => $value['regter_st'],
                    'regter_et' => $value['regter_et'],
                    'year_ct'   => $value['year_ct'],
                    'group_abty'=> $value['group_abty'],
                    'group_open'=> $value['group_open'],
                    'witn_abty' => $value['witn_abty'],
                    'witn_st'   => $value['witn_st'],
                    'witn_open' => $value['witn_open'],
                    'ealte_abty'=> $value['ealte_abty'],
                    'ealte_open'=> $value['ealte_open'],
                    'turn_version'=> $value['turn_version'],
                    'qlfts_state' => $value['qlfts_state'],
                );
            }
        });
        DB::beginTransaction();
        try {
            if(!empty($flight['add'])){
                $fligha = UserType::insert($flight['add']);
                if($fligha == 0){
                    DB::rollback();
                    return response()->json(['status'=>101,'msg'=>'资质添加失败']);
                }
            }
            if(!empty($flight['edit'])){
                $flighe = SystemUpdate::UpdateBatch('major_user',$flight['edit']);
                if($flighe == 0){
                    DB::rollback();
                    return response()->json(['status'=>101,'msg'=>'资质修改失败']);
                }
            }
            DB::commit();
        }catch (\Exception $e){
            DB::rollback();
            return response()->json(['status'=>101,'msg'=>'资质添加/修改失败']);
        }
        return response()->json(['status'=>100,'msg'=>'资质添加/修改成功']);
    }

    /**人员资质删除**/
    public function TypeDel(Request $request){
        $id = explode(";",$request->id);
        DB::beginTransaction();
        try {
            $flight = UserType::whereIn('id',$id)->delete();
            if($flight == 0){
                return response()->json(['status'=>101,'msg'=>'删除失败']);
            }
            UserMajor::whereIn('m_id',$id)->delete();
            DB::commit();
        }catch (\Exception $e){
            DB::rollback();
            return response()->json(['status'=>101,'msg'=>'删除失败']);
        }
        return response()->json(['status'=>100,'msg'=>'删除成功']);
    }

    /**人员资质类别**/
    public function AuditorCategory(Request $request){
        $flighs = UserCtfcate::where('state','1')
            ->select('code','qlfts')
            ->get();
        if($flighs->isEmpty()){
            return response()->json(['status'=>101,'msg'=>'无数据']);
        }
        return response()->json(['status'=>100,'msg'=>'请求成功','data'=>$flighs]);
    }

    /**人员评定信息**/
    public function AuditorDecide(Request $request){
        $where  = array(
            ['us_id','=',$request->id],
            ['qlfts_type','=',0],
        );
        $flighs = UserType::where($where)
            ->get();
        if($flighs->isEmpty()){
            return response()->json(['status'=>101,'msg'=>'无数据']);
        }
        return response()->json(['status'=>100,'msg'=>'请求成功','data'=>$flighs]);
    }

    /**人员评定添加/修改**/
    public function DecideAdd(Request $request){
        $data   = json_decode($request->data,true );
        $flight = array();
        array_walk($data, function ($value,$key) use (&$flight){
            if($value['id'] == 0){
                $flight['add'][] = array(
                    'us_id'     => $value['us_id'],
                    'rgt_type'  => $value['rgt_type'],
                    'group_abty'=> 1,
                    'ealte_abty'=> 1,
                    'ealte_open'=> $value['ealte_open'],
                    'qlfts_state'=> $value['qlfts_state'],
                    'qlfts_type' => 0,
                );
            }else{
                $flight['edit'][] = array(
                    'id'          => $value['id'],
                    'us_id'      => $value['us_id'],
                    'rgt_type'   => $value['rgt_type'],
                    'ealte_open' => $value['ealte_open'],
                    'qlfts_state'=> $value['qlfts_state'],
                );
            }
        });
        DB::beginTransaction();
        try {
            if(!empty($flight['add'])){
                $fligha = UserType::insert($flight['add']);
                if($fligha == 0){
                    DB::rollback();
                    return response()->json(['status'=>101,'msg'=>'评定添加失败']);
                }
            }
            if(!empty($flight['edit'])){
                $flighe = SystemUpdate::UpdateBatch('major_user',$flight['edit']);
                if($flighe == 0){
                    DB::rollback();
                    return response()->json(['status'=>101,'msg'=>'评定修改失败']);
                }
            }
            DB::commit();
        }catch (\Exception $e){
            DB::rollback();
            return response()->json(['status'=>101,'msg'=>'评定添加/修改失败']);
        }
        return response()->json(['status'=>100,'msg'=>'评定添加/修改成功']);
    }

    /**人员评定删除**/
    public function DecideDel(Request $request){
        $id = explode(";",$request->id);
        $flight = UserType::where('qlfts_type','=','0')
            ->whereIn('id',$id)
            ->delete();
        if($flight == 0){
            return response()->json(['status'=>101,'msg'=>'删除失败,请在资质中取消评定能力']);
        }
        return response()->json(['status'=>101,'msg'=>'删除成功']);
    }

    /**人员教育经历**/
    public function AuditorStudy(Request $request){
        $flighs = UserStudy::where('us_id',$request->id)
            ->get();
        if($flighs->isEmpty()){
            return response()->json(['status'=>101,'msg'=>'无数据']);
        }
        return response()->json(['status'=>100,'msg'=>'请求成功','data'=>$flighs]);
    }

    /**人员教育添加/修改**/
    public function StudyAdd(Request $request){
        $data   = json_decode($request->data,true );
        $flight = array();
        array_walk($data, function ($value,$key) use (&$flight){
            if($value['id'] == 0){
                $flight['add'][] = array(
                    'us_id'      => $value['us_id'],
                    'start_time'=> $value['start_time'],
                    'end_time'  => $value['end_time'],
                    'school'    => $value['school'],
                    'major'     => $value['major'],
                    'education'=> $value['education'],
                );
            }else{
                $flight['edit'][] = array(
                    'id'         => $value['id'],
                    'us_id'      => $value['us_id'],
                    'start_time'=> $value['start_time'],
                    'end_time'  => $value['end_time'],
                    'school'    => $value['school'],
                    'major'     => $value['major'],
                    'education'=> $value['education'],
                );
            }
        });
        DB::beginTransaction();
        try {
            if(!empty($flight['add'])){
                $fligha = UserStudy::insert($flight['add']);
                if($fligha == 0){
                    return response()->json(['status'=>101,'msg'=>'教育经历添加失败']);
                }
            }
            if(!empty($flight['edit'])){
                $flighe = SystemUpdate::UpdateBatch('user_study',$flight['edit']);
                if($flighe == 0){
                    return response()->json(['status'=>101,'msg'=>'教育经历修改失败']);
                }
            }
            DB::commit();
        }catch (\Exception $e){
            DB::rollback();
            return response()->json(['status'=>101,'msg'=>'教育经历添加/修改失败']);
        }
        return response()->json(['status'=>100,'msg'=>'教育经历添加/修改成功']);
    }

    /**人员教育删除**/
    public function StudyDel(Request $request){
        $id = explode(";",$request->id);
        $flight = UserStudy::whereIn('id',$id)->delete();
        if($flight == 0){
            return response()->json(['status'=>101,'msg'=>'删除失败']);
        }
        return response()->json(['status'=>100,'msg'=>'删除成功']);
    }

    /**人员工作经历**/
    public function AuditorWork(Request $request){
        $flighs = UserWork::where('us_id',$request->id)
            ->get();
        if($flighs->isEmpty()){
            return response()->json(['status'=>101,'msg'=>'无数据']);
        }
        return response()->json(['status'=>100,'msg'=>'请求成功','data'=>$flighs]);
    }

    /**人员工作添加/修改**/
    public function WorkAdd(Request $request){
        $data   = json_decode($request->data,true );
        $flight = array();
        array_walk($data, function ($value,$key) use (&$flight){
            if($value['id'] == 0){
                $flight['add'][] = array(
                    'us_id'      => $value['us_id'],
                    'start_time'=> $value['start_time'],
                    'end_time'  => $value['end_time'],
                    'company'   => $value['company'],
                    'witness'   => $value['witness'],
                    'tell'      => $value['tell'],
                    'detail'    => $value['detail'],
                );
            }else{
                $flight['edit'][] = array(
                    'id'         => $value['id'],
                    'us_id'      => $value['us_id'],
                    'start_time'=> $value['start_time'],
                    'end_time'  => $value['end_time'],
                    'company'   => $value['company'],
                    'witness'   => $value['witness'],
                    'tell'      => $value['tell'],
                    'detail'    => $value['detail'],
                );
            }
        });
        DB::beginTransaction();
        try {
            if(!empty($flight['add'])){
                $fligha = UserWork::insert($flight['add']);
                if($fligha == 0){
                    return response()->json(['status'=>101,'msg'=>'工作经历添加失败']);
                }
            }
            if(!empty($flight['edit'])){
                $flighe = SystemUpdate::UpdateBatch('user_work',$flight['edit']);
                if($flighe == 0){
                    return response()->json(['status'=>101,'msg'=>'工作经历修改失败']);
                }
            }
            DB::commit();
        }catch (\Exception $e){
            DB::rollback();
            return response()->json(['status'=>101,'msg'=>'工作经历添加/修改失败']);
        }
        return response()->json(['status'=>100,'msg'=>'工作经历添加/修改成功']);
    }

    /**人员工作删除**/
    public function WorkDel(Request $request){
        $id = explode(";",$request->id);
        $flight = UserWork::whereIn('id',$id)->delete();
        if($flight == 0){
            return response()->json(['status'=>101,'msg'=>'删除失败']);
        }
        return response()->json(['status'=>100,'msg'=>'删除成功']);
    }

    /**人员培训经历**/
    public function AuditorTrain(Request $request){
        $flighs = UserTrain::where('us_id',$request->id)
            ->get();
        if($flighs->isEmpty()){
            return response()->json(['status'=>101,'msg'=>'无数据']);
        }
        return response()->json(['status'=>100,'msg'=>'请求成功','data'=>$flighs]);
    }

    /**人员培训添加/修改**/
    public function TrainAdd(Request $request){
        $data   = json_decode($request->data,true );
        $flight = array();
        array_walk($data, function ($value,$key) use (&$flight){
            if($value['id'] == 0){
                $flight['add'][] = array(
                    'us_id'       => $value['us_id'],
                    'start_time' => $value['start_time'],
                    'end_time'   => $value['end_time'],
                    'certificate'=> $value['certificate'],
                    'content'    => $value['content'],
                );
            }else{
                $flight['edit'][] = array(
                    'id'          => $value['id'],
                    'us_id'       => $value['us_id'],
                    'start_time' => $value['start_time'],
                    'end_time'   => $value['end_time'],
                    'certificate'=> $value['certificate'],
                    'content'    => $value['content'],
                );
            }
        });
        DB::beginTransaction();
        try {
            if(!empty($flight['add'])){
                $fligha = UserTrain::insert($flight['add']);
                if($fligha == 0){
                    return response()->json(['status'=>101,'msg'=>'培训经历添加失败']);
                }
            }
            if(!empty($flight['edit'])){
                $flighe = SystemUpdate::UpdateBatch('user_train',$flight['edit']);
                if($flighe == 0){
                    return response()->json(['status'=>101,'msg'=>'培训经历修改失败']);
                }
            }
            DB::commit();
        }catch (\Exception $e){
            DB::rollback();
            return response()->json(['status'=>101,'msg'=>'培训经历添加/修改失败']);
        }
        return response()->json(['status'=>100,'msg'=>'培训经历添加/修改成功']);
    }

    /**人员培训删除**/
    public function TrainDel(Request $request){
        $id = explode(";",$request->id);
        $flight = UserTrain::whereIn('id',$id)->delete();
        if($flight == 0){
            return response()->json(['status'=>101,'msg'=>'删除失败']);
        }
        return response()->json(['status'=>100,'msg'=>'删除成功']);
    }

    /**人员专业代码**/
    public function AuditorMajor(Request $request){
        $flighs = $this->MajorShare($request->id,'',$request->limit);
        return ($flighs);
    }

    /**人员代码查询**/
    public function MajorQuery(Request $request){
        $where = array();
        if(!empty($request->major_m)){
            $where[] = ['major_m','=',$request->major_m];

        }
        if(!empty($request->m_id)){
            $where[] = ['m_id','=',$request->m_id];
        }
        if(!empty($request->major_code)){
            $where[] = ['major_code','like','%'.$request->major_code.'%'];
        }
        if(!empty($request->major_source)){
            $where[] = ['major_source','=',$request->major_source];
        }
        if(!empty($request->major_statee)){
            $where[] = ['major_statee','=',$request->major_statee];
        }
        $flighs = $this->MajorShare($request->id,$where,$request->limit);
        return ($flighs);
    }

    /**人员代码函数**/
    public function MajorShare($id,$where,$limit){
        //DB::connection()->enableQueryLog();#开启执行日志
        $flighs = UserBasic::find($id)
            ->UserMajor()
            ->when($where,function ($query) use ($where) {
                return  $query->where($where);
            })
            ->paginate($limit);
        //dump(DB::getQueryLog());die;
        if($flighs->isEmpty()){
            return response()->json(['status'=>101,'msg'=>'无数据']);
        }
        $flighs = $flighs->toArray();
        array_walk($flighs['data'], function (&$value,$key){
            switch ($value['major_m'])
            {
                case 1:
                    $value['major_m'] = 'CNAS';
                    break;
                case 2:
                    $value['major_m'] = 'UKAS';
                    break;
                case 3:
                    $value['major_m'] = 'JAS-ANS';
                    break;
            }
//            switch ($value['major_statee'])
//            {
//                case '01':
//                    $value['major_statee'] = '正常';
//                    break;
//                case '02':
//                    $value['major_statee'] = '暂停';
//                    break;
//                case '03':
//                    $value['major_statee'] = '撤销';
//                    break;
//            }
        });
        return response()->json(['status'=>100,'msg'=>'请求成功','data'=>$flighs]);
    }

    /**人员代码添加/修改**/
    public function MajorAdd(Request $request){
        $data   = json_decode($request->data,true );
        $flight = array();
        array_walk($data, function ($value,$key) use (&$flight){
            if($value['id'] == 0){
                $flight['add'][] = array(
                    'm_id'       => $value['m_id'],
                    'major_name'=> $value['major_name'],
                    'major_code'=> $value['major_code'],
                    'major_rang'=> $value['major_rang'],
                    'major_time'=> $value['major_time'],
                    'major_source'=> $value['major_source'],
                    'major_statee'=> $value['major_statee'],
                    'major_m'      => $value['major_m'],
                    'major_remark'=> $value['major_remark'],
                    'major_new' => $value['major_new'],
                );
            }else{
                $flight['edit'][] = array(
                    'id'         => $value['id'],
                    'major_rang'=> $value['major_rang'],
                    'major_time'=> $value['major_time'],
                    'major_source'=> $value['major_source'],
                    'major_statee'=> $value['major_statee'],
                    'major_remark'=> $value['major_remark'],
                    'major_new' => $value['major_new'],
                );
            }
        });
        DB::beginTransaction();
        try {
            if(!empty($flight['add'])){
                $fligha = SystemUpdate::InsertBatch('major_userm',$flight['add']);
                if($fligha != true){
                    DB::rollback();
                    return response()->json(['status'=>101,'msg'=>$fligha]);
                }
            }
            if(!empty($flight['edit'])){
                $flighe = SystemUpdate::UpdateBatch('major_userm',$flight['edit']);
                if($flighe == 0){
                    DB::rollback();
                    return response()->json(['status'=>101,'msg'=>'代码修改失败']);
                }
            }
            DB::commit();
        }catch (\Exception $e){
            DB::rollback();
            return response()->json(['status'=>101,'msg'=>$e->getMessage()]);
        }
        return response()->json(['status'=>100,'msg'=>'代码添加/修改成功']);
    }

    /**人员代码导入（EXCEL）**/
    public function MajorImport(Request $request){
        $data = json_decode($request->data,true );
        unset($data[0]);
        unset($data[1]);
/*        $data = array(
            ['','QMS',0,' 03.01.03N ',' 每个专业代码对应的专业说明 ',' 2020-02-03 ','工作经历','01','备注'],
            ['','QMS',1,'03.01.03','每个专业代码对应的专业说明','2020-02-03','工作经历','01'],
            ['','QMS',0,'03.01.04','每个专业代码对应的专业说明','2020-02-03','工作经历','01','备注'],
            ['','QMS',0,'03.01.05',' 每个专业代码对应的专业说明 ','2020-02-03','工作经历','01','备注'],
            ['','QMS',0,' 03.01.06 ','每个专业代码对应的专业说明','2020-02-03','工作经历','01','备注'],
            ['',' QMS ',0,'03.01.07','每个专业代码对应的专业说明','2020-02-03','工作经历','01','备注'],
        );*/
        if(empty($data)){
            return response()->json(['status'=>101,'msg'=>'表格数据为空!']);
        }
        $flighs = UserType::where('us_id',$request->uid)
            ->select('id','rgt_type')
            ->get();
        if($flighs->isEmpty()){
            return response()->json(['status'=>101,'msg'=>'人员资质为空!']);
        }
        $flighs = $flighs->toArray();
        array_walk($flighs, function ($value,$key) use (&$flight){
            if(!isset($flight[$value['rgt_type']])){
                $flight[$value['rgt_type']] = $value['id'];
            }
        });
        array_walk($data, function ($value,$key,$flight) use (&$flights){
            $regular['major_name'] = isset($value[1])?trim($value[1]):'';
            $regular['major_m'] = isset($value[2])?trim($value[2]):'';
            $regular['major_code'] = isset($value[3])?trim($value[3]):'';
            $regular['major_rang'] = isset($value[4])?trim($value[4]):'';
            $regular['major_time'] = isset($value[5])?trim($value[5]):'';
            $regular['major_source'] = isset($value[6])?trim($value[6]):'';
            $regular['major_statee'] = isset($value[7])?trim($value[7]):'';
            $regular['major_remark'] = isset($value[8])?trim($value[8]):'';
            $date = UserMajor::FormValidation($regular);
            if($date['type'] == false){
                $regular['key']   = $key;
                $regular['error'] = implode('<br/>',$date['error']);
                $flights['error'][$key] = $regular;
            }
            if(!isset($flight[$regular['major_name']])){
                if(!isset($flights['error'][$key])){
                    $regular['key']   = $key;
                    $regular['error'] = '该人员没有此资质';
                    $flights['error'][$key] = $regular;
                }else{
                    $flights['error'][$key]['error'] .= '<br/>该人员没有此资质';
                }
            }
            if(empty($flights['error'])){
                $regular['m_id'] = $flight[$regular['major_name']];
                if(strpos($regular['major_code'],'N') == false){
                    $regular['major_new'] = 0;
                }else{
                    $regular['major_new'] = 1;
                }
                $flights['right'][] = $regular;
            }
        },$flight);
        if(!empty($flights['error'])){
            return response()->json(['status'=>101,'msg'=>'表格数据有误','data'=>array_values($flights['error'])]);
        }
        $flighe = SystemUpdate::InsertBatch('major_userm',$flights['right']);
        if($flighe == true){
            return response()->json(['status'=>100,'msg'=>'表格导入成功']);
        }
        return response()->json(['status'=>101,'msg'=>$flighe]);
    }

    /**人员代码删除**/
    public function MajorDel(Request $request){
        $id = explode(";",$request->id);
        $flight = UserMajor::whereIn('id',$id)->delete();
        if($flight == 0){
            return response()->json(['status'=>101,'msg'=>'删除失败']);
        }
        return response()->json(['status'=>100,'msg'=>'删除成功']);
    }

    /**人员代码转态修改**/
    public function MajorState(Request $request){
        $data = json_decode($request->data,true );
        if(empty($data)){
            return response()->json(['status' => 101, 'msg' => '未选择专业代码']);
        }
        switch ($request->state)
        {
            case '01':
                $flights = UserMajor::whereIn('id', $data)
                    ->update(['major_statee' => '01']);
                if($flights == 0){
                    return response()->json(['status' => 101, 'msg' => '专业代码状态修改失败']);
                }
                break;
            case '02':
                $flights = UserMajor::whereIn('id', $data)
                    ->update(['major_statee' => '02']);
                if($flights == 0){
                    return response()->json(['status' => 101, 'msg' => '专业代码状态修改失败']);
                }
                break;
        }
        return response()->json(['status'=>100,'msg'=>'成功修改'.$flights.'条专业代码状态']);
    }

    /**代码复制**/
    public function MajorCopy(Request $request){
        $flight = SystemMajor::where([
            ['b_m','=',1],
            ['n_old','=',1],
            ['e_name','=','QMS'],
        ])
            ->get();
        if($flight->isEmpty()){
            return response()->json(['status'=>101,'msg'=>'专业代码为空!']);
        }
        $flight = $flight->toArray();
        array_walk($flight, function ($value,$key,$rztx) use (&$flighs){
            $data['e_name'] = $rztx;
            $data['b_code'] = $value['b_code'];
            $data['b_range'] = $value['b_range'];
            $data['n_old'] = $value['n_old'];
            $data['b_m'] = $value['b_m'];
            $flighs[] = $data;
        },$request->rztx);
        $fligha = SystemMajor::insert($flighs);
        if($fligha == true){
            return response()->json(['status'=>100,'msg'=>'专业代码导入成功']);
        }
        return response()->json(['status'=>101,'msg'=>'专业代码导入失败']);
    }
}
