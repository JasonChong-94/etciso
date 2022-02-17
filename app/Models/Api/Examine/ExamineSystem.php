<?php
/**
 * Created by PhpStorm.
 * User: Greatwall
 * Date: 2019/8/13
 * Time: 17:53
 */
namespace App\Models\Api\Examine;
use Illuminate\Database\Eloquent\Model;

class ExamineSystem extends Model{

    //指定表名
    protected $table = 'qyht_htrz';

    //自动维护时间戳  默认是'true'
    public $timestamps = false;

    //指定允许批量的字段
    protected $fillable = ['ht_id','rztx','shlx','rzfw'];

    //指定不允许批量赋值的字段
    protected $guarded=[];

    public function marketContract()
    {
        return $this->belongsTo('App\Models\Api\Market\MarketContract','ht_id','id');
    }

    public function inspectPlan()
    {
        return $this->hasMany('App\Models\Api\Inspect\InspectPlan','xm_id','id');
    }

    //认证项目
    protected function IndexSystem($cndtn,$where){
        $flighs = ExamineSystem::
            leftJoin('examine_activity', 'examine_activity.code', '=', 'qyht_htrz.shlx')
            ->select($cndtn)
            ->where($where)
            ->get();
        return($flighs);
    }

    //项目详情
    protected function DetailSystem($cndtn,$where){
        $flighs = ExamineSystem::
        join('qyht','qyht.id','=','qyht_htrz.ht_id')
            ->leftJoin('xiangmu','xiangmu.xiangmu', '=','qyht_htrz.rztx')
            ->select($cndtn)
            ->where($where)
            ->get();
        return($flighs);
    }

    //修改客户
    protected function EditSystem($where,$data){
        $flighs = ExamineSystem::where($where)
            ->update($data);
        return($flighs);
    }
}
