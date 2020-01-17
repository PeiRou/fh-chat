<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/5/27
 * Time: 22:10
 */

namespace App\Socket\Model;


class ChatHongbaoBlacklist extends Base
{
    public static $users = [];
    public static $upUsersTime;
    protected static $DB_READ_FUNCTION = [];

    public static function getUsers($chat_hongbao_idx = 0)
    {
        if(!isset(self::$users[$chat_hongbao_idx]) || self::$upUsersTime < (time() - 60 * 60 * 2)){
            $mysqlPool = \App\Socket\Utility\Pool\PoolManager::getInstance()->getPool(\App\Socket\Pool\MysqlPool::class);
            $db = $mysqlPool->getObj();
            self::upUsers($db, $chat_hongbao_idx);
            $mysqlPool->recycleObj($db);
        }
        return self::$users[$chat_hongbao_idx];
    }

    protected static function upUsers($db, $chat_hongbao_idx)
    {
        $users = $db->where('chat_hongbao_idx', $chat_hongbao_idx)->get('chat_hongbao_blacklist', null, 'user_id');
        self::$users[$chat_hongbao_idx] = array_map(function($v){
            return $v['user_id'];
        }, $users);
        self::$upUsersTime = time();
        return self::$users[$chat_hongbao_idx];
    }

}