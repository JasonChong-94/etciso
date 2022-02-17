<?php
/**
 * Created by PhpStorm.
 * User: Greatwall
 * Date: 2021/8/4
 * Time: 15:03
 */
namespace App\Http\Controllers\Wechat\Customer;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Wechat\JsapiController;
use App\Models\Wechat\Contacts;
use App\Models\Wechat\Home;
use App\Models\Wechat\Khxx;
use App\Models\Wechat\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
class UserController  extends Controller
{

    public function user_info(Request $request){
        $openid=$request->input('openid');
        if(!$openid){
            return response()->json([
                'status'=>101,
                'msg'=>'缺少openid'
            ]);
        }
        $field=[
            'khxx.qymc',
            'khxx.scjl',
            'contacts.kh_id',
        ];
        $where[] = ['contacts.openid', '=', $openid];
        $res = Home::info($where,$field);

        if($res){
//            if(strpos($res['scjl'],';2;')){
                $scjl = str_replace(';2;','',$res['scjl']);
                $res['scjl'] = str_replace(';','',$scjl);
//            }
            return response()->json(['status'=>100,'msg'=>'请求成功','data'=>$res]);
        }else{
            return response()->json(['status'=>101,'msg'=>'无数据']);
        }
    }
    //客户详情
    public function kh_detail(Request $request){
        $kh_id=$request->input('kh_id');
        if(!$kh_id){
            return response()->json([
                'status'=>101,
                'msg'=>'缺少kh_id'
            ]);
        }
        $res=Khxx::find($kh_id);

        if(!$res){
            return response()->json(['status'=>101,'msg'=>'无数据']);
        }
        return response()->json(['status'=>100,'msg'=>'请求成功','data'=>$res]);
    }
    //联系人
    public function Contacts_list(Request $request){
        $kh_id=$request->input('kh_id');
        if(!$kh_id){
            return response()->json([
                'status'=>101,
                'msg'=>'缺少kh_id'
            ]);
        }
        $where[]=['contacts.kh_id','=',$kh_id];
        $fild=[
            'contacts.id',
            'contacts.phone',
            'contacts.name',
            'contacts.job',
            'contacts.tell',
            'contacts.qq',
            'contacts.weixin',
            'contacts.mail',
            'contacts_type.type_name',
        ];
        $res=Contacts::list($where,$fild);

        if(!$res){
            return response()->json(['status'=>101,'msg'=>'无数据']);
        }
        return response()->json(['status'=>100,'msg'=>'请求成功','data'=>$res]);
    }
    //客户经理详情
    public function cus_detail(Request $request){
        $id=$request->input('id');
        if(!$id){
            return response()->json([
                'status'=>101,
                'msg'=>'缺少id'
            ]);
        }
        $res=User::find($id);
        if(!$res){
            return response()->json(['status'=>101,'msg'=>'无数据']);
        }
        return response()->json(['status'=>100,'msg'=>'请求成功','data'=>$res]);
    }
    public function ocr_license(Request $request){
        $messages = [
            'required' => '缺少参数',
            'image' => '请上传图片',
        ];
        $validator = Validator::make($request->all(), [
            'license' => 'required|image',
        ],$messages);

        if ($validator->fails()) {
            return response()->json(['msg' => $validator->errors()->first(),'status'=>101]);
        }
        $path = $request->file('license')->store('license','wechat');
        $obj=new JsapiController();
        $xydm=$obj->ocr_license('http://oc.etciso.com/wechat/'.$path);
        if(!$xydm){
            return response()->json(['status'=>101,'msg'=>'执照识别失败']);
        }
        return response()->json(['status'=>100,'msg'=>'成功','data'=>$xydm]);
    }
    public function binding(Request $request){
        $messages = [
            'required' => '缺少参数',
        ];
        $validator = Validator::make($request->all(), [
            'openid' => 'required',
            'phone' => 'required',
            'code' => 'required',
            'xydm' => 'required',
        ],$messages);

        if ($validator->fails()) {
            return response()->json(['msg' => $validator->errors()->first(),'status'=>101]);
        }
        $where[] = ['khxx.xydm', '=', $request->xydm];
        $where[] = ['contacts.phone', '=', $request->phone];
        $res=Home::info($where,['contacts.id','contacts.openid']);
        if(!$res){
            return response()->json(['status'=>101,'msg'=>'未查找到该企业人员']);
        }
        if($res->openid){
            return response()->json(['status'=>101,'msg'=>'该号码已绑定微信']);
        }
        $cache  = Cache::has($request->phone);
        if($cache == false) {
            return response()->json(['status'=>101,'msg'=>'验证码已过期']);
        }
        $contacts=Contacts::find($res->id);
        $contacts->openid=$request->openid;
        if($contacts->save()){
            return response()->json(['status'=>100,'msg'=>'绑定成功']);
        }else{
            return response()->json(['status'=>101,'msg'=>'绑定失败']);
        }
    }

}