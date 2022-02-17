<?php
/**
 * Created by PhpStorm.
 * User: Greatwall
 * Date: 2019/8/13
 * Time: 17:53
 */
namespace App\Models\Api\User;
use Illuminate\Database\Eloquent\Model;
class UserRole extends Model{

    /**定义表名**/
    protected $table = 'user_role';

    public $timestamps = false;

    /**权限验证**/
    protected function TestRole($where,$route){
        $role = UserRole::where($where)
            ->first();
        if($role == null){
            return(false);
        }else{
            $lever = json_decode($role->toArray()['lever']);
            if(in_array($route,$lever)){
                return(true);
            }else{
                return(false);
            }
        }
    }

    /**人员职务**/
    protected function UserRole($cndtn,$where){
        $flighs = UserRole::
            leftJoin('users','users.zw_id', '=','user_role.id')
            ->select($cndtn)
            ->when($where,function ($query) use ($where) {
                return  $query->where($where);
            })
            ->get();
        return($flighs);
    }
}
