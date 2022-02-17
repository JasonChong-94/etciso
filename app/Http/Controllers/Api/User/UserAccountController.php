<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\Api\User\UserRole;
use App\Models\Api\User\UserBasic;
use App\Models\Api\User\UserMent;
use App\Models\Api\Inspect\InspectAuditTeam;
use App\Http\Controllers\Api\Email\EmailCodeController;
use Illuminate\Support\Facades\DB;

class UserAccountController extends Controller
{
    /**人员账户列表**/
    public function UserIndex(Request $request){
        $flighs = $this->UserResult('',$request->limit);
        return($flighs);
    }

    /**人员账户查询**/
    public function UserQuery(Request $request){
        $where = array();
        if($request->name){
            $where[] = ['name','like','%'.$request->name.'%'];
        }
        if($request->ment){
            $where[] = ['bm_id','=',$request->ment];
        }
        if($request->role){
            $where[] = ['zw_id','=',$request->role];
        }
        $flighs = $this->UserResult($where,$request->limit);
        return($flighs);
    }

    /**查询人员函数**/
    public function UserResult($where,$limit){
        $file = array(
            'users.id as id',
            'username',
            'openid',
            'name',
            'sex',
            'cft_numb',
            'telephone',
            'mailaccount',
            'mailname',
            'setvip',
            'e_mail',
            'bumen',
            'email_id',
            'zhiwu',
            'type',
            'time',
            'fz_at',
            'stop',
        );
        //DB::connection()->enableQueryLog();#开启执行日志
        $flighs = UserBasic::UserIndex($file,$where,$limit);
        //dump(DB::getQueryLog());die;
        if($flighs->isEmpty()){
            return response()->json(['status'=>101,'msg'=>'无数据']);
        }
        $flighs = $flighs->toArray();
        array_walk($flighs['data'], function (&$value,$key){
            $value = UserBasic::codeSwitch($value);
        });
        return response()->json(['status'=>100,'msg'=>'请求成功','data'=>$flighs]);
    }

    /**人员账户详情**/
    public function UserDetails(Request $request){
        $file = array(
            'users.id as id',
            'username',
            'name',
            'sex',
            'cft_numb',
            'telephone',
            'mailaccount',
            'mailname',
            'setvip',
            'e_mail',
            'bm_id',
            'zw_id',
            'type',
            'time',
            'region',
            'stop',
        );
        //DB::connection()->enableQueryLog();#开启执行日志
        $flighs = UserBasic::select($file)
            ->where([
                ['id',$request->id],
            ])
            ->get();
        //dump(DB::getQueryLog());die;
        if($flighs->isEmpty()){
            return response()->json(['status'=>101,'msg'=>'无数据']);
        }
        return response()->json(['status'=>100,'msg'=>'请求成功','data'=>$flighs->first()]);
    }

