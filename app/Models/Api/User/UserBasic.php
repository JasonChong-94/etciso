<?php
/**
 * Created by PhpStorm.
 * User: Greatwall
 * Date: 2019/8/13
 * Time: 17:53
 */
namespace App\Models\Api\User;
use Illuminate\Database\Eloquent\Model;
class UserBasic extends Model{

    /**定义表名**/
    protected $table = 'users';

    public $timestamps = false;

    public function teamUser()
    {
        return $this->hasMany('App\Models\Api\Inspect\InspectAuditTeam','us_id');
    }

    public function userType()
    {
        return $this->hasMany('App\Models\Api\User\UserType','us_id');
    }

    public function userClock()
    {
        return $this->hasMany('App\Models\Api\User\UserClock','openid','openid');
    }

    public function marketRegion()
    {
        return $this->belongsTo('App\Models\Api\Market\MarketRegion','region','id');
    }

    protected function codeSwitch($value){
        if(isset($value['us_qlfts']))
            switch ($value['us_qlfts']) {
                case '01':
                    $value['qlfts'] = '高级审核员';
                    break;
                case '02':
                    $value['qlfts'] = '审核员';
                    break;
                case '03':
                    $value['qlfts'] = '实习审核员';
                    break;
                case '04':
                    $value['qlfts'] = '技术专家';
                    break;
                case '05':
                    $value['qlfts'] = '高级审查员';
                    break;
                case '06':
                    $value['qlfts'] = '审查员';
                    break;
                case '07':
                    $value['qlfts'] = '主任审核员';
                    break;
                default:
                    $value['qlfts'] = '其他';
            };
        if(isset($value['witic']))
            switch($value['witic']){
                case '01':
                    $value['c_witic'] = '见证人';
                    break;
                case '02':
                    $value['c_witic'] = '被见证人';
                    break;
                case '00':
                    $value['c_witic'] = '';
                    break;
            };
        if(isset($value['role']))
            switch($value['role']){
                case '01':
                    $value['c_role'] = '组长';
                    break;
                case '02':
                    $value['c_role'] = '组员';
                    break;
                case '03':
                    $value['c_role'] = '技术专家';
                    break;
            };
        if(isset($value['type']))
            $value['c_type']  = $value['type'] == 1 ? '专职':'兼职';
        if(isset($value['trial']))
            $value['c_trial']  = $value['trial'] == 1 ? '是':'否';
        if(isset($value['mjexm']))
            $value['c_mjexm']  = $value['mjexm'] == 1 ? '是':'否';
        if(isset($value['group_abty']))
            $value['c_group'] = $value['group_abty'] == 1 ? '是' : '否';
        if(isset($value['ealte_abty']))
            $value['c_ealte']= $value['ealte_abty'] == 1 ? '是' : '否';
        if(isset($value['witn_abty']))
            $value['c_abty']= $value['witn_abty'] == 1 ? '是' : '否';
        if(isset($value['turn_version']))
            $value['c_version']= $value['turn_version'] == 1 ? '是' : '否';
        if(isset($value['sex']))
            $value['c_sex']= $value['sex'] == 1 ? '女' : '男';
        if(isset($value['type']))
            $value['c_type']= $value['type'] == 1 ? '专职' : '兼职';
        if(isset($value['setvip']))
            $value['c_setvip']= $value['setvip'] == 1 ? '是' : '否';
        if(isset($value['stop']))
            switch ($value['stop'])
            {
                case 1:
                    $value['c_stop']= '在职';
                    break;
                case 2:
                    $value['c_stop'] = '离职';
                    break;
                case 3:
                    $value['c_stop'] = '禁用';
                    break;
            }
        return($value);
    }

    protected function clockIndex($where,$date){
        $flights = UserBasic::with(['userClock' => function ($query) use ($date) {
                $query->whereBetween('date',$date);
            }])
            ->whereHas('userClock',function ($query) use ($date){
                $query->whereBetween('date',$date);
            })
            ->when($where,function ($query) use ($where) {
                return  $query->where($where);
            })
            ->get();
        return $flights;
    }

    /**人员基本信息**/
    protected function UserBasic($cndtn,$where,$limit,$sortField,$sort){
        $flighs = UserBasic::
        leftJoin('major_user','major_user.us_id', '=','users.id')
            ->leftJoin('fzjg', 'fzjg.id', '=', 'users.region')
            ->select($cndtn)
            ->where($where)
            ->orderBy($sortField,$sort)
            ->paginate($limit);
        return($flighs);
    }

    /**所有者**/
    protected function IndexBasic($cndtn,$change,$where){
        $flighs = UserBasic::select($cndtn)
            ->whereIn($change,$where)
            ->get();
        return($flighs);
    }

    /**部门人员**/
    protected function MentBasic($cndtn,$where){
        $flighs = UserBasic::select($cndtn)
            ->where($where)
            ->get();
        return($flighs);
    }

