<?php

namespace App\Http\Controllers\Api\Finance;

use App\Http\Controllers\Controller;
use App\Models\Api\Market\MarketAmount;
use App\Models\Api\Market\MarketAmountChange;
use App\Models\Api\Market\MarketInvoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
date_default_timezone_set('Asia/Shanghai');
class FinanceInvoiceController extends Controller
{
    /**收款确认**/
    public function confirmReceipt(Request $request){
        $amount_id=$request->input('amount_id');
        $state=$request->input('state');
        if(!$amount_id){
            return response()->json(['status'=>101,'msg'=>'缺少金额id']);
        }
        if($state==1){ //通过审核增加企业开票金额
            DB::beginTransaction();
            try {
                $amount = MarketAmount::where('id',$request->amount_id)->sharedLock()->first();
                if($amount->state==1){
                    DB::rollback();
                    return response()->json(['status'=>101,'msg'=>'该记录已确认过']);
                }
                $amount->state = $state;
                $amount->confirm_date = date("Y-m-d H:i:s");
                $amount->sh_name = Auth::guard('api')->user()->name;
                $amount->save();//审核后保存
                if($amount->type==1){
                    //增加开票余额
                    MarketAmount::add_amount_n($amount->xydm,$amount->to_xydm,$amount->money);
                }
                MarketAmount::add_amount($amount->xydm,$amount->to_xydm,$amount->money);//增加开票总金额
                MarketAmount::add_khxx_amount($amount->xydm,$amount->money);//增加开票总金额
                DB::commit();
            }catch (\Exception $e){
                DB::rollback();
                return response()->json(['status'=>101,'msg'=>'写入失败'.$e->getMessage()]);
            }
        }else{
            $res=MarketAmount::find( $amount_id);
            $res->state = $state;
            $res->sh_name = Auth::guard('api')->user()->name;
            if(!$res->save()){
                return response()->json(['status'=>101,'msg'=>'该信息不存在/失败']);
            }
        }
        return response()->json(['status'=>100,'msg'=>'成功']);
    }

    /**收款修改**/
    public function editReceipt(Request $request){
        $amount_id=$request->input('amount_id');
        $messages = [
            'amount_id.required' => '缺少金额id',
            'company.required' => '缺少到账公司名',
            'time.required' => '缺少到账时间',
        ];
        $validator = Validator::make($request->all(), [
            'amount_id' => 'required',
            'company' => 'required',
            'time' => 'required',
        ],$messages);
        if ($validator->fails()) {
            return response()->json(['msg' => $validator->errors()->first(),'status'=>101]);
        }
        DB::beginTransaction();
        try {
            $amount = MarketAmount::find($request->amount_id);
            $data['old_company'] = $amount->company;
            $data['company'] = $request->company;
            $data['old_time'] = $amount->time;
            $data['time'] = $request->time;
            $data['old_remarks'] = $amount->remarks;
            $data['remarks'] = $request->remarks;
            $amount->company = $request->company;
            $amount->time = $request->time;
            $amount->remarks = $request->remarks;
            $amount->save();
            $data['amount_id'] = $amount_id;
            $data['user_name'] = Auth::guard('api')->user()->name;
            $data['change_time'] = date('Y-m-d');
            MarketAmountChange::insert($data);
            DB::commit();
        }catch (\Exception $e){
            DB::rollback();
            return response()->json(['status'=>101,'msg'=>'变更失败']);
        }
        return response()->json(['status'=>100,'msg'=>'变更成功']);

    }

    /**修改记录**/
    public function indexReceipt(Request $request){
        $amount_id=$request->input('amount_id');
        if(!$amount_id){
            return response()->json(['status'=>101,'msg'=>'缺少消费金额id']);
        }
        $where[]=['amount_id','=',$amount_id];
        $change = MarketAmountChange::index($where);
        if($change->first()){
            return response()->json(['status'=>100,'msg'=>'请求成功','data'=>$change]);
        }else{
            return response()->json(['status'=>101,'msg'=>'无记录']);
        }
    }

    /**开票确认**/
    public function confirmInvoice(Request $request){
        $invoice_id=$request->input('invoice_id');
        $state=$request->input('state');
        if(!$invoice_id){
            return response()->json(['status'=>101,'msg'=>'缺少发票id']);
        }
        if($state==1){ //通过审核增加企业开票金额
            DB::beginTransaction();
            try {
                $invoice = MarketInvoice::where('id',$request->invoice_id)->sharedLock()->first();
                if($invoice->state==1){
                    DB::rollback();
                    return response()->json(['status'=>101,'msg'=>'该记录已确认过']);
                }
                $invoice->state = $state;
                $invoice->confirm_date = date("Y-m-d H:i:s");
                $invoice->sh_name = Auth::guard('api')->user()->name;
                $invoice->save();//审核后保存
                MarketInvoice::add_amount_y($invoice->tax_no,$invoice->to_xydm,$invoice->amount);//增加已开票金额
                MarketInvoice::edit_amount_y($invoice->tax_no,$invoice->to_xydm,$invoice->amount);//减少可开票金额
                DB::commit();
            }catch (\Exception $e){
                DB::rollback();
                //$e->getMessage()
                return response()->json(['status'=>101,'msg'=>'写入失败']);
            }
        }else{
            $res=MarketInvoice::find($invoice_id);
            $res->state = $state;
            $res->sh_name = Auth::guard('api')->user()->name;
            if(!$res->save()){
                return response()->json(['status'=>101,'msg'=>'该信息不存在/失败']);
            }
        }
        return response()->json(['status'=>100,'msg'=>'成功']);
    }
    /**金额列表**/
    public function indexAmount(Request $request){
        $where=[];
        if($request->qymc){
            $where[]=['qymc','like','%'.$request->qymc.'%'];
        }
        if($request->khxx_id){
            $where[]=['khxx_id','=',$request->khxx_id];
        }

        $res = MarketAmount::index($where,$request->limit);
        if($res->first()){
            return response()->json(['status'=>100,'msg'=>'请求成功','data'=>$res]);
        }else{
            return response()->json(['status'=>101,'msg'=>'无记录']);
        }
    }
    /**发票列表**/
    public function indexInvoice(Request $request){
        $where=[];
        if($request->kh_name){
            $where[]=['kh_name','like','%'.$request->kh_name.'%'];
        }
        if($request->khxx_id){
            $where[]=['khxx_id','=',$request->khxx_id];
        }
        $res = MarketInvoice::index($where,$request->limit);
        if($res->first()){
            return response()->json(['status'=>100,'msg'=>'请求成功','data'=>$res]);
        }else{
            return response()->json(['status'=>101,'msg'=>'无记录']);
        }
    }

}
