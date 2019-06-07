<?php
/**
 * Created by PhpStorm.
 * User: yf
 * Date: 2018/8/14
 * Time: 下午6:17
 */

namespace App\Socket\Utility;



use App\Service\Singleton;

class Trigger
{
    use Singleton;
    public function error($msg, int $errorCode = E_USER_ERROR, Location $location = null)
    {

    }

    public function throwable(\Throwable $e)
    {
        if(env('APP_DEBUG')){
            echo $e->getMessage().PHP_EOL;
            echo $e->getFile().'('.$e->getLine().')'.PHP_EOL;
            echo $e->getTraceAsString().PHP_EOL;
        }
        writeLog('error',
            $e->getMessage().PHP_EOL.
            $e->getFile().'('.$e->getLine().')'.PHP_EOL.
            $e->getTraceAsString());
    }

    public function writeLog(\Throwable $e)
    {
        if(env('APP_DEBUG')){
            echo $e->getMessage().PHP_EOL;
            echo $e->getFile().'('.$e->getLine().')'.PHP_EOL;
            echo $e->getTraceAsString().PHP_EOL;
        }
        writeLog('error',
            $e->getMessage().PHP_EOL.
            $e->getFile().'('.$e->getLine().')'.PHP_EOL.
            $e->getTraceAsString());
    }

    private function getLocation()
    {


    }
}