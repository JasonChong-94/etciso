<?php

namespace App\Http\Controllers\Api\WeChat;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Api\User\UserBasic;
use App\Models\Api\User\UserType;
use App\Models\Api\Inspect\InspectPlan;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Wechat\JsapiController;
use App\Http\Controllers\Api\LoginController;

class WeixinCodeController extends Controller
{
    /**获取微信Code**/
    public function requestCode(Request $request){
        $appid = 'wxfdb2fdcf8e05a39c';
        $state = $request->state;
        $url ='https://open.weixin.qq.com/connect/oauth2/authorize?appid='.$appid.'&redirect_uri=http%3A%2F%2Foc.etciso.com%2Fapi%2Fwechat%2Fget%2Fcode&response_type=code&scope=snsapi_userinfo&state='.$state.'#wechat_redirect';
        return($url);
    }

    /**请求微信access_token**/
    public function getCode(Request $request){
        if (!$request->has('code')) {
            return response()->json(['status'=>101,'msg'=>'授权失败']);
        }
        $code  = $request->code;
        $appid = 'wxfdb2fdcf8e05a39c';
        $appsecret = 'eb6afcd56dead8e8e55decb2f337265b';
        $token_url = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid='.$appid.'&secret='.$appsecret.'&code='.$code.'&grant_type=authorization_code';
        $token = json_decode(file_get_contents($token_url));
        $user = UserBasic::where([
            ['openid',$token->openid],
            ['stop',1]
        ])
            ->get();
        if($user->isEmpty()){
            header("Location:".'http://h5.etciso.com/scan/three.html');die;
        }
        $user = UserBasic::find($user->first()->id);
        $token = str_random(32).$user->id;
        $user->api_token  = $token;
        $user->time        = $user->token_time;
        $user->token_time = time();
        if($user->save()){
            $data = [
               'token' => $token,
               'user'  => $user,
            ];
            cache([$request->state => $data], 1);
            header("Location:".'http://h5.etciso.com/scan/one.html');die;
        }
        header("Location:".'http://h5.etciso.com/scan/two.html');die;
    }

    /**获取公众号access_token**/
    public function accessToken(){
        $appid = 'wxfdb2fdcf8e05a39c';
        $appsecret = 'eb6afcd56dead8e8e55decb2f337265b';
        $token_url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.$appid.'&secret='.$appsecret.'';
        $access_token = json_decode(file_get_contents($token_url));
        return($access_token->access_token);
    }

    /**再注册、年度确认消息推送**/
    public function sendTemplate(){
        //$start_time = time();//或者Y-m-d H:i:s
        $flight = UserType::join('users', 'major_user.us_id','=', 'users.id')
            ->select('rgt_numb','regter_st','regter_et','name','openid')
            ->where([
                ['stop',1],
                ['openid','<>',null],
            ])
            ->where(function ($query){
                $start_time = strtotime("-3 month",time());
                $three_start= date("Y-m-d",strtotime("-3 year",$start_time));
                $three_end  = date("Y-m-d",strtotime("-3 year",time()));
                $two_start= date("Y-m-d",strtotime("-2 year",$start_time));
                $two_end  = date("Y-m-d",strtotime("-2 year",time()));
                $one_start= date("Y-m-d",strtotime("-1 year",$start_time));
                $one_end  = date("Y-m-d",strtotime("-1 year",time()));
                $query->WhereBetween('regter_st',[$three_start,$three_end])->orWhereBetween('regter_st',[$two_start,$two_end])->orWhereBetween('regter_st',[$one_start,$one_end]);

            })
            ->get();
        if($flight->isEmpty()){
            return response()->json(['status'=>101,'msg'=>'无参数']);
        }
        $flighs = $flight->toArray();
        $flighs = array_unique($flighs,SORT_REGULAR);
        $access_token = new JsapiController;
        $access_token = $access_token->get_token();
        $res = array();
        foreach ($flighs as $data){
            $end_time = strtotime("-3 month",time());
            $end_date = date("Y-m-d",$end_time);
            $strtotime= strtotime($data['regter_st']);
            if(date("d",$strtotime)>date("t")){
                if(date("t") == date("d")) {
                    if ($data['regter_et'] > $end_date && $data['regter_et'] < date("Y-m-d")) {
                        $data['type'] = '再注册';
                    } else {
                        $data['type'] = '年度确认';
                    }
                    $json_template = $this->json_tempalte($data);
                    $url = "https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=" . $access_token;
                    $call_back = $this->curl_post($url, urldecode($json_template));
                    json_decode($call_back, true);
                    /*                  if ($call_back['errcode'] != 0) {
                                          $res[] = $call_back;
                                      }*/
                }
            }else{
                if(date("d",$strtotime) == date("d")) {
                    if ($data['regter_et'] > $end_date && $data['regter_et'] < date("Y-m-d")) {
                        $data['type'] = '再注册';
                    } else {
                        $data['type'] = '年度确认';
                    }
                    $json_template = $this->json_tempalte($data);
                    $url = "https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=" . $access_token;
                    $call_back = $this->curl_post($url, urldecode($json_template));
                    json_decode($call_back, true);
  /*                  if ($call_back['errcode'] != 0) {
                        $res[] = $call_back;
                    }*/
                }
            }
        }
/*        if(empty($res)){
            dump(111111);die;
        }
        dump(2222);die;*/
    }

