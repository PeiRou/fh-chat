<?php
/**
 * 群聊
 */

namespace App\Socket\Utility;


use App\Service\Cache;
use App\Socket\Exception\SocketApiException;
use App\Socket\Model\ChatFriendsList;
use App\Socket\Model\ChatRoom;
use App\Socket\Model\ChatRoomDt;
use App\Socket\Model\ChatUser;
use App\Socket\Push;
use App\Socket\Redis\Chat;
use App\Socket\Utility\Tables\FdStatus;
use Illuminate\Support\Facades\Storage;

class Room
{
    use Cache;
    //进入房间
    public static function joinRoom($roomId, $fd, $iRoomInfo)
    {
        # 如果在其它房间就退出
        if($status = self::getFdStatus($fd)){
            if($status['type'] == 'room')
                self::exitRoom($status['id'], $fd, $iRoomInfo);
        }
        # 将fd 推入 room list  用来获取聊天室的所有在线fd
        self::roomPush($roomId, $fd, $iRoomInfo['userId']);
        # 将userId 推入 room list 用来获取聊天室的所有在线userId
        self::roomPushUserId($roomId, $fd, $iRoomInfo['userId']);
        # 设置 Fd => RoomId 映射
        self::setFdRoomIdMap($fd, $roomId);
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

        return Chat::getUserId($fd);
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
     * $type 聊天模式 users：单聊、 room：群聊、many：多对一
     */
    public static function setFdStatus($userId, $id, $type, $fd)
    {
        FdStatus::getInstance()->set($fd, [
            'userId' => $userId,
            'fd' => $fd,
            'type' => $type,
            'id' => $id
        ]);
    }
    //删除用户状态
    public static function delFdStatus($fd)
    {
        return FdStatus::getInstance()->del($fd);
    }
    //获取用户状态
    public static function getFdStatus($fd)
    {
        return FdStatus::getInstance()->get($fd);
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
    //获取 roomlist下所有Fd
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

    /**
     * 记录用户聊过的列表
     * @param $userId 要设置的userId
     * @param $type room users many
     * @param $id  room:roomId | users:userId | many:userId
     * @param $aParam
     * @return bool
     */
    public static function setHistoryChatList($userId, $type, $id, $aParam)
    {
        try{
            $disk = 'home';
            $filekey = 'chatList/'.$userId.'/'.$type.'_'.$id;

            if((is_null($param = self::get($filekey, $disk)) || !$param) || !$param = @json_decode($param, 1)){
                # 没有的话创建默认值
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
                    preg_match('/img=\/upchat\/dataimg/', $value) && $value = '图片';
                    $param['lastMsg'] = $value;
                    $param['lastTime'] = time();
                }else{
                    if($value instanceof \Closure){
                        if($key == 'name' && (empty($param[$key]) || ($param['update_name_at'] < time() - 3600 * 24))){
                            $param[$key] = call_user_func($value);
                            $param['update_name_at'] = time();
                        }
                    }else{
                        $param[$key] = $value;
                        $param['lastTime'] = time();
                    }
                }
            }
            !isset($aParam['roomId']) && $param['roomId'] = 0;

            # name 如果没有的话自己根据id查  24小时更新一次
            # 注：type = many 的情况比较特殊 需要根据房间的名称组合 所以在上面写了闭包来设置
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
                $param['update_name_at'] = time();
            }
            # 因为上面使用闭包设置name  所以head_img就没设置  这里如果是空的话设置一下
            if(empty($param['head_img'])){
                if($type == 'many' || $type == 'users'){
                    $param['head_img'] = ChatUser::getUserValue(['users_id' => $id], 'img');
                }
            }
            $json = json_encode($param, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if(!$json)
                return false;
            if(self::set($filekey, $json, $disk)){
                # 每次设置就将信息推送前端改变 因为异步里面不能使用异步 改为同步
                return Push::pushUser($userId, 'HistoryChatList', false);
            }
            return true;
        }catch (\Throwable $e){
            Trigger::getInstance()->throwable($e);
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
        # 是否在当前房间
        if(!ChatRoomDt::getOne([
            'id' => $roomId,
            'user_id' => $iRoomInfo['userId']
        ]))
            throw new SocketApiException('您不在当前房间，请先加入房间');
        //发送消息
        if(!is_array($iRoomInfo))
            $iRoomInfo = (array)$iRoomInfo;
        $getUuid = app('swoole')->getUuid($iRoomInfo['name']);
        $iRoomInfo['timess'] = $getUuid['timess'];
        $iRoomInfo['uuid'] = $getUuid['uuid'];
        self::sendRoom($fd, $iRoomInfo, $aMesgRep, $roomId);
        //自动推送清数据
//        app('swoole')->chkHisMsg($iRoomInfo,$fd,false);
    }

    //发送消息到聊天室
    public static function sendRoom($fd, $iRoomInfo, $msg, $roomId)
    {
        $aMesgRep = base64_encode(str_replace('+', '%20', urlencode($msg)));
        # 所有在群里的会员
        $userIds = ChatRoomDt::getRoomUserIds($roomId);
        foreach ($userIds as $toUserId){
            $status = 2;
            if(Chat::getUserId($fd) == $toUserId)
                $status = 4;
            $bMsg = app('swoole')->msg($status,$aMesgRep,$iRoomInfo,'room', $roomId);
            Push::pushUserMessage($toUserId, 'room', $roomId, $bMsg,['msg' => $msg]);
        }
    }

    /**
     * 发送消息到聊天室 房间的所有会员
     * @param $roomId
     * @param $msg
     * @param $lastMsg
     * @param bool $isSetLookNum false不记录未读条数
     */
    public static function sendRoomSystemMsg($roomId, $msg, $lastMsg, $isSetLookNum = false)
    {
        $userIds = ChatRoomDt::getRoomUserIds($roomId);
        foreach ($userIds as $toUserId){
            Push::pushUserMessage($toUserId, 'room', $roomId, $msg,['msg' => $lastMsg],['isSetLookNum'=>$isSetLookNum]);
        }
    }

}