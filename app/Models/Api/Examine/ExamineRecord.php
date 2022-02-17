<?php
/**
 * Created by PhpStorm.
 * User: Greatwall
 * Date: 2019/8/13
 * Time: 17:53
 */
namespace App\Models\Api\Examine;
use Illuminate\Database\Eloquent\Model;

class ExamineRecord extends Model{

    /**定义表名**/
    protected $table = 'examine_record';

    public $timestamps = false;

    /**变更信息列表**/
    protected function IndexRecord($where,$limit,$sortField,$sort){
        $flighs = ExamineRecord::
            where($where)
            ->orderBy($sortField,$sort)
            ->paginate($limit);
        return($flighs);
    }
}
