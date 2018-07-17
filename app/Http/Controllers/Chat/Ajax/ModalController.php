<?php

namespace App\Http\Controllers\Chat\Ajax;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class ModalController extends Controller
{
    //显示修改聊天室用户信息-弹窗表单
    public function editUserLevel($data)
    {
        $data = explode("&",$data);
        $roles = DB::table('chat_roles')->select('name','level')->orderBy('level','asc')->get();
        return view('modal.editUserLevel')->with('id',$data[0])->with('roles',$roles)->with('level',$data[1])->with('unauto',$data[2]);
    }
    //显示修改用户角色层级-弹窗表单
    public function editRoleInfo($id)
    {
        $role = DB::table('chat_roles')->select('id', 'name', 'type', 'level', 'bg_color1 as bg1', 'bg_color2 as bg2', 'font_color as font', 'length', 'permission', 'description', 'created_at', 'updated_at')->where('id',$id)->first();
        if($role==null){
            $role = new \stdClass();
            $role->id = "";
            $role->name = "";
            $role->level = 1;
            $role->type = "";
            $role->bg1 = "";
            $role->bg2 = "";
            $role->font = "";
            $role->length = "";
            $role->permission = "";
            $role->description = "";
        }
        $level = DB::table('chat_level')->select('id','levelname')->get();    //用户层级表
        $permission = explode(',',$role->permission);                //权限
        return view('modal.editRoleInfo')->with('role',$role)->with('role_level',$level)->with('permiss',$permission);
    }
    //显示修改房间信息-弹窗表单
    public function editRoomLimit($data)
    {
        $data = explode("&",$data);
        return view('modal.editRoomLimit')->with('id',$data[0])->with('name',$data[1])->with('rech',$data[2])->with('bet',$data[3]);
    }
    //显示修改聊天室公告-弹窗表单
    public function editNoteInfo($data)
    {
        $data = explode("&",$data);
        $note = DB::table('chat_note')->select('room_id','content')->where('chat_note_idx',$data[0])->first();
        if($note==null){
            $note = new \stdClass();
            $note->content = "";
            $roomid = $data[2];
        }else
            $roomid = $note->room_id;
        return view('modal.editNoteInfo')->with('id',$data[0])->with('name',$data[1])->with('roomid',$roomid)->with('note',$note);
    }
    //显示修改聊天室管理员-弹窗表单
    public function editAdminInfo($data)
    {
        $data = explode("&",$data);
        $admin = DB::table('chat_sa')->select('sa_id')->where('sa_id',$data[0])->first();
        if($admin==null){
            $data[1] = "";           //帐号
            $data[2] = "";           //呢称
        }
        return view('modal.editAdminInfo')->with('id',$data[0])->with('account',$data[1])->with('nickname',$data[2]);
    }
    //显示修改违禁词-弹窗表单
    public function editForbidInfo($data)
    {
        $data = explode("&",$data);
        $regex = DB::table('chat_regex')->select('regex')->where('chat_regex_idx',$data[0])->first();
        if($regex==null)
            $data[2] = "";           //违禁词
        else
            $data[2] = $regex->regex;
        return view('modal.editForbidInfo')->with('id',$data[0])->with('roomid',$data[1])->with('regex',$data[2]);
    }
    //显示发红包-弹窗表单
    public function addHongbao()
    {
        $room = DB::table('chat_room')->select('room_id as roomid','room_name')->get();
        return view('modal.addHongbao')->with('room',$room);
    }
    //显示手动发送计画任务-弹窗表单
    public function manualPlan()
    {
        return view('modal.manualPlan');
    }
    //显示修改层级信息-弹窗表单
    public function editLevelInfo($id){
        $level = DB::table('chat_level')->select('id','recharge_min','bet_min')->where('id','=',$id)->first();
        return view('modal.editLevelInfo')->with('id',$level->id)->with('recharge_min',$level->recharge_min)->with('bet_min',$level->bet_min);
    }
}
