<?php

namespace App\Http\Controllers\Api\System;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Api\System\SystemRegion;
use Illuminate\Support\Facades\DB;

class SystemRegionController extends Controller
{
    /**地区代码**/
    public function RegionIndex(Request $request){
        $where  = array(
            ['pid','=',$request->id],
            ['state','=',1]
        );
        $file = array(
            'id',
            'areacode',
            'areaname'
        );
        //DB::enableQueryLog();
        $flighs = SystemRegion::IndexRegion($file,$where);
        //return response()->json(DB::getQueryLog());
        if($flighs->isEmpty()){
            return response()->json(['status'=>101,'msg'=>'无数据']);
        }
        return response()->json(['status'=>100,'msg'=>'请求成功','data'=>$flighs]);
    }
}