    /**人员账户添加**/
    public function UserAdd(Request $request){
        if(!$request->id){
            $flighs = UserBasic::where([
                ['cft_type',$request->cft_type],
                ['cft_numb',trim($request->cft_numb)],
            ])
                ->get();
            if($flighs->isEmpty()){
                DB::beginTransaction();
                try {
                    $flight = new UserBasic;
                    if($request->mailtype == 1){
                        $Email= new EmailCodeController;
                        $ment = $Email->addUser($request->mailaccount,$request->mailname,$request->mailid,$request->setvip);
                        if($ment->errcode != 0){
                            return response()->json(['status'=>101,'msg'=>$ment->errmsg]);
                        }
                        $flight->mailaccount= $request->mailaccount;
                        $flight->mailname   = $request->mailname;
                        $flight->setvip     = $request->setvip;
                    }
                    $flight->username= $request->account;
                    $flight->password= Hash::make($request->password);
                    $flight->name    = $request->name;
                    $flight->sex     = $request->sex;
                    $flight->cft_type= $request->cft_type;
                    $flight->cft_numb= trim($request->cft_numb);
                    $flight->telephone= $request->telephone;
                    $flight->e_mail   = $request->e_mail;
                    $flight->bm_id    = $request->ment;
                    $flight->zw_id    = $request->role;
                    $flight->type     = $request->type;
                    $flight->us_type  = 0;
                    $flight->region   = $request->region;
                    $flight->stop     = $request->stop;
                    if(!$flight->save()){
                        return response()->json(['status'=>101,'msg'=>'添加失败']);
                    }
                    DB::commit();
                }catch (\Exception $e){
                    DB::rollback();
                    return response()->json(['status'=>101,'msg'=>'添加失败']);
                }
                return response()->json(['status'=>100,'msg'=>'添加成功']);
            }
        }
        $request->id = $request->id?$request->id:$flighs->first()->id;
        //DB::connection()->enableQueryLog();#开启执行日志
        $flight = UserBasic::find($request->id);
        DB::beginTransaction();
        try {
            if ($request->mailtype == 1) {
                if ($flight->mailaccount == null) {
                    $Email = new EmailCodeController;
                    $ment = $Email->addUser($request->mailaccount, $request->mailname, $request->mailid, $request->setvip);
                    if ($ment->errcode != 0) {
                        return response()->json(['status' => 101, 'msg' => $ment->errmsg]);
                    }
                    $flight->mailaccount = $request->mailaccount;
                    $flight->mailname = $request->mailname;
                    $flight->setvip = $request->setvip;
                } else {
                    $Email = new EmailCodeController;
                    $enable = $request->stop == 1 ? $request->stop : 0;
                    $ment = $Email->editUser($flight->mailaccount, $request->mailname, $request->mailid, $enable, $request->setvip);
                    if ($ment->errcode != 0) {
                        return response()->json(['status' => 101, 'msg' => $ment->errmsg]);
                    }
                    $flight->mailname = $request->mailname;
                    $flight->setvip = $request->setvip;
                }
            }
            $flight->username = $request->account;
            $flight->name = $request->name;
            $flight->sex = $request->sex;
            $flight->cft_type = $request->cft_type;
            $flight->cft_numb = trim($request->cft_numb);
            $flight->telephone = $request->telephone;
            $flight->e_mail = $request->e_mail;
            $flight->bm_id = $request->ment;
            $flight->zw_id = $request->role;
            $flight->type = $request->type;
            $flight->region = $request->region;
            $flight->stop = $request->stop;
            if (!$flight->save()) {
                return response()->json(['status' => 101, 'msg' => '修改失败']);
            }
            DB::commit();
        }catch (\Exception $e){
            DB::rollback();
            return response()->json(['status'=>101,'msg'=>'修改失败']);
        }
        return response()->json(['status'=>100,'msg'=>'修改成功']);
    }

    /**人员账户查重**/
    public function UserRepeat(Request $request){
        if($request->has('username')){
            $flighs = UserBasic::where([
                ['username',$request->username],
                ['id','<>',$request->id],
            ])
                ->count();
            if($flighs >0){
                return response()->json(['status'=>101,'msg'=>'该账户号已存在']);
            }
        }
        $flight = UserBasic::where([
            ['name',$request->name],
            ['id','<>',$request->id],
        ])
            ->count();
        if($flight >0){
            return response()->json(['status'=>102,'msg'=>'该姓名已存在']);
        }
        return response()->json(['status'=>100,'msg'=>'未存在重复']);
    }

    /**账户密码修改**/
    public function UserWord(Request $request){
        if($request->has('oldword')){
            if(!Hash::check($request->oldword, Auth::guard('api')->user()->password)) {
                return response()->json(['status'=>101,'msg'=>'原密码错误']);
            }
        }
        $flight = UserBasic::find($request->id);
        $flight->password= Hash::make($request->password);
        if(!$flight->save()){
            return response()->json(['status'=>101,'msg'=>'修改失败']);
        }
        return response()->json(['status'=>100,'msg'=>'修改成功']);
    }

    /**人员部门更换**/
    public function UserMent(Request $request){
        $id = explode(";",$request->id);
        $flight = UserBasic::whereIn('id',$id)
            ->update(['bm_id' => $request->ment]);
        if($flight == 0){
            return response()->json(['status'=>101,'msg'=>'更换部门失败']);
        }
        return response()->json(['status'=>100,'msg'=>'更换部门成功']);
    }

    /**人员职务列表**/
    public function RoleIndex(Request $request){
        $flighs = UserRole::where('state',1)
            ->select('id','zhiwu')
            ->orderBy('sort','asc')
            ->get();
        if($flighs->isEmpty()){
            return response()->json(['status'=>101,'msg'=>'无数据']);
        }
        return response()->json(['status'=>100,'msg'=>'请求成功','data'=>$flighs]);
    }

