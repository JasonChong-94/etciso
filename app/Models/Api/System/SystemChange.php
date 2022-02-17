<?php
/**
 * Created by PhpStorm.
 * User: Greatwall
 * Date: 2019/8/13
 * Time: 17:53
 */
namespace App\Models\Api\System;
use Illuminate\Database\Eloquent\Model;

class SystemChange extends Model{

    /**定义表名**/
    protected $table = 'examine_change';

    public $timestamps = false;

    /**变更类型**/
    protected function IndexChange($cndtn,$where){
        $flighs = SystemChange::select($cndtn)
            ->where($where)
            ->get();
        return($flighs);
    }
}
