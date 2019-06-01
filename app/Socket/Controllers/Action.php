<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/5/30
 * Time: 19:04
 */

namespace App\Socket\Controllers;


use App\Socket\Utility\Room;

class Action extends Base
{

    public function exitWindow()
    {
        # 如果在其它房间就退出
        if($status = Room::getUserStatus($this->iRoomInfo['userId'])){
            if($status['type'] == 'room')
                Room::exitRoom($status['id'], $this->request->fd, $this->iRoomInfo);
        }
    }
}