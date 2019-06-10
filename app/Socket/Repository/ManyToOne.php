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
use App\Socket\Redis\Chat;
use App\Socket\Utility\Room;
use App\Socket\Utility\Task\TaskManager;
use App\Socket\Utility\Users;

class ManyToOne
{

    public $user;

    public $roomId; //多对一的那个roomid
    public $oneId;  //多对一那一个的userid
    public $oneUser;  //多对一那一个的user

    /**
     * 一对多发送消息
     *  有两种情况  客服：（type=many） | 会员(type=room)
     * 客服：$type:many 客服发消息这个群里的(所有客服 + 这个会员)都能看到
     * 会员：$type:room 会员发消息这个群里的所有客服都能看到
     * 聊天的人只有 这个群里所有客服 + 单个会员
     * @param $fd
     * @param $userId
     * @param $toId  $type=（many：会员id）|（room:群id）
     * @param $msg
     * @param $type
     * @param $iRoomInfo
     */
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
        $arr = Users::buildMsg(2, $msg, $iRoomInfo, $this->oneId, 'many', 2);
        $arr['userMap'] = Users::getUserMap($this->oneId, $this->roomId);
        $roomId = $this->roomId;
        foreach ($userIds as $v){
            $userStatus = $this->userStatus($v);
            TaskManager::async(function() use($userStatus, $msg, $v, $fd, $iRoomInfo, $arr, $roomId){
                $lookNum = 1;
                # 如果打开的是这个群 将消息推送过去 未读消息数就是0 不然消息数+1
                if($userStatus) {
                    foreach ($userStatus['fdArr'] as $fdArr){
                        if ($fdArr['isOpen']) {
                            $status = 2;
                            $fdArr['fd'] == $fd && $status = 4;
                            # 推消息
                            $arr['status'] = $status;
                            if(app('swoole')->push($fdArr['fd'], json_encode($arr, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))){
                                $lookNum = 0;
                            }
                        }
                    }
                }
                # 设置未读消息数和最后一条消息
                Room::setHistoryChatList($v, $userStatus['type'], $userStatus['id'], [
                    'lookNum' => $lookNum,
                    'lastMsg' => $msg,
                    'roomId' => $roomId,
                    'name' => function()use($userStatus, $roomId){
                        if($userStatus['type'] == 'many'){
                            $str =  ChatRoom::getRoomValue(['room_id' => $roomId], 'room_name');
                            $str .= '-'.ChatUser::getUserValue(['users_id' => $userStatus['id']], 'nickname');
                            return $str;
                        }
                        return null;
                    }
                ]);
            });
        }
        # 记日志
        PersonalLog::insertMsgLog($arr);
    }

    public function userStatus($userId)
    {
        $arr = [];
        $arr['fdArr'] = [];
        $arr['type'] = 'many'; # 默认都是这个类型  只有会员本人才是 room 类型
        $arr['id'] = $this->oneId; # 默认对方id 都是会员id
         # 会员发消息 如果这个userId == 发消息的这个人
         if($userId == $this->oneId){
             $arr['type'] = 'room';  # 只有会员本人才是 room 类型
             $arr['id'] = $this->roomId; # id 就是这个房间id
         }

        $fds = Chat::getUserFd($userId);

        foreach ($fds as $fd){
            $fdStatus = Room::getFdStatus($fd);
            $array = [
                'fd' => $fd,
                'isOpen' => false # fd 的状态
            ];
            if($fdStatus && $fdStatus['type'] == $arr['type'] && $fdStatus['id'] == $arr['id']){
                $array['isOpen'] = true;
            }
            $arr['fdArr'][] = $array;
        }
        return $arr;
    }

    //获取所有要推送信息的user
    public function getUsers()
    {
        $arr = [];
        if($this->type == 'many'){
            array_push($arr, $this->toId);
            $this->oneId = $this->toId;
            $this->roomId = 2;
        }else{
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