<?php
/**
 * Created by PhpStorm.
 * User: Greatwall
 * Date: 2019/8/13
 * Time: 17:53
 */
namespace App\Models\Api\User;
use Illuminate\Database\Eloquent\Model;
class UserWork extends Model{

    /**定义表名**/
    protected $table = 'user_work';

    public $timestamps = false;
}
