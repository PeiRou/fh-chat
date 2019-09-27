<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/5/28
 * Time: 21:20
 */

namespace App\Socket\Repository;


use App\Socket\Http\Controllers\Traits\ApiException;
use App\Socket\Model\ChatRoom;
use App\Socket\Model\OtherDb\PersonalLog;
use App\Socket\Push;
use App\Socket\Utility\Room;

class ChatRoomRepository extends BaseRepository
{

    //房间踢人
    public static function deleteUser($roomId, $user_id)
    {
        if(ChatRoom::outRoom($roomId, [
            'user_id' => $user_id
        ])){
            # 清会员在线数据
            \App\Repository\ChatRoomRepository::clearUserInfo($user_id);
            # 通知这个人
            app('swoole')->sendUser($user_id, 25, [
                'id' => $roomId,
                'type' => 'room',
            ]);
            return true;
        }
        return false;
    }
    //房间家人
    public static function addRoomUser($roomId, $user_id)
    {
        if(ChatRoom::inRoom($roomId, [
            'user_id' => $user_id
        ])){
            # 清会员在线数据
            \App\Repository\ChatRoomRepository::clearUserInfo($user_id);

            # 更新这个人房间列表
            Push::pushUser($user_id, 'RoomList');
            return true;
        }
        return false;
    }

    //删除管理
    public static function delAdmin($roomId, $user_id)
    {
        if(ChatRoom::outAdmin($roomId, $user_id)){
            return true;
        }
        return false;
    }

    //添加管理
    public static function addRoomAdmin($roomId, $user_id)
    {
        if(ChatRoom::inAdmin($roomId, $user_id)){
            return true;
        }
        return false;
    }
    //删除房间
    public static function delRoom($roomId)
    {
        if(in_array($roomId, [1, 2, 3])){
            writeLog('error', '此房间不能删除');
            return false;
        }
        # 获取在这个房间的会员
        $users = \App\Socket\Model\ChatRoomDt::getRoomUserIds($roomId);
        # 删除房间
        if(ChatRoom::delRoom($roomId)){
            # 更新这些人的房间列表
            foreach ($users as $user){
                # 删除历史列表
                $pushs = ['RoomList'];
                if(Room::delHistoryChatList($user, 'room', $roomId)){
                    array_push($pushs, 'HistoryChatList');
                }
                # 更新些人房间列表
                Push::pushUser($user, $pushs);
            }
            # 清日志
//            PersonalLog::clearLog('room', 0, $roomId);
            return true;
        }
        return false;
    }

    public static function userDelRoom($user, $roomId)
    {
        if(!($roomInfo = ChatRoom::getRoomOne([
            'room_id'=> $roomId,
            'room_founder' => $user['userId']
        ])))
            ThrowOut(2, '您不是房主！');

        # 删除房间
        return self::delRoom($roomId);
    }

    //新建房间
    public static function buildRoom($user, $param = [])
    {
        $data = [
            'is_auto' => (isset($param['is_auto']) && (int)$param['is_auto'] >= 1) ? 1 : 0,
            'room_name' => $param['room_name'],
//            'head_img' => $param['head_img'],
            'chat_sas' => $user['userId'],
            'room_founder' => $user['userId'],
        ];
        if($res = ChatRoom::getRoomOne(['room_founder' => $user['userId']], true)){
            ThrowOut(1, '您已经创建过房间');
        }
        if(!$roomId = ChatRoom::buildRoom($user['userId'], $data)){
            return false;
        }
        # 保存并修改头像
        isset($param['head_img']) && self::upRoomHeadImg($roomId, $param['head_img']);

        # 将自己加入房间映射
        ChatRoomRepository::addRoomUser($roomId, $user['userId']);
        return $roomId;
    }

    //上传聊天室头像
    public static function upRoomHeadImg($roomid, $base64)
    {
        $path = "/roomImg/";
        $imgName = md5($roomid).".jpg";
        if(upImg($path, $imgName, $base64)){
            $img = $path . $imgName;
            return ChatRoom::update(['room_id' => $roomid], ['head_img' => '/upchat'. $img."?t=".time().rand(111,22222)]);
        }
        return false;
    }
}