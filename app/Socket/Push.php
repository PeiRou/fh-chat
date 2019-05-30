<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/5/28
 * Time: 14:14
 */

namespace App\Socket;


use App\Socket\Model\ChatRoom;
use App\Socket\Model\ChatUser;
use App\Socket\Model\OtherDb\PersonalLog;
use App\Socket\Utility\Room;
use App\Socket\Utility\SortName;
use Illuminate\Support\Facades\DB;

class Push
{
    //推送用户聊过的列表
    public static function getHistoryChatList($fd, $iRoomInfo)
    {
        $list = Room::getHistoryChatList($iRoomInfo['userId']);
        $msg = app('swoole')->json(20,$list);
        app('swoole')->push($fd, $msg);
    }
    //推送房间列表
    public static  function getRoomList($fd,$iRoomInfo){
        $room_list = ChatRoom::getRoomList([
            'is_open' => 1,
            'rooms' => $iRoomInfo['rooms']
        ]);
        $msg = app('swoole')->json(16,$room_list);
//        $msg = app('swoole')->msg(16,json_encode($room_list),$iRoomInfo);
        app('swoole')->push($fd, $msg);
    }

    //推送好友列表、群组列表、好友申请列表、用户聊过的列表
    public static function pushList($fd,$iRoomInfo)
    {
        $data = [
            # 群组列表
            'RoomList' =>  ChatRoom::getRoomList(['is_open' => 1,'rooms' => $iRoomInfo['rooms']]),
            # 聊过的列表
            'HistoryChatList' => Room::getHistoryChatList($iRoomInfo['userId']),
            # 好友列表
            'FriendsList' => SortName::addPeople(ChatUser::getUserFriendList(['user_id' => $iRoomInfo['userId']]), 'name'),
            # 好友申请列表
            'FriendsLogList' => SortName::addPeople(ChatUser::getFriendsLogList(['to_id' => $iRoomInfo['userId']]), 'name'),
        ];
        $msg = app('swoole')->json(22,$data);
        app('swoole')->push($fd, $msg);
    }

    /**
     * 推送单聊历史记录
     * @param $fd
     * @param $user_id 当前的user_id
     * @param $toUserId 好友id
     */
    public static function pushPersonalLog($fd,$user_id, $toUserId)
    {
        $data = PersonalLog::getPersonalLog($user_id, $toUserId);
        foreach ($data as &$v){
            if($v['user_id'] == $user_id)
                $v['status'] = 4;
        }
        app('swoole')->sendFd($fd, 25, $data);
    }

}