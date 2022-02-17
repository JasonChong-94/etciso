<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class WeixinCheckController extends Controller
{
    /**登录验证**/
    public function checkToken(Request $request){
        $signature = $request->input('msg_signature');

        $timestamp = $request->input('timestamp');

        $nonce = $request->input('nonce');

        $echoStr = $request->input('echostr');
        $token = 'yixinbiaozhun202101201712etciso';
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode( $tmpArr );
        $tmpStr = sha1( $tmpStr );
        if($tmpStr == $signature){
            return ($echoStr);// 认证通过...
        }else{
            return (false);
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
}
