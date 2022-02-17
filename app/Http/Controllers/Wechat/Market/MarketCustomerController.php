<?php

namespace App\Http\Controllers\Wechat\Market;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MarketCustomerController extends Controller
{

    /**客户详情**/
    public function CustomerDetails(Request $request){
        $obj=new \App\Http\Controllers\Api\Market\MarketCustomerController($request);
        $list=$obj->CustomerDetails($request);
        return $list;
    }


}
