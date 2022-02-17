<?php
/**
 * Created by PhpStorm.
 * User: Greatwall
 * Date: 2019/8/13
 * Time: 17:53
 */
namespace App\Models\Api\Approval;
use Illuminate\Database\Eloquent\Model;

class ApprovalType extends Model{

    /**定义表名**/
    protected $table = 'aprv_type';

    /**审批类型**/
    protected function TypeIndex($cndtn,$where){
        $flighs = ApprovalType::select($cndtn)
            ->where($where)
            ->get();
        return($flighs);
    }

    /**类型节点**/
    protected function TypeNode($cndtn,$where){
        $flighs = ApprovalType::
        join('aprv_node', 'aprv_node.type_id', '=', 'aprv_type.id')
            ->select($cndtn)
            ->where($where)
            ->orderBy('node_sort','asc')
            ->get();
        return($flighs);
    }
}
