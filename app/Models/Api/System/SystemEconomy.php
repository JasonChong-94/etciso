<?php
/**
 * Created by PhpStorm.
 * User: Greatwall
 * Date: 2019/8/13
 * Time: 17:53
 */
namespace App\Models\Api\System;
use Illuminate\Database\Eloquent\Model;

class SystemEconomy extends Model{

    /**定义表名**/
    protected $table = 'majorem';

    public $timestamps = false;

    /**专业代码**/
    protected function IndexEconomy($cndtn,$where){
        $flighs = SystemEconomy::select($cndtn)
            ->where($where)
            ->get();
        return($flighs);
    }
}
