<?php

namespace App\Http\Controllers\Chat;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;

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
        $unauto_count = $request->input('auto_count')=="on"?1:0;
        switch ($level){
            case 0;
                return response()->json([
                    'status'=>false,
                    'msg'=>'不可选择游客角色'
                ]);
                break;
            case 99:    //管理员
                $chat_role = 3;
                break;
            default:    //会员
                $chat_role = 2;
                break;
        }

        DB::table('chat_users')->where('users_id',$userid)->update([
            'chat_role'=>$chat_role,
            'level'=>$level,
            'isnot_auto_count'=>$unauto_count,      //是否不是自动计算层级，如果此栏位1则登陆不自动计算层级
            'updated_at'=>date("Y-m-d H:i:s",time())
        ]);
        return response()->json(['status'=>true],200);
    }

    //禁言聊天室用户
    public function unSpeak($data)
    {
        $data = explode("&",$data);

        DB::table('chat_users')->where('users_id',$data[0])->update([
            'chat_status'=>$data[1]=="un"?1:0,
            'updated_at'=>date("Y-m-d H:i:s",time())
        ]);
        return response()->json(['status'=>true],200);
    }
}
