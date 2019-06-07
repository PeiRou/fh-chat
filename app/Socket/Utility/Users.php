<?php
/**
 * 单聊
 */

namespace App\Socket\Utility;


use App\Socket\Model\OtherDb\PersonalLog;
use App\Socket\Push;
use App\Socket\Utility\Task\TaskManager;

class Users
{
    //
    public static function buildMsg($status, $msg, $iRoomInfo, $toId, $type)
    {
        $msgArr = app('swoole')->msgBuild($status, $msg, $iRoomInfo, $type, $toId);
        $msgArr['msg'] = base64_encode(str_replace('+', '%20', urlencode($msgArr['msg'])));
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

        # 会员在线消息推过去
        Push::pushUserMessage($toUserId, 'users', $user['userId'], json_encode($arr, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),['msg' => $msg]);

        # 推送自己
        $arr['status'] = 4;
        Push::pushUserMessage($user['userId'], 'users', $toUserId, json_encode($arr, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ['msg' => $msg]);

        //记录聊天日志
        if(!PersonalLog::insertMsgLog($arr)){ }
        return true;
    }



}