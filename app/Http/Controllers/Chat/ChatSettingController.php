<?php

namespace App\Http\Controllers\Chat;

use App\Swoole;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Redis;

class ChatSettingController extends Controller
{
    //修改角色管理
    public function updRoleInfo(Request $request){
        $data['bg_color1'] = $request->input('bg1');                //背景1
        $data['font_color'] = $request->input('font');              //字体
        $data['length'] = $request->input('length')!=null?$request->input('length'):"";                //消息最大长度
        $data['updated_at'] = date("Y-m-d H:i:s",time());    //更新日期

        if(!empty($request->input('level')))
            $data['level'] = $request->input('level');              //会员等级
        if(!empty($request->input('bg2')))
            $data['bg_color2'] = $request->input('bg2');                //背景2
        if(!empty($request->input('roleName')))
            $data['name'] = $request->input('roleName');                //角色名
        if(!empty($request->input('permiss1')))                     //权限 1:发言 2:发图 3:踢人 4:禁言
            $permiss[] = 1;
        if(!empty($request->input('permiss2')))
            $permiss[] = 2;
        if(!empty($request->input('permiss3')))
            $permiss[] = 3;
        if(!empty($request->input('permiss4')))
            $permiss[] = 4;
        if(isset($permiss) && count($permiss)>0)
            $data['permission'] = implode(",",$permiss);
        $data['description'] = $request->input('content')==null?"":$request->input('content');          //描述

        $roleid = $request->input('id');
        if(empty($roleid)){
            if(empty($data['level']))
                return response()->json(['status'=>false,'msg'=>'角色不可为空或不可选择游客'],200);
            $hasData = DB::table('chat_roles')->select('id')->where('level',$data['level'])->first();
            if(isset($hasData->id))
                return response()->json(['status'=>false,'msg'=>'角色已存在'],200);
            $data['type'] = 2;
            DB::table('chat_roles')->insert($data);
        }else
            DB::table('chat_roles')->where('id',$roleid)->update($data);
        return response()->json(['status'=>true],200);
    }

    //删除角色管理
    public function delRoleInfo($data){
        if(in_array($data,array(1,2,4,7)))
            return response()->json(['status'=>false,'msg'=>'基础角色不可删除'],200);
        $del = DB::table('chat_roles')->where('id',$data)->delete();
        if($del==1)
            return response()->json(['status'=>true],200);
        else
            return response()->json(['status'=>false,'msg'=>'删除聊天室角色失败'],200);
    }

    //修改房间信息
    public function updRoomInfo(Request $request){
        $roomid = $request->input('id');
        $data['room_name'] = $request->input('roomName');                //房间名称
        $data['recharge'] = $request->input('rech');              //充值要求
        $data['bet'] = $request->input('bet');                //打码要求

        $update = DB::table('chat_room')->where('room_id',$roomid)->update($data);
        if($update==1)
            return response()->json(['status'=>true],200);
        else
            return response()->json(['status'=>false,'msg'=>'修改房间信息失败'],200);
    }

    //禁言房间
    public function unSpeakRoom($data)
    {
        $data = explode("&",$data);

        DB::table('chat_room')->where('room_id',$data[0])->update([
            'is_speaking'=>$data[1]=="un"?0:1,
            'updated_at'=>date("Y-m-d H:i:s",time())
        ]);
        return response()->json(['status'=>true],200);
    }

    //修改聊天室公告
    public function updNoteInfo(Request $request){
        $redis = Redis::connection();
        $redis->select(1);
        if($redis->exists('addnotc'))
            return response()->json(['status'=>false,'msg'=>'请勿连续点击'],200);
        $redis->setex('addnotc',5,'ing');
        $noteid = $request->input('id');
        $roomid = $request->input('roomid');
        $data['content'] = $request->input('content');                //公告信息
        $data['upd_sa_id'] = Session::get('account_id');              //添加管理员id
        $data['upd_account'] = Session::get('account');               //添加管理员
        $data['updated_at'] = date("Y-m-d H:i:s",time());      //更新日期

        if($noteid>0)
            $update = DB::table('chat_note')->where('chat_note_idx',$noteid)->update($data);
        else{
            $data['room_id'] = $request->input('roomid');                //房间id
            $data['add_sa_id'] = Session::get('account_id');              //添加管理员id
            $data['add_account'] = Session::get('account');               //添加管理员
            $data['created_at'] = date("Y-m-d H:i:s",time());    //新增日期
            $update = DB::table('chat_note')->insert($data);
        }
        if($update==1){
            $rsKeyH = 'chatList';
            $redis = Redis::connection();
            $redis->select(1);                                   //切换到聊天平台
            $redis->HSET($rsKeyH,'notice'.$roomid.'='.'notice','notice');
//            $swoole = new Swoole();
//            $swoole->swooletest('notice',$roomid);
            return response()->json(['status'=>true,'type'=>'notice','data'=>$roomid],200);
        }else
            return response()->json(['status'=>false,'msg'=>'修改聊天室公告失败'],200);
    }

