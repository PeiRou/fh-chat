<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/5/28
 * Time: 15:49
 */

namespace App\Socket\Model\OtherDb;


use App\Socket\Utility\Room;
use App\Socket\Utility\Task\TaskManager;
use App\Socket\Utility\Users;
use Illuminate\Support\Facades\Storage;

class PersonalLog extends Base
{

    const FILEPATH = 'userChatLog/';
    const LOG_MAX_NUM = 5;  //聊天记录保存条数


    //用户聊天记录
    protected static function getPersonalLog($db, $user_id, $to_id)
    {
        $path = self::FILEPATH.'users'.'/'.str_replace(',','_', Users::getUserMap($user_id, $to_id)).'/';
        return self::getPersonalLogfile($db, $path);
    }

    //多对一聊天记录
    protected static function getManyLog($db, $user_id, $to_id, $roomId)
    {
        $path = self::FILEPATH.'many'.'/'.str_replace(',','_', Users::getUserMap($to_id, $roomId)).'/';
        return self::getPersonalLogfile($db, $path);
    }
    //聊天室历史记录
    protected static function getRoomLog($db, $roomId)
    {
        $path = self::FILEPATH.'room'.'/'.$roomId.'/';
        return self::getPersonalLogfile($db, $path);
    }

    //存聊天信息 数组
    protected static function insertMsgLog($db, $arr)
    {
        $filePath = '';
        if($arr['type'] == 'room'){
            $filePath = $arr['toId'];
        }else{
            $filePath = str_replace(',','_', $arr['userMap']);
        }
        $path = self::FILEPATH.$arr['type'].'/'.$filePath.'/';
        return self::insertMsgLogFile($db, $arr, $path);
    }
    //存聊天信息 数组
    protected static function insertMsgLogRoom($db, $arr)
    {
        $path = self::FILEPATH.$arr['type'].'/'.$arr['toId'].'/';
        return self::insertMsgLogFile($db, $arr, $path);
    }

    protected static function getPersonalLogdb($db, $user_id, $to_id)
    {
        # 获取未读条数
        $lookNum = Room::getHistoryChatValue($user_id, 'users', $to_id, 'lookNum') ?? 0;
        $offset = 50 + $lookNum;
        $userMap = Users::getUserMap($user_id, $to_id);
        # 获取要删除的所有id
        $ids = $db->rawQuery(' SELECT `id` FROM `personal_log` WHERE `userMap` = "'.$userMap.'" AND `is_look` = 1  ORDER BY `id` DESC LIMIT 200 OFFSET '.$offset);
        # 通知这两个人删除信息
        if(count($ids)){
            $db->where('id', $ids, 'IN')->delete('personal_log');
        }

        # 获取剩下的， 不管多少都拿
        $list = $db->where('userMap', $userMap)->get('personal_log');
        return $list;
    }

    protected static function getPersonalLogfile($db, $path)
    {
        $storage = Storage::disk('home');
        $iRoomUsers = array();
        $files = Storage::disk('home')->files($path);
        $timess = (int)(microtime(true)*1000*10000*10000);

        //控制数据
        $needDelnum = count($files)-self::LOG_MAX_NUM;
        $needDelnum = $needDelnum > 0 ? $needDelnum : 0;
        $ii = -1;
        foreach ($files as $value){
            $ii ++;
            if($storage->exists($value)){
                $orgHis = $storage->get($value);
                $aryHis =  (array)json_decode($orgHis);
                $iRoomUsers[$aryHis['time']] = $aryHis;
                if($aryHis['time'] < ($timess-(7200*1000*10000*10000)) || $ii < $needDelnum){
                    if(Storage::disk('home')->exists($value))
                        Storage::disk('home')->delete($value);              //删除历史
                    continue;
                }
            }
        }

        ksort($iRoomUsers);
        return $iRoomUsers;
    }

    // 用文件保存日志
    protected static function insertMsgLogFile($db, $arr, $path)
    {
        $addVal = $arr;
        if(is_array($addVal)) $addVal = json_encode($addVal, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $tmpTxt = $path;
        for($ii=0;$ii<10000;$ii++){//判断是否有并发一样的时间
            $timeIdx = (int)$arr['uuid'] + $ii;
            if(!Storage::disk('home')->exists($tmpTxt.$timeIdx)){
                if($ii>0){
                    $addId = $timeIdx;
                    $addVal = json_decode($addVal,true);
                    $addVal['time'] = $addId;
                    $addVal = json_encode($addVal,JSON_UNESCAPED_UNICODE);
                }
                break;
            }
        }
        if(!Storage::disk('home')->put($tmpTxt.$timeIdx, $addVal))
            return false;
        //删除多余信息
        $files = Storage::disk('home')->files($path);
        $needDelnum = count($files)-self::LOG_MAX_NUM;
        if($needDelnum > 0){
            TaskManager::async(function() use($needDelnum, $files){
                while ($needDelnum){
                    $v = array_shift($files);
                    if(Storage::disk('home')->exists($v)){
                        $arr = json_decode(Storage::disk('home')->get($v), 1);
                        Storage::disk('home')->delete($v);
                        # 通知这两个人删除消息
                        app('swoole')->sendUser($arr['user_id'], 24, [
                            'type' => $arr['type'],
                            'id' => $arr['uuid'],
                            'toId' => $arr['toId']
                        ]);
                        app('swoole')->sendUser($arr['toId'], 24, [
                            'type' => $arr['type'],
                            'id' => $arr['uuid'],
                            'toId' => $arr['toId']
                        ]);
                    }
                    $needDelnum --;
                }
            });
        }
        return true;
    }
}