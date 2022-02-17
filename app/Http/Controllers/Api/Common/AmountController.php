<?php
/**
 * 发票
 * 企业消费*
 */
namespace App\Http\Controllers\Api\Common;

use App\Http\Controllers\Controller;
use App\Models\Api\Market\MarketAmount;
use App\Models\Api\Market\MarketInvoice;
use App\Models\Api\Market\MarketInvoiceAmount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Api\Market\MarketCustomer;
use Illuminate\Support\Facades\Validator;
class AmountController extends Controller
{


    /**企业消费金额**/
    public function save_amount(Request $request){
        $messages = [
            'ddid.required' => '缺少ddid',
            'money.required' => '缺少金额',
            'time.required' => '缺少到账时间',
            'company.required' => '缺少到账公司',
            'to_xydm.required' => '缺少到账公司代码',
            'type.required' => '选择是否开票',
        ];
        $validator = Validator::make($request->all(), [
            'ddid' => 'required',
            'money' => 'required',
            'time' => 'required',
            'company' => 'required',
            'to_xydm' => 'required',
            'type' => 'required',
        ],$messages);

        if ($validator->fails()) {
            return response()->json(['msg' => $validator->errors()->first(),'status'=>101]);
        }
        //查询企业可开票额度
        $khxx=MarketInvoiceAmount::where('xydm',$request->xydm)
            ->where('to_xydm',$request->to_xydm)
            ->first();
        if(!$khxx){
            //不存在就创建
            MarketInvoiceAmount::create([
                'qymc' => $request->qymc,
                'xydm' => $request->xydm,
                'company' => $request->company,
                'to_xydm' => $request->to_xydm,
            ]);
        }
        if($request->amount_id){
            $amount = MarketAmount::find($request->amount_id);
            if(!$amount){
                return response()->json(['status'=>101,'msg'=>'该数据不存在']);
            }
            if($amount->state==1){
                return response()->json(['status'=>101,'msg'=>'该状态不允许修改']);
            }
        }else{
            $amount=new MarketAmount;
            $amount->user_name=$request->user_name;
        }
        $amount->ddid=$request->ddid;
        $amount->qymc=$request->qymc;
        $amount->xydm=$request->xydm;
        $amount->money=$request->money;
        $amount->time=$request->time;
        $amount->company=$request->company;
        $amount->to_xydm=$request->to_xydm;
        $amount->type=$request->type;
        $amount->remarks=$request->remarks;
        if($amount->save()){
            return response()->json(['status'=>100,'msg'=>'成功']);
        }else{
            return response()->json(['status'=>101,'msg'=>'失败']);
        }
    }

    /**企业消费金额列表**/
    public function list_amount(Request $request){
        $ddid=$request->input('ddid');
        if(!$ddid){
            return response()->json(['status'=>101,'msg'=>'缺少ddid']);
        }
        $where[]=['ddid','=',$ddid];
        if($request->qymc){
            $where[]=['qymc','like','%'.$request->qymc.'%'];
        }
        if($request->xydm){
            $where[]=['xydm','=',$request->xydm];
        }

        $res = MarketAmount::index($where,$request->limit);
        if($res->first()){
            return response()->json(['status'=>100,'msg'=>'请求成功','data'=>$res]);
        }else{
            return response()->json(['status'=>101,'msg'=>'无记录']);
        }
    }
    /**企业消费金额 删除**/
    public function del_amount(Request $request){
        $amount_id=$request->input('amount_id');
        if(!$amount_id){
            return response()->json(['status'=>101,'msg'=>'缺少金额id']);
        }
        $amount = MarketAmount::find($amount_id);
        if(!$amount){
            return response()->json(['status'=>101,'msg'=>'不存在该信息']);
        }
        if($amount->state==1){
            return response()->json(['status'=>101,'msg'=>'该状态无法删除']);
        }
        $amount->delete();
        return response()->json(['status'=>100,'msg'=>'删除成功']);
    }

