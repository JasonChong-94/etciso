<?php
namespace App\Http\Middleware;

use Closure;
use App\Models\Api\User\UserRole;
use Illuminate\Support\Facades\Auth;

class EtcToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $lever = [
            "api/approval/group/stop",
            "api/approval/group/set",
            "api/approval/set/group",
            "api/approval/group/sort",
            "api/approval/type/add",
            "api/market/customer/index",
            "api/market/review/index",
            "api/market/customer/query",
            "api/market/review/query",
            "api/market/customer/all",
            "api/market/review/all",
            "api/market/review/export",
            "api/market/customer/query",
            "api/market/review/query",
            "api/market/customer/add",
            "api/market/customer/edit",
            "api/market/contract/systemadd",
            "api/market/contract/systemedit",
            "api/market/contract/moneyadd",
            "api/market/contract/moneyedit",
            "api/market/contract/add",
            "api/market/contract/systemdel",
            "api/market/contract/del",
            "api/market/contract/moneydel",
            "api/market/contract/submit",
            "api/market/contract/sure",
            "api/market/contacts/add",
            "api/market/contacts/edit",
            "api/market/contacts/state",
            "api/examine/review/index",
            "api/examine/review/adopt",
            "api/examine/review/nquery",
            "api/examine/review/select",
            "api/examine/review/export",
            "api/examine/review/customer",
            "api/examine/review/edit",
            "api/examine/review/add",
            "api/examine/review/submit",
            "api/examine/change/submit",
            "api/examine/review/change",
            "api/examine/change/basic",
            "api/examine/personnel/index",
            "api/examine/personnel/select",
            "api/examine/personnel/export",
            "api/examine/personnel/submit",
            "api/examine/personnel/udele",
            "api/examine/personnel/uadd",
            "api/examine/personnel/edit",
            "api/examine/evaluate/english",
            "api/examine/evaluate/submit",
            "api/examine/evaluate/edit",
            "api/inspect/dispatch/index",
            "api/inspect/dispatch/query",
            "api/inspect/dispatch/export",
            "api/inspect/dispatch/add",
            "api/inspect/dispatch/edit",
            "api/inspect/dispatch/submit",
            "api/inspect/dispatch/udele",
            "api/inspect/dispatch/uadd",
            "api/inspect/dispatch/stage",
            "api/inspect/dispatch/union",
            "api/inspect/dispatch/cancel",
            "api/inspect/report/plan",
            "api/inspect/report/export",
            "api/inspect/report/result",
            "api/inspect/report/revoke",
            "api/inspect/plan/query",
            "api/inspect/result/query",
            "api/inspect/revoke/query",
            "api/inspect/result/submit",
            "api/inspect/revoke/add",
            "api/inspect/revoke/delt",
            "api/inspect/plan/submit",
            "api/inspect/plan/state",
            "api/inspect/print/index",
            "api/inspect/print/query",
            "api/inspect/print/export",
            "api/inspect/print/sample",
            "api/inspect/print/add",
            "api/inspect/print/edit",
            "api/inspect/print/suspend",
            "api/inspect/suspend/query",
            "api/inspect/suspend/export",
            "api/inspect/suspend/add",
            "api/inspect/activity/index",
            "api/inspect/activity/query",
            "api/inspect/agenda/index",
            "api/inspect/agenda/query",
            "api/inspect/auditor/index",
            "api/inspect/auditor/query",
            "api/inspect/user/add",
            "api/inspect/type/add",
            "api/inspect/type/del",
            "api/inspect/decide/add",
            "api/inspect/decide/del",
            "api/inspect/study/add",
            "api/inspect/study/del",
            "api/inspect/work/add",
            "api/inspect/work/del",
            "api/inspect/train/add",
            "api/inspect/train/del",
            "api/inspect/major/add",
            "api/inspect/major/export",
            "api/inspect/major/del",
            "api/inspect/major/import",
            "api/inspect/major/state",
            "api/inspect/code/index",
            "api/inspect/code/query",
            "api/inspect/extension/index",
            "api/inspect/extension/delete",
            "api/system/clause/index",
            "api/system/clause/add",
            "api/user/user/index",
            "api/user/user/query",
            "api/user/user/add",
            "api/user/user/ment",
            "api/user/ment/add",
            "api/user/ment/del",
            "api/user/user/role",
            "api/user/role/user",
            "api/user/role/add",
            "api/user/role/del",
            "api/user/clock/index",
            "api/system/template/index",
            "api/system/template/copy",
            "api/system/template/delt",
            "api/system/template/edit",
            "api/system/template/add",
            "api/system/major/index",
            'api/talent/talent/save',
            'api/talent/talent/list',
            'api/talent/talent/official',
            'api/talent/track/save',
            'api/talent/track/list',
            'api/finance/index/amount',
            'api/finance/index/invoice',
            'api/finance/edit/receipt',
            'api/finance/confirm/receipt',
            'api/finance/confirm/invoice',
        ];
        try{
            if(!$request->isMethod('post')){
                return response()->json(['status' => 105,'msg' => '请求异常']);
            }
            if (Auth::guard('api')->guest()) {
                return response()->json(['status' => 104,'msg' => 'token错误']);
            }
            $user  = Auth::guard('api')->user();
            if(time()-$user->token_time >= 14400){
                return response()->json(['status' => 106,'msg' => 'token过期']);
            }
            //dump($request->path());die;
            if(in_array($request->path(),$lever)){
                if(!UserRole::TestRole(['id' =>Auth::guard('api')->user()->zw_id],$request->path())){
                    return response()->json(['status' => 107,'msg' => '无权限访问']);
                }
            }
            $user->token_time = time();
            if($user->save()){
                return $next($request);
            }
            return $next($request);
            throw new \Exception("抛出异常");
        }catch (\Exception $e){
            return response()->json(['status'=>103,'msg'=>'账户异常']);
        }
    }
}