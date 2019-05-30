<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/5/24
 * Time: 22:23
 */

namespace App\Socket\Controllers;


use App\Socket\Model\OtherDb\PersonalLog;
use App\Socket\Push;
use App\Socket\Utility\Room;
use App\Socket\Utility\Users;
use Illuminate\Support\Arr;
use App\Socket\Utility\SqlBuild;

class SendMessage extends Base
{

    //进入单聊页面
    public function inUser()
    {
        $toUserId = (int)$this->toUser;
        # 设置用户状态
        Room::setUserStatus($this->iRoomInfo['userId'], $toUserId, 'users');

        $toUser = \App\Socket\Pool\MysqlPool::invoke(function (\App\Socket\Pool\MysqlObject $db) use($toUserId) {
            return $db->where('users_id', $toUserId)->getOne('chat_users');
        });

        if(empty($toUser))
            return false;

        # 推单聊历史记录
        Push::pushPersonalLog($this->request->fd, $this->iRoomInfo['userId'], $toUserId);

        # 设置 未读条数改为0
        Room::setHistoryChatList($this->iRoomInfo['userId'], 'users', $toUserId, ['lookNum' => 0]);
    }


    public function toUser()
    {
        $toUserId = (int)$this->toUser;

        Users::senMessage($this->iRoomInfo, $this->msg, $toUserId);
    }





}