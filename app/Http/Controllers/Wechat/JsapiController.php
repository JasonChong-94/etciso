<?php
/**
 * Created by PhpStorm.
 * User: Greatwall
 * Date: 2020/5/9
 * Time: 16:45
 */
namespace App\Http\Controllers\Wechat;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
date_default_timezone_set('Asia/Shanghai');
class JsapiController extends Controller
{
    const APPID='wxfdb2fdcf8e05a39c';
    const SECRET='eb6afcd56dead8e8e55decb2f337265b';
    const KEY='YPnuaFU77q7GgZaEME3p7lpb5mrI4eJduhQSeGxh7ho';
    const SIGN_TYPE='MD5';

    // 获取签名等信息，本方法内容可做微信分享接口用
    public function getInfo(Request $request) {
        $url=$request->input('url');
        if(!$url){
            return response()->json([
                'status'=>101,
                'msg'=>'缺少url',
            ]);
        }
        // 获取最新可用ticket
        $jsapiTicket = $this->getJsApiTicket ('jsapi_ticket');

        $timestamp = time ();
        $nonceStr  = $this->getNonceStr (16);

        // 这里参数的顺序要按照 key 值 ASCII 码升序排序
        $string = "jsapi_ticket=$jsapiTicket&noncestr=$nonceStr&timestamp=$timestamp&url=$url";

        $signature = sha1 ( $string );

        $signPackage = array (
            "appId"     => self::APPID,
            "nonceStr"  => $nonceStr,
            "timestamp" => $timestamp,
            "url"       => $url,
            "signature" => $signature,
            "rawString" => $string
        );
        //如果是接口，这里则是 echo json_encode($signPackage);
        return response()->json([
            'status'=>100,
            'msg'=>'成功',
            'data'=>$signPackage,
        ]);
    }
    //获取token
    public function get_token(){
        $token=$this->getJsApiTicket('access_token');
        return $token;

    }
    // 获取jsapi_ticket/access_token
    private function getJsApiTicket($name) {
        $data=DB::table('wx_info')->first();

        //不存在就获取
        if(!isset($data)){
            $res=$this->token_tiket();
            DB::table('wx_info')->insert([
            'access_token' => $res['access_token'],
            'jsapi_ticket' => $res['jsapi_ticket'],
            'expire_time' => $res['expire_time'],
            'add_time' => $res['add_time'],
        ]);
            $jsapi_ticket=$res[$name];
        }
        //如果获取到信息，并且信息没有过期，就使用该信息
        else if($data->expire_time > time()){
            $jsapi_ticket=$data->$name;
        }
        //如果信息过期了，就更新信息
        else{
            $res=$this->token_tiket();

            DB::table('wx_info')
                ->where('id', 1)
                ->update([
                'access_token' => $res['access_token'],
                'jsapi_ticket' => $res['jsapi_ticket'],
                'expire_time' => $res['expire_time'],
                'add_time' => $res['add_time'],
            ]);
            $jsapi_ticket=$res[$name];
        }

        return $jsapi_ticket;
    }
    private function token_tiket(){
        //获取access_token
        $getTokenUrl="https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".self::APPID."&secret=".self::SECRET;
        $tokenContent=file_get_contents($getTokenUrl);
        $tokenContentObj=json_decode($tokenContent);
        //获取jsapi_ticket
        $getTicketUrl="https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token=".$tokenContentObj->access_token."&type=jsapi";
        $ticketContent=file_get_contents($getTicketUrl);
        $ticketContentObj=json_decode($ticketContent);
        $data['access_token']=$tokenContentObj->access_token;
        $data['jsapi_ticket']=$ticketContentObj->ticket;
        $data['expire_time']=time() + 7000;
        $data['add_time']=date('Y-m-d H:i:s');
        return $data;
    }
    public function GetOpenid(Request $request)
    {
        $code=$request->input('code');
        //通过code获得openid
        if (!$code){
            return response()->json([
                'status'=>101,
                'msg'=>'缺少code',
            ]);
        } else {
            //获取code码，以获取openid
            $openid = $this->getOpenidFromMp($code);
            if($openid){
                $info=$this->userinfo($openid);
                return response()->json([
                    'status'=>100,
                    'msg'=>'成功',
                    'data'=>$openid,
                    'info'=>$info,
                ]);
            }else{
                return response()->json([
                    'status'=>101,
                    'msg'=>'无数据',
                ]);
            }
        }
    }
    //通过openid获取基本信息
    public function userinfo($openid){
        $url='https://api.weixin.qq.com/cgi-bin/user/info?access_token='.$this->get_token().'&openid='.$openid.'&lang=zh_CN';
        $res=file_get_contents($url);
        return json_decode($res,true);
    }
    public function ocr_license($license){
        $url='https://api.weixin.qq.com/cv/ocr/bizlicense';
        $data=[
            'img_url'=>urlencode($license),
            'access_token'=>$this->get_token()
            ];
        $res=$this->post($url,$data);
        if($res['errcode']!=0){
            return false;
        }
        return $res['reg_num'];
    }
    private function __CreateOauthUrlForOpenid($code)
    {
        $urlObj["appid"] = self::APPID;
        $urlObj["secret"] = self::SECRET;
        $urlObj["code"] = $code;
        $urlObj["grant_type"] = "authorization_code";
        $bizString = $this->ToUrlParams($urlObj);
        return "https://api.weixin.qq.com/sns/oauth2/access_token?".$bizString;
    }
    private function ToUrlParams($urlObj)
    {
        $buff = "";
        foreach ($urlObj as $k => $v)
        {
            if($k != "sign"){
                $buff .= $k . "=" . $v . "&";
            }
        }

        $buff = trim($buff, "&");
        return $buff;
    }
    public function GetOpenidFromMp($code)
    {
        $url = $this->__CreateOauthUrlForOpenid($code);

        //初始化curl
        $ch = curl_init();
        $curlVersion = curl_version();

        //设置超时
//        curl_setopt($ch, CURLOPT_TIMEOUT, $this->curl_timeout);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,FALSE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

        $proxyHost = "0.0.0.0";
        $proxyPort = 0;
//        $config->GetProxy($proxyHost, $proxyPort);
        if($proxyHost != "0.0.0.0" && $proxyPort != 0){
            curl_setopt($ch,CURLOPT_PROXY, $proxyHost);
            curl_setopt($ch,CURLOPT_PROXYPORT, $proxyPort);
        }
        //运行curl，结果以jason形式返回
        $res = curl_exec($ch);
        curl_close($ch);
        //取出openid
        $data = json_decode($res,true);
//        $this->data = $data;
        if(isset($data['errcode'])){
            $openid = $data;
        }else{
            $openid = $data['openid'];
        }
        return $openid;
    }
    /**
     *
     * 构造获取code的url连接
     * @param string $redirectUrl 微信服务器回跳的url，需要url编码
     *
     * @return 返回构造好的url
     */
    private function __CreateOauthUrlForCode($redirectUrl)
    {
        $urlObj["appid"] = self::APPID;
        $urlObj["redirect_uri"] = "$redirectUrl";
        $urlObj["response_type"] = "code";
        $urlObj["scope"] = "snsapi_base";
        $urlObj["state"] = "STATE"."#wechat_redirect";
        $bizString = $this->ToUrlParams($urlObj);
        return "https://open.weixin.qq.com/connect/oauth2/authorize?".$bizString;
    }
    /**
     *
     * 产生随机字符串，不长于32位
     * @param int $length
     * @return 产生的随机字符串
     */
    public static function getNonceStr($length = 32)
    {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $str ="";
        for ( $i = 0; $i < $length; $i++ )  {
            $str .= substr($chars, mt_rand(0, strlen($chars)-1), 1);
        }
        return $str;
    }
    /**
     * 生成签名
     * @param WxPayConfigInterface $config  配置对象
     * @param bool $needSignType  是否需要补signtype
     * @return 签名，本函数不覆盖sign成员变量，如要设置签名需要调用SetSign方法赋值
     */
    public function MakeSign($params)
    {
        ksort($params);
        $paramStr = '';
        foreach ($params as $k => $v) {
            $paramStr = $paramStr . $k . '=' . $v . '&';
        }
        $paramStr = $paramStr . 'key='.self::KEY;
        if(self::SIGN_TYPE == "MD5"){
            $sign = strtoupper(md5($paramStr));
        } else if(self::SIGN_TYPE == "HMAC-SHA256") {
            $sign = hash_hmac("sha256",$paramStr ,self::KEY);
        } else {
            throw new \Exception("签名类型不支持！");
//            throw new \Exception("签名类型不支持！");
        }
//        $sign = strtoupper(md5($paramStr));
        return $sign;
    }
    /**
     * 将数组转为XML
     * @param array $params 支付请求参数
     */
    public function array_to_xml($params)
    {
        if(!is_array($params)|| count($params) <= 0) {
            return false;
        }
        $xml = "<xml>";
        foreach ($params as $key=>$val) {
//            $xml.="<".$key.">".$val."</".$key.">";
            if (is_numeric($val)) {
                $xml.="<".$key.">".$val."</".$key.">";
            } else {
                $xml.="<".$key."><![CDATA[".$val."]]></".$key.">";
            }
        }
        $xml.="</xml>";
        return $xml;
    }
    public function xmltoarr($path)
    {//xml字符串转数组
        if(!$path){
            return false;
        }
        //将XML转为array
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        $data = json_decode(json_encode(simplexml_load_string($path, 'SimpleXMLElement', LIBXML_NOCDATA)), true);

        return $data;
    }


    public function post($url,$params){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        $return = curl_exec($ch);
        curl_close($ch);
        return json_decode($return,true);
    }
}