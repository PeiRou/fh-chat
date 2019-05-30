<?php
/**
 * 群聊
 */

namespace App\Socket\Utility;


use App\Service\Cache;
use App\Socket\Model\ChatRoom;
use App\Socket\Model\ChatRoomDt;
use App\Socket\Model\ChatUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class Room
{
    use Cache;
    //进入房间
    public static function joinRoom($roomId, $fd, $iRoomInfo)
    {
        # 如果在其它房间就退出
        if(!empty($r = self::getUserIdRoomId($iRoomInfo['userId'])))
            self::exitRoom($r, $fd, $iRoomInfo);
        # 将fd 推入 room list
        self::roomPush($roomId, $fd, $iRoomInfo['userId']);
        # 将userId 推入 room list
        self::roomPushUserId($roomId, $fd, $iRoomInfo['userId']);
        # 设置 Fd => RoomId 映射
        self::setFdRoomIdMap($fd, $roomId);
        # 设置 user_id => RoomId 映射
        self::setUserIdRoomIdMap($iRoomInfo['userId'], $roomId);
        # 设置用户状态 打开的群组还是单人 和id
        self::setUserStatus($iRoomInfo['userId'], $roomId);
        # 修改数据库表房间
        DB::table('chat_users')->where('users_id', $iRoomInfo['userId'])->update(['room_id' => $roomId]);
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
        # 删除 user_id => RoomId 映射
        self::setUserIdRoomIdMap($iRoomInfo['userId'], $roomId);
        # 删除用户状态
        self::delUserStatus($iRoomInfo['userId']);
    }

    //获取用户fd
    public static function getUserFd($user_id)
    {
        $chatusr = 'chatusr:'.md5($user_id);
        return Storage::disk('chatusr')->exists($chatusr) ? Storage::disk('chatusr')->get($chatusr) : null;
    }

    //------------------------------------------------------------------------------------------------------------------

    /**
     *  设置用户状态
     * $type 聊天模式 users：单聊、 room：群聊
     */
    public static function setUserStatus($userId, $id, $type = 'room')
    {
        $key = 'userStatus/'.$userId;
        return self::set($key, json_encode([
            'type' => $type,
            'id' => $id
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
    //删除用户状态
    public static function delUserStatus($userId)
    {
        $key = 'userStatus/'.$userId;
        return self::del($key);
    }
    //获取用户状态
    public static function getUserStatus($userId)
    {
        $key = 'userStatus/'.$userId;
        $res = self::get($key);
        if(!$res)
            return $res;
        return json_decode($res, 1);
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
    //设置 user_id => RoomId 映射 每个用户只能在一个群里发信息
    public static function setUserIdRoomIdMap($userId, $roomId)
    {
        $key = 'UserIdRoomId/'.$userId;
        return self::set($key, $roomId);
    }
    //删除 user_id => RoomId 映射
    public static function deleteUserIdRoomId($userId)
    {
        $key = 'UserIdRoomId/'.$userId;
        return self::del($key);
    }
    //获取用户所在房间
    public static function getUserIdRoomId($userId)
    {
        $key = 'UserIdRoomId/'.$userId;
        return self::get($key);
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
        }
        $param['update_at'] = date('Y-m-d H:i:s');
        $param['lookNum'] = $param['lookNum'] ?? 0;
        foreach ($aParam as $key => $value){
            if($key == 'lookNum'){
                if($value > 0)
                    $param['lookNum'] = $param['lookNum'] + $value;
                else
                    $param['lookNum'] = $value;
            }else{
                $param[$key] = $value;
            }
        }

        if(!isset($param['name']) || ($param['update_name_at'] < time() - 3600 * 24)){
            if($type == 'users'){
                $param['name'] = ChatUser::getUserName(['users_id' => $id]) ?? '';
            }elseif($type == 'room'){
                $param['name'] = ChatRoom::getRoomValue(['room_id' => $id], 'room_name') ?? '';
            }
        }
        # 每次设置就将信息推送前端改变
        app('swoole')->sendUser($userId, 23, $param);

        return self::set($filekey, json_encode($param, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), $disk);
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
    public static function sendMessage($fd, $iRoomInfo, $aMesgRep)
    {
        //不广播被禁言的用户
        if($iRoomInfo['noSpeak']==1)
            return app('swoole')->sendToSerf($fd,5,'此帐户已禁言');

        $speaking = \App\Socket\Pool\RedisPool::invoke(function (\App\Socket\Pool\RedisObject $redis) use($iRoomInfo, $fd) {
            $redis->select(1);
            //如果全局禁言
            if($redis->exists('speak') && $redis->get('speak')=='un'){
                app('swoole')->sendToSerf($fd,5,'当前聊天室处于禁言状态！');
                return false;
            }

            if($redis->exists($iRoomInfo['userId'].'speaking:')){
                $iRoomCss = app('swoole')->cssText(98,4);
                $Css['name'] = '系统消息';                          //用户显示名称
                $Css['level'] = 0;                                //用户背景颜色1
                $Css['bg1'] = $iRoomCss->bg_color1;                //用户背景颜色1
                $Css['bg2'] = $iRoomCss->bg_color2;                //用户背景颜色2
                $Css['font'] = $iRoomCss->font_color;              //用户会话文字颜色
                $Css['img'] = '/game/images/chat/sys.png';         //用户大头
                app('swoole')->sendToSerf($fd,13,'您说话太快啦，请先休息一会',$Css);
                return false;
            }
            $redis->setex($iRoomInfo['userId'].'speaking:',2,'on');
            return true;
        });
        if(!$speaking) return false;

        $aMesgRep = urlencode($aMesgRep);
        $aMesgRep = base64_encode(str_replace('+', '%20', $aMesgRep));   //计划发消息
        //发送消息
        if(!is_array($iRoomInfo))
            $iRoomInfo = (array)$iRoomInfo;
        $getUuid = app('swoole')->getUuid($iRoomInfo['name']);
        $iRoomInfo['timess'] = $getUuid['timess'];
        $iRoomInfo['uuid'] = $getUuid['uuid'];
        self::sendRoom($fd, $iRoomInfo, $aMesgRep, $iRoomInfo['room']);
        //自动推送清数据
        app('swoole')->chkHisMsg($iRoomInfo,0,false);
    }

    //发送消息到聊天室
    public static function sendRoom($fd, $iRoomInfo, $msg, $roomId)
    {
        var_dump($roomId);
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
                    $msg = app('swoole')->msg(4,$msg,$iRoomInfo);   //自己发消息
                else
                    $msg = app('swoole')->msg(2,$msg,$iRoomInfo);   //别人发消息
                app('swoole')->push($ufd, $msg,$iRoomInfo['room']);
            }

            # 设置未读消息数和最后一条消息
            Room::setHistoryChatList($v, 'users', $roomId, [
                'lookNum' => $lookNum,
                'lastMsg' => $msg
            ]);
        }

    }

}