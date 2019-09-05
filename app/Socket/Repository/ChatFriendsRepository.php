<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/5/31
 * Time: 13:31
 */

namespace App\Socket\Repository;


use App\Socket\Model\ChatFriendsList;
use App\Socket\Push;
use App\Socket\Utility\Room;

class ChatFriendsRepository extends BaseRepository
{

    /**
     * 添加一个人为好友
     * @param $db 需要用到事物 所以传入db句柄
     * @param array $user
     * @param array $toUser
     * @return bool
     */
    public static function addUserFriends($db, array $user, array $toUser)
    {
        # 如果失败 看这个人是否已经是好友
        if(!$a = ChatFriendsList::addUserFriends($db, $user['users_id'], $toUser)){
            if($user['users_id'] !== $toUser['users_id'] && count(ChatFriendsList::getUserFriend([
                'user_id' => $user['users_id'],
                'to_id' => $toUser['users_id']
            ])) < 1){
                return false;
            }
        }
        return true;
    }
}