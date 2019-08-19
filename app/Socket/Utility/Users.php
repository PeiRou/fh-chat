<?php
/**
 * 单聊
 */

namespace App\Socket\Utility;


use App\Socket\Exception\SocketApiException;
use App\Socket\Model\ChatFriendsList;
use App\Socket\Model\OtherDb\PersonalLog;
use App\Socket\Push;
use App\Socket\Redis\Chat;
use App\Socket\Utility\Task\TaskManager;

class Users
{
    //
    public static function buildMsg($status, $msg, $iRoomInfo, $toId, $type, $roomId = 0)
    {
        $msgArr = app('swoole')->msgBuild($status, $msg, $iRoomInfo, $type, $toId, $roomId);
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
    public static function sendMessage(array $user, $msg, $toUserId, $fd)
    {
        # 是不是好友
        if(!ChatFriendsList::getUserFriend([
            'user_id' => $user['userId'],
            'to_id' => $toUserId,
        ])){
            Push::pushFdTipMessage($fd, '你们不是好友');
            throw new SocketApiException('你们不是好友');
        }

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

    // 验证其它用户的所有fd token 如果失效删除所有登录信息
    public static function checkFdToken(int $userId, string $sess)
    {
        $fds = Chat::getUserFd($userId);
        foreach ($fds as $fd){
            $userInfo = app('swoole')->getUserInfo($fd);
            if(isset($userInfo['sess']) && $userInfo['sess'] !== $sess){
                app('swoole')->sendToSerf($fd, 3, '登陆失效');
                app('swoole')->ws->close($fd);  # 如果已经链接的fd保存的sess不对，就主动关闭链接
            }
        }
    }

}