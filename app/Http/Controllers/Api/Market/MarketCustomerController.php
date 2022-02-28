<?php

namespace App\Http\Controllers\Api\Market;

use App\Http\Controllers\Controller;
use App\Models\Api\Market\MarketAmount;
use App\Models\Api\Market\MarketAmountChange;
use App\Models\Api\Market\MarketInvoiceAmount;
use App\Models\Api\Market\MarketInvoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Api\Market\MarketCustomer;
use App\Models\Api\Market\MarketNature;
use App\Models\Api\Market\MarketType;
use App\Models\Api\Market\MarketRegion;
use App\Models\Api\Examine\ExamineMoneyType;
use App\Models\Api\User\UserMent;
use App\Models\Api\User\UserBasic;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
class MarketCustomerController extends Controller
{
    /**我的客户**/
    public function CustomerIndex(Request $request){
        $userId = Auth::guard('api')->id();
        $file = array(
            'khxx.id',
            'qymc',
            'xydm',
            'khxx_nature.nature',
            'khxx_type.type',
            'bgdz',
            'scjl',
            'amount',
            'amount_y',
            'amount_n',
            'fzjg.fz_name',
            'cj_time',
            'gx_time'
        );
        $where = array(
            ['scjl','like','%;'.$userId.';%'],
        );
        if($request->name){
            $where[] = ['khxx.qymc', 'like','%'.$request->name.'%'];
        }

        if($request->type){
            $where[] = ['khxx.khlx', '=',$request->type];
        }

        if($request->nature){
            $where[] = ['khxx.qyxz', '=',$request->nature];
        }

        if($request->region){
            $where[] = ['khxx.fzjg', '=',$request->region];
        }

        $time = array();
        if($request->ustime){
            $time['khxx.gx_time'] = [$request->ustime,$request->uetime];
        }
        if($request->astime){
            $time['khxx.cj_time'] = [$request->astime,$request->aetime];
        }
        $flighs= $this->CustomerShare($file,$where,$request->limit,$request->field,$request->sort,$time);
        return ($flighs);
    }

    /**全部客户**/
    public function CustomerAll(Request $request){
        $file = array(
            'khxx.id',
            'qymc',
            'xydm',
            'khxx_nature.nature',
            'khxx_type.type',
            'bgdz',
            'scjl',
            'fzjg.fz_name',
            'cj_time',
            'gx_time'
        );
        if($request->userid){
            $where = array(
                ['scjl','like','%;'.$request->userid.';%'],
            );
        }else{
            $where = array(
                ['khxx.id','!=',0],
            );
        }
        if($request->name){
            $where[] = ['khxx.qymc', 'like','%'.$request->name.'%'];
        }

        if($request->type){
            $where[] = ['khxx.khlx', '=',$request->type];
        }

        if($request->nature){
            $where[] = ['khxx.qyxz', '=',$request->nature];
        }

        if($request->region){
            $where[] = ['khxx.fzjg', '=',$request->region];
        }
        $time = array();
        if($request->ustime){
            $time['khxx.gx_time'] = [$request->ustime,$request->uetime];
        }
        if($request->astime){
            $time['khxx.cj_time'] = [$request->astime,$request->aetime];
        }
        $flighs= $this->CustomerShare($file,$where,$request->limit,$request->field,$request->sort,$time);
        return ($flighs);
    }

    /**客户查询**/
    public function CustomerQuery(Request $request){
        $file = array(
            'khxx.id',
            'qymc',
            'xydm',
            'khxx_nature.nature',
            'khxx_type.type',
            'bgdz',
            'scjl',
            'fzjg.fz_name',
            'cj_time',
            'gx_time'
        );
        switch ($request->scene)
        {
            case 1:
                $userId = Auth::guard('api')->id();
                $where = array(
                    ['scjl','like','%;'.$userId.';%'],
                );
                break;
            case 2:
                if(!empty($request->userid)){
                    $where = array(
                        ['scjl','like','%;'.$request->userid.';%'],
                    );
                }else{
                    $where = array(
                        ['khxx.id','!=',0],
                    );
                }
                break;
            default:
                return response()->json(['status'=>101,'msg'=>'筛选场景错误']);
        };

        if($request->name){
            $where[] = ['khxx.qymc', 'like','%'.$request->name.'%'];
        }

        if($request->type){
            $where[] = ['khxx.khlx', '=',$request->type];
        }

        if($request->nature){
            $where[] = ['khxx.qyxz', '=',$request->nature];
        }

        if($request->region){
            $where[] = ['khxx.fzjg', '=',$request->region];
        }

        $time = array();
        if($request->ustime){
            $time['khxx.gx_time'] = [$request->ustime,$request->uetime];
        }
        if($request->astime){
            $time['khxx.cj_time'] = [$request->astime,$request->aetime];
        }
        $flighs= $this->CustomerShare($file,$where,$request->limit,$request->field,$request->sort,$time);
        return ($flighs);
    }

