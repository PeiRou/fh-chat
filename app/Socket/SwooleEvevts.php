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
use App\Socket\Utility\Tables\UserStatus;
use App\Socket\Utility\Task\SuperClosure;
use App\Socket\Utility\Trigger;

class SwooleEvevts
{
    public static function initialize()
    {
        //注册数据库连接池
        $mysqlConf = PoolManager::getInstance()->register(MysqlPool::class, config('swoole.MYSQLPOOL.POOL_MAX_NUM'));
        $mysqlConf->setMaxObjectNum(50)->setMinObjectNum(10);
        //其它数据库连接池
//        $mysqlConf = PoolManager::getInstance()->register(Mysql2Pool::class, 6);
//        $mysqlConf->setMaxObjectNum(20)->setMinObjectNum(10);

        //redis连接池
        $redisConf = PoolManager::getInstance()->register(RedisPool::class, config('swoole.REDISPOOL.POOL_MAX_NUM'));
        $redisConf->setMaxObjectNum(20)->setMinObjectNum(0);
    }


    public static function onOpen(\swoole_websocket_server $server, \swoole_http_request $request)
    {

    }
    public static function onTask($server, \Swoole\Server\Task $task)
    {
        $taskObj = $task->data;
        if($taskObj instanceof SuperClosure){
            try{
                return $taskObj( $server, $task->id,$task->worker_id,$task->flags);
            }catch (\Throwable $throwable){
                Trigger::getInstance()->throwable($throwable);
            }
        }
    }

    public static function mainServerCreate()
    {
        UserStatus::getInstance(); // 创建fd状态表
//        app('swoole')->ws->addProcess((new Test('testProcess'))->getProcess());
    }
    public static function onWorkerStart($server, $id)
    {
        \swoole_process::signal(SIGPIPE, function($signo) {
            \swoole_process::signal(SIGPIPE, null);
        });
        if($id >= $server->setting['worker_num']) {
               swoole_set_process_name(config('swoole.SERVER_NAME')."_swoole_task_worker");
        } else {
            swoole_set_process_name(config('swoole.SERVER_NAME')."_swoole_worker");
        }
    }

    //open事件之后
    public static function onOpenAfter($request, $iRoomInfo)
    {
        # 推送所有的列表
        Push::pushList($request->fd,$iRoomInfo);
    }



}