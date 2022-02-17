<?php
/**
 * Created by PhpStorm.
 * User: Greatwall
 * Date: 2019/8/13
 * Time: 17:53
 */
namespace App\Models\Api\System;
use Illuminate\Database\Eloquent\Model;

class SystemSuspend extends Model{

    /**定义表名**/
    protected $table = 'examine_suspend';

    public $timestamps = false;

    /**变更类型**/
    protected function IndexSuspend($cndtn,$where){
        $flighs = ApprovalGroup::select($cndtn)
            ->where($where)
            ->get();
        return($flighs);
    }
}
