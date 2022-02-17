<?php

namespace App\Http\Controllers\Api\Approval;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Api\Approval\ApprovalApply;
use App\Models\Api\Approval\ApprovalType;
use App\Models\Api\Approval\ApprovalNode;
use App\Models\Api\Inspect\InspectPlan;
use App\Models\Api\User\UserBasic;
use Illuminate\Support\Facades\DB;

class ApprovalApplyController extends Controller
{
    /**申请审批**/
    public function ApplyAdd(Request $request)
    {
        if(!$request->tid){
            return response()->json(['status' => 101, 'msg' => '没有选择审批类型']);
        }
        $flights = InspectPlan::find($request->apply_object);
        switch ($request->tid)
        {
            case 1:
                if($flights->audit_phase != '05' || $flights->change_type != 1){
                    return response()->json(['status' => 101, 'msg' => '不是暂停撤销阶段，不能进行暂停撤销申请']);
                }
                if($flights->adopt_sbmt == 1){
                    return response()->json(['status' => 101, 'msg' => '该阶段已通过审批，不能再进行申请']);
                }
                break;
            case 2:
                if($flights->rept_sbmt == 0){
                    return response()->json(['status' => 101, 'msg' => '项目计划未提交，不能进行修改申请']);
                }
                if($flights->dp_jdct != 0){
                    return response()->json(['status'=>101,'msg'=>'该项目有其他人员正在修改，请稍后再申请']);
                }
                break;
            case 3:
                if($flights->dp_sbmt == 0){
                    return response()->json(['status' => 101, 'msg' => '项目复核未提交，不能进行修改申请']);
                }
                if($flights->dp_jdct != 0){
                    return response()->json(['status'=>101,'msg'=>'该项目有其他人员正在修改，请稍后再申请']);
                }
                break;
            case 4:
                if($flights->evte_sbmt == 0){
                    return response()->json(['status' => 101, 'msg' => '评定安排未提交，不能进行修改申请']);
                }
                if($flights->dp_jdct != 0){
                    return response()->json(['status'=>101,'msg'=>'该项目有其他人员正在修改，请稍后再申请']);
                }
                break;
            case 5:
                if($flights->cert_sbmt == 0){
                    return response()->json(['status' => 101, 'msg' => '技术评定未完成，不能进行证书打印申请']);
                }
                if($flights->adopt_sbmt == 1){
                    return response()->json(['status' => 101, 'msg' => '该阶段已通过审批，不能再进行申请']);
                }
                break;
            case 6:
                if($flights->print_sbmt == 0){
                    return response()->json(['status' => 101, 'msg' => '证书打印未完成，不能进行修改申请']);
                }
                if($flights->dp_jdct != 0){
                    return response()->json(['status'=>101,'msg'=>'该项目有其他人员正在修改，请稍后再申请']);
                }
                break;
            case 7:
                if($flights->cert_sbmt == 0){
                    return response()->json(['status' => 101, 'msg' => '技术评定未完成，不能进行修改申请']);
                }
                if($flights->dp_jdct != 0){
                    return response()->json(['status'=>101,'msg'=>'该项目有其他人员正在修改，请稍后再申请']);
                }
                break;
        }
        $file = array(
            'type_user',
        );
        $where = array(
            ['id',$request->tid]
        );
        $flighs = ApprovalType::TypeIndex($file,$where);
        if ($flighs->isEmpty()) {
            return response()->json(['status' => 101, 'msg' => '审批发起人为空，请联系管理员']);
        }
        $cltUser= explode(";", $flighs->first()->type_user);
        $userId = Auth::guard('api')->user()->id;
        if(!in_array($userId,$cltUser)){
            return response()->json(['status' => 101, 'msg' => '您没有权限申请该审批，请联系管理员赋予权限']);
        }
        $file = array(
            'node_type',
            'node_user',
        );
        $where = array(
            ['type_id',$request->tid]
        );
        $flight = ApprovalNode::NodeIndex($file,$where);
        if ($flight->isEmpty()) {
            return response()->json(['status' => 101, 'msg' => '没有设置完整的审批流程']);
        }

        $record['data'][] = [
            'userId'   =>Auth::guard('api')->user()->id,
            'state'    =>1,
            'userName'=>Auth::guard('api')->user()->name,
            'userType'=>'00',
            'userTime'=>date("Y-m-d H:i:s"),
        ];
        $record['handle']= '';
        $record['share'] = '';
        $flight = $flight->toArray();
        array_walk($flight, function ($value,$key) use (&$record){
            $file = array(
                'id',
                'name',
            );
            if($value['node_type'] == '01'){
                if($record['handle'] == ''){
                    $record['handle'] = $value['node_user'];
                    $state = 3;
                }else{
                    $state = 0;
                }
                $where = array(
                    ['id',$value['node_user']]
                );
                $userName = UserBasic::MentBasic($file,$where);
                $record['data'][] = [
                    'userId'  =>$value['node_user'],
                    'state'   =>$state,
                    'userName'=>$userName->first()->name,
                    'userType'=>$value['node_type'],
                    'userTime'=>'',
                ];
            }else{
                if(strpos($value['node_user'],';') !== false){
                    $nodeUser = explode(';',$value['node_user']);
                    $userName = UserBasic::IndexBasic($file,'id',$nodeUser);
                    $userName = $userName->toArray();
                    array_walk($userName, function ($value,$key,$node) use (&$share){
                        $share['share'][] = [
                            'userId'  =>$value['id'],
                            'state'   =>0,
                            'userName'=>$value['name'],
                            'userType'=>$node['node_type'],
                            'userTime'=>'',
                        ];
                    },$value);
                    $record['data'][] = $share;
                }else{
                    $where = array(
                        ['id',$value['node_user']]
                    );
                    $userName = UserBasic::MentBasic($file,$where);
                    $record['data'][] = [
                        'userId'  =>$value['node_user'],
                        'state'   =>0,
                        'userName'=>$userName->first()->name,
                        'userType'=>$value['node_type'],
                        'userTime'=>'',
                    ];
                }
                if($record['share'] == ''){
                    $record['share'] = ';'.$value['node_user'].';';
                }else{
                    $record['share'] .= $value['node_user'].';';
                }
            }
        });
        DB::beginTransaction();
        try {
            switch ($request->tid){
                case 1:
                    $flighs = $this->ApplyOperation($request->tid,$request->apply_object,3);
                    if($flighs == false){
                        return response()->json(['status'=>101,'msg'=>'发起审批失败']);
                    }
                    break;
            }
            $flights = new ApprovalApply;
            $flights->apply_code  = time().mt_rand(1000,9999);
            $flights->apply_title = Auth::guard('api')->user()->name.'提交的'.$request->type_name;
            $flights->apply_content = $request-> corporate.'"'.$request->system.'":'.$request->contents;
            $flights->apply_object= $request->apply_object;
            $flights->apply_type  = $request->tid;
            $flights->apply_node  = json_encode($record['data']);
            $flights->apply_user  = Auth::guard('api')->user()->id;
            $flights->apply_handle= $record['handle'];
            //$flights->apply_record= json_encode($record);
            $flights->apply_later = 1;
            $flights->share_id    = $record['share'];
            if(!$flights->save()){
                DB::rollback();
                return response()->json(['status'=>101,'msg'=>'申请失败']);
            }
            DB::commit();
        }catch (\Exception $e){
            DB::rollback();
            return response()->json(['status'=>101,'msg'=>'评定提交失败']);
        }
        return response()->json(['status'=>100,'msg'=>'申请成功']);
    }

