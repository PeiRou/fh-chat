<?php
/**
 * Created by PhpStorm.
 * User: Tioncico
 * Date: 2018/10/18 0018
 * Time: 9:43
 */

namespace App\Socket\Process;


use App\Socket\Utility\Process\AbstractProcess;
use App\Socket\Utility\Task\TaskManager;
use App\Socket\Utility\Trigger;
use App\Socket\Utility\Users;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use SameClass\Config\AdSource\AdSource;
use App\Socket\Redis\Chat;

class Request extends AbstractProcess
{
    private $isRun = false;
    public function run($arg)
    {
        $this->addTick(1000,function (){
            if(!$this->isRun){
                $this->isRun = true;
                while ($request = json_decode(\App\Socket\Redis1\Redis::exec(10, 'LPOP', 'openRequest'))){
                    TaskManager::async(function() use($request){
                        Request::openAction($request);
                    });
                }
                $this->isRun = false;
            }
        });
    }

    public function onShutDown()
    {
        // TODO: Implement onShutDown() method.
    }

    public function onReceive(string $str, ...$args)
    {
        // TODO: Implement onReceive() method.
    }
    public static function openAction($request)
    {
        if(!app('swoole')->ws->connection_info($request->fd)){
            return false;
        }
        DB::disconnect();
        error_log(date('Y-m-d H:i:s',time())." | ".$request->fd." => ".json_encode($request).PHP_EOL, 3, '/tmp/chat/open.log');        //只要连接就记下log
        try {
            $strParam = (array)$request->server;
            $strParam = explode("/", $strParam['request_uri']);      //房间号码
            $iSess = $strParam[1];
            var_dump('start___'.$request->fd.'____'.microtime().'_____'.$iSess);
            if(empty($iSess))
                return app('swoole')->sendToSerf($request->fd, 3, '登陆失效');
            $iRoomInfo = app('swoole')->getUsersess($iSess, $request->fd);                 //从sess取出会员资讯
            if(empty($iRoomInfo)){
                return app('swoole')->sendToSerf($request->fd, 3, '登陆失效');
            }
            $rooms = $iRoomInfo['rooms'];
            app('swoole')->sendToSerf($request->fd, 14, 'init');
            if (empty($iRoomInfo) || !isset($iRoomInfo['room']) || empty($iRoomInfo['room']))                                   //查不到登陆信息或是房间是空的
                return app('swoole')->sendToSerf($request->fd, 3, '登陆失效');
            app('swoole')->updUserInfo($request->fd, $iRoomInfo, app('swoole')->ws);        //成员登记他的房间号码
            # 绑定userId fd
            Chat::bindUser($iRoomInfo['userId'], $request->fd);
            Users::checkFdToken($iRoomInfo['userId'], $iRoomInfo['sess']); # 验证用户的所有fd token 如果失效删除所有登录信息
            foreach ($rooms as $room){
                //广播登陆信息，如果三个小时内广播过一次，就不再重复广播
                if (!Storage::disk('chatlogintime')->exists(md5($iRoomInfo['userId'])) || Storage::disk('chatlogintime')->get(md5($iRoomInfo['userId'])) < time()){
                    Storage::disk('chatlogintime')->put(md5($iRoomInfo['userId']),time()+10800);
                    $msg = app('swoole')->msg(1, '进入聊天室', $iRoomInfo);   //进入聊天室
                    app('swoole')->sendToAll($room, $msg);
                }
            }
            //回传自己的基本设置
            if($iRoomInfo['setNickname']==0)
                $iRoomInfo['nickname'] = '';
            $msg = app('swoole')->msg(7,'fstInit',$iRoomInfo);
            app('swoole')->push($request->fd, $msg);
            \App\Socket\SwooleEvevts::onOpenAfter($request, $iRoomInfo);

            var_dump('start777777777777___'.$request->fd.'____'.microtime());
            $AdSource = new AdSource();
            $ISROOMS = $AdSource->getOneSource('chatType');
            $ISROOMS = $ISROOMS == '1' ? (int)$ISROOMS : 0;
            # 如果是老聊天室 默认打开1聊天室
            if(!$ISROOMS){
                $room_dt = DB::table('chat_room_dt')->where('id',1)->where('user_id',$iRoomInfo['userId'])->first();
                if(empty($room_dt)){
                    $room1data['id'] = 1;
                    $room1data['user_id'] = $iRoomInfo['userId'];
                    $room1data['user_name'] = $iRoomInfo['userName'];
                    $room1data['is_speaking'] = 1;
                    $room1data['is_pushbet'] = 0;
                    $room1data['created_at'] = date('Y-m-d H:i:s');
                    $room1data['updated_at'] = date('Y-m-d H:i:s');
                    DB::table('chat_room_dt')->insert($room1data);      // type 1:chatusr 2:chatusrfd
                }
                app('swoole')->inRoom(1, $request->fd, $iRoomInfo, $iSess);
            }
        }catch (\Exception $e){
            Trigger::getInstance()->throwable($e);
//                error_log(date('Y-m-d H:i:s',time()).$e.PHP_EOL, 3, '/tmp/chat/err.log');
        }
    }
}