<?php

namespace App\Http\Controllers\Api\Inspect;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Api\Examine\ExamineSystem;
use App\Models\Api\Market\MarketContract;
use App\Models\Api\Inspect\InspectPlan;
use App\Models\Api\Inspect\InspectAuditTeam;
use App\Models\Api\System\SystemState;
use App\Models\Api\System\ApprovalGroup;
use App\Models\Api\User\UserAgenda;
use App\Models\Api\User\UserBasic;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use App\Sdk\MacAddInfo;

class InspectAgendaController extends Controller
{
    /**人员排班列表**/
    public function AgendaIndex(Request $request){
        $time = date('Y-m');
        $time = [$time.'-01',$time.'-31 24:00:00'];
        $flighs = $this->AgendaShare($time);
        return ($flighs);
    }

    /**人员排班查询**/
    public function AgendaQuery(Request $request){
        $time = $request->time?$request->time:date('Y-m');
        switch ($request->type) {
            case 1:
                $time = [$time.'-01-01',$time.'-12-31 24:00:00'];
                break;
            case 2:
                $time = [$time.'-01',$time.'-31 24:00:00'];
                break;
            default :
                $time = [$time.'-01',$time.'-31 24:00:00'];
        }
/*        if(!empty($request->name)){
            $where[] = ['name','=',$request->name];
        }
        if(!empty($request->na_code)){
            $where[] = ['na_code','=',$request->na_code];
        }
        if(!empty($request->region)){
            $where[] = ['region','=',$request->region];
        }*/
        $flighs = $this->AgendaShare($time);
        return($flighs);
    }

    /**人员排班统计**/
    public function AgendaStatistic(Request $request){
        $time = $request->time?$request->time:date('2019-05');
        $where= array(
            ['year','like',$time.'%'],
        );
        if(!empty($request->name)){
            $where[] = ['name','=',$request->name];
        }
        if(!empty($request->na_code)){
            $where[] = ['na_code','=',$request->na_code];
        }
        if(!empty($request->region)){
            $where[] = ['region','=',$request->region];
        }
        $file = array(
            'type',
            '001',
            '002',
            '003',
            '004',
            '005',
            '006',
            '007',
            '008',
            '009',
            '010',
            '011',
            '012',
            '013',
            '014',
            '015',
            '016',
            '017',
            '018',
            '019',
            '020',
            '021',
            '022',
            '023',
            '024',
            '025',
            '026',
            '027',
            '028',
            '029',
            '030',
            '031'
        );
        $flighs = UserAgenda::UserAgenda($file,$where,'');
        if($flighs->isEmpty()){
            return response()->json(['status'=>101,'msg'=>'无数据']);
        }
        $flighs = $flighs->toArray();
        $fights = array(
            'full' => [
                '01'=> 0,
                '02'=> 0,
                '03'=> 0,
                '04'=> 0,
                '05'=> 0,
                '06'=> 0,
                '07'=> 0,
            ],
            'part' => [
                '01'=> 0,
                '02'=> 0,
                '03'=> 0,
                '04'=> 0,
                '05'=> 0,
                '06'=> 0,
                '07'=> 0,
            ],
        );
        array_walk($flighs, function ($value) use (&$fights){
            if($value['type'] == 1){
                unset($value['type']);
                $data = $this->AuditDays($value);
                $fights['full']['01'] += $data['01'];
                $fights['full']['02'] += $data['02'];
                $fights['full']['03'] += $data['03'];
                $fights['full']['04'] += $data['04'];
                $fights['full']['05'] += $data['05'];
                $fights['full']['06'] += $data['06'];
                $fights['full']['07'] += $data['07'];
            }else{
                unset($value['type']);
                $data = $this->AuditDays($value);
                $fights['part']['01'] += $data['01'];
                $fights['part']['02'] += $data['02'];
                $fights['part']['03'] += $data['03'];
                $fights['part']['04'] += $data['04'];
                $fights['part']['05'] += $data['05'];
                $fights['part']['06'] += $data['06'];
                $fights['part']['07'] += $data['07'];
            }
        });
        return response()->json(['status'=>100,'msg'=>'请求成功','data'=>$fights]);
    }

