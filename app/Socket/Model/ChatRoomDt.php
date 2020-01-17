<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/5/29
 * Time: 21:00
 */

namespace App\Socket\Model;



class ChatRoomDt extends Base
{
    protected static $DB_READ_FUNCTION = ['getRoomUserIds', 'is_pushbet', 'pushbetRooms', 'uMapRoomInfo', 'getOne'];

    protected static function getRoomUserIds($db, $roomId)
    {
        //获取聊天室所有会员id 缓存5秒
        return self::RedisCacheData(function() use($roomId, $db){
            $ids = $db->where('id', $roomId)->get('chat_room_dt', null, ['user_id']);
            $data = [];
            while ($ids){
                $data[] = array_shift($ids)['user_id'];
            }
            return $data;
        }, 5);
    }

    //删除user表里已经删掉的会员
    protected static function clearInvalidUser($db)
    {
        $sql = ' SELECT
                    `chat_room_dt`.`id`,
                    `chat_room_dt`.`user_id`,
                    `users`.`id` AS `uid` 
                FROM
                    `chat_room_dt`
                    LEFT JOIN `users` ON `chat_room_dt`.`user_id` = `users`.`id`
                    WHERE `users`.`id`  is null ';
        $list = $db->rawQuery($sql);
        if(count($list)){
            foreach ($list as $v){
                $del = true;
                $db->whereOr("(`id` = ? AND `user_id` = ?)", Array($v['id'],$v['user_id']));
            }
            if(isset($del) && $del){
                $db->delete('chat_room_dt');
            }
        }
    }

    //是否推送此会员的注单 0不跟单1跟单
    protected static function is_pushbet($db, $roomId, $userId)
    {
        self::RedisCacheData(function() use($db, $roomId, $userId){
            return $db->where('id', $roomId)
                ->where('user_id', $userId)
                ->getValue('is_pushbet') ?? 0;
        });
    }
    //获取用户可以推送跟单的房间
    protected static function pushbetRooms($db, $userId)
    {
        return self::RedisCacheData(function() use($db, $userId){
            $res = $db->where('user_id', $userId)
                ->where('is_pushbet', 1)
                ->get('chat_room_dt', null, ['id']);
            return array_map(function($val){
                return $val['id'];
            }, $res);
        }, 30);
    }

    protected static function uMapRoomInfo($db, $param = [],  $isSaveCache = false)
    {
        return self::RedisCacheData(function()use($db, $param){
            $page = [];
            if(isset($param['page'], $param['rows'])){
                array_push($page, ($param['page'] - 1) * $param['rows']);
                array_push($page, $param['rows']);
                unset($param['page']);
                unset($param['rows']);
            }

            foreach ($param as $k=>$v)
                $db->where($k, $v);
            $db->join('chat_users', 'chat_users.users_id = chat_room_dt.user_id', 'LEFT');
            $db->orderBy("chat_room_dt.created_at","desc");
            return $db->get('chat_room_dt', $page ?: null, ['chat_room_dt.user_id', 'chat_room_dt.room_nickname', 'chat_users.username', 'chat_users.nickname', 'chat_users.img']);
        }, 30, true, $isSaveCache);
    }

    protected static function getOne($db, $param = [], $value = null, $isSaveCache = false)
    {
        return self::RedisCacheData(function()use($db, $param, $value){
            foreach ($param as $k=>$v)
                $db->where($k, $v);
            return $db->getOne('chat_room_dt', $value) ?? null;
        }, 30, true, $isSaveCache);
    }

    protected static function set($db, $param, $data)
    {
        foreach ($param as $k=>$v)
            $db->where($k, $v);
        return $db->update('chat_room_dt', $data);
    }
}