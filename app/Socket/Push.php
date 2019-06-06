<?php
/**
 * 推送消息 所有的推送都为异步 不影响别的处理
 */

namespace App\Socket;


use App\Socket\Model\ChatFriendsList;
use App\Socket\Model\ChatFriendsLog;
use App\Socket\Model\ChatRoom;
use App\Socket\Model\ChatUser;
use App\Socket\Model\OtherDb\PersonalLog;
use App\Socket\Utility\Room;
use App\Socket\Utility\SortName;
use App\Socket\Utility\Tables\UserStatus;
use App\Socket\Utility\Task\TaskManager;
use App\Socket\Utility\Trigger;

class Push
{
    //推送用户聊过的列表
    public static function getHistoryChatList($fd, $iRoomInfo)
    {
        TaskManager::async(function()use($fd, $iRoomInfo) {
            $list = Room::getHistoryChatList($iRoomInfo['userId']);
            $msg = app('swoole')->json(20, $list);
            app('swoole')->push($fd, $msg);
        });
    }

    public static function pushUser($userId, $column = 'all', $async = true)
    {
        $user = ChatUser::getUser(['users_id' => $userId]);
        if($user)
            return self::pushList(Room::getUserFd($userId), $user, $column, $async);
        else
            return false;
    }

    /**
     * 异步推送好友列表、群组列表、好友申请列表、用户聊过的列表
     * @param $fd
     * @param $user
     * @param string $column
     * @param bool $async 是否使用异步推送
     */
    public static function pushList($fd, $user, $column = 'all', $async = true)
    {
        !isset($user['userId']) && $user['userId'] = $user['users_id'];
        $closure = function()use($fd,$user, $column){
            try{
                $columns = $column;
                is_string($columns) && $columns = [$columns];
                is_string($user['rooms']) && $user['rooms'] = explode(',', $user['rooms']);
                $data = [];
                # 群组列表 房间列表
                if(in_array('RoomList', $columns) || $column == 'all')
                    $data['RoomList'] = ChatRoom::getRoomList(['is_open' => 1,'rooms' => $user['rooms']]);
                # 聊过的列表
                if(in_array('HistoryChatList', $columns) || $column == 'all')
                    $data['HistoryChatList'] = Room::getHistoryChatList($user['userId']);
                # 好友列表
                if(in_array('FriendsList', $columns) || $column == 'all')
                    $data['FriendsList'] = SortName::addPeople(ChatFriendsList::getUserFriendList($user['userId']), 'nickname');
                # 好友申请列表
                if(in_array('FriendsLogList', $columns) || $column == 'all'){
                    # 列表
                    $data['FriendsLogList'] = SortName::addPeople(ChatFriendsLog::getFriendsLogList(['to_id' => $user['userId']]), 'name');
                    # 未处理数
                    $data['FriendsLogListNum'] = ChatFriendsLog::getFriendsLogListNum($user['userId']) ?? 0;
                }

                $msg = app('swoole')->json(22,$data);
                app('swoole')->push($fd, $msg);
                return true;
            }catch (\Throwable $e){
                Trigger::getInstance()->throwable($e);
                return false;
            }
        };
        if($async){
            TaskManager::async($closure);
        }else{
            return $closure();
        }
    }


    /**
     * 推送单聊历史记录
     * @param $fd
     * @param $user_id 当前的user_id
     * @param $toUserId 好友id
     */
    public static function pushPersonalLog($fd,$user_id, $toUserId)
    {
        TaskManager::async(function()use($fd,$user_id, $toUserId) {
            $data = PersonalLog::getPersonalLog($user_id, $toUserId);
            $swoole = app('swoole');
            $status = UserStatus::getInstance()->get($user_id);
            foreach ($data as $v) {
                $u = UserStatus::getInstance()->get($user_id);
                if($status['type'] !== $u['type'] ||
                    $status['id'] !== $u['id'])
                    break;
                if ($v['user_id'] == $user_id)
                    $v['status'] = 4;
                $swoole->push($fd, json_encode($v));
            }
        });
    }

    /**
     * 推送多对一历史记录
     * @param $fd
     * @param $user_id 当前的user_id
     * @param $toUserId 那一个会员的id
     * @param $roomId 会员对应群的id
     */
    public static function pushManyLog($fd,$user_id, $toUserId, $roomId)
    {
        TaskManager::async(function()use($fd,$user_id, $toUserId, $roomId) {
            $data = PersonalLog::getManyLog($user_id, $toUserId, $roomId);
            $swoole = app('swoole');
            $status = UserStatus::getInstance()->get($user_id);
            foreach ($data as $v) {
                $u = UserStatus::getInstance()->get($user_id);
                if($status['type'] !== $u['type'] ||
                    $status['id'] !== $u['id'])
                    break;
                if ($v['user_id'] == $user_id)
                    $v['status'] = 4;
                $swoole->push($fd, json_encode($v));
            }
        });
    }

    //推群聊历史记录
    public static function pushRoomLog($fd, $iRoomInfo, $roomId)
    {
        if($roomId !== 2){
            app('swoole')->chkHisMsg($iRoomInfo,$fd);
        }else{
            self::pushManyLog($fd,$iRoomInfo['userId'], $iRoomInfo['userId'], $roomId);
        }
    }

    //推权限
    public static function pushSpeak($type, $iRoomInfo, $async = true)
    {
        $closure = function()use($iRoomInfo, $type){
            if($type == 'users'){
                $iRoomInfo['noSpeak'] = ChatUser::getUserValue(['users_id' => $iRoomInfo['userId']], 'chat_status');
                $iRoomInfo['allnoSpeak'] = 0; # 单聊就就不管聊天室的权限
            }elseif ($type == 'room'){

            }elseif ($type == 'many'){
                $iRoomInfo['noSpeak'] = ChatUser::getUserValue(['users_id' => $iRoomInfo['userId']], 'chat_status');
                $iRoomInfo['allnoSpeak'] = 0; # 单聊就就不管聊天室的权限
            }
            # 更新权限
            return app('swoole')->push(Room::getUserFd($iRoomInfo['userId']), app('swoole')->msg(7,'fstInit',$iRoomInfo));
        };
        if($closure) {
            if ($async) {
                TaskManager::async($closure);
            } else {
                return $closure();
            }
        }
        return false;
    }

    //
    public static function pushDelChatLog($fd, $type, $key)
    {

    }

}