    /**人员排班详情**/
    public function AgendaDetail(Request $request){
        $file = array(
            'khxx.id as id',
            'qyht_htrza.id as did',
            'qymc',
            'xmbh',
            'rztx',
            'audit_phase',
            'start_time',
            'end_time',
        );
        $whereIn= explode(";",$request->stage_id);
        $flighs = MarketContract::UnionPlan($file,$whereIn);
        if($flighs->isEmpty()){
            return response()->json(['status'=>101,'msg'=>'没有添加认证项目']);
        }
        $flighs = $flighs->sort();
        $flighs = $flighs->toArray();
        array_walk($flighs, function (&$value,$key){
            $stateWhere = array(
                ['code','=',$value['audit_phase']],
            );
            $state = SystemState::IndexState('activity',$stateWhere);
            $value['audit_phase'] = $state->first()->activity;
            $where = array(
                ['ap_id','=',$value['did']]
            );
            $file = array(
                'name',
                'role',
                'type_qlfts'
            );
            $teamUser = InspectAuditTeam::IndexTeam($file,$where);
            if($teamUser->isEmpty()){
                $value['groud']  = '';
                $value['peple']  = '';
                $value['major']  = '';
            }else{
                $teamUser = $teamUser->toArray();
                array_walk($teamUser, function ($value,$key) use (&$team){
                    switch ($value['type_qlfts']) {
                        case '01':
                            $value['type_qlfts'] = '高级审核员';
                            break;
                        case '02':
                            $value['type_qlfts'] = '审核员';
                            break;
                        case '03':
                            $value['type_qlfts'] = '实习审核员';
                            break;
                        case '04':
                            $value['type_qlfts'] = '技术专家';
                            break;
                        case '05':
                            $value['type_qlfts'] = '高级审查员';
                            break;
                        case '06':
                            $value['type_qlfts'] = '审查员';
                            break;
                        case '07':
                            $value['type_qlfts'] = '主任审核员';
                            break;
                    }
                    switch ($value['role']) {
                        case '01':
                            $team['groud'] = $value['name'].'('.$value['type_qlfts'].')';
                            break;
                        case '02':
                            if(!isset($team['peple'])){
                                $team['peple'] = $value['name'].'('.$value['type_qlfts'].')';
                            }else{
                                $team['peple'] .= ';'.$value['name'].'('.$value['type_qlfts'].')';
                            }
                            break;
                        case '03':
                            if(!isset($team['major'])){
                                $team['major'] = $value['name'].'('.$value['type_qlfts'].')';
                            }else{
                                $team['major'] .= ';'.$value['name'].'('.$value['type_qlfts'].')';
                            }
                            break;
                    }
                });
                $value['groud'] = isset($team['groud'])?$team['groud']:'';
                $value['peple'] = isset($team['peple'])?$team['peple']:'';
                $value['major'] = isset($team['major'])?$team['major']:'';
            }
        });
        return response()->json(['status'=>100,'msg'=>'请求成功','data'=>$flighs]);
    }

