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

class InspectAuditTeam extends Model{

    //指定表名
    protected $table = 'qyht_htrzu';

    //自动维护时间戳  默认是'true'
    public $timestamps = false;

    public function userBasic()
    {
        return $this->belongsTo('App\Models\Api\User\UserBasic','us_id','id');
    }

    public function inspectPlan()
    {
        return $this->belongsTo('App\Models\Api\Inspect\InspectPlan','ap_id','id');
    }

    protected function auditTeam(){
        $flighs = InspectAuditTeam::with(['inspectPlan:id,start_time,end_time', 'userBasic:id,name'])
            ->paginate(15);
        return($flighs);
    }

    //审核阶段
    protected function IndexTeam($cndtn,$where){
        $flighs = InspectAuditTeam::
            leftJoin('users', 'users.id', '=', 'qyht_htrzu.us_id')
            ->select($cndtn)
            ->where($where)
            ->get();
        return($flighs);
    }

    //审核阶段
    protected function TeamMajor($cndtn,$where){
        $flighs = InspectAuditTeam::
            leftJoin('qyht_htrza', 'qyht_htrza.id', '=', 'qyht_htrzu.ap_id')
            ->leftJoin('qyht_htrz', 'qyht_htrz.id', '=', 'qyht_htrza.xm_id')
            ->select($cndtn)
            ->where($where)
            ->get();
        return($flighs);
    }

    //项目人员
    protected function UserTeam($cndtn,$where){
        $flighs = InspectAuditTeam::
            leftJoin('users', 'users.id', '=', 'qyht_htrzu.us_id')
            ->join('major_user','major_user.us_id', '=','users.id')
            ->leftJoin('fzjg', 'fzjg.id', '=', 'users.region')
            ->select($cndtn)
            ->where($where)
            ->get();
        return($flighs);
    }

    //项目人员
    protected function PlanTeam($cndtn,$where,$whereIn=''){
        $flighs = InspectAuditTeam::
            leftJoin('users', 'users.id', '=', 'qyht_htrzu.us_id')
            ->join('major_user','major_user.us_id', '=','users.id')
            ->select($cndtn)
            ->where($where)
            ->when($whereIn,function ($query) use ($whereIn) {
                return  $query->whereIn('ap_id',$whereIn);
            })
            ->get();
        return($flighs);
    }

    //项目人员
    protected function UserPlan($cndtn,$where,$whereIn=''){
        $flighs = InspectAuditTeam::
        Join('qyht_htrza', 'qyht_htrza.id', '=', 'qyht_htrzu.ap_id')
            ->Join('users', 'users.id', '=', 'qyht_htrzu.us_id')
            ->join('major_user','major_user.us_id', '=','users.id')
            ->select($cndtn)
            ->where($where)
            ->when($whereIn,function ($query) use ($whereIn) {
                return  $query->whereIn('ap_id',$whereIn);
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
}