    //删除聊天室公告
    public function delNoteInfo($data){
        $del = DB::table('chat_note')->where('chat_note_idx',$data)->delete();
        if($del==1)
            return response()->json(['status'=>true],200);
        else
            return response()->json(['status'=>false,'msg'=>'删除聊天室公告失败'],200);
    }

    //修改聊天室管理员
    public function updAdminInfo(Request $request){
        $sa_id = $request->input('id');
        $data['account'] = $request->input('account');                //管理员帐号
        $data['name'] = $request->input('nickname');                  //管理员呢称
        $data['password'] = $request->input('password');                  //管理员密码
        $data['updated_at'] = date("Y-m-d H:i:s",time());      //更新日期

        if(empty($data['password']))                                    //如果没有传密码则不修改
            unset($data['password']);
        else
            $data['password'] = Hash::make($data['password']);

        if($sa_id>0)
            $update = DB::table('chat_sa')->where('sa_id',$sa_id)->update($data);
        else{
            $data['created_at'] = date("Y-m-d H:i:s",time());    //新增日期
            $update = DB::table('chat_sa')->insert($data);
        }
        if($update==1)
            return response()->json(['status'=>true],200);
        else
            return response()->json(['status'=>false,'msg'=>'修改聊天室管理员失败'],200);
    }

    //删除聊天室管理员
    public function delAdminInfo($data){
        if(in_array($data,array(1)))
            return response()->json(['status'=>false,'msg'=>'基础管理员不可删除'],200);
        $del = DB::table('chat_sa')->where('sa_id',$data)->delete();
        if($del==1)
            return response()->json(['status'=>true],200);
        else
            return response()->json(['status'=>false,'msg'=>'删除聊天室管理员失败'],200);
    }

    //修改聊天室违禁词
    public function updForbidInfo(Request $request){
        $regex_id = $request->input('id');
        $data['room_id'] = $request->input('roomid');                //房间id
        $data['regex'] = $request->input('regex');                   //违禁词
        $data['sa_id'] = Session::get('account_id');              //添加管理员id
        $data['account'] = Session::get('account');               //添加管理员
        $data['updated_at'] = date("Y-m-d H:i:s",time());      //更新日期

        if($regex_id>0)
            $update = DB::table('chat_regex')->where('chat_regex_idx',$regex_id)->update($data);
        else{
            $data['created_at'] = date("Y-m-d H:i:s",time());    //新增日期
            $update = DB::table('chat_regex')->insert($data);
        }
        if($update==1)
            return response()->json(['status'=>true],200);
        else
            return response()->json(['status'=>false,'msg'=>'修改违禁词失败'],200);
    }

    //删除聊天室违禁词
    public function delForbidInfo($data){
        $del = DB::table('chat_regex')->where('chat_regex_idx',$data)->delete();
        if($del==1)
            return response()->json(['status'=>true],200);
        else
            return response()->json(['status'=>false,'msg'=>'删除违禁词失败'],200);
    }

    //发红包
    public function addHongbao(Request $request)
    {
        $redis = Redis::connection();
        $redis->select(1);                                   //切换到聊天平台
        if($redis->exists('addhb'))
            return response()->json(['status'=>false,'msg'=>'请勿连续点击'],200);
        $redis->setex('addhb',5,'ing');
        $data['room_id'] = $request->input('room');                //房间id
        $data['hongbao_total_amount'] = $request->input('hongbao_total_amount');        //红包总金额
        $data['hongbao_remain_amount'] = $data['hongbao_total_amount'];                 //红包剩馀金额
        $data['hongbao_total_num'] = $request->input('hongbao_total_num');              //红包总个数
        $data['hongbao_remain_num'] = $request->input('hongbao_total_num');              //红包剩馀个数
        $data['recharge'] = $request->input('recharge');                                //最低充值金额
        $data['bet'] = $request->input('bet');                                          //最低下注金额

        $data['sa_id'] = Session::get('account_id');              //添加管理员id
        $data['account'] = Session::get('account');               //添加管理员
        $data['hongbao_status'] = 1;                              //红包状态 1:抢疯中 2:已抢完 3:已关闭
        $data['posttime'] = date("Y-m-d H:i:s", time());    //新增日期

        $id = DB::table('chat_hongbao')->insertGetId($data);
        if ($id > 0) {
            return $this->reHongbao($data['room_id'].'&'.$id);
        }else
            return response()->json(['status'=>false,'msg'=>'发红包失败'],200);
    }

