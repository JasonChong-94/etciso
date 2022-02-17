<?php

namespace App\Http\Controllers\Api\System;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Api\System\SystemMajor;
use Illuminate\Support\Facades\DB;

class SystemMajorController extends Controller
{
    /**审核阶段**/
    public function MajorIndex(Request $request){
        $where  = array(
            ['b_m','=',$request->state],
        );
        if($request->rztx){
            $where[] = ['e_name','=',$request->rztx];
        }
        if($request->code){
            $where[] = ['b_code','like','%'.$request->code.'%'];
        }
        if($request->new){
            $where[] = ['n_old','=',$request->new];
        }
        DB::connection()->enableQueryLog();#开启执行日志
        $flight = SystemMajor::IndexMajor('*',$where,$request->limit);
        //dump(DB::getQueryLog());die;
        if($flight->isEmpty()){
            return response()->json(['status'=>101,'msg'=>'无数据']);
        }
        return response()->json(['status'=>100,'msg'=>'请求成功','data'=>$flight]);
    }
}
