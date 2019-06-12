<?php
/**
 * Created by PhpStorm.
 * User: yf
 * Date: 2018/8/14
 * Time: 下午6:17
 */

namespace App\Socket\Utility;



use App\Service\Singleton;
use App\Socket\Exception\SocketApiException;

class Trigger
{
    use Singleton;

    public function throwable(\Throwable $e)
    {
        if($e instanceof SocketApiException){
            echo $e->getMessage();
            return '';
        }
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

}