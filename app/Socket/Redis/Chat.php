<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/6/6
 * Time: 19:55
 */

namespace App\Socket\Redis;


class Chat extends Base
{
    //方便使用钩子，全部使用私有函数
    public static function __callStatic($name, $arguments)
    {
        foreach ($arguments as $k => $v){
            if($v instanceof \App\Socket\Pool\RedisObject)
                return static::$name(...$arguments);
        }
        $redisPool = \App\Socket\Utility\Pool\PoolManager::getInstance()->getPool(\App\Socket\Pool\RedisPool::class);

        $redis = $redisPool->getObj();
        $redis->select(REDIS_DB_CHAT_USER_MAP);
        $res = static::$name($redis, ...$arguments);
        $redisPool->recycleObj($redis);
        return $res;
    }

    //绑定User和fd的关系
    protected static function bindUser($redis, int $userId, int $fd)
    {
        self::setFdUserMap($redis, $userId, $fd);
        self::setUserFdMap($redis, $userId, $fd);
    }

    //设置Fd => userId 映射
    protected static function setFdUserMap($redis, int $userId, int $fd)
    {
        return $redis->hSet('fdUserIdMap', $fd, $userId);
    }

    //设置User => Fd 映射
    protected static function setUserFdMap($redis, int $userId, int $fd)
    {
        $fdList = self::findFdListToUserId($redis, $userId);
        // 检查此user 是否已经存在fd
        if (is_null($fdList)) {
            $fdList = [];
        }
        array_push($fdList, $fd);

        self::setUserFdList($redis, $userId, $fdList);
    }

    //通过userId 查询 fd list
    protected static function findFdListToUserId($redis, int $userId)
    {
        return array_diff(explode(',', $redis->hGet('userIdFdMap', $userId)), ['']);
    }

    //设置User Fd list
    protected static function setUserFdList($redis, int $userId, array $fdList)
    {
        $redis->hSet('userIdFdMap', $userId, implode(',',array_unique($fdList)));
    }
    //通过Fd 删除UserId => Fd Map
    protected static function deleteUserIdFdMapByFd($redis, int $fd)
    {
        $userId = self::findUserIdByFd($redis, $fd);
        $fdList = self::findFdListToUserId($redis, $userId);
        if($fdList){
            foreach ($fdList as $number => $valFd) {
                if ($valFd == $fd) {
                    unset($fdList[$number]);
                }
            }
        }
        if($userId && $userId>0){
            self::setUserFdList($redis, $userId, $fdList);
        }
    }
    //通过Fd 删除 Fd => UserId Map
    protected static function deleteFdUserIdMapByFd($redis, int $fd)
    {
        $redis->hDel('fdUserIdMap', $fd);
    }
    //通过fd 查询 userId
    protected static function findUserIdByFd($redis, int $fd)
    {
        return (int)$redis->hGet('fdUserIdMap', $fd);
    }
    //获取User的Fd
    protected static function getUserFd($redis, int $userId)
    {
        return self::findFdListToUserId($redis, $userId);
    }
    // 获取UserId
    protected static function getUserId($redis, int $fd)
    {
        return self::findUserIdByFd($redis, $fd);
    }

    // 获取所有UserId
    protected static function getUserIds($redis)
    {
        return $redis->Hkeys('userIdFdMap') ?? [];
    }

    // 清除redis 用户在线信息
    protected static function clearAll($redis)
    {
        foreach ($redis->hKeys('fdUserIdMap') as $v){
            $redis->hDel('fdUserIdMap', $v);
        }

        foreach ($redis->hKeys('userIdFdMap') as $v){
            $redis->hDel('userIdFdMap', $v);
        }
    }
}