    /**查询函数**/
    public function AgendaShare($time){
        //DB::connection()->enableQueryLog();#开启执行日志
        $flighs = InspectPlan::with(['planUser' => function ($query) {
            //$query->where('us_id', '=',443);
            $query->select('ap_id','us_id');
        }])
            ->where([
                ['start_time','<>',''],
                ['start_time','<>',null],
                ['end_time','<>',''],
                ['end_time','<>',null]
            ])
            ->whereBetween('start_time',$time)
            ->orWhereBetween('end_time',$time)
            ->select('id','start_time','end_time')
            ->orderBy('start_time','asc')
            ->get();
        //dump(DB::getQueryLog());die;
        if($flighs->isEmpty()){
            return response()->json(['status'=>101,'msg'=>'无数据']);
        }
        $flighs = $flighs->toArray();
        $flight = array();
        array_walk($flighs, function ($value,$key) use (&$flight){
            $stimestamp = strtotime($value['start_time']);
            $etimestamp = strtotime($value['end_time']);
            // 计算日期段内有多少天
            $date = $this->dateDays($stimestamp,$etimestamp);

            array_walk($value['plan_user'], function ($value,$key,$date) use (&$flight){
                $plan = array(
                    'us_id'=>$value['us_id'],
                    'data' =>$date
                );
                if(!in_array($plan,$flight)){
                    $flight[] = $plan;
                }
            },$date);
        });
        $userId = array_column($flight, 'us_id');
        $userId = array_unique($userId);
        //DB::connection()->enableQueryLog();#开启执行日志
        $userName = UserBasic::whereIn('id',$userId)
            ->select('id','name','na_code','type')
            ->get();
        //dump(DB::getQueryLog());die;
        if($userName->isEmpty()){
            return response()->json(['status'=>101,'msg'=>'无数据']);
        }
        $userName = $userName->toArray();
        array_walk($userName, function ($value,$key) use (&$userTeam){
            $userTeam[$value['id']]['name'] = $value['name'];
            $userTeam[$value['id']]['code'] = $value['na_code'];
            $userTeam[$value['id']]['type'] = $value['type'];
        });
        array_walk($flight, function ($value,$key,$userTeam) use (&$userPlan){
            if(!isset($userPlan[$value['us_id']])){
                $userPlan[$value['us_id']]['name'] = $userTeam[$value['us_id']]['name'];
                $userPlan[$value['us_id']]['us_id']= $value['us_id'];
                $userPlan[$value['us_id']]['code']= $userTeam[$value['us_id']]['code'];
                $userPlan[$value['us_id']]['type']= $userTeam[$value['us_id']]['type'];
                $userPlan[$value['us_id']]['data'] = $value['data'];
                $userPlan[$value['us_id']]['count']= array_sum($value['data']);
            }else{
                $userdata['data'] = $userPlan[$value['us_id']]['data'];
                $userdata['count']= $userPlan[$value['us_id']]['count'];
                array_walk($value['data'], function ($value,$key) use (&$userdata){
                    if (array_key_exists($key,$userdata['data'])) {
                        $userdata['data'][$key] = $userdata['data'][$key].'/'.$value;
                        $userdata['count'] += $value;
                    }else{
                        $userdata['data'][$key] = $value;
                        $userdata['count'] += $value;
                    }
                });
                $userPlan[$value['us_id']]['data'] = $userdata['data'];
                $userPlan[$value['us_id']]['count']= $userdata['count'];
            }
        },$userTeam);
        return response()->json(['status' => 100, 'msg' => '请求成功', 'data' => array_values($userPlan)]);
    }

    /**排班天数转换**/
    protected function dateDays($stimestamp,$etimestamp){
        $days = intval(($etimestamp-$stimestamp)/86400+1);
        // 保存每天日期
        $date = array();
        for($i=0;$i<$days;$i++){
            if($days> 1){
                if($i == 0){
                    switch (date('H:i:s', $stimestamp)) {
                        case "08:30:00":
                            $date[date('d', $stimestamp+(86400*($i)))] = '1';
                            break;
                        case "10:30:00":
                            $date[date('d', $stimestamp+(86400*($i)))] = '0.75';
                            break;
                        case "13:30:00":
                            $date[date('d', $stimestamp+(86400*($i)))] = '0.5';
                            break;
                        case "15:30:00":
                            $date[date('d', $stimestamp+(86400*($i)))] = '0.25';
                            break;
                    }
                }
                if(($i>0) && ($i<$days-1)){
                    $date[date('d', $stimestamp+(86400*($i)))] = '1';
                }
                if($i == $days-1){
                    switch (date('H:i:s', $etimestamp)) {
                        case "10:30:00":
                            $date[date('d', $stimestamp+(86400*($i)))] = '0.25';
                            break;
                        case "12:30:00":
                            $date[date('d', $stimestamp+(86400*($i)))] = '0.5';
                            break;
                        case "15:30:00":
                            $date[date('d', $stimestamp+(86400*($i)))] = '0.75';
                            break;
                        case "17:30:00":
                            $date[date('d', $stimestamp+(86400*($i)))] = '1';
                            break;
                    }
                }
            }else{
                switch (date('H:i:s', $stimestamp)) {
                    case "08:30:00":
                        switch (date('H:i:s', $etimestamp)) {
                            case "10:30:00":
                                $date[date('d', $stimestamp+(86400*($i)))] = '0.25';
                                break;
                            case "12:30:00":
                                $date[date('d', $stimestamp+(86400*($i)))] = '0.5';
                                break;
                            case "15:30:00":
                                $date[date('d', $stimestamp+(86400*($i)))] = '0.75';
                                break;
                            case "17:30:00":
                                $date[date('d', $stimestamp+(86400*($i)))] = '1';
                                break;
                        }
                        break;
                    case "10:30:00":
                        switch (date('H:i:s', $etimestamp)) {
                            case "12:30:00":
                                $date[date('d', $stimestamp+(86400*($i)))] = '0.25';
                                break;
                            case "15:30:00":
                                $date[date('d', $stimestamp+(86400*($i)))] = '0.5';
                                break;
                            case "17:30:00":
                                $date[date('d', $stimestamp+(86400*($i)))] = '0.75';
                                break;
                        }
                        break;
                    case "13:30:00":
                        switch (date('H:i:s', $etimestamp)) {
                            case "15:30:00":
                                $date[date('d', $stimestamp+(86400*($i)))] = '0.25';
                                break;
                            case "17:30:00":
                                $date[date('d', $stimestamp+(86400*($i)))] = '0.5';
                                break;
                        }
                        break;
                    case "15:30:00":
                        switch (date('H:i:s', $etimestamp)) {
                            case "17:30:00":
                                $date[date('d', $stimestamp+(86400*($i)))] = '0.25';
                                break;
                        }
                        break;
                }
            }
        }
        return ($date);
    }

