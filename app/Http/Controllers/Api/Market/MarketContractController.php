<?php

namespace App\Http\Controllers\Api\Market;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Api\Market\MarketContract;
use App\Models\Api\Examine\ExamineProject;
use App\Models\Api\Examine\ExamineActivity;
use App\Models\Api\Examine\ExamineMoneyType;
use App\Models\Api\Examine\ExamineNames;
use App\Models\Api\Examine\ExamineSystem;
use App\Models\Api\Examine\ExamineMoney;
use Illuminate\Support\Facades\DB;

class MarketContractController extends Controller
{
    /**客户合同**/
    public function ContractIndex(Request $request){
        $where = array(
            ['kh_id','=',$request->id],
        );
        $file = array(
            'id',
            'htbh',
            'user_id',
            'add_time',
            'bz',
            'm_rzly',
            'department',
            'd_patch',
            'u_patch',
            'tj_time',
        );
        $flighs= MarketContract::IndexContract($file,$where);
        if($flighs->isEmpty()){
            return response()->json(['status'=>101,'msg'=>'无数据']);
        }
        return response()->json(['status'=>100,'msg'=>'请求成功','data'=>$flighs]);
    }

    /**认证项目**/
    public function ContractProject(Request $request){
        $typeWhere= array(
            ['xm_id','=',0],
            ['xm_state','=',1],
        );
        $typeFile = array(
            'id',
            'xiangmu',
            'xm_china',
        );
        $project = ExamineProject::IndexProject($typeFile,$typeWhere);
        $Stage = ExamineActivity::
            where('state', '=', 1)
            ->get();
        $money = ExamineMoneyType::
            where('state', '=', 1)
            ->get();
        if($project->isEmpty() || $Stage->isEmpty()){
            return response()->json(['status'=>101,'msg'=>'无数据']);
        }else{
            $data['project']= $project;
            $data['stage']  = $Stage;
            $data['money'] = $money;
            return response()->json(['status'=>100,'msg'=>'请求成功','data'=>$data]);
        }
    }

    /**多名称匹配**/
    protected function MultipleNames($system){
        $syste = explode(";",$system);
        $names = ExamineNames::where('state', '=', 1)
            ->get();
        $m_rzly = '';
        foreach ($names as $key => $data){
            $condition = explode(";",$data['condition']);
            if(count($syste) == count($condition)){
                $result = array_diff($syste,$condition);
                if(empty($result)){
                    $m_rzly = $names[$key]['name'];
                    break;
                }
            }
        }
        return($m_rzly);
    }

    /**合同添加**/
    public function ContractAdd(Request $request){
        $m_rzly = $this->MultipleNames($request->system);
        if($m_rzly == ''){
            return response()->json(['status'=>102,'msg'=>'没有相匹配的名称，请联系管理员进行添加']);
        };
        DB::beginTransaction();
        try {
            $marketSystem = new MarketContract;
            $marketSystem->kh_id = $request->id;
            $marketSystem->user_id = Auth::guard('api')->user()->name;
            $marketSystem->add_time= date('Y-m-d');
            $marketSystem->m_rzly= $m_rzly;
            $marketSystem->bz  = $request->remark;
            $marketSystem->save();
            $project = json_decode($request->project,true );
            array_walk($project, function (&$v, $k, $p) {$v = array_merge($v, $p);}, array('ht_id' => $marketSystem->id));
            ExamineSystem::insert($project);
            $money = json_decode($request->money,true );
            array_walk($money, function (&$v, $k, $p) {$v = array_merge($v, $p);}, array('ht_id' => $marketSystem->id));
            ExamineMoney::insert($money);
            DB::commit();
        }catch (\Exception $e){
            DB::rollback();
            return response()->json(['status'=>101,'msg'=>'添加失败']);
        }
        return response()->json(['status'=>100,'msg'=>'添加成功','data'=>$marketSystem->id]);
    }

