<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/5/27
 * Time: 22:10
 */

namespace App\Socket\Model;



class ChatSendConfig extends Base
{
    protected static function get($db, int $roomId)
    {
        return self::RedisCacheData(function()use($db,  $roomId){
            # 先拿房间对应的， 没有的话拿默认的
            $res = $db->where('room_id', $roomId)->get('chat_send_config');
            if(empty($res) && $roomId !== 0){
                return ChatSendConfig::get($db, 0);
            }
            return $res;
        }, 30, true);
    }
}