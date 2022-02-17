<?php

namespace App\Http\Controllers\Wechat\Inspect;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
class InspectTaskController extends Controller
{

    /**项目计划列表**/
    public function index(Request $request){
        $userid=$request->input('userid');
        if(!$userid){
            return response()->json(['status'=>101,'msg'=>'请求失败']);
        }
        $obj=new \App\Http\Controllers\Api\Inspect\InspectTaskController($request);
        $list=$obj->index($request);
        return $list;
    }

    /**项目列表详情**/
    public function detail(Request $request){
        $userid=$request->input('userid');
        if(!$userid){
            return response()->json(['status'=>101,'msg'=>'请求失败']);
        }
        $obj=new \App\Http\Controllers\Api\Inspect\InspectTaskController($request);
        $list=$obj->detail($request);
        return $list;
    }

}
