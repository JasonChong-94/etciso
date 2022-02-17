<?php

namespace App\Http\Controllers\Api\Inspect;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Api\Market\MarketContract;
use App\Models\Api\System\SystemState;
use App\Models\Api\System\ApprovalGroup;
use Illuminate\Support\Facades\DB;

class InspectActivityController extends Controller
{
    /**人员活动列表**/
    public function ActivityIndex(Request $request){
        $flighs = $this->ActivityShare('',$request->limit,$request->field,$request->sort);
        return($flighs);
    }

    /**人员活动查询**/
    public function ActivityQuery(Request $request){
        $where = array();
        if(!empty($request->name)){
            $where[] = ['name','=',$request->name];

        }
        if(!empty($request->na_code)){
            $where[] = ['na_code','=',$request->na_code];
        }
        if(!empty($request->rztx)){
            $where[] = ['rztx','=',$request->rztx];
        }
        if(!empty($request->witic)){
            $where[] = ['witic','=',$request->witic];
        }
        if(!empty($request->start_time)){
            $where[] = ['start_time','>=',$request->start_time];
        }
        if(!empty($request->end_time)){
            $where[] = ['end_time','<=',$request->end_time.' 24:00:00'];
        }
        $flighs = $this->ActivityShare($where,$request->limit,$request->field,$request->sort);
        return($flighs);
    }

    /**查询函数**/
    public function ActivityShare($where,$limit,$sortField,$sort,$time=''){
        $file = array(
            'name',
            'type',
            'type_qlfts',
            'role',
            'witic',
            'witic_type',
            'witic_ctgy',
            'm_code',
            'audit_phase',
            'start_time',
            'end_time',
            'actual_s',
            'actual_e',
            'rztx',
            'qymc',
        );
        switch ($sortField)
        {
            case 1:
                $sortField = 'start_time';
                break;
            case 2:
                $sortField = 'end_time';
                break;
            case 3:
                $sortField = 'actual_s';
                break;
            case 4:
                $sortField = 'actual_e';
                break;
            default:
                $sortField = 'start_time';
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
        //DB::connection()->enableQueryLog();#开启执行日志
        $flighs = MarketContract::UserActivity($file,$where,$limit,$sortField,$sort,$time,'');
        //dump(DB::getQueryLog());
        if($flighs->isEmpty()){
            return response()->json(['status'=>101,'msg'=>'无数据']);
        }
        $flighs = $flighs->toArray();
        array_walk($flighs['data'], function (&$value,$key){
            switch ($value['type']) {
                case 0:
                    $value['type'] = '兼职';
                    break;
                case 1:
                    $value['type'] = '专职';
                    break;
            }
            $strto= strtotime($value['actual_e'])-strtotime($value['actual_s']);
            $day  = intval($strto/86400);
            $remd = $strto/86400-$day;
            $time = intval(round($remd*24));
            switch($time){
                case 2:
                    $day = $day+0.25;
                    break;
                case 4:
                    $day = $day+0.5;
                    break;
                case 7:
                    $day = $day+0.75;
                    break;
                case 9:
                    $day = $day+1;
                    break;
            }
            $value['day'] = $day;
            $stateWhere = array(
                ['code','=',$value['audit_phase']],
                ['state','=',1]
            );
            $state = SystemState::IndexState('activity',$stateWhere);
            $value['audit_phase'] = $state->first()->activity;
            switch ($value['role']) {
                case '01':
                    $value['role'] = '组长';
                    break;
                case '02':
                    $value['role'] = '组员';
                    break;
                case '03':
                    $value['role'] = '技术专家';
                    break;
            }
            switch ($value['witic']) {
                case '00':
                    $value['witic'] = '未见证';
                    break;
                case '01':
                    $value['witic'] = '见证人';
                    break;
                case '02':
                    $value['witic'] = '被见证人';
                    break;
            }
        });
        return response()->json(['status'=>100,'msg'=>'请求成功','data'=>$flighs]);
    }
}
