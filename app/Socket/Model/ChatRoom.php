<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/5/28
 * Time: 14:25
 */

namespace App\Socket\Model;


use App\Socket\Exception\FuncApiException;

class ChatRoom extends Base
{
    /**
     * 2:普通会员
     * 3:管理员
     * 4:房主
     */
    const USER = 2;
    const ADMIN = 3;
    const FOUNDER = 4;

    const ADMINACTION = [3, 4]; # 有管理员权限的



    //房间列表
    protected static function getRoomList($db, $param = [], $columns = null, $isSaveCache = false)
    {
        return self::RedisCacheData(function() use($db, $param, $columns){
            isset($param['is_open']) && $db->where('is_open', 1);
            isset($param['is_auto']) && $db->where('is_auto', $param['is_auto']);
            isset($param['rooms']) && $db->where('room_id', $param['rooms'], 'IN');
            $db->orderBy("top_sort","desc");
            $db->orderBy("room_id","asc");
            return $db->get('chat_room', null, $columns ?? ['room_id', 'room_name','head_img']);
        }, 30, false, $isSaveCache);
    }

    protected static function getRoomValue($db, $param = [], $value, $isSaveCache = false)
    {
        return self::RedisCacheData(function()use($db, $param, $value){
            foreach ($param as $k=>$v)
                $db->where($k, $v);
            return $db->getOne('chat_room', [$value])[$value] ?? null;
        }, 30, false, $isSaveCache); //10秒缓存
    }

    protected static function getRoomOne($db, $param = [], $isSaveCache = false)
    {
        return self::RedisCacheData(function()use($db, $param){
            if(isset($param['room_founders'])){
                $db->where('room_founder', $param['room_founders'], 'IN');
                unset($param['room_founders']);
            }
            foreach ($param as $k=>$v)
                $db->where($k, $v);
            return $db->getOne('chat_room') ?? null;
        }, 30, false, $isSaveCache);
    }

    //获取房间的说有管理员
    protected static function getRoomSas($db, int $roomId, bool $isSaveCache = false)
    {
        $room = ChatRoom::getRoomOne($db, ['room_id' => $roomId], $isSaveCache);
        return array_unique(array_diff(explode(',', $room['chat_sas']), ['']));
    }

    /**
     * 会员在房间的身份
     * 2:普通会员
     * 3:管理员
     * 4:房主
     */
    protected static function getUserRoomSas($db, int $userId, int $roomId, bool $isSaveCache = false):int
    {
        $room = ChatRoom::getRoomOne($db, ['room_id' => $roomId], $isSaveCache);
        $sas = array_unique(array_diff(explode(',', $room['chat_sas']), ['']));
        if(in_array($userId, $sas)){
            if($userId == $room['room_founder'])
                return self::FOUNDER;
            return self::ADMIN;
        }
        return self::USER;
    }

    /**
     * 加入房间
     * @param $roomId 房间id
     * @param array $param where数组
     * @return bool
     */
//    protected static function inRoom($db, $roomId, $param = [])
//    {
//        $db->startTransaction();
//        try{
//            if(!ChatRoom::getRoomOne($db, ['room_id' => $roomId], true)){
//                throw new \Exception('没有这个房间');
//            }
//            isset($param['user_id']) && $db->where('users_id', $param['user_id']);
//            $uModel = clone $db;
//            $user = $db->getOne('chat_users');
//
//            # 加入用户房间映射
//            $rooms = explode(',', $user['rooms']);
//            array_push($rooms, $roomId);
//            $uModel->update('chat_users', [
//                'rooms' => trim(implode(',', array_unique($rooms)), ',')
//            ]);
//
//            # 加入房间
//            $data = [
//                'id' => $roomId,
//                'user_id' => $user['users_id'],
//                'user_name' => $user['username'],
//                'is_speaking' => 1,
//                'created_at' => date('Y-m-d H:i:s'),
//                'updated_at' => date('Y-m-d H:i:s'),
//            ];
//            $db->insert('chat_room_dt', $data);
//            $db->commit();
//            return true;
//        }catch (\Throwable $e){
//            $db->rollback();
//            writeLog('error', var_export($e->getMessage().$e->getFile().'('.$e->getLine().')', 1));
//            return false;
//        }
//    }

