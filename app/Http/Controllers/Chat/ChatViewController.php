<?php

namespace App\Http\Controllers\Chat;

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
//        $FRONT_LOGO = '';
        $FRONT_LOGO = $AdSource->getOneSource('color_217X160');
        $BACK_LOGO = $AdSource->getOneSource('color_311X105');
        Session::put('BACK_LOGO', $BACK_LOGO);
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
        $plan_send_game = explode(",",$baseSetting->plan_send_game);
        $planSendGamePK10 = 0;        //北京pk10
        $planSendGameCQSSC = 0;         //重庆时时彩
        $planSendGameJSKS = 0;         //江苏快三
        $planSendGameXYFT = 0;         //幸运飞艇
        $planSendGamePCDD = 0;         //PC蛋蛋
        $planSendGameMSSC = 0;         //秒速赛车
        $planSendGameKSSC = 0;         //快速赛车
        $planSendGameKSFT = 0;         //快速飞艇
        $planSendGameKSSSC = 0;         //快速时时彩

        foreach ($plan_send_game as& $key){
            switch ($key){
                case 50:            //北京pk10
                    $planSendGamePK10 = 1;
                    break;
                case 1:             //重庆时时彩
                    $planSendGameCQSSC = 1;
                    break;
                case 10:             //江苏快三
                    $planSendGameJSKS = 1;
                    break;
                case 55:             //幸运飞艇
                    $planSendGameXYFT = 1;
                    break;
                case 66:             //PC蛋蛋
                    $planSendGamePCDD = 1;
                    break;
                case 80:             //秒速赛车
                    $planSendGameMSSC = 1;
                    break;
                case 801:             //快速赛车
                    $planSendGameKSSC = 1;
                    break;
                case 802:             //快速飞艇
                    $planSendGameKSFT = 1;
                    break;
                case 803:             //快速时时彩
                    $planSendGameKSSSC = 1;
                    break;

            }
        }
        return view('chat.baseManage')
            ->with('base',$baseSetting)
            ->with('PK10',$planSendGamePK10)
            ->with('CQSSC',$planSendGameCQSSC)
            ->with('JSKS',$planSendGameJSKS)
            ->with('XYFT',$planSendGameXYFT)
            ->with('PCDD',$planSendGamePCDD)
            ->with('MSSC',$planSendGameMSSC)
            ->with('KSSC',$planSendGameKSSC)
            ->with('KSFT',$planSendGameKSFT)
            ->with('KSSSC',$planSendGameKSSSC);
    }
}