    /**查询函数**/
    public function CustomerShare($file,$where,$limit,$sortField,$sort,$time=''){
        switch ($sortField)
        {
            case 1:
                $sortField = 'khxx.gx_time';
                break;
            case 2:
                $sortField = 'khxx.cj_time';
                break;
            default:
                $sortField = 'khxx.gx_time';
        }

        switch ($sort)
        {
            case 1:
                $sort = 'desc';
                break;
            case 2:
                $sort = 'asc';
                break;
            default:
                $sort = 'desc';
        }
        $flighs= MarketCustomer::QueryCustomer($file,$where,$limit,$sortField,$sort,$time);
        if($flighs->isEmpty()){
            return response()->json(['status'=>101,'msg'=>'无数据']);
        }
        foreach ($flighs as $data){
            $data->user_id  = substr($data->scjl,1,-1);
            $clientUser= explode(";",$data->user_id);
            $UserArray = UserBasic::IndexBasic('name','id',$clientUser);
            $UserArray = $UserArray->toArray();
            $data->scjl = implode(";",array_column($UserArray,'name'));
        }
        return response()->json(['status'=>100,'msg'=>'请求成功','data'=>$flighs]);
    }

    /**添加客户**/
    public function CustomerBasic(Request $request){
        $nature = MarketNature::
            where('state', '=', 1)
            ->get();
        $data['nature'] = $nature;

        $type = MarketType::
            where('state', '=', 1)
            ->get();
        $data['type'] = $type;

        $region = MarketRegion::
            where('state', '=', 1)
            ->get();
        $data['region'] = $region;

        $money = ExamineMoneyType::
            where('state', '=', 1)
            ->get();
        $data['money'] = $money;
/*        $mentWhere= array(
            ['pid','=',0],
        );
        $mentFile = array(
            'id',
            'bumen',
        );
        $ment = UserMent::IndexMent($mentFile,$mentWhere);
        $data['ment'] = $ment;*/
        return response()->json(['status'=>100,'msg'=>'请求成功','data'=>$data]);
    }

    /**保存客户信息**/
    public function CustomerAdd(Request $request){
        $data = MarketCustomer::FormValidation($request);
        if($data['type'] == false){
            return response()->json(['status'=>101,'msg'=>$data['error']]);
        }
        DB::beginTransaction();
        try {
            $flights = new MarketCustomer;
            $flights->qymc  = $request->name;
            $flights->frdb  = $request->legal;
            $flights->xydm  = $request->code;
            $flights->qyxz  = $request->nature;
            $flights->zczb_my = $request->money;
            $flights->zczb_bz = $request->currency;
            $flights->khwd  = $request->bank;
            $flights->yhzh  = $request->account;
            $flights->zcdz  = $request->register;
            $flights->zc_code= $request->register_code;
            $flights->bgdz  = $request->office;
            $flights->bg_code= $request->office_code;
            $flights->scdz  = $request->product;
            $flights->scdz_code= $request->product_code;
            $flights->postal= $request->postal;
            $flights->postal_code = $request->postal_code;
            $flights->gsdh  = $request->tell;
            $flights->gswz  = $request->site;
            $flights->qyrs  = $request->number;
            $flights->khlx  = $request->type;
            $flights->fzjg  = $request->region;
            $flights->scjl  = ';'.$request->person.';';
            $flights->bz    = $request->remark;
            $flights->cj_time = date('Y-m-d');
            $flights->gx_time = date('Y-m-d');
            $flights->save();
            DB::commit();
        }catch (\Exception $e){
            DB::rollback();
            return response()->json(['status'=>101,'msg'=>'添加失败/企业已存在']);
        }
/*        $record = [
            'userName'=>Auth::guard('api')->user()->name,
            'customer'=>$flights,
        ];
        Log::channel('customer_add')->info(response()->json($record)->setEncodingOptions(JSON_UNESCAPED_UNICODE));*/
        return response()->json(['status'=>100,'msg'=>'添加成功']);
    }

