<?php
/**
 * 单聊
 */

namespace App\Socket\Utility;


use App\Socket\Model\OtherDb\PersonalLog;
use App\Socket\Utility\Task\TaskManager;

class Users
{
    //
    public static function buildMsg($status, $msg, $iRoomInfo, $toId, $type)
    {
        $msgArr = app('swoole')->msgBuild($status, $msg, $iRoomInfo, $type, $toId);
        $msgArr['msg'] = base64_encode(str_replace('+', '%20', urlencode($msgArr['msg'])));
        $msgArr['created_at'] = date('Y-m-d H:i:s');
        $msgArr['userMap'] = self::getUserMap($toId, $iRoomInfo['userId']);
        $msgArr['user_id'] = $iRoomInfo['userId'];
        return $msgArr;
    }

    public static function getUserMap(...$args)
    {
        self::asort($args);
        return implode(',', $args);
    }

    public static function asort(array &$arr)
    {
        return asort($arr);
    }

    //单聊发消息
    public static function sendMessage(array $user, $msg, $toUserId)
    {
        $msg = htmlspecialchars($msg);
        $arr = Users::buildMsg(2, $msg, $user, $toUserId, 'users');
        TaskManager::async(function() use($arr, $user){
            # 推送自己
            $arr['status'] = 4;
            $fd = (int)Room::getUserFd($user['userId']);
            app('swoole')->push($fd, json_encode($arr, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        });
        # 会员在线消息推过去
        $toUserFd = Room::getUserFd($toUserId);
        $lookNum = 1;
        if($toUserFd > 0){
            # 会员是不是正在这个聊天环境 如果是状态改为已读
            $s = Room::getUserStatus($toUserId);
            if($s && $s['type'] == 'users' && $s['id'] == $toUserId){
                $lookNum = 0;
                $arr['look_time'] = date('Y-m-d H:i:s');
                app('swoole')->push($toUserFd, json_encode($arr, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            }
        }
        # 设置目标用户聊过的列表
        Room::setHistoryChatList($toUserId, 'users', $user['userId'], [
            'lookNum' => $lookNum,
            'lastMsg' => $msg
        ]);

        # 设置自己聊过的列表
        Room::setHistoryChatList($user['userId'], 'users', $toUserId, ['lastMsg' => $msg]);
        //记录聊天日志
        if(!PersonalLog::insertMsgLog($arr)){ }
        return true;
    }



}