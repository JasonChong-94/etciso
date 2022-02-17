<?php
/**
 * Created by PhpStorm.
 * User: Greatwall
 * Date: 2021/9/6
 * Time: 13:33
 */

namespace App\Models\Wechat;
use Illuminate\Database\Eloquent\Model;
date_default_timezone_set('Asia/Shanghai');
class Invoice extends Model{

    /**定义表名**/
    protected $table = 'khxx_invoice';

    protected function index($filed,$where=[],$limit=15,$sortField='created_at',$sort='desc'){

        $list=Invoice::
        where($where)
            ->select($filed)
            ->orderBy($sortField,$sort)
            ->paginate($limit);
//        dump(DB::getQueryLog());
        return $list;
    }

}