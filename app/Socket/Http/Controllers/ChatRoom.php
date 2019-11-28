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
use App\Socket\Redis\Redis;
use App\Socket\Repository\ChatRoomRepository;

class ChatRoom extends Base
{
    use Login;

    //房间踢人
    public function deleteUser()
    {
        if(!($roomId = (int)$this->get('roomId')) || empty($user_id = $this->get('user_id'))){
            return $this->show(1, '参数错误');
        }
        $userIds = explode(',',$user_id);
        # 会员在房间的身份
        $sas = \App\Socket\Model\ChatRoom::getUserRoomSas($this->user['userId'], $roomId, true);
        if($sas == \App\Socket\Model\ChatRoom::USER)
            return $this->show(1, '您没有权限');
        if(!$error = ChatRoomRepository::deleteUser($roomId, $userIds)){
            return $this->show(0);
        }
        return $this->show(1, $error);
    }

    //申请加群
    public function inRoom(int $roomId)
    {
        if($roomId <= 0){
            return $this->show(1, '参数错误');
        }

        if($error = ChatRoomRepository::inRoom($this->user['userId'], $roomId)){
            return $this->show(1, $error);
        }
        return $this->show(0);
    }

    // 退出房间
    public function outRoom()
    {
        if(!($roomId = (int)$this->get('roomId'))){
            return $this->show(1, '参数错误');
        }
        if($error = ChatRoomRepository::outRoom($roomId, $this->user['userId'])){
            return $this->show(1, $error);
        }
        return $this->show(0);
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

    // 修改群资料
    public function upRoomInfo()
    {
        if(($roomId = (int)$this->get('roomId')) < 1)
            return $this->show(1, '参数错误');
        if(!in_array(\App\Socket\Model\ChatRoom::getUserRoomSas($this->user['userId'], $roomId), \App\Socket\Model\ChatRoom::ADMINACTION)){
            return $this->show(1, '您没有权限');
        }
        $key = 'upRoomInfo_'.$roomId;
        if(!Redis::check($key, 5)){
            return $this->show(1, '请不要频繁修改聊天室信息');
        }
        $update = [];
        if(!empty($room_name = $this->get('room_name'))){
            if(mb_strlen($room_name) > 12)
                return $this->show(1, '名称太长啦');
            $update['room_name'] = $room_name;
        }
        if(count($update) && ChatRoomRepository::upRoomInfo($roomId, $update)){
            return $this->show(0, '');
        }
        return $this->show(1, '修改失败');
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
            'page' => 1,
            'rows' => 14
        ], (bool) $this->get('isSaveCache'));
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
        $uList = ChatRoomDt::uMapRoomInfo([
            'chat_room_dt.id' => $roomId,
        ]);
        return $this->show(0, '', $uList);
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
        $toUsers = explode(',', trim($toUsers, ','));
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

    // 设置我在本群的昵称
    public function setRoomNickname()
    {
        if(empty($nickname = $this->get('nickname')) || (($roomId = (int)$this->get('roomId')) < 1))
            return $this->show(1, '参数错误');
        if($error = ChatRoomRepository::setRoomNickname($this->user['userId'], $roomId, $nickname)){
            return $this->show(2, $error);
        }
        return $this->show(0);
    }

    //通过申请
    public function passlog()
    {
        if(!$id = (int)$this->get('id')){
            return $this->show(1, '参数错误');
        }
        $key = 'passlog_'.$id;
        if(!Redis::check($key, 10)){
            return $this->show(1, '正在处理，请不要频繁操作');
        }
        if(!count($info = \App\Socket\Model\ChatRoomDtLog::get([
            'id' => $id
        ], [
            'limit' => 1
        ])[0] ?? [])){
            return $this->show(1, '没有数据');
        }
        if($info['status'] !== 0){
            return $this->show(1, '已被处理');
        }
        if(!in_array(\App\Socket\Model\ChatRoom::getUserRoomSas($this->user['userId'], $info['to_id']), \App\Socket\Model\ChatRoom::ADMINACTION)){
            return $this->show(1, '您没有权限');
        }
        if($error = ChatRoomRepository::passlog($id, $info)){
            return $this->show(2, (string)$error);
        }
        Redis::del($key);
        return $this->show(0);
    }


    //群申请列表
    public function subLog()
    {
        if(($roomId = (int)$this->get('roomId')) < 1){
            return $this->show(1, '参数错误');
        }
        if(!in_array(\App\Socket\Model\ChatRoom::getUserRoomSas($this->user['userId'], $roomId), \App\Socket\Model\ChatRoom::ADMINACTION)){
            return $this->show(1, '您没有权限');
        }
        return $this->show(0, '', ChatRoomDtLog::getList([
            'to_id' => $roomId,
            'status' => 0
        ]));
    }

}