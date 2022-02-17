<?php
/**
 * Created by PhpStorm.
 * User: Greatwall
 * Date: 2021/1/21
 * Time: 14:28
 */


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
/**
 * 微信
 **/
Route::group([
    'namespace'=>'Wechat',
    'prefix'=>'wechat'
], function () {
    Route::any('js_api/getInfo','JsapiController@getInfo');
    Route::any('js_api/openid','JsapiController@GetOpenid');
    Route::any('js_api/ocr_license','JsapiController@ocr_license');
    Route::any('express/search','Express\SfController@search');
});

//打卡模块
Route::group([
    'namespace'=>'Wechat\Attendance',
    'prefix'=>'attendance'
], function () {
    Route::any('check/info','CheckController@getinfo');
    Route::any('check/check_info','CheckController@check_info');
    Route::any('check/save_info','CheckController@save_info');
    Route::any('check/user_info','CheckController@user_info');
    Route::any('check/lng_lat','CheckController@lng_lat');//经纬度
});
//打卡个人中心
Route::group([
    'namespace'=>'Wechat\Home',
    'prefix'=>'home'
], function () {
    Route::any('sms/send','UserController@send');//发送短信
    Route::any('sms/binding','UserController@binding');//短信绑定
});
//打卡审核计划数据
Route::group([
    'namespace'=>'Wechat',
    'prefix'=>'oa'
], function () {
    /**
    审核计划安排
     **/
    /**计划列表**/
    Route::any('task/index','Inspect\InspectTaskController@index');
    /**列表详情**/
    Route::any('task/detail','Inspect\InspectTaskController@detail');

    /**市场部 企业详情**/
    Route::any('customer/details','Market\MarketCustomerController@CustomerDetails');
    /**市场部  客户联系人**/
    Route::any('contacts/index','Market\MarketContactsController@ContactsIndex');
});
//后台接口数据
Route::group([
    'namespace'=>'Api',
    'prefix'=>'ob'
], function () {
    /**项目人员**/
    Route::any('dispatch/user','Inspect\InspectDispatchController@DispatchUser');
    /**审核阶段**/
    Route::any('stage/index','System\SystemStageController@StageIndex');
    /**年审复评类型**/
    Route::any('review/type','Market\MarketReviewController@ReviewType');
});
//客户服务端
Route::group([
    'namespace'=>'Wechat\Customer',
    'prefix'=>'customer'
], function () {
    Route::any('user/info','UserController@user_info');
    Route::any('user/binding','UserController@binding');
    Route::any('user/ocr_license','UserController@ocr_license');
    Route::any('user/kh_detail','UserController@kh_detail');
    Route::any('user/Contacts_list','UserController@Contacts_list');
    Route::any('user/cus_detail','UserController@cus_detail');
    Route::any('home/plan_list','HomeController@plan_list');
    Route::any('home/pj_add','HomeController@pj_add');
    Route::any('home/certificate','HomeController@certificate');
    Route::any('home/rz_list','HomeController@rz_list');
    Route::any('home/process','HomeController@process');//流程查询
    Route::any('home/shjy','HomeController@shjy');//审核建议
//    Route::any('home/sh_jy','HomeController@sh_jy');
    Route::any('home/zzjy','HomeController@zzjy');//组长建议
    Route::any('home/review_type','HomeController@ContractProject');
    Route::any('home/recommend','HomeController@recommend');//推荐客户
    Route::any('complaint/add','FeedbackController@complaint');//廉政投诉
    Route::any('feedback/add','FeedbackController@feedback');//意见反馈
    Route::any('reviews/list','FeedbackController@reviews');//满意度调查列表
    Route::any('invoice/add','InvoiceController@add');
    Route::any('invoice/list','InvoiceController@list');
    Route::any('invoice/detail','InvoiceController@detail');
});