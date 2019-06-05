<?php
/**
 * 群聊
 */

namespace App\Socket\Utility;


use App\Service\Cache;
use App\Socket\Model\ChatFriendsList;
use App\Socket\Model\ChatRoom;
use App\Socket\Model\ChatRoomDt;
use App\Socket\Push;
use App\Socket\Utility\Tables\UserStatus;
use Illuminate\Support\Facades\Storage;

class Room
{
    use Cache;
    //进入房间
    public static function joinRoom($roomId, $fd, $iRoomInfo)
    {
        # 如果在其它房间就退出
        if($status = self::getUserStatus($iRoomInfo['userId'])){
            if($status['type'] == 'room')
                self::exitRoom($status['id'], $fd, $iRoomInfo);
        }
        # 将fd 推入 room list
        self::roomPush($roomId, $fd, $iRoomInfo['userId']);
        # 将userId 推入 room list
        self::roomPushUserId($roomId, $fd, $iRoomInfo['userId']);
        # 设置 Fd => RoomId 映射
        self::setFdRoomIdMap($fd, $roomId);
        # 设置用户状态 打开的群组还是单人 和id
        self::setUserStatus($iRoomInfo['userId'], $roomId, 'room', $fd);
        # 修改数据库表房间
        \App\Socket\Pool\MysqlPool::invoke(function (\App\Socket\Pool\MysqlObject $db) use($iRoomInfo, $roomId) {
            return $db->where('users_id', $iRoomInfo['userId'])->update('chat_users', ['room_id' => $roomId]);
        });
        # 清空用户进入的房间未读消息数 并且加入这个列表
        self::setHistoryChatList($iRoomInfo['userId'], 'room', $roomId, ['lookNum' => 0]);
    }

    //离开房间 - 只是更换房间 不退出房间
    public static function exitRoom($roomId, $fd, $iRoomInfo)
    {
        # 将fd 移除 room list
        self::deleteRoomFd($roomId, $fd);
        # 将UserId 移除 room list
        self::deleteRoomUserId($roomId, $iRoomInfo['userId']);
        # 删除 Fd => RoomId 映射
        self::deleteRoomIdMapByFd($fd);
        # 删除用户状态
        self::delUserStatus($iRoomInfo['userId']);
    }

    //获取用户fd
    public static function getUserFd($user_id)
    {
        $chatusr = 'chatusr:'.md5($user_id);
        return Storage::disk('chatusr')->exists($chatusr) ? Storage::disk('chatusr')->get($chatusr) : null;
    }
    //获取用户id
    public static function getUserId($fd)
    {
        return self::getiRoomInfo($fd)['userId'] ?? null;
    }
    //获取用户信息
    public static function getIRoomInfo($fd)
    {
        $key = 'chatusrfd:'.$fd;
        return Storage::disk('chatusrfd')->exists($key) ? @json_decode(Storage::disk('chatusrfd')->get($key), 1) : null;
    }

    //------------------------------------------------------------------------------------------------------------------

    /**
     *  设置用户状态
     * $type 聊天模式 users：单聊、 room：群聊
     */
    public static function setUserStatus($userId, $id, $type, $fd)
    {
        UserStatus::getInstance()->set($userId, [
            'userId' => $userId,
            'fd' => $fd,
            'type' => $type,
            'id' => $id
        ]);
    }
    //删除用户状态
    public static function delUserStatus($userId)
    {
        UserStatus::getInstance()->del($userId);
    }
    //获取用户状态
    public static function getUserStatus($userId)
    {
        return UserStatus::getInstance()->get($userId);
    }

    //------------------------------------------------------------------------------------------------------------------
    //设置 Fd => RoomId 映射
    public static function setFdRoomIdMap($fd, $roomId)
    {
        $key = 'roomIdFdMap/'.$fd;
        return self::set($key, $roomId);
    }
    //删除fd 在 RoomId => fd 映射
    public static function deleteRoomIdMapByFd($fd)
    {
        $key = 'roomIdFdMap/'.$fd;
        return self::del($key);
    }
    //获取fd 在 RoomId => fd 映射
    public static function getRoomIdMapByFd($fd)
    {
        $key = 'roomIdFdMap/'.$fd;
        return self::get($key);
    }

