<?php
/**
 * Created by PhpStorm.
 * User: Greatwall
 * Date: 2019/8/13
 * Time: 17:53
 */
namespace App\Models\Api\Market;
use Illuminate\Database\Eloquent\Model;

class MarketChange extends Model{

    /**定义表名**/
    protected $table = 'khxx_change';

    public $timestamps = false;

    /**变更信息列表**/
    protected function IndexChange($where,$limit,$sortField,$sort){
        $flighs = MarketChange::
            where($where)
            ->orderBy($sortField,$sort)
            ->paginate($limit);
        return($flighs);
    }
}
