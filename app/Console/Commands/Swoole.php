<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

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
                $this->clean();
                die;
                break;
            case 'start':
                $this->init();
            default:
                $this->start();
                break;
        }
    }

    /***
     * 初始化
     */
    private function init(){
        //清除用户表
        $keys = $this->redis->keys('chatusr*');
        if(!empty($keys)){
            $this->redis->multi();
            foreach ($keys as $item){
                $this->redis->del($item);
            }
            $this->redis->exec();
        }
        //清除红包ing
        $keys = $this->redis->keys('hbing'.'*');
        if(!empty($keys)) {
            $this->redis->multi();
            foreach ($keys as $item) {
                $this->redis->del($item);
            }
            $this->redis->exec();
        }
        //清除各种ing
        $keys = $this->redis->keys('*'.'ing:'.'*');
        if(!empty($keys)) {
            $this->redis->multi();
            foreach ($keys as $item) {
                $this->redis->del($item);
            }
            $this->redis->exec();
        }

        $files = Storage::disk('chatusr')->files();
        $arrayTmp = [];
        foreach ($files as $hisKey){
            $arrayTmp[] = $hisKey;
        }
        Storage::disk('chatusr')->delete($arrayTmp);              //删除用户在文件的历史数据

        $files = Storage::disk('chatusrfd')->files();
        $arrayTmp = [];
        foreach ($files as $hisKey){
            $arrayTmp[] = $hisKey;
        }
        Storage::disk('chatusrfd')->delete($arrayTmp);              //删除用户在文件的历史数据

        $files = Storage::disk('hongbaoNum')->files();
        $arrayTmp = [];
        foreach ($files as $hisKey){
            $arrayTmp[] = $hisKey;
        }
        Storage::disk('hongbaoNum')->delete($arrayTmp);              //删除红包数据

        //如果资料夹不存在，则创建资料夹
        if(!file_exists(public_path().'/data'))
            mkdir(public_path().'/data');
        if(!file_exists(public_path().'/dataimg'))
            mkdir(public_path().'/dataimg');

        DB::table('chat_online')->truncate();           //聊天室在线记录
    }

    /***
     * 清空数据
     */
    private function clean(){
        $this->redis->select(2);
        $this->redis->flushdb();        //服务每天一启动就要清除之前的聊天室redis
        $this->redis->select(6);
        $this->redis->flushdb();        //服务每天一启动就要清除之前的聊天室redis
        $this->redis->select(1);
        $this->redis->flushdb();        //服务每天一启动就要清除之前的聊天室redis
        $del = DB::table('chat_users')->where('level',0)->delete();
        $files = Storage::disk('chathis')->files();
        $arrayTmp = [];
        foreach ($files as $hisKey){
            $arrayTmp[] = $hisKey;
        }
        Storage::disk('chathis')->delete($arrayTmp);              //删除历史
    }

    public function start(){
        //创建websocket服务器对象，监听0.0.0.0:2021端口
        if(env('WS_HOST_SSL')!='cs'){
            $this->ws = new \swoole_websocket_server("0.0.0.0", env('WS_PORT',2021),SWOOLE_PROCESS, SWOOLE_SOCK_TCP | SWOOLE_SSL);
            $this->ws->set(array(
                'ssl_cert_file' => __DIR__ . '/config/' .env('WS_HOST_SSL','fh').'_ssl.crt',
                'ssl_key_file' => __DIR__ . '/config/' .env('WS_HOST_SSL','fh').'_ssl.key',
            ));
        }else
            $this->ws = new \swoole_websocket_server("0.0.0.0", env('WS_PORT',9501));

        //监听WebSocket连接打开事件
        $this->ws->on('open', function ($ws, $request) {
            DB::disconnect();
            error_log(date('Y-m-d H:i:s',time())." | ".$request->fd." => ".json_encode($request).PHP_EOL, 3, '/tmp/chat/open.log');        //只要连接就记下log
            try{
                $strParam = $request->server;
                $strParam = explode("/",$strParam['request_uri']);      //房间号码
                $iSess = $strParam[1];
                $iRoomInfo = $this->getUsersess($iSess,$request->fd);                 //从sess取出会员资讯
                $this->sendToSerf($request->fd,14,'init');
                if(empty($iRoomInfo) || !isset($iRoomInfo['room'])|| empty($iRoomInfo['room']))                                   //查不到登陆信息或是房间是空的
                    return $this->sendToSerf($request->fd,3,'登陆失效');
                $this->updUserInfo($request->fd,$iRoomInfo,$ws);        //成员登记他的房间号码

                //获取聊天室公告
                $msg = $this->getChatNotice($iRoomInfo['room']);
                $this->ws->push($request->fd, $msg);
                //广播登陆信息
                $msg = $this->msg(1, '进入聊天室', $iRoomInfo);   //进入聊天室
                $this->sendToAll( $iRoomInfo['room'], $msg);
                //检查历史讯息
                $this->chkHisMsg($iRoomInfo,$request->fd);
                //回传自己的基本设置
                if($iRoomInfo['setNickname']==0)
                    $iRoomInfo['nickname'] = '';
                $msg = $this->msg(7,'fstInit',$iRoomInfo);
                $this->ws->push($request->fd, $msg);
            }catch (\Exception $e){
                error_log(date('Y-m-d H:i:s',time()).$e.PHP_EOL, 3, '/tmp/chat/err.log');
            }
        });

        //监听WebSocket消息事件
        $this->ws->on('message', function ($ws, $request) {
            if(substr($request->data,0,6)=="heart="){       //心跳检查
                return true;
            }else if(substr($request->data,0,6)=="token="){
                $iSess = substr($request->data,6,40);
                $uuid = '';
                $type = '';
                if(substr($request->data,46,6)=="&type="){
                    $type = substr($request->data,52,3);
                    if(!substr($request->data,55,6)=="&uuid=")
                        return true;
                    $uuid = substr($request->data,61);
                }else
                    $request->data = substr($request->data,47);
            }
            $iRoomInfo = $this->getUserInfo($request->fd);   //取出他的房间号码
            error_log(date('Y-m-d H:i:s',time())." 发言=> ".$request->fd." => ".json_encode($request).json_encode($iRoomInfo).PHP_EOL, 3, '/tmp/chat/'.date('Ymd').'.log');        //只要连接就记下log
            //登陆失效
            if(!isset($iRoomInfo['room'])|| empty($iRoomInfo['room'])){
                if(isset($iSess)){
                    $iRoomInfo = $this->getUsersess($iSess,$request->fd);                 //从sess取出会员资讯
                    if(empty($iRoomInfo) || !isset($iRoomInfo['room']) || empty($iRoomInfo['room']))
                        return $this->sendToSerf($request->fd,3,'登陆失效');
                }else
                    return $this->sendToSerf($request->fd,3,'登陆失效');
            }
            try{
                $this->updUserInfo($request->fd,$iRoomInfo);        //成员登记他的房间号码
                //获取聊天用户数组
                $iRoomUsers = $this->updAllkey('usr',$iRoomInfo['room']);   //获取聊天用户数组，在反序列化回数组
                if($iRoomInfo['level']==99){
                    if($uuid != '' && $type != ''){
                        $serv = json_decode(json_encode(array()));
                        switch ($type){
                            case 'del':     //删除讯息
                                $this->chkDelHis($iRoomInfo['room'],$serv,$uuid);
                                return true;
                            case 'ons':     //解言
                            case 'uns':     //禁言
                                $fd = Storage::disk('chatusr')->exists('chatusr:'.md5($uuid))?Storage::disk('chatusr')->get('chatusr:'.md5($uuid)):'';
                                $userInfo = $this->getIdToUserInfo(md5($uuid));
                                $userInfo['noSpeak'] = $type=='uns'?1:0;
                                $this->upinfo($userInfo,$fd,json_encode($userInfo));
                                $update = DB::table('chat_users')->where('users_id',$uuid)->update([
                                    'chat_status'=>$userInfo['noSpeak'],
                                    'updated_at'=>date("Y-m-d H:i:s",time())
                                ]);
                                return true;
                            default:
                                return true;
                        }
                    }
                }
                //不广播被禁言的用户
                if($iRoomInfo['noSpeak']==1)
                    return $this->sendToSerf($request->fd,5,'此帐户已禁言');

                $this->redis->select(1);
                if($this->redis->exists($iRoomInfo['userId'].'speaking:')){
                    $iRoomCss = $this->cssText(98,4);
                    $Css['name'] = '系统消息';                          //用户显示名称
                    $Css['level'] = 0;                                //用户背景颜色1
                    $Css['bg1'] = $iRoomCss->bg_color1;                //用户背景颜色1
                    $Css['bg2'] = $iRoomCss->bg_color2;                //用户背景颜色2
                    $Css['font'] = $iRoomCss->font_color;              //用户会话文字颜色
                    $Css['img'] = '/game/images/chat/sys.png';         //用户大头
                    return $this->sendToSerf($request->fd,13,'您说话太快啦，请先休息一会',$Css);
                }
                $this->redis->setex($iRoomInfo['userId'].'speaking:',2,'on');

                //消息过滤HTML标签
                $aMesgRep = urldecode(base64_decode($request->data));
                $aMesgRep = trim ($aMesgRep);
                $aMesgRep = strip_tags ($aMesgRep);
                $aMesgRep = htmlspecialchars ($aMesgRep);
                $aMesgRep = addslashes ($aMesgRep);
                $aMesgRep = str_replace('&amp;', '&', $aMesgRep);
                //消息处理违禁词
                if(empty($iRoomInfo['level'])||$iRoomInfo['level'] != 99)
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
                //自动推送清数据
                $this->chkHisMsg($iRoomInfo,0,false);
            }catch (\Exception $e){
                error_log(date('Y-m-d H:i:s',time()).$e.PHP_EOL, 3, '/tmp/chat/err.log');
            }
        });
        $this->ws->on('receive', function ($ws, $request) {
        });
        //接收WebSocket服务器推送功能
        $this->ws->on('request', function ($serv) {
            $room = isset($serv->post['room'])?$serv->post['room']:(isset($serv->get['room'])?$serv->get['room']:0);
            $type = isset($serv->post['type'])?$serv->post['type']:(isset($serv->get['type'])?$serv->get['type']:'');
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
                case 'upchat':
                    //检查上传图片
                    $this->upchat($serv);
                    break;
                case 'upinfo':
                    //更新个人信息
                    $this->upinfo($serv);
                    break;
                case 'getchatUser':
                    //获得个人信息
                    $this->getUser($serv);
                    break;
                case 'getchatFd':
                    //获得个人信息fd
                    $this->getFd($serv);
                    break;
                case 'setplan':
                    //发送计划任务
                    $this->setPlan($serv);
                    break;
                case 'betInfo':
                    //发送跟单
                    $this->pushBetInfo($serv);
                    break;
                default:
                    break;
            }
        });

        //监听WebSocket连接关闭事件
        $this->ws->on('close', function ($ws, $fd) {
            $this->delAllkey($fd,'usr');   //删除用户
        });

        $this->ws->start();
    }

    //发送计划任务
    private function setPlan($serv){
        $plan = isset($serv->post['data'])?$serv->post['data']:(isset($serv->get['data'])?$serv->get['data']:"");
        if(empty($plan))
            return "";
        $session_id = md5(time().rand(1,10));
        if(empty($session_id))
            return "";

        $aRep =array(
            'userId' => 'plans',
            'plans' => $plan,
            'img' => '/game/images/chat/sys.png'                          //用户头像
        );
        $serv->post['id'] = $session_id;
        $serv->post['pln'] = json_encode($aRep,JSON_UNESCAPED_UNICODE);
        $this->chkPlan(1,$serv);
    }

    //发送跟单
    private function pushBetInfo($serv){
        $sess = isset($serv->post['sess'])?$serv->post['sess']:(isset($serv->get['sess'])?$serv->get['sess']:"");
        $betInfo = isset($serv->post['betInfo'])?$serv->post['betInfo']:(isset($serv->get['betInfo'])?$serv->get['betInfo']:"");
        $issueInfo = isset($serv->post['issueInfo'])?$serv->post['issueInfo']:(isset($serv->get['issueInfo'])?$serv->get['issueInfo']:"");
        if(empty($sess) || empty($betInfo) || empty($issueInfo))
            return "";
        $iRoomInfo = $this->getUsersess($sess);
        if(empty($iRoomInfo) || !isset($iRoomInfo['room'])|| empty($iRoomInfo['room']))                                   //查不到登陆信息或是房间是空的
            return "";
        $iRoomUsers = $this->updAllkey('usr',$iRoomInfo['room']);   //获取聊天用户数组，在反序列化回数组
        //发送消息
        if(!is_array($iRoomInfo))
            $iRoomInfo = (array)$iRoomInfo;
        $getUuid = $this->getUuid($iRoomInfo['name']);
        $iRoomInfo['timess'] = $getUuid['timess'];
        $iRoomInfo['uuid'] = $getUuid['uuid'];
        $iRoomInfo['dt'] = $issueInfo;
        foreach ($iRoomUsers as $fdId =>$val) {
            $msg = $this->msg(15,$betInfo,$iRoomInfo);   //发送跟单内容
            $this->push($val, $msg,$iRoomInfo['room']);
        }
    }

    //获得个人信息
    private function getUser($serv){
        $fd = isset($serv->post['fd'])?$serv->post['fd']:$serv->get['fd'];

        $iRoomInfo = empty($fd) || !Storage::disk('chatusrfd')->exists('chatusrfd:'.$fd)?'':Storage::disk('chatusrfd')->get('chatusrfd:'.$fd);     //从聊天室的广播号码取得每个人的聊天室信息

        DB::table('chat_online')->where('type',2)->where('k',$fd)->delete();      // type 1:chatusr 2:chatusrfd
        $data = array();
        $data['type'] = 2;
        $data['k'] = $fd;
        $data['info_data'] = $iRoomInfo;
        $data['updated_at'] = date('Y-m-d H:i:s');
        DB::table('chat_online')->insert($data);      // type 1:chatusr 2:chatusrfd
    }

    //获得个人信息fd
    private function getFd($serv){
        $k = isset($serv->post['chatusr'])?$serv->post['chatusr']:$serv->get['chatusr'];

        $room_key = Storage::disk('chatusr')->exists('chatusr:'.$k)?Storage::disk('chatusr')->get('chatusr:'.$k):'';                      //从md5的用户ID去找到在聊天室的广播号码
        DB::table('chat_online')->where('type',1)->where('k',$k)->delete();      // type 1:chatusr 2:chatusrfd
        $data = array();
        $data['type'] = 1;
        $data['k'] = $k;
        $data['info_data'] = $room_key;
        $data['updated_at'] = date('Y-m-d H:i:s');
        DB::table('chat_online')->insert($data);      // type 1:chatusr 2:chatusrfd

        $fd = $room_key;
        $iRoomInfo = empty($fd) || !Storage::disk('chatusrfd')->exists('chatusrfd:'.$fd)?'':Storage::disk('chatusrfd')->get('chatusrfd:'.$fd);     //从聊天室的广播号码取得每个人的聊天室信息
        DB::table('chat_online')->where('type',2)->where('k',$fd)->delete();      // type 1:chatusr 2:chatusrfd
        $data = array();
        $data['type'] = 2;
        $data['k'] = $fd;
        $data['info_data'] = $iRoomInfo;
        $data['updated_at'] = date('Y-m-d H:i:s');
        DB::table('chat_online')->insert($data);      // type 1:chatusr 2:chatusrfd
    }

    //推送给自己消息
    private function sendToSerf($fd,$status=13,$msg,$userinfo=array()){
        $msg = $this->msg($status,$msg,$userinfo);
        $this->ws->push($fd, $msg);
    }

    //更新个人信息
    private function upinfo($serv,$fd=0,$userInfo=''){
        $fd = isset($serv->post['fd'])?$serv->post['fd']:(isset($serv->get['fd'])?$serv->get['fd']:$fd);
        $info = isset($serv->post['info'])?$serv->post['info']:(isset($serv->get['info'])?$serv->get['info']:$userInfo);
        if(empty($fd)||empty($info))
            return true;
        Storage::disk('chatusrfd')->put('chatusrfd:'.$fd,$info);
    }

    //检查上传图片
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
        if(!$this->ws->connection_info($fd)){        //检查如果与聊天室服务器断线，则取消发送信息
            $this->delAllkey($fd,'usr');   //删除用户
        }else{
            $this->ws->push($fd, $msg);
        }
    }

    /***
     * 组装回馈讯息
     * $status =>1:进入聊天室 2:别人发言 3:退出聊天室 4:自己发言 5:禁言 6:公告 7:获取自己权限 8:红包 9:抢到红包消息 10:删除讯息 11:右上角消息推送 12:中间消息推送 13:您说话太快啦 14:begin 15:跟单注单
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
            'dt' => isset($userinfo['dt'])?$userinfo['dt']:'',
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
        if((isset($data['level'])&&$data['level']==98) || in_array($status,array(4,8,9))){
            $this->updAllkey('his',$userinfo['room'],$data['uuid'],json_encode($data),'first',true);     //写入历史纪录
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
        $rsKeyH = 'notice';

        //检查公告异动
        error_log(date('Y-m-d H:i:s',time())." 检查公告=> ".$rsKeyH.'|'.PHP_EOL, 3, '/tmp/chat/notice.log');
        $msg = $this->getChatNotice($room_id);
        $this->sendToAll($room_id, $msg);
    }
    //检查删除消息
    private function chkDelhis($room_id,$serv,$sUuid=''){
        $uuid = isset($serv->post['uuid'])?$serv->post['uuid']:(isset($serv->get['uuid'])?$serv->get['uuid']:$sUuid);
        if(empty($uuid))
            return false;
        if(!empty($sUuid))
            $this->delHisInfo('his'.$room_id.'='.$sUuid);

        $rsKeyH = 'delH';
        error_log(date('Y-m-d H:i:s',time())." 检查删除消息=> ".$rsKeyH.'|'.$uuid.PHP_EOL, 3, '/tmp/chat/delHis.log');
        $iRoomInfo = $this->getUsersess($uuid,'','delHis');     //包装删除信息
        $iMsg = $uuid;
        $msg = $this->msg(10, $iMsg, $iRoomInfo);   //删除信息
        $this->sendToAll($room_id, $msg);
    }
    private function delHisInfo($value){
        if(Storage::disk('chathis')->exists($value))
            Storage::disk('chathis')->delete($value);
    }
    //检查红包异动
    private function chkHongbao($room_id,$serv){
        $hd_idx = isset($serv->post['id'])?$serv->post['id']:$serv->get['id'];

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

        $rsKeyH = 'hbN';

        //检查抢到红包消息
        error_log(date('Y-m-d H:i:s',time())." 抢到红包消息every=> ".$rsKeyH.'|'.$dt_idx.'==='.$amount.PHP_EOL, 3, '/tmp/chat/hongbaoNum.log');
        //红包不存在则return
        $getHB = DB::connection('mysql::write')->table('chat_hongbao_dt')->select('chat_hongbao_dt_idx','amount')->where('chat_hongbao_dt_idx',$dt_idx)->first();

        if(empty($getHB) || $amount!=$getHB->amount || Storage::disk('hongbaoNum')->exists('hongbaoNum:'.$dt_idx))
            return false;
        Storage::disk('hongbaoNum')->put('hongbaoNum:'.$dt_idx,$userId);
        $iRoomInfo = $this->getUsersess($dt_idx,$userId,'hongbaoNum');     //包装计划消息
        $iMsg = $amount;          //把金额提出来
        $msg = $this->msg(9,$iMsg,$iRoomInfo);   //发送抢红包消息
        $this->sendToAll($room_id,$msg);
    }
    //检查计画任务
    private function chkPlan($room_id,$serv){
        $id = isset($serv->post['id'])?$serv->post['id']:$serv->get['id'];
        $valHis = isset($serv->post['pln'])?$serv->post['pln']:$serv->get['pln'];
        $game = isset($serv->post['game'])?$serv->post['game']:$serv->get['game'];
        $canSend = false;//是否能发送

        //判断是否可以发送
        $baseSetting = DB::table('chat_base')->where('chat_base_idx',1)->first();
        if($game!=0){
            //判断时间内不开启计画 低于此时间不开启
            if(time() < strtotime(date('Y-m-d '.$baseSetting->send_starttime)) && (time() > strtotime(date('Y-m-d '.$baseSetting->send_endtime)))) return;
            //判断符合的彩种才发送
            $plan_send_game = explode(",",$baseSetting->plan_send_game);
            foreach ($plan_send_game as& $key){
                if ($game == $key) $canSend = true;
            }
            //如果不能发送，就退出
            if (!$canSend) return;
        }

        $rsKeyH = 'pln';

        //检查计划消息
        error_log(date('Y-m-d H:i:s', time()) . " 计划发消息every=> " . $rsKeyH . '++++' . $valHis . PHP_EOL, 3, '/tmp/chat/plan.log');
        $iRoomInfo = $this->getUsersess($valHis, '', 'plan');     //包装计划消息
        $iMsg = base64_decode($iRoomInfo['plans']);             //取出计划消息
        unset($iRoomInfo['plans']);
        //计画消息组合底部固定信息
        $iMsg .= urlencode($baseSetting->plan_msg);
        $msg = $this->msg(2, base64_encode(str_replace('+', '%20', $iMsg)), $iRoomInfo);   //计划发消息
        $this->sendToAll($room_id, $msg);
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
//        $this->redis->select(1);
//        $res = empty($this->redis->get($iSess))?'':(array)json_decode($this->redis->get($iSess));
        $res = (array)DB::table('chat_users')->select('users_id as userId','username as userName')->where('sess',$iSess)->first();
        return $res;
    }

    /**
     * 获取昵称
     */
    private function getNickname($userId){
        $aUsers = DB::table('chat_users')->select('users_id','username','nickname')->where('users_id',$userId)->first();
        $name = empty($aUsers->nickname)?substr($aUsers->username,0,2).'******'.substr($aUsers->username,-2,3):$aUsers->nickname;
        return $name;
    }

    //取得会员资讯
    private function getUsersess($iSess,$fd=0,$type=null){
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
                $aAllInfo = $this->getIdToUserInfo(md5($fd));
                $res['room'] = 1;                                  //取得房间id
                $res['name'] = isset($aAllInfo['name'])?$aAllInfo['name']:@$this->getNickname($fd);                  //名称显示
                break;
            case 'delHis':
                $res['room'] = 1;                                  //取得房间id
                $res['name'] = '';                  //名称显示
                break;
            default:
                $res = $this->getMyserf($iSess);
                if(empty($res) || !isset($res['userId']))
                    return array();                                 //切换到聊天室库
                $aUsers = DB::table('chat_users')->select('users_id')->where('users_id',$res['userId'])->first();
                $data = array();
                if(empty($aUsers)){                                     //如果从未登入聊天室，则要把信息
                    $resUsers = DB::table('users')->select('testFlag')->where('id',$res['userId'])->first();
                    $data['room_id'] = 1;           //目前一个平台只有一间房
                    $data['users_id'] = $res['userId'];
                    $data['username'] = $res['userName'];
                    $data['nickname'] = substr($res['userName'],0,2).'******'.substr($res['userName'],-2,3);
                    $data['updated_at']= date("Y-m-d H:i:s",time());
                    $data['created_at']= date("Y-m-d H:i:s",time());
                    $data['level'] = 1;
                    $data['chat_role'] = 2;
                    if(isset($resUsers->testFlag) && $resUsers->testFlag==1){      //判断如果是游客
                        $data['chat_role'] = 1;
                        $data['level'] = 1;
                    }
                    try{
                        DB::table('chat_users')->insert($data);
                    }catch (\Exception $e){
                        error_log(date('Y-m-d H:i:s',time()).$e.PHP_EOL, 3, '/tmp/chat/err.log');
                    }
                    $data['testFlag'] = isset($resUsers->testFlag)?isset($resUsers->testFlag):0;
                }
                if(empty($res['userId']))
                    return array();
                $aUsers = $this->chkUserSpeak($res['userId'],$data);
                $uLv = $aUsers->level;

                $iRoomCss = $this->cssText($uLv,$aUsers->chat_role);
                $res['room'] = $aUsers->room_id;                   //取得房间id
                //如果没有呢称，屏蔽帐号部分字元
                $res['name'] = !isset($aUsers->nickname)||empty($aUsers->nickname)?substr($res['userName'],0,2).'******'.substr($res['userName'],-2,3):$aUsers->nickname;
                $res['nickname'] = $aUsers->nickname;                 //用户呢称
                $pattern = '/(\*\*\*\*\*\*)/u';
                $matches = preg_match($pattern, $aUsers->nickname);
                $res['setNickname'] = $matches?0:1;
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
    
    //检查发言状态
    private function chkUserSpeak($userid = 0,$aUsersData){
        //重新计算最近2天下注&充值
        $this->setBetRech($userid);
        //获取最近2天下注&充值
        $aUsers = DB::connection('mysql::write')->table('chat_users')
            ->select('chat_users.*','users.testFlag','chat_room.is_speaking','chat_room.recharge as room_recharge','chat_room.bet as room_bet','chat_room.isTestSpeak as room_isTestSpeak')
            ->join('users', 'users.id', '=', 'chat_users.users_id')
            ->join('chat_room', 'chat_users.room_id', '=', 'chat_room.room_id')
            ->where('users_id',$userid)->first();
        $chat_role = isset($aUsers->chat_role)?$aUsers->chat_role:1;
        $recharge  = isset($aUsers->recharge)?$aUsers->recharge:0;
        $bet       = isset($aUsers->bet)?$aUsers->bet:0;
        $isnot_auto_count = isset($aUsers->isnot_auto_count)?$aUsers->isnot_auto_count:0;
        $level     = isset($aUsers->level)?$aUsers->level:1;
        if(empty($aUsers)){
            $aUsers = (object) [];
            $aUsers->chat_status=0;
            $chat_role = isset($aUsersData['chat_role'])?$aUsersData['chat_role']:1;
            $recharge = 0;
            $bet = 0;
            $isnot_auto_count = 0;
            $level = isset($aUsersData['level'])?$aUsersData['level']:1;
        }
        $uLv = $this->chkChat_level($chat_role,$recharge,$bet,$isnot_auto_count,$level);          //取得用户层级

        DB::table('chat_users')->where('users_id',$userid)->update([
            'level'=> $uLv,
            'updated_at'=> date("Y-m-d H:i:s",time())
        ]);
        //流水说话基准
        //检查是否符合平台的发言条件
        if(isset($aUsers->testFlag)){
            switch ($aUsers->testFlag){
                case 2: //测试帐号
                    if($aUsers->room_isTestSpeak==1)
                        $betSpeak = 1;
                    else
                        $betSpeak = ($aUsers->bet >= $aUsers->room_bet || $aUsers->recharge >= $aUsers->room_recharge);
                    break;
                case 1: //游客帐号  预设不能说话
                    $betSpeak = 0;
                    break;
                case 0: //正式帐号
                    $betSpeak = ($aUsers->bet >= $aUsers->room_bet || $aUsers->recharge >= $aUsers->room_recharge);
                    break;
                default:
                    $betSpeak = 0;
                    break;
            }
        }else{
            $betSpeak = 0;
        }
        if($isnot_auto_count==0)
            $aUsers-> chat_status = $betSpeak?$aUsers-> chat_status:1;
        //检查平台是否开放聊天
        $aUsers->chat_status = $aUsers->is_speaking==1?$aUsers-> chat_status:1;
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
        $logVal = 'chatusrfd:'.$fd;
        if(Storage::disk('chatusrfd')->exists($logVal)){
            $tmp = Storage::disk('chatusrfd')->get($logVal);
            if(!$this->is_not_json($tmp))
                return (array)json_decode(Storage::disk('chatusrfd')->get($logVal));   //如果房客存在，把用户组反序列化
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
    private function updUserInfo($fd,$iRoomInfo,$ws=null){
        try{
            $room_key = $fd;               //成员房间号码
            $chatusr = 'chatusr:'.md5($iRoomInfo['userId']);
            Storage::disk('chatusr')->put($chatusr, $room_key);
            Storage::disk('chatusrfd')->put('chatusrfd:'.$room_key,json_encode($iRoomInfo,JSON_UNESCAPED_UNICODE));
            usleep(25000);
        }catch (\Exception $e){
            error_log(date('Y-m-d H:i:s',time()).$e.PHP_EOL, 3, '/tmp/chat/err.log');
        }
    }
    //注销全局存LIST
    private function delAllkey($addVal,$logo=''){
        switch ($logo){
            case 'usr':
                $logVal ='chatusrfd:'.$addVal;
                if(Storage::disk('chatusrfd')->exists($logVal)){
                    $usr = (array)json_decode(Storage::disk('chatusrfd')->get($logVal));
                    if(isset($usr['userId'])){
                        $usrKey = 'chatusr:'.md5($usr['userId']);
                        Storage::disk('chatusrfd')->delete($logVal);              //删除用户
                        try{
                        DB::table('chat_online')->where('type',2)->where('k',$addVal)->delete();
                        }catch (\Exception $e){
                        }
                        if(Storage::disk('chatusr')->exists($usrKey)) {           //检查用户的fd是否存在
                            $usrFd = Storage::disk('chatusr')->get($usrKey);
                            if($addVal==$usrFd)
                                Storage::disk('chatusr')->delete($usrKey);              //删除用户
                        }
                    }
                }
                break;
        }
    }

    //全局存LIST
    private function updAllkey($logo = 'usr',$iRoomID,$addId = 0,$addVal = 0,$type='first',$notReturn = false){
        if(in_array($logo,array('usr')))
            $tmpTxt = 'chatusrfd:';
        else
            $tmpTxt = $logo.$iRoomID.'=';

        if(empty($iRoomID))
            return false;
        if(!empty($addId)) {
            if($logo=='his'){
                $timeIdx = $addId;
                if($type=='first'){         //讯息若是第一次则要判断是否有并发一样的时间
                    for($ii=0;$ii<10000;$ii++){
                        $timeIdx = $addId + $ii;
                        if(!Storage::disk('chathis')->exists($tmpTxt.$timeIdx, $addVal)){
                            if($ii>0){
                                $addId = $timeIdx;
                                $addVal = json_decode($addVal,true);
                                $addVal['time'] = $addId;
                                $addVal = json_encode($addVal,JSON_UNESCAPED_UNICODE);
                            }
                            break;
                        }
                    }
                }
                $write = Storage::disk('chathis')->put($tmpTxt.$timeIdx, $addVal);
            }
        }
        if($notReturn)
            return false;
//        $chatList = $this->redis->HGETALL($this->chatkey);

        $len = strlen($tmpTxt);
        $iRoomUsers = array();
        try{
            switch ($logo){
                case 'usr':         //获取用户
                    $iRoomUsers = array();
                    $files = Storage::disk('chatusrfd')->files();
                    foreach ($files as $value){
                        if(Storage::disk('chatusrfd')->exists($value)){
                            $orgHis = Storage::disk('chatusrfd')->get($value);
                            $aryValue =  (array)json_decode($orgHis);
                            if(isset($aryValue['room']) && $iRoomID==$aryValue['room']){
                                $itemfd = substr($value,$len);
                                $iRoomUsers[$itemfd] = $itemfd;
                            }
                        }
                    }
                    break;
                case 'his':         //历史消息
                    $iRoomUsers = array();
                    $files = Storage::disk('chathis')->files();
                    foreach ($files as $value){
                        if(Storage::disk('chathis')->exists($value)){
                            $orgHis = Storage::disk('chathis')->get($value);
                            $aryHis =  (array)json_decode($orgHis);
                            $iRoomUsers[$aryHis['time']] = $orgHis;
                        }
                    }
                    break;
            }
        }catch (\Exception $e){
            error_log(date('Y-m-d H:i:s',time()).$e.PHP_EOL, 3, '/tmp/chat/err.log');
        }
        return $iRoomUsers;   //获取聊天用户数组，在反序列化回数组
    }
    /**
     * 从md5的用户ID去找到在聊天室的广播号码，在取得每个人的聊天室信息
     */
    private function getIdToUserInfo($k){
        try{
            $tmpUsr = Storage::disk('chatusr')->exists('chatusr:'.$k)?Storage::disk('chatusr')->get('chatusr:'.$k):'';                      //从md5的用户ID去找到在聊天室的广播号码
            $tmpUsrInfo = empty($tmpUsr) || !Storage::disk('chatusrfd')->exists('chatusrfd:'.$tmpUsr)?'':(array)json_decode(Storage::disk('chatusrfd')->get('chatusrfd:'.$tmpUsr));     //从聊天室的广播号码取得每个人的聊天室信息
        }catch (\Exception $e){
            $tmpUsrInfo = '';
        }
        return $tmpUsrInfo;
    }

    //重新整理历史讯息
    private function chkHisMsg($iRoomInfo,$fd=0,$IsPush=true){
        $rsKeyH = 'his';

        $iRoomHisTxt = $this->updAllkey($rsKeyH,$iRoomInfo['room']);     //取出历史纪录
        ksort($iRoomHisTxt);
        //控制两个小时内的数据
        $timess = (int)(microtime(true)*1000*10000*10000);
        //控制聊天室数据
        $needDelnum = count($iRoomHisTxt)-80;
        $needDelnum = $needDelnum > 0 ? $needDelnum : 0;
        $ii = -1;
        //检查计划消息
        try{
            foreach ($iRoomHisTxt as $tmpkey =>$hisMsg) {
                $ii ++;
                $hisKey = $rsKeyH.$iRoomInfo['room'].'='.$tmpkey;
                $hisMsg = (array)json_decode($hisMsg);
                //清除过期或多的数据
                if($hisMsg['time'] < ($timess-(7200*1000*10000*10000)) || $ii < $needDelnum){
                    $serv = (object) [];
                    $serv->post['uuid'] = (string)$tmpkey;
                    $this->chkDelhis($iRoomInfo['room'],$serv);
                    if(Storage::disk('chathis')->exists($hisKey))
                        Storage::disk('chathis')->delete($hisKey);              //删除历史
                    continue;
                }
                //如果需要推送
                if($IsPush && $fd > 0){
                    if(isset($hisMsg['level']) && !empty($hisMsg['level']) && $hisMsg['level'] != 98){
                        $aAllInfo = $this->getIdToUserInfo($hisMsg['k']);
                        if(isset($aAllInfo['img']) && !empty($aAllInfo['img']) && ($hisMsg['img'] != $aAllInfo['img'])){
                            $hisMsg['img'] = $aAllInfo['img'];
                            $this->updAllkey('his',$iRoomInfo['room'],$hisMsg['uuid'],json_encode($hisMsg),'changeImg',true);     //写入历史纪录
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
        }catch (\Exception $e){
            error_log(date('Y-m-d H:i:s',time()).$e.PHP_EOL, 3, '/tmp/chat/err.log');
        }
    }
}