    protected function AuditDays($data){
        $fights = array(
            '01'=> 0,
            '02'=> 0,
            '03'=> 0,
            '04'=> 0,
            '05'=> 0,
            '06'=> 0,
            '07'=> 0,
        );
        array_walk($data,function($valuee,$key) use (&$fights){
            if($this->IsJson($valuee) == true){
                $fight = json_decode($valuee,true);
                if(strpos($fight['type_qlfts'],'/') !== false){
                    $type = explode('/',$fight['type_qlfts']);
                    $day  = explode(';',$fight['day']);
                    array_walk($type,function($value,$key,$day) use (&$fights){
                        if(strpos($value,';') !== false){
                            $qlfts = explode(';',$value);
                            array_walk($qlfts,function($value,$key,$day) use (&$fights){
                               switch ($value) {
                                    case '01':
                                        $fights['01'] += $day;
                                        break;
                                    case '02':
                                        $fights['02'] += $day;
                                        break;
                                    case '03':
                                        $fights['03'] += $day;
                                        break;
                                    case '04':
                                        $fights['04'] += $day;
                                        break;
                                    case '05':
                                        $fights['05'] += $day;
                                        break;
                                    case '06':
                                        $fights['06'] += $day;
                                        break;
                                    case '07':
                                        $fights['07'] += $day;
                                        break;
                                }
                            },$day[$key]);
                        }else{
                            switch ($value) {
                                case '01':
                                    $fights['01'] += $day[$key];
                                    break;
                                case '02':
                                    $fights['02'] += $day[$key];
                                    break;
                                case '03':
                                    $fights['03'] += $day[$key];
                                    break;
                                case '04':
                                    $fights['04'] += $day[$key];
                                    break;
                                case '05':
                                    $fights['05'] += $day[$key];
                                    break;
                                case '06':
                                    $fights['06'] += $day[$key];
                                    break;
                                case '07':
                                    $fights['07'] += $day[$key];
                                    break;
                            }
                        };
                    },$day);
                }else{
                    switch ($fight['type_qlfts']) {
                        case '01':
                            $fights['01'] += $fight['day'];
                            break;
                        case '02':
                            $fights['02'] += $fight['day'];
                            break;
                        case '03':
                            $fights['03'] += $fight['day'];
                            break;
                        case '04':
                            $fights['04'] += $fight['day'];
                            break;
                        case '05':
                            $fights['05'] += $fight['day'];
                            break;
                        case '06':
                            $fights['06'] += $fight['day'];
                            break;
                        case '07':
                            $fights['07'] += $fight['day'];
                            break;
                    }
                }
            }
        });
        return $fights;
    }

    protected function IsJson($string) {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }

