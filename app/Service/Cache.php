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
     * $nullIsCache
     */
    public static function HandleCacheData(\Closure $closure, $time = 1, $nullIsCache = true, $isSaveCache = false, ...$args)
    {
        $res = new \ReflectionFunction ($closure);
        $param = $res->getStaticVariables();
        if(isset($param['db']) && $param['db'] instanceof \App\Socket\Pool\MysqlObject)
            unset($param['db']);
        $key = md5((string)$res . $time . $nullIsCache . json_encode($param) . json_encode($args));
        $cache = self::CaCheInstance();
        if(!($val = $cache->get($key, false)) || $isSaveCache === true){
            $val = call_user_func($closure, ...$args);
            if(!$nullIsCache && (empty($val) || !$val || !count((array)$val)))
                return $val;
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
        $path =  'Cache/'.str_replace('\\','_',static::class);
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
     * @nullIsCache  如果数据为空是否还缓存
     * $isSaveCache 某些需要实时数据的就传true 获取实时数据并且更新缓存
     */
    public static function RedisCacheData(\Closure $closure, $time = 1, $nullIsCache = true, $isSaveCache = false, ...$args)
    {
        $res = new \ReflectionFunction ($closure);
        $param = $res->getStaticVariables();
        if(isset($param['db']) && $param['db'] instanceof \App\Socket\Pool\MysqlObject)
            unset($param['db']);
        $key = md5((string)$res . $time . $nullIsCache . json_encode($param) . json_encode($args));
        return \App\Socket\Pool\RedisPool::invoke(function (\App\Socket\Pool\RedisObject $redis) use($isSaveCache, $key, $closure, $nullIsCache, $args, $time) {
            $redis->select(12);
            if($redis->exists($key) && ($data = unserialize($redis->get($key))) && $isSaveCache === false){
                return $data;
            }else{
                $val = call_user_func($closure, ...$args);
                if(!$nullIsCache && (empty($val) || !$val || !count((array)$val)))
                    return $val;
                $redis->setex($key , $time, serialize($val));
                return $val;
            }
        });
    }


}