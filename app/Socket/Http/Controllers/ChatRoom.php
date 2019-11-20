<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/5/28
 * Time: 21:06
 */

namespace App\Socket\Http\Controllers;


use App\Socket\Http\Controllers\Traits\ApiException;
use App\Socket\Http\Controllers\Traits\Login;
use App\Socket\Model\ChatFriendsList;
use App\Socket\Model\ChatRoomDt;
use App\Socket\Model\ChatRoomDtLog;
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

    // 退出房间
    public function outRoom()
    {
        if(!($roomId = (int)$this->get('roomId'))){
            return $this->show(1, '参数错误');
        }




    }

    //建群
    public function buildRoom()
    {
        if(
            empty($roomName = $this->post('roomName')) ||
            empty($headImg = $this->post('headImg'))
        ){
            return $this->show(1, '参数错误');
        }
        $param = [
            'is_auto' => $this->get('is_auto') ?? 1,
            'room_name' => $roomName,
            'head_img' => $headImg,
        ];
        if(ChatRoomRepository::buildRoom($this->user, $param) === false){
            return $this->show(2, '失败');
        }

        return $this->show(0);
    }

    //解散群
    public function releaseRoom()
    {
        if(($roomId = $this->get('roomId')) < 1)
            return $this->show(1, '参数错误');

        if(ChatRoomRepository::userDelRoom($this->user, $roomId)){
            return $this->show(0);
        }
        return $this->show(2, '失败');
    }

    //群资讯
    public function roomInfo()
    {
        if(($roomId = (int)$this->get('roomId')) < 1)
            return $this->show(1, '参数错误');
        # 群的信息
        if(!$roomInfo = \App\Socket\Model\ChatRoom::getRoomOne([
            'room_id' => $roomId
        ])){
            return $this->show(1, '此房间已解散！');
        }
        # 用户数量
        $uNum = ChatRoomDt::getOne([
                'id' => $roomId
            ],'count(*) as count')['count'] ?? 1;
        # 用户列表
        $uList = ChatRoomDt::uMapRoomInfo([
            'chat_room_dt.id' => $roomId,
//            'page' => 1,
//            'rows' => 14
        ]);
        # 用户在群里的信息
        $uMapRoomInfo = (ChatRoomDt::uMapRoomInfo([
                'chat_room_dt.user_id' => $this->user['userId'],
                'chat_room_dt.id' => $roomId,
            ])[0] ?? null);
        return $this->show(0, '', [
            'is_inside' => $uMapRoomInfo ? 1 : 0,
            'room_founder' => $roomInfo['room_founder'], //房主id
            'user_num' => $uNum,
            'chat_sas' => $roomInfo['chat_sas'], // 管理员列表
            'room_name' => $roomInfo['room_name'], //群聊名称
            'r_nickname' => $uMapRoomInfo['room_nickname'] ?: $this->user['nickname'], //我在本群的昵称
            'u_list' => $uList,
        ]);
    }

    //群成员
    public function userList()
    {
        if(($roomId = (int)$this->get('roomId')) < 1)
            return $this->show(1, '参数错误');
    }

    // 邀请成员进群 - 列表
    public function invitationUsersIndex()
    {
        if(($roomId = (int)$this->get('roomId')) < 1)
            return $this->show(1, '参数错误');

        return $this->show(0, '', ChatFriendsList::invitationUserList($this->user['userId'], $roomId));
    }

    // 邀请成员进群
    public function invitationUsersAction()
    {
        if ((($roomId = (int)$this->get('roomId')) < 1) || (!$toUsers = $this->get('toUsers')))
            return $this->show(1, '参数错误');
        $toUsers = explode(',', $toUsers);
        $code = 0;
        if(in_array($this->user['userId'], \App\Socket\Model\ChatRoom::getRoomSas($roomId))){
            # todo 是管理员直接添加进群
            $error = ChatRoomRepository::addRoomUser($roomId, $toUsers);
        }else{
            # todo 不是管理员 申请进入房间
            $error = ChatRoomRepository::subAdd($roomId, $toUsers);
            $code = 1;
        }
        if($error){
            return $this->show(2, (string)$error);
        }
        return $this->show($code);
    }
}