    /**合同详情**/
    public function ContractDetail(Request $request){
        $contractWhere= array(
            ['id','=',$request->id],
        );
        $contractFile = array(
            'htbh',
            'bz',
            'user_id',
            'm_rzly',
            'add_time',
            'd_patch',
            'department',
            'ps_m',
        );
        $contract = MarketContract::IndexContract($contractFile,$contractWhere);
        if($contract->isEmpty()){
            return response()->json(['status'=>101,'msg'=>'无数据']);
        }
        $projectWhere= array(
            ['ht_id','=',$request->id],
        );
        $projectFile = array(
            'qyht_htrz.id',
            'rztx',
            'shlx',
            'rzfw',
            'stage',
        );
        $project = ExamineSystem::IndexSystem($projectFile,$projectWhere);
        $moneyWhere= array(
            ['ht_id','=',$request->id],
        );
        $moneyFile = array(
            'qyht_money.id',
            'htfy',
            'htbz',
            'fylx',
            'stage',
            'money_type.name',
        );
        $money = ExamineMoney::IndexMoney($moneyFile,$moneyWhere);
/*        if($contract->isEmpty() || $project->isEmpty() || $money->isEmpty()){
            return response()->json(['status'=>101,'msg'=>'无数据']);
        }else{*/
            $data['contract'] = $contract;
            $data['project'] = $project;
            $data['money'] = $money;
            return response()->json(['status'=>100,'msg'=>'请求成功','data'=>$data]);
      /*  }*/
    }

    /**合同删除**/
    public function ContractDel(Request $request){
        DB::beginTransaction();
        try {
            $flights = MarketContract::find($request->id);
            if($flights->ps_m == 1){
                return response()->json(['status'=>101,'msg'=>'该合同已评审，不能进行删除']);
            }
            if(!$flights->delete()){
                return response()->json(['status'=>101,'msg'=>'删除失败']);
            }
            ExamineSystem::where('ht_id','=',$request->id)->delete();
            ExamineMoney::where('ht_id','=',$request->id)->delete();
            DB::commit();
        }catch (\Exception $e){
            DB::rollback();
            return response()->json(['status'=>101,'msg'=>'删除失败']);
        }
        return response()->json(['status'=>100,'msg'=>'删除成功']);
    }

    /**合同提交**/
    public function ContractSubmit(Request $request){
        $flights = MarketContract::find($request->id);
        if($flights->department == 1){
            return response()->json(['status'=>101,'msg'=>'该合同已提交，不能进行再次提交']);
        }
        $flights->department= 1;
        $flights->tj_time = date('Y-m-d H:i');
        if($flights->save()){
            return response()->json(['status'=>100,'msg'=>'提交成功']);
        }else{
            return response()->json(['status'=>101,'msg'=>'提交失败']);
        }
    }

    /**审核确认**/
    public function ContractSure(Request $request){
        $flights = MarketContract::find($request->id);
        if($flights->d_patch == 1){
            return response()->json(['status'=>101,'msg'=>'该合同已确认，无需再次确认']);
        }
        $flights->d_patch = 1;
        $flights->u_patch = Auth::guard('api')->user()->name;
        if($flights->save()){
            return response()->json(['status'=>100,'msg'=>'确认成功']);
        }
        return response()->json(['status'=>101,'msg'=>'确认失败']);
    }

    /**认证项目修改**/
    public function SystemEdit(Request $request){
        $m_rzly = $this->MultipleNames($request->system);
        if(!$m_rzly){
            return response()->json(['status'=>102,'msg'=>'没有相匹配的名称，请联系管理员进行添加']);
        };
        DB::beginTransaction();
        try {
            $where = array(
                ['id','=',$request->id],
                ['ps_m','=',0],
            );
            $project = json_decode($request->data,true );
            $flighs = ExamineSystem::where($where)
                ->update($project);
            if($flighs == 0){
                return response()->json(['status'=>101,'msg'=>'修改失败']);
            }
            $flights = MarketContract::find($request->ht_id);
            $flights->m_rzly= $m_rzly;
            if(!$flights->save()){
                return response()->json(['status'=>101,'msg'=>'修改失败']);
            }
            DB::commit();
        }catch (\Exception $e){
            DB::rollback();
            return response()->json(['status'=>101,'msg'=>'修改失败']);
        }
        return response()->json(['status'=>100,'msg'=>'修改成功']);
    }

