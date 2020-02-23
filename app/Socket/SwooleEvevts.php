<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/5/24
 * Time: 20:59
 */

namespace App\Socket;


use App\Socket\Model\ChatRoomDt;
use App\Socket\Model\OtherDb\PersonalLog;
use App\Socket\Pool\Mysql2Pool;
use App\Socket\Pool\MysqlPool;
use App\Socket\Pool\Redis1Pool;
use App\Socket\Pool\RedisPool;
use App\Socket\Redis\Chat;
use App\Socket\Utility\Pool\PoolManager;
use App\Socket\Utility\Tables\FdStatus;
use App\Socket\Utility\Task\SuperClosure;
use App\Socket\Utility\Trigger;

class SwooleEvevts
{
    public static function initialize()
    {
        //注册数据库连接池
        $mysqlConf = PoolManager::getInstance()->register(MysqlPool::class, config('swoole.MYSQLPOOL.POOL_MAX_NUM'));
        $mysqlConf->setMaxObjectNum(10)->setMinObjectNum(1); //设置最大数和最小数
        //其它数据库连接池
        $mysqlConf1 = PoolManager::getInstance()->register(Mysql2Pool::class, config('swoole.MYSQLPOOL2.POOL_MAX_NUM'));
        $mysqlConf1->setMaxObjectNum(env('POOL_MAX_NUM_1', 20))->setMinObjectNum(2);
        //redis连接池
        $redisConf = PoolManager::getInstance()->register(RedisPool::class, config('swoole.REDISPOOL.POOL_MAX_NUM'));
        $redisConf->setMaxObjectNum(10)->setMinObjectNum(0);
        //redis连接池-缓存专用
        $redisConf1 = PoolManager::getInstance()->register(Redis1Pool::class, config('swoole.REDISPOOL1.POOL_MAX_NUM'));
        $redisConf1->setMaxObjectNum( config('swoole.REDISPOOL1.POOL_MAX_NUM'))->setMinObjectNum(0);
    }


    public static function onOpen(\swoole_websocket_server $server, \swoole_http_request $request)
    {

    }
    public static function onStart($server)
    {
        swoole_set_process_name(config('swoole.SERVER_NAME')."_master");
        go(function(){
            Chat::clearAll(); #清除redis 保存的聊天室信息
            ChatRoomDt::clearInvalidUser(); #删除ChatRoomDt表在user表里已经删掉的会员
            PersonalLog::delLog(); #删除聊天日志超过一定时间的
        });
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
        FdStatus::getInstance(); // 创建fd状态表
//        app('swoole')->ws->addProcess((new \App\Socket\Process\Test('testProcess'))->getProcess());
        app('swoole')->ws->addProcess((new \App\Socket\Process\Request('RequestProcess'))->getProcess());
    }
    public static function onWorkerStart($server, $worker_id)
    {
        \swoole_process::signal(SIGPIPE, function($signo) {
            \swoole_process::signal(SIGPIPE, null);
        });
        if($worker_id >= $server->setting['worker_num']) {
               swoole_set_process_name(config('swoole.SERVER_NAME')."_task_worker");
        } else {
            swoole_set_process_name(config('swoole.SERVER_NAME')."_worker");
        }

        # 每个进程初始化的时候默认链接多少个链接 这样处理第一个请求不会有第一次链接的io时间
        if ($server->taskworker == false) {
            PoolManager::getInstance()->getPool(MysqlPool::class)->preLoad(3);//最小创建数量
            PoolManager::getInstance()->getPool(RedisPool::class)->preLoad(1);//最小创建数量
        }
    }

    //open事件之后
    public static function onOpenAfter($request, $iRoomInfo)
    {
        # 推送所有的列表
        Push::pushList($request->fd,$iRoomInfo);
    }

}