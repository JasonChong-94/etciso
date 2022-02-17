<?php
/**
 * Created by PhpStorm.
 * User: Greatwall
 * Date: 2019/8/13
 * Time: 17:53
 */
namespace App\Models\Api\Approval;
use Illuminate\Database\Eloquent\Model;

class ApprovalApply extends Model{

    /**定义表名**/
    protected $table = 'aprv_apply';

    /**审批列表**/
    protected function ApplyIndex($cndtn,$where,$limit,$sortField,$sort,$time){
        $flighs = ApprovalApply::select($cndtn)
            ->where($where)
            ->when($time,function ($query) use ($time) {
                foreach($time as $key => $vel){
                    return  $query->whereBetween($key, $vel);
                }
            })
            ->orderBy($sortField,$sort)
            ->paginate($limit);
        return($flighs);
    }

    /**审批详情**/
    protected function ApplyDetail($cndtn,$where){
        $flighs = ApprovalApply::
        leftJoin('aprv_type', 'aprv_type.id', '=', 'aprv_apply.apply_type')
            ->select($cndtn)
            ->where($where)
            ->get();
        return($flighs);
    }
}
