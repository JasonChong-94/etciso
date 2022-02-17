<?php

namespace App\Http\Controllers\Api\System;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Api\User\UserCtfcate;
use Illuminate\Support\Facades\DB;

class SystemCtfcateController extends Controller
{
    /**人员资质类别**/
    public function CtfcateIndex(Request $request){
        $flighs = UserCtfcate::get();
        if($flighs->isEmpty()){
            return response()->json(['status'=>101,'msg'=>'无数据']);
        }
        return response()->json(['status'=>100,'msg'=>'请求成功','data'=>$flighs]);
    }
}