    //重发红包
    public function reHongbao($data){
        Redis::select(1);
        $data = explode("&",$data);
        $room = $data[0];
        $id = $data[1];
        $md5id = md5($data[1].time());
        $rsKeyH = 'chatList';
        $redis = Redis::connection();
        $redis->select(1);                                   //切换到聊天平台
        $redis->HSET($rsKeyH,'hb'.$room.'='.$md5id,$id);
//        $swoole = new Swoole();
//        $swoole->swooletest('hongbao',$room);
        return response()->json(['status'=>true,'msg'=>'发红包成功','type'=>'hongbao','data'=>$room],200);
    }

    //关闭红包
    public function closeHongbao($data){
        $upd = DB::table('chat_hongbao')->where('chat_hongbao_idx',$data)->update(array('hongbao_status'=>3));      //红包状态 1:抢疯中 2:已抢完 3:已关闭
        if($upd==1) {
            Redis::select(1);
            Redis::del('hbing'.$data);
            return response()->json(['status' => true], 200);
        }else
            return response()->json(['status'=>false,'msg'=>'关闭红包失败'],200);
    }

    //开启红包
    public function openHongbao($data){
        $upd = DB::table('chat_hongbao')->where('chat_hongbao_idx',$data)->update(array('hongbao_status'=>1));      //红包状态 1:抢疯中 2:已抢完 3:已关闭
        if($upd==1)
            return response()->json(['status'=>true],200);
        else
            return response()->json(['status'=>false,'msg'=>'关闭红包失败'],200);
    }

    //修改平台配置
    public function updBaseInfo(Request $request){
        $data['open_status'] = $request->input('openStatus')=="on"?1:0;              //聊天室状态
        $data['plan_send_mode'] = $request->input('planSendMode')=="1"?1:0;         //计划发布方式
        $planSendGamePK10 = $request->input('planSendGamePK10');                    //计划推送游戏-北京赛车
        if($planSendGamePK10=="on")
            $data['plan_send_game'] = "50";
        $planSendGameCQSSC = $request->input('planSendGameCQSSC');                  //计划推送游戏-重庆时时彩
        if($planSendGameCQSSC=="on")
            $data['plan_send_game'] .= (isset($data['plan_send_game'])?",":"")."1";
        $data['plan_msg'] = $request->input('planMsg');                             //计划底部信息
        $data['send_starttime'] = $request->input('starttime');                     //发布时段(开始)
        $data['send_endtime'] = $request->input('endtime');                         //发布时段(结束)
        $data['is_open_auto'] = $request->input('isOpenAuto')=="1"?1:0;                      //是否展开聊天室
        $data['bet_min_amount'] = $request->input('betMin');                        //下注最低推送额
        $data['ip_blacklist'] = $request->input('ipBlacklist');                     //IP黑名单
        $data['sa_id'] = Session::get('account_id');              //添加管理员id
        $data['account'] = Session::get('account');               //添加管理员
        $data['updated_at'] = date("Y-m-d H:i:s",time());      //更新日期

        $update = DB::table('chat_base')->where('chat_base_idx',1)->update($data);

        if($update==1)
            return response()->json(['status'=>true],200);
        else
            return response()->json(['status'=>false,'msg'=>'修改违禁词失败'],200);
    }
    //手动发送计画任务
    public function sendPlan(Request $request){
        $plan = $request->input('plan').'<br>';                    //计划推送
        $session_id = md5(time().rand(1,10));
        $aRep =array(
            'userId' => 'plans',
            'plans' => $plan,
            'img' => '/game/images/chat/sys.png'                          //用户头像
        );
        $rsKeyH = 'chatList';
        $redis = Redis::connection();
        $redis->select(1);                                   //切换到聊天平台
        $redis->HSET($rsKeyH,'pln='.$session_id,json_encode($aRep,JSON_UNESCAPED_UNICODE));
        return response()->json(['status'=>true],200);

    }

    //修改层级信息
    public function updLevelInfo(Request $request){
        if(!empty($request->input('id'))){
            $id = $request->input('id');
        }else{
            return response()->json(['status'=>false,'msg'=>'修改参数错误'],200);
        }
        $data = [];
        if(!empty($request->input('recharge_min'))){
            $data['recharge_min'] = $request->input('recharge_min');
        }
        if(!empty($request->input('bet_min'))){
            $data['bet_min'] = $request->input('bet_min');
        }
        $data['updated_at'] = date("Y-m-d H:i:s",time());      //更新日期
        if(DB::table('chat_level')->where('id','=',$id)->update($data)){
            return response()->json(['status'=>true],200);
        }else{
            return response()->json(['status'=>false,'msg'=>'修改层级失败'],200);
        }
    }
}
