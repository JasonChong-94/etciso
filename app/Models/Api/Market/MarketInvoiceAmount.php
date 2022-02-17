<?php
/**
 * Created by PhpStorm.
 * User: Greatwall
 * Date: 2022/2/10
 * Time: 10:33
 */
namespace App\Models\Api\Market;
use Illuminate\Database\Eloquent\Model;
date_default_timezone_set('Asia/Shanghai');
class MarketInvoiceAmount extends Model{

    /**定义表名**/
    protected $table = 'khxx_invoice_amount';
    protected $fillable=['qymc','xydm','company','to_xydm','amount_y','amount_n'];
    protected function index($where=[],$limit=15,$sortField='time',$sort='desc'){
        $filed=[
            'id',
            'qymc',
            'xydm',
            'company',
            'to_xydm',
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
}