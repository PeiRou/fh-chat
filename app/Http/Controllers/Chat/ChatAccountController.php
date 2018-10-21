<?php

namespace App\Http\Controllers\Chat;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;

class ChatAccountController extends Controller
{
    //登录
    public function login(Request $request)
    {
        $account = $request->input('account');
        $password = $request->input('password');
        $captcha = $request->input('captcha');
        $sessCaptcha = Session::get('captcha');
        if($captcha!=$sessCaptcha)
            return response()->json([
                    'status'=>false,
                    'msg'=>'验证码错误'
                ]);
        $find = DB::table('chat_sa')->where('account',$account)->first();
        if($find){
            if(Hash::check($password,$find->password))
            {
                Session::put('isLogin',true);
                Session::put('account_id',$find->sa_id);
                Session::put('account',$find->account);
                Session::put('account_name',$find->name);
                return response()->json([
                    'status'=>true,
                    'msg'=>'登录成功，正在进入'
                ]);
            } else {
                return response()->json([
                    'status'=>false,
                    'msg'=>'账号密码错误，请重试'
                ]);
            }
        } else {
            return response()->json([
                'status'=>false,
                'msg'=>'账号不存在，请核实'
            ]);
        }
    }

    //退出登录
    public function logout()
    {
        Session::flush();
        return response()->json([
            'status'=>true
        ]);
    }

    //修改聊天室用户信息
    public function updUserInfo(Request $request){
        $userid = $request->input('id');
        $level = $request->input('level');
        $nickname = $request->input('nickname');
        $unauto_count = $request->input('auto_count')=="on"?1:0;
        switch ($level){
            case 0;
                return response()->json([
                    'status'=>false,
                    'msg'=>'不可选择游客角色'
                ]);
                break;
            case 98;
                return response()->json([
                    'status'=>false,
                    'msg'=>'不可选择计划消息角色'
                ]);
                break;
            case 99:    //管理员
                $chat_role = 3;
                break;
            default:    //会员
                $chat_role = 2;
                break;
        }
        if(!empty($nickname))
            $data['nickname'] = $nickname;
        $data['chat_role'] = $chat_role;
        $data['level'] = $level;
        $data['isnot_auto_count'] = $unauto_count;       //是否不是自动计算层级，如果此栏位1则登陆不自动计算层级
        $data['updated_at'] = date("Y-m-d H:i:s",time());
        DB::table('chat_users')->where('users_id',$userid)->update($data);
        return response()->json(['status'=>true],200);
    }

    //禁言聊天室用户
    public function unSpeak($data)
    {
        $data = explode("&",$data);
        $userId = $data[0];
        $keyUser = 'chatusr:'.md5($userId);
        $user = Storage::disk('chatusr')->exists($keyUser)?Storage::disk('chatusr')->get($keyUser):'';
        if(!empty($user)){
            $keyUserFd = 'chatusrfd:'.$user;
            $userFd = Storage::disk('chatusrfd')->exists($keyUserFd)?Storage::disk('chatusrfd')->get($keyUserFd):'';
            if(!empty($userFd)){
                $userFd = (array)json_decode($userFd);
                $userFd['noSpeak'] = $data[1]=="un"?1:0;
                Storage::disk('chatusrfd')->put($keyUserFd,json_encode($userFd,JSON_UNESCAPED_UNICODE));
            }
        }
        DB::table('chat_users')->where('users_id',$userId)->update([
            'chat_status'=>$data[1]=="un"?1:0,
            'updated_at'=>date("Y-m-d H:i:s",time())
        ]);
        return response()->json(['status'=>true],200);
    }
}
