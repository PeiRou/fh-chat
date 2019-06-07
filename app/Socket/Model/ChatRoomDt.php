<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/5/29
 * Time: 21:00
 */

namespace App\Socket\Model;


use App\Service\Cache;

class ChatRoomDt extends Base
{

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
}