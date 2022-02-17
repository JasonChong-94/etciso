<?php
/**
 * Created by PhpStorm.
 * User: Greatwall
 * Date: 2021/6/15
 * Time: 17:24
 */
namespace App\Models\Api\Market;
use Illuminate\Database\Eloquent\Model;
date_default_timezone_set('Asia/Shanghai');
class MarketAmount extends Model{

    /**定义表名**/
    protected $table = 'khxx_amount';

    protected function index($where=[],$limit=15,$sortField='time',$sort='desc'){
        $filed=[
            'id as amount_id',
            'khxx_id',
            'qymc',
            'user_name',
            'sh_name',
            'qymc',
            'xydm',
            'money',
            'time',
            'company',
            'to_xydm',
            'type',
            'remarks',
            'state',
            'confirm_date',
            'created_at',
        ];
        $list=MarketAmount::
            where($where)
            ->select($filed)
            ->orderBy($sortField,$sort)
            ->paginate($limit);
//        dump(DB::getQueryLog());
        return $list;
    }
    /**增加企业消费金额**/
    protected function add_khxx_amount($xydm,$amount){
        MarketCustomer::where('xydm',$xydm)->increment('amount',$amount);
    }
    /**增加企业消费金额 新表**/
    protected function add_amount($xydm,$toxydm,$amount){
        MarketInvoiceAmount::where('xydm',$xydm)->where('to_xydm',$toxydm)->increment('amount',$amount);
    }
    /**增加企业开票余额 新表**/
    protected function add_amount_n($xydm,$toxydm,$amount){
        MarketInvoiceAmount::where('xydm',$xydm)->where('to_xydm',$toxydm)->increment('amount_n',$amount);
    }
}