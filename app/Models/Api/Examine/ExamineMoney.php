<?php
/**
 * Created by PhpStorm.
 * User: Greatwall
 * Date: 2019/8/13
 * Time: 17:53
 */
namespace App\Models\Api\Examine;
use Illuminate\Database\Eloquent\Model;

class ExamineMoney extends Model{

    //指定表名
    protected $table = 'qyht_money';

    //自动维护时间戳  默认是'true'
    public $timestamps = false;

    //指定允许批量的字段
    protected $fillable = ['htfy','htbz','fylx'];

    //指定不允许批量赋值的字段
    protected $guarded=[];

    /**合同金额**/
    protected function IndexMoney($cndtn,$where){
        $flighs = ExamineMoney::select($cndtn)
            ->leftJoin('examine_activity', 'examine_activity.code', '=', 'qyht_money.fylx')
            ->leftJoin('money_type', 'money_type.code', '=', 'qyht_money.htbz')
            ->where($where)
            ->get();
        return($flighs);
    }
}