    //------------------------------------------------------------------------------------------------------------------
    //将userId 推入 room list
    public static function roomPushUserId($roomId, $fd, $userId)
    {
        $key = 'roomUserId/'.$roomId.'/'.$userId;
        return self::set($key, $fd);
    }
    //删除Room中的userId
    public static function deleteRoomUserId($roomId, $userId)
    {
        $key = 'roomUserId/'.$roomId.'/'.$userId;
        return self::del($key);
    }
    //获取 Room中的userId
    public static function getRoomUserId($roomId)
    {
        $key = 'roomUserId/'.$roomId;
        $list = Storage::disk('room')->files($key);

        return array_map(static function($v){
            $v = explode('/', $v);
            $v = @array_pop($v) ?? '';
            return $v;
        }, $list);
    }
    //------------------------------------------------------------------------------------------------------------------
    //将fd 推入 room list
    public static function roomPush($roomId, $fd, $userId)
    {
        $key = 'roomList/'.$roomId.'/'.$fd;
        return self::set($key, $userId);
    }
    //删除Room中的Fd
    public static function deleteRoomFd($roomId, $fd)
    {
        $key = 'roomList/'.$roomId.'/'.$fd;
        return self::del($key);
    }
    //获取 roomlist下所有UserId
    public static function getRoomFd($roomId)
    {
        $key = 'roomList/'.$roomId;
        $list = Storage::disk('room')->files($key);

        return array_map(static function($v){
            $v = explode('/', $v);
            $v = @array_pop($v) ?? '';
            return $v;
        }, $list);
    }

