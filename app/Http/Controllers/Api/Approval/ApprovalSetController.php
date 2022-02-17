<?php

namespace App\Http\Controllers\Api\Approval;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Api\Approval\ApprovalGroup;
use App\Models\Api\Approval\ApprovalType;
use App\Models\Api\Approval\ApprovalNode;
use App\Models\Api\User\UserBasic;
use Illuminate\Support\Facades\DB;

class ApprovalSetController extends Controller
{
    /**类型分组**/
    public function SetIndex(Request $request)
    {
        $file = array(
            'id',
            'group_name',
            'group_default',
        );
        $where = array(
            ['group_state',1]
        );
        $flight = ApprovalGroup::GroupIndex($file,$where);
        if ($flight->isEmpty()) {
            return response()->json(['status' => 101, 'msg' => '无数据']);
        }
        $flight = $flight->toArray();
        $file = array(
            'group_id as id',
            'id as tid',
            'type_name',
            'type_user',
            'type_icon',
            'type_explain',
            'updated_at',
            'type_state',
            'type_default'
        );
        $where = array(
            ['type_state',1]
        );
        $flighs = ApprovalType::TypeIndex($file,$where);
        $flighs = $flighs->toArray();
        array_walk($flighs, function ($value, $key)  use (&$flighl){
            $cltUser = explode(";", $value['type_user']);
            $file = array(
                'name'
            );
            $evteUser = UserBasic::IndexBasic($file, 'id', $cltUser);
            $evteUser = $evteUser->toArray();
            $value['user_name'] = implode(";", array_column($evteUser, 'name'));
            $flighl[] = $value;
        });
        $flight = array_merge($flight,$flighl);
        array_walk($flight, function ($value, $key)  use (&$flights){
            if (!isset($flights[$value['id']])) {
                $flights[$value['id']] = $value;
            }else{
                $flights[$value['id']]['type'][] = $value;
            }
        });
        return response()->json(['status' => 100, 'msg' => '请求成功', 'data' => array_values($flights)]);
    }

    /**审批列表**/
    public function GroupSet(Request $request)
    {
        $file = array(
            'id',
            'group_name',
            'group_default',
        );
        $where = array(
            ['group_state',1]
        );
        $flight = ApprovalGroup::GroupIndex($file,$where);
        if ($flight->isEmpty()) {
            return response()->json(['status' => 101, 'msg' => '无数据']);
        }
        $flight = $flight->toArray();
        $file = array(
            'group_id as id',
            'id as tid',
            'type_name',
            'type_user',
            'type_icon',
            'type_explain',
            'updated_at',
            'type_state',
            'type_default'
        );
        $where = array(
            ['type_state',1]
        );
        $flighs = ApprovalType::TypeIndex($file,$where);
        $flighs = $flighs->toArray();
        array_walk($flighs, function ($value, $key)  use (&$flighl){
            $cltUser = explode(";", $value['type_user']);
            $file = array(
                'name'
            );
            $evteUser = UserBasic::IndexBasic($file, 'id', $cltUser);
            $evteUser = $evteUser->toArray();
            $value['user_name'] = implode(";", array_column($evteUser, 'name'));
            $flighl[] = $value;
        });
        $flight = array_merge($flight,$flighl);
        array_walk($flight, function ($value, $key)  use (&$flights){
            if (!isset($flights[$value['id']])) {
                $flights[$value['id']] = $value;
            }else{
                $flights[$value['id']]['type'][] = $value;
            }
        });
        return response()->json(['status' => 100, 'msg' => '请求成功', 'data' => array_values($flights)]);
    }

    /**已停用类型**/
    public function GroupStop(Request $request){
        $file = array(
            'group_id as id',
            'id as tid',
            'type_name',
            'type_user',
            'type_icon',
            'type_explain',
            'updated_at',
            'type_state'
        );
        $where = array(
            ['type_state',0]
        );
        $flighs = ApprovalType::TypeIndex($file,$where);
        if ($flighs->isEmpty()) {
            return response()->json(['status' => 101, 'msg' => '无数据']);
        }
        $flighs = $flighs->toArray();
        array_walk($flighs, function (&$value, $key){
            $cltUser = explode(";", $value['type_user']);
            $file = array(
                'name'
            );
            $evteUser = UserBasic::IndexBasic($file, 'id', $cltUser);
            $evteUser = $evteUser->toArray();
            $value['user_name'] = implode(";", array_column($evteUser, 'name'));
        });
        return response()->json(['status' => 100, 'msg' => '请求成功', 'data' => $flighs]);
    }

