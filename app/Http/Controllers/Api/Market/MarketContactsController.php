<?php

namespace App\Http\Controllers\Api\Market;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Api\Market\MarketContacts;
use App\Models\Api\Market\MarketContactsType;
use Illuminate\Support\Facades\DB;

class MarketContactsController extends Controller
{
    /**客户联系人**/
    public function ContactsIndex(Request $request){
        $where = array(
            ['kh_id','=',$request->id],
        );
        $file = array(
            'contacts.id as id',
            'name',
            'phone',
            'job',
            'tell',
            'weixin',
            'qq',
            'mail',
            'type',
            'contacts.state as state',
            'contacts_type.state as type_state',
        );
        $flighs= MarketContacts::IndexContacts($file,$where);
        if($flighs->isEmpty()){
            return response()->json(['status'=>101,'msg'=>'无数据']);
        }
        return response()->json(['status'=>100,'msg'=>'请求成功','data'=>$flighs]);
    }

    /**联系人类型**/
    public function ContactsType(Request $request){
        $flighs= MarketContactsType::
            where('state', '=', 1)
            ->get();
        if($flighs->isEmpty()){
            return response()->json(['status'=>101,'msg'=>'无数据']);
        }
        return response()->json(['status'=>100,'msg'=>'请求成功','data'=>$flighs]);
    }

    /**联系人添加**/
    public function ContactsAdd(Request $request){
        $data = MarketContacts::FormValidation($request);
        if($data['type'] == false){
            return response()->json(['status'=>101,'msg'=>$data['error']]);
        }
        $flights = new MarketContacts;
        $flights->name = $request->name;
        $flights->phone= $request->phone;
        $flights->job  = $request->job;
        $flights->tell = $request->tell;
        $flights->weixin = $request->weixin;
        $flights->qq   = $request->qq;
        $flights->mail = $request->mail;
        $flights->type = $request->type;
        $flights->kh_id= $request->id;
        if($flights->save()){
            return response()->json(['status'=>100,'msg'=>'添加成功','data'=>$flights]);
        }else{
            return response()->json(['status'=>101,'msg'=>'添加失败']);
        }
    }

    /**联系人修改**/
    public function ContactsEdit(Request $request){
        $data = MarketContacts::FormValidation($request);
        if($data['type'] == false){
            return response()->json(['status'=>101,'msg'=>$data['error']]);
        }
        $flights = MarketContacts::find($request->id);
        $flights->name = $request->name;
        $flights->phone= $request->phone;
        $flights->job  = $request->job;
        $flights->tell = $request->tell;
        $flights->weixin = $request->weixin;
        $flights->qq   = $request->qq;
        $flights->mail = $request->mail;
        $flights->type = $request->type;
        //return($request);
        if($flights->save()){
            return response()->json(['status'=>100,'msg'=>'修改成功','data'=>$flights]);
        }else{
            return response()->json(['status'=>101,'msg'=>'修改失败']);
        }
    }

    /**默认联系人**/
    public function ContactsState(Request $request){
        DB::beginTransaction();
        try {
            $flights = MarketContacts::find($request->id);
            $flights->state = 1;
            $flights->save();
            $where = array(
                ['kh_id', '=', $request->kh_id],
                ['id', '<>', $request->id],
            );
            $data = array(
                'state' => 0,
            );
            MarketContacts::where($where)
                ->update($data);
            DB::commit();
        }catch (\Exception $e){
            DB::rollback();
            return response()->json(['status'=>101,'msg'=>'设置失败']);
        }
        return response()->json(['status'=>100,'msg'=>'设置成功']);
    }
}
