<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\DB;

class Swoole extends Command
{
    public $ws;
    public $redis;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'swoole {action?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'swoole';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->redis = new \Redis();
        $this->redis->connect(env('REDIS_HOST','127.0.0.0.1'), env('REDIS_PORT',6379));
        $this->redis->select(1);
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $action = $this->argument('action');
        switch ($action) {
            case 'close':
                break;
            case 'clean':
                $this->redis->flushdb();        //服务每天一启动就要清除之前的聊天室redis
                die;
                break;
            case 'start':
                $this->init();
            default:
                $this->start();
                break;
        }
    }
    private $chatkey = 'chatList';
    private $tmpChatList = array();

    private function init(){
        $this->redis->del($this->chatkey);
        $keys = $this->redis->keys('hbing'.'*');
        $this->redis->multi();
        foreach ($keys as $item){
            $this->redis->del($item);
        }
        $this->redis->exec();
    }

    public function start(){
        //创建websocket服务器对象，监听0.0.0.0:9502端口
        $this->ws = new \swoole_websocket_server("0.0.0.0", env('WS_PORT',2021),SWOOLE_PROCESS, SWOOLE_SOCK_TCP | SWOOLE_SSL);
        $this->ws->set(array(
            'ssl_cert_file' => __DIR__ . '/config/' .env('WS_HOST_SSL','fh').'_ssl.crt',
            'ssl_key_file' => __DIR__ . '/config/' .env('WS_HOST_SSL','fh').'_ssl.key',
        ));

        //监听WebSocket连接打开事件
        $this->ws->on('open', function ($ws, $request) {
            //        $this->redis = Redis::connection();
            $this->redis->select(1);
            $strParam = $request->server;
            $strParam = explode("/",$strParam['request_uri']);      //房间号码
            $iSess = $strParam[1];
            $iRoomInfo = $this->getUsersess($iSess,$request->fd);                 //从sess取出会员资讯
            if(!isset($iRoomInfo['room'])|| empty($iRoomInfo['room']))                                   //查不到登陆信息或是房间是空的
                return $this->msg(3,'登陆失效1');
            $this->updUserInfo($request->fd,$iRoomInfo);        //成员登记他的房间号码

            //获取聊天室公告
            $msg = $this->getChatNotice($iRoomInfo['room']);
            $this->ws->push($request->fd, $msg);
            //广播登陆信息
            $msg = $this->msg(1, '进入聊天室', $iRoomInfo);   //进入聊天室
            $this->sendToAll( $iRoomInfo['room'], $msg);
            //检查历史讯息
            $this->chkHisMsg($iRoomInfo,$request->fd);
            //回传自己的基本设置
            $msg = $this->msg(7,'fstInit',$iRoomInfo);
            $this->ws->push($request->fd, $msg);
        });

        //监听WebSocket消息事件
        $this->ws->on('message', function ($ws, $request) {
            //        $this->redis = Redis::connection();
            $this->redis->select(1);
            if(substr($request->data,0,6)=="heart="){       //心跳检查
                $iSess = substr($request->data,6);
                $request->data = "heart";
                //不广播客户端传来的心跳
                if($request->data=='heart')
                    return true;
            }else if(substr($request->data,0,6)=="token="){
                $iSess = substr($request->data,6,40);
                $request->data = substr($request->data,47);
            }
            $iRoomInfo = $this->getUserInfo($request->fd);   //取出他的房间号码
            error_log(date('Y-m-d H:i:s',time())." 发言=> ".$request->fd." => ".json_encode($request).json_encode($iRoomInfo).PHP_EOL, 3, '/tmp/chat/'.date('Ymd').'.log');        //只要连接就记下log
            //登陆失效
            if(!isset($iRoomInfo['room'])|| empty($iRoomInfo['room'])){
                if(isset($iSess)){
                    $iRoomInfo = $this->getUsersess($iSess,$request->fd);                 //从sess取出会员资讯
                    if(empty($iRoomInfo) || empty($iRoomInfo['room'])) {
                        $msg = $this->msg(3, '登陆失效2');
                        return $this->ws->push($request->fd, $msg);
                    }
                }else{
                    $msg = $this->msg(3, '登陆失效3');
                    return $this->ws->push($request->fd, $msg);
                }
            }

            //获取聊天用户数组
            $iRoomUsers = $this->updAllkey('usr',$iRoomInfo['room']);   //获取聊天用户数组，在反序列化回数组

            //不广播被禁言的用户
            if($iRoomInfo['noSpeak']==1){
                $msg = $this->msg(5,'此帐户已禁言');
                return $this->ws->push($request->fd, $msg);
            }
            //消息过滤HTML标签
            $aMesgRep = urldecode(base64_decode($request->data));
            $aMesgRep = trim ($aMesgRep);
            $aMesgRep = strip_tags ($aMesgRep);
            $aMesgRep = htmlspecialchars ($aMesgRep);
            $aMesgRep = addslashes ($aMesgRep);
            //消息处理违禁词
            $aMesgRep = $this->regSpeaking($aMesgRep);
            $aMesgRep = urlencode($aMesgRep);
            $aMesgRep = base64_encode(str_replace('+', '%20', $aMesgRep));   //计划发消息
            //发送消息
            if(!is_array($iRoomInfo))
                $iRoomInfo = (array)$iRoomInfo;
            $getUuid = $this->getUuid($iRoomInfo['name']);
            $iRoomInfo['timess'] = $getUuid['timess'];
            $iRoomInfo['uuid'] = $getUuid['uuid'];
            foreach ($iRoomUsers as $fdId =>$val) {
                if($val==$request->fd)//组装消息数据
                    $msg = $this->msg(4,$aMesgRep,$iRoomInfo);   //自己发消息
                else
                    $msg = $this->msg(2,$aMesgRep,$iRoomInfo);   //别人发消息
                $this->push($val, $msg,$iRoomInfo['room']);
            }
        });
        $this->ws->on('receive', function ($ws, $request) {
        });
        //接收WebSocket服务器推送功能
        $this->ws->on('request', function ($serv) {
            $room = isset($serv->post['room'])?$serv->post['room']:$serv->get['room'];
            $type = isset($serv->post['type'])?$serv->post['type']:$serv->get['type'];
            switch ($type){
                case 'plan':
                    //检查计划消息
                    $this->chkPlan($room,$serv);
                    break;
                case 'delHis':
                    //检查删除消息
                    $this->chkDelHis($room,$serv);
                    break;
                case 'hongbao':
                    //检查红包
                    $this->chkHongbao($room,$serv);
                    break;
                case 'hongbaoNum':
                    //检查抢红包消息
                    $this->chkHongbaoNum($room,$serv);
                    break;
                case 'notice':
                    //检查公告异动
                    $this->chkNotice($room);
                    break;
                case 'msgSendR':
                    //检查消息推送
                    $this->chkSendR($room);
                    break;
                case 'msgSendC':
                    //检查消息推送
                    $this->chkSendC($room);
                    break;
                case 'upchat':
                    //检查上传图片
                    $this->upchat($serv);
                    break;
            }
        });

        //监听WebSocket连接关闭事件
        $this->ws->on('close', function ($ws, $fd) {
            $this->delAllkey($fd,'usr');   //删除用户
        });

        $this->ws->start();
    }
    private function upchat($serv){
        $path = isset($serv->post['path'])?$serv->post['path']:$serv->get['path'];
        $imageName = isset($serv->post['imgName'])?$serv->post['imgName']:$serv->get['imgName'];
        $img = isset($serv->post['img'])?$serv->post['img']:$serv->get['img'];
        if(empty($path) || empty($img))
            return false;
        $docUrl = public_path().$path;
        if(!file_exists($docUrl))                   //如果资料夹不存在，则创建资料夹
            mkdir($docUrl);

        \File::put(public_path(). $imageName, base64_decode($img));
    }
    //发消息给所有人
    private function sendToAll($room_id,$msg){
        $iRoomUsers = $this->updAllkey('usr',$room_id);   //获取聊天用户数组，在反序列化回数组
        foreach ($iRoomUsers as $usrfdId =>$fdId) {
            $this->push( $fdId, $msg,$room_id);
        }
    }
    //检查如果与聊天室服务器断线，则取消发送信息
    private function push($fd,$msg,$room_id =1){
        //        $this->redis = Redis::connection();
        $this->redis->select(1);
        if(!$this->ws->connection_info($fd)){        //检查如果与聊天室服务器断线，则取消发送信息
            $this->delAllkey($fd,'usr',$room_id);   //删除用户
        }else
            $this->ws->push($fd, $msg);
    }

    /***
     * 组装回馈讯息
     * $status =>1:进入聊天室 2:别人发言 3:退出聊天室 4:自己发言 5:禁言 6:公告 7:获取自己权限 8:红包 9:抢到红包消息 10:删除讯息 11:右上角消息推送 12:中间消息推送
     */
    private function msg($status,$msg,$userinfo = array()){
        if(!is_array($userinfo))
            $userinfo = (array)$userinfo;
        $data['fd'] = isset($userinfo['name'])?$userinfo['name']:'';
        $getUuid = $this->getUuid($data['fd']);
        $data = [
            'status'=>$status,
            'fd' => isset($userinfo['name'])?$userinfo['name']:'',
            'nickname' => isset($userinfo['nickname'])?$userinfo['nickname']:'',        //用户呢称
            'img' => isset($userinfo['img'])?$userinfo['img']:'',                       //用户头像
            'msg' => $msg,
            'bg1' => isset($userinfo['bg1'])?$userinfo['bg1']:'',                       //背景色1
            'bg2' => isset($userinfo['bg2'])?$userinfo['bg2']:'',                       //背景色2
            'font' => isset($userinfo['font'])?$userinfo['font']:'',                    //字颜色
            'level' => isset($userinfo['level'])?$userinfo['level']:'',                 //角色
            'k' => isset($userinfo['userId'])?md5($userinfo['userId']):'',              //用户id
            'nS' => isset($userinfo['noSpeak'])?$userinfo['noSpeak']:'',                //是否能发言
            'anS' => isset($userinfo['allnoSpeak'])?$userinfo['allnoSpeak']:'',        //是否全局不能发言
            'uuid' => isset($userinfo['uuid'])?(string)$userinfo['uuid']:(string)$getUuid['uuid'],        //发言的唯一标实
            'times' => date('H:i:s',time()),                                        //服务器接收到讯息时间
            'time' => isset($userinfo['timess'])?$userinfo['timess']:$getUuid['timess']      //服务器接收到讯息时间
        ];
        if($data['level']==98 || in_array($status,array(4,8,9))){
            $this->updAllkey('his',$userinfo['room'],$data['uuid'],json_encode($data),true);     //写入历史纪录
        }
        $res = json_encode($data,JSON_UNESCAPED_UNICODE);
        return $res;//如果房客存在，把用户组反序列化
    }
    private function getUuid($name=''){
        $timess = (int)(microtime(true)*1000*10000*10000);
        return array('timess'=>$timess,'uuid'=>(string)$timess);
    }
    //检查公告异动
    private function chkNotice($room_id){
//        $redis = Redis::connection();
        $this->redis->select(1);
        $rsKeyH = 'notice';
        if(!$this->redis->exists($rsKeyH.'ing:')){
            $this->redis->setex($rsKeyH.'ing:',10,'on');       //公告异动处理中
            //检查公告异动
            error_log(date('Y-m-d H:i:s',time())." 检查公告=> ".$rsKeyH.'|'.PHP_EOL, 3, '/tmp/chat/notice.log');
            $msg = $this->getChatNotice($room_id);
            $this->sendToAll($room_id, $msg);
            $this->redis->del($rsKeyH.'ing:');
        }
    }
    //检查删除消息
    private function chkDelhis($room_id,$serv){
        $uuid = isset($serv->post['uuid'])?$serv->post['uuid']:$serv->get['uuid'];

        $redis = Redis::connection();
        $this->redis->select(1);
        $rsKeyH = 'delH';
        error_log(date('Y-m-d H:i:s',time())." 检查删除消息=> ".$rsKeyH.'|'.$uuid.PHP_EOL, 3, '/tmp/chat/delHis.log');
        $iRoomInfo = $this->getUsersess($uuid,'','delHis');     //包装删除信息
        $iMsg = $uuid;
        $msg = $this->msg(10, $iMsg, $iRoomInfo);   //删除信息
        $this->sendToAll($room_id, $msg);
    }
    //检查红包异动
    private function chkHongbao($room_id,$serv){
        $hd_idx = isset($serv->post['id'])?$serv->post['id']:$serv->get['id'];

//        $redis = Redis::connection();
        $this->redis->select(1);
        $rsKeyH = 'hb';
        error_log(date('Y-m-d H:i:s',time())." 红包异动=> ".$rsKeyH.'|'.$hd_idx.PHP_EOL, 3, '/tmp/chat/hongbao.log');
        $iRoomInfo = $this->getUsersess($hd_idx,'','hongbao');     //包装红包消息
        $iMsg = (int)$hd_idx;
        $msg = $this->msg(8,$iMsg,$iRoomInfo);   //发送红包异动
        $this->sendToAll($room_id,$msg);
    }
    //检查抢到红包消息
    private function chkHongbaoNum($room_id,$serv){
        $dt_idx = isset($serv->post['hbN'])?$serv->post['hbN']:$serv->get['hbN'];
        $userId = isset($serv->post['userId'])?$serv->post['userId']:$serv->get['userId'];
        $amount = isset($serv->post['amount'])?$serv->post['amount']:$serv->get['amount'];

        //        $this->redis = Redis::connection();
        $this->redis->select(1);
        $rsKeyH = 'hbN';
        if(!$this->redis->exists($rsKeyH.$dt_idx.'ing')){
            $this->redis->setex($rsKeyH.$dt_idx.'ing',30,'on');
            //检查抢到红包消息
            error_log(date('Y-m-d H:i:s',time())." 抢到红包消息every=> ".$rsKeyH.'|'.$dt_idx.'==='.$amount.PHP_EOL, 3, '/tmp/chat/hongbaoNum.log');
            $iRoomInfo = $this->getUsersess($dt_idx,$userId,'hongbaoNum');     //包装计划消息
            $iMsg = $amount;          //把金额提出来
            $msg = $this->msg(9,$iMsg,$iRoomInfo);   //发送抢红包消息
            $this->sendToAll($room_id,$msg);
            $this->redis->del($rsKeyH.'ing');
        }
    }
    //检查计画任务
    private function chkPlan($room_id,$serv){
        $id = isset($serv->post['id'])?$serv->post['id']:$serv->get['id'];
        $valHis = isset($serv->post['pln'])?$serv->post['pln']:$serv->get['pln'];

        //        $this->redis = Redis::connection();
        $this->redis->select(1);
        $rsKeyH = 'pln';
        if(!$this->redis->exists($rsKeyH.$id.'ing')) {
            $this->redis->setex($rsKeyH.$id. 'ing', 30, 'on');
            //检查计划消息
            error_log(date('Y-m-d H:i:s', time()) . " 计划发消息every=> " . $rsKeyH . '++++' . $valHis . PHP_EOL, 3, '/tmp/chat/plan.log');
            $iRoomInfo = $this->getUsersess($valHis, '', 'plan');     //包装计划消息
            $iMsg = base64_decode($iRoomInfo['plans']);             //取出计划消息
            unset($iRoomInfo['plans']);
            //计画消息组合底部固定信息
            $iMsg_back = DB::table('chat_base')->select('plan_msg')->first();
            $iMsg .= urlencode($iMsg_back->plan_msg);
            $msg = $this->msg(2, base64_encode(str_replace('+', '%20', $iMsg)), $iRoomInfo);   //计划发消息
            $this->sendToAll($room_id, $msg);
        }
    }
    //检查消息推送
    private function chkSendC($room_id){
        //        $this->redis = Redis::connection();
        $this->redis->select(1);
        $rsKeyH = 'sendC';                          //中间的消息推送
        $this->redis->setex($rsKeyH.'ing',10,'on');
        $isendC = $this->updAllkey($rsKeyH,$room_id);     //中间的消息推送
        //检查消息人是否在线
        foreach ($isendC as $keyHis =>$valHis) {
            $this->delAllkey($rsKeyH.'='.$keyHis);
            error_log(date('Y-m-d H:i:s',time())." 中消息推送=> ".$rsKeyH.'|'.$keyHis.'==='.$valHis.'++++'.json_encode($isendC).PHP_EOL, 3, '/tmp/chat/sendC.log');
            $usr = $this->redis->HGET($this->chatkey,'usr:'.md5($keyHis));
            if(empty($usr))
                continue;
            $iRoomInfo = $this->getUsersess($valHis,'',$rsKeyH);     //包装消息
            $msg = $this->msg(12,$valHis,$iRoomInfo);   //广播发消息
            $this->push(substr($usr,3), $msg,$room_id);
        }
        $this->redis->del($rsKeyH.'ing');
    }
    //检查消息推送
    private function chkSendR($room_id){
        //        $this->redis = Redis::connection();
        $this->redis->select(1);
        $rsKeyH = 'sendR';                          //右下角的消息推送
        $isendR = $this->updAllkey($rsKeyH,$room_id);     //右下角的消息推送
        //检查消息人是否在线
        foreach ($isendR as $keyHis =>$valHis) {
            $this->delAllkey($rsKeyH.'='.$keyHis);
            error_log(date('Y-m-d H:i:s',time())." 右消息推送=> ".$rsKeyH.'|'.$keyHis.'==='.$valHis.'++++'.json_encode($isendR).PHP_EOL, 3, '/tmp/chat/sendR.log');
            $usr = $this->redis->HGET($this->chatkey,'usr:'.md5($keyHis));
            if(empty($usr))
                continue;
            $iRoomInfo = $this->getUsersess($valHis,'',$rsKeyH);     //包装计划消息
            $msg = $this->msg(11,$valHis,$iRoomInfo);   //广播发消息
            $this->push(substr($usr,3), $msg,$room_id);
        }
    }
    //取得聊天室公告
    private function getChatNotice($room = 1){
        $aNoteceData = DB::table('chat_note')->select('content')->where('room_id',$room)->get();
        $msg = array();
        foreach ($aNoteceData as&$val){
            $msg [] = $val->content;
        }
        $strMsg = $this->msg(6,$msg);
        return $strMsg;
    }
    //取得自己的登陆信息
    private function getMyserf($iSess){
        //        $this->redis = Redis::connection();
        $this->redis->select(1);
        $res = empty($this->redis->get($iSess))?'':(array)json_decode($this->redis->get($iSess));
        return $res;
    }

    //取得会员资讯
    private function getUsersess($iSess,$fd,$type=null){
        //        $this->redis = Redis::connection();
        $this->redis->select(1);
        switch ($type){
            case 'plan':
                $res = (array)json_decode($iSess);
                $res['room'] = 1;                                  //取得房间id
                $res['name'] = '计划任务';                          //名称显示
                $res['level'] = 98;                                //用户层级
                $res['noSpeak'] = 1;                               //用户是否禁言
                $res['type'] = 4;                                  //用户角色-4:计划任务
                $iRoomCss = $this->cssText($res['level'],$res['type']);
                $res['bg1'] = $iRoomCss->bg_color1;                //用户背景颜色1
                $res['bg2'] = $iRoomCss->bg_color2;                //用户背景颜色2
                $res['font'] = $iRoomCss->font_color;              //用户会话文字颜色
                break;
            case 'hongbao':
                $res['room'] = 1;                                  //取得房间id
                $res['name'] = '系统红包';                          //名称显示
                break;
            case 'hongbaoNum':
//                $aHongBao = DB::table('chat_hongbao_dt')->where('chat_hongbao_dt_idx',$iSess)->first();
                $aAllInfo = $this->getIdToUserInfo(md5($fd));
                $res['room'] = 1;                                  //取得房间id
                $res['name'] = $aAllInfo['name'];                  //名称显示
                break;
            case 'delHis':
                $res['room'] = 1;                                  //取得房间id
                $res['name'] = '';                  //名称显示
                break;
            case 'sendR':
                $res['room'] = 1;                                  //取得房间id
                $res['name'] = '';                  //名称显示
                break;
            case 'sendC':
                $res['room'] = 1;                                  //取得房间id
                $res['name'] = '';                  //名称显示
                break;
            default:
                $res = $this->getMyserf($iSess);
                if(empty($res) || !isset($res['userId']))
                    return array();                                 //切换到聊天室库
                $this->updUserInfo($fd,$res);
                $aUsers = DB::table('chat_users')->select('users_id')->where('users_id',$res['userId'])->first();
                $data = array();
                if(empty($aUsers)){                                     //如果从未登入聊天室，则要把信息
                    $resUsers = DB::table('users')->select('testFlag')->where('id',$res['userId'])->first();
                    $data['room_id'] = 1;           //目前一个平台只有一间房
                    $data['users_id'] = $res['userId'];
                    $data['username'] = $res['userName'];
                    $data['updated_at']= date("Y-m-d H:i:s",time());
                    $data['created_at']= date("Y-m-d H:i:s",time());
                    $data['level'] = 1;
                    if(isset($resUsers->testFlag) && $resUsers->testFlag==1){      //判断如果是游客
                        $data['chat_role'] = 1;
                        $data['level'] = 0;
                    }
                    DB::table('chat_users')->insert($data);
                }
                if(empty($res['userId']))
                    return array();
                $aUsers = $this->chkUserSpeak($res['userId'],$data);
                //检查其他sess状态，并删除他们
                $this->chkElseLogin($iSess,$res['userId']);
                $uLv = $aUsers->level;

                $iRoomCss = $this->cssText($uLv,$aUsers->chat_role);
                $res['room'] = $aUsers->room_id;                   //取得房间id
                //如果没有呢称，屏蔽帐号部分字元
                $res['name'] = empty($aUsers->nickname)?substr($res['userName'],0,2).'******'.substr($res['userName'],-2,3):$aUsers->nickname;
                $res['nickname'] = $aUsers->nickname;                 //用户呢称
                $res['level'] = $uLv;                              //用户层级
                $res['noSpeak'] = $aUsers->chat_status;            //用户是否禁言
                $res['allnoSpeak'] = $aUsers->is_speaking?0:1;            //用户是否禁言
                $res['type'] = $aUsers->chat_role;                 //用户角色
                $res['img'] = $aUsers->img;                        //用户头像
                $res['bg1'] = $iRoomCss->bg_color1;                //用户背景颜色1
                $res['bg2'] = $iRoomCss->bg_color2;                //用户背景颜色2
                $res['font'] = $iRoomCss->font_color;              //用户会话文字颜色
                break;
        }
        return $res;
    }
    //检查其他sess状态，并删除他们
    private function chkElseLogin($iSess,$userId){
        //        $this->redis = Redis::connection();
        $this->redis->select(1);
        $arryKeys = $this->redis->keys('*');
        foreach ($arryKeys as $item){
            if($item==$this->chatkey)
                continue;
            try{
                $redisUser = $this->redis->get($item);
                $redisUser = (array)json_decode($redisUser,true);
                if(isset($redisUser['userId'])){
                    if($redisUser['userId']==$userId && $item!=$iSess){
                        $this->redis->del($redisUser['userId']);
                    }
                }
            }catch (\Exception $e){

            }
        }
    }
    //检查发言状态
    private function chkUserSpeak($userid = 0,$aUsersData){
        //重新计算最近2天下注&充值
        $this->setBetRech($userid);
        //获取最近2天下注&充值
        $aUsers = DB::table('chat_users')
            ->select('chat_users.*','chat_room.is_speaking','chat_room.recharge as room_recharge','chat_room.bet as room_bet')
            ->join('chat_room', 'chat_users.room_id', '=', 'chat_room.room_id')
            ->where('users_id',$userid)->first();
        if(empty($aUsers)){
            $aUsers->chat_role = $aUsersData['chat_role'];
            $aUsers->recharge = 0;
            $aUsers->bet = 0;
            $aUsers->isnot_auto_count = 0;
            $aUsers->level = $aUsersData['level'];
        }
        $uLv = $this->chkChat_level($aUsers->chat_role,$aUsers->recharge,$aUsers->bet,$aUsers->isnot_auto_count,$aUsers->level);          //取得用户层级

        DB::table('chat_users')->where('users_id',$userid)->update([
            'level'=> $uLv,
            'updated_at'=> date("Y-m-d H:i:s",time())
        ]);
        //检查是否符合平台的发言条件
        if($aUsers->isnot_auto_count==0)
            $aUsers-> chat_status = ($aUsers->bet >= $aUsers->room_bet || $aUsers->recharge >= $aUsers->room_recharge)?$aUsers-> chat_status:1;
        //检查平台是否开放聊天
        $aUsers-> chat_status = $aUsers->is_speaking==1?$aUsers-> chat_status:1;
        $aUsers->level = $uLv;
        return $aUsers;
    }

    //取代违禁词
    private function setBetRech($userid = 0){
        if(empty($userid))
            return false;
        //重新计算最近2天下注
        $aUserBet = DB::table('bet')->where('user_id',$userid)->whereBetween('created_at',[date("Y-m-d H:i:s",strtotime("-2 day")),date("Y-m-d H:i:s",time())])->sum('bet_money');
        //重新计算最近2天充值
        $aUserRecharges = DB::table('recharges')->where('userId',$userid)->where('status',2)->where('addMoney',1)->whereBetween('created_at',[date("Y-m-d H:i:s",strtotime("-2 day")),date("Y-m-d H:i:s",time())])->sum('amount');
        DB::table('chat_users')->where('users_id',$userid)->update([
            'bet'=> $aUserBet,
            'recharge'=> $aUserRecharges,
            'updated_at'=> date("Y-m-d H:i:s",time())
        ]);
    }

    //取代违禁词
    private function regSpeaking($str){
        $aRegex = DB::table('chat_regex')->select('regex')->get();
        $aRegStr = "";
        foreach ($aRegex as $key => $val){
            $aRegStr .= "(".$val->regex.")|";
        }
        $aRegStr = substr($aRegStr,0,-1);
        $str=preg_replace("/".$aRegStr."/is","***", $str);
        return $str;
    }

    //消息根据群组样式化
    private function cssText($level,$role){
        $aCssColor = DB::table('chat_roles')->select('bg_color1','bg_color2','font_color')
            ->where(function ($query) use ($level,$role){
                if(isset($role)){
                    switch ($role){
                        case 2://如果是会员
                            $query->where("type",2)->where("level",$level);
                            break;
                        default:
                            $query->where("type",$role);
                            break;
                    }
                }
            })->first();
        return $aCssColor;
    }

    //回传用户层级
    private function chkChat_level($role=0,$reg=0,$bet=0,$isnotAuto_count=0,$resLv = 0){
        if($role==3)                //如果是管理员LEVEL无条件给99
            return 99;
        elseif($role==1)            //如果是游客LEVEL无条件给0
            return 0;
        elseif($isnotAuto_count==1) //如果是不自动计算LEVEL无条件给原来设定的
            return $resLv;
        $aLevel = DB::table('chat_level')->get();
        $resLv = 1;
        foreach ($aLevel as $key => $val){
            if($reg >= $val->recharge_min && $bet >= $val->bet_min)
                $resLv = $val->id;
            else
                break;
        }
        return $resLv;
    }

    //取得目前房客资讯
    private function getUserInfo($fd){
        //        $this->redis = Redis::connection();
        $this->redis->select(1);
        if($this->redis->HEXISTS($this->chatkey,'usr'.$fd)){
            $tmp = $this->redis->HGET($this->chatkey,'usr'.$fd);
            if(!$this->is_not_json($tmp))
                return (array)json_decode($this->redis->HGET($this->chatkey,'usr'.$fd));   //如果房客存在，把用户组反序列化
            else
                return '';
        }else
            return '';
    }
    private function is_not_json($str){
        return is_null(json_decode($str));
    }

    /**
     * 更新目前房客资讯
    */
    private function updUserInfo($fd,$iRoomInfo){
        //        $this->redis = Redis::connection();
        $this->redis->select(1);
        $iRoomInfo = (array)$iRoomInfo;
        $room_key = 'usr'.$fd;               //成员房间号码
        $this->redis->multi();
        $this->redis->HSET($this->chatkey,'usr:'.md5($iRoomInfo['userId']),$room_key);         //成员登记他的房间号码
        $this->redis->HSET($this->chatkey,$room_key,json_encode($iRoomInfo,JSON_UNESCAPED_UNICODE));          //成员登记他的房间号码
        $this->redis->exec();

    }
    //注销全局存LIST
    private function delAllkey($addVal,$logo='',$room_id=1){
        switch ($logo){
            case 'usr':
                $addVal = $logo.$addVal;
                break;
            case 'his':
                $addVal = $logo.$room_id.'='.$addVal;
                break;
        }
//        $redis = Redis::connection();
        $this->redis->select(1);
        $this->redis->multi();
        $this->redis->HDEL($this->chatkey,$addVal);
        $this->redis->exec();
    }

    //全局存LIST
    private function updAllkey($logo = 'usr',$iRoomID,$addId = 0,$addVal = 0,$notReturn = false){
        if(in_array($logo,array('usr')))
            $tmpTxt = $logo;
        else if(in_array($logo,array('sendR','sendC')))
            $tmpTxt = $logo.'=';
        else
            $tmpTxt = $logo.$iRoomID.'=';

        //        $this->redis = Redis::connection();
        $this->redis->select(1);
        if(empty($iRoomID))
            return false;
        if(!empty($addId)) {
            if($logo=='his'){
                for($ii=0;$ii<10000;$ii++){
                    $timeIdx = $addId + $ii;
                    error_log(date('Y-m-d H:i:s',time())." 开始循环同时间=> ".$ii.' origin:'.$addId.' after:'.$timeIdx.PHP_EOL, 3, '/tmp/chat/chkHisMsgii.log');
                    if(!$this->redis->HEXISTS($this->chatkey,$tmpTxt.$timeIdx)){
                        if($ii>0){
                            $addId = $timeIdx;
                            $addVal = json_decode($addVal,true);
                            $addVal['time'] = $addId;
                            $addVal = json_encode($addVal,JSON_UNESCAPED_UNICODE);
                            error_log(date('Y-m-d H:i:s',time())." 开始循环同时间()=> ".$ii.'--'.$addId.PHP_EOL, 3, '/tmp/chat/chkHisMsgii.log');
                        }
                        break;
                    }
                }
            }
            $this->redis->multi();
            $this->redis->HSET($this->chatkey,$tmpTxt.$addId,$addVal);
            $this->redis->exec();
            if(!empty($this->tmpChatList))
                $this->tmpChatList[$tmpTxt.$addId]=$addVal;
        }
        if($notReturn)
            return false;
        if(empty($this->tmpChatList))
            $chatList = $this->redis->HGETALL($this->chatkey);
        else
            $chatList = $this->tmpChatList;
        $len = strlen($tmpTxt);

        foreach ($chatList as  $item=>$value){
            if(substr($item,0,$len)==$tmpTxt){
                switch ($logo){
                    case 'usr':         //获取用户
                        $aryValue = (array)json_decode($value);
                        if(isset($aryValue['room']) && $iRoomID==$aryValue['room']){
                            $itemfd = substr($item,$len);
                            $iRoomUsers[$item] = $itemfd;
                        }
                        break;
                    case 'his':         //历史消息
                        $aryValue = (array)json_decode($value);
                        $iRoomUsers[$aryValue['time']] = $value;
                        break;
                    case 'sendR':       //右下角的消息推送
                    case 'sendC':       //中间的消息推送
                        $item = substr($item,$len);
                        $iRoomUsers[$item] = $value;
                        break;
                    default:
                        $iRoomUsers[$item] = $value;
                        break;
                }
            }
        }
        if(empty($iRoomUsers)){
            error_log(date('Y-m-d H:i:s',time())." 重新整理历史讯息All=> ".json_encode($chatList).PHP_EOL, 3, '/tmp/chat/chkHisMsg.log');
            return array();
        }
        return $iRoomUsers;   //获取聊天用户数组，在反序列化回数组
    }
    /**
     * 从md5的用户ID去找到在聊天室的广播号码，在取得每个人的聊天室信息
     */
    private function getIdToUserInfo($k){
        //        $this->redis = Redis::connection();
        $this->redis->select(1);
        $tmpUsr = $this->redis->HGET($this->chatkey,'usr:'.$k)?$this->redis->HGET($this->chatkey,'usr:'.$k):'';                      //从md5的用户ID去找到在聊天室的广播号码
        $tmpUsrInfo = empty($tmpUsr) || !$this->redis->HEXISTS($this->chatkey,$tmpUsr)?'':(array)json_decode($this->redis->HGET($this->chatkey,$tmpUsr));     //从聊天室的广播号码取得每个人的聊天室信息
        return $tmpUsrInfo;
    }

    //重新整理历史讯息
    private function chkHisMsg($iRoomInfo,$fd){
        //        $this->redis = Redis::connection();
        $this->redis->select(1);
        $rsKeyH = 'his';

        $iRoomHisTxt = $this->updAllkey($rsKeyH,$iRoomInfo['room']);     //取出历史纪录
        ksort($iRoomHisTxt);
        //检查计划消息
        error_log(date('Y-m-d H:i:s',time())." 重新整理历史讯息1=> ".$rsKeyH.'|room: '.$iRoomInfo['room'].'-'.json_encode($iRoomHisTxt).PHP_EOL, 3, '/tmp/chat/chkHisMsg.log');
        $timess = (int)(microtime(true)*1000*10000*10000);
        foreach ($iRoomHisTxt as $tmpkey =>$hisMsg) {
            $hisMsg = (array)json_decode($hisMsg);
            if($hisMsg['time'] < ($timess-(7200*1000*10000*10000))){
                $this->delAllkey($hisMsg['uuid'],$rsKeyH,$iRoomInfo['room']);       //删除历史
                continue;
            }
            if(isset($hisMsg['level']) && !empty($hisMsg['level']) && $hisMsg['level'] != 98){
                $aAllInfo = $this->getIdToUserInfo($hisMsg['k']);
                if(isset($aAllInfo['img']) && !empty($aAllInfo['img']) && ($hisMsg['img'] != $aAllInfo['img'])){
                    $hisMsg['img'] = $aAllInfo['img'];
                    $this->updAllkey('his',$iRoomInfo['room'],$hisMsg['uuid'],json_encode($hisMsg),true);     //写入历史纪录
                }
            }
            if(isset($hisMsg['status']) && !in_array($hisMsg['status'],array(8,9))){         //状态非红包
                if($hisMsg['k']==md5($iRoomInfo['userId']))     //如果历史讯息有自己的讯息则调整status = 4
                    $hisMsg['status'] = 4;
                else
                    $hisMsg['status'] = 2;
            }
            $msg = json_encode($hisMsg,JSON_UNESCAPED_UNICODE);
            $this->ws->push($fd, $msg);
        }
    }
}