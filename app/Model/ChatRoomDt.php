<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ChatRoom extends Model
{

    /**
     * 加入房间
     */
    public static function inRoom($roomId, $param = [])
    {

        try{
            $uModel = DB::table('chat_users')->select('users_id', 'username', 'rooms')->where(function($sql)use($param){
                isset($param['user_id']) && $sql->where('users_id', $param['user_id']);
            });
            $user = $uModel->first();
            # 加入用户房间映射
            $rooms = explode(',', $user->rooms);
            array_push($rooms, $roomId);
            $model =

            # 加入房间
            $data = [
                'id' => $roomId,
                'user_id' => $user->users_id,
                'user_name' => $user->username,
                'is_speaking' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];
            DB::table('chat_room_dt')->insert($data);
            return true;
        }catch (\Throwable $e){
            writeLog('error', var_export($e->getMessage().$e->getFile().'('.$e->getLine().')', 1));
            return false;
        }
    }

}