    /**人员搜索**/
    protected function SearchUser($cndtn,$where){
        $flighs = UserBasic::
        leftJoin('user_bumen','user_bumen.id', '=','users.bm_id')
            ->select($cndtn)
            ->where($where)
            ->get();
        return($flighs);
    }

    /**审核人员匹配(常规体系QES/EMS/OHSMS/EC)**/
    protected function RoutineGroup($where,$major='',$user='',$orWhere=''){
        $flighs = UserBasic::
        join('major_user','major_user.us_id', '=','users.id')
            ->join('major_userm', 'major_userm.m_id', '=', 'major_user.id')
            ->leftJoin('fzjg', 'fzjg.id', '=', 'users.region')
            ->select('users.id as uid','name','us_qlfts','rgt_type','type','group_abty','witn_abty','ealte_abty','regter_et','nmbe_et','major_code','turn_version','telephone','fz_at')
            ->where($where)
            ->when($major,function ($query) use ($major) {
                return  $query->whereIn('major_code',$major);
            })
            ->when($user,function ($query) use ($user) {
                return  $query->whereNotIn('users.id',$user);
            })
            ->where(function ($query) use ($orWhere) {
                if(!empty($orWhere)){
                    foreach($orWhere as $vel){
                        $query->orWhere($vel);
                    }
                }
            })
            ->get();
        return($flighs);
    }

    /**审核人员匹配(服务认证ECPSC/养老服务)**/
    protected function ServiceGroup($where,$user=''){
        $flighs = UserBasic::
        join('major_user','major_user.us_id', '=','users.id')
            //->leftJoin('fzjg', 'fzjg.id', '=', 'users.region')
            ->select('users.id as uid','major_user.id as mid','name','us_qlfts','type','group_abty','witn_abty','ealte_abty','regter_et','nmbe_et','turn_version','telephone')
            ->where($where)
            ->when($user,function ($query) use ($user) {
                return  $query->whereNotIn('users.id',$user);
            })
            ->get();
        return($flighs);
    }

    /**审核人员匹配(医疗器械YY)**/
    protected function MedicalGroup($where,$userId,$major){
        $flighs = UserBasic::
        join('major_user','major_user.us_id', '=','users.id')
            ->join('major_userm', 'major_userm.m_id', '=', 'major_user.id')
            ->leftJoin('fzjg', 'fzjg.id', '=', 'users.region')
            ->select('users.id as uid','major_code')
            ->where($where)
            ->whereIn('users.id',$userId)
            ->whereIn('major_code',$major)
            ->get();
        return($flighs);
    }

    /**评定人员匹配**/
    protected function ReviewGroup($cndtn,$where='',$whereIn=''){
        $flighs = UserBasic::
        join('major_user','major_user.us_id', '=','users.id')
            ->leftJoin('fzjg', 'fzjg.id', '=', 'users.region')
            ->select($cndtn)
            ->when($where,function ($query) use ($where) {
                return  $query->where($where);
            })
            ->when($whereIn,function ($query) use ($whereIn) {
                return  $query->whereIn('users.id',$whereIn);
            })
            ->get();
        return($flighs);
    }

    /**人员专业代码**/
    public function UserMajor(){
        return $this->hasManyThrough(
            'App\Models\Api\User\UserMajor',
            'App\Models\Api\User\UserType',
            'us_id',
            'm_id',
            'id',
            'id'
        );
    }

    /**人员资质代码**/
    protected function TypeMajor($cndtn,$where,$limit,$sortField,$sort){
        $flighs = UserBasic::
        join('major_user','major_user.us_id','=','users.id')
            ->join('major_userm', 'major_userm.m_id','=','major_user.id')
            ->leftJoin('fzjg', 'fzjg.id', '=', 'users.region')
            ->select($cndtn)
            ->where($where)
            ->orderBy($sortField,$sort)
            ->when(!$limit,function ($query) use ($limit) {
                return  $query->get();
            })
            ->when($limit,function ($query) use ($limit) {
                return  $query->paginate($limit);
            });
        return($flighs);
    }

    /**人员账户列表**/
    protected function UserIndex($cndtn,$where,$limit){
        $flighs = UserBasic::leftJoin('user_bumen','user_bumen.id', '=','users.bm_id')
            ->leftJoin('user_role','user_role.id', '=','users.zw_id')
            ->leftJoin('fzjg', 'fzjg.id', '=', 'users.region')
            ->select($cndtn)
            ->when($where,function ($query) use ($where) {
                return  $query->where($where);
            })
            //->orderBy('bm_id','asc')
            ->when(!$limit,function ($query) use ($limit) {
                return  $query->get();
            })
            ->when($limit,function ($query) use ($limit) {
                return  $query->paginate($limit);
            });
            //->orderBy('pid','asc');
        return($flighs);
    }
}
