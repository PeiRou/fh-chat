<?php

namespace App\Socket\Pool;

use App\Socket\Utility\Pool\AbstractPool;
use EasySwoole\Mysqli\Config;

class MysqlPool extends AbstractPool
{
    protected function createObject()
    {
        //当连接池第一次获取连接时,会调用该方法
        //我们需要在该方法中创建连接
        //返回一个对象实例
        //必须要返回一个实现了AbstractPoolObject接口的对象
        $conf = config('swoole.MYSQLPOOL');
        $dbConf = new Config($conf);
        return new MysqlObject($dbConf);
        // TODO: Implement createObject() method.
    }
}