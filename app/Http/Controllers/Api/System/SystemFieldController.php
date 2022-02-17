<?php

namespace App\Http\Controllers\Api\System;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Api\Examine\ExamineNames;

class SystemFieldController extends Controller
{
    /**多领域**/
    public function FieldIndex(Request $request){
        $flighs = ExamineNames::
        where('state', '=', 1)
            ->get();
        if($flighs->isEmpty()){
            return response()->json(['status'=>101,'msg'=>'无数据']);
        }
        return response()->json(['status'=>100,'msg'=>'请求成功','data'=>$flighs]);
    }
}
