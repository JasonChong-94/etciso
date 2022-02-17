<?php
/**
 * Created by PhpStorm.
 * User: Greatwall
 * Date: 2019/8/13
 * Time: 17:53
 */
namespace App\Models\Api\User;
use Illuminate\Database\Eloquent\Model;
class UserAgenda extends Model{

    /**定义表名**/
    protected $table = 'user_agenda';

    public $timestamps = false;

    /**审核人员排班**/
    protected function UserAgenda($cndtn,$where,$limit){
        $flighs = UserAgenda::
        join('users','users.id', '=','user_agenda.us_id')
            ->leftJoin('fzjg', 'fzjg.id', '=', 'users.region')
            ->select($cndtn)
            ->where($where)
            ->when(!$limit,function ($query) use ($limit) {
                return  $query->get();
            })
            ->when($limit,function ($query) use ($limit) {
                return  $query->paginate($limit);
            });
        return($flighs);
    }
}
