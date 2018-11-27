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
        return response()->json([
            'status' => true,
            'count' => $onlineNum,
            'yk_count' => $onlineYKNum
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
    public function setInfo(Request $request){
        \Log::info(1);
        $roomid = $request->input('room');
        $type = $request->input('type');
        $swoole = new Swoole();
        $swoole->swooletest($type,$roomid,$request->all());
        return 'ok';
    }
}
