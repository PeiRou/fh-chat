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
use App\Socket\Model\OtherDb\PersonalLog;
use App\Socket\Utility\Room;
use App\Socket\Utility\Tables\UserStatus;
use App\Socket\Utility\Task\TaskManager;
use App\Socket\Utility\Trigger;
use App\Socket\Utility\Users;

class ManyToOne
{

    public $user;

    public $roomId; //多对一的那个roomid
    public $oneId;  //多对一那一个的userid
    public $oneUser;  //多对一那一个的user

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
        $arr = Users::buildMsg(2, $msg, $iRoomInfo, $this->roomId, 'many');
        $arr['userMap'] = Users::getUserMap($this->oneId, $this->roomId);
        $roomId = $this->roomId;
        foreach ($userIds as $v){
            $ChatListType = 'many';
            $ChatListToId = $this->oneId;
            $userStatus = $this->userStatus($v);
            if($v == $this->oneId){
                $ChatListType = 'room';
                $ChatListToId = $this->roomId;
            }
            TaskManager::async(function() use($userStatus, $msg, $v, $fd, $iRoomInfo, $ChatListType, $ChatListToId, $arr, $roomId){
                $lookNum = 1;
                # 如果打开的是这个群 将消息推送过去 未读消息数就是0 不然消息数+1
                if($userStatus && $userStatus['isOpen']) {
                    if ($ufd = Room::getUserFd($v)) {
                        $lookNum = 0;
                        $status = 2;
                        $ufd == $fd && $status = 4;
                        # 推消息
                        $arr['status'] = $status;
                        app('swoole')->push($ufd, json_encode($arr, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                    }
                }
                echo PHP_EOL;
                # 设置未读消息数和最后一条消息
                Room::setHistoryChatList($v, $ChatListType, $ChatListToId, [
                    'lookNum' => $lookNum,
                    'lastMsg' => $msg,
                    'name' => function()use($ChatListType, $ChatListToId, $roomId){
                        if($ChatListType == 'many'){
                            $str =  ChatRoom::getRoomValue(['room_id' => $roomId], 'room_name');
                            $str .= '-'.ChatUser::getUserValue(['users_id' => $ChatListToId], 'nickname');
                            return $str;
                        }
                        return null;
                    },
                    'head_img' => function()use($ChatListType, $ChatListToId, $roomId){
                        if($ChatListType == 'many'){
                            $str = ChatUser::getUserValue(['users_id' => $ChatListToId], 'img');
                            return $str;
                        }
                        return null;
                    },
                ]);
            });
        }
        # 记日志
        PersonalLog::insertMsgLog($arr);
    }

    public function userStatus($userId)
    {
        $userStatus = UserStatus::getInstance()->get($userId);

        $arr = [
            'isOpen' => false
        ];

        $arr['type'] = 'many'; # 默认都是这个类型  只有会员本人才是 room 类型
        $arr['id'] = $this->oneId; # 默认对方id 都是会员id
         # 会员发消息 如果这个userId == 发消息的这个人
         if($userId == $this->oneId){
             $arr['type'] = 'room';  # 只有会员本人才是 room 类型
             $arr['id'] = $this->roomId; # id 就是这个房间id
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