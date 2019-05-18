<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ChatRoom extends Model
{

    protected $table = 'chat_room';

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
                'rooms' => trim(implode(',', $rooms), ',')
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
                'rooms' => trim(implode(',', $rooms), ',')
            ]);

            # 退出房间
            $data = [
                'id' => $roomId,
                'user_id' => $user->users_id,
            ];
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
                'chat_sas' => trim(implode(',', $users), ',')
            ]);
            return true;
        }catch (\Throwable $e){
            writeLog('error', var_export($e->getMessage().$e->getFile().'('.$e->getLine().')', 1));
            return false;
        }
    }
    /**
     * 删除管理
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
                'chat_sas' => trim(implode(',', $users), ',')
            ]);
            return true;
        }catch (\Throwable $e){
            writeLog('error', var_export($e->getMessage().$e->getFile().'('.$e->getLine().')', 1));
            return false;
        }
    }
}