    /**审批列表**/
    public function ApplyIndex(Request $request)
    {
        switch ($request->type)
        {
            case '01':
               $where = [
                   ['apply_handle',Auth::guard('api')->user()->id],
                   ['apply_state',0],
               ];
                break;
            case '00':
                $where = [
                    ['apply_user',Auth::guard('api')->user()->id],
                ];
                break;
            case '02':
                $where = [
                    ['share_id','like','%;'.Auth::guard('api')->user()->id.';%'],
                ];
                break;
            case '03':
                $where = [
                    ['apply_id','like','%;'.Auth::guard('api')->user()->id.';%'],
                ];
                break;
            default:
                $where = [
                    ['apply_handle',Auth::guard('api')->user()->id],
                    ['apply_state',0],
                ];
        }
        $flights = $this->ApplyShare($where,$request->limit,$request->field,$request->sort);
        return($flights);
    }

    /**审批查询**/
    public function ApplyQuery(Request $request){
        switch ($request->type)
        {
            case '01':
                $where = [
                    ['apply_handle',Auth::guard('api')->user()->id],
                    ['apply_state',0],
                ];
                break;
            case '00':
                $where = [
                    ['apply_user',Auth::guard('api')->user()->id],
                ];
                break;
            case '02':
                $where = [
                    ['share_id','like','%;'.Auth::guard('api')->user()->id.';%'],
                ];
                break;
            case '03':
                $where = [
                    ['apply_id','like','%;'.Auth::guard('api')->user()->id.';%'],
                ];
                break;
            default:
                $where = [
                    ['apply_handle',Auth::guard('api')->user()->id],
                    ['apply_state',0],
                ];
        }

        if(!empty($request->title)){
            $where[] = ['apply_title','like', '%'.$request->title.'%'];
        }

        if(!empty($request->tid)){
            $where[] = ['apply_type',$request->tid];
        }

        $time = array();

        if(!empty($request->crst) && !empty($request->cret)){//发起时间
            $time['created_at'] = [$request->crst,$request->cret];
        }

        if(!empty($request->cpst) && !empty($request->cpet)){//完成时间
            $time['complete_at'] = [$request->cpst,$request->cpet];
        }

        $flighs = $this->ApplyShare($where,$request->limit,$request->field,$request->sort,$time);
        return($flighs);
    }

