<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/5/28
 * Time: 15:49
 */

namespace App\Socket\Model\OtherDb;


use App\Socket\Push;
use App\Socket\Utility\Room;
use App\Socket\Utility\Task\TaskManager;
use App\Socket\Utility\Users;
use Illuminate\Support\Facades\Storage;

class PersonalLog extends Base
{

    const FILEPATH = 'userChatLog/';
    const LOG_MAX_NUM = 5;  //聊天记录保存条数


    //用户聊天记录
    protected static function getPersonalLog($db, $user_id, $to_id, $param = [])
    {
        $path = self::FILEPATH.'users'.'/'.str_replace(',','_', Users::getUserMap($user_id, $to_id)).'/';
        return self::getPersonalLogfile($db, $path, [
            'type' => 'users',
//            'toId' => $to_id,
            'user_map' => Users::getUserMap($user_id, $to_id),
            'page' => $param['page'] ?? 1,
        ]);
    }

    /**
     * 多对一聊天记录
     * @param $db
     * @param $user_id 请求的userId
     * @param $to_id如果是客服会传入会员id  如果是会员会传入自己id、
     * @param $roomId
     * @param $param  [page]
     * @return array
     */
    protected static function getManyLog($db, $user_id, $to_id, $roomId, $param = [])
    {
        $path = self::FILEPATH.'many'.'/'.$roomId.'/'.$to_id.'/';
        return self::getPersonalLogfile($db, $path, [
            'type' => 'many',
            'toId' => $to_id,
            'roomId' => $roomId,
            'page' => $param['page'] ?? 1,
        ]);
    }
    //聊天室历史记录
    protected static function getRoomLog($db, $roomId, $param = [])
    {
        $path = self::FILEPATH.'room'.'/'.$roomId.'/';
        return self::getPersonalLogfile($db, $path, [
            'type' => 'room',
            'toId' => $roomId,
            'page' => $param['page'] ?? 1,
        ]);
    }

    //存聊天信息 数组
    protected static function insertMsgLog($db, $arr)
    {
        if($arr['type'] == 'room'){
            $filePath = $arr['toId'];
        }elseif($arr['type'] == 'many'){
            $filePath = $arr['roomId'].'/'.$arr['toId'];
        }else{
            $filePath = str_replace(',','_', $arr['userMap']);
        }
        $path = self::FILEPATH.$arr['type'].'/'.$filePath.'/';
        return self::insertMsgLogFile($db, $arr, $path);
    }

    protected static function delRawLog($db, $param = [])
    {
        $res = self::getOne($db, $param);
        if(!count($res))
            return false;
        $path = self::getPath($res['type'], $res['user_id'], $res['to_id'], $res['room_id']);
        self::delOneFile($db, $path.$res['idx']); # 删除文件
    }

    protected static function delOneFile($db, $file)
    {
        if(Storage::disk('home')->exists($file)){
            $arr = json_decode(Storage::disk('home')->get($file), 1);
            Storage::disk('home')->delete($file);
            # 删数据库
            self::deleteRaw($db, [
                'type'=> $arr['type'],
                'file'=> $file
            ]);
            # 通知
            Push::pushDelChatLogAction($arr['type'], $arr['uuid'], $arr['user_id'], $arr['toId'], $arr['roomId']);
        }
    }

    protected static function getOne($db, $param = [])
    {
        isset($param['type']) && $db->where('type', $param['type']);
        isset($param['toId']) && $db->where('to_id', $param['toId']);
        isset($param['roomId']) && $db->where('room_id', $param['roomId']);
        isset($param['idx']) && $db->where('idx', $param['idx']);
        return $db->getOne('chat_log');
    }

