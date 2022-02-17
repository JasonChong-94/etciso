<?php
/**
 * Created by PhpStorm.
 * User: Greatwall
 * Date: 2021/5/24
 * Time: 10:54
 */
/**
 * Created by PhpStorm.
 * User: Greatwall
 * Date: 2021/5/20
 * Time: 13:51
 */

namespace App\Http\Controllers\Api\Talent;

use App\Http\Controllers\Controller;
use App\Models\Api\Talent\TalentTrack;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
date_default_timezone_set('Asia/Shanghai');
class TalentTrackController extends Controller
{
    //人员跟进
    public function save(Request $request){

        $res=TalentTrack::FormValidation($request->all());
        if($res['type'] == false){
            return response()->json(['status'=>101,'msg'=>$res['error']]);
        }
        $check=new TalentTrack;

        $check->t_id=$request->id;
        $check->date=$request->date;
        $check->contents=$request->contents;
        $check->person = Auth::guard('api')->user()->name;
        if($check->save()){
            DB::table('talent')
                ->where('id', $request->id)
                ->update([
                    'state' => $request->state,
                    'last_date' => date('Y-m-d',time())
                ]);
            return response()->json(['status'=>100,'msg'=>'成功']);
        }else{
            return response()->json(['status'=>101,'msg'=>'失败']);
        }
    }
    //人员跟进列表
    public function list(Request $request){
        $id=$request->input('id');
        if(!$id){
            return response()->json([
                'status'=>101,
                'msg'=>'缺少人员id'
            ]);
        }
        $where[]=['t_id','=',$id];
        $res = TalentTrack::list($where,$request->limit);
        if($res->first()){
            return response()->json(['status'=>100,'msg'=>'请求成功','data'=>$res]);
        }else{
            return response()->json(['status'=>101,'msg'=>'无记录']);
        }
    }
}
