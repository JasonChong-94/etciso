<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use App\Models\Api\User\UserBasic;

class LoginController extends Controller
{
    /**登录验证**/
    public function UserCode(Request $request){
/*        $key = 'dinghot7iri1bifb6vid';
        $secret = 'sPBIeo9DMO-CuqBNf5LnepRvnkT-M6CP8tbJZJtIgdxwzY8wUPK0EI7FO41srXOW';
        $token_url = 'https://oapi.dingtalk.com/gettoken?appkey='.$key.'&appsecret='.$secret;
        $token = json_decode(file_get_contents($token_url));
        if($token->errcode !== 0){
            return response()->json(['status'=>101, 'msg'=>'该公司不存在']);
        }
        $user_url = 'https://oapi.dingtalk.com/user/getuserinfo?access_token='.$token->access_token.'&code='.$request->code;
        $ding_user= json_decode(file_get_contents($user_url));*/
        $user_id   = 123456;//$ding_user->userid;
        if (Auth::attempt(['userid' =>$user_id,'password' =>123456,'stop'=>1])) {
            $token = 123456780;//str_random(12).$user_id;
            $user  = Auth::guard('api')->user();
            $user->api_token  = $token;
            $user->token_time = time();
            if($user->save()){
                return response()->json(['status'=>100, 'msg'=>'数据请求成功', 'token'=>$token,'user'=>$user]);// 认证通过...
            }
        }else{
            return response()->json(['status'=>101, 'msg'=>'该用户不存在/账号已注销']);
        }
    }

    /**登录验证**/
    public function SignIn(Request $request){
        if (Auth::attempt(['username' =>$request->account,'password' =>$request->password,'stop'=>1])) {
            $user  = Auth::user();
            $token = str_random(32).$user->id;
            $user->api_token  = $token;
            $user->time        = $user->token_time;
            $user->token_time = time();
            if($user->save()){
                return response()->json(['status'=>100, 'msg'=>'数据请求成功', 'token'=>$token,'user'=>$user]);// 认证通过...
            }
        }else{
            return response()->json(['status'=>101, 'msg'=>'该用户不存在/账号已注销']);
        }
    }

    /**登录验证**/
    public function WechatSign(Request $request){
        if (Cache::has($request->state)) {
            $value = Cache::pull($request->state);
            return response()->json(['status'=>100, 'msg'=>'数据请求成功', 'token'=>$value['token'],'user'=>$value['user']]);// 认证通过...
        }
    }
}
