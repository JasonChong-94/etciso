<?php
/**
 * Created by PhpStorm.
 * User: Greatwall
 * Date: 2019/8/13
 * Time: 17:53
 */
namespace App\Models\Api\User;
use Illuminate\Database\Eloquent\Model;
class UserMent extends Model{

    /**定义表名**/
    protected $table = 'user_bumen';

    public $timestamps = false;

    /**企业部门**/
    protected function IndexMent($cndtn,$where){
        $flighs = UserMent::select($cndtn)
            ->where($where)
            ->get()
            ->toArray();
        return($flighs);
    }
}
