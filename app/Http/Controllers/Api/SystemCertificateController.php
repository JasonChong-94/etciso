<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Api\Inspect\InspectPlan;
use App\Models\Api\Examine\ExamineSystem;

class SystemCertificateController extends Controller
{
    /**登录验证**/
    public function CertificateIndex(Request $request){
        /*try {*/
            $info = base64_decode($request->data);
            $data = json_decode($info,true);
            $flight = InspectPlan::find($data['did']);
            if($data['style'] == 1){
                $m_url = json_decode($flight->m_url,true);
                if($data['type'] == 1){
                    $t_url = $m_url['c'];
                }else{
                    $t_url = $m_url['e'];
                }

            }else{
                return($flight);die;
                $s_url = json_decode($flight->s_url,true);
                if($data['type'] == 1){
                    $t_url = $s_url[$data['name']]['c'];
                }else{
                    $t_url = $s_url[$data['name']]['e'];
                }
            }
            return response()->json(['status'=>100,'msg'=>'数据请求成功','data'=>'http://oc.etciso.com/certificate/'.$t_url]);
        /*} catch (DecryptException $e) {
            return response()->json(['status'=>101,'msg'=>$e->getMessage()]);
        }*/
    }

    /**登录验证**/
    public function CertificateQuery(Request $request){
        $where  = array(
            ['department','=',1],
            ['pt_m','=',1],
        );
        $input = $request->all();
        if(!$request->name && !$request->code ){
            return response()->json(['status'=>101,'msg'=>'无数据']);
        }
        if($request->name){
            $where[] = ['khxx.qymc', 'like','%'.$request->name.'%'];
        }
        if($request->code){
            $where[] = ['zs_nb', '=',$request->code];
        }
        if($request->rztx){
            $where[] = ['rztx', '=',$request->rztx];
        }
        switch ($request->sortField)
        {
            case 1:
                $sortField = 'zs_ftime';
                break;
            case 2:
                $sortField = 'zs_etime';
                break;
            default:
                $sortField = 'zs_ftime';
        }
        switch ($request->sort)
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
        $flighs = ExamineSystem::
            join('qyht', 'qyht.id', '=', 'qyht_htrz.ht_id')
            ->join('khxx', 'khxx.id', '=', 'qyht.kh_id')
            ->where($where)
            ->select('qymc','zs_nb','rzbz','rev_range','zs_ftime','zs_etime','rztx')
            ->orderBy($sortField,$sort)
            ->get();
        if($flighs->isEmpty()){
            return response()->json(['status'=>101,'msg'=>'无数据']);
        }
        return response()->json(['status'=>100,'msg'=>'请求成功','data'=>$flighs]);
    }
}
