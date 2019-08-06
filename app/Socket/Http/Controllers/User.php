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
use App\Socket\Push;
use App\Socket\Repository\UserFriendsRepository;
use App\Socket\Utility\Room;

class User extends Base
{
    use Login;

    //申请添加好友
    public function addUserFriendsLog()
    {
        if(!($toUserId = (int)$this->get('toUserId')) && !($toUserName = $this->get('toUserName')))
            return $this->show(1, '参数错误');

        if(!$toUserId && $toUserName){
            $toUserId = ChatUser::getUserValue(['username' => $toUserName], 'users_id');
        }

        $res = ChatFriendsList::getUserFriend([
            'user_id' => $this->user['users_id'],
            'to_id' => $toUserId
        ]);
        if($toUserId == $this->user['users_id'])
            return $this->show(1, '您不能添加自己');
        if(count($res))
            return $this->show(1, '你们已经是好友了');
        if(!ChatFriendsLog::checkAddFriend($this->user['users_id'], $toUserId))
            return $this->show(1, '请等待对方同意');
        if($data = ChatFriendsLog::addFriend($this->user, $toUserId)){
//            app('swoole')->sendUser($toUserId, 21, $data);
            Push::pushUser($toUserId, 'FriendsLogList');
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
//        if($chat_role !== 3)
//            $param['chat_role'] = 3;
        $users = ChatUser::search($param, $this->user['users_id'], 20, (boolean)$this->get('nocache'));
        return $this->show(0, '', $users ?? [], false);
    }

    //设置备注
    public function setRemark()
    {
        if(empty($remark = $this->get('remark')) || !($toId = $this->get('toId')))
            return $this->show(1, '参数错误');
        if(mb_strlen($remark) > 10)
            return $this->show(1, '昵称太长啦');
        if(!ChatFriendsList::setRemark($this->user['users_id'], $toId, $remark))
            return $this->show(1, '修改失败');
        # 更新历史列表
        Room::setHistoryChatList($this->user['users_id'], 'users', $toId, ['name' => $remark]);
        return $this->show(0);
    }

    //删除一条聊天历史列表
    public function delHistoryChatList()
    {
        if(empty($type = $this->get('type')) || ($id = (int)$this->get('id')) < 1){
            return $this->show(1, '参数错误');
        }
        if(!Room::delHistoryChatList($this->user['users_id'], $type, $id)){
            return $this->show(2, '失败');
        }
        # 更新列表
        Push::pushUser($this->user['users_id'], 'HistoryChatList');
        return $this->show(0);
    }

    //删除好友
    public function delUserFriends()
    {
        if(($to_id = (int)$this->get('to_id')) < 1){
            return $this->show(1, '参数错误');
        }
        if(UserFriendsRepository::delUserFriends($this->user['users_id'], $to_id)){
            return $this->show(0);
        }
        return $this->show(1, '失败');
    }

    //搜索用户-用户详细信息
    public function searchUserInfo()
    {
        if(($toUserId = $this->get('toUserId')) < 1){
            return $this->show(1, '参数错误');
        }
        if(!$res = ChatUser::searchUserInfo($this->user['users_id'], $toUserId, (boolean)$this->get('nocache'))){
            return $this->show(1, '没有这个用户！');
        }
        return $this->show(0, '', [
            'users_id' => $res['users_id'],
            'username' => $res['username'],
            'nickname' => $res['nickname'],
            'img' => $res['img'],
            'is_friend' => $res['is_friend'],
            'remark' => $res['remark'],
            'status' => $res['status'],
        ]);
    }

}