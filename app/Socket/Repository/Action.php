<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/6/1
 * Time: 15:35
 */

namespace App\Socket\Repository;


use App\Socket\Model\ChatRoom;
use App\Socket\Model\ChatUser;
use App\Socket\Push;
use App\Socket\Utility\Message;
use App\Socket\Utility\Room;
use App\Socket\Utility\Task\TaskManager;
use App\Socket\Utility\Users;

class Action extends BaseRepository
{

    //进入单聊页面
    public static function inUser($fd, $iRoomInfo, $toUser, $type)
    {
        $toUserId = (int)$toUser;

        $toUser = \App\Socket\Pool\MysqlPool::invoke(function (\App\Socket\Pool\MysqlObject $db) use($toUserId) {
            return $db->where('users_id', $toUserId)->getOne('chat_users');
        });

        if(empty($toUser))
            return false;
        # 推会员在这个房间的权限
        Push::pushSpeak($type, $iRoomInfo);
        # 推单聊历史记录
        Push::pushPersonalLog($fd, $iRoomInfo['userId'], $toUserId);
        # 设置 未读条数改为0
        Room::setHistoryChatList($iRoomInfo['userId'], 'users', $toUserId, ['lookNum' => 0]);
    }

    //进入多对一页面
    public static function inMany($fd, $iRoomInfo, $toUserId, $type)
    {
        $roomId = 2;

        $toUser = \App\Socket\Pool\MysqlPool::invoke(function (\App\Socket\Pool\MysqlObject $db) use($toUserId) {
            return $db->where('users_id', $toUserId)->getOne('chat_users');
        });

        if(empty($toUser))
            return false;
        # 推会员在这个房间的权限
        Push::pushSpeak($type, $iRoomInfo);
        # 推历史记录
        Push::pushManyLog($fd, $iRoomInfo['userId'], $toUserId, $roomId);
        # 设置 未读条数改为0
        Room::setHistoryChatList($iRoomInfo['userId'], $type, $toUserId, ['lookNum' => 0]);
    }

    /**
     * 发送信息
     * @param int $fd 消息来源客户端的fd
     * @param string $type 消息类型
     * @param int $id  目标id
     * @param string $msg 消息
     * @param array $iRoomInfo
     */
    public static function sendMessage(int $fd, string $type, int $id, string $msg, array $iRoomInfo)
    {
        # 用户是否可发言 是否正常
        if(!Message::is_speak($fd, $iRoomInfo, $type, $id))
            return false;
        # 消息过滤
        $msg = Message::filterMsg($msg, $iRoomInfo);
        # 单聊
        if($type == 'users')
            Users::sendMessage($iRoomInfo, $msg, $id);
        # 群聊
        elseif($type == 'room' || $type == 'many'){
            if(ChatRoom::getRoomValue(['room_id' => $id], 'is_speaking') === 0){
                app('swoole')->sendToSerf($fd,5,'当前聊天室处于禁言状态！');
                return false;
            }

            if(($type == 'room' && $id == 2) || $type == 'many'){
                (new ManyToOne($fd, $iRoomInfo['userId'], $id, $msg, $type, $iRoomInfo))->sendMessage();
            }else{
                Room::sendMessage($fd, $iRoomInfo, $msg, $id);
            }
        }

    }

}