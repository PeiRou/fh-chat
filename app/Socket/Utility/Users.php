<?php
/**
 * 单聊
 */

namespace App\Socket\Utility;


class Users
{
    //
    public static function buildMsg($status, $msg, $iRoomInfo, $toUserId)
    {
        $msgArr = app('swoole')->msgBuild($status, $msg, $iRoomInfo, 'users');
        $arr = [
            'user_id' => $iRoomInfo['userId'],
            'to_user' => $toUserId,
            'is_look' => 0,
            'status' => $msgArr['status'],
            'nickname' => $msgArr['nickname'],
            'img' => $msgArr['img'],
            'msg' => $msgArr['msg'],
            'dt' => $msgArr['dt'],
            'bg1' => $msgArr['bg1'],
            'bg2' => $msgArr['bg2'],
            'font' => $msgArr['font'],
            'level' => $msgArr['level'],
            'k' => $msgArr['k'],
            'nS' => $msgArr['nS'],
            'anS' => $msgArr['anS'],
            'uuid' => $msgArr['uuid'],
            'times' => $msgArr['times'],
            'time' => $msgArr['time'],
            'type' => $msgArr['type'],
            'is_look' => 0,
            'created_at' => date('Y-m-d H:i:s'),
            'userMap' => self::getUserMap([$toUserId, $iRoomInfo['userId']])
        ];
        return $arr;
    }

    public static function getUserMap(...$args)
    {
        return implode(',', self::asort($args));
    }

    public static function asort(array $arr)
    {
        asort($arr);
        return $arr;
    }

    //存聊天信息
    public static function insertMsgLog($arr)
    {
        return \App\Socket\Pool\Mysql2Pool::invoke(function (\App\Socket\Pool\Mysql2Object $db) use($arr) {
            return $db->insert('personal_log', $arr);
        });
    }

    //单聊发消息
    public static function senMessage(array $user, $msg, $toUserId)
    {
        $arr = Users::buildMsg(2, $msg, $user, $toUserId);
        $msg = htmlspecialchars($msg);

        # 会员在线消息推过去
        $toUserFd = Room::getUserFd($toUserId);
        if($toUserFd > 0){
            # 会员是不是正在这个聊天环境 如果是状态改为已读
            $s = Room::getUserStatus($toUserId);
            if($s && $s['type'] == 'users' && $s['id'] == $user['userId']){
                $arr['is_look'] = 1;
                $arr['look_time'] = date('Y-m-d H:i:s');
                app('swoole')->push($toUserFd, json_encode($arr, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            }
        }
        # 设置自己聊过的列表
        Room::setHistoryChatList($user['userId'], 'users', $toUserId, ['lastMsg' => $msg]);

        # 设置目标用户聊过的列表
        $lookNum = $arr['is_look'] == 1 ? 0 : 1;
        Room::setHistoryChatList($toUserId, 'users', $user['userId'], [
            'lookNum' => $lookNum,
            'lastMsg' => $msg
        ]);
        # 推送自己
        $arr['status'] = 4;
        $fd = (int)Room::getUserFd($user['userId']);
        app('swoole')->push($fd, json_encode($arr, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        unset($arr['type']);
        //记录聊天日志
        if(!Users::insertMsgLog($arr)){ }
        return true;
    }



}