    /**人员排班导入**/
    public function AgendaImport(Request $request){
        set_time_limit(0);
        $where = array(
            ['dp_pret','=',1],
            ['user_state','=',1],
        );
        $file = array(
            'qyht_htrza.id as stage_id',
            'users.id as id',
            'type_qlfts',
            'start_time',
            'end_time',
        );
        $sortField = array(
            'start_time'=>'asc'
        );
        //DB::connection()->enableQueryLog();#开启执行日志
        $flighs = InspectPlan::IndexPlan($file,$where,$orWhere='',$sortField);
        //dump(DB::getQueryLog());
        if($flighs->isEmpty()){
            return response()->json(['status'=>101,'msg'=>'无数据']);
        }
        $flighs = $flighs->toArray();
        array_walk($flighs, function ($value,$key) use (&$flight){
            if(!isset($flight[$value['id']])){
                $flight[$value['id']]['id']   =$value['id'];
                $stimestamp = strtotime($value['start_time']);
                $etimestamp = strtotime($value['end_time']);
                // 计算日期段内有多少天
                $days = intval(($etimestamp-$stimestamp)/86400+1);
                // 保存每天日期
                $date = array();

                for($i=1;$i<$days+1;$i++){
                   if($days> 1){
                      if($i == 1){
                          switch (date('H:i:s', $stimestamp)) {
                              case "08:30:00":
                                  $date[date('Y-m-d', $stimestamp+(86400*($i-1)))]['day'] = '1';
                                  break;
                              case "10:30:00":
                                  $date[date('Y-m-d', $stimestamp+(86400*($i-1)))]['day'] = '0.75';
                                  break;
                              case "13:30:00":
                                  $date[date('Y-m-d', $stimestamp+(86400*($i-1)))]['day'] = '0.5';
                                  break;
                              case "15:30:00":
                                  $date[date('Y-m-d', $stimestamp+(86400*($i-1)))]['day'] = '0.25';
                                  break;
                          }
                      }
                      if(($i>1) && ($i<$days)){
                          $date[date('Y-m-d', $stimestamp+(86400*($i-1)))]['day'] = '1';
                      }
                       if($i == $days){
                           switch (date('H:i:s', $etimestamp)) {
                               case "10:30:00":
                                   $date[date('Y-m-d', $stimestamp+(86400*($i-1)))]['day'] = '0.25';
                                   break;
                               case "12:30:00":
                                   $date[date('Y-m-d', $stimestamp+(86400*($i-1)))]['day'] = '0.5';
                                   break;
                               case "15:30:00":
                                   $date[date('Y-m-d', $stimestamp+(86400*($i-1)))]['day'] = '0.75';
                                   break;
                               case "17:30:00":
                                   $date[date('Y-m-d', $stimestamp+(86400*($i-1)))]['day'] = '1';
                                   break;
                           }
                       }
                    }else{
                        switch (date('H:i:s', $stimestamp)) {
                            case "08:30:00":
                                switch (date('H:i:s', $etimestamp)) {
                                    case "10:30:00":
                                        $date[date('Y-m-d', $stimestamp+(86400*($i-1)))]['day'] = '0.25';
                                        break;
                                    case "12:30:00":
                                        $date[date('Y-m-d', $stimestamp+(86400*($i-1)))]['day'] = '0.5';
                                        break;
                                    case "15:30:00":
                                        $date[date('Y-m-d', $stimestamp+(86400*($i-1)))]['day'] = '0.75';
                                        break;
                                    case "17:30:00":
                                        $date[date('Y-m-d', $stimestamp+(86400*($i-1)))]['day'] = '1';
                                        break;
                                }
                                break;
                            case "10:30:00":
                                switch (date('H:i:s', $etimestamp)) {
                                    case "12:30:00":
                                        $date[date('Y-m-d', $stimestamp+(86400*($i-1)))]['day'] = '0.25';
                                        break;
                                    case "15:30:00":
                                        $date[date('Y-m-d', $stimestamp+(86400*($i-1)))]['day'] = '0.5';
                                        break;
                                    case "17:30:00":
                                        $date[date('Y-m-d', $stimestamp+(86400*($i-1)))]['day'] = '0.75';
                                        break;
                                }
                                break;
                            case "13:30:00":
                                switch (date('H:i:s', $etimestamp)) {
                                    case "15:30:00":
                                        $date[date('Y-m-d', $stimestamp+(86400*($i-1)))]['day'] = '0.25';
                                        break;
                                    case "17:30:00":
                                        $date[date('Y-m-d', $stimestamp+(86400*($i-1)))]['day'] = '0.5';
                                        break;
                                }
                                break;
                            case "15:30:00":
                                switch (date('H:i:s', $etimestamp)) {
                                    case "17:30:00":
                                        $date[date('Y-m-d', $stimestamp+(86400*($i-1)))]['day'] = '0.25';
                                        break;
                                }
                                break;
                        }
                    }
                }
                $flight[$value['id']]['day'][$value['start_time'].'-'.$value['end_time']] = array(
                    'type_qlfts' => $value['type_qlfts'],
                    'stage_id' => $value['stage_id'],
                    'date'     =>  $date
                );
            }else{
                if(!isset($flight[$value['id']]['day'][$value['start_time'].'-'.$value['end_time']])){
                    $stimestamp = strtotime($value['start_time']);
                    $etimestamp = strtotime($value['end_time']);
                    // 计算日期段内有多少天
                    $days = intval(($etimestamp-$stimestamp)/86400+1);
                    // 保存每天日期
                    $date = array();

                    for($i=1;$i<$days+1;$i++){
                        if($days> 1){
                            if($i == 1){
                                switch (date('H:i:s', $stimestamp)) {
                                    case "08:30:00":
                                        $date[date('Y-m-d', $stimestamp+(86400*($i-1)))]['day'] = '1';
                                        break;
                                    case "10:30:00":
                                        $date[date('Y-m-d', $stimestamp+(86400*($i-1)))]['day'] = '0.75';
                                        break;
                                    case "13:30:00":
                                        $date[date('Y-m-d', $stimestamp+(86400*($i-1)))]['day'] = '0.5';
                                        break;
                                    case "15:30:00":
                                        $date[date('Y-m-d', $stimestamp+(86400*($i-1)))]['day'] = '0.25';
                                        break;
                                }
                            }
                            if(($i>1) && ($i<$days)){
                                $date[date('Y-m-d', $stimestamp+(86400*($i-1)))]['day'] = '1';
                            }
                            if($i == $days){
                                switch (date('H:i:s', $etimestamp)) {
                                    case "10:30:00":
                                        $date[date('Y-m-d', $stimestamp+(86400*($i-1)))]['day'] = '0.25';
                                        break;
                                    case "12:30:00":
                                        $date[date('Y-m-d', $stimestamp+(86400*($i-1)))]['day'] = '0.5';
                                        break;
                                    case "15:30:00":
                                        $date[date('Y-m-d', $stimestamp+(86400*($i-1)))]['day'] = '0.75';
                                        break;
                                    case "17:30:00":
                                        $date[date('Y-m-d', $stimestamp+(86400*($i-1)))]['day'] = '1';
                                        break;
                                }
                            }
                        }else{
                            switch (date('H:i:s', $stimestamp)) {
                                case "08:30:00":
                                    switch (date('H:i:s', $etimestamp)) {
                                        case "10:30:00":
                                            $date[date('Y-m-d', $stimestamp+(86400*($i-1)))]['day'] = '0.25';
                                            break;
                                        case "12:30:00":
                                            $date[date('Y-m-d', $stimestamp+(86400*($i-1)))]['day'] = '0.5';
                                            break;
                                        case "15:30:00":
                                            $date[date('Y-m-d', $stimestamp+(86400*($i-1)))]['day'] = '0.75';
                                            break;
                                        case "17:30:00":
                                            $date[date('Y-m-d', $stimestamp+(86400*($i-1)))]['day'] = '1';
                                            break;
                                    }
                                    break;
                                case "10:30:00":
                                    switch (date('H:i:s', $etimestamp)) {
                                        case "12:30:00":
                                            $date[date('Y-m-d', $stimestamp+(86400*($i-1)))]['day'] = '0.25';
                                            break;
                                        case "15:30:00":
                                            $date[date('Y-m-d', $stimestamp+(86400*($i-1)))]['day'] = '0.5';
                                            break;
                                        case "17:30:00":
                                            $date[date('Y-m-d', $stimestamp+(86400*($i-1)))]['day'] = '0.75';
                                            break;
                                    }
                                    break;
                                case "13:30:00":
                                    switch (date('H:i:s', $etimestamp)) {
                                        case "15:30:00":
                                            $date[date('Y-m-d', $stimestamp+(86400*($i-1)))]['day'] = '0.25';
                                            break;
                                        case "17:30:00":
                                            $date[date('Y-m-d', $stimestamp+(86400*($i-1)))]['day'] = '0.5';
                                            break;
                                    }
                                    break;
                                case "15:30:00":
                                    switch (date('H:i:s', $etimestamp)) {
                                        case "17:30:00":
                                            $date[date('Y-m-d', $stimestamp+(86400*($i-1)))]['day'] = '0.25';
                                            break;
                                    }
                                    break;
                            }
                        }
                    }
                    $flight[$value['id']]['day'][$value['start_time'].'-'.$value['end_time']] = array(
                        'type_qlfts' => $value['type_qlfts'],
                        'stage_id' => $value['stage_id'],
                        'date'     =>  $date
                    );
                }else{
                    $type = explode(';',$flight[$value['id']]['day'][$value['start_time'].'-'.$value['end_time']]['type_qlfts']);
                    if(!in_array($value['type_qlfts'],$type)){
                        $flight[$value['id']]['day'][$value['start_time'].'-'.$value['end_time']]['type_qlfts'] .= ';'. $value['type_qlfts'];
                    }
                    $flight[$value['id']]['day'][$value['start_time'].'-'.$value['end_time']]['stage_id'] .= ';'. $value['stage_id'];
                    //$flight[$value['id']]['day'][$value['start_time'].'-'.$value['end_time']]['date'] = array_merge_recursive($flight[$value['id']]['day'][$value['start_time'].'-'.$value['end_time']]['date'],$stage );
                }
            }
        });
        //dump($flight);die;
        foreach ($flight as $value){
            $userDate = array();
            foreach ($value['day'] as $date){
                foreach ($date['date'] as $key =>$day){
                    if(!isset($userDate[date('Y-m',strtotime($key)).'-'.$value['id']])){
                        $userDate[date('Y-m',strtotime($key)).'-'.$value['id']]['us_id'] = $value['id'];
                        $userDate[date('Y-m',strtotime($key)).'-'.$value['id']]['year']  = date('Y-m',strtotime($key));
                        $userDate[date('Y-m',strtotime($key)).'-'.$value['id']][date('d',strtotime($key))] = array(
                            'type_qlfts'=> $date['type_qlfts'],
                            'stage_id'  => $date['stage_id'],
                            'day' => $day['day'],
                        );
                    }else{
                        if(!isset($userDate[date('Y-m',strtotime($key)).'-'.$value['id']][date('d',strtotime($key))])){
                            $userDate[date('Y-m',strtotime($key)).'-'.$value['id']][date('d',strtotime($key))] = array(
                                'type_qlfts'=> $date['type_qlfts'],
                                'stage_id'  => $date['stage_id'],
                                'day' => $day['day'],
                            );
                        }else{
                            $userDate[date('Y-m',strtotime($key)).'-'.$value['id']][date('d',strtotime($key))]['type_qlfts'] .= '/'.$date['type_qlfts'];
                            $userDate[date('Y-m',strtotime($key)).'-'.$value['id']][date('d',strtotime($key))]['stage_id'] .= ';'.$date['stage_id'];
                            $userDate[date('Y-m',strtotime($key)).'-'.$value['id']][date('d',strtotime($key))]['day'] .= ';'.$day['day'];
                        }
                    }
                }
            }
            foreach ($userDate as $dateKey => $user){
  /*              $day = array_column($user, 'day');
                foreach ($day as $days){
                    if(strpos($days,';') !== false){
                        $time[] = explode(';',$days);
                    }else{
                        $time[] = $days;
                    }
                }
                dump(array_sum($time));die;*/
                $userWork = array();
                $userWork[$dateKey]['us_id'] = $user['us_id'];
                $userWork[$dateKey]['year']  = $user['year'];
                foreach ($user as $userKey => $userDay){
                    if(IS_Array($userDay)){
                        $userWork[$dateKey]['0'.$userKey] = json_encode($userDay);
                    }else{
                        continue;
                    }
                }
                $userWork = array_values($userWork);
                $flight = UserAgenda::insert($userWork);
                if($flight == 0){
                    return response()->json(['status'=>101,'msg'=>'添加失败']);
                }
            }
        }

        return response()->json(['status'=>100,'msg'=>'添加成功']);
    }
}
