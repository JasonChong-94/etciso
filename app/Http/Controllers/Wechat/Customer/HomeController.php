<?php
/**
 * Created by PhpStorm.
 * User: Greatwall
 * Date: 2021/8/4
 * Time: 9:48
 */
namespace App\Http\Controllers\Wechat\Customer;

use App\Http\Controllers\Controller;
use App\Models\Wechat\Home;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
class HomeController  extends Controller
{

    //审核列表
    public function plan_list(Request $request){
        $kh_id=$request->input('kh_id');
        if(!$kh_id){
            return response()->json(['status'=>101,'msg'=>'缺少参数']);
        }
        if($request->audit_phase){
            $where[]=['qyht_htrza.audit_phase','=',$request->audit_phase];
        }
        if($request->rztx){
            $where[]=['qyht_htrz.rztx','=',$request->rztx];
        }
//        $where[]=['users.id','=',663];
        $where[]=['qyht.kh_id','=',$kh_id];
//        $where[]=['users.openid','=','oSI2ws6QZ2ZuuGGLGcKRK9E0-n7U'];
        $field=[
            'qyht_htrza.start_time',
            'qyht_htrza.end_time',
            'qyht_htrza.id',//阶段id
            'qyht_htrza.audit_phase',//审核阶段
            'qyht_htrza.dp_sbmt',
            'qyht_htrza.shjy',//审核建议
            'qyht_htrz.one_mode',//一阶段审核方式 01非现场 02现场
            'qyht_htrz.rztx',
            'qyht_htrz.id as xm_id',
            'khxx.bgdz',
            'khxx.qymc',
            'khxx.id as kh_id',
            'examine_stage.activity',//审核阶段
//            'qyht_htrza.xm_id',
        ];
        $res=Home::plan_list($where,$field);
        return response()->json(['status'=>100,'msg'=>'请求成功','data'=>$res]);
    }
    //审核建议
    public function shjy(Request $request){
        $id=$request->input('id');
        if(!$id){
            return response()->json(['status'=>101,'msg'=>'缺少参数']);
        }

        $res=DB::table('qyht_htrza')->where('id',$id)->select('shjy','shjy_name')->get();
        return response()->json(['status'=>100,'msg'=>'请求成功','data'=>$res]);
    }
    /*public function sh_jy(Request $request){
        $id=$request->input('id');
        if(!$id){
            return response()->json(['status'=>101,'msg'=>'缺少参数']);
        }

        $res = DB::table('qyht_htrzu')
            ->leftJoin('qyht_htrza', 'qyht_htrza.id', '=', 'qyht_htrzu.ap_id')
            ->where('qyht_htrza.id','=',$id)
            ->select('zynl','shtd','contents','shjy')
            ->get();
        return response()->json(['status'=>100,'msg'=>'请求成功','data'=>$res]);
    }*/
    //通过体系查找组长建议
    public function zzjy(Request $request){
        $rztx=$request->input('rztx');
        $kh_id=$request->input('kh_id');
        if(!$kh_id ||!$rztx){
            return response()->json(['status'=>101,'msg'=>'缺少参数']);
        }
        $where=[
            ['qyht.kh_id','=',$kh_id],
            ['qyht_htrz.rztx','=',$rztx],
            ['qyht_htrza.result_sbmt','=',1],
        ];
        $field=[
            'qyht_htrza.audit_phase',
            'qyht_htrza.shjy',
            'qyht_htrza.shjy_name',
            'qyht_htrza.end_time',
        ];
        $res = Home::zzjy($where,$field);
        if($res->isEmpty()){
            return response()->json(['status'=>101,'msg'=>'无数据']);
        }
        return response()->json(['status'=>100,'msg'=>'请求成功','data'=>$res]);
    }
    //提交审核评价
    public function pj_add(Request $request){
        $messages = [
            'required' => '缺少参数',
            'integer' => '星级必须是int类型',
        ];
        $validator = Validator::make($request->all(), [
            'aid' => 'required',
            'zynl' => 'required|integer',
            'shtd' => 'required|integer',
        ],$messages);
        if ($validator->fails()) {
            return response()->json(['msg' => $validator->errors()->first(),'status'=>101]);
        }
        $res=DB::table('qyht_htrzu')
            ->where('id', $request->aid)
            ->update([
                'zynl' => $request->zynl,
                'shtd' => $request->shtd,
                'contents' => $request->contents
            ]);
        if(!$res){
            return response()->json(['status'=>100,'msg'=>'提交失败']);
        }
        return response()->json(['status'=>100,'msg'=>'提交成功']);
    }
    public function certificate(Request $request){
        $where  = array(
            ['department','=',1],
            ['pt_m','=',1],
        );
        if(!$request->qymc){
            return response()->json(['status'=>101,'msg'=>'无数据']);
        }
        if($request->qymc){
            $where[] = ['khxx.qymc', 'like','%'.$request->qymc.'%'];
        }
        if($request->rztx){
            $where[] = ['rztx', '=',$request->rztx];
        }
        switch ($request->sortField)
        {
            case 1:
                $sortField = 'zs_ftime';
                $sort = 'desc';
                break;
            case 2:
                $sortField = 'zs_ftime';
                $sort = 'asc';
                break;
            case 3:
                $sortField = 'zs_etime';
                $sort = 'desc';
                break;
            case 4:
                $sortField = 'zs_etime';
                $sort = 'asc';
                break;
            default:
                $sortField = 'zs_ftime';
                $sort = 'desc';
        }
        $flighs = Home::Certificate($where,$sortField,$sort);
        if($flighs->isEmpty()){
            return response()->json(['status'=>101,'msg'=>'无数据']);
        }
        foreach ($flighs as &$v){
            $v->m_url=json_decode($v->m_url,true);
        }
        return response()->json(['status'=>100,'msg'=>'请求成功','data'=>$flighs]);
    }
    //评审类型
    public function ContractProject()
    {
        $typeWhere = array(
            ['xm_id', '=', 0],
            ['xm_state', '=', 1],
        );
        $typeFile = array(
            'id',
            'xiangmu',
            'xm_china',
        );
        $res=DB::table('xiangmu')->where($typeWhere)->select($typeFile)->get();
        return response()->json(['status'=>100,'msg'=>'请求成功','data'=>$res]);
    }
    //推荐客户
    public function recommend(Request $request)
    {
        $messages = [
            'required' => '缺少参数',
        ];
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'phone' => 'required',
            'system' => 'required',
        ],$messages);
        if ($validator->fails()) {
            return response()->json(['msg' => $validator->errors()->first(),'status'=>101]);
        }

        $time=date('Y-m-d H:i:s',time());
        $res=DB::table('cus_recommend')->insert([
            'name' => $request->name,
            'phone' => $request->phone,
            'system' => $request->system,
            'created_at' => $time,
            'updated_at' => $time
        ]);
        if(!$res){
            return response()->json(['status'=>101,'msg'=>'提交失败']);
        }
        return response()->json(['status'=>100,'msg'=>'提交成功']);
    }
    public function rz_list(Request $request){
        $where  = array(
            ['department','=',1],
            ['pt_m','=',1],
        );
        if(!$request->kh_id){
            return response()->json(['status'=>101,'msg'=>'无数据']);
        }
        if($request->kh_id){
            $where[] = ['khxx.id', '=',$request->kh_id];
        }

        $flighs = Home::rz_list($where);
        if($flighs->isEmpty()){
            return response()->json(['status'=>101,'msg'=>'无数据']);
        }
        foreach ($flighs as $key=>$v){
            $res[$key]['value']=$v[0]['rztx'];
            $res[$key]['text']=$v[0]['xm_china'];
        }
        return response()->json(['status'=>100,'msg'=>'请求成功','data'=>$res]);
    }
    //流程查询
    public function process(Request $request){
        $id=$request->input('xm_id');
        $audit_phase=$request->input('audit_phase');
        if(!$id || !$audit_phase){
            return response()->json(['status'=>101,'msg'=>'缺少参数']);
        }
        $where=[
            ['qyht_htrz.id','=',$id],
            ['qyht_htrza.audit_phase','=',$audit_phase],
        ];
        $res = DB::table('qyht_htrz')
            ->leftJoin('qyht_htrza', 'qyht_htrza.xm_id', '=', 'qyht_htrz.id')
            ->where($where)
            ->select('review_time','review_user','pt_m','plan_time','plan_user','dp_pret','dp_time','dp_user','dp_sbmt','evte_time','evte_name','cert_sbmt','print_time','print_user','print_sbmt')
            ->get();
        if($res->isEmpty()){
            return response()->json(['status'=>101,'msg'=>'无数据']);
        }

        foreach ($res as $v){
            $data[0]['time']=$v->print_time;
            $data[0]['user']=$v->print_user;
            $data[0]['state']=$v->print_sbmt;
            $data[0]['type']='证书打印';
            $data[1]['time']=$v->evte_time;
            $data[1]['user']=$v->evte_name;
            $data[1]['state']=$v->cert_sbmt;
            $data[1]['type']='技术评定';
            $data[2]['time']=$v->dp_time;
            $data[2]['user']=$v->dp_user;
            $data[2]['state']=$v->dp_sbmt;
            $data[2]['type']='案卷复核';
            $data[3]['time']=$v->plan_time;
            $data[3]['user']=$v->plan_user;
            $data[3]['state']=$v->dp_pret;
            $data[3]['type']='计划调度';
            if(!in_array($audit_phase,['0101','0102','0201','0202'])){
                $data[4]['time']=$v->review_time;
                $data[4]['user']=$v->review_user;
                $data[4]['state']=$v->pt_m;
                $data[4]['type']='合同评审';
            }
//            unset($data[4]);
            /*array_push($data[0],$v->print_time,$v->print_user,$v->print_sbmt);
            array_push($data[1],$v->evte_time,$v->evte_name,$v->cert_sbmt);
            array_push($data[2],$v->dp_time,$v->dp_user,$v->dp_sbmt);
            array_push($data[3],$v->plan_time,$v->plan_user,$v->dp_pret);
            array_push($data[4],$v->review_time,$v->review_user,$v->pt_m);*/
        }
        /*if(!in_array('0202',['0101','0102','0201','0202'])){
            unset($data[4]);
        }*/
        return response()->json(['status'=>100,'msg'=>'请求成功','data'=>$data]);
    }
}