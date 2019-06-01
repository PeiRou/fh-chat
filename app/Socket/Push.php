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
use App\Socket\Utility\Task\TaskManager;

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
    //推送房间列表
//    public static  function getRoomList($fd,$iRoomInfo){
//        $room_list = ChatRoom::getRoomList([
//            'is_open' => 1,
//            'rooms' => $iRoomInfo['rooms']
//        ]);
//        $msg = app('swoole')->json(16,$room_list);
//        app('swoole')->push($fd, $msg);
//    }

    public static function pushUser($userId, $column = 'all')
    {
        $user = ChatUser::getUser(['users_id' => $userId]);
        return self::pushList(Room::getUserFd($userId), $user, $column);
    }

    //异步推送好友列表、群组列表、好友申请列表、用户聊过的列表
    public static function pushList($fd, $user, $column = 'all')
    {
        !isset($user['userId']) && $user['userId'] = $user['users_id'];
        TaskManager::async(function()use($fd,$user, $column){
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
        });
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
            foreach ($data as &$v) {
                if ($v['user_id'] == $user_id)
                    $v['status'] = 4;
            }
            app('swoole')->sendFd($fd, 25, $data);
        });
    }

}