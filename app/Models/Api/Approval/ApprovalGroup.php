<?php
/**
 * Created by PhpStorm.
 * User: Greatwall
 * Date: 2019/8/13
 * Time: 17:53
 */
namespace App\Models\Api\Approval;
use Illuminate\Database\Eloquent\Model;

class ApprovalGroup extends Model{

    /**定义表名**/
    protected $table = 'aprv_group';

    /**审批分组**/
    protected function GroupIndex($cndtn,$where){
        $flighs = ApprovalGroup::select($cndtn)
            ->where($where)
            ->orderBy('group_sort','asc')
            ->get();
        return($flighs);
    }

    /**审批类型**/
    protected function GroupType($cndtn,$where){
        $flighs = ApprovalGroup::
        leftJoin('aprv_type','aprv_type.group_id', '=','aprv_group.id')
            ->select($cndtn)
            ->where($where)
            ->get();
        return($flighs);
    }
}
