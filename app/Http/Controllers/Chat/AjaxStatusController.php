<?php

namespace App\Http\Controllers\Chat;

use App\Recharges;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;

class AjaxStatusController extends Controller
{

    public function __construct()
    {
        $this->redis = new \Redis();
        $this->redis->connect(env('REDIS_HOST','127.0.0.0.1'), env('REDIS_PORT',6379));
        $this->redis->select(1);
    }

    //在线人数状态
    public function online()
    {
        $this->redis->select(1);
        $keys = $this->redis->keys('chatusr:'.'*');
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
        $this->redis->select(1);
        $userid = $request->get('id');

        //检查在线状态
        if($this->redis->exists('chatusr:'.md5($userid)))
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
            $orgHis = Storage::disk('chathis')->delete($value);
        return 'ok';
    }
}