    /**人员部门添加**/
    public function MentAdd(Request $request){
        if(!$request->id){
            DB::beginTransaction();
            try {
                $Email= new EmailCodeController;
                $ment = $Email->addDepartment($request->ment,$request->email_id);
                if($ment->errcode != 0){
                    return response()->json(['status'=>101,'msg'=>$ment->errmsg]);
                }
                $flight = new UserMent;
                $flight->bumen = $request->ment;
                $flight->pid   = $request->pid;
                $flight->email_id = $ment->id;
                if(!$flight->save()){
                    return response()->json(['status'=>101,'msg'=>'添加失败']);
                }
                DB::commit();
            }catch (\Exception $e){
                DB::rollback();
                return response()->json(['status'=>101,'msg'=>'添加失败']);
            }
            return response()->json(['status'=>100,'msg'=>'添加成功']);
        }
        //DB::connection()->enableQueryLog();#开启执行日志
        if($request->id == $request->pid){
            return response()->json(['status'=>101,'msg'=>'不能设置本部门为上级部门']);
        }
        DB::beginTransaction();
        try {
            $flight = UserMent::find($request->id);
            $Email= new EmailCodeController;
            $ment = $Email->editDepartment($flight->email_id,$request->ment,$request->email_id);
            if($ment->errcode != 0){
                return response()->json(['status'=>101,'msg'=>$ment->errmsg]);
            }
            $flight->bumen= $request->ment;
            $flight->pid  = $request->pid;
            if(!$flight->save()){
                return response()->json(['status'=>101,'msg'=>'修改失败']);
            }
            DB::commit();
        }catch (\Exception $e){
            DB::rollback();
            return response()->json(['status'=>101,'msg'=>'修改失败']);
        }
        return response()->json(['status'=>100,'msg'=>'修改成功']);
    }

    /**人员部门删除**/
    public function MentDel(Request $request){
        $flighs = UserBasic::where([
            ['bm_id',$request->id],
        ])
            ->count();
        if($flighs != 0){
            return response()->json(['status'=>101,'msg'=>'该部门还有人员，不能进行删除']);
        }
        DB::beginTransaction();
        try {
            $flight = UserMent::find($request->id);
            $Email= new EmailCodeController;
            $ment = $Email->deleteDepartment($flight->email_id);
            if($ment->errcode != 0){
                return response()->json(['status'=>101,'msg'=>$ment->errmsg]);
            }
            if(!$flight->delete()){
                return response()->json(['status'=>101,'msg'=>'删除失败']);
            }
            DB::commit();
        }catch (\Exception $e){
            DB::rollback();
            return response()->json(['status'=>101,'msg'=>'修改失败']);
        }
        return response()->json(['status'=>100,'msg'=>'删除成功']);
    }

    /**人员职务权限**/
    public function UserRole(Request $request){
        $file = array(
            'user_role.id as id',
            'name',
            'zhiwu',
        );
        $flighs = UserRole::UserRole($file,'');
        if($flighs->isEmpty()){
            return response()->json(['status'=>101,'msg'=>'无数据']);
        }
        $flighs = $flighs->toArray();
        array_walk($flighs, function ($value,$key) use (&$flight){
            if($value['name']){
                if(!isset($flight[$value['id']])){
                    $flight[$value['id']] = $value;
                    $flight[$value['id']]['number'] = 1;
                }else{
                    $flight[$value['id']]['name'] .= ','.$value['name'];
                    $flight[$value['id']]['number']= $flight[$value['id']]['number']+1;
                }
            }else{
                $flight[] =  $value;
            }
        });
        return response()->json(['status'=>100,'msg'=>'请求成功','data'=>array_values($flight)]);
    }

    /**职务权限添加**/
    public function RoleAdd(Request $request){
        if(!$request->id){
            $flight = new UserRole;
        }else{
            $flight = UserRole::find($request->id);
        }
        $flight->zhiwu = $request->zhiwu;
        $flight->lever = $request->lever;
        if($flight->save()){
            return response()->json(['status'=>100,'msg'=>'添加成功']);
        }
        return response()->json(['status'=>101,'msg'=>'添加失败']);
    }

