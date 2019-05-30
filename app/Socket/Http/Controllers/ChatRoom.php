<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/5/28
 * Time: 21:06
 */

namespace App\Socket\Http\Controllers;


use App\Socket\Http\Controllers\Traits\Login;
use App\Socket\Repository\ChatRoomRepository;

class ChatRoom extends Base
{
    use Login;

    //房间踢人
    public function deleteUser()
    {
        if(!($roomId = (int)$this->get('roomId')) || !$user_id = (int)$this->get('user_id')){
            return $this->show(1, '参数错误');
        }
        if($this->user['chat_role'] !== 3)
            return $this->show(1, '您没有权限');
        if(ChatRoomRepository::deleteUser($roomId, $user_id)){
            return $this->show(0);
        }
        return $this->show(1, 'error');
    }
}