    /**审核任务消息推送**/
    public function taskTemplate(){
        $start_time = strtotime("1 day",time());
        $start_date = date("Y-m-d",$start_time);
        $flighs = InspectPlan::with([
            'planUser' => function ($query){
                $query->select('ap_id','us_id');
            },
            'planUser.userBasic' => function ($query){
                $query->select('id','openid','name','telephone');
            },
            'examineSystem' => function ($query){
                $query->select('id','ht_id');
            },
            'examineSystem.marketContract'=> function ($query){
                $query->select('id','kh_id');
            },
            'examineSystem.marketContract.marketCustomer' => function ($query){
                $query->select('id','qymc','bgdz');
            },
            'examineSystem.marketContract.marketCustomer.marketContacts' => function ($query){
                $query->where('state', '=',1);
                $query->select('kh_id','name','phone');
            }])
        ->whereBetween('start_time', [$start_date." 00:00:00",$start_date." 24:00:00"])
        ->select('id','xm_id','start_time','end_time')
        ->get();
        //dump(DB::getQueryLog());
        if($flighs->isEmpty()){
            return response()->json(['status'=>101,'msg'=>'无数据']);
        }
        $flighs = $flighs->toArray();
        array_walk($flighs, function ($value,$key) use (&$flight){
            $data['id']   = $value['examine_system']['market_contract']['kh_id'];
            $data['qymc'] = $value['examine_system']['market_contract']['market_customer']['qymc'];
            if(!empty($value['examine_system']['market_contract']['market_customer']['market_contacts'])){
                $data['contact'] = $value['examine_system']['market_contract']['market_customer']['market_contacts'][0]['name'].'('.$value['examine_system']['market_contract']['market_customer']['market_contacts'][0]['phone'].')';
            }else{
                $data['contact'] = '';
            }
            $data['time'] = $value['start_time'].'至'.$value['end_time'];
            $data['bgdz'] = $value['examine_system']['market_contract']['market_customer']['bgdz'];
            array_walk($value['plan_user'], function ($value,$key) use (&$data){
                if(empty($data['user'])){
                    $data['user'] = $value['user_basic']['name'].'('.$value['user_basic']['telephone'].')';
                }else{
                    $data['user'] .= ' '.$value['user_basic']['name'].'('.$value['user_basic']['telephone'].')';
                }
            });
            array_walk($value['plan_user'], function ($value,$key,$data) use (&$flight){
                if($value['user_basic']['openid'] != null){
                    $data['openid'] = $value['user_basic']['openid'];
                    $data['name'] = $value['user_basic']['name'];
                    $flight[] = $data;
                }
            },$data);
        });
        $flighs = array_unique($flight,SORT_REGULAR);
        $access_token = new JsapiController;
        $access_token = $access_token->get_token();
        $res = array();
        foreach ($flighs as $data){
            $json_template = $this->task_tempalte($data);
            $url = "https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=" . $access_token;
            $call_back = $this->curl_post($url, urldecode($json_template));
            json_decode($call_back, true);
/*            if ($call_back['errcode'] != 0) {
                $res[] = $call_back;
            }*/
        }
    }

    protected function json_tempalte($user){
        //模板消息
        $template=array(
            'touser'=>$user['openid'],  //用户openid
            'template_id'=>"8fjp4ib4bBTQkRAQGJgQTei4Kk-sreAF0qGhNXfhH6M", //在公众号下配置的模板id
            'url'=>"", //点击模板消息会跳转的链接
            'data'=>array(
                'first'=>array('value'=>urlencode("尊敬的".$user['name']."老师"),'color'=>"#FF0000"),
                'keyword1'=>array('value'=>urlencode($user['rgt_numb']),'color'=>'#FF0000'),  //keyword需要与配置的模板消息对应
                'keyword2'=>array('value'=>urlencode(date($user['regter_et'])),'color'=>'#FF0000'),
                'keyword3'=>array('value'=>urlencode($user['type']),'color'=>'#FF0000'),
                'remark' =>array('value'=>urlencode('请及时登录CCAA3.0系统进行'.$user['type'].'，以免给工作带来不便。如有任何问题，请联系能研部。'),'color'=>'#FF0000'), )
        );
        $json_template=json_encode($template);
        return $json_template;
    }

    protected function task_tempalte($user){
        //模板消息
        $template=array(
            'touser'=>$user['openid'],  //用户openid
            'template_id'=>"ybhWwJciJm-M8YlQSZ3j6XZqTxaIgn-zMxDsxP9l8GI", //在公众号下配置的模板id
            'url'=>"http://h5.etciso.com/scan/attendance.html", //点击模板消息会跳转的链接
            'data'=>array(
                'first'=>array('value'=>urlencode("尊敬的".$user['name']."老师您好，".$user['qymc']."审核计划已安排。请进入企业邮箱下载文件。"),'color'=>"#FF0000"),
                'keyword1'=>array('value'=>urlencode($user['qymc']),'color'=>'#FF0000'),  //keyword需要与配置的模板消息对应
                'keyword2'=>array('value'=>urlencode(date($user['contact'])),'color'=>'#FF0000'),
                'keyword3'=>array('value'=>urlencode($user['time']),'color'=>'#FF0000'),
                'keyword4'=>array('value'=>urlencode($user['bgdz']),'color'=>'#FF0000'),
                'keyword5'=>array('value'=>urlencode($user['user']),'color'=>'#FF0000'),
                'remark' =>array('value'=>urlencode('感谢您辛苦的付出，在审核的时候别忘记打卡。'),'color'=>'#FF0000'), )
        );
        $json_template=json_encode($template);
        return $json_template;
    }

    public function curl_post($url , $data=array()){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        // POST数据
        curl_setopt($ch, CURLOPT_POST, 1);
        // 把post的变量加上
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $output = curl_exec($ch);
        curl_close($ch);
        return $output;
    }
}
