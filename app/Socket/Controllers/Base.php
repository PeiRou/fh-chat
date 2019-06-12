<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/5/24
 * Time: 22:24
 */

namespace App\Socket\Controllers;


use App\Socket\Exception\SocketApiException;
use App\Socket\Utility\Parser;

class Base
{
    public $parser;
    public function __construct(Parser $Parser)
    {
        $this->parser = $Parser;
    }

    public function __get($name)
    {
        if(isset($this->parser->$name))
            return $this->parser->$name;
        return null;
    }

    public function onException(\Throwable $throwable):void
    {
        throw $throwable;
    }

    public function query($aSql, $db = 'db')
    {
        return app('workerServer')->{$db}->query($aSql);
    }

    public function push($fd, $arr)
    {
        $msg = json_encode($arr, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if(is_array($fd)){
            foreach ($fd as $v){
                app('swoole')->push((int)$v, $msg);
            }
        }else{
            app('swoole')->push((int)$fd, $msg);
        }
    }

}