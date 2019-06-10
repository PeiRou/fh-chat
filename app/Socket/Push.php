<?php
/**
 * 推送消息 所有的推送都为异步 不影响别的处理
 */

namespace App\Socket;


use App\Socket\Model\ChatFriendsList;
use App\Socket\Model\ChatFriendsLog;
use App\Socket\Model\ChatRoom;
use App\Socket\Model\ChatUser;
use App\Socket\Model\OtherDb\PersonalLog;
use App\Socket\Redis\Chat;
use App\Socket\Utility\Room;
use App\Socket\Utility\SortName;
use App\Socket\Utility\Task\TaskManager;
use App\Socket\Utility\Trigger;

class Push
{
    //推送用户聊过的列表
    public static function getHistoryChatList($fd, $iRoomInfo)
    {
        TaskManager::async(function()use($fd, $iRoomInfo) {
            $list = Room::getHistoryChatList($iRoomInfo['userId']);
            $msg = app('swoole')->json(20, $list);
            app('swoole')->push($fd, $msg);
        });
    }

    public static function pushUser($userId, $column = 'all', $async = true)
    {
        $user = ChatUser::getUser(['users_id' => $userId]);
        if($user){
            $fds = Chat::getUserFd($userId);
            foreach ($fds as $fd){
                self::pushList($fd, $user, $column, $async);
            }
        }
        else{
            return false;
        }

    }

    /**
     * 异步推送好友列表、群组列表、好友申请列表、用户聊过的列表
     * @param $fd
     * @param $user
     * @param string $column
     * @param bool $async 是否使用异步推送
     */
    public static function pushList($fd, $user, $column = 'all', $async = true)
    {
        !isset($user['userId']) && $user['userId'] = $user['users_id'];
        $closure = function()use($fd,$user, $column){
            try{
                $columns = $column;
                is_string($columns) && $columns = [$columns];
                is_string($user['rooms']) && $user['rooms'] = explode(',', $user['rooms']);
                $data = [];
                # 群组列表 房间列表
                if(in_array('RoomList', $columns) || $column == 'all')
                    $data['RoomList'] = ChatRoom::getRoomList(['is_open' => 1,'rooms' => $user['rooms']]);
                # 聊过的列表
                if(in_array('HistoryChatList', $columns) || $column == 'all')
                    $data['HistoryChatList'] = Room::getHistoryChatList($user['userId']);
                # 好友列表
                if(in_array('FriendsList', $columns) || $column == 'all')
                    $data['FriendsList'] = SortName::addPeople(ChatFriendsList::getUserFriendList($user['userId']), 'nickname');
                # 好友申请列表
                if(in_array('FriendsLogList', $columns) || $column == 'all'){
                    # 列表
                    $data['FriendsLogList'] = SortName::addPeople(ChatFriendsLog::getFriendsLogList(['to_id' => $user['userId']]), 'name');
                    # 未处理数
                    $data['FriendsLogListNum'] = ChatFriendsLog::getFriendsLogListNum($user['userId']) ?? 0;
                }

                $msg = app('swoole')->json(22,$data);
                app('swoole')->push($fd, $msg);
                return true;
            }catch (\Throwable $e){
                Trigger::getInstance()->throwable($e);
                return false;
            }
        };
        if($async){
            TaskManager::async($closure);
        }else{
            return $closure();
        }
    }


    /**
     * 推送单聊历史记录
     * @param $fd
     * @param $user_id 当前的user_id
     * @param $toUserId 好友id
     */
    public static function pushPersonalLog($fd,$user_id, $toUserId, $param = [])
    {
        TaskManager::async(function()use($fd,$user_id, $toUserId, $param) {
            $data = PersonalLog::getPersonalLog($user_id, $toUserId, $param);
            $swoole = app('swoole');
            $status = Room::getFdStatus($fd);
            foreach ($data as $v) {
                $u = Room::getFdStatus($fd);
                if($status['type'] !== $u['type'] ||
                    $status['id'] !== $u['id'])
                    break;
                $v['status'] = 2;
                if ($v['user_id'] == $user_id)
                    $v['status'] = 4;
                $swoole->push($fd, json_encode($v));
            }
        });
    }

    /**
     * 推送多对一历史记录
     * @param $fd
     * @param $user_id 当前的user_id
     * @param $toUserId 那一个会员的id  如果是客服会传入会员id  如果是会员会传入自己id、
     * @param $roomId 会员对应群的id
     */
    public static function pushManyLog($fd,$user_id, $toUserId, $roomId, $param = [])
    {
        TaskManager::async(function()use($fd,$user_id, $toUserId, $roomId, $param) {
            $data = PersonalLog::getManyLog($user_id, $toUserId, $roomId, $param);
            $swoole = app('swoole');
            $status = Room::getFdStatus($fd);
            foreach ($data as $v) {
                $u = Room::getFdStatus($fd);
                if($status['type'] !== $u['type'] ||
                    $status['id'] !== $u['id'])
                    break;
                if ($v['user_id'] == $user_id)
                    $v['status'] = 4;
                $swoole->push($fd, json_encode($v));
            }
        });
    }

    //推群聊历史记录
    public static function pushRoomLog($fd, $iRoomInfo, $roomId, $param = [])
    {
        if($roomId !== 2){
//            app('swoole')->chkHisMsg($iRoomInfo,$fd);
            self::chkHisMsg($fd, $iRoomInfo, ['page' => $param['page'] ?? 1]);
        }else{
            self::pushManyLog($fd,$iRoomInfo['userId'], $iRoomInfo['userId'], $roomId, $param);
        }
    }