    /**客户详情**/
    public function CustomerDetails(Request $request){
        $file = array(
            'khxx.id',
            'qymc',
            'qymc_e',
            'frdb',
            'xydm',
            'qyxz',
            'khxx_nature.nature',
            'khxx_nature.state as nature_state',
            'zczb_my',
            'zczb_bz',
            'money_type.state as money_state',
            'khwd',
            'yhzh',
            'zcdz',
            'zcdz_e',
            'zc_code',
            'bgdz',
            'bgdz_e',
            'bg_code',
            'scdz',
            'scdz_e',
            'scdz_code',
            'postal',
            'postal_e',
            'postal_code',
            'gsdh',
            'gswz',
            'qyrs',
            'khlx',
            'khxx_type.type',
            'khxx_type.state as type_state',
            'scjl',
            'bz',
            'fzjg',
            'fzjg.fz_name',
            'fzjg.state as region_state',
            'khjl',
            'dqdm',
            'cj_time',
            'amount',
            'amount_y',
            'amount_n',
            'gx_time'
        );
        $where = array(
            ['khxx.id','=',$request->id],
        );
        $flighs= $this->CustomerShare($file,$where,1,'khxx.gx_time','desc');
        return ($flighs);
    }

    /**企业所有者**/
    public function CustomerOwner(Request $request){
        $flight = MarketCustomer::where('id',$request->id)
            ->value('scjl');
        $userId = substr($flight,1,-1);
        $cltUser= explode(";",$userId);
        $file = array(
            'id',
            'name'
            );
        $flights = UserBasic::IndexBasic($file,'id',$cltUser);
        if($flights->isEmpty()){
            return response()->json(['status'=>101,'msg'=>'无数据']);
        }
        return response()->json(['status'=>100,'msg'=>'请求成功','data'=>$flights]);
    }

    /**搜索所有者**/
    public function SearchOwner(Request $request){
        $userWhere= array(
            ['name','like','%'.$request->name.'%'],
            ['stop','=',1]
        );
        $userFile = array(
            'users.id as uid',
            'name',
            'bumen',
            'telephone',
        );
        $flights = UserBasic::SearchUser($userFile,$userWhere);
        if($flights->isEmpty()){
            return response()->json(['status'=>101,'msg'=>'无数据']);
        }
        return response()->json(['status'=>100,'msg'=>'搜索成功','data'=>$flights]);
    }

    /**企业分配**/
    public function OwnerAdd(Request $request){
        //$compId = json_decode($request->id,true );
        $compId = explode(";",$request->id);
        $flight = MarketCustomer::whereIn('id',$compId)
            ->select('id','scjl')
            ->get()
            ->toArray();
        $flighs = array();
        switch ($request->type)
        {
            case 0:
                array_walk($flight, function ($value,$key,$person) use (&$flighs) {
                    $fligh['id'] = $value['id'];
                    $cltUser = explode(";",$person);
                    $addUser = array_unique($cltUser);
                    $fligh['scjl'] = ';'.implode(";", $addUser) . ';';
                    $flighs[] = $fligh;
                },$request->person);
                break;
            case 1:
                array_walk($flight, function ($value,$key,$person) use (&$flighs) {
                    $fligh['id'] = $value['id'];
                    $value['scjl'] = substr($value['scjl'],1);
                    $cltUser = explode(";",$value['scjl'].$person);
                    $addUser = array_unique($cltUser);
                    $fligh['scjl'] = ';'.implode(";", $addUser) . ';';
                    $flighs[] = $fligh;
                },$request->person);
                break;
        }
        $flight= MarketCustomer::UpdateBatch($flighs);
        if($flight !== 0){
            return response()->json(['status'=>101,'msg'=>'修改成功']);
        }
        return response()->json(['status'=>100,'msg'=>'修改失败']);
    }

    /**修改所有者**/
    public function OwnerEdit(Request $request){
        $flights = MarketCustomer::find($request->id);
        $flights->scjl  = ';'.$request->person.';';
        if($flights->save()){
            return response()->json(['status'=>100,'msg'=>'修改成功','data'=>$flights]);
        }
        return response()->json(['status'=>101,'msg'=>'修改失败']);
    }

