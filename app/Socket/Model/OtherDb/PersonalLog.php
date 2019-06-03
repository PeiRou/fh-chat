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

    const DRIVER = 'db';  // db | file


    //用户聊天记录
    protected static function getPersonalLog($db, $user_id, $to_id)
    {
        if(static::DRIVER == 'db'){
            # 获取未读条数
            $lookNum = Room::getHistoryChatValue($user_id, 'users', $to_id, 'lookNum') ?? 0;
            $offset = 50 + $lookNum;
            $userMap = Users::getUserMap($user_id, $to_id);
            # 获取要删除的所有id
            $ids = $db->rawQuery(' SELECT `id` FROM `personal_log` WHERE `userMap` = "'.$userMap.'" AND `is_look` = 1  ORDER BY `id` DESC LIMIT 200 OFFSET '.$offset);
            # 通知这两个人删除信息
            if(count($ids)){
                $db->where('id', $ids, 'IN')->delete('personal_log');
                $data = ['roomType' => 'users'];
                foreach ($ids as $v){
                    $data['id'] = $v;
                    app('swoole')->sendUser($user_id, 24, $data);
                    app('swoole')->sendUser($to_id, 24, $data);
                }
            }

            # 获取剩下的， 不管多少都拿
            $list = $db->where('userMap', $userMap)->get('personal_log');
            return $list;
        }elseif(self::DRIVER == 'file'){
            return '';
        }

    }



    //存聊天信息
    protected static function insertMsgLog($db, $arr)
    {
        if(self::DRIVER == 'db'){
            return $db->insert('personal_log', $arr);
        }elseif(self::DRIVER == 'file'){
            return '';
        }
    }
}