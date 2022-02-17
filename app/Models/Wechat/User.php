<?php
/**
 * Created by PhpStorm.
 * User: Greatwall
 * Date: 2021/1/21
 * Time: 17:30
 */
namespace App\Models\Wechat;
use Illuminate\Database\Eloquent\Model;
date_default_timezone_set('Asia/Shanghai');
class User extends Model{

    /**定义表名**/
    protected $table = 'users';

    public $timestamps = false;
    protected $hidden = [
        'openid',
        'password',
        'e_mail',
        'mailaccount',
        'mailname',
        'time',
        'api_token',
        'token_time',
    ];

    /**人员审核基本信息**/
    protected function UserBasic($where,$field,$date){
        $res = User::
        leftJoin('qyht_htrzu', 'qyht_htrzu.us_id', '=', 'users.id')
            ->leftJoin('qyht_htrza', 'qyht_htrza.id', '=', 'qyht_htrzu.ap_id')
            ->leftJoin('qyht_htrz', 'qyht_htrz.id', '=', 'qyht_htrza.xm_id')
            ->leftJoin('qyht', 'qyht.id', '=', 'qyht_htrz.ht_id')
            ->leftJoin('khxx', 'khxx.id', '=', 'qyht.kh_id')
//            ->select('users.name','qyht_htrza.start_time','qyht_htrza.end_time','etc_qyht_htrza.xm_id')
            ->select($field)
            ->where($where)
            ->whereDate('qyht_htrza.start_time','<=',$date)
            ->whereDate('qyht_htrza.end_time','>=',$date)
            ->orderBy('qyht_htrza.start_time','asc')
            ->get();
        return $res;
    }

    /**user信息**/
    protected function info($openid){
        $res = User::where('openid',$openid)->where('stop',1)->first();
        return $res;
    }
    //美橙短信
    protected function send($mobile,$content) {
        /*反馈信息
        $msg_arr = array(1 => '发送成功',2 => '参数不正确',3 => '验证失败',4 => '用户名或密码错误',
                         5 => '数据库操作失败',6 => '余额不足',7 => '内容不符合格式',8 => '频率超限',
                         9 => '接口超时',10 => '后缀签名长度超过限制');
        */
        $url = 'http://sms.edmcn.cn/api/cm/trigger_mobile.php';//接口地址
        $time=time() - 8 * 3600;
        $data=array(
            'username'=>'sms568007',
            'time'=>$time,
            'mobile'=>$mobile,
            'content'=>urlencode($content.'【亿信标准】'),
            'authkey'=>md5('sms568007'.$time.md5('p58qtn').'019d971a4294cb57acfdcde9fd4b8d9f'),
        );
        $row = parse_url($url);
        $host = $row['host'];
        $port = isset($row['port']) ? $row['port']:80;
        $file = $row['path'];
        $post = '';
//        while (list($k,$v) = each($data)) $post .= $k."=".$v."&";
        foreach ($data as $k=>$v){
            $post .= $k."=".$v."&";
        }
        $post = substr( $post , 0 , -1 );
        $len = strlen($post);
        $fp = @fsockopen($host ,$port, $errno, $errstr, 10);
        if(!$fp) return "connect error";

        $receive = '';
        $out = "POST $file HTTP/1.0\r\n";
        $out .= "Host: $host\r\n";
        $out .= "Content-type: application/x-www-form-urlencoded\r\n";
        $out .= "Connection: Close\r\n";
        $out .= "Content-Length: $len\r\n\r\n";
        $out .= $post;
        fwrite($fp, $out);
        while (!feof($fp)) {
            $receive .= fgets($fp, 128);
        }
        fclose($fp);
        $receive = explode("\r\n\r\n",$receive);
        unset($receive[0]);
        return implode("",$receive);
    }
}