    /**职务权限详情**/
    public function RoleDetails(Request $request){
        $flight = UserRole::find($request->id);
        if(!$flight){
            return response()->json(['status'=>101,'msg'=>'无数据']);
        }
        $flight->lever = json_decode($flight->lever);
        return response()->json(['status'=>100,'msg'=>'请求成功','data'=>$flight]);
    }

    /**权限人员添加**/
    public function RoleUser(Request $request){
        DB::beginTransaction();
        try {
            UserBasic::where('zw_id',$request->role)
                ->update(['zw_id' => 0]);
            $id = explode(";",$request->id);
            $flight = UserBasic::whereIn('id',$id)
                ->update(['zw_id' => $request->role]);
            if($flight == 0){
                return response()->json(['status'=>101,'msg'=>'职务新增人员失败']);
            }
            DB::commit();
        }catch (\Exception $e){
            DB::rollback();
            return response()->json(['status'=>101,'msg'=>'职务新增人员失败']);
        }
        return response()->json(['status'=>100,'msg'=>'职务新增人员成功']);
    }

    /**职务权限删除**/
    public function RoleDel(Request $request){
        $flighs = UserBasic::where([
            ['zw_id',$request->role],
        ])
            ->count();
        if($flighs != 0){
            return response()->json(['status'=>101,'msg'=>'该职务还有人员，不能进行删除']);
        }
        $flight = UserRole::find($request->role);
        if($flight->delete()){
            return response()->json(['status'=>100,'msg'=>'删除成功']);
        }
        return response()->json(['status'=>101,'msg'=>'删除失败']);
    }

    /**微信绑定**/
    public function userWeixin($userId,$openid){
        $flight = UserBasic::find($userId);
        if($flight->openid != null){
            header("Location:".'http://h5.etciso.com/scan/three.html');die;
        }
        $flight->openid= $openid;
        if($flight->save()){
            header("Location:".'http://h5.etciso.com/scan/one.html');die;
        }
        header("Location:".'http://h5.etciso.com/scan/two.html');die;
    }

    /**人员考勤**/
    public function userClock(Request $request){
        //DB::connection()->enableQueryLog();#开启执行日志
        $time = $request->date?$request->date:date('Y-m');
        $where = array();
        if($request->name){
            $where[] = ['name','=',$request->name];
        }
        if($request->code){
            $where[] = ['na_code','=',$request->code];
        }
        if($request->region){
            $where[] = ['region','=',$request->region];
        }
        $date = $this->planUser($time,$where);
        if($date == false){
            return response()->json(['status'=>101,'msg'=>'未安排审核计划']);
        }
        $clock = $this->clockUser($time,$where);
        if($clock == false){
            return response()->json(['status'=>100,'msg'=>'请求成功','data'=>array_values($date)]);
        }
        array_walk($date, function (&$value,$key,$clock){
            if(!empty($clock[$value['openid']])){
                array_walk($value['data'], function (&$value,$key,$date){
                    if(!empty($date[$key])){
                        $value = array_replace_recursive($value,$date[$key]);
                    }
                },$clock[$value['openid']]['date']);
            }
        },$clock);
        return response()->json(['status'=>100,'msg'=>'请求成功','data'=>array_values($date)]);
    }

