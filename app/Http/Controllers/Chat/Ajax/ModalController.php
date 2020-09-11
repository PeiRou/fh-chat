<?php

namespace App\Http\Controllers\Chat\Ajax;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use SameClass\Config\LotteryGames\Games;

class ModalController extends Controller
{
    protected $lottery = [];
    protected $gameIdtoType = [];
    protected $cnLotteryType = [];

    private $roomType = array(
        '1'=>'平台聊天室',
        '2'=>'多人聊天室',
        '4'=>'客服.快速引导房',
//        '3'=>'1对1'
    );

    //更新计划任务彩种
    private function setPlanlottery(){
        $Games = new Games();
        $this->lottery = $Games->setlottery(['gameId','lottery'],'havePlan',true);
        $this->gameIdtoType = $Games->getGameData('gameIdtoType');
        $this->cnLotteryType = $Games->cnLotteryType;
    }
    //取得计划任务彩种
    public function getLottery(){
        $this->setPlanlottery();
        return json_encode($this->lottery);
    }
    //获取房间类型
    public function getRoomType(){
        return json_encode($this->roomType);
    }
    //取得所有房间ID跟名称
    public function getAllRooms($type='json'){
        $res = DB::table('chat_room')->select('room_id','room_name')->whereNotIn('room_id',[3])->orderby('room_id')->get();
        if($type=='json')
            return json_encode($res);
        else
            return $res;

    }
    //显示修改聊天室用户信息-弹窗表单
    public function editUserLevel($data)
    {
        $data = explode("&",$data);
        $chatUser = DB::table('chat_users')->select('nickname')->where('users_id',$data[0])->first();
        $roles = DB::table('chat_roles')->select('name','level')->orderBy('level','asc')->get();
        return view('modal.editUserLevel')->with('id',$data[0])->with('roles',$roles)->with('level',$data[1])->with('unauto',$data[2])->with('nickname',$chatUser->nickname);
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
        $games = isset($data[5])?array_flip(explode(",",$data[5])):array();
        $roomInfo = DB::table('chat_room')->where('room_id', @$data[0])->first();
        $pushBetGames = !empty($roomInfo)?explode(',',$roomInfo->pushBetGame):[];
        $openGames = DB::table('game')->where('status',1)->get();
        $this->setPlanlottery();
        return view('modal.editRoomLimit')->with('id',@$data[0])->with('name',@$data[1])->with('roomType',@$data[2])->with('rech',@$data[3])->with('bet',@$data[4])->with('roomInfo', $roomInfo)
            ->with('games',$games)
            ->with('lotterys',$this->lottery)
            ->with('gameIdtoType',$this->gameIdtoType)
            ->with('cnLotteryType',$this->cnLotteryType)
            ->with('roomTypes',$this->roomType)
            ->with('openGames',$openGames)
            ->with('pushBetGames',$pushBetGames);
    }
    //显示修改聊天室公告-弹窗表单
    public function editNoteInfo($data)
    {
        $data = explode("&",$data);
        $note = DB::table('chat_note')->select('room_id','rooms','content')->where('chat_note_idx',$data[0])->first();
        if($note==null){
            $res = DB::table('chat_room')->select('room_id','room_name')->where('room_id',1)->first();
            $data[1] = $res->room_name;
            $data[2] = $res->room_id;
            $note = new \stdClass();
            $note->content = "";
            $roomid = $data[2];
            $rooms = [];
        }else{
            $roomid = $note->room_id;
            $rooms = explode(',',$note->rooms);
        }
        //如果是多房间，则获取所有房间
        if(Session::get('ISROOMS'))
            $allRooms = $this->getAllRooms('array');
        else
            $allRooms = [];
        return view('modal.editNoteInfo')->with('id',$data[0])->with('name',$data[1])->with('roomid',$roomid)->with('note',$note)->with('rooms',$rooms)->with('allRooms',$allRooms);
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
    //显示手动发送计划任务-弹窗表单
    public function manualPlan()
    {
        return view('modal.manualPlan');
    }
    //显示修改层级信息-弹窗表单
    public function editLevelInfo($id){
        $level = DB::table('chat_level')->select('id','recharge_min','bet_min')->where('id','=',$id)->first();
        return view('modal.editLevelInfo')->with('id',$level->id)->with('recharge_min',$level->recharge_min)->with('bet_min',$level->bet_min);
    }

    //子账号google验证码
    public function googleSubAccount($id)
    {
        $get = DB::table('chat_sa')->where('sa_id',$id)->first();
        $account = $get->account;
        if('jssaadmin' !== Session::get('account') && 'admin' !== Session::get('account') && $account !== Session::get('account')){
            die('您没有权限修改别人的');
//            return abort('503');
        }
        $subAccountId = $get->sa_id;
        $google_code = $get->google_code;
        $ga = new \PHPGangsta_GoogleAuthenticator();
        $qrCodeUrl = $ga->getQRCodeGoogleUrl('chat_'.$account,$google_code,null,['chs'=>'300x300']);
        return view('modal.member.subAccountGoogleCode',compact('qrCodeUrl','subAccountId','account','google_code'));
    }

    public function editRoomUsers()
    {
        return view('modal.editRoomUsers');
    }
    public function editRoomSearchUsers()
    {
        return view('modal.editRoomSearchUsers');
    }
    public function editRoomAdmins()
    {
        return view('modal.editRoomAdmins');
    }
    public function editRoomSearchAdmins()
    {
        return view('modal.editRoomSearchAdmins');
    }
    // 红包黑名单
    public function hongbaoBlacklist()
    {
        return view('modal.hongbaoBlacklist');
    }
    // 红包黑名单-添加
    public function hongbaoBlacklistSearchUsers()
    {
        return view('modal.hongbaoBlacklistSearchUsers');
    }
    // 强制添加好友
    public function addFriends()
    {
        return view('modal.addFriends');
    }
}
