<?php

namespace App\Http\Controllers\Api\Inspect;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\Auth;
use App\Models\Api\Inspect\InspectAuditTeam;
use App\Models\Api\Market\MarketContract;
use Illuminate\Support\Facades\DB;

class InspectTaskController extends Controller
{
    /**项目计划列表**/
    public function index(Request $request){
        //DB::connection()->enableQueryLog();#开启执行日志
        $user_id = $request->userid?$request->userid:Auth::guard('api')->user()->id;
        $where  = array(
            ['us_id','=',$user_id],
            ['dp_sbmt', '=', 0],
        );
        if($request->name){
            $where[] = ['khxx.qymc', 'like', '%'.$request->name.'%'];
        }
        if($request->system){
            $where[] = ['rztx', '=',$request->system];
        }
        $time = array();
        if($request->plst && $request->plet){
            $time = [$request->plst,$request->plet];
        }
        $flighs = InspectAuditTeam::join('qyht_htrza', 'qyht_htrzu.ap_id', '=', 'qyht_htrza.id')
            ->Join('qyht_htrz', 'qyht_htrza.xm_id', '=', 'qyht_htrz.id')
            ->join('qyht', 'qyht.id', '=', 'qyht_htrz.ht_id')
            ->join('khxx', 'khxx.id', '=', 'qyht.kh_id')
            ->with([
            'inspectPlan' => function ($query){
                $query->select('id','xm_id','audit_phase','p_rzbz','p_rev_range','start_time','end_time','dp_sbmt');
                //$query->paginate(15);
            },
            'inspectPlan.systemState' => function ($query){
                $query->where('state', '=',1);
                $query->select('code','activity');
            },
            'inspectPlan.examineSystem' => function ($query){
                $query->select('id','ht_id','rztx','rzbz','rev_range');
            },
            'inspectPlan.examineSystem.marketContract'=> function ($query){
                $query->select('id','kh_id');
            },
            'inspectPlan.examineSystem.marketContract.marketCustomer' => function ($query){
                $query->select('id','qymc','bgdz');
            },
            'inspectPlan.examineSystem.marketContract.marketCustomer.marketContacts' => function ($query){
                $query->where('state', '=',1);
                $query->select('kh_id','name','phone');
            }])
            ->where($where)
            ->when($time,function ($query) use ($time) {
                return  $query->whereBetween('start_time', $time);
            })
            ->orderBy('start_time','desc')
            ->select('ap_id')
            ->paginate($request->limit);
        //dump(DB::getQueryLog());
        if($flighs->isEmpty()){
            return response()->json(['status'=>101,'msg'=>'无数据']);
        }
        $flighs = $flighs->toArray();
        array_walk($flighs['data'], function ($value,$key) use (&$flight){
            $data['id']   = $value['inspect_plan']['examine_system']['market_contract']['kh_id'];
            $data['did']  = $value['inspect_plan']['id'];
            $data['dp_sbmt'] = $value['inspect_plan']['dp_sbmt'];
            $data['qymc'] = $value['inspect_plan']['examine_system']['market_contract']['market_customer']['qymc'];
            $data['bgdz'] = $value['inspect_plan']['examine_system']['market_contract']['market_customer']['bgdz'];
            if(!empty($value['inspect_plan']['examine_system']['market_contract']['market_customer']['market_contacts'])){
                $data['name'] = $value['inspect_plan']['examine_system']['market_contract']['market_customer']['market_contacts'][0]['name'].'('.$value['inspect_plan']['examine_system']['market_contract']['market_customer']['market_contacts'][0]['phone'].')';
                $data['phone']= $value['inspect_plan']['examine_system']['market_contract']['market_customer']['market_contacts'][0]['phone'];
            }else{
                $data['name'] = '';
                $data['phone']= '';
            }
            $data['rztx']        = $value['inspect_plan']['examine_system']['rztx'];
            $data['audit_phase']= $value['inspect_plan']['system_state']['activity'];
            $data['start_time'] = $value['inspect_plan']['start_time'];
            $data['end_time']   = $value['inspect_plan']['end_time'];
            $data['rzbz']       = $value['inspect_plan']['p_rzbz']?$value['inspect_plan']['p_rzbz']:$value['inspect_plan']['examine_system']['rzbz'];
            $data['rev_range']  = $value['inspect_plan']['p_rev_range']?$value['inspect_plan']['p_rev_range']:$value['inspect_plan']['examine_system']['rev_range'];
            $flight[] = $data;
        });
        $flighs['data'] = $flight;
        return response()->json(['status'=>100,'msg'=>'请求成功','data'=>$flighs]);
    }

