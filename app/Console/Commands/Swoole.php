<?php

namespace App\Console\Commands;

use App\Socket\Exception\SocketApiException;
use App\Socket\Model\ChatBase;
use App\Socket\Model\ChatHongbao;
use App\Socket\Model\ChatHongbaoBlacklist;
use App\Socket\Model\ChatLevel;
use App\Socket\Model\ChatRoles;
use App\Socket\Model\ChatRoom;
use App\Socket\Model\ChatRoomDt;
use App\Socket\Model\ChatSendConfig;
use App\Socket\Model\ChatUser;
use App\Socket\Model\OtherDb\PersonalLog;
use App\Socket\Push;
use App\Socket\Redis\Chat;
use App\Socket\Repository\Action;
use App\Socket\Repository\ChatRoomRepository;
use App\Socket\SwooleEvevts;
use App\Socket\Utility\HttpParser;
use App\Socket\Utility\Parser;
use App\Socket\Utility\Room;
use App\Socket\Utility\Task\TaskManager;
use App\Socket\Utility\Trigger;
use App\Socket\Utility\Users;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use SebastianBergmann\CodeCoverage\Report\PHP;
use SameClass\Config\AdSource\AdSource;

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
//        $keys = $this->redis->keys('chatusr*');
//        if(!empty($keys)){
//            $this->redis->multi();
//            foreach ($keys as $item){
//                $this->redis->del($item);
//            }
//            $this->redis->exec();
//        }
        //清除红包ing
//        $keys = $this->redis->keys('hbing'.'*');
//        if(!empty($keys)) {
//            $this->redis->multi();
//            foreach ($keys as $item) {
//                $this->redis->del($item);
//            }
//            $this->redis->exec();
//        }
        //清除各种ing
//        $keys = $this->redis->keys('*'.'ing:'.'*');
//        if(!empty($keys)) {
//            $this->redis->multi();
//            foreach ($keys as $item) {
//                $this->redis->del($item);
//            }
//            $this->redis->exec();
//        }

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
        Room::clearAllRoom();  # 清聊天室所有数据

