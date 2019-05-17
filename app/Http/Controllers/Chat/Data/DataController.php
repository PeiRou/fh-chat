<?php

namespace App\Http\Controllers\Chat\Data;

use App\Http\Controllers\Chat\ChatAccountController;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Yajra\DataTables\Contracts\DataTable;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DataController extends Controller
{
    //会员管理-表格数据
    public function userManage(Request $request)
    {
        $account = $request->get('account');        //用户名/呢称
        $level = $request->get('role');             //角色
        $statusOnline = $request->get('statusOnline');     //在线状态
        $chat_status = $request->get('status');     //状态
        $login_ip = $request->get('ip');            //登陆ip
        $parram['account'] = $account;
        $parram['statusOnline'] = $statusOnline;

        //用户表join角色表
        $users = DB::table('chat_users')
            ->join('chat_roles', 'chat_users.level', '=', 'chat_roles.level')
            ->select('users_id','username','nickname','login_ip','chat_roles.name as levelname','chat_status','recharge','bet','chat_users.level','chat_users.isnot_auto_count as unauto')
            ->where('chat_role','>=',2)
            ->where(function ($query) use($parram){        //用户名/呢称
                if(isset($parram['account']) && $parram['account']){
                    $query->where('username','=',$parram['account'])
                        ->orWhere('nickname','=',$parram['account']);
                }else if($parram['statusOnline']!=""){
                    $files = Storage::disk('chatusrfd')->files();
                    $manyUsers = array();
                    foreach ($files as $usrKey){
                        if(Storage::disk('chatusrfd')->exists($usrKey)){
                            $arrayUsr = @(array)json_decode(Storage::disk('chatusrfd')->get($usrKey));              //删除用户在文件的历史数据
                            $manyUsers[] = $arrayUsr['userId'];
                        }
                    }
                    if(count($manyUsers)>0){
                        switch ($parram['statusOnline']){
                            case 0:
                                $query->whereNotIn('users_id',$manyUsers);
                                break;
                            case 1:
                                $query->whereIn('users_id',$manyUsers);
                                break;
                        }
                    }
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
            })
            ->orderBy('users_id','DESC')
            ->get();
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
        $users = DB::table('chat_room')->whereIn('roomtype',[1,2])->get();
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
    public function adminManage(Request $request)
    {
        $users = DB::table('chat_sa')->where('account','<>',ChatAccountController::ADMIN)->get();
        return DataTables::of($users)
            ->editColumn('control',function ($data) use($request){
                $str = "<ul class='control-menu'>";
                if($request->user->account == 'admin' || $request->user->account == ChatAccountController::ADMIN || $data->account == $request->user->account){
                    $str .= "<li onclick='updAdminInfo(".$data->sa_id.",\"".$data->account."\",\"".$data->name."\")'>修改</li>";
                    $str .= "<li class='' onclick='del(".$data->sa_id.",\"delAdminInfo\")'>删除</li>";
                }
                if($data->account !== 'admin')
                    $str .= "<li onclick='google(".$data->sa_id.")'> Google双重验证</li>";
                $str .= "</ul>";
                return $str;
            })
            ->rawColumns(['control'])
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
    public function hongbaoManage(Request $request)
    {
        $starttime = $request->get('timeStart');
        $endtime = $request->get('timeEnd');
        $id = $request->get('id');
        $status = $request->get('status');
        $users = DB::table('chat_hongbao')
            ->select('chat_hongbao.*','room_name')
            ->join('chat_room', 'chat_room.room_id', '=', 'chat_hongbao.room_id')
            ->where(function ($query) use($starttime){        //发送时间(开始)
                if(isset($starttime) && $starttime){
                    $query->where('chat_hongbao.posttime','>=',date("Y-m-d 00:00:00",strtotime($starttime)));
                }
            })
            ->where(function ($query) use($endtime){        //发送时间(结束)
                if(isset($endtime) && $endtime){
                    $query->where('chat_hongbao.posttime','<=',date("Y-m-d 23:59:59",strtotime($endtime)));
                }
            })
            ->where(function ($query) use($id){        //红包id
                if(isset($id) && $id>0){
                    $query->where('chat_hongbao.chat_hongbao_idx',$id);
                }
            })
            ->where(function ($query) use($status){        //红包id
                if(isset($status) && $status>0){
                    $query->where('chat_hongbao.hongbao_status',$status);
                }
            })->orderBy('chat_hongbao_idx','desc')
            ->get();
        return DataTables::of($users)
            ->make(true);
    }
    //红包明细-表格数据
    public function hongbaoDt(Request $request)
    {
        $starttime = $request->get('timeStart');
        $endtime = $request->get('timeEnd');
        $id = $request->get('id');
        $or_id = $request->get('or_id');
        $account = $request->get('account');
        $status = $request->get('status');
        $min_amount = $request->get('min_amount');
        $max_amount = $request->get('max_amount');

        $users = DB::table('chat_hongbao_dt')
            ->where(function ($query) use($starttime){        //发送时间(开始)
                if(isset($starttime) && $starttime){
                    $query->where('getdatetimes','>=',strtotime($starttime.' 00:00:00'));
                }
            })
            ->where(function ($query) use($endtime){        //发送时间(结束)
                if(isset($endtime) && $endtime){
                    $query->where('getdatetimes','<=',strtotime($endtime.' 23:59:59'));
                }
            })
            ->where(function ($query) use($id){        //红包id
                if(isset($id) && $id>0){
                    $query->where('hongbao_idx',$id);
                }
            })
            ->where(function ($query) use($or_id){        //订单号
                if(isset($or_id) && !empty($or_id)){
                    $query->where('hongbao_dt_orderno',$or_id);
                }
            })
            ->where(function ($query) use($account){        //用户名
                if(isset($account) && !empty($account)){
                    $query->where('username',$account);
                }
            })
            ->where(function ($query) use($status){        //红包状态
                if(isset($status) && $status>0){
                    $query->where('hongbao_status',$status);
                }
            })
            ->where(function ($query) use($min_amount){        //最小金额
                if(isset($min_amount) && $min_amount){
                    $query->where('amount','>=',$min_amount);
                }
            })
            ->where(function ($query) use($max_amount){        //最大金额
                if(isset($max_amount) && $max_amount){
                    $query->where('amount',"<=",$max_amount);
                }
            })->get();
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

    //层级管理-表格数据
    public function levelManage(){
        //角色表
        $users = DB::table('chat_level')
            ->select('id','levelname', 'recharge_min', 'bet_min', 'created_at', 'updated_at')
            ->orderBy('id','asc')
            ->get();
        return DataTables::of($users)
            ->editColumn('length',function ($users){
                $length = empty($users->length)?'不限制':$users->length;
                return $length;
            })
            ->make(true);
    }
}
