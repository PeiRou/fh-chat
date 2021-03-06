<?php

namespace App\Http\Controllers\Chat;

use App\Http\Controllers\Common\Reward;
use App\Model\ChatHongbaoBlacklist;
use App\Model\ChatRoom;
use App\Model\ChatSendConfig;
use App\Repository\ChatRoomRepository;
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
//            DB::table('chat_roles')->insert($data);   //暂时没有新增
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
        $redis = Redis::connection();
        $redis->select(REDIS_DB_LOCK_BASIS);
        $key = 'chat_updRoomInfo';
        if($redis->exists($key))
            return response()->json(['status'=>false,'msg'=>'请勿连续点击'],200);
        $redis->setex($key , 30, 'no');

        $roomid = $request->input('id');
        $data = [];
        $request->input('roomName') && $data['room_name'] = $request->input('roomName');                //房间名称
        $request->input('roomType') && $data['roomType'] = $request->input('roomType');                //房间类型
        $request->input('rech')!='' && $data['recharge'] = $request->input('rech');              //充值要求
        $request->input('bet')!='' && $data['bet'] = $request->input('bet');                //打码要求
        if(empty($request->planSendGames)){
            $data['planSendGame'] = '';
        }else{
            $data['planSendGame'] = implode(',', $request->planSendGames);
        }
