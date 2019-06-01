<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/6/1
 * Time: 15:35
 */

namespace App\Socket\Repository;


use App\Socket\Push;
use App\Socket\Utility\Room;

class Action extends BaseRepository
{


    //进入单聊页面
    public static function inUser($fd, $iRoomInfo, $toUser)
    {
        $toUserId = (int)$toUser;
        # 设置用户状态
        Room::setUserStatus($iRoomInfo['userId'], $toUserId, 'users');

        $toUser = \App\Socket\Pool\MysqlPool::invoke(function (\App\Socket\Pool\MysqlObject $db) use($toUserId) {
            return $db->where('users_id', $toUserId)->getOne('chat_users');
        });

        if(empty($toUser))
            return false;
        # 推单聊历史记录
        Push::pushPersonalLog($fd, $iRoomInfo['userId'], $toUserId);

        # 设置 未读条数改为0
        Room::setHistoryChatList($iRoomInfo['userId'], 'users', $toUserId, ['lookNum' => 0]);
    }
}