//        $AdSource = new AdSource();
//        $ISROOMS = $AdSource->getOneSource('chatType');
//        $ISROOMS = $ISROOMS == '1' ? (int)$ISROOMS : 0;
//        if(!$ISROOMS)
//            DB::table('chat_room_dt')->truncate();           //聊天室在线记录
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
        $del = DB::table('chat_users')->where('level',0)->delete();                         //删除游客在聊天室的纪录
        $del = DB::table('chat_room_dt')->where('user_name', 'like', 'guest_%')->delete();  //删除游客在聊天室的纪录
        $files = Storage::disk('chathis')->files();
        $arrayTmp = [];
        foreach ($files as $hisKey){
            $arrayTmp[] = $hisKey;
        }
        Storage::disk('chathis')->delete($arrayTmp);              //删除历史

        $handler = opendir(public_path().'/dataimg');                                  //删除除了昨天以前的图片
        while(false!==($file=readdir($handler))){
            if(is_numeric($file)&& (int)$file < date('Ymd',strtotime('-1 days')))
                $this->deldir(public_path().'/dataimg/'.$file);
        }
    }

    private function deldir($dir) {
        //先删除目录下的文件：
        $dh = opendir($dir);
        while ($file = readdir($dh)) {
            if($file != "." && $file!="..") {
                $fullpath = $dir."/".$file;
                if(!is_dir($fullpath)) {
                    unlink($fullpath);
                } else {
                    deldir($fullpath);
                }
            }
        }
        closedir($dh);

        //删除当前文件夹：
        if(rmdir($dir)) {
            return true;
        } else {
            return false;
        }
    }

    public function start(){
        swoole_set_process_name(config('swoole.SERVER_NAME')."_manager");
        \App\Socket\SwooleEvevts::initialize();
        //创建websocket服务器对象，监听0.0.0.0:2021端口
        if(env('WS_HOST_SSL')!='cs'){
            $this->ws = new \swoole_websocket_server("0.0.0.0", env('WS_PORT',2021),SWOOLE_PROCESS, SWOOLE_SOCK_TCP | SWOOLE_SSL);
            $this->ws->set([
                'ssl_cert_file' => __DIR__ . '/config/wx-chat-ssl/' .env('WS_HOST_SSL','fh').'_ssl.crt',
                'ssl_key_file' => __DIR__ . '/config/wx-chat-ssl/' .env('WS_HOST_SSL','fh').'_ssl.key',
            ]);
        }else
            $this->ws = new \swoole_websocket_server("0.0.0.0", env('WS_PORT',9501));
        $this->ws->set(config('swoole.MAIN_SERVER.SETTING'));
        //监听WebSocket连接打开事件
        $this->ws->on('open', function ($ws, $request) {
            $this->push($request->fd, 'open');
            \App\Socket\SwooleEvevts::onOpen($ws, $request);
            \App\Socket\Redis1\Redis::exec(REDIS_DB_CHAT_USEROPEN_QUEUE, 'RPUSH', 'openRequest', json_encode($request));

//            DB::disconnect();
//            error_log(date('Y-m-d H:i:s',time())." | ".$request->fd." => ".json_encode($request).PHP_EOL, 3, '/tmp/chat/open.log');        //只要连接就记下log
//            try {
//                $strParam = $request->server;
//                $strParam = explode("/", $strParam['request_uri']);      //房间号码
//                $iSess = $strParam[1];
//                var_dump('start___'.$request->fd.'____'.microtime().'_____'.$iSess);
//                if(empty($iSess))
//                    return $this->sendToSerf($request->fd, 3, '登陆失效');
//                $iRoomInfo = $this->getUsersess($iSess, $request->fd);                 //从sess取出会员资讯
//                if(empty($iRoomInfo)){
//                    return $this->sendToSerf($request->fd, 3, '登陆失效');
//                }
//                $rooms = $iRoomInfo['rooms'];
//                $this->sendToSerf($request->fd, 14, 'init');
//                if (empty($iRoomInfo) || !isset($iRoomInfo['room']) || empty($iRoomInfo['room']))                                   //查不到登陆信息或是房间是空的
//                    return $this->sendToSerf($request->fd, 3, '登陆失效');
//                $this->updUserInfo($request->fd, $iRoomInfo, $ws);        //成员登记他的房间号码
//                # 绑定userId fd
//                Chat::bindUser($iRoomInfo['userId'], $request->fd);
//                Users::checkFdToken($iRoomInfo['userId'], $iRoomInfo['sess']); # 验证用户的所有fd token 如果失效删除所有登录信息
//                foreach ($rooms as $room){
//                    //广播登陆信息，如果三个小时内广播过一次，就不再重复广播
//                    if (!Storage::disk('chatlogintime')->exists(md5($iRoomInfo['userId'])) || Storage::disk('chatlogintime')->get(md5($iRoomInfo['userId'])) < time()){
//                        Storage::disk('chatlogintime')->put(md5($iRoomInfo['userId']),time()+10800);
//                            $msg = $this->msg(1, '进入聊天室', $iRoomInfo);   //进入聊天室
//                            $this->sendToAll($room, $msg);
//                    }
//                }
//                //回传自己的基本设置
//                if($iRoomInfo['setNickname']==0)
//                    $iRoomInfo['nickname'] = '';
//                $msg = $this->msg(7,'fstInit',$iRoomInfo);
//                $this->push($request->fd, $msg);
//                SwooleEvevts::onOpenAfter($request, $iRoomInfo);
//                var_dump('start777777777777___'.$request->fd.'____'.microtime());
//                $AdSource = new AdSource();
//                $ISROOMS = $AdSource->getOneSource('chatType');
//                $ISROOMS = $ISROOMS == '1' ? (int)$ISROOMS : 0;
//                # 如果是老聊天室 默认打开1聊天室
//                if(!$ISROOMS){
//                    $room_dt = DB::table('chat_room_dt')->where('id',1)->where('user_id',$iRoomInfo['userId'])->first();
//                    if(empty($room_dt)){
//                        $room1data['id'] = 1;
//                        $room1data['user_id'] = $iRoomInfo['userId'];
//                        $room1data['user_name'] = $iRoomInfo['userName'];
//                        $room1data['is_speaking'] = 1;
//                        $room1data['is_pushbet'] = 0;
//                        $room1data['created_at'] = date('Y-m-d H:i:s');
//                        $room1data['updated_at'] = date('Y-m-d H:i:s');
//                        DB::table('chat_room_dt')->insert($room1data);      // type 1:chatusr 2:chatusrfd
//                    }
//                    $this->inRoom(1, $request->fd, $iRoomInfo, $iSess);
//                }
//            }catch (\Exception $e){
//                Trigger::getInstance()->throwable($e);
////                error_log(date('Y-m-d H:i:s',time()).$e.PHP_EOL, 3, '/tmp/chat/err.log');
//            }
        });
        $this->ws->on('start', [\App\Socket\SwooleEvevts::class, 'onStart']);
        $this->ws->on('Task',function($serv, \Swoole\Server\Task $task){
            \App\Socket\SwooleEvevts::onTask($serv, $task);
        });
        $this->ws->on('finish', function($ws){});
        $this->ws->on('workerStart', function($server, $id){
            \App\Socket\SwooleEvevts::onWorkerStart($server, $id);
        });
        //监听WebSocket消息事件
        $this->ws->on('message', function ($ws, $request) {
            if(substr($request->data,0,6)=="heart="){       //心跳检查
                $this->push($request->fd,'pong');
                return true;
            }else if(substr($request->data,0,6)=="token="){
                $iSess = substr($request->data,6,40);
                $uuid = '';
                $type = '';
                if(substr($request->data,46,6)=="&type="){
                    $type = substr($request->data,52,3);
                    if(substr($request->data,55,6)=="&uuid="){
                        $uuid = substr($request->data,61);
                    }elseif(substr($request->data,55,8)=="&roomId="){
                        $roomId = (int)substr($request->data,63);
                    }elseif(substr($request->data,55,9)=="&message="){
                        $messageInfo = substr($request->data,64);
                    }else{
                        return true;
                    }
                }else
                    $request->data = substr($request->data,47);
            }
            $iRoomInfo = $this->getUserInfo($request->fd);   //取出他的房间号码
            error_log(date('Y-m-d H:i:s',time())." 发言=> ".$request->fd." => ".json_encode($request).json_encode($iRoomInfo).PHP_EOL, 3, '/tmp/chat/'.date('Ymd').'.log');        //只要连接就记下log
            //登陆失效
            if(!isset($iRoomInfo['userId'])|| empty($iRoomInfo['userId'])){
                if(isset($iSess)){
                    $iRoomInfo = $this->getUsersess($iSess,$request->fd);                 //从sess取出会员资讯
                    if(empty($iRoomInfo) || !isset($iRoomInfo['userId']) || empty($iRoomInfo['userId']))
                        return $this->sendToSerf($request->fd,3,'登陆失效');
                }else
                    return $this->sendToSerf($request->fd,3,'登陆失效');
            }
            try{
                $this->updUserInfo($request->fd,$iRoomInfo);        //成员登记他的房间号码
                if(isset($roomId) && $roomId > 0 && $type == 'inr'){
                    return $this->inRoom($roomId ?? 1, $request->fd, $iRoomInfo, $iSess);
                }
                if(isset($messageInfo) && strlen($messageInfo) > 0 && $type == 'ins'){
                    try{
                        return (new Parser($ws, $request, $messageInfo, $iSess))->run($iRoomInfo);
                    }catch (\Throwable $e){
                        throw $e;

                    }
                }

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
                //获取聊天类型
                if(($userStatus = Room::getFdStatus($request->fd)) && isset($userStatus['type'])){
                    Action::sendMessage($request->fd, $userStatus['type'], $userStatus['id'], $request->data, $iRoomInfo);
                }

            }catch (\Exception $e){
                # 在这里捕获SocketApi的异常 推送给客户端
                if($e instanceof SocketApiException){
                    $this->sendToSerf($request->fd, 26, $e->getMessage());
                    return '';
                }
                Trigger::getInstance()->throwable($e);
            }
        });
        //接收WebSocket服务器推送功能
        $this->ws->on('request', function ($serv, $response) {
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
                case 'planBetInfo':
                    //发送跟单
                    $this->pushBetInfo($serv);
                    break;
                default: # 如果全都没有
                    $this->httpParser($serv, $response);
                    break;
            }
        });

        //监听WebSocket连接关闭事件
        $this->ws->on('close', function ($ws, $fd) {
            $this->delAllkey($fd,'usr');   //删除用户
        });
        //绑定自己
        \Illuminate\Container\Container::getInstance()->instance('swoole', $this);
        \App\Socket\SwooleEvevts::mainServerCreate();
        $this->ws->start();
    }

    private function httpParser($serv, $response)
    {
        try {
            if(!(new HttpParser($serv, $response))->run()){

            }
        }catch (\Throwable $e){
            Trigger::getInstance()->throwable($e);
            $response->end(json_encode([
                'code' => 500,
                'msg' => 'error',
                'data' => []
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }
    }
    //进入房间
    public function inRoom($roomId, $fd, $iRoomInfo, $iSess)
    {
        try{
            //获取聊天室公告
            $msg = $this->getChatNotice($roomId);
            $this->push($fd, $msg);
            # 房间信息
            $roomInfo = $rooms = DB::table('chat_room')->where('room_id', $roomId)->first();
            if((!$roomInfo || $roomInfo->is_open !== 1)){
                if(!$roomInfo){
                    # 删除房间信息
                    Room::delHistoryChatList($iRoomInfo['userId'], 'room', $roomId);
                    # 更新些人房间列表
                    Push::pushUser($iRoomInfo['userId'], 'HistoryChatList');
//                    ChatRoomRepository::delRoom($roomId);
                }
                throw new \Exception('房间暂未开启', 203);
            }

            if((array_search((string)$roomId, (array)$iRoomInfo['rooms']) === false) || !ChatRoomDt::getOne([
                    'user_id' => $iRoomInfo['userId'],
                    'id' => $roomId
                ])){# 不在房间
                # 是否可以快速加入
                if($roomInfo->is_auto || in_array($roomId, [1, 2])){
                    if(!$this->addRoom($roomId, $iRoomInfo, $fd))
                        throw new \Exception('加入房间失败', 203);
                }else{
                    throw new \Exception('您不在当前房间中', 203);
                }
            }
            # 进入房间
            Room::joinRoom($roomId, $fd, $iRoomInfo);
            # 从sess取出会员资讯 切换聊天室后需要更新下用户各种权限信息
            $iRoomInfo = $this->getUsersess($iSess,$fd);
            # 更新目前房客资讯
            $this->updUserInfo($fd,$iRoomInfo);
            //回传自己的基本设置
            if($iRoomInfo['setNickname']==0)
                $iRoomInfo['nickname'] = '';
            $msg = $this->msg(7,'fstInit',$iRoomInfo);
            $this->push($fd, $msg);
            # 历史讯息
//            Push::pushRoomLog($fd, $iRoomInfo, $roomId);
            # 如果进入的房间是2把快速进入的房间列表显示出来
            if($roomId == 2){
                $data = [];
                # 欢迎语
//                $data['guan_msg'] = DB::table('chat_base')->value('guan_msg');
                $data['guan_msg'] = ChatBase::getValue('guan_msg');
                # 房间列表及信息
                $data['rooms'] = ChatRoom::getRoomList([
                    'is_open' => 1,
                    'is_auto' => 1,
                ], ['room_id', 'room_name', 'is_auto', 'is_speaking', 'recharge', 'bet', 'isTestSpeak']);
//                $data['rooms'] = DB::table('chat_room')
//                    ->select('room_id', 'room_name', 'is_auto', 'is_speaking', 'recharge', 'bet', 'isTestSpeak')
//                    ->where('is_open', 1)
////                    ->where('roomtype', 2)
//                    ->where('is_auto', 1)->get();
                $msg = $this->json(19,$data);
                $this->push($fd, $msg);
            }
            return true;
        }catch (\Throwable $e){
            if($e->getCode() == 203){
//                $msg = $this->json(17,$e->getMessage());
//                $this->push($fd, $msg);
                $msg = $this->msg(5,$e->getMessage());
                $this->push($fd, $msg);
            }else{
                throw $e;
            }
            return false;
        }
    }

    //发送计划任务
    public function setPlan($serv){
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
        $betArr = json_decode(urldecode(base64_decode($betInfo)), 1);
        if(\App\Socket\Pool\RedisPool::invoke(function (\App\Socket\Pool\RedisObject $redis) use($sess){
            $redis->select(0);
            return $redis->get('planBetInfo_md5') == $sess;
        })){
            $iRoomInfo = $this->getUsersess((array)$sess, 0, 'plan');
        }else{
            $iRoomInfo = $this->getUsersess($sess);
        }

        if(empty($iRoomInfo) || !isset($iRoomInfo['room'])|| empty($iRoomInfo['room']))                                   //查不到登陆信息或是房间是空的
            return "";

        # 获取需要推送的房间
        $getUuid = app('swoole')->getUuid($iRoomInfo['name']);
        if(!is_array($iRoomInfo))
            $iRoomInfo = (array)$iRoomInfo;
        foreach (ChatRoom::getPushBetInfoRooms($betArr['gameId']) as $roomId){
            $iRoomInfo['timess'] = $getUuid['timess'];
            $iRoomInfo['uuid'] = $getUuid['uuid'];
            $iRoomInfo['dt'] = $issueInfo;
            $iRoomInfo['room'] = $roomId;
            $msg = $this->msg(15,$betInfo,$iRoomInfo);   //发送跟单内容
            TaskManager::async(function() use($iRoomInfo, $issueInfo, $roomId, $msg){
                try{
                    //发送消息
                    $fds = Room::getRoomFd($roomId); # 获取在群组里的所有fd
                    foreach ($fds as $fdId =>$val) {
                        app('swoole')->push($val, $msg);
                    }
                }catch (\Throwable $e){
                    Trigger::getInstance()->throwable($e);
                }
            });
        }

//        $iRoomUsers = $this->updAllkey('usr',$iRoomInfo['room']);   //获取聊天用户数组，在反序列化回数组
//        //发送消息
//        if(!is_array($iRoomInfo))
//            $iRoomInfo = (array)$iRoomInfo;
//        $getUuid = $this->getUuid($iRoomInfo['name']);
//        $iRoomInfo['timess'] = $getUuid['timess'];
//        $iRoomInfo['uuid'] = $getUuid['uuid'];
//        $iRoomInfo['dt'] = $issueInfo;
//        foreach ($iRoomUsers as $fdId =>$val) {
//            $msg = $this->msg(15,$betInfo,$iRoomInfo);   //发送跟单内容
//            $this->push($val, $msg,$iRoomInfo['room']);
//        }
    }

    //获得个人信息
    public function getUser($serv){
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
    public function getFd($serv){
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
    public function sendToSerf($fd,$status=13,$msg,$userinfo=array()){
        $msg = $this->msg($status,$msg,$userinfo);
        $this->push($fd, $msg);
    }

    //更新个人信息
    public function upinfo($serv,$fd=0,$userInfo=''){
        $fd = isset($serv->post['fd'])?$serv->post['fd']:(isset($serv->get['fd'])?$serv->get['fd']:$fd);
        $info = isset($serv->post['info'])?$serv->post['info']:(isset($serv->get['info'])?$serv->get['info']:$userInfo);
        if(empty($fd)||empty($info))
            return true;
        Storage::disk('chatusrfd')->put('chatusrfd:'.$fd,$info);
    }

    //检查上传图片
    public function upchat($serv){
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
    public function sendToAll($room_id,$msg){
//        $iRoomUsers = $this->updAllkey('usr',$room_id);   //获取聊天用户数组，在反序列化回数组
        $fds = Room::getRoomFd($room_id); # 获取在群组里的所有fd
        foreach ($fds ?? [] as $usrfdId =>$fdId) {
            $this->push( $fdId, $msg,$room_id);

            # 如果是发消息 记录用户聊过的列表
            # 会员是不是正在这个聊天环境 如果是状态改为已读
            if($json = json_decode($msg, 1)){
                if(in_array($json['status'], [
                    2, 4
                ])){
                    $lookNum = 1;
                    $s = Room::getFdStatus($fdId);
                    if($s && $s['type'] == 'room' && $s['id'] == $room_id){
                        $lookNum = 0;
                    }
                    Room::setHistoryChatList(Room::getUserId($fdId), 'room', $room_id, ['lookNum' => $lookNum]);
                }
            }
        }
    }
    //检查如果与聊天室服务器断线，则取消发送信息
    public function push($fd,$msg){
        try{
            if(!$this->ws->connection_info($fd)){        //检查如果与聊天室服务器断线，则取消发送信息
                $this->delAllkey($fd,'usr');   //删除用户
                return false;
            }else{
                return $this->ws->push($fd, $msg);
            }
        }catch (\Throwable $e){
            $this->delAllkey($fd,'usr');   //删除用户
            Trigger::getInstance()->throwable($e);
            return false;
        }
    }

    /***
     * 组装回馈讯息
     * $status =>1:进入聊天室 2:别人发言 3:退出聊天室 4:自己发言 5:禁言 6:公告 7:获取自己权限 8:红包 9:抢到红包消息
     * 10:删除讯息 11:右上角消息推送 12:中间消息推送 13:您说话太快啦 14:begin 15:跟单注单 16:房间列表 17:没有权限
     * 18:加入房间成功 19:可以快速进入的房间列表与欢迎语 20:用户聊过天的房间和好友
     * 21:添加好友请求  22首页所有的列表
     */
    public function msg($status,$msg,$userinfo = array(), $type = 'room', $id = null){
        $data = $this->msgBuild(...func_get_args());
        if((isset($data['level'])&&$data['level']==98) || (in_array($status,array(4,8,9)) && $data['toId']!==2 && $type == 'room') || $status==15){
//            $this->updAllkey('his',$userinfo['room'],$data['uuid'],json_encode($data),true);     //写入历史纪录
            TaskManager::async(function() use($data){
                PersonalLog::insertMsgLog($data);
            });
        }
        $res = json_encode($data,JSON_UNESCAPED_UNICODE);
        return $res;//如果房客存在，把用户组反序列化
    }
    public function msgBuild($status,$msg,$userinfo = array(), $type = 'room', $id = null, $roomId = 0)
    {
        if(!is_array($userinfo))
            $userinfo = (array)$userinfo;
        $data['fd'] = isset($userinfo['name'])?$userinfo['name']:'';
        $getUuid = $this->getUuid($data['fd']);
        $data = [
            'status'=>$status,
            'fd' => isset($userinfo['name'])?$userinfo['name']:'',
            'nickname' => isset($userinfo['nickname'])?$userinfo['nickname']:'',        //用户呢称
            'img' => isset($userinfo['img'])&&!empty($userinfo['img'])?$userinfo['img']:'/game/images/chat/avatar.png',                       //用户头像
            'msg' => $msg,
            'dt' => isset($userinfo['dt'])?$userinfo['dt']:'',
            'bg1' => isset($userinfo['bg1'])?$userinfo['bg1']:'',                       //背景色1
            'bg2' => isset($userinfo['bg2'])?$userinfo['bg2']:'',                       //背景色2
            'font' => isset($userinfo['font'])?$userinfo['font']:'',                    //字颜色
            'level' => isset($userinfo['level'])?$userinfo['level']:'',                 //角色
            'k' => isset($userinfo['userId'])?md5($userinfo['userId']):'',              //用户id
            'nS' => isset($userinfo['noSpeak'])?(string)$userinfo['noSpeak']:'',                //是否能发言
            'anS' => isset($userinfo['allnoSpeak'])?(string)$userinfo['allnoSpeak']:'',        //是否全局不能发言
            'uuid' => isset($userinfo['uuid'])?(string)$userinfo['uuid']:(string)$getUuid['uuid'],        //发言的唯一标实
            'times' => date('H:i:s',time()),                                        //服务器接收到讯息时间
            'time' => isset($userinfo['timess'])?$userinfo['timess']:$getUuid['timess'],      //服务器接收到讯息时间
//            'roomId' => isset($userinfo['room'])?$userinfo['room']:1,     //房间号码
            'type' => $type,
            'toId' => $id ? $id : ($type == 'room' ? (isset($userinfo['room']) ? $userinfo['room'] : 0) : 0), //目标id
            'user_id' => $userinfo['userId'] ?? 0,
            'created_at' => date('Y-m-d H:i:s'),
            'roomId' => $roomId
        ];
        isset($data['user_id']) && $data['user_id'] > 0 && $data['userMap'] = Users::getUserMap($id, $userinfo['userId']);
        return $data;
    }

    public function json($status, $data)
    {
        return json_encode([
            'status' => $status,
            'msg' => json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'data' => $data
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function sendFd($fd, $status, $data)
    {
        $fd && $this->push($fd, $this->json($status, $data));
    }

    //推送user
    public function sendUser($userId, $status, $data)
    {
        $fds = Chat::getUserFd($userId);
        foreach ($fds as $fd){
            $fd && $this->push($fd, $this->json($status, $data));
        }
    }

    public function getUuid($name=''){
        $timess = (int)((microtime(true)*10000-15147360000000)*10000);
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
        if(!empty($sUuid)){
            PersonalLog::delRawLog([
//                'type' => $serv->post['type'] ?? $serv->get['type'] ?? 'room',
//                'toId' => $serv->post['toId'] ?? $serv->get['toId'] ?? 1,
                'idx' => $uuid,
            ]);
        }

//            $this->delHisInfo('his'.$room_id.'='.$sUuid);

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
        $iRoomInfo = $this->getUsersess($hd_idx,'','hongbao', $room_id);     //包装红包消息
        $iMsg = (int)$hd_idx;
        $msg = $this->msg(8,$iMsg,$iRoomInfo);   //发送红包异动
//        $this->sendToAll($room_id,$msg);
        Room::sendRoomSystemMsg($room_id, $msg, \App\Socket\Utility\Language::hongbaolastMsg);
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
        $getHB = DB::connection('mysql::write')->table('chat_hongbao_dt')->select('chat_hongbao_dt_idx','amount','hongbao_idx')->where('chat_hongbao_dt_idx',$dt_idx)->first();

        if(empty($getHB) || $amount!=$getHB->amount || Storage::disk('hongbaoNum')->exists('hongbaoNum:'.$dt_idx))
            return false;
        // 查红包的房间id
        $roomId = ChatHongbao::getValue(['chat_hongbao_idx' => $getHB->hongbao_idx], 'room_id');

        Storage::disk('hongbaoNum')->put('hongbaoNum:'.$dt_idx,$userId);
        $iRoomInfo = $this->getUsersess($dt_idx,$userId,'hongbaoNum', $roomId);     //包装计划消息
        $iMsg = $amount;          //把金额提出来
        $msg = $this->msg(9,$iMsg,$iRoomInfo);   //发送抢红包消息

        $this->sendToAll($room_id,$msg);
    }
    //检查计划任务
    private function chkPlan($room_id,$serv){
        writeLog('chkPlan', 'post:'.json_encode($serv->post, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        writeLog('chkPlan', 'get:'.json_encode($serv->get, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $id = isset($serv->post['id'])?$serv->post['id']:$serv->get['id'];
        $valHis = isset($serv->post['pln'])?$serv->post['pln']:$serv->get['pln'];
        $game = isset($serv->post['game'])?$serv->post['game']:$serv->get['game'];
        $plantype = isset($serv->post['plantype'])?$serv->post['plantype']:(isset($serv->get['plantype'])?$serv->get['plantype']:'');
        //判断统一杀率计划是否与
        if($plantype=='guan'){
            $res = DB::table('excel_base')->select('is_user')->where('game_id',$game)->where('is_user',0)->first();       //要在平台检查是不是走统一杀率，是的才能接入统一杀率计划
            if(empty($res)) return;
        }else if ($plantype=='ziying'){
            $res = DB::table('excel_base')->select('is_user')->where('game_id',$game)->where('is_user',1)->first();       //要在平台检查是不是走统一杀率，是的才能接入统一杀率计划
            if(empty($res)) return;
        }
        //判断是否可以发送
        $baseSetting = DB::table('chat_base')->where('chat_base_idx',1)->first();
//        if($game!=0){
//            //判断时间内不开启计划 低于此时间不开启
//            if(time() > strtotime(date('Y-m-d '.$baseSetting->send_starttime)) || (time() < strtotime(date('Y-m-d '.$baseSetting->send_endtime)))) return;
//        }

        $rsKeyH = 'pln';
        error_log(date('Y-m-d H:i:s', time()) . " 计划发消息every=> " . $rsKeyH . '++++' . json_encode($valHis) . PHP_EOL, 3, '/tmp/chat/plan.log');

        # 拿所有要推送的房间
        $key = 'planSendGame'.$game;
        if(!$rooms = cache($key)){
            $rooms = DB::table('chat_room')->select('room_id')->whereRaw('FIND_IN_SET("'.$game.'",planSendGame)')->get();
            cache([$key=>$rooms], 1); # 缓存
        }
        $valHis = json_decode($valHis, 1);
        writeLog('chkPlan', '$rooms'.json_encode($rooms, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        foreach ($rooms as $v){
            if($game!=0){
                //判断时间内不开启计划 低于此时间不开启
                if(!$this->checkoutSendPlan((int)$v->room_id)){
                    continue;
                }
            }
            $valHis['room'] = $v->room_id;
            //检查计划消息
            $iRoomInfo = app('swoole')->getUsersess($valHis, '', 'plan');     //包装计划消息
            $iMsg = base64_decode($iRoomInfo['plans']);             //取出计划消息
            unset($iRoomInfo['plans']);
            //计划消息组合底部固定信息
            $iMsg .= urlencode($baseSetting->plan_msg);
            $msg = app('swoole')->msg(2, base64_encode(str_replace('+', '%20', $iMsg)), $iRoomInfo);   //计划发消息

            TaskManager::async(function()use($rsKeyH, $baseSetting, $valHis, $msg){
                 Room::sendRoomSystemMsg($valHis['room'], $msg, '计划消息');
           });
//            $this->sendToAll($valHis['room'], $msg);
        }
    }
    //检查发布计划时间
    public function checkoutSendPlan($roomId)
    {
        $list = ChatSendConfig::get($roomId);
        $pdate = date('Y-m-d ');
        $time = time();
        foreach ($list as $v){
            $start = strtotime($pdate.$v['send_starttime'].':00');
            $end =  strtotime($pdate.$v['send_endtime'].':00');
            if($time <= $end && $time >= $start){
                return true;
            }
        }
        return false;
    }
    //取得聊天室公告
    private function getChatNotice($room = 1){
        $AdSource = new AdSource();
        $ISROOMS = $AdSource->getOneSource('chatType');
        $ISROOMS = $ISROOMS == '1' ? (int)$ISROOMS : 0;
        if($ISROOMS)
            $aNoteceData = DB::select("select content from chat_note where (`room_id` = {$room}) OR FIND_IN_SET('{$room}',rooms)");
        else
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
    public function getUsersess($iSess,$fd=0,$type=null, $room_id = 1){
        switch ($type){
            case 'plan':
                $res = $iSess;
                $res['room'] = $res['room'] ?? 1;                          //取得房间id
                $res['userId'] = 'plans';                                  //Plan id
                $res['img'] = '/game/images/chat/sys.png';                 //用户头像
                $res['name'] = '计划小帮手';                          //名称显示
                $res['level'] = 98;                                //用户层级
                $res['noSpeak'] = 1;                               //用户是否禁言
                $res['type'] = 4;                                  //用户角色-4:计划任务
                $iRoomCss = $this->cssText($res['level'],$res['type']);
                $res['bg1'] = $iRoomCss->bg_color1;                //用户背景颜色1
                $res['bg2'] = $iRoomCss->bg_color2;                //用户背景颜色2
                $res['font'] = $iRoomCss->font_color;              //用户会话文字颜色
                break;
            case 'hongbao':
                $res['room'] = $room_id;                                  //取得房间id
                $res['name'] = '系统红包';                          //名称显示
                break;
            case 'hongbaoNum':
                $aAllInfo = $this->getIdToUserInfo(md5($fd));
                $res['room'] = $room_id;                                  //取得房间id
                $res['name'] = isset($aAllInfo['name'])?$aAllInfo['name']:@$this->getNickname($fd);                  //名称显示
                break;
            case 'delHis':
                $res['room'] = $room_id;                                  //取得房间id
                $res['name'] = '';                  //名称显示
                break;
            default:
                $res = $this->getMyserf($iSess);
                if(empty($res) || !isset($res['userId']))
                    return array();                                 //切换到聊天室库
//                $aUsers = DB::table('chat_users')->select('users_id')->where('users_id',$res['userId'])->first();
                $aUsers = (object) ChatUser::getUser([
                    'users_id' => $res['userId'],
                ]);
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
                $res['rooms'] = explode(',', $aUsers->rooms);                   //取得房间id
//                $res['rooms'] = array_values(array_diff(array_unique(array_merge(explode(',', $res['rooms']), ['1','2'])), [''])); # 并入房间1 去重 去掉空的 重置索引避免数组变对象
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
                $res['sess'] = $iSess; # 保存sess 用来验证token失效
                $this->autoInRoom($res, $fd); //进入房间
                break;
        }
        return $res;
    }

    /**
     * 自动加入房间 加入完后会在$iRoomInfo rooms推入加入房间的id 所以会更新$iRoomInfo
     * @param $iRoomInfo
     * @param $fd
     */
    public function autoInRoom(&$iRoomInfo, $fd)
    {
        //默认加入1、2房间
        $arr = [1,2];
        foreach ($arr as $v){
            (!in_array($v, $iRoomInfo['rooms']) || !ChatRoomDt::getOne([
                    'user_id' => $iRoomInfo['userId'],
                    'id' => $v
                ])) &&
            ($this->addRoom($v,$iRoomInfo, $fd));
        }
    }
    
    //检查发言状态
    private function chkUserSpeak($userid = 0,$aUsersData){
        //重新计算最近30天下注&充值
        $this->setBetRech($userid);
        //获取最近2天下注&充值
//        $aUsers = DB::connection('mysql::write')->table('chat_users')
//            ->select('chat_users.*','users.testFlag','chat_room.is_speaking','chat_room.recharge as room_recharge','chat_room.bet as room_bet','chat_room.isTestSpeak as room_isTestSpeak')
//            ->join('users', 'users.id', '=', 'chat_users.users_id')
//            ->join('chat_room', 'chat_users.room_id', '=', 'chat_room.room_id')
//            ->where('users_id',$userid)->first();
//        $aUsers = (object)\App\Socket\Pool\MysqlPool::invoke(function (\App\Socket\Pool\MysqlObject $db) use($userid) {
//            return $db->join('users', 'users.id = chat_users.users_id')
//                ->join('chat_room', 'chat_users.room_id = chat_room.room_id')
//                ->where('users_id',$userid)
//                ->getOne('chat_users', ['chat_users.*','users.testFlag','chat_room.is_speaking','chat_room.recharge as room_recharge','chat_room.bet as room_bet','chat_room.isTestSpeak as room_isTestSpeak']);
//        });
        $aUsers = (object)ChatUser::getUserBetRechargeInfo([
            'users_id' => $userid
        ]);
        # 如果没找到可能房间被删除了，也可能这个会员是单聊还没加入房间 因为这里不知道怎么改先给个默认的
        if(empty((array)$aUsers)){
            $aUsers = (object)ChatUser::getUser(['users_id' => $userid], true);
            $aUsers->is_speaking = 0;
        }
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

//        DB::table('chat_users')->where('users_id',$userid)->update([
//            'level'=> $uLv,
//            'updated_at'=> date("Y-m-d H:i:s",time())
//        ]);
        \App\Socket\Pool\MysqlPool::invoke(function (\App\Socket\Pool\MysqlObject $db) use($userid, $uLv) {
            $db->where('users_id',$userid)
                ->update('chat_users', [
                     'level'=> $uLv,
                    'updated_at'=> date("Y-m-d H:i:s",time())
                ]);
        });

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
            $aUsers->chat_status = $betSpeak?$aUsers->chat_status:1;
        //检查平台是否开放聊天
        $aUsers->chat_status = $aUsers->is_speaking==1?$aUsers->chat_status:1;
        if($uLv==99){   //管理员不受限制
            $aUsers->is_speaking = 1;
            $aUsers->chat_status = 0;
        }
        $aUsers->level = $uLv;
        return $aUsers;
    }

    //取代违禁词
    public function setBetRech($userid = 0){
        if(empty($userid))
            return false;
        //重新计算最近2天下注
//        $aUserBet_his = DB::table('bet_his')->select(DB::raw('sum(`bet_money`) as aggregate'))->where('user_id',$userid)->whereBetween('created_at',[date("Y-m-d H:i:s",strtotime("-2 day")),date("Y-m-d H:i:s",time())]);
//        $aUserBet = DB::table('bet')->select(DB::raw('sum(`bet_money`) as aggregate'))->where('user_id',$userid)->whereBetween('created_at',[date("Y-m-d H:i:s",strtotime("-2 day")),date("Y-m-d H:i:s",time())])->union($aUserBet_his)->first();
//        $aUserBet = DB::select("select sum(bet_money) as bet_money_all from bet where user_id = :user_id1 and created_at between :cr_start1 and :cr_end1 union select sum(bet_money) as bet_money_all from bet_his where user_id = :user_id2 and created_at between :cr_start2 and :cr_end2",
//            [
//                'user_id1'=>$userid,
//                'user_id2'=>$userid,
//                'cr_start1'=>date("Y-m-d H:i:s",strtotime("-2 day")),
//                'cr_end1'=>date("Y-m-d H:i:s"),
//                'cr_start2'=>date("Y-m-d H:i:s",strtotime("-2 day")),
//                'cr_end2'=>date("Y-m-d H:i:s")
//            ]);
//        $aUserBet = @$aUserBet[0]->bet_money_all;
        # 最近2天下注
        $aUserBet = \App\Socket\Model\Users::getUserBetDay([
            'userId' => $userid,
            'timeStart' => date("Y-m-d H:i:s", strtotime("-30 day")),
            'timeEnd'=> date("Y-m-d H:i:s")
        ], false);

        //重新计算最近30天充值
        \App\Socket\Pool\MysqlPool::invoke(function (\App\Socket\Pool\MysqlObject $db) use($userid, $aUserBet) {
//            $aUserRecharges = $db
//                ->where('userId',$userid)
//                ->where('status',2)
//                ->where('addMoney',1)
//                ->where ('created_at', ['BETWEEN' => [date("Y-m-d H:i:s",strtotime("-30 day")), date("Y-m-d H:i:s",time())]])
//                ->getOne('recharges', 'sum(`amount`) as amount')['amount'] ?? 0;
            $aUserRecharges = \App\Socket\Model\Users::getUserRecharges([
                'userId' => $userid
            ]);
            return $db
                ->where('users_id',$userid)
                ->update('chat_users', [
                    'bet'=> $aUserBet,
                    'recharge'=> $aUserRecharges,
                    'updated_at'=> date("Y-m-d H:i:s",time())
                ]);
        });
//        $aUserRecharges = DB::table('recharges')->where('userId',$userid)->where('status',2)->where('addMoney',1)->whereBetween('created_at',[date("Y-m-d H:i:s",strtotime("-2 day")),date("Y-m-d H:i:s",time())])->sum('amount');
//        DB::table('chat_users')->where('users_id',$userid)->update([
//            'bet'=> $aUserBet,
//            'recharge'=> $aUserRecharges,
//            'updated_at'=> date("Y-m-d H:i:s",time())
//        ]);
    }

    //消息根据群组样式化
    public function cssText($level,$role){

//        $aCssColor = DB::table('chat_roles')->select('bg_color1','bg_color2','font_color')
//            ->where(function ($query) use ($level,$role){
//                if(isset($role)){
//                    switch ($role){
//                        case 2://如果是会员
//                            $query->where("type",2)->where("level",$level);
//                            break;
//                        default:
//                            $query->where("type",$role);
//                            break;
//                    }
//                }
//            })->first();
        $aCssColor = (object)ChatRoles::first([
            'role' => $role,
            'level' => $level
        ]);
        return $aCssColor;
    }

    //回传用户层级
    public function chkChat_level($role=0,$reg=0,$bet=0,$isnotAuto_count=0,$resLv = 0){
        if($role==3)                //如果是管理员LEVEL无条件给99
            return 99;
        elseif($role==1)            //如果是游客LEVEL无条件给0
            return 0;
        elseif($isnotAuto_count==1) //如果是不自动计算LEVEL无条件给原来设定的
            return $resLv;
//        $aLevel = DB::table('chat_level')->get();
        $aLevel = (object)\App\Socket\Pool\RedisPool::invoke(function (\App\Socket\Pool\RedisObject $db) {
            $db->select(12);
            if(!$chat_level = @json_decode($db->get('chat_level'), 1)){
                $chat_level = ChatLevel::get();
                $db->set('chat_level', json_encode($chat_level, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            }
            return json_decode(json_encode($chat_level));
        });
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
    public function getUserInfo($fd){
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
    public function is_not_json($str){
        return is_null(json_decode($str));
    }

    /**
     * 更新目前房客资讯
    */
    public function updUserInfo($fd,$iRoomInfo,$ws=null){
        try{
            $room_key = $fd;               //成员房间号码
            $chatusr = 'chatusr:'.md5($iRoomInfo['userId']);
            Storage::disk('chatusr')->put($chatusr, $room_key);
            Storage::disk('chatusrfd')->put('chatusrfd:'.$room_key,json_encode($iRoomInfo,JSON_UNESCAPED_UNICODE));
        }catch (\Exception $e){
            error_log(date('Y-m-d H:i:s',time()).$e.PHP_EOL, 3, '/tmp/chat/err.log');
        }
    }
    //注销全局存LIST
    public function delAllkey($fd,$logo=''){
        switch ($logo){
            case 'usr':
                $logVal ='chatusrfd:'.$fd;
                if(Storage::disk('chatusrfd')->exists($logVal)){
                    $usr = (array)json_decode(Storage::disk('chatusrfd')->get($logVal));
                    $this->closeLink($fd, $usr); # 退出房间 注销fd=>userId映射
                    if(isset($usr['userId'])){
                        $usrKey = 'chatusr:'.md5($usr['userId']);
                        Storage::disk('chatusrfd')->delete($logVal);              //删除用户
                        try{
                        DB::table('chat_online')->where('type',2)->where('k',$fd)->delete();
                        }catch (\Exception $e){
                        }
                        if(Storage::disk('chatusr')->exists($usrKey)) {           //检查用户的fd是否存在
                            $usrFd = Storage::disk('chatusr')->get($usrKey);
                            if($fd==$usrFd)
                                Storage::disk('chatusr')->delete($usrKey);              //删除用户
                        }
                    }
                }
                break;
        }
    }

    //全局存LIST
    public function updAllkey($logo = 'usr',$iRoomID,$addId = 0,$addVal = 0,$notReturn = false){
        if(in_array($logo,array('usr')))
            $tmpTxt = 'chatusrfd:';
        else
            $tmpTxt = $logo.$iRoomID.'=';
        if(empty($iRoomID))
            return false;
        if(!empty($addId)) {
            if($logo=='his'){
                PersonalLog::insertMsgLog(json_decode($addVal, 1));
//                $timeIdx = $addId;
//                if($type=='first'){         //讯息若是第一次则要判断是否有并发一样的时间
//                    for($ii=0;$ii<10000;$ii++){
//                        $timeIdx = $addId + $ii;
//                        if(!Storage::disk('chathis')->exists($tmpTxt.$timeIdx, $addVal)){
//                            if($ii>0){
//                                $addId = $timeIdx;
//                                $addVal = json_decode($addVal,true);
//                                $addVal['time'] = $addId;
//                                $addVal = json_encode($addVal,JSON_UNESCAPED_UNICODE);
//                            }
//                            break;
//                        }
//                    }
//                }
//                $write = Storage::disk('chathis')->put($tmpTxt.$timeIdx, $addVal);
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
//                    $files = Storage::disk('chatusrfd')->files();
                    $fds = Room::getRoomFd($iRoomID);
                    # 获取群下所有用户
                    foreach ($fds as $value){
                        $value = 'chatusrfd:'.$value;
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
//                    $iRoomUsers = array();
//                    $files = Storage::disk('chathis')->files();
//                    foreach ($files as $value){
//                        if(Storage::disk('chathis')->exists($value)&&substr($value,0,strlen($tmpTxt))==$tmpTxt){
//                            $orgHis = Storage::disk('chathis')->get($value);
//                            $aryHis =  (array)json_decode($orgHis);
//                            $iRoomUsers[$aryHis['time']] = $orgHis;
//                        }
//                    }
                    $iRoomUsers = [];
                    foreach (PersonalLog::getRoomLog($iRoomID) as $k => $v){
                        $iRoomUsers[$k] = json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
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
    public function getIdToUserInfo($k){
        try{
            $tmpUsr = Storage::disk('chatusr')->exists('chatusr:'.$k)?Storage::disk('chatusr')->get('chatusr:'.$k):'';                      //从md5的用户ID去找到在聊天室的广播号码
            $tmpUsrInfo = empty($tmpUsr) || !Storage::disk('chatusrfd')->exists('chatusrfd:'.$tmpUsr)?'':(array)json_decode(Storage::disk('chatusrfd')->get('chatusrfd:'.$tmpUsr));     //从聊天室的广播号码取得每个人的聊天室信息
        }catch (\Exception $e){
            $tmpUsrInfo = '';
        }
        return $tmpUsrInfo;
    }

    //重新整理历史讯息
    public function chkHisMsg($iRoomInfo,$fd=0,$IsPush=true){
//        $rsKeyH = 'his';
//        $iRoomHisTxt = $this->updAllkey($rsKeyH,$iRoomInfo['room']);     //取出历史纪录
        $iRoomHisTxt = PersonalLog::getRoomLog($iRoomInfo['room']);
//        ksort($iRoomHisTxt);
        //控制两个小时内的数据
//        $timess = (int)((microtime(true)*10000-15147360000000)*10000);
        //控制聊天室数据
//        $needDelnum = count($iRoomHisTxt)-80;
//        $needDelnum = $needDelnum > 0 ? $needDelnum : 0;
//        $ii = -1;
        //检查计划消息
        try{
            $status = Room::getFdStatus($fd);
            foreach ($iRoomHisTxt as $tmpkey =>$hisMsg) {
                $u = Room::getFdStatus($fd);
                if($status['type'] !== $u['type'] ||
                    $status['id'] !== $u['id'])
                    break;
//                $ii ++;
//                $hisKey = $rsKeyH.$iRoomInfo['room'].'='.$tmpkey;
//                $hisMsg = (array)json_decode($hisMsg);
                //清除过期或多的数据
//                if($hisMsg['time'] < ($timess-(7200*1000*10000*10000)) || $ii < $needDelnum){
//                    $serv = (object) [];
//                    $serv->post['uuid'] = (string)$tmpkey;
//                    $this->chkDelhis($iRoomInfo['room'],$serv);
//                    if(Storage::disk('chathis')->exists($hisKey))
//                        Storage::disk('chathis')->delete($hisKey);              //删除历史
//                    continue;
//                }
                //如果需要推送
                if($IsPush && $fd > 0){
                    if(isset($hisMsg['level']) && !empty($hisMsg['level']) && $hisMsg['level'] != 98){
                        $aAllInfo = $this->getIdToUserInfo($hisMsg['k']);
                        if(isset($aAllInfo['img']) && !empty($aAllInfo['img']) && ($hisMsg['img'] != $aAllInfo['img'])){
                            $hisMsg['img'] = $aAllInfo['img'];
                            PersonalLog::insertMsgLog($hisMsg);
//                            $this->updAllkey('his',$iRoomInfo['room'],$hisMsg['uuid'],json_encode($hisMsg),true);     //写入历史纪录
                        }
                    }
                    if(isset($hisMsg['status']) && !in_array($hisMsg['status'],array(8,9,15))){         //状态非红包
                        if($hisMsg['k']==md5($iRoomInfo['userId']))     //如果历史讯息有自己的讯息则调整status = 4
                            $hisMsg['status'] = 4;
                        else
                            $hisMsg['status'] = 2;
                    }
                    $msg = json_encode($hisMsg,JSON_UNESCAPED_UNICODE);
                    $this->push($fd, $msg);
                }
            }
        }catch (\Exception $e){
            Trigger::getInstance()->throwable($e);
//            error_log(date('Y-m-d H:i:s',time()).$e.PHP_EOL, 3, '/tmp/chat/err.log');
        }
    }

    /**********************************************************************************/
    //加入房间-先加入在进入
    public function addRoom($roomId, &$iRoomInfo, $fd)
    {
        if(!ChatRoom::inRoom($roomId, $iRoomInfo['userId']))
            return false;
        array_push($iRoomInfo['rooms'], $roomId);
        $iRoomInfo['rooms'] = array_values(array_diff(array_unique($iRoomInfo['rooms']), ['']));
        $this->updUserInfo($fd,$iRoomInfo);

        # 设置未读消息数和最后一条消息
        Room::setHistoryChatList($iRoomInfo['userId'], 'room', $roomId, [
            'lookNum' => 0,
            'lastMsg' => '加入聊天室'
        ]);
        return true;
    }
    //关闭链接
    public function closeLink($fd, $iRoomInfo)
    {
        // 解除UserId => Fd 关系
        Chat::deleteUserIdFdMapByFd($fd);
        // 解除Fd => UserId 关系
        Chat::deleteFdUserIdMapByFd($fd);
        # 退出房间
        if(!empty($roomId = Room::getRoomIdMapByFd($fd))){
            Room::exitRoom($roomId, $fd, $iRoomInfo);
        }
        # 删除用户状态
        Room::delFdStatus($fd);
    }

}