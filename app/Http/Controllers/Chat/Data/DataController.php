<?php

namespace App\Http\Controllers\Chat\Data;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Yajra\DataTables\Contracts\DataTable;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\DB;

class DataController extends Controller
{
    //会员管理-表格数据
    public function userManage(Request $request)
    {
        $account = $request->get('account');        //用户名/呢称
        $level = $request->get('role');             //角色
        $chat_status = $request->get('status');     //状态
        $login_ip = $request->get('ip');            //登陆ip

        //用户表join角色表
        $users = DB::table('chat_users')
            ->join('chat_roles', 'chat_users.level', '=', 'chat_roles.level')
            ->select('users_id','username','nickname','login_ip','chat_roles.name as levelname','chat_status','recharge','bet','chat_users.level','chat_users.isnot_auto_count as unauto')
            ->where(function ($query) use($account){        //用户名/呢称
                if(isset($account) && $account){
                    $query->where('username','=',$account)
                        ->orWhere('nickname','=',$account);
                }
            })
            ->where(function ($query) use ($level){         //角色
                if(isset($level) && $level!=""){
                    $query->where("chat_users.level",'=',$level);
                }
            })
            ->where(function ($query) use ($chat_status){   //状态
                if(isset($chat_status) && $chat_status!=""){
                    $query->where("chat_status",'=',$chat_status);
                }
            })
            ->where(function ($query) use ($login_ip){      //登陆ip
                if(isset($login_ip) && $login_ip){
                    $query->where("login_ip",'=',$login_ip);
                }
            })->get();
        return DataTables::of($users)
            ->editColumn('nickname',function ($users){
                $nickname = empty($users->nickname)?substr($users->username,0,2).'******'.substr($users->username,-2,3):$users->nickname;
                return $nickname;
            })
            ->make(true);
    }
    //角色管理-表格数据
    public function roleManage()
    {
        //角色表
        $users = DB::table('chat_roles')
            ->select('id','level', 'name', 'type', 'bg_color1 as bg1', 'bg_color2 as bg2', 'font_color as font', 'length', 'permission', 'description')
            ->orderBy('level','asc')
            ->get();
        return DataTables::of($users)
            ->editColumn('length',function ($users){
                $length = empty($users->length)?'不限制':$users->length;
                return $length;
            })
            ->make(true);
    }
    //房间管理-表格数据
    public function roomManage()
    {
        $users = DB::table('chat_room')->get();
        return DataTables::of($users)
            ->make(true);
    }
    //公告管理-表格数据
    public function noteManage()
    {
        $users = DB::table('chat_note')
            ->select('chat_note.*','room_name')
            ->join('chat_room', 'chat_room.room_id', '=', 'chat_note.room_id')->get();
        return DataTables::of($users)
            ->make(true);
    }
    //管理员管理-表格数据
    public function adminManage()
    {
        $users = DB::table('chat_sa')->get();
        return DataTables::of($users)
            ->make(true);
    }
    //违禁词管理-表格数据
    public function forbidManage()
    {
        $users = DB::table('chat_regex')
            ->select('chat_regex.*','room_name')
            ->join('chat_room', 'chat_room.room_id', '=', 'chat_regex.room_id')->get();
        return DataTables::of($users)
            ->make(true);
    }
    //红包管理-表格数据
    public function hongbaoManage()
    {
        $users = DB::table('chat_hongbao')
            ->select('chat_hongbao.*','room_name')
            ->join('chat_room', 'chat_room.room_id', '=', 'chat_hongbao.room_id')->get();
        return DataTables::of($users)
            ->make(true);
    }
    //红包明细-表格数据
    public function hongbaoDt()
    {
        $users = DB::table('chat_hongbao_dt')->get();
        return DataTables::of($users)
            ->make(true);
    }
    //平台配置-表格数据
    public function baseManage()
    {
        $users = DB::table('chat_roles')->get();
        return DataTables::of($users)
            ->make(true);
    }
}
