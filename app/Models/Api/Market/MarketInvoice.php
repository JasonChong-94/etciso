<?php
/**
 * Created by PhpStorm.
 * User: Greatwall
 * Date: 2021/6/16
 * Time: 17:43
 */
namespace App\Models\Api\Market;
use Illuminate\Database\Eloquent\Model;
date_default_timezone_set('Asia/Shanghai');
class MarketInvoice extends Model{

    /**定义表名**/
    protected $table = 'khxx_invoice';

    protected function index($where=[],$limit=15,$sortField='created_at',$sort='desc'){
        $filed=[
            'khxx_invoice.id as invoice_id',
            'khxx_id',
            'kh_name',
            'user_name',
            'sh_name',
            'tax_no',
            'address',
            'phone',
            'phone',
            'bank',
            'cate',
            'account',
            'hw_name',
            'ggxh',
            'unit',
            'num',
            'price',
            'khxx_invoice.amount as money',
            'company',
            'to_xydm',
            'type',
            'remarks',
            'state',
            'khxx.amount',
            'khxx.amount_y',
            'khxx.amount_n',
            'confirm_date',
            'created_at',
        ];
        $list=MarketInvoice::
        leftJoin('khxx', 'khxx.id', '=', 'khxx_invoice.khxx_id')
            ->where($where)
            ->select($filed)
            ->orderBy($sortField,$sort)
            ->paginate($limit);
//        dump(DB::getQueryLog());
        return $list;
    }

    /**增加企业已开票金额**/
    protected function add_amount_y($xydm,$toxydm,$amount){
        MarketInvoiceAmount::where('xydm',$xydm)->where('to_xydm',$toxydm)->increment('amount_y',$amount);
    }
    /**减少企业已开票金额**/
    protected function edit_amount_y($xydm,$toxydm,$amount){
        MarketInvoiceAmount::where('xydm',$xydm)->where('to_xydm',$toxydm)->decrement('amount_n',$amount);
    }
}