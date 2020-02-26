<?php
/**
 * Created by PhpStorm.
 * User: Tioncico
 * Date: 2018/10/18 0018
 * Time: 9:43
 */

namespace App\Socket\Process;


use App\Socket\Utility\Process\AbstractProcess;

class ListenMasterException extends AbstractProcess
{
    private $isRun = false;
    public function run($arg)
    {
        $this->addTick(10000,function (){
            if(!$this->isRun){
                $this->isRun = true;
                try{
                    if(!\swoole_process::kill(app('swoole')->ws->master_pid, 0)){
                        \swoole_process::kill(app('swoole')->ws->manager_pid, 0) && \swoole_process::kill(app('swoole')->ws->manager_pid, SIGTERM);
                    }
                }catch (\Throwable $e){
                    Trigger::getInstance()->throwable($e);
                }finally{

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
}