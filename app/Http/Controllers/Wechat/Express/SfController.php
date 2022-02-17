<?php
/**
 * Created by PhpStorm.
 * User: Greatwall
 * Date: 2021/9/3
 * Time: 15:34
 */
namespace App\Http\Controllers\Wechat\Express;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
class SfController  extends Controller
{

    public function search(Request $request){
        $order=$request->input('order');
        $type=$request->input('type',1);
        if(!$order || !$type){
            return response()->json(['status'=>101,'msg'=>'缺少参数']);
        }
        switch ($type)
        {
            case 1:
                $res=$this->route($order);
                break;
            case 2:
                $res=$this->route($order);
                break;
            default:
                $res=$this->route($order);
        }
        if($res['code']==101){
            return response()->json(['status'=>$res['code'],'msg'=>$res['msg']]);
        }
        //acceptTime  排序
        array_multisort(array_column($res['data']['routes'],'acceptTime'),SORT_DESC ,$res['data']['routes']);
        return response()->json(['status'=>$res['code'],'msg'=>$res['msg'],'data'=>$res['data']]);
    }
    /***
     顺丰丰桥
     **/
    const   Checkword="TuBWSqHKdb2whwjNYCT1HvGS61veR9wk";//此处替换为您在丰桥平台获取的校验码
    const   PartnerID = "JJXXKJ";//此处替换为您在丰桥平台获取的顾客编码
    //路由查询
    public function route($order){
        $requestID = $this->create_uuid();

        //获取时间戳
        $timestamp = time();
        $msgData = '{"language":"0","trackingType":"1","trackingNumber":["'.$order.'"],"methodType": "1"}';//读取文件内容
        //通过MD5和BASE64生成数字签名
        $msgDigest = base64_encode(md5((urlencode($msgData .$timestamp. self::Checkword)), TRUE));
        //发送参数
        $post_data = array(
            'partnerID' => self::PartnerID,
            'requestID' => $requestID,
            'serviceCode' => 'EXP_RECE_SEARCH_ROUTES',
            'timestamp' => $timestamp,
            'msgDigest' => $msgDigest,
            'msgData' => $msgData
        );

        //沙箱环境的地址
        $CALL_URL_BOX = "http://sfapi-sbox.sf-express.com/std/service";
        //生产环境的地址
        $CALL_URL_PROD = "https://sfapi.sf-express.com/std/service";

        $resultCont = $this->send_post($CALL_URL_PROD, $post_data); //沙盒环境
        $res=json_decode($resultCont);
        if($res->apiResultCode!='A1000'){
            $msg['code']=101;
            $msg['msg']=$res->apiErrorMsg;
            return $msg;
        }
        $data=json_decode($res->apiResultData,true);
        if($data['success']==false){
            $msg['code']=101;
            $msg['msg']='查询失败';
            return $msg;
        }
        $msg['code']=100;
        $msg['msg']='请求成功';
        $msg['data']=$data['msgData']['routeResps'][0];
        return $msg;
    }
    //获取UUID
    public function create_uuid() {
        $chars = md5(uniqid(mt_rand(), true));
        $uuid = substr ( $chars, 0, 8 ) . '-'
            . substr ( $chars, 8, 4 ) . '-'
            . substr ( $chars, 12, 4 ) . '-'
            . substr ( $chars, 16, 4 ) . '-'
            . substr ( $chars, 20, 12 );
        return $uuid ;
    }
    public function send_post($url, $post_data) {

        $postdata = http_build_query($post_data);
        $options = array(
            'http' => array(
                'method' => 'POST',
                'header' => 'Content-type:application/x-www-form-urlencoded;charset=utf-8',
                'content' => $postdata,
                'timeout' => 15 * 60 // 超时时间（单位:s）
            )
        );
        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);

        return $result;
    }

}