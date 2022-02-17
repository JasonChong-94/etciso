<?php
/**
 * Created by PhpStorm.
 * User: Greatwall
 * Date: 2019/8/13
 * Time: 17:53
 */
namespace App\Models\Api\System;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class SystemUpdate extends Model{

    /**批量更新**/
    protected function UpdateBatch($table,$multipleData = [])
    {
        try {
            if (empty($multipleData)) {
                throw new \Exception("数据不能为空");
            }
            $tableName = DB::getTablePrefix() . $table; // 表名
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

    /**批量添加**/
    protected function InsertBatch($table,$multipleData = [])
    {
        try {
            if (empty($multipleData)) {
                throw new \Exception("数据不能为空");
            }
            $tableName = DB::getTablePrefix() . $table; // 表名
            $firstRow  = current($multipleData);
            $insertColumn = array_keys($firstRow);
            $keys = '('.implode(',',$insertColumn).')';
            // 拼接sql语句
            $insertSql = "INSERT IGNORE INTO " . $tableName .' '. $keys .' VALUES';
            // 传入预处理sql语句和对应绑定数据
            foreach ($multipleData as $data) {
                $whereIn[] = "(" . rtrim(str_repeat('?,', count($insertColumn)), ',') . ")";
                foreach ($insertColumn as $value) {
                    $bindings[] = $data[$value];
                }
            }
            $insertSql .= implode(', ', $whereIn);
            $insertSql = $insertSql.';';
            return DB::insert($insertSql,$bindings);
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }
}