    /**已启用类型**/
    public function GroupEnable(Request $request){
        $file = array(
            'id as tid',
            'type_name',
        );
        $where = array(
            ['type_state',1]
        );
        $flighs = ApprovalType::TypeIndex($file,$where);
        if ($flighs->isEmpty()) {
            return response()->json(['status' => 101, 'msg' => '无数据']);
        }
        return response()->json(['status' => 100, 'msg' => '请求成功', 'data' => $flighs]);
    }

    /**新建分组**/
    public function SetGroup(Request $request){
        DB::beginTransaction();
        try{
            $flights = new ApprovalGroup;
            $flights->group_name = $request->name;
            $flights->group_state= 1;
            $flights->save();
            ApprovalGroup::where('id', '<>', $flights->id)
            ->increment('group_sort');
            DB::commit();
        }catch (\Exception $e){
            DB::rollback();
            return response()->json(['status'=>101,'msg'=>'分组名称重复']);
        }
        return response()->json(['status'=>100,'msg'=>'添加成功','data'=>$flights]);
    }

    /**分组编辑**/
    public function GroupEdit(Request $request){
        try{
            $flights = ApprovalGroup::find($request->id);
            $flights->group_name = $request->name;
            $flights->save();
        }catch (\Exception $e){
            return response()->json(['status'=>101,'msg'=>'分组名称重复']);
        }
        return response()->json(['status'=>100,'msg'=>'修改成功']);
    }

    /**分组删除**/
    public function GroupDelt(Request $request){
        $count = ApprovalType::where([
            ['group_id',$request->id],
            ['type_state',1],
        ])
        ->count();
        if($count != 0){
            return response()->json(['status'=>101,'msg'=>'该分组类型未清空']);
        }
        $flight = ApprovalGroup::find($request->id);
        if($flight->delete()){
            ApprovalGroup::where('group_sort', '>', $flight->group_sort)
                ->decrement('group_sort');
            return response()->json(['status'=>100,'msg'=>'删除成功']);
        }
        return response()->json(['status'=>101,'msg'=>'删除失败']);
    }

    /**分组列表**/
    public function GroupIndex(Request $request){
        $file = array(
            'id',
            'group_name',
            'group_sort',
        );
        $where = array(
            ['group_state','1']
        );
        $flights = ApprovalGroup::GroupIndex($file,$where);
        if($flights->isEmpty()){
            return response()->json(['status'=>101,'msg'=>'无数据']);
        }else{
            return response()->json(['status'=>100,'msg'=>'请求成功','data'=>$flights]);
        }
    }

    /**分组排序**/
    public function GroupSort(Request $request){
        $flights = json_decode($request->newsort,true);
        DB::beginTransaction();
        try {
            foreach ($flights as $key => $value){
                $flights = ApprovalGroup::find($value);
                $flights->group_sort = $key;
                $flights->save();
            }
            DB::commit();
        }catch (\Exception $e){
            DB::rollback();
            return response()->json(['status'=>102,'msg'=>'保存失败']);
        }
        return response()->json(['status'=>100,'msg'=>'保存成功']);
    }

    /**类型停用/启用**/
    public function TypeStop(Request $request){
        switch ($request->type_state)
        {
            case 0:
                $flight = ApprovalType::find($request->tid);
                $flight->group_id   = $request->id;
                $flight->type_state = 1;
                if($flight->save()){
                    return response()->json(['status'=>100,'msg'=>'启用成功']);
                }else{
                    return response()->json(['status'=>101,'msg'=>'启用失败']);
                }
                break;
            case 1:
                $flight = ApprovalType::find($request->tid);
                $flight->type_state = 0;
                if($flight->save()){
                    return response()->json(['status'=>100,'msg'=>'停用成功']);
                }else{
                    return response()->json(['status'=>101,'msg'=>'停用失败']);
                }
                break;
        }
    }

    /**类型移动分组**/
    public function TypeMove(Request $request){
        $flight = ApprovalType::find($request->tid);
        $flight->group_id = $request->id;
        if($flight->save()){
            return response()->json(['status'=>100,'msg'=>'移动成功']);
        }else{
            return response()->json(['status'=>101,'msg'=>'移动失败']);
        }
    }

    /**类型添加**/
    public function TypeAdd(Request $request){
        DB::beginTransaction();
        try{
            $flight = new ApprovalType;
            $flight->group_id  = $request->id;
            $flight->type_name = $request->name;
            $flight->type_user = $request->user;
            $flight->type_icon = $request->icon;
            $flight->type_explain= $request->explain;
            $flight->type_state= 1;
            if(!$flight->save()){
                return response()->json(['status'=>101,'msg'=>'添加失败']);
            }
            $node  = json_decode($request->node,true);
            array_walk($node, function (&$v, $k, $p) {
                $v['node_user'] = $v['node_user'];
                $v = array_merge($v, $p);
                }, array('type_id' => $flight->id));
            $flighs= ApprovalNode::insert($node);
            if($flighs == 0){
                DB::rollback();
                return response()->json(['status'=>101,'msg'=>'添加失败']);
            }
            DB::commit();
        }catch (\Exception $e){
            DB::rollback();
            return response()->json(['status'=>101,'msg'=>'添加失败']);
        }
        return response()->json(['status'=>100,'msg'=>'添加成功']);
    }

