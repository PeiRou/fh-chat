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

class Test extends AbstractProcess
{
    private $isRun = false;
    public function run($arg)
    {

        TaskManager::async(function(){
            echo 's';
        });
        $this->addTick(5000,function (){
            if(!$this->isRun){
                $this->isRun = true;
                while (true){
                    try{

                        if(1){

                        }else{
                            break;
                        }
                    }catch (\Throwable $e){

                        break;
                    }finally{

                    }
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