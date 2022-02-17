<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\User\UserAccountController;
use App\Http\Controllers\Wechat\JsapiController;
use App\Http\Controllers\Api\LoginController;

class MessageSendController extends Controller
{
    /**短信发送**/
    public function sendMessage(Request $request){
        $url = "http://api.yx.ihuyi.com/webservice/sms.php?method=Submit";
        $mobile = $request->mobile;//手机号码，多个号码请用,隔开
        //模板消息
        $curlPost = "account=M05223022&password=c14d8fe42f2813c1920f19799c709502&mobile=".$mobile."&content=".$request->conts."退订回TD【亿信标准】&stime=".$request->stime."&format=json";
        $output = $this->curlPost($url,$curlPost);
        $output = json_decode($output);
        if($output->code == 2){
            return response()->json(['status'=>100,'msg'=>'发送成功']);
        }else{
            return response()->json(['status'=>101,'msg'=>$output->msg]);
        }
    }

    protected function curlPost($url,$curlPost){
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_NOBODY, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $curlPost);
        $return_str = curl_exec($curl);
        curl_close($curl);
        return $return_str;
    }

}