    /**类型详情**/
    public function TypeIndex(Request $request){
        $file = array(
            'group_id as id',
            'id as tid',
            'type_name',
            'type_user',
            'type_icon',
            'type_explain',
        );
        $where = array(
            ['id',$request->tid]
        );
        $flighs = ApprovalType::TypeIndex($file,$where);
        if ($flighs->isEmpty()) {
            return response()->json(['status' => 101, 'msg' => '无数据']);
        }
        $cltUser = explode(";", $flighs->first()->type_user);
        $file = array(
            'name'
        );
        $evteUser = UserBasic::IndexBasic($file, 'id', $cltUser);
        $evteUser = $evteUser->toArray();
        $flighs->first()->user_name = implode(";", array_column($evteUser, 'name'));
        $file = array(
            'id as nid',
            'type_id',
            'node_type',
            'node_user',
        );
        $where = array(
            ['type_id',$request->tid]
        );
        $flight = ApprovalNode::NodeIndex($file,$where);
        if ($flight->isEmpty()) {
            $flighs->first()->node = '';
        }else{
            $flight = $flight->toArray();
            array_walk($flight, function ($value, $key)  use (&$flighl){
                $cltUser = explode(";", $value['node_user']);
                $file = array(
                    'name'
                );
                $evteUser = UserBasic::IndexBasic($file, 'id', $cltUser);
                $evteUser = $evteUser->toArray();
                $value['user_name'] = implode(";", array_column($evteUser, 'name'));
                $flighl[] = $value;
            });
            $flighs->first()->node = $flighl;
        }
        return response()->json(['status' => 100, 'msg' => '请求成功', 'data' => $flighs]);
    }

    /**类型修改**/
    public function TypeEdit(Request $request){
        $data = json_decode($request->node,true );
        array_walk($data, function ($value,$key,$id) use (&$flighs) {
            if($value['nid'] == 0){
                $flighs['add'][] = array(
                    'type_id'  => $id,
                    'node_type'=> $value['node_type'],
                    'node_user'=> $value['node_user'],
                    'node_sort'=> $value['node_sort'],
                );
            }else{
                $flighs['edit'][] = array(
                    'id'        => $value['nid'],
                    'type_id'  => $id,
                    'node_type'=> $value['node_type'],
                    'node_user'=> $value['node_user'],
                    'node_sort'=> $value['node_sort'],
                );
            }
        },$request->tid);
        DB::beginTransaction();
        try{
            $flight = ApprovalType::find($request->tid);
            $flight->group_id  = $request->id;
            $flight->type_name = $request->name;
            $flight->type_user = $request->user;
            $flight->type_icon = $request->icon;
            $flight->type_explain= $request->explain;
            if(!$flight->save()){
                return response()->json(['status'=>101,'msg'=>'修改失败']);
            }
            if(!empty($flighs['add'])){
                ApprovalNode::insert($flighs['add']);
            }
            if(!empty($flighs['edit'])){
                ApprovalNode::UpdateBatch($flighs['edit']);
            }
            DB::commit();
        }catch (\Exception $e){
            DB::rollback();
            return response()->json(['status'=>101,'msg'=>'修改失败']);
        }
        return response()->json(['status'=>100,'msg'=>'修改成功']);
    }

    /**类型删除**/
    public function TypeDelt(Request $request){
        DB::beginTransaction();
        try {
            ApprovalNode::where('type_id',$request->tid)
                ->delete();
            $flighs = ApprovalType::find($request->tid);
            if(!$flighs->delete()){
                return response()->json(['status'=>101,'msg'=>'删除失败']);
            }
            DB::commit();
        }catch (\Exception $e){
            DB::rollback();
            return response()->json(['status'=>102,'msg'=>'删除失败']);
        }
        return response()->json(['status'=>100,'msg'=>'删除成功']);
    }

    /**节点删除**/
    public function NodeDelt(Request $request){
        $flighs = ApprovalNode::find($request->nid);
        if($flighs->delete()){
            return response()->json(['status'=>100,'msg'=>'删除成功']);
        }
        return response()->json(['status'=>100,'msg'=>'删除成功']);
    }
}
