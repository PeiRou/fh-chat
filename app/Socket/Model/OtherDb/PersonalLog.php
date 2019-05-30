<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/5/28
 * Time: 15:49
 */

namespace App\Socket\Model\OtherDb;


use App\Socket\Utility\Room;
use App\Socket\Utility\Users;

class PersonalLog extends Base
{

    //用户聊天记录
    protected static function getPersonalLog($db, $user_id, $to_id)
    {
        # 获取未读条数
        $lookNum = Room::getHistoryChatValue($user_id, 'users', $to_id, 'lookNum') ?? 0;
        $offset = 50 + $lookNum;
        $userMap = Users::getUserMap($user_id, $to_id);
        # 获取要删除的所有id
        $ids = $db->rawQuery(' SELECT `id` FROM `personal_log` WHERE `userMap` = "'.$userMap.'" AND `is_look` = 1  ORDER BY `id` DESC LIMIT 200 OFFSET '.$offset);

        # 通知这两个人删除信息
        if(count($ids)){
            $db->where('id', $ids, 'IN')->delete('personal_log');
            app('swoole')->sendUser($user_id, 24, $ids);
            app('swoole')->sendUser($to_id, 24, $ids);
        }

        # 获取剩下的， 不管多少都拿
        $list = $db->where('userMap', $userMap)->get('personal_log');

        return $list;
    }
}