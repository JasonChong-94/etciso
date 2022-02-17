<?php
/**
 * Created by PhpStorm.
 * User: Greatwall
 * Date: 2019/8/13
 * Time: 17:53
 */
namespace App\Models\Api\System;
use Illuminate\Database\Eloquent\Model;

class SystemUnion extends Model{

    /**定义表名**/
    protected $table = 'examine_union';

    public $timestamps = false;

    /**地区代码**/
    protected function IndexUnion($cndtn,$where){
        $flighs = SystemUnion::select($cndtn)
            ->where($where)
            ->get();
        return($flighs);
    }
}