    /**
     * 加入房间 - 批量
     * @param $roomId 房间id
     * @param array $param where数组
     * @return bool
     */
    protected static function inRoom($db, int $roomId, $userIds)
    {
        $userIds = (array)$userIds;

        $db->startTransaction();
        try{
            if(!ChatRoom::getRoomOne($db, ['room_id' => $roomId], true)){
                throw new FuncApiException('没有这个房间', 200);
            }
            $aUsers = ChatUser::getList([], [
                'whereRaw' => [
                    "users_id IN ( ". implode(',', array_merge([0], $userIds)) ." ) ",
                    "users_id NOT IN ( SELECT user_id FROM chat_room_dt WHERE id = {$roomId} AND user_id IN ( ". implode(',', array_merge([0], $userIds)) ." ) )",
                ],
                'column' => ['users_id', 'rooms', 'username', 'nickname'],
                'nocache' => true
            ]);
            if(count($aUsers) < 1){
                throw new FuncApiException('没有找到会员，或会员已经加入房间！', 200);
            }
            $update = [];
            $data = [];
            foreach ($aUsers as $v){
                $rs = explode(',', $v['rooms']);
                array_push($rs, $roomId);
                $update[] = [
                    'users_id' => $v['users_id'],
                    'rooms' => trim(implode(',', array_unique($rs)), ','),
                ];
                $data[] = [
                    'id' => $roomId,
                    'user_id' => $v['users_id'],
                    'user_name' => $v['username'],
                    'room_nickname' => $v['nickname'],
                    'is_speaking' => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ];
            }
            self::batchUpdate($db, $update, 'users_id', 'chat_users');
            if(!$db->insertMulti('chat_room_dt', $data)){
                throw new FuncApiException('失败', 201);
            }
            $db->commit();
            return false;
        }catch (\Throwable $e){
            $db->rollback();
            if(!($e instanceof FuncApiException)){
                \App\Socket\Utility\Trigger::getInstance()->throwable($e);
                return 'error';
            }
            return $e->getMessage();
        }
    }

    /**
     * 退出房间
     * @param $roomId
     * @param array $param
     */
//    protected static function outRoom($db, $roomId, $param = [])
//    {
//        $db->startTransaction();
//        try{
//            isset($param['user_id']) && $db->where('users_id', $param['user_id']);
//            $uModel = clone $db;
//            $user = $db->getOne('chat_users');
//            # 删除用户房间映射
//            $rooms = explode(',', $user['rooms']);
//            $rooms = array_diff($rooms, [$roomId]);
//            $uModel->update('chat_users', [
//                'rooms' => trim(implode(',', array_unique($rooms)), ',')
//            ]);
//
//            # 退出房间
//            $db->where('id', $roomId)->where('user_id', $user['users_id'])->delete('chat_room_dt');
//            $db->commit();
//            return true;
//        }catch (\Throwable $e){
//            $db->rollback();
//            writeLog('error', var_export($e->getMessage().$e->getFile().'('.$e->getLine().')', 1));
//            return false;
//        }
//    }

    /**
     * 退出房间 - 批量
     * @param $roomId
     * @param array $param
     */
    protected static function outRoom($db, $roomId, $userIds)
    {
        $userIds = (array)$userIds;
        $db->startTransaction();
        try{
            $aUsers = ChatUser::getList([], [
                'whereRaw' => [
                    "users_id IN ( ". implode(',', array_merge([0], $userIds)) ." ) ",
                  ],
                'column' => ['users_id', 'rooms'],
                'nocache' => true
            ]);
            if(count($aUsers) < 1){
                throw new FuncApiException('没有找到会员', 200);
            }
            $update = [];
            foreach ($aUsers as $v){
                $rs = explode(',', $v['rooms']);
                $rs = array_diff($rs, [$roomId]);
                $update[] = [
                    'users_id' => $v['users_id'],
                    'rooms' => trim(implode(',', array_unique($rs)), ','),
                ];
            }
            if(!self::batchUpdate($db, $update, 'users_id', 'chat_users')){
                throw new FuncApiException('失败！', 200);
            }
            $chat_sas = self::getRoomValue($db, [
                'room_id' => $roomId
            ], 'chat_sas', true);
            $sasArr = explode(',', $chat_sas);
            $sas_sas1 = trim(implode(',', array_unique(array_diff($sasArr, $userIds))), ',');
            if($chat_sas !== $sas_sas1){
                $db->where('room_id', $roomId)->update('chat_room',[
                    'chat_sas' => $sas_sas1
                ]);
            }
            $db->where('id', $roomId)->where('user_id', $userIds, 'IN')->delete('chat_room_dt');
            $db->commit();
            return false;
        }catch (\Throwable $e){
            $db->rollback();
            if(!($e instanceof FuncApiException)){
                writeLog('error', $e->getMessage().$e->getFile().'('.$e->getLine().')'.$e->getTraceAsString());
                return 'error';
            }
            return $e->getMessage();
        }
    }

