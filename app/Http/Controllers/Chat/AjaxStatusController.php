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
        $keys = Storage::disk('chatusr')->files();
        //还没检查在线状态的人数
        $onlineNum =  count($keys);
        return response()->json([
            'status' => true,
            'count' => $onlineNum
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
    public function getHisInfo(Request $request){
        $value = $request->get('uuid');
        $orgHis = Storage::disk('chathis')->get($value);
        return $orgHis;
    }
    public function delHisInfo(Request $request){
        $value = $request->get('uuid');
        if(Storage::disk('chathis')->exists($value))
            Storage::disk('chathis')->delete($value);
        return 'ok';
    }
}
