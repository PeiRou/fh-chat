<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Base extends Model
{

    /**
     * 更新一个表
     * @param array $update 要更新的数据
     * @param string $whereField 根据哪个键值作为where条件
     * @param $table
     * @return bool
     */
    public static function batchUpdate(array $update, $whereField = 'id', $table)
    {
        try {
            if (empty($update)) {
                throw new \Exception("数据不能为空");
            }
            $tableName = $table; // 表名
            $firstRow  = current($update);
            $updateColumn = array_keys($firstRow);
            $referenceColumn = isset($firstRow[$whereField]) ? $whereField : current($updateColumn);
            // 拼接sql语句
            $updateSql = "UPDATE " . $tableName . " SET ";
            $sets      = [];
            $bindings  = [];
            foreach ($updateColumn as $uColumn) {
                if($uColumn == $referenceColumn) continue;
                $setSql = "`" . $uColumn . "` = CASE ";
                foreach ($update as $data) {
                    $setSql .= "WHEN `" . $referenceColumn . "` = ? THEN ? ";
                    $bindings[] = $data[$referenceColumn];
                    $bindings[] = $data[$uColumn];
                }
                $setSql .= "ELSE `" . $uColumn . "` END ";
                $sets[] = $setSql;
            }
            $updateSql .= implode(', ', $sets);
            $whereIn   = collect($update)->pluck($referenceColumn)->values()->all();
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
