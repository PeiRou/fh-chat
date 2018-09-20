<?php

namespace App\Http\Controllers\Chat;

use App\Recharges;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;

class AjaxStatusController extends Controller
{
    //在线人数状态
    public function online()
    {
        Redis::select(3);
        $key = 'roomL1';
        //还没检查在线状态的人数
        $onlineNum =  Redis::SCARD($key);
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
        if(!(Redis::get('usr:'.md5($userid))))
            $status = false;
        else
            $status = true;

        return response()->json([
            'status' => $status
        ]);
    }
    public function getHisInfo(Request $request){
        $value = $request->get('uuid');
        $orgHis = Storage::disk('chathis')->get($value);
        return $orgHis;
    }
    public function delHisInfo(Request $request){
        $value = $request->get('uuid');
        $orgHis = Storage::disk('chathis')->delete($value);
        return $orgHis;
    }
}
