<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/5/27
 * Time: 22:08
 */

namespace App\Socket\Model;



class Base
{

    //方便使用钩子，全部使用私有函数
    public static function __callStatic($name, $arguments)
    {
        foreach ($arguments as $k => $v){
            if($v instanceof \App\Socket\Pool\MysqlObject)
                return static::$name(...$arguments);
        }
        $mysqlPool = \App\Socket\Utility\Pool\PoolManager::getInstance()->getPool(\App\Socket\Pool\MysqlPool::class);
        $db = $mysqlPool->getObj();
        $res = static::$name($db, ...$arguments);
        $mysqlPool->recycleObj($db);
        return $res;
    }

    /**
     * 更新一个表
     * @param array $update 要更新的数据
     * @param string $whereField 根据哪个键值作为where条件
     * @param $table
     * @return bool
     */
    protected static function batchUpdate($db, array $update, $whereField = 'id', $table)
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
            return $db->rawQuery($updateSql, $bindings);
        } catch (\Exception $e) {
            return false;
        }
    }
}