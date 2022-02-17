<?php
/**
 * Created by PhpStorm.
 * User: Greatwall
 * Date: 2021/8/4
 * Time: 10:25
 */
namespace App\Models\Wechat;
use Illuminate\Database\Eloquent\Model;
class Contacts extends Model{

    /**定义表名**/
    protected $table = 'contacts';

    public $timestamps = false;

    protected function list($where,$field){
        $res = Contacts::
        leftJoin('contacts_type', 'contacts_type.id', '=', 'contacts.id')
//            ->select('users.name','qyht_htrza.start_time','qyht_htrza.end_time','etc_qyht_htrza.xm_id')
            ->select($field)
            ->where($where)
            ->get();
        return $res;
    }

}