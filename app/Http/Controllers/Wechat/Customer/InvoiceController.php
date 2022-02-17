<?php
/**
 * Created by PhpStorm.
 * User: Greatwall
 * Date: 2021/9/6
 * Time: 14:03
 */

namespace App\Http\Controllers\Wechat\Customer;

use App\Http\Controllers\Controller;
use App\Models\Wechat\Invoice;
use App\Models\Wechat\Khxx;
use Illuminate\Http\Request;
class InvoiceController  extends Controller
{
    /**企业发票申请**/
    public function add(Request $request){
        $amount=new Invoice;
        $khxx=Khxx::select('amount_n')->find($request->khxx_id);
        if(!$khxx){
            return response()->json(['status'=>101,'msg'=>'不存在该企业']);
        }
        if($request->amount>$khxx->amount_n){
            $amount->cate='先票后款';
        }else{
            $amount->cate='先款后票';
        }
        $amount->khxx_id=$request->khxx_id;
        $amount->kh_name=$request->kh_name;
        $amount->tax_no=$request->tax_no;
        $amount->address=$request->address;
        $amount->phone=$request->phone;
        $amount->bank=$request->bank;
        $amount->account=$request->account;
        $amount->hw_name=$request->hw_name;
        $amount->ggxh=$request->ggxh;
        $amount->unit=$request->unit;
        $amount->num=$request->num;
        $amount->price=$request->price;
        $amount->amount=$request->amount;
        $amount->company=$request->company;
        $amount->type=$request->type;
        $amount->remarks=$request->remarks;
        $amount->methods=2;//2表示微信端提交
        $amount->state=0;
        if($amount->save()){
            return response()->json(['status'=>100,'msg'=>'成功']);
        }else{
            return response()->json(['status'=>101,'msg'=>'失败']);
        }
    }
    /**企业发票申请列表**/
    public function list(Request $request){
        $kh_id=$request->input('kh_id');
        if(!$kh_id){
            return response()->json(['status'=>101,'msg'=>'缺少kh_id']);
        }
        $where[]=['khxx_id','=',$kh_id];
        $filed=[
            'id',
            'khxx_id',
            'company',
            'type',
            'amount',
            'wl_number',
            'state',
            'created_at',
        ];
        $res = Invoice::index($filed,$where,$request->limit);
        if(!$res->first()){
            return response()->json(['status'=>101,'msg'=>'无记录']);
        }
        return response()->json(['status'=>100,'msg'=>'请求成功','data'=>$res]);
    }
    /**企业发票申请详情**/
    public function detail(Request $request){
        $id=$request->input('id');
        if(!$id){
            return response()->json(['status'=>101,'msg'=>'缺少id']);
        }
        $res = Invoice::find($id);
        if(!$res){
            return response()->json(['status'=>101,'msg'=>'无记录']);
        }
        return response()->json(['status'=>100,'msg'=>'请求成功','data'=>$res]);
    }
}