    protected function planUser($time,$where){
        //DB::connection()->enableQueryLog();#开启执行日志
        $time = [$time.'-01',$time.'-31 24:00:00'];
        $flighs = UserBasic::join('qyht_htrzu', 'qyht_htrzu.us_id','=', 'users.id')
            ->join('qyht_htrza', 'qyht_htrzu.ap_id','=', 'qyht_htrza.id')
            ->join('qyht_htrz', 'qyht_htrza.xm_id','=', 'qyht_htrz.id')
            ->join('qyht', 'qyht_htrz.ht_id','=', 'qyht.id')
            ->where(function ($query) use ($time) {
                if(!empty($time)){
                    $query->WhereBetween('start_time',$time)->orWhereBetween('end_time',$time);
                }
            })
            ->where([
                ['start_time','<>',''],
                ['start_time','<>',null],
                ['end_time','<>',''],
                ['end_time','<>',null],
                ['type_qlfts','<>','04'],
            ])
            ->when($where,function ($query) use ($where) {
                return  $query->where($where);
            })
            ->select('kh_id','one_mode','audit_phase','start_time','end_time','users.id as userid','name','openid')
            ->orderBy('users.id','asc')
            ->orderBy('start_time','asc')
            ->get();
        //dump(DB::getQueryLog());
        if($flighs->isEmpty()){
            return (false);
        }
        $flighs = $flighs->toArray();
        array_walk($flighs, function (&$value,$key)  use (&$flight){
            if($value['audit_phase'] == '0101' || $value['audit_phase'] == '0201'){
                if($value['one_mode'] == '02'){
                    $flight[] = array(
                        'name'  => $value['name'],
                        'openid'=> $value['openid'],
                        'userid'=> $value['userid'],
                        'start_time' => $value['start_time'],
                        'end_time' => $value['end_time'],
                        'kh_id' => $value['kh_id'],
                    );
                }
            }else{
                $flight[] = array(
                    'name'  => $value['name'],
                    'openid'=> $value['openid'],
                    'userid'=> $value['userid'],
                    'start_time' => $value['start_time'],
                    'end_time' => $value['end_time'],
                    'kh_id' => $value['kh_id'],
                );
            }
        });
        $flight = array_unique($flight,SORT_REGULAR);
        array_walk($flight, function (&$value,$key)  use (&$date){
            if(!isset($date[$value['userid']])){
                $date[$value['userid']]['name'] = $value['name'];
                $date[$value['userid']]['openid'] = $value['openid'];
                $stimestamp = strtotime($value['start_time']);
                $etimestamp = strtotime($value['end_time']);
                $days = intval(($etimestamp-$stimestamp)/86400+1);
                // 保存每天日期
                for($i=0;$i<$days;$i++){
                    if($stimestamp+(86400*($i))<time()){
                        $date[$value['userid']]['data'][date('d', $stimestamp+(86400*($i)))][$value['kh_id']] = array(
                            'date'=>'缺卡',
                            'state'=>'2'
                        );
                    }else{
                        $date[$value['userid']]['data'][date('d', $stimestamp+(86400*($i)))][$value['kh_id']] = array(
                            'date'=>'',
                            'state'=>''
                        );
                    };
                }
            }else{
                $stimestamp = strtotime($value['start_time']);
                $etimestamp = strtotime($value['end_time']);
                $days = intval(($etimestamp-$stimestamp)/86400+1);
                // 保存每天日期
                for($i=0;$i<$days;$i++){
                    if($stimestamp+(86400*($i))<time()){
                        $date[$value['userid']]['data'][date('d', $stimestamp+(86400*($i)))][$value['kh_id']] = array(
                            'date'=>'缺卡',
                            'state'=>'2'
                        );
                    }else{
                        $date[$value['userid']]['data'][date('d', $stimestamp+(86400*($i)))][$value['kh_id']] = array(
                            'date'=>'',
                            'state'=>''
                        );
                    };
                }
            }
        });
        return($date);
    }

    protected function clockUser($time,$where){
        $date = [$time.'-01',$time.'-31'];
        //DB::connection()->enableQueryLog();#开启执行日志
        $flighs =  UserBasic::clockIndex($where,$date);
        //dump(DB::getQueryLog());die;
        if($flighs->isEmpty()){
            return (false);
        }
        $flighs = $flighs->toArray();
        array_walk($flighs, function (&$value,$key)  use (&$clock){
            $clock[$value['openid']]['name']  = $value['name'];
            $clock[$value['openid']]['openid']= $value['openid'];
            array_walk($value['user_clock'], function ($value,$key) use (&$clock){
                if(!isset($clock[$value['openid']]['date'][date('d',strtotime($value['date']))][$value['kh_id']])){
                    $clock[$value['openid']]['date'][date('d',strtotime($value['date']))][$value['kh_id']]['date'] =  $value['time'];
                    $clock[$value['openid']]['date'][date('d',strtotime($value['date']))][$value['kh_id']]['state'] =  $value['state'];
                }else{
                    $clock[$value['openid']]['date'][date('d',strtotime($value['date']))][$value['kh_id']]['date'] .=  '-'.$value['time'];
                    if($clock[$value['openid']]['date'][date('d',strtotime($value['date']))][$value['kh_id']]['state'] != $value['state']){
                        $clock[$value['openid']]['date'][date('d',strtotime($value['date']))][$value['kh_id']]['state'] =  0;
                    }
                }
            });
        });
        return($clock);
    }

}