    /**查询函数**/
    public function ApplyShare($where,$limit,$sortField,$sort,$time=''){
        $file = array(
            'id',
            'apply_title',
            'apply_content',
            'created_at',
            'complete_at',
            'apply_state',
        );
        switch ($sortField)
        {
            case 1:
                $sortField = 'created_at';
                break;
            case 2:
                $sortField = 'updated_at';
                break;
            default:
                $sortField = 'created_at';
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
        $flights = ApprovalApply::ApplyIndex($file,$where,$limit,$sortField,$sort,$time);
        if($flights->isEmpty()){
            return response()->json(['status'=>101,'msg'=>'无数据']);
        }
        $flights = $flights->toArray();
        array_walk($flights['data'], function (&$value,$key) {
            switch ($value['apply_state']) {
                case 0:
                    $value['apply_state'] = '审批中';
                    break;
                case 1:
                    $value['apply_state'] = '未通过';
                    break;
                case 2:
                    $value['apply_state'] = '已通过';
                    break;
                case 3:
                    $value['apply_state'] = '已撤销';
                    break;
            }
        });
        return response()->json(['status'=>100,'msg'=>'请求成功','data'=>$flights]);
    }

    /**审批详情**/
    public function ApplyDetail(Request $request)
    {
        $file = array(
            'aprv_apply.id as id',
            'apply_code',
            'apply_title',
            'type_name',
            'apply_content',
            'aprv_apply.created_at as created_at',
            'apply_state',
            'apply_node',
            'read_id',
        );
        $where = [
            ['aprv_apply.id',$request->id],
        ];
        $flights = ApprovalApply::ApplyDetail($file,$where);
        if($flights->isEmpty()){
            return response()->json(['status'=>101,'msg'=>'无数据']);
        }
        switch ($flights->first()->apply_state) {
            case 0:
                $flights->first()->state_name = '审批中';
                break;
            case 1:
                $flights->first()->state_name = '未通过';
                break;
            case 2:
                $flights->first()->state_name = '已通过';
                break;
            case 3:
                $flights->first()->state_name = '已撤销';
                break;
        }
        $flights->first()->apply_node = json_decode($flights->first()->apply_node,true);
        $userId  = Auth::guard('api')->user()->id;
        $readUser= explode(';',$flights->first()->read_id);
        if($request->type == '02'){
            if(!in_array($userId,$readUser)){
                $userNode = array();
                foreach ($flights->first()->apply_node as $key => $value){
                    if(isset($value['share'])){
                        $share = array();
                        foreach ($value['share'] as $keyy => $valuee){
                            if(($userId == $valuee['userId']) && ($valuee['userType'] == '02')){
                                $valuee['state']    = 3;
                                $valuee['userTime'] = date("Y-m-d H:i:s");
                            };
                            $share[] = $valuee;
                        }
                        $userNode[]['share'] = $share;
                    }else{
                        if(($userId == $value['userId']) && ($value['userType'] == '02')){
                            $value['state'] = 3;
                            $value['userTime'] = date("Y-m-d H:i:s");
                        };
                        $userNode[] = $value;
                    }
                }
                $flight = ApprovalApply::find($request->id);
                $flight->apply_node = json_encode($userNode);
                $flight->read_id    = $flights->first()->read_id.$userId.';';
                $flight->save();
            }
        }
        return response()->json(['status'=>100,'msg'=>'请求成功','data'=>$flights]);
    }

    /**审批撤销**/
    public function ApplyRevoke(Request $request)
    {
        $flight = ApprovalApply::find($request->id);
        if($flight->apply_state !== 0){
            return response()->json(['status'=>101,'msg'=>'该审批不能进行撤销']);
        }
        $node = json_decode($flight->apply_node,true);
        $userId  = Auth::guard('api')->user()->id;
        if($userId !== $flight->apply_user) {
            return response()->json(['status'=>101,'msg'=>'审批发起人不匹配']);
        }
        $userNode = array();
        foreach ($node as $key => $value) {
            if(!isset($value['share'])){
                if (($userId == $value['userId']) && ($value['userType'] == '00')) {
                    $value['state'] = 0;
                    $value['userTime'] = date("Y-m-d H:i:s");
                };
            }
            $userNode[] = $value;
        }
        $flight->apply_node = json_encode($userNode);
        $flight->apply_state= 3;
        if(!$flight->save()){
            return response()->json(['status'=>101,'msg'=>'撤销失败']);
        }
        return response()->json(['status'=>100,'msg'=>'撤销成功']);
    }

    /**审批通过**/
    public function ApplyAdopt(Request $request)
    {
        $flight = ApprovalApply::find($request->id);
        if($flight->apply_state == 2){
            return response()->json(['status'=>101,'msg'=>'该审批已通过，不能进行二次审批']);
        }
        $node  = json_decode($flight->apply_node,true);
        $userId= Auth::guard('api')->user()->id;
        if($userId !== $flight->apply_handle) {
            return response()->json(['status'=>101,'msg'=>'审批人不匹配']);
        }
        $applyNode = array_filter($node,function ($value){
            if(!isset($value['share'])){
                if (($value['state'] == 3) && ($value['userType'] =='01')) {
                    return ($value);
                }
            }
        },ARRAY_FILTER_USE_BOTH);
        $userKey = key($applyNode);
        if($node[$userKey]['userId'] != $userId){
            return response()->json(['status'=>101,'msg'=>'当前审批人与流程不匹配，请联系管理员']);
        }
        $node[$userKey]['state'] = 2;
        $node[$userKey]['userTime'] = date("Y-m-d H:i:s");
        $nextUser = array_filter($node,function ($value){
            if(!isset($value['share'])){
                if (($value['state'] == 0) && ($value['userType'] =='01')) {
                    return ($value);
                }
            }
        },ARRAY_FILTER_USE_BOTH);
        DB::beginTransaction();
        try {
            if(empty($nextUser)){
                $flight->apply_state = 2;
                $flight->complete_at = date("Y-m-d H:i:s");
                $flighs = InspectPlan::find($flight->apply_object);
                switch ($flight->apply_type){
                    case 1:
                        $flighs->adopt_sbmt = 1;
                        $flighs->adopt_user = Auth::guard('api')->user()->name;
                        $flighs->adopt_time = date("Y-m-d");
                        break;
                    case 2:
                        if($flighs->dp_jdct != 0){
                            return response()->json(['status'=>101,'msg'=>'该项目有其他人员正在修改，请稍后再操作']);
                        }
                        $flighs->dp_jdct = 1;
                        $flighs->rept_sbmt = 3;
                        break;
                    case 3:
                        if($flighs->dp_jdct != 0){
                            return response()->json(['status'=>101,'msg'=>'该项目有其他人员正在修改，请稍后再操作']);
                        }
                        $flighs->dp_jdct = 2;
                        if($flighs->result_sbmt == 1){
                            $flighs->result_sbmt = 3;
                        }
                        break;
                    case 4:
                        if($flighs->dp_jdct !== 0){
                            return response()->json(['status'=>101,'msg'=>'该项目有其他人员正在修改，请稍后再操作']);
                        }
                        $flighs->dp_jdct = 3;
                        if($flighs->result_sbmt == 1){
                            $flighs->result_sbmt = 3;
                        }
                        break;
                    case 5:
                        if($flighs->adopt_sbmt != 0){
                            return response()->json(['status'=>101,'msg'=>'该项目已经通过批准了，无需多次提交']);
                        }
                        $flighs->adopt_user = Auth::guard('api')->user()->name;
                        $flighs->adopt_time = date("Y-m-d");
                        $flighs->adopt_sbmt = 1;
                        break;
                    case 6:
                        if($flighs->dp_jdct !== 0){
                            return response()->json(['status'=>101,'msg'=>'该项目有其他人员正在修改，请稍后再操作']);
                        }
                        $flighs->dp_jdct = 5;
                        if($flighs->result_sbmt == 1){
                            $flighs->result_sbmt = 3;
                        }
                        break;
                    case 7:
                        if($flighs->dp_jdct !== 0){
                            return response()->json(['status'=>101,'msg'=>'该项目有其他人员正在修改，请稍后再操作']);
                        }
                        $flighs->dp_jdct = 4;
                        if($flighs->result_sbmt == 1){
                            $flighs->result_sbmt = 3;
                        }
                        break;
                }
                if(!$flighs->save()){
                    return response()->json(['status'=>101,'msg'=>'审批通过失败']);
                }
            }else{
                $nextKey = key($nextUser);
                $node[$nextKey]['state'] = 3;
                $flight->apply_handle= $node[$nextKey]['userId'];
            }
            $flight->apply_node = json_encode($node);
            if($flight->apply_id == ''){
                $flight->apply_id = ';'.$userId.';';
            }else{
                $flight->apply_id .= $userId.';';
            }
            if(!$flight->save()){
                DB::rollback();
                return response()->json(['status'=>101,'msg'=>'审批通过失败']);
            }
            DB::commit();
        }catch (\Exception $e){
            DB::rollback();
            return response()->json(['status'=>101,'msg'=>'审批通过失败']);
        }
        return response()->json(['status'=>100,'msg'=>'审批通过成功']);
    }

    /**审批拒绝**/
    public function ApplyRefuse(Request $request)
    {
        $flight = ApprovalApply::find($request->id);
        if($flight->apply_state !== 0){
            return response()->json(['status'=>101,'msg'=>'该审批不能进行操作']);
        }
        $userNode= json_decode($flight->apply_node,true);
        $userId  = Auth::guard('api')->user()->id;
        if($userId !== $flight->apply_handle) {
            return response()->json(['status'=>101,'msg'=>'审批人不匹配']);
        }
        $record = [
            'userId'   =>Auth::guard('api')->user()->id,
            'state'    =>3,
            'userName'=>Auth::guard('api')->user()->name,
            'userType'=>'01',
            'userTime'=>'',
        ];
        $key = array_search($record,$userNode);
        if($key == false){
            return response()->json(['status'=>101,'msg'=>'不能进行重复审批']);
        }
        DB::beginTransaction();
        try {
            switch ($flight->apply_type){
                case 1:
                    $flighs = $this->ApplyOperation($flight->apply_type,$flight->apply_object,2);
                    if($flighs == false){
                        return response()->json(['status'=>101,'msg'=>'发起审批失败']);
                    }
                    break;
            }
            $userNode[$key]['state'] = 1;
            $userNode[$key]['userTime'] = date("Y-m-d H:i:s");
            $flight->apply_node = json_encode($userNode);
            $flight->apply_state= 1;
            if($flight->apply_id == ''){
                $flight->apply_id = ';'.$userId.';';
            }else{
                $flight->apply_id .= $userId.';';
            }
            if(!$flight->save()){
                DB::rollback();
                return response()->json(['status'=>101,'msg'=>'审批拒绝失败']);
            }
            DB::commit();
        }catch (\Exception $e){
            DB::rollback();
            return response()->json(['status'=>101,'msg'=>'审批拒绝失败']);
        }
        return response()->json(['status'=>100,'msg'=>'审批拒绝成功']);
    }

    /**审批操作**/
    protected  function ApplyOperation($applyType,$applyObject,$applyState){
        switch ($applyType){
            case 1:
                $flighs = InspectPlan::find($applyObject);
                $flighs->adopt_sbmt = $applyState;
                if(!$flighs->save()){
                    return false;
                }
                break;
        }
        return true;
    }
}
