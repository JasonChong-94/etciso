<?php
/**
 * Created by PhpStorm.
 * User: Greatwall
 * Date: 2021/3/18
 * Time: 14:53
 */
namespace App\Http\Controllers\Wechat\Home;

use App\Http\Controllers\Controller;
use App\Models\Wechat\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
class UserController  extends Controller
{

    //短信发送
    public function send(Request $request){
        $phone=$request->input('phone');
        $code=rand(1000,9999);
        $content='您此次的验证码为：'.$code.'，请勿泄露给他人!';
        $res = User::send($phone,$content);

        if($res==1){
            Cache::put($phone, $code, 1);
            return response()->json(['status'=>100,'msg'=>'发送成功']);

        }else{
            return response()->json(['status'=>101,'msg'=>'发送失败']);
        }
    }
    public function binding(Request $request){
        $phone=$request->input('phone');
        $code=$request->input('code');
        $openid=$request->input('openid');
        if(!$phone){
            return response()->json([
                'status'=>101,
                'msg'=>'缺少电话'
            ]);
        }
        if(!$code){
            return response()->json([
                'status'=>101,
                'msg'=>'缺少验证码'
            ]);
        }
        if(!$openid){
            return response()->json([
                'status'=>101,
                'msg'=>'缺少openid'
            ]);
        }
        $res=User::where('username',$phone)->where('stop',1)->first();
        if(!$res){
            return response()->json(['status'=>101,'msg'=>'该人员不存在或已离职']);
        }
        if($res->openid){
            return response()->json(['status'=>101,'msg'=>'该号码已绑定微信']);
        }
        $cache  = Cache::has($phone);
        if($cache == false) {
            return response()->json(['status'=>101,'msg'=>'验证码已过期']);
        }
        $res->openid=$openid;
        if($res->save()){
            return response()->json(['status'=>100,'msg'=>'绑定成功']);
        }else{
            return response()->json(['status'=>101,'msg'=>'绑定失败']);
        }
    }


}