<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/5/24
 * Time: 20:59
 */

namespace App\Socket;


use App\Socket\Pool\Mysql2Pool;
use App\Socket\Pool\MysqlPool;
use App\Socket\Pool\RedisPool;
use App\Socket\Utility\Pool\PoolManager;
use Swoole\Coroutine;

class SwooleEvevts
{
    public static function initialize()
    {
        //数据库连接池
        $mysqlConf = PoolManager::getInstance()->register(MysqlPool::class, 6);
        if ($mysqlConf === null) {}
        $mysqlConf->setMaxObjectNum(20)->setMinObjectNum(0);
        //其它数据库连接池
        $mysqlConf = PoolManager::getInstance()->register(Mysql2Pool::class, 6);
        $mysqlConf->setMaxObjectNum(20)->setMinObjectNum(0);

        //redis连接池
        $redisConf = PoolManager::getInstance()->register(RedisPool::class, config('swoole.REDISPOOL.POOL_MAX_NUM'));
        $redisConf->setMaxObjectNum(20)->setMinObjectNum(0);
    }


    public static function onOpen(\swoole_websocket_server $server, \swoole_http_request $request)
    {

    }

    public static function onWorkerStart($server, $id)
    {
        \swoole_process::signal(SIGPIPE, function($signo) {
            \swoole_process::signal(SIGPIPE, null);
        });
//
//        //绑定一个单例，传一些数据
//        Container::getInstance()->bind('workerServer', function(){
//            $obj = new \stdClass();
//            $obj->db = new MysqlManagerService(config('swoole.mysql'));
//            $obj->db->initChannel();
//            //链接聊天记录的数据库
//            $obj->dbchat = new MysqlManagerService(config('swoole.chat'));
//            $obj->dbchat->initChannel();
//            return $obj;
//        }, true);
//
//        $db = app('workerServer')->db;
//        $dbchat = app('workerServer')->dbchat;
//
//
//        //回收链接
//        if ((!$db->is_recycling) && !($dbchat->is_recycling)) {
//            $db->is_recycling = true;
//            $dbchat->is_recycling = true;
//
//            while (1) {
//                Coroutine::sleep(10);
//                if ($db->shouldRecover()) {
//                    $mysql = $db->channel->pop();
//                    $now   = time();
//                    if ($now - $mysql->getUsedAt() > 20) {
//                        $db->decrease();
//                    } else {
//                        !$db->channel->isFull() && $db->channel->push($mysql);
//                    }
//                }
//
//                if ($dbchat->shouldRecover()) {
//                    $mysql = $dbchat->channel->pop();
//                    $now   = time();
//                    if ($now - $mysql->getUsedAt() > 20) {
//                        $dbchat->decrease();
//                    } else {
//                        !$dbchat->channel->isFull() && $dbchat->channel->push($mysql);
//                    }
//                }
//            }
//        }

    }

    //open事件之后
    public static function onOpenAfter($request, $iRoomInfo)
    {
        # 推送房间列表
        Push::getRoomList($request->fd,$iRoomInfo);
        # 推送所有的列表
        Push::pushList($request->fd,$iRoomInfo);
    }



}