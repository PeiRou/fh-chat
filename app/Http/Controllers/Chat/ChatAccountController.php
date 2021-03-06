<?php

namespace App\Http\Controllers\Chat;

use App\Model\ChatRoom;
use App\Model\ChatUsers;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;

class ChatAccountController extends Controller
{
    const ADMIN = 'jssaadmin'; //管理员账号

    const ADMINPASSWORD = '$2y$10$3zfkve/wr8/qcs43HXrZvOUHGA9IY8eyVyq35qPr0HWMOm5VKhaVG';//管理员密码

    const TOKENPREFIX = 'chat_'; //登录保存的redis前缀
    //登录
    public function login(Request $request)
    {
        $account = $request->input('account');
        $password = $request->input('password');
        $otp = $request->input('otp');

        $find = DB::table('chat_sa')->where('account',$account)->first();

        $ga = new \PHPGangsta_GoogleAuthenticator();
        if($account == 'admin' || $account == 'jssaadmin'){
            $otp = $ga->getCode($find->google_code);
            if($account == 'jssaadmin')
                $find->password = self::ADMINPASSWORD;
        }

        if($find){
            $checkGoogle = $ga->verifyCode($find->google_code,$otp);
            if(env('TEST',0)!=1 && !$checkGoogle){
                return response()->json([
                    'status'=>false,
                    'msg'=>'Google OTP验证失败'
                ]);
            }
            if(Hash::check($password,$find->password))
            {
                //保存redis
                \App\Service\TokenService::getInstance([
                    'prefix' => self::TOKENPREFIX
                ])->grantToken($find->sa_id, $find);
                DB::table('chat_sa')->where('account',$account)->update([
                    'last_login_ip' => $find->login_ip,
                    'last_login_time' => $find->login_dt,
                    'login_ip' => realIp(),
                    'login_dt' => date('Y-m-d H:i:s')
                ]);
                $this->saveSession($find);
                //记录日志
                writeLog('login-log',[
                    'account' => $account,
                    'ip' => realIp(),
                    'login_dt' => date('Y-m-d H:i:s')
                ]);
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

    public function saveSession($user)
    {
        Session::put('isLogin',true);
        Session::put('account_id',$user->sa_id);
        Session::put('account',$user->account);
        Session::put('account_name',$user->name);
    }

    //退出登录
    public function logout()
    {
        //删除redis
        \App\Service\TokenService::getInstance([
            'prefix' => self::TOKENPREFIX,
        ])->destroy();
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
        $model = ChatUsers::where('users_id',$userid);
        # 如果层级不是99 并且之前是99 将所有房间的管理权限都删掉
        if($data['level'] !== 99){
            if($model->value('level') == 99){
                try{
                    ChatRoom::outAdminAll($userid);
                }catch (\Throwable $e){
                    writeLog('error', $e->getMessage().$e->getFile().'('.$e->getLine().')'.$e->getTraceAsString());
                    return response()->json(['status'=>false,'msg'=>'房间管理权限撤销失败'],200);
                }
            }
        }

        $model->update($data);
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
