<?php

namespace App\Http\Controllers\Chat;

use App\Recharges;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Redis;

class AjaxStatusController extends Controller
{
    //在线人数状态
    public function online()
    {
        Redis::select(1);
        $key = 'online';
        //还没检查在线状态的人数
        $onlineNum =  Redis::LLEN($key);
        //检查在线状态重新计算人数
        for($ii=0;$ii<$onlineNum;$ii++){
            $checkMember = Redis::LRANGE($key,$ii,$ii);
            if(!(Redis::get($checkMember[0]))){
                Redis::LREM($key,0,$checkMember[0]);
                $onlineNum--;
            }
        }
        return response()->json([
            'status' => true,
            'count' => $onlineNum
        ]);
    }
    //检查此人在线状态
    public function getOnlineStatus(Request $request)
    {
        Redis::select(1);
        $userid = $request->get('id');

        //检查在线状态
        if(!(Redis::get('user:'.md5($userid))))
            $status = false;
        else
            $status = true;

        return response()->json([
            'status' => $status
        ]);
    }
}
