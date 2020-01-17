<?php


namespace App\Socket\Model;


use App\Socket\Push;
use App\Socket\Repository\ChatFriendsRepository;
use App\Socket\Utility\Task\TaskManager;

class ChatFriendsLog extends Base
{
    protected static $DB_READ_FUNCTION = ['getFriendsLogList','getFriendsLogListNum','getUser','checkAddFriend'];
    //好友申请列表
    protected static function getFriendsLogList($db, $param = [])
    {
        foreach ($param as $k=>$v)
            $db->where($k, $v);
        $res = $db->get('chat_friends_log');
        return $res;
    }

    //未处理的好友请求数
    protected static function getFriendsLogListNum($db, $userId)
    {
        $db->where('to_id', $userId);
        $db->where('status', 0);
        return $db->getValue('chat_friends_log', 'count(*)');
    }

    //根据id获取用户信息
    protected static function getUser($db, int $logId, $column = 'user_id')
    {
        return self::HandleCacheData(function() use($db, $logId, $column){
            $userId = $db->where('id', $logId)->getValue('chat_friends_log', $column);
            if($userId > 0)
                return ChatUser::getUser(['users_id' => $userId]);
            return null;
        }, 5, false);

    }

        /**
     * 处理好友申请
     * @param $db
     * @param $logId id
     * @param $toUser 只能本人处理
     * @param $status
     */
    protected static function setStatus($db, int $logId, array $toUser, int $status)
    {
        $db->startTransaction();
        try{
            $user = self::getUser($db, $logId);
            if(!$user || !count($user))
                throw new \Exception('参数错误', 123);
            $db->where('id', $logId)->where('to_id', $toUser['users_id']);
            if(!$db->update('chat_friends_log', [
                'status' => $status,
                'updated_at' => date('Y-m-d H:i:s')
            ]))
                throw new \Exception('update logdb error', 123);
            # 如果是同意 将两个人互加为好友
            if($status === 1){
                self::addUserFriends($db, $user, $toUser);
            }
            $db->commit();
            return false;
        }catch (\Throwable $e){
            $db->rollback();
            if($e->getCode() == 123)
                return $e->getMessage();
            \App\Socket\Utility\Trigger::getInstance()->throwable($e);
            return 'error';
        }
    }

    // 将两个人加为好友
    protected static function addUserFriends($db, $user, $toUser)
    {
        $db->startTransaction();
        if(
            !ChatFriendsRepository::addUserFriends($db, $user, $toUser) ||
            !ChatFriendsRepository::addUserFriends($db, $toUser, $user)
        ){
            $db->rollback();
            throw new \Exception('add list error', 123);
        }
        $db->commit();
        TaskManager::async(function() use($user, $toUser){
            # 更新这个人好友列表
            Push::pushUser($user['users_id'], ['FriendsList', 'FriendsLogList'], false);
            Push::pushUser($toUser['users_id'], ['FriendsList', 'FriendsLogList'], false);
        });
    }

    //是否可以添加好友 看有没有未处理的
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

    /**
     * 添加好友
     * @param $db
     * @param array $user
     * @param $toUserId
     * @return 申请人的信息和数据id | false
     */
    protected static function addFriend($db, array $user, $toUserId)
    {
        $data = [
            'user_id' => $user['users_id'],
            'name' => $user['username'],
            'img' => $user['img'],
            'to_id' => $toUserId,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        if($data['id'] = $db->insert('chat_friends_log', $data)) {
            unset($data['to_id']);
            return $data;
        }
        return false;
    }
}