    /**
     * @param $type
     * @param $toId
     * @param int $roomId  只有type=many的时候才用到
     */
    public static function getPath($type, $userId, $toId, $roomId = 2)
    {
        if($type == 'room'){
            $filePath = $toId;
        }elseif($type == 'many'){
            $filePath = $roomId.'/'.$toId;
        }else{
            $filePath = str_replace(',','_', Users::getUserMap($userId, $toId));
        }
        return self::FILEPATH.$type.'/'.$filePath.'/';
    }

    protected static function getPersonalLogfile($db, $path, $param = [])
    {
        $storage = Storage::disk('home');
        $res = self::getIdx($db, $param);
        $iRoomUsers = [];
        foreach ($res as $row){
            $file = $path.$row['idx'];
            if(Storage::disk('home')->exists($file)){
                $orgHis = $storage->get($file);
                $aryHis = json_decode($orgHis, 1);
                $iRoomUsers[$aryHis['time']] = $aryHis;
//                array_unshift($iRoomUsers,$aryHis);
            }
        }
//        rsort($iRoomUsers);
        ksort($iRoomUsers);
        return $iRoomUsers;
    }

    protected static function getIdx($db, $param = [])
    {
        isset($param['type']) && $db->where('type', $param['type']);
        isset($param['toId']) && $db->where('to_id', $param['toId']);
        isset($param['roomId']) && $db->where('room_id', $param['roomId']);
        isset($param['user_map']) && $db->where('user_map', $param['user_map']);
        $page = $param['page'] ?? 1;
        $page_size = $param['page_size'] ?? 10;
        $db->orderBy ("idx","desc");
        return $db->get('chat_log', [($page-1)*$page_size,$page_size], ['idx']);
    }

    //要改成数据库的形式  重新写
    protected static function getPersonalLogfile1111111111111($db, $path, $param = [])
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
                $aryHis = json_decode($orgHis, 1);
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

    protected static function delLog($db)
    {
        $timess = (int)(microtime(true)*1000*10000*10000) - (7200*1000*10000*10000);
        $db->where (' idx < ? ', [$timess]);
        $db->delete('chat_log');
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
                    $addVal['uuid'] = $addId;
                    $addVal = json_encode($addVal,JSON_UNESCAPED_UNICODE);
                }
                # 将id插入数据库
                if(!self::insert($db, [
                    'idx' => $timeIdx,
                    'type' => $arr['type'],
                    'to_id' => $arr['toId'],
                    'room_id' => $arr['roomId'],
                    'user_id' => $arr['user_id'],
                    'user_map' => Users::getUserMap($arr['toId'], $arr['user_id']),
                ])){
                    continue;
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
            while ($needDelnum){
                $v = array_shift($files);
                self::delOneFile($db, $v); # 删除文件
//                    if(Storage::disk('home')->exists($v)){
//                        $arr = json_decode(Storage::disk('home')->get($v), 1);
//                        Storage::disk('home')->delete($v);
//                        # 删数据库
//                        PersonalLog::deleteRaw([
//                            'type'=> $arr['type'],
//                            'file'=> $v
//                        ]);
//
//                        # 通知这两个人删除消息
//                        app('swoole')->sendUser($arr['user_id'], 24, [
//                            'type' => $arr['type'],
//                            'id' => $arr['uuid'],
//                            'toId' => $arr['toId']
//                        ]);
//                        app('swoole')->sendUser($arr['toId'], 24, [
//                            'type' => $arr['type'],
//                            'id' => $arr['uuid'],
//                            'toId' => $arr['toId']
//                        ]);
//                    }
                $needDelnum --;
            }
        }
        return true;
    }

    protected static function deleteRaw($db, $param = [])
    {
        if(isset($param['file'])){
            $idxs = explode('/', $param['file']);
            $param['idx'] = (int)array_pop($idxs);
            unset($param['file']);
        }

        foreach ($param as $k=>$v){
            $db->where($k, $v);
        }
        if(count($param))
            $db->delete('chat_log');
    }

    protected static function insert($db, $data)
    {
        return $db->insert('chat_log', $data);
    }
}