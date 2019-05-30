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
    use Cache;

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
}