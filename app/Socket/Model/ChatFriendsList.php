<?php

namespace App\Socket\Model;


class ChatFriendsList extends Base
{
    protected static $DB_READ_FUNCTION = ['getUserFriendList', 'invitationUserList', 'getUserFriend'];
    //获取好友列表
    protected static function getUserFriendList($db, $userId, int $toId = null)
    {
        $where = '';
        if($toId)
            $where .= " AND `u`.`users_id` = {$toId} ";
        $sql = " SELECT 	
                    `u`.`users_id` AS `user_id`,
                    `u`.`nickname`,
                    `u`.`img`,
                    `l`.`remark`
                FROM
                `chat_users` AS `u`
                LEFT JOIN `chat_friends_list` AS `l` ON `u`.`users_id` = `l`.`to_id`
                WHERE `l`.`user_id` = {$userId} {$where}";

        return $db->rawQuery($sql);
    }

    //邀请好友进群的好友列表
    protected static function invitationUserList($db, int $userId, int $roomId)
    {
        $sql = "SELECT
                    c.*,
                    ! isnull( d.user_id ) AS is_room ,
	                u.username, 
	                u.img
                FROM
                    chat_friends_list AS c
                    LEFT JOIN chat_room_dt AS d ON c.to_id = d.user_id AND d.id = {$roomId} 
                    LEFT JOIN chat_users AS u ON u.users_id = c.to_id 
                WHERE
                    c.user_id = {$userId}
                    AND `c`.`status` = 1 ";
        return $db->rawQuery($sql);
    }

    //获取好友 单个
    protected static function getUserFriend($db, $param = [])
    {
        return self::RedisCacheData(function() use ($db, $param){
            foreach ($param as $k=>$v)
                $db->where($k, $v);
            return $db->getOne('chat_friends_list');
        }, 30, false);
    }

    //添加一个人为好友
    protected static function addUserFriends($db, int $userId, array $toUser)
    {
        $data = [
            'user_id' => $userId,
            'to_id' => $toUser['users_id'],
            'remark' => $toUser['nickname'],
            'status' => 1,
//            'nickname' => $toUser['nickname'],
//            'img' => $toUser['img'],
        ];
        return $db->insert('chat_friends_list', $data);
    }

    //设置备注
    protected static function setRemark($db, int $userId, int $toId, string $remark)
    {
        return $db->where('user_id', $userId)
            ->where('to_id', $toId)
            ->update('chat_friends_list', [
                'remark' => $remark
            ]);
    }

    //删除好友
    protected static function delUserFriends($db, int $userId, int $toId)
    {
        $db->startTransaction();
        try{
            $db->where('user_id', $toId)->where('to_id', $userId)->delete('chat_friends_list');
            $db->where('user_id', $userId)->where('to_id', $toId)->delete('chat_friends_list');
            $db->commit();
            return true;
        }catch (\Throwable $e){
            $db->rollback();
            return false;
        }
    }

}