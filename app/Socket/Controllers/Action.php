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

    //打开窗口
    public function openWindow()
    {
        $id = (int)$this->id;
        $type = $this->type;
        if(!$id)
            return false;
        if($type == 'room'){
            app('swoole')->inRoom($id, $this->request->fd, $this->iRoomInfo, $this->iSess);
        }elseif($type == 'users'){
            \App\Socket\Repository\Action::inUser($this->request->fd, $this->iRoomInfo, $id);
        }
    }

    //关闭窗口
    public function exitWindow()
    {
        # 如果在其它房间就退出
        if($status = Room::getUserStatus($this->iRoomInfo['userId'])){
            if($status['type'] == 'room')
                Room::exitRoom($status['id'], $this->request->fd, $this->iRoomInfo);
        }
    }

    //发送消息
    public function message()
    {
        $userStatus = Room::getUserStatus($this->iRoomInfo['userId']);
        !($type = $this->type) && $type = $userStatus['type'];
        !($id = $this->id) && $id = $userStatus['id'];

        \App\Socket\Repository\Action::sendMessage($this->request->fd, $type, $id, $this->msg, $this->iRoomInfo);
    }
}