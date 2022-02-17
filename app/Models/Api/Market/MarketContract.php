<?php
/**
 * Created by PhpStorm.
 * User: Greatwall
 * Date: 2019/8/13
 * Time: 17:53
 */
namespace App\Models\Api\Market;
use Illuminate\Database\Eloquent\Model;

class MarketContract extends Model{

    //定义表名
    protected $table = 'qyht';

    public $timestamps = false;

    public function marketCustomer()
    {
        return $this->belongsTo('App\Models\Api\Market\MarketCustomer','kh_id','id');
    }

    public function examineSystem()
    {
        return $this->hasMany('App\Models\Api\Examine\ExamineSystem','ht_id','id');
    }

    //客户合同
    protected function IndexContract($cndtn,$where){
        $flighs = MarketContract::select($cndtn)
            ->where($where)
            ->orderBy('add_time','desc')
            ->get();
        return($flighs);
    }

    //年审复评
    protected function ReviewContract($cndtn,$where,$limit,$sortField,$sort,$time=''){
        $flighs = MarketContract::
            join('khxx', 'khxx.id', '=', 'qyht.kh_id')
            ->leftJoin('fzjg', 'fzjg.id', '=', 'khxx.fzjg')
            ->join('qyht_htrz', 'qyht.id', '=', 'qyht_htrz.ht_id')
            ->leftJoin('examine_activity', 'examine_activity.code', '=', 'qyht_htrz.ji_shlx')
            ->where($where)
            ->where(function ($query) use ($time) {
                if(!empty($time)){
                    foreach($time as $key => $vel){
                        $query->whereBetween($key, $vel);
                    }
                }
            })
            ->select($cndtn)
            ->orderBy($sortField,$sort)
            ->orderBy('khxx.id','asc')
            ->paginate($limit);
        return($flighs);
    }

    //合同评审列表
    protected function NotreviewContract($cndtn,$where,$limit,$sortField,$sort,$time=''){
        $flighs = MarketContract::
        join('khxx', 'khxx.id', '=', 'qyht.kh_id')
            ->leftJoin('fzjg', 'fzjg.id', '=', 'khxx.fzjg')
            ->join('qyht_htrz', 'qyht.id', '=', 'qyht_htrz.ht_id')
            ->leftJoin('examine_activity', 'examine_activity.code', '=', 'qyht_htrz.shlx')
            ->where($where)
            ->when($time,function ($query) use ($time) {
                foreach($time as $key => $vel){
                    return  $query->whereBetween($key, $vel);
                }
            })
            ->select($cndtn)
            ->orderBy($sortField,$sort)
            ->paginate($limit);
        return($flighs);
    }

    //合同评审企业数
    protected function NumberContract($where){
        $flighs = MarketContract::
        join('khxx', 'khxx.id', '=', 'qyht.kh_id')
            ->join('qyht_htrz', 'qyht.id', '=', 'qyht_htrz.ht_id')
            ->select('khxx.id')
            ->where($where)
            ->groupBy('khxx.id')
            ->get();
        return($flighs->count());
    }

    //项目计划列表
    protected function DispatchContract($cndtn,$where,$limit,$sortField,$sort,$orWhere='',$time){
        $flighs = MarketContract::
        join('khxx', 'khxx.id', '=', 'qyht.kh_id')
            //->leftJoin('fzjg', 'fzjg.id', '=', 'khxx.fzjg')
            ->join('qyht_htrz', 'qyht.id', '=', 'qyht_htrz.ht_id')
            //->leftJoin('examine_activity', 'examine_activity.code', '=', 'qyht_htrz.ji_shlx')
            ->join('qyht_htrza', 'qyht_htrza.xm_id', '=', 'qyht_htrz.id')
            ->where($where)
            ->where(function ($query) use ($orWhere) {
                if(!empty($orWhere)){
                    foreach($orWhere as $vel){
                        $query->orWhere($vel);
                    }
                }
            })
            ->when($time,function ($query) use ($time) {
                foreach($time as $key => $vel){
                    return  $query->whereBetween($key, $vel);
                }
            })
            ->select($cndtn)
            ->orderBy($sortField,$sort)
            ->orderBy('audit_phase','asc')
            ->paginate($limit);
        return($flighs);
    }

