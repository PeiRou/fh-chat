<?php

namespace App\Http\Controllers\Chat;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Home\CaptchaController;

class ChatViewController extends Controller
{
    //管理登录页面
    public function AdminLogin()
    {
        $captcha = CaptchaController::makeCaptcha();
        return view('chat.O_adminLogin',compact('captcha'));
    }
    //控制台
    public function Dash()
    {
        return view('chat.dash');
    }
    //会员管理
    public function userManage()
    {
        return view('chat.userManage');
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
    public function hongbaoDt()
    {
        return view('chat.hongbaoDt');
    }
    //平台配置
    public function baseManage()
    {
        $baseSetting = DB::table('chat_base')->where('chat_base_idx',1)->first();
        $plan_send_game = explode(",",$baseSetting->plan_send_game);
        $planSendGamePK10 = 0;        //北京pk10
        $planSendGameCQSSC = 0;         //重庆时时彩
        foreach ($plan_send_game as& $key){
            switch ($key){
                case 50:            //北京pk10
                    $planSendGamePK10 = 1;
                    break;
                case 1:             //重庆时时彩
                    $planSendGameCQSSC = 1;
                    break;
            }
        }
        return view('chat.baseManage')->with('base',$baseSetting)->with('PK10',$planSendGamePK10)->with('CQSSC',$planSendGameCQSSC);
    }
}
