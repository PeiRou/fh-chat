<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/5/27
 * Time: 22:10
 */

namespace App\Socket\Model;


use App\Service\Cache;

class ChatUser extends Base
{
    use Cache;

    //用户信息
    protected static function getUser($db, $param = [])
    {
        return self::HandleCacheData(function()use($db, $param){
            foreach ($param as $k=>$v)
                $db->where($k, $v);
            return $db->getOne('chat_users');
        }, 5);
    }

    //用户信息单个值
    protected static function getUserValue($db, $param = [], $value)
    {
        return self::HandleCacheData(function()use($db, $param, $value){
            foreach ($param as $k=>$v)
                $db->where($k, $v);
            return $db->getOne('chat_users', [$value])[$value] ?? null;
        }, 5);
    }

    //用户名称 昵称
    protected static function getUserName($db, $param = [])
    {
        return self::HandleCacheData(function()use($db, $param){
            foreach ($param as $k=>$v)
                $db->where($k, $v);
            return $db->getOne('chat_users', ['nickname'])['nickname'] ?? null;
        }, 5);
    }

    //查会员角色
    protected static function getUserRole($db, $param = [])
    {
        return self::RedisCacheData(function()use($db, $param){
            foreach ($param as $k=>$v)
                $db->where($k, $v);
            return $db->getValue('chat_users', 'chat_role');
        }, 10);
    }

    //搜索
    protected static function search($db, $param = [], int $userId, int $limit = 20)
    {
        $chatUsersWhere = ' 1 AND `chat_users`.`users_id` <> '.$userId;
        $chatRoomWhere = ' 1 ';
        $aParam = [];
        $limit = '';
        if($limit)
            $limit = ' LIMIT ' . $limit;
        if(isset($param['chat_role'])){
            $chatUsersWhere .= ' AND `chat_users`.`chat_role` = '.(int)$param['chat_role'];
        }
        if(isset($param['name'])){
            $chatUsersWhere .= ' AND `chat_users`.`username` LIKE ? ';
            array_push($aParam, $param['name'].'%');
            $chatRoomWhere .= ' AND `chat_room`.`room_name` LIKE ? ';
            array_push($aParam, $param['name'].'%');
        }
        $sql = " SELECT
                    `chat_users`.`users_id` AS `id`,
                    `chat_users`.`username` AS `name`,
                    IF
                        ( `chat_friends_list`.`to_id`, 1, 0 ) AS is_with,
                        'users' AS `type` 
                    FROM
                        `chat_users`
                        LEFT JOIN `chat_friends_list` ON `chat_friends_list`.`to_id` = `chat_users`.`users_id` 
                        AND `chat_friends_list`.`user_id` = {$userId} 
                    WHERE
                        {$chatUsersWhere} 
                        {$limit}
                     UNION ALL
                    
                    SELECT
                        `chat_room`.`room_id` AS `id`,
                        `chat_room`.`room_name` AS `name`,
                    IF
                        ( `chat_room_dt`.`user_id`, 1, 0 ) AS is_with,
                        'room' AS `type` 
                    FROM
                        `chat_room`
                        LEFT JOIN `chat_room_dt` ON `chat_room_dt`.`id` = `chat_room`.`room_id` 
                        AND `chat_room_dt`.`user_id` = {$userId}
                    WHERE
                        {$chatRoomWhere}
                        {$limit}
                    ";
        return $db->rawQuery($sql, $aParam);
    }
}