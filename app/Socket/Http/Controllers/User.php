<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/5/27
 * Time: 20:57
 */

namespace App\Socket\Http\Controllers;


use App\Socket\Http\Controllers\Traits\Login;
use App\Socket\Model\ChatUser;

class User extends Base
{
    use Login;
    //添加好友
    public function addUser()
    {
        if(!($toUserId = (int)$this->get('toUserId')))
            return $this->show(1, '参数错误');
        $res = ChatUser::getUserFriend([
            'user_id' => $this->user['users_id'],
            'to_id' => $toUserId
        ]);
//        if($toUserId == $this->user['users_id'])
//            return $this->show(1, '您不能添加自己');
        if(count($res))
            return $this->show(1, '你们已经是好友了');
        if(!ChatUser::checkAddFriend($this->user['users_id'], $toUserId))
            return $this->show(1, '请等待对方同意');
        if($data = ChatUser::addFriend($this->user, $toUserId)){
            app('swoole')->sendUser($toUserId, 21, $data);
            return $this->show(0);
        }

        return $this->show(1,'失败');
    }

}