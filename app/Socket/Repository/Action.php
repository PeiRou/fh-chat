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
    public static function inUser($fd, $iRoomInfo, $toUser)
    {
        $toUserId = (int)$toUser;
        # 设置用户状态
        Room::setUserStatus($iRoomInfo['userId'], $toUserId, 'users', $fd);

        $toUser = \App\Socket\Pool\MysqlPool::invoke(function (\App\Socket\Pool\MysqlObject $db) use($toUserId) {
            return $db->where('users_id', $toUserId)->getOne('chat_users');
        });

        if(empty($toUser))
            return false;
        TaskManager::async(function()use($iRoomInfo){
            $iRoomInfo['noSpeak'] = ChatUser::getUserValue(['users_id' => $iRoomInfo['userId']], 'chat_status');
            $iRoomInfo['allnoSpeak'] = 0; # 单聊就就不管聊天室的权限
            # 更新权限
            app('swoole')->push(Room::getUserFd($iRoomInfo['userId']), app('swoole')->msg(7,'fstInit',$iRoomInfo));
        });

        # 推单聊历史记录
        Push::pushPersonalLog($fd, $iRoomInfo['userId'], $toUserId);

        # 设置 未读条数改为0
        Room::setHistoryChatList($iRoomInfo['userId'], 'users', $toUserId, ['lookNum' => 0]);
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