    /**企业发票申请**/
    public function save_invoice(Request $request){
        $messages = [
            'ddid.required' => '缺少ddidid',
            'kh_name.required' => '缺少企业名称',
            'tax_no.required' => '缺少识别号',
            'address.required' => '缺少地址',
            'phone.required' => '缺少电话',
            'bank.required' => '缺少开户行',
            'account.required' => '缺少开户账号',
            'hw_name.required' => '缺少货物名称',
            'amount.required' => '缺少金额',
            'company.required' => '缺少开票公司',
            'to_xydm.required' => '缺少开票公司代码',
            'type.required' => '选择发票类型',
//            'remarks.required' => '缺少备注',
        ];
        $validator = Validator::make($request->all(), [
            'ddid' => 'required',
            'kh_name' => 'required',
            'tax_no' => 'required',
            'address' => 'required',
            'phone' => 'required',
            'bank' => 'required',
            'account' => 'required',
            'hw_name' => 'required',
            'amount' => 'required',
            'company' => 'required',
            'to_xydm' => 'required',
            'type' => 'required',
//            'remarks' => 'required',
        ],$messages);

        if ($validator->fails()) {
            return response()->json(['msg' => $validator->errors()->first(),'status'=>101]);
        }
        if($request->invoice_id){
            $amount = MarketInvoice::find($request->invoice_id);
            if(!$amount){
                return response()->json(['status'=>101,'msg'=>'该数据不存在']);
            }
            if($amount->state==1){
                return response()->json(['status'=>101,'msg'=>'该状态不允许修改']);
            }
        }else{
            $amount=new MarketInvoice;
            $amount->user_name=$request->user_name;;
        }
        //查询企业可开票额度
        $khxx=MarketInvoiceAmount::where('xydm',$request->tax_no)
            ->where('to_xydm',$request->to_xydm)
            ->first();
        if(!$khxx){
            //不存在就创建
            $khxx=MarketInvoiceAmount::create([
                'qymc' => $request->kh_name,
                'xydm' => $request->tax_no,
                'company' => $request->company,
                'to_xydm' => $request->to_xydm,
                'amount_n' => 0,
            ]);
        }
        if($request->amount>$khxx->amount_n){
            $amount->cate='先票后款';
        }else{
            $amount->cate='先款后票';
        }
        /*$khxx=MarketCustomer::select('amount_n')->find($request->khxx_id);
        if($request->amount>$khxx->amount_n){
            $amount->cate='先票后款';
        }else{
            $amount->cate='先款后票';
        }*/
        $amount->ddid=$request->ddid;
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
        $amount->to_xydm=$request->to_xydm;
        $amount->type=$request->type;
        $amount->remarks=$request->remarks;
        $amount->state=0;
        if($amount->save()){
            return response()->json(['status'=>100,'msg'=>'成功']);
        }else{
            return response()->json(['status'=>101,'msg'=>'失败']);
        }
    }

    /**企业发票申请列表**/
    public function list_invoice(Request $request){
        $ddid=$request->input('ddid');
        if(!$ddid){
            return response()->json(['status'=>101,'msg'=>'缺少ddid']);
        }
        $where[]=['ddid','=',$ddid];
        if($request->kh_name){
            $where[]=['kh_name','like','%'.$request->kh_name.'%'];
        }
        if($request->xydm){
            $where[]=['xydm','=',$request->xydm];
        }
        $res = MarketInvoice::index($where,$request->limit);
        if($res->first()){
            return response()->json(['status'=>100,'msg'=>'请求成功','data'=>$res]);
        }else{
            return response()->json(['status'=>101,'msg'=>'无记录']);
        }
    }
    /**企业发票 删除**/
    public function del_invoice(Request $request){
        $invoice_id=$request->input('invoice_id');
        if(!$invoice_id){
            return response()->json(['status'=>101,'msg'=>'缺少发票id']);
        }
        $invoice = MarketInvoice::find($invoice_id);
        if(!$invoice){
            return response()->json(['status'=>101,'msg'=>'不存在该信息']);
        }
        if($invoice->state>=1){
            return response()->json(['status'=>101,'msg'=>'该状态无法删除']);
        }
        $invoice->delete();
        return response()->json(['status'=>100,'msg'=>'删除成功']);
    }
    /**企业发票认领**/
    public function get_invoice(Request $request){
        $invoice_id=$request->input('invoice_id');
        if(!$invoice_id){
            return response()->json(['status'=>101,'msg'=>'缺少发票id']);
        }
        $invoice = MarketInvoice::find($invoice_id);
        if(!$invoice){
            return response()->json(['status'=>101,'msg'=>'不存在该信息']);
        }
        if($invoice->state!=1){
            return response()->json(['status'=>101,'msg'=>'该状态无法领取']);
        }
        $invoice->state = 2;
        $invoice->save();
        return response()->json(['status'=>100,'msg'=>'成功']);
    }
    /**发票申请列表 个人userid**/
    public function userInvoice(Request $request){
        $userid=$request->input('userid');
        if(!$userid){
            return response()->json(['status'=>101,'msg'=>'缺少userid']);
        }
        $where[]=['userid','=',$request->userid];
        if($request->kh_name){
            $where[]=['kh_name','like','%'.$request->kh_name.'%'];
        }
        $res = MarketInvoice::index($where,$request->limit);
        if($res->first()){
            return response()->json(['status'=>100,'msg'=>'请求成功','data'=>$res]);
        }else{
            return response()->json(['status'=>101,'msg'=>'无记录']);
        }
    }
}
