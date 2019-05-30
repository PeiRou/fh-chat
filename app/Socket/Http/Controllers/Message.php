<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/5/28
 * Time: 20:05
 */

namespace App\Socket\Http\Controllers;


use App\Socket\Http\Controllers\Traits\Login;
use App\Socket\Utility\Users;

class Message extends Base
{
    use Login;
    public function toUser()
    {
        if(!($toUserId = (int)$this->get('toUserId')) || strlen($msg = $this->get('msg')) < 1)
            return $this->show(1, '参数错误');
        $userId = $this->iRoomInfo['userId'];
        if(!\App\Common\Utility\Pool\RedisPool::invoke(function (\App\Common\Utility\Pool\RedisObject $redis) use($userId, $toUserId) {
            $redis->select(12);
            $key = 'sendMessageWait_'.$userId.'_'.$toUserId;
            if($redis->exists($key))
                return false;

            $redis->setex($key, 3, 'on');
            return true;
        }))
            return $this->show(1, '您发言太快了');
        if(Users::senMessage($this->iRoomInfo, $msg, $toUserId))
            return $this->show(0);
        return $this->show(1, '失败');
    }
}