<?php
/**
 * Created by PhpStorm.
 * User: Greatwall
 * Date: 2019/8/13
 * Time: 17:53
 */
namespace App\Models\Api\System;
use Illuminate\Database\Eloquent\Model;

class SystemState extends Model{

    /**定义表名**/
    protected $table = 'examine_stage';

    public $timestamps = false;

    /**审核阶段**/
    protected function IndexState($cndtn,$where){
        $flighs = SystemState::select($cndtn)
            ->where($where)
            ->get();
        return($flighs);
    }
}
