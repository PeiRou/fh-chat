<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/5/11
 * Time: 21:18
 */

namespace App\Service;


use Illuminate\Cache\FileStore;
use Illuminate\Cache\TaggedCache;
use Illuminate\Cache\TagSet;

trait Cache
{
    /**
     * 缓存
     * @param \Closure $closure 如果没有缓存执行的回调
     * @param int $time 缓存时间 分
     * @param mixed ...$args 附加参数
     */
    public static function HandleCacheData(\Closure $closure, $time = 1, ...$args)
    {
        $res = new \ReflectionFunction ($closure);
        $param = $res->getStaticVariables();
        if(isset($param['db']) && $param['db'] instanceof \App\Socket\Pool\MysqlObject)
            unset($param['db']);
        $key = md5((string)$res . $time . json_encode($param) . json_encode($args));
        $cache = self::CaCheInstance();
        if(!($val = $cache->get($key, false))){
            $val = call_user_func($closure, ...$args);
            $cache->put($key, $val, $time);
        }
        return $val;
    }

    /**
     * 获取缓存的实例-没用到laravel的cache辅助函数是为了删除这些缓存的时候分了文件夹方便
     * @return mixed
     */
    public static function CaCheInstance()
    {
        $path =  'Cache/'.str_replace('\\','_',get_class());
        static $Cache = [];
        if(empty($Cache[$path])){
            $store = new FileStore(new \Illuminate\Filesystem\Filesystem(), storage_path($path));
            $TagSet = new TagSet($store);
            $Cache[$path] = new TaggedCache($store, $TagSet);
        }
        return $Cache[$path];
    }

    /**
     * redis缓存
     * @param \Closure $closure 如果没有缓存执行的回调
     * @param int $time 缓存时间 秒
     * @param mixed ...$args 附加参数
     */
    public static function RedisCacheData(\Closure $closure, $time = 1, ...$args)
    {
        $res = new \ReflectionFunction ($closure);
        $param = $res->getStaticVariables();
        if(isset($param['db']) && $param['db'] instanceof \App\Socket\Pool\MysqlObject)
            unset($param['db']);
        $key = md5((string)$res . $time . json_encode($param) . json_encode($args));
        return \App\Socket\Pool\RedisPool::invoke(function (\App\Socket\Pool\RedisObject $redis) use($key, $closure, $args, $time) {
            $redis->select(12);
            if($redis->exists($key) && $data = unserialize($redis->get($key))){
                return $data;
            }else{
                $val = call_user_func($closure, ...$args);
                $redis->setex($key , $time, serialize($val));
                return $val;
            }
        });
    }


}