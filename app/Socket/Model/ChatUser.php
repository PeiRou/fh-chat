<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/5/27
 * Time: 22:10
 */

namespace App\Socket\Model;



class ChatUser extends Base
{

    //用户信息
    protected static function getUser($db, $param = [], $isSaveCache = false)
    {
        return self::RedisCacheData(function()use($db, $param){
            foreach ($param as $k=>$v)
                $db->where($k, $v);
            return $db->getOne('chat_users');
        }, 30, false, $isSaveCache);
    }

    //用户信息单个值
    protected static function getUserValue($db, $param = [], $value)
    {
        return self::RedisCacheData(function()use($db, $param, $value){
            foreach ($param as $k=>$v)
                $db->where($k, $v);
            return $db->getOne('chat_users', [$value])[$value] ?? null;
        }, 10);
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
    protected static function search($db, $param = [], int $userId, int $len = 20, $nocache = false)
    {
        return self::RedisCacheData(function() use($db, $param, $userId, $len){
            $chatUsersWhere = ' 1 AND `chat_users`.`users_id` <> '.$userId;
            $chatRoomWhere = ' 1 ';
            $aParam = [];
            $limit = '';
            if($len)
                $limit = ' LIMIT ' . $len;
            if(isset($param['chat_role'])){
                $chatUsersWhere .= ' AND `chat_users`.`chat_role` = '.(int)$param['chat_role'];
            }
            if(isset($param['name'])){
                $chatUsersWhere .= ' AND `chat_users`.`username` LIKE ? ';
                array_push($aParam, $param['name'].'%');
                $chatRoomWhere .= ' AND `chat_room`.`room_name` LIKE ? ';
                array_push($aParam, $param['name'].'%');
            }
            $sql = " (SELECT
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
                        {$limit} )
                     UNION ALL
                    
                    ( SELECT
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
                        {$limit} )
                    ";
            return $db->rawQuery($sql, $aParam);
        }, 60, true, $nocache); # redis60秒缓存 这样一样的搜索条件不会一直请求数据库
    }

    //搜索用户-用户详细信息
    protected static function searchUserInfo($db, $userId, int $toUserId, $nocache = false)
    {
        return self::RedisCacheData(function() use($db, $userId, $toUserId){
            $sql = " SELECT `u`.`users_id`, `u`.`username`, `u`.`nickname`, `u`.`img`, !ISNULL(`l`.`to_id`) AS `is_friend`, IFNULL(`l`.`remark`, '') AS `remark`, IFNULL(`l`.`status`, 0) AS `status` 
                FROM `chat_users` as `u`
                LEFT JOIN `chat_friends_list` AS `l` ON `l`.`to_id` = `u`.`users_id` AND `l`.`user_id` = {$userId}
                WHERE `u`.`users_id` = {$toUserId} ";
            return $db->rawQuery($sql)[0] ?? null;
        }, 30, false, $nocache);

    }

    protected static function getList($db, $param = [], $flow = [])
    {
        extract($flow);
        $whereRaw = $whereRaw ?? [];
        $column = $column ?? null;

        return self::RedisCacheData(function() use($db, $param, $whereRaw, $column){
            foreach ($param as $k=>$v)
                $db->where($k, $v);
            foreach ($whereRaw as $v)
                $db->where($v);
            return $db->get('chat_users', null, $column);
        },30, false, $nocache ?? false);
    }
}