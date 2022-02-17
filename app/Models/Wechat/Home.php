<?php
/**
 * Created by PhpStorm.
 * User: Greatwall
 * Date: 2021/8/4
 * Time: 10:21
 */
namespace App\Models\Wechat;
use Illuminate\Database\Eloquent\Model;
class Home extends Model{


    public $timestamps = false;

    //获取基本信息
    protected function info($where,$field){
        $res = Contacts::leftJoin('khxx', 'khxx.id', '=', 'contacts.kh_id')
            ->where($where)
            ->select($field)
            ->first();
        return $res;
    }
    protected function plan_list($where,$field){
        $res = Qyht::
        leftJoin('khxx', 'khxx.id', '=', 'qyht.kh_id')
            ->leftJoin('qyht_htrz', 'qyht_htrz.ht_id', '=', 'qyht.id')
            ->leftJoin('qyht_htrza', 'qyht_htrza.xm_id', '=', 'qyht_htrz.id')
            ->leftJoin('examine_stage', 'examine_stage.code', '=', 'qyht_htrza.audit_phase')
//            ->select('users.name','qyht_htrza.start_time','qyht_htrza.end_time','etc_qyht_htrza.xm_id')
            ->select($field)
            ->where($where)
//            ->whereDate('qyht_htrza.start_time','<=',$date)
//            ->whereDate('qyht_htrza.end_time','>=',$date)
            ->orderBy('qyht_htrza.start_time','desc')
            ->paginate(15);
        return $res;
    }
    protected function Certificate($where,$sortField,$sort){
        $res = Qyht::
        leftJoin('qyht_htrz', 'qyht_htrz.ht_id', '=', 'qyht.id')
            ->leftJoin('khxx', 'khxx.id', '=', 'qyht.kh_id')
//            ->leftJoin('qyht_htrza', 'qyht_htrza.xm_id', '=', 'qyht_htrz.id')
            ->where($where)
            ->select('qyht_htrz.id','qymc','zs_nb','rzbz','rev_range','zs_ftime','zs_etime','rztx','wl_number','m_url','s_url')
            ->orderBy($sortField,$sort)
            ->get();
        return $res;
    }
    protected function rz_list($where){
        $res = Qyht::
        leftJoin('qyht_htrz', 'qyht_htrz.ht_id', '=', 'qyht.id')
            ->leftJoin('xiangmu', 'xiangmu.xiangmu', '=', 'qyht_htrz.rztx')
            ->leftJoin('khxx', 'khxx.id', '=', 'qyht.kh_id')
            ->where($where)
            ->select('rztx','xm_china')
            ->get()
            ->groupBy('rztx');
        return $res;
    }
    protected function zzjy($where,$field){
        $res = Qyht::
        leftJoin('qyht_htrz', 'qyht_htrz.ht_id', '=', 'qyht.id')
            ->leftJoin('qyht_htrza', 'qyht_htrza.xm_id', '=', 'qyht_htrz.id')
            ->where($where)
            ->select($field)
            ->orderBy('qyht_htrza.end_time','desc')
            ->get();
        return $res;
    }
}