//        isset($request->planSendGames) && $data['planSendGame'] = implode(',', $request->planSendGames);
        isset($request->pushBetGames) && $data['pushBetGame'] = implode(',', $request->pushBetGames);
        isset($request->top_sort) && $data['top_sort'] = (int)$request->top_sort;
        if(isset($request->head_img)){
            $image = $request->head_img;    //接收base64的图
            $limit = strpos($image,'base64,');
            $image = substr($image,$limit+7);
            $image = str_replace(' ', '+', $image);
            $path = "/roomImg/";
            $imageName = $path.md5($roomid.time()).".jpg";                       //MD5图片名称

            $arr = array();
            $arr['path'] = $path;
            $arr['imgName'] = $imageName;
            $arr['img'] = $image;
            $swoole = new Swoole();
            if($swoole->swooletest('upchat',$roomid,$arr) == 'ok')
                $data['head_img'] = "/upchat".$imageName."?t=".time().rand(111,22222);
        }

        if($roomid > 3) //不给关
            !is_null($request->is_open) && $data['is_open'] = $request->is_open == 1 ? 0 : 1;
        $data['updated_at'] = date('Y-m-d H:i:s');          //修改时间
        if($roomid == 0){
            $data['created_at'] = date('Y-m-d H:i:s');      //新增时间
            $u = DB::table('chat_room')->insert($data);
        }else{
            $top_sort = DB::table('chat_room')->where('room_id',$roomid)->value('top_sort');
            $u = DB::table('chat_room')->where('room_id',$roomid)->update($data);
        }
        if($u==1){
            if(isset($data['top_sort']) && isset($top_sort) && $data['top_sort'] !== $top_sort){
                # 修改了置顶 修改所有会员的历史列表
                if(!($res = Swoole::getSwoole('BackAction/setSortRoom', [
                        'roomId' => $roomid,
                        'top_sort' => $data['top_sort'],
                        'token' => Session::getId()
                    ])) || $res['code'] !== 0){
                    return response()->json([
                        'status'=>false,
                        'msg'=> '修改房间成功，推送失败'
                    ]);
                }
            }
            return response()->json(['status'=>true],200);
        }
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
        $redis = Redis::connection();
        $redis->select(REDIS_DB_DAY_CLEAR);
        $redis->set('speak',$data[1]=="un"?"un":"on");
        return response()->json(['status'=>true],200);
    }

    //是否开启快速加入
    public function openAutoRoom($data)
    {
        $data = explode("&",$data);

        DB::table('chat_room')->where('room_id',$data[0])->update([
            'is_auto'=>$data[1]=="un"?0:1,
            'updated_at'=>date("Y-m-d H:i:s",time())
        ]);
        return response()->json(['status'=>true],200);
    }

    //开放测试帐号聊天
    public function onTestSpeakRoom($data)
    {
        $data = explode("&",$data);

        DB::table('chat_room')->where('room_id',$data[0])->update([
            'isTestSpeak'=>$data[1]=="un"?0:1,
            'updated_at'=>date("Y-m-d H:i:s",time())
        ]);
        return response()->json(['status'=>true],200);
    }

    //修改聊天室公告
    public function updNoteInfo(Request $request){
        $redis = Redis::connection();
        $redis->select(REDIS_DB_LOCK_BASIS);
        if($redis->exists('addnotc'))
            return response()->json(['status'=>false,'msg'=>'请勿连续点击'],200);
        $redis->setex('addnotc',5,'ing');
        $noteid = $request->input('id');
        $roomid = $request->input('roomid');
        $data['content'] = $request->input('content');                //公告信息
        $data['upd_sa_id'] = Session::get('account_id');              //添加管理员id
        $data['upd_account'] = Session::get('account');               //添加管理员
        $data['updated_at'] = date("Y-m-d H:i:s",time());      //更新日期
//        $data['rooms'] = implode(',',$request->input('rooms'));      //多房间公告设定
        isset($request->rooms) && $data['rooms'] = implode(',', $request->rooms);       //多房间公告设定

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
            $swoole = new Swoole();
            $swoole->swooletest('notice',$roomid);
            return response()->json(['status'=>true,'data'=>$roomid],200);
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

        if($sa_id>0){
            unset($data['account']);
            $update = DB::table('chat_sa')->where('sa_id',$sa_id)->update($data);
        }else{
            $ga = new \PHPGangsta_GoogleAuthenticator();
            $googleCode = $ga->createSecret();
            $data['google_code'] = $googleCode;
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
//    public function addHongbao(Request $request)
//    {
//        $redis = Redis::connection();
//        $redis->select(1);                                   //切换到聊天平台
//        if($redis->exists('addhb'))
//            return response()->json(['status'=>false,'msg'=>'请勿连续点击'],200);
//        $redis->setex('addhb',5,'ing');
//        $data['room_id'] = $request->input('room');                //房间id
//        $data['hongbao_total_amount'] = $request->input('hongbao_total_amount');        //红包总金额
//        $data['hongbao_remain_amount'] = $data['hongbao_total_amount'];                 //红包剩馀金额
//        $data['hongbao_total_num'] = $request->input('hongbao_total_num');              //红包总个数
//        $data['hongbao_remain_num'] = $request->input('hongbao_total_num');              //红包剩馀个数
//        $data['recharge'] = $request->input('recharge');                                //最低充值金额
//        $data['bet'] = $request->input('bet');                                          //最低下注金额
//
//        $data['sa_id'] = Session::get('account_id');              //添加管理员id
//        $data['account'] = Session::get('account');               //添加管理员
//        $data['hongbao_status'] = 1;                              //红包状态 1:疯抢中 2:已抢完 3:已关闭
//        $data['posttime'] = date("Y-m-d H:i:s", time());    //新增日期
//
//        $id = DB::table('chat_hongbao')->insertGetId($data);
//        if ($id > 0) {
//            return $this->reHongbao($data['room_id'].'&'.$id,$data);
//        }else
//            return response()->json(['status'=>false,'msg'=>'发红包失败'],200);
//    }
    //发红包
    public function addHongbao(Request $request)
    {
        $redis = Redis::connection();
        $redis->select(REDIS_DB_LOCK_BASIS);                                   //切换到聊天平台
        if($redis->exists('addhb'))
            return response()->json(['status'=>false,'msg'=>'请勿连续点击'],200);
        $redis->setex('addhb',5,'ing');
        $data['room_id'] = $request->input('room');                //房间id
        $data['type'] = (int)$request->type;
        $data['hongbao_total_amount'] = (float)$request->input('hongbao_total_amount');        //红包总金额
        if($data['hongbao_total_amount'] >= 10000000){
            return response()->json(['status'=>false,'msg'=>'金额过大'],200);
        }
        $data['hongbao_remain_amount'] = (float)$data['hongbao_total_amount'];                 //红包剩馀金额
        $data['recharge'] = $request->input('recharge');                                //最低充值金额
        $data['bet'] = $request->input('bet');                                          //最低下注金额
        $data['sa_id'] = Session::get('account_id');              //添加管理员id
        $data['account'] = Session::get('account');               //添加管理员
        $data['hongbao_status'] = 1;                              //红包状态 1:疯抢中 2:已抢完 3:已关闭
        $data['posttime'] = date("Y-m-d H:i:s", time());    //新增日期
        $data['hongbao_total_num'] = (int)$request->input('hongbao_total_num');              //红包总个数
        $data['hongbao_remain_num'] = (int)$request->input('hongbao_total_num');              //红包剩馀个数
        $data['hongbao_min_amount'] = (float)$request->hongbao_min_amount;  //红包最小金额
        $data['hongbao_max_amount'] = (float)$request->hongbao_max_amount;  //红包最大金额
        $data['hongbao_min_amount'] < 0 && $data['hongbao_min_amount'] = 0;
        $data['hongbao_max_amount'] < 0 && $data['hongbao_max_amount'] = 0;
        $func = 'reHongbao';
        if($data['type'] == 1){
            if($data['hongbao_min_amount'] > $data['hongbao_max_amount']){
                return response()->json(['status'=>false,'msg'=>'红包金额错误'],200);
            }
            if($data['hongbao_max_amount'] == 0){
                return response()->json(['status'=>false,'msg'=>'请设置红包最大金额'],200);
            }
            $func = 'reHongbao1';
        }
        $id = DB::table('chat_hongbao')->insertGetId($data);

        if ($id > 0) {
            return $this->$func($data['room_id'].'&'.$id,$data);
        }else
            return response()->json(['status'=>false,'msg'=>'发红包失败'],200);
    }

    //重发红包
    public function reHongbao1($data,$hbdt=array()){
        try{
            $data = explode("&",$data);
            $room = $data[0];
            $id = $data[1];
            if(count($hbdt)==0){
                $hbdt = (array)DB::table('chat_hongbao')->select('hongbao_remain_amount','hongbao_remain_num')->where('chat_hongbao_idx',$id)->first();
            }

            //放置红包缓存
            $redis = Redis::connection();
            $redis->select(REDIS_DB_DAY_CLEAR);
            $hb_key = 'hbing' . $id;
            if(!$redis->exists($hb_key)) {
                $redis->sadd('hbing' . $id, 0);
            }

            //将红包算好数量，放到redis红包里，供人读取
            if($this->saveRedEnvelopeRedis1($hbdt['hongbao_total_num'],$hbdt['hongbao_min_amount'], $hbdt['hongbao_max_amount'],$id)){
                $data['id'] = $id;
                $swoole = new Swoole();
                $res = $swoole->swooletest('hongbao',$room,$data);
                return response()->json(['status'=>true,'msg'=>'发红包成功','data'=>$res],200);
            }
        }catch (\Throwable $e){
            writeLog('error', $e->getMessage().$e->getFile().'('.$e->getLine().')'.$e->getTraceAsString());
        }
        DB::table('chat_hongbao')->where('chat_hongbao_idx',$id)->update(array('hongbao_status'=>2));      //红包状态 1:抢疯中 2:已抢完 3:已关闭
        return response()->json(['status'=>false,'msg'=>'发红包失败'],200);
    }

    /**
     * 分配好红包，类型2
     * @param int $total_num
     * @param float $min
     * @param float $max
     * @param int $id
     * @return bool
     */
    public function saveRedEnvelopeRedis1(int $total_num, float $min, float $max, int $id)
    {
        $redis = Redis::connection();
        $redis->select(REDIS_DB_DAY_CLEAR);      //聊天室红包
        if(!$redis->exists('hb_'.$id)){
            if($total_num < 0 || $min < 0 || $max <= 0 || $min > $max){
                return false;
            }
            $redWardArray = [];
            $hongbao_total_amount = 0;
            $rmin =  (int)($min * 100);
            $rmax =  (int)($max * 100);
            for ($i = 0; $i<$total_num; $i++){
                $v = rand($rmin, $rmax) / 100;
                array_push($redWardArray, $v);
            }
            shuffle($redWardArray);
            shuffle($redWardArray);
            foreach ($redWardArray as $k => $v){
                $redis->SADD('hb_'.$id,$k.'-'.$v);
                $hongbao_total_amount += $v;
            }
            DB::table('chat_hongbao')->where('chat_hongbao_idx',$id)->update(array(
                'hongbao_total_amount'=>$hongbao_total_amount,
                'hongbao_remain_amount'=>$hongbao_total_amount,
            ));
        }
        return true;
    }




    //重发红包
    public function reHongbao($data,$hbdt=array()){
        $data = explode("&",$data);
        $room = $data[0];
        $id = $data[1];
        if(count($hbdt)==0){
            $hbdt = (array)DB::table('chat_hongbao')->select('hongbao_remain_amount','hongbao_remain_num','type','hongbao_min_amount', 'hongbao_max_amount')->where('chat_hongbao_idx',$id)->first();
        }

        //放置红包缓存
        $redis = Redis::connection();
        $redis->select(REDIS_DB_DAY_CLEAR);
        $hb_key = 'hbing' . $id;
        if(!$redis->exists($hb_key)) {
            $redis->sadd('hbing' . $id, 0);
        }

        //将红包算好数量，放到redis红包里，供人读取
        $hb_amount = $hbdt['hongbao_remain_amount'];      //要发的金额
        $hb_num = $hbdt['hongbao_remain_num'];           //要发的数量
        if($hbdt['type'] == 0){
            $res = $this->saveRedEnvelopeRedis($hb_amount,$hb_num,$id);
        }elseif($hbdt['type'] == 1){
            $res = $this->saveRedEnvelopeRedis1($hb_num,$hbdt['hongbao_min_amount'], $hbdt['hongbao_max_amount'] ,$id);
        }
        if($res){
            $data['id'] = $id;
            $swoole = new Swoole();
            $res = $swoole->swooletest('hongbao',$room,$data);
            return response()->json(['status'=>true,'msg'=>'发红包成功','data'=>$res],200);
        }else{
            DB::table('chat_hongbao')->where('chat_hongbao_idx',$id)->update(array('hongbao_status'=>2));      //红包状态 1:抢疯中 2:已抢完 3:已关闭
            return response()->json(['status'=>false,'msg'=>'发红包失败'],200);
        }
    }

    /**
     * 红包分割并存入redis，类型1
     * @param $total
     * @param $num
     * @param $id
     * @return int
     */
    public function saveRedEnvelopeRedis($total,$num,$id){
        if($total<=0 || $num<=0)
            return 0;
        $redWardArray = [];
        $remain_amount = $total;
        $remain_num = $num;
        for($ii=1;$ii<=$num;$ii++){
            $tmp = $remain_amount / $remain_num * 100 * 2;
            $rand = round( rand(1,$tmp)/100,3);
            $randAddSubtract = rand(0,1);
            if($randAddSubtract)
                $rand = +$rand;
            else
                $rand = -$rand;
            if($ii == $num) {
                $takeHongBao = $remain_amount;
            }else{
                $tmpAvg = $remain_amount  / $remain_num;
                $takeHongBao = $tmpAvg+$rand;
                if($takeHongBao<=0)             //如果是负的
                    $takeHongBao += $tmpAvg;
                if($takeHongBao >= $remain_amount)
                    $takeHongBao = $tmpAvg;
            }
            $takeHongBao = round($takeHongBao,2);
            $redWardArray[] = $takeHongBao;
            $remain_amount -= $takeHongBao;
        }
        shuffle($redWardArray);
        shuffle($redWardArray);
        $redis = Redis::connection();
        $redis->select(REDIS_DB_DAY_CLEAR);      //聊天室红包
        if(!$redis->exists('hb_'.$id)){
            $sum = 0;
            foreach ($redWardArray as $k => $v){
                $redis->SADD('hb_'.$id,$k.'-'.$v);
                $sum += $v;
            }
            \Log::info($sum);
        }
        return 1;
    }

    //关闭红包
    public function closeHongbao($data){
        $upd = DB::table('chat_hongbao')->where('chat_hongbao_idx',$data)->update(array('hongbao_status'=>3));      //红包状态 1:疯抢中 2:已抢完 3:已关闭
        if($upd==1) {
            $redis = Redis::connection();
            $redis->select(REDIS_DB_DAY_CLEAR);
            $redis->del('hbing'.$data);
            return response()->json(['status' => true], 200);
        }else
            return response()->json(['status'=>false,'msg'=>'关闭红包失败'],200);
    }

    //开启红包
    public function openHongbao($data){
        $upd = DB::table('chat_hongbao')->where('chat_hongbao_idx',$data)->update(array('hongbao_status'=>1));      //红包状态 1:疯抢中 2:已抢完 3:已关闭
        if($upd==1) {
            $redis = Redis::connection();
            $redis->select(REDIS_DB_DAY_CLEAR);
            $redis->sadd('hbing'.$data,0);
            return response()->json(['status' => true], 200);
        }else
            return response()->json(['status'=>false,'msg'=>'关闭红包失败'],200);
    }

    //修改平台配置
    public function updBaseInfo(Request $request){
        $data['open_status'] = $request->input('openStatus')=="on"?1:0;              //聊天室状态
        $data['bet_push_status'] = $request->input('bet_push_status')=="on"?1:0;              //聊天室是否开启推送跟单
        $data['plan_msg'] = $request->input('planMsg');                             //计划底部信息
        $data['is_open_auto'] = $request->input('isOpenAuto')=="1"?1:0;                      //是否展开聊天室
        $data['bet_min_amount'] = $request->input('betMin');                        //下注最低推送额
        $data['ip_blacklist'] = $request->input('ipBlacklist');                     //IP黑名单
        $data['sa_id'] = Session::get('account_id');              //添加管理员id
        $data['account'] = Session::get('account');               //添加管理员
        $data['updated_at'] = date("Y-m-d H:i:s",time());      //更新日期
        $data['guan_msg'] = $request->guan_msg;      //
        $data['is_build_room'] = Session::get('ISROOMS')? (int)$request->is_build_room : 0; //会员是否可以建群，只有多聊天室模式下可以开
        $data['is_add_friends'] = (int)$request->is_add_friends;      //

        if($i = ChatSendConfig::editConfigBefore($request, 0)){
            return response()->json(['status'=>false,'msg'=>'发布时段更新失败：'.$i],200);
        }

        $update = DB::table('chat_base')->where('chat_base_idx',1)->update($data);
        $redis = Redis::connection();
        $redis->select(5);       //操作锁定REDIS库
        $redisKey = 'chat_base';
        $redis->del($redisKey);

        if($update==1)
            return response()->json(['status'=>true],200);
        else
            return response()->json(['status'=>false,'msg'=>'修改违禁词失败'],200);
    }
    //手动发送计划任务
    public function sendPlan(Request $request){
        $plan = $request->input('plan').'<br>';                    //计划推送
        $session_id = md5(time().rand(1,10));
        //字符串转十六进制函数
        $plan = base64_encode(urlencode($plan));

        $aRep =array(
            'userId' => 'plans',
            'plans' => $plan,
            'img' => '/game/images/chat/sys.png'                          //用户头像
        );
        $data['id'] = $session_id;
        $data['game'] = 0;                                  //不分类
        $data['pln'] = json_encode($aRep,JSON_UNESCAPED_UNICODE);
        $swoole = new Swoole();
        $swoole->swooletest('plan',1,$data);
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

    //刷新google账号
    public function changeGoogleCode(Request $request)
    {
        $id = $request->get('id');
        $ga = new \PHPGangsta_GoogleAuthenticator();
        $secret = $ga->createSecret();
        $update = DB::table('chat_sa')->where('sa_id',$id)
            ->update([
                'google_code'=>$secret
            ]);
        if($update == 1){
            $find = DB::table('chat_sa')->where('sa_id',$id)->first();
            $qrCodeUrl = $ga->getQRCodeGoogleUrl('chat_'.$find->account,$secret);
            return response()->json([
                'status'=>true,
                'msg'=>[
                    'qrCodeUrl'=>$qrCodeUrl,
                    'code'=>$secret
                ]
            ]);
        } else {
            return response()->json([
                'status'=>false,
                'msg'=>'暂时无法更新，请稍后重试'
            ]);
        }
    }
    //房间家人
    public function addRoomUser(Request $request)
    {
        if(!($request->id = (int)$request->id) || !($request->user_id = (int)$request->user_id)){
            return response()->json([
                'status'=>false,
                'msg'=>'参数错误'
            ]);
        }

        if(!($res = Swoole::getSwoole('BackAction/addRoomUser', [
                'roomId' => $request->id,
                'user_id' => $request->user_id,
                'token' => Session::getId()
            ])) || $res['code'] !== 0){
            return response()->json([
                'status'=>false,
                'msg'=> 'error'
            ]);
        }

        return response()->json([
            'status'=>true,
        ]);
    }
    //房间踢人
    public function deleteUser(Request $request)
    {
        if(!($request->roomId = (int)$request->roomId) || !($request->user_id = (int)$request->user_id)){
            return response()->json([
                'status'=>false,
                'msg'=>'参数错误'
            ]);
        }

        if(!($res = Swoole::getSwoole('BackAction/deleteUser', [
            'roomId' => $request->roomId,
            'user_id' => $request->user_id,
            'token' => Session::getId()
        ])) || $res['code'] !== 0){
            return response()->json([
                'status'=>false,
                'msg'=> 'error'
            ]);
        }

        return response()->json([
            'status'=>true,
        ]);
    }
    //删除管理
    public function delAdmin(Request $request)
    {
        if(!($request->roomId = (int)$request->roomId) || !($request->user_id = (int)$request->user_id)){
            return response()->json([
                'status'=>false,
                'msg'=>'参数错误'
            ]);
        }
        if(!($res = Swoole::getSwoole('BackAction/delAdmin', [
                'roomId' => $request->roomId,
                'user_id' => $request->user_id,
                'token' => Session::getId()
            ])) || $res['code'] !== 0){
            return response()->json([
                'status'=>false,
                'msg'=> 'error'
            ]);
        }

        return response()->json([
            'status'=>true,
        ]);
    }
    //添加管理
    public function addRoomAdmin(Request $request)
    {
        if(!($request->roomId = (int)$request->roomId) || !($request->user_id = (int)$request->user_id)){
            return response()->json([
                'status'=>false,
                'msg'=>'参数错误'
            ]);
        }
        if(!($res = Swoole::getSwoole('BackAction/addRoomAdmin', [
                'roomId' => $request->roomId,
                'user_id' => $request->user_id,
                'token' => Session::getId()
            ])) || $res['code'] !== 0){
            return response()->json([
                'status'=>false,
                'msg'=> 'error'
            ]);
        }

        return response()->json([
            'status'=>true,
        ]);
    }
    //删除房间
    public function delRoom(Request $request)
    {
        if(!($request->roomId = (int)$request->roomId)){
            return response()->json([
                'status'=>false,
                'msg'=>'参数错误'
            ]);
        }

        if(!($res = Swoole::getSwoole('BackAction/delRoom', [
                'roomId' => $request->roomId,
                'token' => Session::getId()
            ])) || $res['code'] !== 0){
            return response()->json([
                'status'=>false,
                'msg'=> 'error'
            ]);
        }

        return response()->json([
            'status'=>true,
        ]);
    }
    //跟单设置
    public function setPushBet(Request $request){
        $data = $request->all();
        if($data['is_pushBet'] == 0){
            $data['is_pushBet'] = 1;
        }elseif($data['is_pushBet'] == 1){
            $data['is_pushBet'] = 0;
        }
        $res = DB::table('chat_room_dt')->where('id',$data['roomId'])->where('user_id',$data['user_id'])->update(['is_pushbet'=>$data['is_pushBet']]);
        if($res){
            return response()->json([
                'status'=>true,
            ]);
        }
        return response()->json([
            'status'=>false,
            'msg'=> 'error'
        ]);
    }

    //删掉红包黑名单会员
    public function deleteHongbaoBlacklist(Request $request)
    {
        $redis = Redis::connection();
        $redis->select(REDIS_DB_LOCK_BASIS);
        $key = 'deleteHongbaoBlacklist';
        if($redis->exists($key)){
            return response()->json([
                'status'=>false,
                'msg'=> '操作频繁'
            ]);
        }
        $redis->setex($key, 3, 'i');
        if(ChatHongbaoBlacklist::deleteHongbaoBlacklist($request->chat_hongbao_idx, $request->user_id) && $this->upRemoteHongbaoBlacklist((int)$request->chat_hongbao_idx))
            $redis->del($key);
            return response()->json([
                'status'=>true,
            ]);
        return response()->json([
            'status'=>false,
            'msg'=> 'error'
        ]);
    }
    //添加红包黑名单会员
    public function addHongbaoBlacklist(Request $request)
    {
        $redis = Redis::connection();
        $redis->select(REDIS_DB_LOCK_BASIS);
        $key = 'addHongbaoBlacklist';
        if($redis->exists($key)){
            return response()->json([
                'status'=>false,
                'msg'=> '操作频繁'
            ]);
        }
        $redis->setex($key, 3, 'i');
        try{
            if(ChatHongbaoBlacklist::insert([
                'user_id' => $request->user_id,
                'chat_hongbao_idx' => (int)$request->chat_hongbao_idx,
            ]) && $this->upRemoteHongbaoBlacklist((int)$request->chat_hongbao_idx)){
                $redis->del($key);
                return response()->json([
                    'status'=>true,
                ]);
            }
        }catch (\Throwable $e){}
        return response()->json([
            'status'=>false,
            'msg'=> 'error'
        ]);
    }

    //更新socket保存的黑名单内存
    public function upRemoteHongbaoBlacklist($chat_hongbao_idx)
    {
        if(!($res = Swoole::getSwoole('BackAction/upStaticChatHongbaoBlacklist', [
                'chat_hongbao_idx' => $chat_hongbao_idx,
                'token' => Session::getId()
            ])) || $res['code'] !== 0){
            return false;
        }
        return true;
    }

    public function addFriends(Request $request)
    {
        if(!$request->account || !$request->to_account){
            return response()->json([
                'status'=>false,
                'msg'=> '参数错误'
            ]);
        }
        if(!($res = Swoole::getSwoole('BackAction/addFriends', [
                'name' => $request->account,
                'toName' => $request->to_account,
                'token' => Session::getId()
            ])) || $res['code'] !== 0){
            return response()->json([
                'status'=>false,
                'msg'=> $res['msg'] ?? 'error'
            ]);
        }
        return response()->json([
            'status'=>true,
        ]);
    }

}
