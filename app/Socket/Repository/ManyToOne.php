<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/6/4
 * Time: 20:47
 */

namespace App\Socket\Repository;


use App\Socket\Model\ChatRoom;
use App\Socket\Model\ChatUser;
use App\Socket\Utility\Room;
use App\Socket\Utility\Tables\UserStatus;
use App\Socket\Utility\Task\TaskManager;
use App\Socket\Utility\Trigger;

class ManyToOne
{

    public $user;

    public $roomId; //多对一的那个roomid
    public $oneId;  //多对一那一个的userid

    public function __construct($fd, $userId, $toId, $msg, $type, $iRoomInfo)
    {
        $this->iRoomInfo = $iRoomInfo;
        $this->user = ChatUser::getUser(['users_id' => $userId]);
        $this->toId = $toId;
        $this->msg = $msg;
        $this->fd = $fd;
        $this->type = $type;
        $this->sendRole = $this->user['chat_role'];
    }

    public function sendMessage()
    {
        $userIds = $this->getUsers();
        $msg = $this->msg;
        $fd = $this->fd;
        $iRoomInfo = $this->iRoomInfo;
        foreach ($userIds as $v){
            $userStatus = $this->userStatus($v);
            TaskManager::async(function() use($userStatus, $msg, $v, $fd, $iRoomInfo){
                $lookNum = 1;
                # 如果打开的是这个群 将消息推送过去 未读消息数就是0 不然消息数+1
                if($userStatus && $userStatus['isOpen']) {
                    if ($ufd = Room::getUserFd($v)) {
                        $lookNum = 0;
                        $status = 2;
                        $ufd == $fd && $status = 4;
                        # 推消息
                        $aMesgRep = urlencode($msg);
                        $aMesgRep = base64_encode(str_replace('+', '%20', $aMesgRep));   //发消息
                        $json = app('swoole')->msg($status, $aMesgRep, $iRoomInfo, $userStatus['type'], $userStatus['id']);   //自己发消息
                        app('swoole')->push($ufd, $json);
                    }
                }
                # 设置未读消息数和最后一条消息
                Room::setHistoryChatList($v, 'room', $userStatus['id'], [
                    'lookNum' => $lookNum,
                    'lastMsg' => $msg
                ]);
            });
        }
    }

    public function userStatus($userId)
    {
        $userStatus = UserStatus::getInstance()->get($userId);

        $arr = [
            'isOpen' => false
        ];

        # 客服发消息
        if($this->type == 'many'){
            $arr['type'] = 'many';
            $arr['id'] = $this->oneId;
         }
         # 会员
         else{
            $arr['type'] = 'room';
            $arr['id'] = $this->roomId;
        }
        if($userStatus && $userStatus['type'] == $arr['type'] && $userStatus['id'] == $arr['id']){
            $arr['isOpen'] = true;
        }
        return $arr;
    }

    //获取所有要推送信息的user
    public function getUsers()
    {
        $arr = [];
        # 类型是many 说明发信息的人是客服  toId 是userId
        if($this->type == 'many'){
            array_push($arr, $this->toId);
            $this->oneId = $this->toId;
            $this->roomId = 2;
        }else{
            # 否则就是会员发的 toId 是room_id
            $this->roomId = $this->toId;
            $this->oneId = $this->user['users_id'];
            array_push($arr, $this->user['users_id']);
        }

        $room = ChatRoom::getRoomOne(['room_id' => $this->roomId]);
        return array_unique(array_diff(array_merge($arr, explode(',', $room['chat_sas'])), ['']));
    }

    public function __destruct()
    {

    }
}