<?php

namespace App\Model;

use Illuminate\Support\Facades\DB;

class ChatRoom extends Base
{

    protected $table = 'chat_room';

    /**
     * 删除房间
     * @param $roomId
     */
    public static function delRoom($roomId)
    {
        try{
            DB::beginTransaction();
            # 删除所有在这个房间的用户映射
            self::delUserRoom($roomId);
            # 删除房间所有人
            $model = ChatRoomDt::where('id', $roomId);
            $model->delete();
            self::where('room_id', $roomId)->delete();
            DB::commit();
            return true;
        }catch (\Throwable $e){
            DB::rollback();
            throw $e;
        }
    }

    /**
     * 删除所有在这个房间的用户映射
     */
    public static function delUserRoom($roomId)
    {
        # 找出所有在这个房间的用户映射
        $arr = ChatUsers::whereRaw('FIND_IN_SET("'.$roomId.'",rooms)')->pluck('rooms', 'users_id');
        $data = [];
        foreach ($arr as $k=>$v){
            $data[] = [
                'users_id' => $k,
                'rooms' => trim(implode(',', array_unique(array_diff($users = explode(',', $v), [$roomId]))), ',')
            ];
        }
        # 组成数组一次性修改所有的房间
        return self::batchUpdate($data, 'users_id', 'chat_users');
    }

    /**
     * 加入房间
     * @param $roomId 房间id
     * @param array $param where数组
     * @return bool
     */
    public static function inRoom($roomId, $param = [])
    {
        DB::beginTransaction();
        try{
            $uModel = ChatUsers::select('users_id', 'username', 'rooms')->where(function($sql)use($param){
                isset($param['user_id']) && $sql->where('users_id', $param['user_id']);
            });
            $user = $uModel->first();
            # 加入用户房间映射
            $rooms = explode(',', $user->rooms);
            array_push($rooms, $roomId);
            $uModel->update([
                'rooms' => trim(implode(',', array_unique($rooms)), ',')
            ]);

            # 加入房间
            $data = [
                'id' => $roomId,
                'user_id' => $user->users_id,
                'user_name' => $user->username,
                'is_speaking' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];
            ChatRoomDt::insert($data);
            DB::commit();
            return true;
        }catch (\Throwable $e){
            DB::rollback();
            writeLog('error', var_export($e->getMessage().$e->getFile().'('.$e->getLine().')', 1));
            return false;
        }
    }

    /**
     * 退出房间
     * @param $roomId
     * @param array $param
     */
    public static function outRoom($roomId, $param = [])
    {
        DB::beginTransaction();
        try{
            $uModel = ChatUsers::select('users_id', 'username', 'rooms')->where(function($sql)use($param){
                isset($param['user_id']) && $sql->where('users_id', $param['user_id']);
            });
            $user = $uModel->first();
            # 删除用户房间映射
            $rooms = explode(',', $user->rooms);
            $rooms = array_diff($rooms, [$roomId]);
            $uModel->update([
                'rooms' => trim(implode(',', array_unique($rooms)), ',')
            ]);

            # 退出房间
            ChatRoomDt::where('id', $roomId)->where('user_id', $user->users_id)->delete();
            DB::commit();
            return true;
        }catch (\Throwable $e){
            DB::rollback();
            writeLog('error', var_export($e->getMessage().$e->getFile().'('.$e->getLine().')', 1));
            return false;
        }
    }
    /**
     * 删除管理
     * @param $roomId
     * @user_id 管理id
     */
    public static function outAdmin($roomId, $user_id)
    {
        try{
            $model = self::where('room_id', $roomId);
            $chat_sas = $model->value('chat_sas');
            # 删除用户房间映射
            $users = explode(',', $chat_sas);
            $users = array_diff($users, [$user_id]);
            $model->update([
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
    public static function inAdmin($roomId, $user_id)
    {
        try{
            $model = self::where('room_id', $roomId);
            $chat_sas = $model->value('chat_sas');
            # 增加房间映射
            $users = explode(',', $chat_sas);
            array_push($users, $user_id);
            $model->update([
                'chat_sas' => trim(implode(',', array_unique($users)), ',')
            ]);
            return true;
        }catch (\Throwable $e){
            writeLog('error', var_export($e->getMessage().$e->getFile().'('.$e->getLine().')', 1));
            return false;
        }
    }

    /**
     * 删除所有房间的管理
     * @param $user_id 管理id
     */
    public static function outAdminAll($user_id)
    {
        $arr = self::whereRaw('FIND_IN_SET("'.$user_id.'",chat_sas)')->pluck('chat_sas', 'room_id');
        $data = [];
        foreach ($arr as $k=>$v){
            $data[] = [
                'room_id' => $k,
                'chat_sas' => trim(implode(',', array_unique(array_diff($users = explode(',', $v), [$user_id]))), ',')
            ];
        }
        # 组成数组一次性修改所有的房间
        return self::batchUpdate($data, 'room_id', 'chat_room');
    }

}
