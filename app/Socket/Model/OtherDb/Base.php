<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/5/27
 * Time: 22:08
 */

namespace App\Socket\Model\OtherDb;



class Base
{

    //方便使用钩子，全部使用私有函数
    public static function __callStatic($name, $arguments)
    {
        if(static::DRIVER == 'db'){
            $mysqlPool = \App\Socket\Utility\Pool\PoolManager::getInstance()->getPool(\App\Socket\Pool\Mysql2Pool::class);
            $db = $mysqlPool->getObj();
            $res = static::$name($db, ...$arguments);
            $mysqlPool->recycleObj($db);
            return $res;
        }elseif(static::DRIVER == 'file'){
            $res = static::$name(null, ...$arguments);
            return $res;
        }

    }
}