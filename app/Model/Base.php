<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use SameClass\Service\Cache;

class Base extends Model
{
    use Cache;
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
                return false;
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

    //获取制定栏位的数据，默认为id
    public function getDataByField($param,$field = ''){
        $field = empty($field)?$this->primaryKey:$field;
        return $this->where($field,$param)->first();
    }


}
