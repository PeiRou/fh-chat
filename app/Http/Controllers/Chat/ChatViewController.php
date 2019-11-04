<?php

namespace App\Http\Controllers\Chat;

use App\Model\ChatSendConfig;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Home\CaptchaController;
use Illuminate\Support\Facades\Session;
use SameClass\Config\AdSource\AdSource;

class ChatViewController extends Controller
{
    //管理登录页面
    public function AdminLogin()
    {
        $captcha = CaptchaController::makeCaptcha();
        $AdSource = new AdSource();
        $FRONT_LOGO = $AdSource->getOneSource('color_217X160');
        $BACK_LOGO = $AdSource->getOneSource('color_311X105');
        $ISROOMS = $AdSource->getOneSource('chatType');
//        $ISROOMS = is_int($ISROOMS)?$ISROOMS:0;
        $ISROOMS = $ISROOMS == '1' ? (int)$ISROOMS : 0;
        Session::put('BACK_LOGO', $BACK_LOGO);
        Session::put('ISROOMS', $ISROOMS);
        return view('chat.O_adminLogin',compact('captcha','FRONT_LOGO'));
    }
    //控制台
    public function Dash(Request $request)
    {
        $accountInfo = DB::table('chat_sa')->where('sa_id', $request->sa_id)->first();
        return view('chat.dash', compact('accountInfo'));
    }
    //会员管理
    public function userManage()
    {
        return view('chat.userManage');
    }
    //等级管理
    public function levelManage(){
        return view('chat.levelManage');
    }
    //角色管理
    public function roleManage()
    {
        return view('chat.roleManage');
    }
    //房间管理
    public function roomManage()
    {
        return view('chat.roomManage');
    }
    //公告管理
    public function noteManage()
    {
        return view('chat.noteManage');
    }
    //管理员管理
    public function adminManage()
    {
        return view('chat.adminManage');
    }
    //违禁词管理
    public function forbidManage()
    {
        return view('chat.forbidManage');
    }
    //红包管理
    public function hongbaoManage()
    {
        return view('chat.hongbaoManage');
    }
    //红包明细
    public function hongbaoDt(Request $request)
    {
        $id = $request->input('id');
        $start = $request->input('start');
        $end = $request->input('end');
        return view('chat.hongbaoDt')->with('id',$id)->with('start',$start)->with('end',$end);
    }
    //平台配置
    public function baseManage()
    {
        $baseSetting = DB::table('chat_base')->where('chat_base_idx',1)->first();

        $chat_send_config = ChatSendConfig::getConfig(['room_id' => 0]);
        return view('chat.baseManage')
            ->with('base',$baseSetting)
            ->with('chat_send_config',$chat_send_config)
            ->with('ISROOMS',Session::get('ISROOMS'));
    }
}
