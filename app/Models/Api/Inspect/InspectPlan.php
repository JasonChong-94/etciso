<?php
/**
 * Created by PhpStorm.
 * User: Greatwall
 * Date: 2019/8/13
 * Time: 17:53
 */
namespace App\Models\Api\Inspect;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class InspectPlan extends Model{

    //指定表名
    protected $table = 'qyht_htrza';

    //自动维护时间戳  默认是'true'
    public $timestamps = false;

    public function systemState()
    {
        return $this->hasOne('App\Models\Api\System\SystemState','code','audit_phase');
    }

    public function planUser()
    {
        return $this->hasMany('App\Models\Api\Inspect\InspectAuditTeam','ap_id','id');
    }

    public function examineSystem()
    {
        return $this->belongsTo('App\Models\Api\Examine\ExamineSystem','xm_id','id');
    }

    //审核阶段
    protected function IndexPlan($cndtn,$where,$orWhere,$sortField=''){
        $flighs = InspectPlan::
        join('qyht_htrzu', 'qyht_htrzu.ap_id', '=', 'qyht_htrza.id')
            ->join('users', 'users.id', '=', 'qyht_htrzu.us_id')
            ->select($cndtn)
            ->where($where)
            ->where(function ($query) use ($orWhere) {
                if(!empty($orWhere)){
                    foreach($orWhere as $vel){
                        $query->orWhere($vel);
                    }
                }
            })
            ->when($sortField,function ($query) use ($sortField) {
                foreach($sortField as $key => $vel){
                    return  $query->orderBy($key, $vel);
                }
            })
            ->get();
        return($flighs);
    }

    //批量更新
    protected function UpdateBatch($multipleData = [])
    {
        try {
            if (empty($multipleData)) {
                throw new \Exception("数据不能为空");
            }
            $tableName = DB::getTablePrefix() . $this->getTable(); // 表名
            $firstRow  = current($multipleData);

            $updateColumn = array_keys($firstRow);
            // 默认以id为条件更新，如果没有ID则以第一个字段为条件
            $referenceColumn = isset($firstRow['id']) ? 'id' : current($updateColumn);
            unset($updateColumn[0]);
            // 拼接sql语句
            $updateSql = "UPDATE " . $tableName . " SET ";
            $sets      = [];
            $bindings  = [];
            foreach ($updateColumn as $uColumn) {
                $setSql = "`" . $uColumn . "` = CASE ";
                foreach ($multipleData as $data) {
                    $setSql .= "WHEN `" . $referenceColumn . "` = ? THEN ? ";
                    $bindings[] = $data[$referenceColumn];
                    $bindings[] = $data[$uColumn];
                }
                $setSql .= "ELSE `" . $uColumn . "` END ";
                $sets[] = $setSql;
            }
            $updateSql .= implode(', ', $sets);
            $whereIn   = collect($multipleData)->pluck($referenceColumn)->values()->all();
            $bindings  = array_merge($bindings, $whereIn);
            $whereIn   = rtrim(str_repeat('?,', count($whereIn)), ',');
            $updateSql = rtrim($updateSql, ", ") . " WHERE `" . $referenceColumn . "` IN (" . $whereIn . ")";
            // 传入预处理sql语句和对应绑定数据
            return DB::update($updateSql, $bindings);
        } catch (\Exception $e) {
            return false;
        }
    }

    //结合阶段项目
    protected function UnionPlan($cndtn,$union,$where=''){
        $flighs = InspectPlan::
        join('examine_stage','examine_stage.code', '=','qyht_htrza.audit_phase')
            ->join('qyht_htrz', 'qyht_htrz.id', '=', 'qyht_htrza.xm_id')
            ->select($cndtn)
            ->when($where,function ($query) use ($where) {
                return  $query->where($where);
            })
            ->whereIn('qyht_htrza.id', $union)
            ->get();
        return($flighs);
    }

    //阶段项目
    protected function PhasePlan($cndtn,$where){
        $flighs = InspectPlan::
        join('examine_stage','examine_stage.code', '=','qyht_htrza.audit_phase')
            ->join('qyht_htrz', 'qyht_htrz.id', '=', 'qyht_htrza.xm_id')
            ->select($cndtn)
            ->where($where)
            ->get();
        return($flighs);
    }

    //审核阶段
    protected function TimePlan($where,$orWhere){
        $flighs = InspectPlan::
            join('qyht_htrz', 'qyht_htrz.id', '=', 'qyht_htrza.xm_id')
            ->join('qyht', 'qyht.id', '=', 'qyht_htrz.ht_id')
            ->join('khxx', 'khxx.id', '=', 'qyht.kh_id')
            ->join('qyht_htrzu', 'qyht_htrzu.ap_id', '=', 'qyht_htrza.id')
            ->select('khxx.id')
            ->where($where)
            ->where(function ($query) use ($orWhere) {
                foreach($orWhere as $vel){
                    $query->orWhere($vel);
                }
            })
            ->get();
        return($flighs);
    }
}
