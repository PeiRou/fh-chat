<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/5/28
 * Time: 22:02
 */

namespace App\Socket\Http\Controllers;


use App\Socket\Http\Controllers\Traits\BackLogin;
use App\Socket\Model\ChatFriendsLog;
use App\Socket\Model\ChatHongbaoBlacklist;
use App\Socket\Model\ChatUser;
use App\Socket\Repository\ChatRoomRepository;

class BackAction extends Base
{
    use BackLogin;

    //后台管理员房间踢人
    public function deleteUser()
    {
        if(!($roomId = (int)$this->get('roomId')) || !$user_id = (int)$this->get('user_id')){
            return $this->show(1, '参数错误');
        }
        if(!ChatRoomRepository::deleteUser($roomId, $user_id)){
            return $this->show(0);
        }
        return $this->show(1, 'error');
    }
    //后台管理员房间加人
    public function addRoomUser()
    {
        if(!($roomId = (int)$this->get('roomId')) || !$user_id = (int)$this->get('user_id')){
            return $this->show(1, '参数错误');
        }
        if(!ChatRoomRepository::addRoomUser($roomId, $user_id)){
            return $this->show(0);
        }
        return $this->show(1, 'error');
    }
    //删除管理
    public function delAdmin()
    {
        if(!($roomId = (int)$this->get('roomId')) || !$user_id = (int)$this->get('user_id')){
            return $this->show(1, '参数错误');
        }
        if(ChatRoomRepository::delAdmin($roomId, $user_id)){
            return $this->show(0);
        }
        return $this->show(1, 'error');
    }
    //添加管理
    public function addRoomAdmin()
    {
        if(!($roomId = (int)$this->get('roomId')) || !$user_id = (int)$this->get('user_id')){
            return $this->show(1, '参数错误');
        }
        if(ChatRoomRepository::addRoomAdmin($roomId, $user_id)){
            return $this->show(0);
        }
        return $this->show(1, 'error');
    }
    //删除房间
    public function delRoom()
    {
        if(!($roomId = (int)$this->get('roomId'))){
            return $this->show(1, '参数错误');
        }
        # 删除房间
        if(ChatRoomRepository::delRoom($roomId)){
            return $this->show(0);
        }

        return $this->show(1, 'error');
    }

    //置顶房间(不改数据库)
    public function setSortRoom()
    {
        if(!($roomId = (int)$this->get('roomId'))){
            return $this->show(1, '参数错误');
        }
        $top_sort = (int)$this->get('top_sort');
        if($r = ChatRoomRepository::setSortRoom($roomId, $top_sort)){
            return $this->show(1, $r);
        }
        return $this->show(0);
    }

    //更新红包黑名单的内存缓存
    public function upStaticChatHongbaoBlacklist()
    {
        ChatHongbaoBlacklist::upUsers((int)$this->get('chat_hongbao_idx'));
        return $this->show(0, $this->get('chat_hongbao_idx'));
    }

    // 添加两个人为好友
    public function addFriends(string $name, string $toName)
    {
        $s = ChatUser::getList([], [
            'whereRaw' => [' username IN(\''.implode("','", [$name, $toName]).'\') ']
        ]);
        if(count($s) !== 2){
            return $this->show(1, '没有找到会员');
        }
        try{
            ChatFriendsLog::addUserFriends($s[0], $s[1]);
            return $this->show(0, '');
        }catch (\Throwable $e){
            return $this->show(3, 'error');
        }
    }

}