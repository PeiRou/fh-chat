<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/5/30
 * Time: 19:04
 */

namespace App\Socket\Controllers;


use App\Socket\Model\ChatRoom;
use App\Socket\Model\OtherDb\PersonalLog;
use App\Socket\Push;
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

        # 设置用户状态 打开的群组还是单人 和id
        Room::setFdStatus($this->iRoomInfo['userId'], $id, $type, $this->request->fd);

        if($type == 'room'){ # 群聊
            app('swoole')->inRoom($id, $this->request->fd, $this->iRoomInfo, $this->iSess);
        }elseif($type == 'users'){ # 单聊
            \App\Socket\Repository\Action::inUser($this->request->fd, $this->iRoomInfo, $id, $type);
        }elseif($type == 'many'){ # 多对一
            \App\Socket\Repository\Action::inMany($this->request->fd, $this->iRoomInfo, $id, $type);
        }
    }

    //关闭窗口
    public function exitWindow()
    {
        # 如果在其它房间就退出
        if($status = Room::getFdStatus($this->request->fd)){
            if($status['type'] == 'room')
                Room::exitRoom($status['id'], $this->request->fd, $this->iRoomInfo);
        }
        # 删除用户状态
        Room::delFdStatus($this->request->fd);
    }

    //发送消息
    public function message()
    {
        $userStatus = Room::getFdStatus($this->request->fd);
        !($type = $this->type) && $type = $userStatus['type'];
        !($id = $this->id) && $id = $userStatus['id'];
        \App\Socket\Repository\Action::sendMessage($this->request->fd, $type, $id, $this->msg, $this->iRoomInfo);
    }

    //删除消息
    public function delLog()
    {
        if(ChatRoom::getUserValue(['users_id'=>$this->iRoomInfo['userId']], 'chat_role') !== 3)
            return false;
        if(!($type = $this->type )|| !($idx = (int)$this->idx)){
            return false;
        }
        PersonalLog::delRawLog([
            'type' => $type,
            'idx' => $idx,
        ]);
    }

    //获取聊天记录
    public function getChatLog()
    {
        $id = (int)$this->id;
        $type = $this->type;
        if(!$id)
            return false;
        if($type == 'room'){ # 群聊
            Push::pushRoomLog($this->request->fd, $this->iRoomInfo, $id, ['page'=> $this->page ?? 1]);
        }elseif($type == 'users'){ #  单聊
            Push::pushPersonalLog($this->request->fd,$this->iRoomInfo['userId'], $id, ['page'=> $this->page ?? 1]);
        }elseif($type == 'many'){ # 多对一
            Push::pushManyLog($this->request->fd,$this->iRoomInfo['userId'], $id, $this->roomId ?? 2, ['page'=> $this->page ?? 1]);
        }
    }
}