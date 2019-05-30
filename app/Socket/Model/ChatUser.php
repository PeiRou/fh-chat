<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/5/27
 * Time: 22:10
 */

namespace App\Socket\Model;


use App\Service\Cache;

class ChatUser extends Base
{
    use Cache;

    //获取好友
    protected static function getUserFriend($db, $param = [])
    {
        foreach ($param as $k=>$v)
            $db->where($k, $v);
        return $db->getOne('chat_friends_list');
    }
    //获取好友列表
    protected static function getUserFriendList($db, $param = [])
    {
        foreach ($param as $k=>$v)
            $db->where($k, $v);
        return $db->get('chat_friends_list');
    }

    //是否可以添加好友
    protected static function checkAddFriend($db, $user_id, $toUserId)
    {
        $db->where('user_id', $user_id);
        $db->where('to_id', $toUserId);
        $res = $db->getOne('chat_friends_log');
        if(count($res)){
            if($res['status'] === 0)
                return false;
        }
        return true;
    }


    //添加好友
    protected static function addFriend($db, array $user, $toUserId)
    {
        $data = [
            'user_id' => $user['users_id'],
            'name' => $user['username'],
            'img' => $user['img'],
            'to_id' => $toUserId,
        ];
        if($data['id'] = $db->insert('chat_friends_log', $data)) {
            unset($data['to_id']);
            return $data;
        }
        return false;
    }

    //好友申请列表
    protected static function getFriendsLogList($db, $param = [])
    {
        foreach ($param as $k=>$v)
            $db->where($k, $v);
        $res = $db->get('chat_friends_log');
        return $res;
    }

    //用户信息
    protected static function getUserValue($db, $param = [], $value)
    {
        return self::HandleCacheData(function()use($db, $param, $value){
            foreach ($param as $k=>$v)
                $db->where($k, $v);
            return $db->getOne('chat_users', [$value])[$value] ?? null;
        }, 5);
    }

    //用户名称 昵称
    protected static function getUserName($db, $param = [])
    {
        return self::HandleCacheData(function()use($db, $param){
            foreach ($param as $k=>$v)
                $db->where($k, $v);
            return $db->getOne('chat_users', ['nickname'])['nickname'] ?? null;
        }, 5);
    }
}