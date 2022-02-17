<?php

namespace App\Http\Controllers\Api\Email;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Api\WeChat\WeixinCodeController;

class EmailCodeController extends Controller
{
    /**企业邮access_token**/
    public function getCode($secrect){
        $corpid = 'wwc1301702b43b5ddb';
        $token_url = 'https://api.exmail.qq.com/cgi-bin/gettoken?corpid='.$corpid.'&corpsecret='.$secrect;
        $token = json_decode(file_get_contents($token_url));
        return($token);
    }

    /**企业邮url**/
    public function getUrl(Request $request){
        $secrect = 'rAKX0mNM6XX2LUjd7ZcNGxZg1h8KozYTTG2HkKGGJPqm7bK6mp5Jg-sTLKV6TAI8';
        $token = $this->getCode($secrect);
        if($token->errcode != 0){
            return response()->json(['status'=>101,'msg'=>$token->errmsg]);
        }
        $access_token = $token->access_token;
        $mail_url = 'https://api.exmail.qq.com/cgi-bin/service/get_login_url?access_token='.$access_token.'&userid='.$request->mailaccount;
        $token = json_decode(file_get_contents($mail_url));
        if($token->errcode == 0){
            return response()->json(['status'=>100,'msg'=>'请求成功','data'=>$token->login_url]);
        }else{
            return response()->json(['status'=>101,'msg'=>$token->errmsg]);
        }
    }

    /**邮件未读数**/
    public function getCount(){
        $secrect = 'j7KR1zTolNh9mnaYWMrXwB_nBsjdVQupLP5WtHIyYUmMk1OHqo7QCQmGrwUWlF29';
        $token = $this->getCode($secrect);
        if($token->errcode != 0){
            return response()->json(['status'=>101,'msg'=>$token->errmsg]);
        }
        $access_token = $token->access_token;
        $userid = Auth::guard('api')->user()->mailaccount;
        $pieces = explode(";", $userid);
        foreach ($pieces as $v){
            $mail_url = 'https://api.exmail.qq.com/cgi-bin/mail/newcount?access_token='.$access_token.'&userid='.$v;
            $token = json_decode(file_get_contents($mail_url));
            if($token->errcode == 0){
               $data[] = array(
                   'mailaccount' => $v,
                   'count' => $token->count,
               );
            }else{
                $data[] = array(
                    'mailaccount' => $v,
                    'count' => $token->errmsg,
                );
            }
        }
        return response()->json(['status'=>100,'msg'=>'请求成功','data'=>$data]);
    }

    /**创建部门**/
    public function addDepartment($name,$parentid,$order=''){
        $secrect = 'Uo-h-jBvM3Wb8HpBkrVSSxXDxMs4ejtM0u7Bs4uzDKqxP1gyBl56WkXnELGIzYSN';
        $token = $this->getCode($secrect);
        if($token->errcode != 0){
            return response()->json(['status'=>101,'msg'=>$token->errmsg]);
        }
        $access_token = $token->access_token;
        $url="https://api.exmail.qq.com/cgi-bin/department/create?access_token=".$access_token;
        $template=array(
            'name'=>$name,  //部门名称
            'parentid'=>intval($parentid), //部门名称
            //'order'=>$order, //部门名称
            );
        $json_template=json_encode($template);
        $curl_post = new WeixinCodeController;
        $call_back = $curl_post->curl_post($url,urldecode($json_template));
        return(json_decode($call_back));
    }

    /**更新部门**/
    public function editDepartment($id,$name,$parentid,$order=''){
        $secrect = 'Uo-h-jBvM3Wb8HpBkrVSSxXDxMs4ejtM0u7Bs4uzDKqxP1gyBl56WkXnELGIzYSN';
        $token = $this->getCode($secrect);
        if($token->errcode != 0){
            return response()->json(['status'=>101,'msg'=>$token->errmsg]);
        }
        $access_token = $token->access_token;
        $url="https://api.exmail.qq.com/cgi-bin/department/update?access_token=".$access_token;
        $template=array(
            'id'=>intval($id),  //部门id
            'name'=>$name,  //部门名称
            'parentid'=>intval($parentid), //部门名称
            //'order'=>intval($order), //部门名称
        );
        $json_template=json_encode($template);
        $curl_post = new WeixinCodeController;
        $call_back = $curl_post->curl_post($url,urldecode($json_template));
        return(json_decode($call_back));
    }

    /**删除部门**/
    public function deleteDepartment($id){
        $secrect = 'Uo-h-jBvM3Wb8HpBkrVSSxXDxMs4ejtM0u7Bs4uzDKqxP1gyBl56WkXnELGIzYSN';
        $token = $this->getCode($secrect);
        if($token->errcode != 0){
            return response()->json(['status'=>101,'msg'=>$token->errmsg]);
        }
        $access_token = $token->access_token;
        $url="https://api.exmail.qq.com/cgi-bin/department/delete?access_token=".$access_token.'&id='.$id;
        $token = json_decode(file_get_contents($url));
        return($token);
    }

