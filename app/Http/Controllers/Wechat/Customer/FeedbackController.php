<?php
/**
 * Created by PhpStorm.
 * User: Greatwall
 * Date: 2021/8/30
 * Time: 9:24
 */

namespace App\Http\Controllers\Wechat\Customer;

use App\Http\Controllers\Controller;
use App\Models\Wechat\Complaint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
class FeedbackController  extends Controller
{
    //廉政投诉
    public function complaint(Request $request)
    {
        $files = $request->file('photo');

        if ($request->hasFile('photo')){
            foreach ($files as $file){
                $path[] = $file->store('complaint','wechat');
            }

            if( isset($path) ) {
                $str=implode(',',$path);
            }
            else {
                return response()->json(['status'=>101,'msg'=>'图片上传失败']);
            }
        }
        $res=new Complaint;
        $res->name=$request->name;
        $res->phone=$request->phone;
        $res->mail=$request->mail;
        $res->person=$request->person;
        $res->job=$request->job;
        $res->contents=$request->contents;
        $res->photo=isset($str)?$str:null;
        $res->type=$request->type;
        $res->save();
        return response()->json(['status'=>100,'msg'=>'提交成功']);
    }
    //意见反馈
    public function feedback(Request $request)
    {
        $contents=$request->input('contents');
        if(!$contents){
            return response()->json(['status'=>101,'msg'=>'缺少内容']);
        }
        $files = $request->file('photo');

        if ($request->hasFile('photo')){
            foreach ($files as $file){
                $path[] = $file->store('complaint','wechat');
            }

            if( isset($path) ) {
                $str=implode(',',$path);
            }
            else {
                return response()->json(['status'=>101,'msg'=>'图片上传失败']);
            }
        }
        $time=date('Y-m-d H:i:s',time());
        $res=DB::table('cus_feedback')->insert([
            'contents' => $request->contents,
            'contact' => $request->contact,
            'photo' => isset($str)?$str:null,
            'created_at' => $time,
            'updated_at' => $time
        ]);
        if(!$res){
            return response()->json(['status'=>101,'msg'=>'提交失败']);
        }
        return response()->json(['status'=>100,'msg'=>'提交成功']);
    }
    //满意度调查列表
    public function reviews(){
        $list=DB::table('cus_reviews')->select('name','url')->get();
        if($list->isEmpty()){
            return response()->json(['status'=>101,'msg'=>'无数据']);
        }
        return response()->json(['status'=>100,'msg'=>'请求成功','data'=>$list]);
    }

}