    public static function chkHisMsg($fd, $iRoomInfo, $param = [])
    {
        $iRoomHisTxt = PersonalLog::getRoomLog($iRoomInfo['room'], $param);
        $status = Room::getFdStatus($fd);
        foreach ($iRoomHisTxt as $tmpkey =>$hisMsg) {
            $u = Room::getFdStatus($fd);
            if($status['type'] !== $u['type'] ||
                $status['id'] !== $u['id'])
                break;
            //如果需要推送
            if(isset($hisMsg['level']) && !empty($hisMsg['level']) && $hisMsg['level'] != 98){
                $aAllInfo = app('swoole')->getIdToUserInfo($hisMsg['k']);
                if(isset($aAllInfo['img']) && !empty($aAllInfo['img']) && ($hisMsg['img'] != $aAllInfo['img'])){
                    $hisMsg['img'] = $aAllInfo['img'];
                    PersonalLog::insertMsgLog($hisMsg);
                }
            }
            if(isset($hisMsg['status']) && !in_array($hisMsg['status'],array(8,9))){         //状态非红包
                if($hisMsg['k']==md5($iRoomInfo['userId']))     //如果历史讯息有自己的讯息则调整status = 4
                    $hisMsg['status'] = 4;
                else
                    $hisMsg['status'] = 2;
            }
            $msg = json_encode($hisMsg,JSON_UNESCAPED_UNICODE);
            app('swoole')->push($fd, $msg);
        }
    }

    //推权限
    public static function pushSpeak($type, $iRoomInfo, $async = true)
    {
        $closure = function()use($iRoomInfo, $type){
            if($type == 'users'){
                $iRoomInfo['noSpeak'] = ChatUser::getUserValue(['users_id' => $iRoomInfo['userId']], 'chat_status');
                $iRoomInfo['allnoSpeak'] = 0; # 单聊就就不管聊天室的权限
            }elseif ($type == 'room'){ # 聊天室先不管 进入房间的时候已经推过了

            }elseif ($type == 'many'){
                $iRoomInfo['noSpeak'] = ChatUser::getUserValue(['users_id' => $iRoomInfo['userId']], 'chat_status');
                $iRoomInfo['allnoSpeak'] = 0; # 多对一就就不管聊天室的权限
            }
            # 更新权限
            return app('swoole')->push(Room::getUserFd($iRoomInfo['userId']), app('swoole')->msg(7,'fstInit',$iRoomInfo));
        };
        if($closure) {
            if ($async) {
                TaskManager::async($closure);
            } else {
                return $closure();
            }
        }
        return false;
    }

    /**
     * 推送消息 只要有一个fd推送成功就返回成功
     * @param $userId  目标userId
     * @param $type  消息类型
     * @param $id  消息目标id  users：userId | room：roomId | many：userId
     * @param $msg
     * @param bool $isSetHistoryChatList 是否记录未读消息数
     * @param array $aParam 扩展参数
     * @param bool $async 是否异步
     */
    public static function pushUserMessage($userId, $type, $id, $msg, $aParam = [], $isSetHistoryChatList = true, $async = true)
    {
        $closure = function() use($userId, $type, $id, $msg, $aParam, $isSetHistoryChatList){
            $lookNum = 1;
            $fds = Chat::getUserFd((int)$userId);
            foreach ($fds as $fd){
                $s = Room::getFdStatus($fd);

                if($s && $s['type'] == $type && $s['id'] == $id){
                    if(app('swoole')->push($fd, $msg))
                        $lookNum = 0;
                }
            }
            # 设置目标用户聊过的列表
            if($isSetHistoryChatList){
                Room::setHistoryChatList($userId, $type, $id, [
                    'lookNum' => $lookNum,
                    'lastMsg' => $aParam['msg'] ?? $msg
                ]);
            }
        };
        if ($async) {
            TaskManager::async($closure);
        } else {
            return $closure();
        }
    }

    /**
     * 推送删除消息
     * @param $type
     * @param $idx  删除消息的key
     * @param $userId 这个不是当前token的userId， 是这条消息的发送人id
     * @param $toId   目标id
     * @param $roomId  只有tyop=many的时候会用到
     */
    public static function pushDelChatLogAction($type, $idx, $userId, $toId, $roomId)
    {
        self::pushDelChatLog($type, $idx, $userId, $toId, $roomId);
        # 单聊特殊 toId 和user_id要反过来在通知一遍
        if($type == 'users')
            self::pushDelChatLog($type, $idx, $toId, $userId, $roomId);
    }

    public static function pushDelChatLog($type, $idx, $userId, $toId, $roomId)
    {
        //获取需要推送的user
        $users = [];
        if($type == 'users'){
            $users = [$userId];
        }elseif($type == 'room'){
            $users = Room::getRoomUserId($toId);
        }elseif($type == 'many'){
            $users = ChatRoom::getRoomSas($roomId);
            array_push($users, $toId);
            array_unique($users);
        }
        $msg = app('swoole')->json(24, [
            'type' => $type,
            'idx' => $idx,
            'toId' => $toId,
            'roomId' => $roomId
        ]);
        # 推送这些人 只有打开这个页面的才推送 没打开的不推送
        foreach ($users as $userId){
            self::pushUserMessage($userId, $type, $toId, $msg, [], false);
        }
    }

}