<?php
/**
 * Created by PhpStorm.
 * User: Greatwall
 * Date: 2019/8/13
 * Time: 17:53
 */
namespace App\Models\Api\System;
use Illuminate\Database\Eloquent\Model;

class SystemRisk extends Model{

    /**定义表名**/
    protected $table = 'examine_risk';

    public $timestamps = false;

    /**地区代码**/
    protected function IndexRisk($cndtn,$where){
        $flighs = SystemRisk::select($cndtn)
            ->where($where)
            ->get();
        return($flighs);
    }
}
