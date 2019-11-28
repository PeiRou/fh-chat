<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/6/6
 * Time: 19:55
 */

namespace App\Socket\Redis;


class Redis extends Base
{

    // 验证一个键是否存在设置缓存时间
    protected static function check($redis, string $key, $time = null, $db = 1)
    {
        $redis->select($db);
        if(!$redis->setnx($key, 'no')){
            return false;
        }
        if($time)
            $redis->expire($key, $time);
        return true;
    }

    protected static function del($redis, string $key, $db = 1)
    {
        $redis->select($db);
        return $redis->del($key);
    }
}