<?php
/**
 * Created by PhpStorm.
 * User: yf
 * Date: 2018/10/26
 * Time: 7:23 PM
 */

namespace App\Socket\Pool;


use App\Socket\Utility\Pool\AbstractPool;

class Redis1Pool extends AbstractPool
{
    protected function createObject()
    {
        // TODO: Implement createObject() method.
        $redis = new Redis1Object();
        $conf = config('swoole.REDISPOOL1');
        if( $redis->connect($conf['host'],$conf['port'])){
            if(!empty($conf['auth'])){
                $redis->auth($conf['auth']);
            }
            return $redis;
        }else{
            return null;
        }
    }
}