<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/5/28
 * Time: 21:20
 */

namespace App\Socket\Repository;


use App\Socket\Model\ChatRoom;
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

        # 获取在这个房间的会员
        $users = \App\Socket\Model\ChatRoomDt::getRoomUserIds($roomId);
        # 删除房间
        if(ChatRoom::delRoom($roomId)){
            # 更新这些人的房间列表
            foreach ($users as $user){
                # 更新些人房间列表
                Push::pushUser($user, 'RoomList');
            }
            return true;
        }
        return false;
    }

}