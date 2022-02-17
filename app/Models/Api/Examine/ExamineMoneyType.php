<?php
/**
 * Created by PhpStorm.
 * User: Greatwall
 * Date: 2019/8/13
 * Time: 17:53
 */
namespace App\Models\Api\Examine;
use Illuminate\Database\Eloquent\Model;

class ExamineMoneyType extends Model{

    //指定表名
    protected $table = 'money_type';

    //自动维护时间戳  默认是'true'
    public $timestamps = false;

}