    /**部门列表**/
    public function indexDepartment(){
        $secrect = 'Uo-h-jBvM3Wb8HpBkrVSSxXDxMs4ejtM0u7Bs4uzDKqxP1gyBl56WkXnELGIzYSN';
        $token = $this->getCode($secrect);
        if($token->errcode != 0){
            return response()->json(['status'=>101,'msg'=>$token->errmsg]);
        }
        $access_token = $token->access_token;
        $url="https://api.exmail.qq.com/cgi-bin/department/list?access_token=".$access_token.'&id=';
        $token = json_decode(file_get_contents($url));
        dump($token);die;
    }

    /**创建人员**/
    public function addUser($userid,$name,$department,$setvip){
        $secrect = 'Uo-h-jBvM3Wb8HpBkrVSSxXDxMs4ejtM0u7Bs4uzDKqxP1gyBl56WkXnELGIzYSN';
        $token = $this->getCode($secrect);
        if($token->errcode != 0){
            return response()->json(['status'=>101,'msg'=>$token->errmsg]);
        }
        $access_token = $token->access_token;
        $url="https://api.exmail.qq.com/cgi-bin/user/create?access_token=".$access_token;
        $template=array(
            'userid'=>$userid,  //	成员UserID。企业邮帐号名，邮箱格式
            'name'=>$name,  //成员名称
            'department'=>[intval($department)], //部门名称
            'password'=>'Etc2021', //密码
            'setvip'=>intval($setvip), //修改用户是否为VIP账户。0表示普通账号，1表示VIP账号
        );
        $json_template=json_encode($template);
        $curl_post = new WeixinCodeController;
        $call_back = $curl_post->curl_post($url,urldecode($json_template));
        return(json_decode($call_back));
    }

    /**更新人员**/
    public function editUser($userid,$name,$department,$enable,$setvip){
        $secrect = 'Uo-h-jBvM3Wb8HpBkrVSSxXDxMs4ejtM0u7Bs4uzDKqxP1gyBl56WkXnELGIzYSN';
        $token = $this->getCode($secrect);
        if($token->errcode != 0){
            return response()->json(['status'=>101,'msg'=>$token->errmsg]);
        }
        $access_token = $token->access_token;
        $url="https://api.exmail.qq.com/cgi-bin/user/update?access_token=".$access_token;
        $template=array(
            'userid'=>$userid,  //	成员UserID。企业邮帐号名，邮箱格式
            'name'=>$name,  //成员名称
            'department'=>$department?[intval($department)]:[], //部门名称
            'enable'=>intval($enable), //启用/禁用成员。1表示启用成员，0表示禁用成员
            'setvip'=>intval($setvip), //修改用户是否为VIP账户。0表示普通账号，1表示VIP账号
        );
        $json_template=json_encode($template);
        $curl_post = new WeixinCodeController;
        $call_back = $curl_post->curl_post($url,urldecode($json_template));
        return(json_decode($call_back));
    }

    /**检查帐号**/
    public function checkUser(Request $request){
        $secrect = 'Uo-h-jBvM3Wb8HpBkrVSSxXDxMs4ejtM0u7Bs4uzDKqxP1gyBl56WkXnELGIzYSN';
        $token = $this->getCode($secrect);
        if($token->errcode != 0){
            return response()->json(['status'=>101,'msg'=>$token->errmsg]);
        }
        $access_token = $token->access_token;
        $url="https://api.exmail.qq.com/cgi-bin/user/batchcheck?access_token=".$access_token;
        $template=array(
            'userlist'=>[$request->mailaccount],  //	成员UserID。企业邮帐号名，邮箱格式
        );
        $json_template=json_encode($template);
        $curl_post = new WeixinCodeController;
        $call_back = $curl_post->curl_post($url,urldecode($json_template));
        $batchcheck= json_decode($call_back);
        if($batchcheck->errcode == 0){
            switch ($batchcheck->list[0]->type)
            {
                case '-1':
                    return response()->json(['status'=>101,'msg'=>'帐号号无效']);
                    break;
                case 0:
                    return response()->json(['status'=>100,'msg'=>'帐号名未被占用']);
                    break;
                case 1:
                    return response()->json(['status'=>101,'msg'=>'主帐号']);
                    break;
                case 2:
                    return response()->json(['status'=>101,'msg'=>'别名帐号']);
                    break;
                case 3:
                    return response()->json(['status'=>101,'msg'=>'邮件群组帐号']);
                    break;
            }
        }else{
            return response()->json(['status'=>101,'msg'=>$token->errmsg]);
        }
    }
}