    //------------------------------------------------------------------------------------------------------------------
    //记录用户聊过的列表
    public static function setHistoryChatList($userId, $type, $id, $aParam)
    {
        $disk = 'home';
        $filekey = 'chatList/'.$userId.'/'.$type.'_'.$id;

        if((is_null($param = self::get($filekey, $disk)) || !$param) || !$param = @json_decode($param, 1)){
            $param = [];
            $param['type'] = $type;
            $param['id'] = $id;
            $param['user_id'] = $userId;
            $param['update_name_at'] = time();
            $param['lookNum'] = 0;
            $param['lastMsg'] = '';
            $param['lastTime'] = time();
        }
        $param['update_at'] = date('Y-m-d H:i:s');
        $param['lookNum'] = $param['lookNum'] ?? 0;
        foreach ($aParam as $key => $value){
            if($key == 'lookNum'){
                if($value > 0){
                    $param['lookNum'] = $param['lookNum'] + $value;
                    $param['lastTime'] = time();
                }
                else
                    $param['lookNum'] = $value;
            }elseif($key == 'lastMsg'){
                $param['lastMsg'] = $value;
                $param['lastTime'] = time();
            }else{
                if($value instanceof \Closure){
                    if($key == 'name' && (empty($param[$key]) || ($param['update_name_at'] < time() - 3600 * 24))){
                        $param[$key] = call_user_func($value);
                    }
                    if($key == 'head_img' && (empty($param[$key]) || ($param['update_name_at'] < time() - 3600 * 24))){
                        $param[$key] = call_user_func($value);
                    }
                }else{
                    $param[$key] = $value;
                    $param['lastTime'] = time();
                }
            }
        }

        if(empty($param['name']) || ($param['update_name_at'] < time() - 3600 * 24)){
            if($type == 'users'){
                $toUser = ChatFriendsList::getUserFriendList($userId, $id)[0];
                $param['name'] = $toUser['remark'] ?? $toUser['nickname'];
                $param['head_img'] = $toUser['img'];
            }elseif($type == 'room'){
                $room = ChatRoom::getRoomOne(['room_id' => $id]);
                $param['name'] = $room['room_name'];
                $param['head_img'] = $room['head_img'];
            }
        }
        $json = json_encode($param, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if(!$json)
            return false;
        if(self::set($filekey, $json, $disk)){
            # 每次设置就将信息推送前端改变 改为同步
            return Push::pushUser($userId, 'HistoryChatList', false);
        }
        return false;
    }

    //获取单个
    public static function getHistoryChatValue($userId, $type, $id, $value = null)
    {
        $disk = 'home';
        $filekey = 'chatList/'.$userId.'/'.$type.'_'.$id;
        if((is_null($param = self::get($filekey, $disk)) || !$param) || !$param = @json_decode($param, 1))
            return null;
        if($value === null)
            return $param;
        return $param[$value] ?? null;
    }
    //获取用户聊过的列表
    public static function getHistoryChatList($userId)
    {
        $disk = 'home';
        $files = Storage::disk($disk)->allFiles('chatList/'.$userId.'/');
        $list = [];
        foreach ($files as $k=>$v){
            array_push($list,json_decode(self::get($v, $disk), 1));
        }
        $list = array_reverse(array_values(array_sort($list, function ($value) {
            return $value['lastTime'];
        })));
        return $list;
    }

    //-----------------------------------------------------------------------------------
    //用文件操作 之后可以改成别的
    public static function get($key, $disk = 'room')
    {
        return Storage::disk($disk)->exists($key) ? Storage::disk($disk)->get($key) : null;
    }
    public static function set($key, $value, $disk = 'room')
    {
        return Storage::disk($disk)->put($key, $value);
    }
    public static function del($key, $disk = 'room')
    {
        return Storage::disk($disk)->exists($key) && Storage::disk($disk)->delete($key);
    }
    //清除所有聊天室有关的
    public static function clearAllRoom()
    {
        $files = Storage::disk('room')->allFiles();
        while ($files){
            Storage::disk('room')->delete(array_shift($files));
        }
    }

    //聊天室发信息
    public static function sendMessage($fd, $iRoomInfo, $aMesgRep, int $roomId)
    {
        $aMesgRep = urlencode($aMesgRep);
        $aMesgRep = base64_encode(str_replace('+', '%20', $aMesgRep));   //发消息
        //发送消息
        if(!is_array($iRoomInfo))
            $iRoomInfo = (array)$iRoomInfo;
        $getUuid = app('swoole')->getUuid($iRoomInfo['name']);
        $iRoomInfo['timess'] = $getUuid['timess'];
        $iRoomInfo['uuid'] = $getUuid['uuid'];
        self::sendRoom($fd, $iRoomInfo, $aMesgRep, $roomId);
        //自动推送清数据
        app('swoole')->chkHisMsg($iRoomInfo,0,false);
    }

    //发送消息到聊天室
    public static function sendRoom($fd, $iRoomInfo, $msg, $roomId)
    {
        # 所有在群里的会员
        $userIds = ChatRoomDt::getRoomUserIds($roomId);

        # 获取在这个群的userId
        $iRoomUserIds  = self::getRoomUserId($roomId);

        foreach ($userIds as $v){
            $lookNum = 1;
            # 如果打开的是这个群 将消息推送过去 未读消息数就是0 不然消息数+1
            if(in_array($v, $iRoomUserIds)){
                $lookNum = 0;
                $ufd = Room::getUserFd($v);
                # 推消息
                if($ufd == $fd)//组装消息数据
                    $json = app('swoole')->msg(4,$msg,$iRoomInfo,'room', $roomId);   //自己发消息
                else
                    $json = app('swoole')->msg(2,$msg,$iRoomInfo,'room', $roomId);   //别人发消息
                app('swoole')->push($ufd, $json);
            }

            # 设置未读消息数和最后一条消息
            Room::setHistoryChatList($v, 'room', $roomId, [
                'lookNum' => $lookNum,
                'lastMsg' => urldecode(str_replace('%20', '+', base64_decode($msg)))
            ]);
        }

    }

}