<?php
/**
 * Created by PhpStorm.
 * User: Greatwall
 * Date: 2021/1/21
 * Time: 16:37
 */
namespace App\Models\Wechat;
use Illuminate\Database\Eloquent\Model;
class Check extends Model{

    /**定义表名**/
    protected $table = 'wx_check';

    public $timestamps = false;

    protected function check($where,$field,$date){

        $list=Check::select($field)
            ->where($where)
            ->whereDate('date','=',$date)
            ->orderBy('id','asc')
            ->get();
        return $list;
    }
}
