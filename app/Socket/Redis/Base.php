<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/5/27
 * Time: 22:08
 */

namespace App\Socket\Redis;


use App\Service\Cache;

class Base
{
    use Cache;

    //方便使用钩子，全部使用私有函数
    public static function __callStatic($name, $arguments)
    {
        foreach ($arguments as $k => $v){
            if($v instanceof \App\Socket\Pool\RedisObject)
                return static::$name(...$arguments);
        }
        $mysqlPool = \App\Socket\Utility\Pool\PoolManager::getInstance()->getPool(\App\Socket\Pool\RedisPool::class);
        $db = $mysqlPool->getObj();
        $res = static::$name($db, ...$arguments);
        $mysqlPool->recycleObj($db);
        return $res;
    }

}