    /**修改客户信息**/
    public function CustomerEdit(Request $request){
        $data = MarketCustomer::FormValidation($request);
        if($data['type'] == false){
            return response()->json(['status'=>101,'msg'=>$data['error']]);
        }
        DB::beginTransaction();
        try {
            $date = array(
                'qymc'  => $request->name,
                'frdb'  => $request->legal,
                'xydm'  => $request->code,
                'qyxz'  => $request->nature,
                'zczb_my' => $request->money,
                'zczb_bz' => $request->currency,
                'khwd'  => $request->bank,
                'yhzh'  => $request->account,
                'zcdz'  => $request->register,
                'zc_code'=> $request->register_code,
                'bgdz'  => $request->office,
                'bg_code'=> $request->office_code,
                'scdz'  => $request->product,
                'scdz_code'=> $request->product_code,
                'postal'=> $request->postal,
                'postal_code' => $request->postal_code,
                'gsdh'  => $request->tell,
                'gswz'  => $request->site,
                'qyrs'  => $request->number,
                'khlx'  => $request->type,
                'fzjg'  => $request->region,
                'scjl'  => ';'.$request->person.';',
                'bz'    => $request->remark,
                'gx_time' => date('Y-m-d')
            );
            $where = array(
                ['id','=',$request->id],
                ['khjl','=',0],
            );
            $flighs = MarketCustomer::EditCustomer($where,$date);
            if($flighs == 0){
                return response()->json(['status'=>101,'msg'=>'没有权限修改']);
            }
            DB::commit();
        }catch (\Exception $e){
            DB::rollback();
            return response()->json(['status'=>101,'msg'=>'修改失败/企业名称已存在']);
        }
/*        $record = [
            'userName'=>Auth::guard('api')->user()->name,
            'customer'=>$data,
        ];
        Log::channel('customer_edit')->info(response()->json($record)->setEncodingOptions(JSON_UNESCAPED_UNICODE));*/
        return response()->json(['status'=>100,'msg'=>'修改成功']);

    }

    /**选择所有者**/
    public function CustomerPerson(Request $request){
        $mentWhere= array(
            ['pid','=',$request->id],
        );
        $mentFile = array(
            'id',
            'pid',
            'bumen',
            'email_id',
            'order',
        );
        $ment = UserMent::IndexMent($mentFile,$mentWhere);
        $data['ment'] = $ment;
        if($request->id !== '0'){
            $userWhere= array(
                ['bm_id','=',$request->id],
                ['stop','=',1]
            );
            $userFile = array(
                'id',
                'name',
                'telephone',
            );
            $user = UserBasic::MentBasic($userFile,$userWhere);
            $data['user'] = $user;
        }
        return response()->json(['status'=>100,'msg'=>'请求成功','data'=>$data]);
    }

    /**企业消费金额**/
    public function save_amount(Request $request){
        $messages = [
            'khxx_id.required' => '缺少企业id',
            'money.required' => '缺少金额',
            'time.required' => '缺少到账时间',
            'company.required' => '缺少到账公司',
            'to_xydm.required' => '缺少到账公司代码',
            'type.required' => '选择是否开票',
        ];
        $validator = Validator::make($request->all(), [
            'khxx_id' => 'required',
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
            $amount->user_name=Auth::guard('api')->user()->name;
        }
        $amount->khxx_id=$request->khxx_id;
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
            'khxx_id.required' => '缺少企业id',
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
            'khxx_id' => 'required',
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
            $amount->user_name=Auth::guard('api')->user()->name;
            $amount->userid=Auth::guard('api')->user()->id;
        }
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
    /**剩余可开票额度**/
    public function invoiceBalance(Request $request){
        $xydm=$request->input('xydm');
        $to_xydm=$request->input('to_xydm');
        if(!$xydm || !$to_xydm){
            return response()->json(['status'=>101,'msg'=>'缺少信用代码']);
        }
        $res=MarketInvoiceAmount::where('xydm',$xydm)->where('to_xydm',$to_xydm)->value('amount_n');
        if(!$res){
            return response()->json(['status'=>101,'msg'=>'无数据']);
        }
        return response()->json(['status'=>100,'msg'=>'请求成功','data'=>$res]);
    }
}
