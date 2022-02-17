<?php

namespace App\Http\Controllers\Api\System;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Api\System\SystemState;
use Illuminate\Support\Facades\DB;

class SystemStageController extends Controller
{
    /**审核阶段**/
    public function StageIndex(Request $request){
        $where  = array(
            ['state','=',1],
        );
        $file = array(
            'code',
            'activity',
        );
        $flighs = SystemState::IndexState($file,$where);
        if($flighs->isEmpty()){
            return response()->json(['status'=>101,'msg'=>'无数据']);
        }
        return response()->json(['status'=>100,'msg'=>'请求成功','data'=>$flighs]);
    }
}
