<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/5/18
 * Time: 20:52
 */

namespace App\Repository;


use Illuminate\Support\Facades\Storage;

class ChatRoomRepository extends BaseRepository
{

    /**
     * 清user_id映射的fd对应user信息
     * @param $user_id
     * @return bool
     */
    public static function clearUserInfo($user_id)
    {
        try{
            $chatusr = 'chatusr:'.md5($user_id);
            $fd = Storage::disk('chatusr')->get($chatusr);
            Storage::disk('chatusrfd')->delete('chatusrfd:'.$fd);
            return true;
        }catch (\Throwable $e){
            return false;
        }
    }

}