<?php
/**
 * Created by PhpStorm.
 * User: Greatwall
 * Date: 2019/8/13
 * Time: 17:53
 */
namespace App\Models\Api\System;
use Illuminate\Database\Eloquent\Model;

class SystemRegion extends Model{

    /**定义表名**/
    protected $table = 'area';

    public $timestamps = false;

    /**地区代码**/
    protected function IndexRegion($cndtn,$where){
        $flighs = SystemRegion::select($cndtn)
            ->where($where)
            ->get();
        return($flighs);
    }
}
