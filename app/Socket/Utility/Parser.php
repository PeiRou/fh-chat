<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/5/24
 * Time: 21:13
 */

namespace App\Socket\Utility;


class Parser
{
    use \Illuminate\Routing\RouteDependencyResolverTrait;

    public $controller;
    public $action;
    public $data;
    public $server;
    public $request;

    public function __construct(\swoole_websocket_server $server, $request, $message)
    {
        $data = json_decode(urldecode(base64_decode($message)), 1);
        $this->request = $request;
        $this->server = $server;
        foreach ($data ?? [] as $k=>$v)
            $this->$k = $v;
    }

    public function run($iRoomInfo)
    {
        $this->iRoomInfo = $iRoomInfo;
        $controller = '\App\Socket\Controllers\\'.ucfirst($this->controller);
        if(!class_exists($controller) || !method_exists($controller, $this->action)){
            return false;
        }
        $instance = new $controller($this);
        try{
            $res = call_user_func([$instance, $this->action]);
        }catch (\Throwable $e){
            call_user_func([$instance, 'onException'], $e);
        }
        return $res ?? false;
    }
}