    //项目计划详情
    protected function DispatchDetail($cndtn,$where='',$whereIn=''){
        $flighs = MarketContract::
            join('qyht_htrz', 'qyht.id', '=', 'qyht_htrz.ht_id')
            ->join('qyht_htrza', 'qyht_htrza.xm_id', '=', 'qyht_htrz.id')
            ->when($where,function ($query) use ($where) {
                return  $query->where($where);
            })
            ->when($whereIn,function ($query) use ($whereIn) {
                return  $query->whereIn('qyht_htrza.id',$whereIn);
            })
            ->select($cndtn)
            ->get();
        return($flighs);
    }

    //项目计划上报
    protected function ReportPlan($cndtn,$where,$limit,$sortField,$sort,$time,$orWhere){
        $flighs = MarketContract::
        join('khxx', 'khxx.id', '=', 'qyht.kh_id')
            ->leftJoin('fzjg', 'fzjg.id', '=', 'khxx.fzjg')
            ->join('qyht_htrz', 'qyht.id', '=', 'qyht_htrz.ht_id')
            ->join('qyht_htrza', 'qyht_htrza.xm_id', '=', 'qyht_htrz.id')
            //->leftJoin('examine_stage', 'examine_stage.code', '=', 'qyht_htrza.audit_phase')
            ->where($where)
            ->when($time,function ($query) use ($time) {
                foreach($time as $key => $vel){
                    return  $query->whereBetween($key, $vel);
                }
            })
            ->where(function ($query) use ($orWhere) {
                if(!empty($orWhere)){
                    foreach($orWhere as $vel){
                        $query->orWhere($vel);
                    }
                }
            })
            ->select($cndtn)
            ->orderBy($sortField,$sort)
            ->orderBy('qymc','asc')
            ->paginate($limit);
        return($flighs);
    }

    //证书打印
    protected function SamplePlan($cndtn,$where){
        $flighs = MarketContract::
        join('khxx', 'khxx.id', '=', 'qyht.kh_id')
            ->join('qyht_htrz', 'qyht.id', '=', 'qyht_htrz.ht_id')
            ->join('qyht_htrza', 'qyht_htrza.xm_id', '=', 'qyht_htrz.id')
            ->where($where)
            ->select($cndtn)
            ->get();
        return($flighs);
    }

    //结合阶段项目
    protected function UnionPlan($cndtn,$union){
        $flighs = MarketContract::
        join('khxx', 'khxx.id', '=', 'qyht.kh_id')
            ->join('qyht_htrz', 'qyht.id', '=', 'qyht_htrz.ht_id')
            ->join('qyht_htrza', 'qyht_htrza.xm_id', '=', 'qyht_htrz.id')
            ->select($cndtn)
            ->whereIn('qyht_htrza.id', $union)
            ->get();
        return($flighs);
    }

    //项目计划列表
    protected function UserActivity($cndtn,$where,$limit,$sortField,$sort,$time){
        $flighs = MarketContract::
        join('khxx', 'khxx.id', '=', 'qyht.kh_id')
            ->join('qyht_htrz', 'qyht.id', '=', 'qyht_htrz.ht_id')
            ->Join('qyht_htrza', 'qyht_htrza.xm_id', '=', 'qyht_htrz.id')
            ->Join('qyht_htrzu', 'qyht_htrzu.ap_id', '=', 'qyht_htrza.id')
            ->Join('users', 'users.id', '=', 'qyht_htrzu.us_id')
            ->when($where,function ($query) use ($where) {
                    return  $query->where($where);
            })
            ->when($time,function ($query) use ($time) {
                foreach($time as $key => $vel){
                    return  $query->whereBetween($key, $vel);
                }
            })
            ->select($cndtn)
            ->orderBy($sortField,$sort)
            ->paginate($limit);
        return($flighs);
    }
}
