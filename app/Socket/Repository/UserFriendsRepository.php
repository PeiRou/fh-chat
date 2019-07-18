<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/7/15
 * Time: 19:41
 */

namespace App\Socket\Repository;


use App\Socket\Model\ChatFriendsList;
use App\Socket\Push;

class UserFriendsRepository extends BaseRepository
{

    public static function delUserFriends(int $userId, int $toId)
    {
        if(!ChatFriendsList::delUserFriends($userId, $toId)){
            return false;
        }
        # 更新列表
        Push::pushUser($userId, 'FriendsList');
        Push::pushUser($toId, 'FriendsList');

        return true;
    }

}