    /**
     * 删除管理
     * @param $roomId
     * @user_id 管理id
     */
    protected static function outAdmin($db, $roomId, $user_id)
    {
        try{
            $db->where('room_id', $roomId);
            $model = clone $db;
            $chat_sas = $db->getOne('chat_room',['chat_sas'])['chat_sas'];
            # 删除用户房间映射
            $users = explode(',', $chat_sas);
            $users = array_diff($users, [$user_id]);
            $model->update('chat_room',[
                'chat_sas' => trim(implode(',', array_unique($users)), ',')
            ]);
            return true;
        }catch (\Throwable $e){
            writeLog('error', var_export($e->getMessage().$e->getFile().'('.$e->getLine().')', 1));
            return false;
        }
    }

    /**
     * 增加管理
     * @param $roomId
     * @user_id 管理id
     */
    protected static function inAdmin($db, $roomId, $user_id)
    {
        try{
            $db->where('room_id', $roomId);
            $model = clone $db;
            $chat_sas = $db->getOne('chat_room',['chat_sas'])['chat_sas'];
            $users = explode(',', $chat_sas);
            array_push($users, $user_id);
            $model->update('chat_room',[
                'chat_sas' => trim(implode(',', array_unique($users)), ',')
            ]);
            return true;
        }catch (\Throwable $e){
            writeLog('error', var_export($e->getMessage().$e->getFile().'('.$e->getLine().')', 1));
            return false;
        }
    }

    /**
     * 删除房间
     * @param $roomId
     */
    protected static function delRoom($db, $roomId)
    {
        try{
            $db->startTransaction();
            if(!self::delUserRoom($db, $roomId))
                throw new \Exception('');
            $db->where('id', $roomId)->delete('chat_room_dt');
            $db->where('room_id', $roomId)->delete('chat_room');
            $db->commit();
            return true;
        }catch (\Throwable $e){
            $db->rollback();
            return false;
        }
    }

    /**
     * 删除所有在这个房间的用户映射
     */
    protected static function delUserRoom($db, $roomId)
    {
        # 找出所有在这个房间的用户映射
        $arr = $db->where('FIND_IN_SET("'.$roomId.'",rooms)')->get('chat_users', null, ['rooms', 'users_id']);
        $data = [];
        foreach ($arr as $k=>$v){
            $data[] = [
                'users_id' => $v['users_id'],
                'rooms' => trim(implode(',', array_unique(array_diff($users = explode(',', $v['rooms']), [$roomId]))), ',')
            ];
        }

        # 组成数组一次性修改所有的房间
        return self::batchUpdate($db, $data, 'users_id', 'chat_users');
    }

    //新建房间
    protected static function buildRoom($db, $userId, $param)
    {
        $data = [
            'is_auto' => (int)$param['is_auto'],
            'room_name' => $param['room_name'],
//            'head_img' => $param['head_img'],
            'roomtype' => $param['roomtype'] ?? 1,
            'chat_sas' => $userId,
            'room_founder' => $userId,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        return $db->insert('chat_room', $data);
    }
    //修改
    protected static function update($db, $where, $param)
    {
        foreach ($where as $k=>$v){
            $db->where($k, $v);
        }
        !isset($param['updated_at']) && ($param['updated_at'] = date('Y-m-d H:i:s'));
        return $db->update('chat_room', $param);
    }

    //获取需要推送跟单的房间
    protected static function getPushBetInfoRooms($db, $gameId)
    {
        return self::RedisCacheData(function() use($db, $gameId){
            $res = $db->where('FIND_IN_SET("'.$gameId.'",`pushBetGame`)')->get('chat_room', null, ['room_id']);
            return array_map(function($val){
                return $val['room_id'];
            }, $res);
        }, 30);
    }

    // 改变申请列表的未处理的变状态
    protected static function upstaus($db, $status, $param = [])
    {
        foreach ($param as $k => $v)
            $db->where($k, $v);
        return $db->update('chat_room_dt_log', [
            'status' => $status
        ]);
    }
}