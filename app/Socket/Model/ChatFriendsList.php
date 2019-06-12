<?php


namespace App\Socket\Model;


class ChatFriendsList extends Base
{

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

}