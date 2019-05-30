<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/5/28
 * Time: 14:25
 */

namespace App\Socket\Model;


use App\Repository\ChatRoomRepository;
use App\Service\Cache;

class ChatRoom extends Base
{
    use Cache;
    //房间列表
    protected static function getRoomList($db, $param = [])
    {
        isset($param['is_open']) && $db->where('is_open', 1);
        isset($param['rooms']) && $db->where('room_id', $param['rooms'], 'IN');
        return $db->get('chat_room', null, ['room_id', 'room_name','head_img']);
    }

    //
    protected static function getRoomValue($db, $param = [], $value)
    {
        return self::HandleCacheData(function()use($db, $param, $value){
            foreach ($param as $k=>$v)
                $db->where($k, $v);
            return $db->getOne('chat_room', [$value])[$value] ?? null;
        }, 5);
    }

    /**
     * 加入房间
     * @param $roomId 房间id
     * @param array $param where数组
     * @return bool
     */
    protected static function inRoom($db, $roomId, $param = [])
    {
        $db->startTransaction();
        try{
            isset($param['user_id']) && $db->where('users_id', $param['user_id']);
            $uModel = clone $db;
            $user = $db->getOne('chat_users');

            # 加入用户房间映射
            $rooms = explode(',', $user['rooms']);
            array_push($rooms, $roomId);
            $uModel->update('chat_users', [
                'rooms' => trim(implode(',', array_unique($rooms)), ',')
            ]);

            # 加入房间
            $data = [
                'id' => $roomId,
                'user_id' => $user['users_id'],
                'user_name' => $user['username'],
                'is_speaking' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];
            $db->insert('chat_room_dt', $data);
            $db->commit();
            return true;
        }catch (\Throwable $e){
            $db->rollback();
            writeLog('error', var_export($e->getMessage().$e->getFile().'('.$e->getLine().')', 1));
            return false;
        }
    }

    /**
     * 退出房间
     * @param $roomId
     * @param array $param
     */
    protected static function outRoom($db, $roomId, $param = [])
    {
        $db->startTransaction();
        try{
            isset($param['user_id']) && $db->where('users_id', $param['user_id']);
            $uModel = clone $db;
            $user = $db->getOne('chat_users');
            # 删除用户房间映射
            $rooms = explode(',', $user['rooms']);
            $rooms = array_diff($rooms, [$roomId]);
            $uModel->update('chat_users', [
                'rooms' => trim(implode(',', array_unique($rooms)), ',')
            ]);

            # 退出房间
            $db->where('id', $roomId)->where('user_id', $user['users_id'])->delete('chat_room_dt');
            $db->commit();
            return true;
        }catch (\Throwable $e){
            $db->rollback();
            writeLog('error', var_export($e->getMessage().$e->getFile().'('.$e->getLine().')', 1));
            return false;
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

            # 增加房间映射
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
            # 删除所有在这个房间的用户映射
            if(!self::delUserRoom($db, $roomId))
                throw new \Exception('');
            # 删除房间所有人
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
}