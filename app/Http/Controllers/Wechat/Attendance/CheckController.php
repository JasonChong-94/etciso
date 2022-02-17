<?php
/**
 * Created by PhpStorm.
 * User: Greatwall
 * Date: 2021/1/21
 * Time: 15:59
 */
namespace App\Http\Controllers\Wechat\Attendance;

use App\Http\Controllers\Controller;
use App\Models\Wechat\Check;
use App\Models\Wechat\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
date_default_timezone_set('Asia/Shanghai');
class CheckController  extends Controller
{
    //获取打卡基本信息
    public function getinfo(Request $request){
        $openid=$request->input('openid');
        if(!$openid){
            return response()->json([
                'status'=>101,
                'msg'=>'缺少openid'
            ]);
        }
        //通过openid获取userid  oSI2ws6QZ2ZuuGGLGcKRK9E0-n7U
        //"lng": 103.976768,
        //"lat": 30.321341
        $where[]=['users.openid','=',$openid];
        $field=[
            'users.name',
            'qyht_htrza.start_time',
            'qyht_htrza.end_time',
            'qyht_htrza.id',//阶段id
            'qyht_htrza.audit_phase',//审核阶段
            'qyht_htrz.one_mode',//一阶段审核方式 01非现场 02现场
            'khxx.bgdz',
            'khxx.qymc',
            'khxx.id as kh_id',
//            'qyht_htrza.xm_id',
//            'qyht_htrza.audit_phase',001410
        ];
        $res = User::UserBasic($where,$field,date("Y-m-d",time()));

        if($res->first()){
            $data=array_column($res->toArray(),null,'kh_id');
            $mun=0;
            foreach ($data as $key=>&$v){
                if(($v['audit_phase']=='0101' && $v['one_mode']=='01') || ($v['audit_phase']=='0201' && $v['one_mode']=='01')){
                    unset($data[$key]);continue;
                }
                $v['value']=$mun++;
                $token_url = 'https://apis.map.qq.com/ws/geocoder/v1/?address='.$v['bgdz'].'&key=I5SBZ-TRK6P-JRQDU-LUJTM-BD6UJ-QCBLV';
                $token = json_decode(file_get_contents($token_url),true);
                if($token['status']!==0){
                    continue;
                }else{
                    $v['lng']=$token['result']['location']['lng'];//经度
                    $v['lat']=$token['result']['location']['lat'];//纬度
                }
            }
            $data=array_values($data);
            if($data){
                return response()->json(['status'=>100,'msg'=>'请求成功','data'=>$data]);
            }
            return response()->json(['status'=>101,'msg'=>'无审核计划']);
        }
        return response()->json(['status'=>101,'msg'=>'无审核计划']);
    }
    //通过openid获取用户信息
    public function user_info(Request $request){
        $openid=$request->input('openid');
        if(!$openid){
            return response()->json([
                'status'=>101,
                'msg'=>'缺少openid'
            ]);
        }
        $res = User::info($openid);

        if($res){
            return response()->json(['status'=>100,'msg'=>'请求成功','data'=>$res]);
        }else{
            return response()->json(['status'=>101,'msg'=>'无权限进入']);
        }
    }
    //获取打卡信息9334
    public function check_info(Request $request){
        $data=$request->has(['openid']);
        if(!$data){
            return response()->json([
                'status'=>101,
                'msg'=>'缺少参数'
            ]);
        }
        $where[]=['openid','=',$request->openid];
        $where[]=['kh_id','=',$request->kh_id];
        $field=[
            'id',
            'time',
            'date',
            'openid',
            'state',
            'ap_id',
            'address',
        ];
        $res = Check::check($where,$field,date("Y-m-d",time()));
        if($res->first()){
            return response()->json(['status'=>100,'msg'=>'请求成功','data'=>$res]);
        }else{
            return response()->json(['status'=>101,'msg'=>'无打卡记录']);
        }
    }
    public function save_info(Request $request){
        $messages = [
            'required' => '提交失败',
            'integer' => '必须是int类型',
            'image' => '请上传图片',
        ];
        $validator = Validator::make($request->all(), [
            'openid' => 'required',
            'ap_id' => 'integer',
            'kh_id' => 'required|integer',
            'address' => 'required',
            'photo' => 'required|image',
            'lat_lng' => 'required',
        ],$messages);

        if ($validator->fails()) {
            return response()->json(['msg' => $validator->errors()->first(),'status'=>101]);
        }
        $path = $request->file('photo')->store('attendance','wechat');
        if($request->id){
            $check = Check::find($request->id);
        }else{
            $check=new Check;
        }
        $check->openid=$request->openid;
        $check->ap_id=$request->ap_id;
        $check->kh_id=$request->kh_id;
        $check->date = date("Y-m-d",time());
        $check->time = date("H:i",time());
        $check->address=$request->address;
        $check->photo=$path;
        $check->state=$request->state;// 0正常  1位置异常 2时间异常 3位置时间异常
        $check->lat_lng=$request->lat_lng;
        if($check->save()){
            return response()->json(['status'=>100,'msg'=>'成功']);
        }else{
            return response()->json(['status'=>101,'msg'=>'失败']);
        }
    }
    public function lng_lat(Request $request){
        $addr=$request->input('address');
        if(!$addr){
            return response()->json(['status'=>101,'msg'=>'缺少地址']);
        }
        $token_url = 'https://apis.map.qq.com/ws/geocoder/v1/?address='.$addr.'&key=I5SBZ-TRK6P-JRQDU-LUJTM-BD6UJ-QCBLV';
        $token = json_decode(file_get_contents($token_url),true);
        if($token['status']!==0){
            return response()->json(['status'=>101,'msg'=>'获取失败']);
        }else{
            $data['lng']=$token['result']['location']['lng'];//经度
            $data['lat']=$token['result']['location']['lat'];//纬度
            return response()->json(['status'=>100,'msg'=>'获取成功','data'=>$data]);
        }
    }

}