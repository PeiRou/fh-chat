<?php

namespace App\Http\Controllers\Chat;

use App\Swoole;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;

class AjaxStatusController extends Controller
{
    //在线人数状态
    public function online()
    {
        $files = Storage::disk('chatusrfd')->files();
        $onlineNum = 0;
        $onlineYKNum = 0;
        foreach ($files as $key){
            if(Storage::disk('chatusrfd')->exists($key)){
                $usr = @Storage::disk('chatusrfd')->get($key);
                if(!empty($usr)){
                    $usr = (array)json_decode($usr);
                    if(isset($usr['level']) && $usr['level']>0)
                        $onlineNum++;
                    else
                        $onlineYKNum++;
                }
            }
        }
        $redis = Redis::connection();
        $redis->select(8);           //后台
        $keys = $redis->keys('us_'.ChatAccountController::TOKENPREFIX.'*');
        $onlineAdminCount = count($keys);
        foreach ($keys as $v){
            if($v == 'us_'.ChatAccountController::TOKENPREFIX.md5(1) || $v == 'us_'.ChatAccountController::TOKENPREFIX.md5(request()->adminInfo->sa_id))
                $onlineAdminCount --;
        }
        return response()->json([
            'status' => true,
            'count' => $onlineNum,
            'yk_count' => $onlineYKNum,
            'onlineAdmin' => $onlineAdminCount,
        ]);
    }
    //检查此人在线状态
    public function getOnlineStatus(Request $request)
    {
        $userid = $request->get('id');

        //检查在线状态
        if(Storage::disk('chatusr')->exists('chatusr:'.md5($userid)))
            $status = true;
        else
            $status = false;

        return response()->json([
            'status' => $status
        ]);
    }
    //从uuid查会员资讯
    public function getHisInfo(Request $request){
        $res = '';
        try{
            $value = $request->get('uuid');
            $orgHis = Storage::disk('chathis')->get($value);
            $k = (array)json_decode($orgHis);
            $k = isset($k['k'])?$k['k']:'';
            if(empty($k))
                return '';
            $fd = @Storage::disk('chatusr')->get('chatusr:'.$k);
            $res = @Storage::disk('chatusrfd')->get('chatusrfd:'.$fd);
        }catch (\Exception $e){
        }
        return $res;
    }
    public function setInfo(Request $request){
        $roomid = $request->input('room');
        $type = $request->input('type');
        $else = $request->input('else');
        \Log::info('setInfo-type:'.$type);
        if($type=='getInfo'){
            $fd = @Storage::disk('chatusr')->get('chatusr:'.$else);
            $res = @Storage::disk('chatusrfd')->get('chatusrfd:'.$fd);
            $res = (array)json_decode($res);
            $res['fd'] = $fd;
            return json_encode($res);
        }else if(!empty($else)){
            $res = @Storage::disk('chatusrfd')->get('chatusrfd:'.$else);
            return $res;
        }
        try{
            $swoole = new Swoole();
            $swoole->swooletest($type,$roomid,$request->all());
        }catch (\Exception $e){
            \Log::info(__CLASS__ . '->' . __FUNCTION__ . ' Line:' . $e->getLine() . ' ' . $e->getMessage());
        }
        return 'ok';
    }
}