    /**认证项目添加**/
    public function SystemAdd(Request $request){
        $where = array(
            ['ht_id','=',$request->ht_id],
            ['pt_m','=',1],
        );
        $flighs = ExamineSystem::where($where)
            ->count();
        if($flighs != 0){
            return response()->json(['status'=>101,'msg'=>'合同评审已提交不能再进行添加']);
        }
        $m_rzly = $this->MultipleNames($request->system);
        if(!$m_rzly){
            return response()->json(['status'=>102,'msg'=>'没有相匹配的名称，请联系管理员进行添加']);
        };
        DB::beginTransaction();
        try {
            $project = json_decode($request->data,true );
            array_walk($project, function (&$v, $k, $p) {$v = array_merge($v, $p);}, array('ht_id' => $request->ht_id));
            $flighs = ExamineSystem::insert($project);
            if($flighs == 0){
                return response()->json(['status'=>101,'msg'=>'新增失败']);
            }
            $flights = MarketContract::find($request->ht_id);
            $flights->m_rzly= $m_rzly;
            if(!$flights->save()){
                return response()->json(['status'=>101,'msg'=>'新增失败']);
            }
            DB::commit();
        }catch (\Exception $e){
            DB::rollback();
            return response()->json(['status'=>101,'msg'=>'新增失败']);
        }
        return response()->json(['status'=>100,'msg'=>'新增成功']);
    }

    /**认证项目删除**/
    public function SystemDel(Request $request){
        DB::beginTransaction();
        try {
            $flight = ExamineSystem::find($request->id);
            if($flight->ps_m == 1){
                return response()->json(['status'=>100,'msg'=>'该项目已评审不能删除']);
            }
            if(!$flight->delete()){
                return response()->json(['status'=>101,'msg'=>'删除失败']);
            }
            $m_rzly = $this->MultipleNames($request->system);
            if(!$m_rzly){
                return response()->json(['status'=>102,'msg'=>'没有相匹配的名称，请联系管理员进行添加']);
            };
            $flights = MarketContract::find($request->ht_id);
            $flights->m_rzly= $m_rzly;
            if(!$flights->save()){
                return response()->json(['status'=>101,'msg'=>'删除失败']);
            }
            DB::commit();
        }catch (\Exception $e){
            DB::rollback();
            return response()->json(['status'=>101,'msg'=>'删除失败']);
        }
        return response()->json(['status'=>100,'msg'=>'删除成功']);
    }

    /**合同金额修改**/
    public function MoneyEdit(Request $request){
        $where = array(
            ['ht_id','=',$request->ht_id],
            ['pt_m','=',1],
        );
        $flighs = ExamineMoney::where($where)
            ->count();
        if($flighs !== 0){
            return response()->json(['status'=>101,'msg'=>'合同评审已提交不能再进行修改']);
        }
        $where = array(
            ['id','=',$request->id],
        );
        $money = json_decode($request->data,true );
        $flighs = ExamineMoney::where($where)
            ->update($money);
        if($flighs == 0){
            return response()->json(['status'=>101,'msg'=>'修改失败']);
        }else{
            return response()->json(['status'=>100,'msg'=>'修改成功']);
        }
    }

    /**合同金额添加**/
    public function MoneyAdd(Request $request){
        $where = array(
            ['ht_id','=',$request->ht_id],
            ['pt_m','=',1],
        );
        $flighs = ExamineMoney::where($where)
            ->count();
        if($flighs !== 0){
            return response()->json(['status'=>101,'msg'=>'合同评审已提交不能再进行添加']);
        }
        $money = json_decode($request->data,true );
        array_walk($money, function (&$v, $k, $p) {$v = array_merge($v, $p);}, array('ht_id' => $request->ht_id));
        $flighs = ExamineMoney::insert($money);
        if($flighs == 0){
            return response()->json(['status'=>101,'msg'=>'添加失败']);
        }else{
            return response()->json(['status'=>100,'msg'=>'添加成功']);
        }
    }

    /**合同金额删除**/
    public function MoneyDel(Request $request){
        $where = array(
            ['ht_id','=',$request->ht_id],
            ['pt_m','=',1],
        );
        $flighs = ExamineMoney::where($where)
            ->count();
        if($flighs !== 0){
            return response()->json(['status'=>101,'msg'=>'合同评审已提交不能再进行删除']);
        }
        $flight = ExamineMoney::find($request->id);
        if($flight->delete()){
            return response()->json(['status'=>100,'msg'=>'删除成功']);
        }else{
            return response()->json(['status'=>101,'msg'=>'删除失败']);
        }
    }
}
