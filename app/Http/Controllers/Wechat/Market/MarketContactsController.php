<?php

namespace App\Http\Controllers\Wechat\Market;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MarketContactsController extends Controller
{
    /**客户联系人**/
    public function ContactsIndex(Request $request){
        $obj=new \App\Http\Controllers\Api\Market\MarketContactsController($request);
        $list=$obj->ContactsIndex($request);
        return $list;
    }

}