    /**项目列表详情**/
    public function detail(Request $request){
        //DB::connection()->enableQueryLog();#开启执行日志
        $user_id = $request->userid?$request->userid:Auth::guard('api')->user()->id;
        $where  = array(
            ['us_id','=',$user_id],
            ['start_time','=',$request->stime],
            ['end_time','=',$request->etime],
        );
        $flighs = InspectAuditTeam::join('qyht_htrza', 'qyht_htrzu.ap_id', '=', 'qyht_htrza.id')
            ->with([
                'inspectPlan' => function ($query){
                    $query->select('id','xm_id','audit_phase','p_rzbz','p_rev_range','p_major_code','start_time','end_time');
                },
                'inspectPlan.systemState' => function ($query){
                    $query->where('state', '=',1);
                    $query->select('code','activity');
                },
                'inspectPlan.examineSystem' => function ($query){
                    $query->select('id','ht_id','xmbh','major_code','rztx','rzbz','rev_range');
                }])
            ->where($where)
            ->select('ap_id')
            ->get();
        //dump(DB::getQueryLog());
        if($flighs->isEmpty()){
            return response()->json(['status'=>101,'msg'=>'无数据']);
        }
        $flighs = $flighs->toArray();
        array_walk($flighs, function ($value,$key) use (&$flight){
            $data['did']  = $value['inspect_plan']['id'];
            $data['xmbh']        = $value['inspect_plan']['examine_system']['xmbh'];
            $data['rztx']        = $value['inspect_plan']['examine_system']['rztx'];
            $data['audit_phase']= $value['inspect_plan']['system_state']['activity'];
            $data['rzbz']        = $value['inspect_plan']['p_rzbz']?$value['inspect_plan']['p_rzbz']:$value['inspect_plan']['examine_system']['rzbz'];
            $data['rev_range']  = $value['inspect_plan']['p_rev_range']?$value['inspect_plan']['p_rev_range']:$value['inspect_plan']['examine_system']['rev_range'];
            $data['major_code'] = $value['inspect_plan']['p_rev_range']?$value['inspect_plan']['p_major_code']:$value['inspect_plan']['examine_system']['major_code'];
            $data['start_time'] = $value['inspect_plan']['start_time'];
            $data['end_time']   = $value['inspect_plan']['end_time'];
            $flight[] = $data;
        });
        $flighs = $flight;
        return response()->json(['status'=>100,'msg'=>'请求成功','data'=>$flighs]);
    }

    /**组员条款添加**/
    public function add(Request $request){
        $user_id= Auth::guard('api')->user()->id;
/*        $flight =  InspectAuditTeam::where([
            ['ap_id',$request->did],
            ['us_id',$request->$user_id],
        ])
            ->get();
        if($flight->isEmpty()){
            return response()->json(['status'=>101,'msg'=>'审核条款只能组长添加']);
        }
        if($flight->first()->role != '01'){
            return response()->json(['status'=>101,'msg'=>'审核条款只能组长添加']);
        }*/
        $flighs =  InspectAuditTeam::find($request->aid);
        if($flighs->role == '03'){
            return response()->json(['status'=>101,'msg'=>'技术专家不需要添加审核条款']);
        }
        $flighs->days = $request->days;
        $flighs->clause = $request->clause;
        if($flighs->save()){
            return response()->json(['status'=>100,'msg'=>'条款添加成功']);
        }
        return response()->json(['status'=>101,'msg'=>'条款添加失败']);
    }
}
