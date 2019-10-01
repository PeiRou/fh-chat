<?php

namespace App\Model;

use Illuminate\Support\Facades\DB;

class ChatSendConfig extends Base
{

    protected $table = 'chat_send_config';

    public static function getConfig($param = [])
    {
        return self::HandleCacheData(function()use($param){
            return ChatSendConfig::where(function($sql) use($param){
                isset($param['room_id']) && $sql->where('room_id', $param['room_id']);
            })->get();
        }, 1, true, true);
    }

    public static function editConfig($roomId, $data = [])
    {
        try{
            DB::beginTransaction();
            self::where('room_id', $roomId)->delete();
            if(!self::insert($data)){
                throw new \Exception('更新失败');
            }
            DB::commit();
            return null;
        }catch (\Throwable $e){
            writeLog('error', $e->getMessage().$e->getFile().'('.$e->getLine().')'.$e->getTraceAsString());
            DB::rollback();
            return $e->getCode();
        }
        return 'error';
    }
    public static function editConfigBefore($request, $roomId = 0)
    {
        $sendConfig = [];
        if(isset($request->starttime, $request->endtime)){
            foreach ($request->starttime as $k=>$v){
                if(empty($v) || empty($request->endtime[$k]))
                    continue;
                $sendConfig[] = [
                    'send_starttime' => $v,
                    'send_endtime' => $request->endtime[$k] ?? '0:00'
                ];
            }
        }
        return self::editConfig($roomId, $sendConfig);
    }

}
