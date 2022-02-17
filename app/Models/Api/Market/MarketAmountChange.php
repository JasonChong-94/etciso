<?php
/**
 * Created by PhpStorm.
 * User: Greatwall
 * Date: 2021/6/16
 * Time: 14:20
 */
namespace App\Models\Api\Market;
use Illuminate\Database\Eloquent\Model;
date_default_timezone_set('Asia/Shanghai');
class MarketAmountChange extends Model{

    /**定义表名**/
    protected $table = 'khxx_amount_change';
    public $timestamps = false;
    protected function index($where=[],$limit=15,$sortField='change_time',$sort='desc'){
        $filed=[
            'id',
            'amount_id',
            'time',
            'old_time',
            'company',
            'old_company',
            'remarks',
            'old_remarks',
            'user_name',
            'change_time',
        ];
        $list=MarketAmountChange::
        where($where)
            ->select($filed)
            ->orderBy($sortField,$sort)
            ->paginate($limit);
//        dump(DB::getQueryLog());
        return $list;
    }
}