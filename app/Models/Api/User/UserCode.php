<?php
/**
 * Created by PhpStorm.
 * User: Greatwall
 * Date: 2019/8/13
 * Time: 17:53
 */
namespace App\Models\Api\User;
use Illuminate\Database\Eloquent\Model;
class UserCode extends Model{

    /**定义表名**/
    protected $table = 'user_code';

    public $timestamps = false;

    /**人员审核代码**/
    protected function UserCode($cndtn,$where,$limit,$sortField,$sort){
        $flighs = UserCode::
        join('users','users.id', '=','user_code.us_id')
            ->leftJoin('fzjg', 'fzjg.id', '=', 'users.region')
            ->select($cndtn)
            ->when($where,function ($query) use ($where) {
                return  $query->where($where);
            })
            ->orderBy($sortField,$sort)
            ->orderBy('system','asc')
            ->paginate($limit);
        return($flighs);
    }
}
