<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/6/1
 * Time: 15:35
 */

namespace App\Socket\Repository;


use App\Socket\Exception\SocketApiException;
use App\Socket\Model\ChatFriendsList;
use App\Socket\Model\ChatRoom;
use App\Socket\Push;
use App\Socket\Utility\Message;
use App\Socket\Utility\Room;
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
        # 推会员在这个房间的权限  为了不让房间的权限影响
        Push::pushSpeak($type, $iRoomInfo);
        # 是不是好友
        $UserFriend = ChatFriendsList::getUserFriend([
            'to_id' => $iRoomInfo['userId'],
            'user_id' => $toUserId,
        ]);
        if(empty($UserFriend)){
            $msg = app('swoole')->msg(5,'你们不是好友');
            app('swoole')->push($fd, $msg);
        }
        # 推单聊历史记录
//        Push::pushPersonalLog($fd, $iRoomInfo['userId'], $toUserId);
        # 设置 未读条数改为0
        Room::setHistoryChatList($iRoomInfo['userId'], 'users', $toUserId, ['lookNum' => 0]);
        return true;
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
//        Push::pushManyLog($fd, $iRoomInfo['userId'], $toUserId, $roomId);
        # 设置 未读条数改为0
        Room::setHistoryChatList($iRoomInfo['userId'], $type, $toUserId, ['lookNum' => 0]);
        return true;
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
        if(!Message::is_speak($fd, $iRoomInfo, $type, $id)){
            throw new SocketApiException('您发言太快了');
        }

        # 消息过滤
        $msg = Message::filterMsg($msg, $iRoomInfo);
        # 单聊
        if($type == 'users') {
            Users::sendMessage($iRoomInfo, $msg, $id, $fd);
            # 群聊
        }elseif($type == 'room' || $type == 'many'){
            $room = ChatRoom::getRoomOne(['room_id' => $id], 1);
            if(empty($room)){
                app('swoole')->sendToSerf($fd,5,'此房间不存在！');
                return false;
            }
            if($room['is_speaking'] === 0){
                app('swoole')->sendToSerf($fd,5,'当前聊天室处于禁言状态');
                return false;
            }
            if($room['is_open'] !== 1){
                app('swoole')->sendToSerf($fd,5,'聊天室已关闭');
                return false;
            }

//            if(($is_speaking = ChatRoom::getRoomValue(['room_id' => $id], 'is_speaking', 1)) === 0){
//                app('swoole')->sendToSerf($fd,5,'当前聊天室处于禁言状态');
//                return false;
//            }
//            if(empty($is_speaking)) {
//                app('swoole')->sendToSerf($fd,5,'此房间不存在！');
//                return false;
//            }

            if(($type == 'room' && $id == 2) || $type == 'many'){
                (new ManyToOne($fd, $iRoomInfo['userId'], $id, $msg, $type, $iRoomInfo))->sendMessage();
            }else{
                Room::sendMessage($fd, $iRoomInfo, $msg, $id);
            }
        }

    }

}