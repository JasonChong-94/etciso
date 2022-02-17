<?php
/**
 * Created by PhpStorm.
 * User: Greatwall
 * Date: 2019/8/13
 * Time: 17:53
 */
namespace App\Models\Api\Examine;
use Illuminate\Database\Eloquent\Model;

class ExamineProject extends Model{

    /**定义表名**/
    protected $table = 'xiangmu';

    public $timestamps = false;

    /**我的客户**/
    protected function IndexProject($cndtn,$where){
        $flighs = ExamineProject::select($cndtn)
            ->where($where)
            ->get();
        return($flighs);
    }
}
