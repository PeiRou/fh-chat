<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/5/27
 * Time: 20:57
 */

namespace App\Socket\Http\Controllers;


use App\Socket\Http\Controllers\Traits\Login;
use App\Socket\Model\ChatFriendsList;
use App\Socket\Model\ChatFriendsLog;
use App\Socket\Model\ChatUser;

class User extends Base
{
    use Login;
    //申请添加好友
    public function addUserFriendsLog()
    {
        if(!($toUserId = (int)$this->get('toUserId')))
            return $this->show(1, '参数错误');
        $res = ChatFriendsList::getUserFriend([
            'user_id' => $this->user['users_id'],
            'to_id' => $toUserId
        ]);
//        if($toUserId == $this->user['users_id'])
//            return $this->show(1, '您不能添加自己');
        if(count($res))
            return $this->show(1, '你们已经是好友了');
        if(!ChatFriendsLog::checkAddFriend($this->user['users_id'], $toUserId))
            return $this->show(1, '请等待对方同意');
        if($data = ChatFriendsLog::addFriend($this->user, $toUserId)){
            app('swoole')->sendUser($toUserId, 21, $data);
            return $this->show(0);
        }

        return $this->show(1,'失败');
    }

    //同意 | 拒绝好友申请
    public function handleUserFriendsLog()
    {
        if(!($logId = $this->get('logId')) || is_null($status = $this->get('status')))
            return $this->show(1,'参数错误');
        $status = (int)$status;
        if(!$res = ChatFriendsLog::setStatus($logId, $this->user, $status)){
            return $this->show(0);
        }

        return $this->show(1, $res ?? '失败');
    }

    //搜索好友 会员只能搜管理员 管理员可以搜全部会员 （前期不需要这个限制）
    public function searchUser()
    {
        if(empty($name = $this->get('name')))
            return $this->show(0, '', []);
        # 查聊天室权限
        $chat_role = ChatUser::getUserRole([
            'users_id' => $this->user['users_id']
        ]);
        $param = [
            'name' => $name
        ];
        # （前期不需要这个限制）
        if($chat_role !== 3)
            $param['chat_role'] = 3;
        $users = ChatUser::search($param, $this->user['users_id']);
        return $this->show(0, '', $users ?? []);
    }
}