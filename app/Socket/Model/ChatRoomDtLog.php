<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/11/20
 * Time: 12:01
 */

namespace App\Socket\Model;


use App\Socket\Exception\FuncApiException;

class ChatRoomDtLog extends Base
{
    /**
     * 申请加入房间 - 批量
     * @param $roomId 房间id
     * @param array $param where数组
     * @return bool
     */
    protected static function inRoom($db, int $roomId, $userIds)
    {
        try{
            if(!ChatRoom::getRoomOne($db, ['room_id' => $roomId], true)){
                throw new FuncApiException('没有这个房间', 200);
            }
            $aUsers = ChatUser::getList([], [
                'whereRaw' => [
                    "users_id IN ( ". implode(',', array_merge([0], $userIds)) ." ) ",
                    "users_id NOT IN ( SELECT user_id FROM chat_room_dt WHERE id = {$roomId} AND user_id IN ( ". implode(',', array_merge([0], $userIds)) ." ) )",
                    "users_id NOT IN ( SELECT user_id FROM chat_room_dt_log WHERE to_id = {$roomId} AND user_id IN ( ". implode(',', array_merge([0], $userIds)) ." ) )",
                ],
                'column' => ['users_id', 'rooms', 'username', 'img', 'nickname'],
                'nocache' => true
            ]);
            if(count($aUsers) < 1){
                throw new FuncApiException('没有找到会员，请等待审核，或会员已经加入房间！', 200);
            }
            $data = [];
            foreach ($aUsers as $v){
                $data[] = [
//                    'id' => $roomId,
                    'user_id' => $v['users_id'],
                    'to_id' => $roomId,
                    'status' => 0,
                    'img' => $v['img'],
                    'name' => $v['nickname'],
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ];
            }
            if(!$db->insertMulti('chat_room_dt_log', $data)){
                throw new FuncApiException('申请失败！', 201);
            }
            return false;
        }catch (\Throwable $e){
            if(!($e instanceof FuncApiException)){
                writeLog('error', $e->getMessage().$e->getFile().'('.$e->getLine().')'.$e->getTraceAsString());
                return 'error';
            }
            return $e->getMessage();
        }
    }

    //列表
    protected static function getList($db, $param = [])
    {
        foreach ($param as $k=>$v)
            $db->where($k, $v);
        $db->orderBy('IF(`status` = 0, 1, 0)', 'DESC');
        $db->orderBy('created_at', 'DESC');
        return $db->get('chat_room_dt_log', null, ['id', 'status', 'img', 'name']);
    }
}