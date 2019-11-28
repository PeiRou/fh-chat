<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/5/24
 * Time: 21:13
 */

namespace App\Socket\Utility;


class HttpParser
{
    use \Illuminate\Routing\RouteDependencyResolverTrait;

    public $controller;
    public $action;
    public $data;
    public $server;
    public $request;

    public function __construct($request, $response)
    {
        $this->request = $request;
        $this->response = $response;
        $server = $request->server;
        $arr = explode('/', trim($server['request_uri'], '/'));
        $this->controller = $arr[0] ?? '';
        $this->action = $arr[1] ?? '';
    }

    public function run()
    {
        $controller = '\App\Socket\Http\Controllers\\'.ucfirst($this->controller);
        if(!class_exists($controller) || !method_exists($controller, $this->action)){
            return false;
        }
        $instance = new $controller($this);
        try{
            if(!$instance->onRequest($this->action)){
                $instance->show(403, '登录失效', [], 403);
                return false;
            }

            $parameters  = (new \ReflectionMethod($instance,$this->action))->getParameters();
            $params = [];
            foreach ($parameters as $reflectionParameter){
                $name = $reflectionParameter->getName();
                $reflectionType = $reflectionParameter->getType();
                $value = $instance->get($name);
                if($value === null){
                    if($reflectionParameter->isOptional()){
                        $value = $reflectionParameter->getDefaultValue();
                    }else{
                        if(!$reflectionType->allowsNull()){
                            $instance->show(102, '参数错误', [], 200);
                            return false;
                        }
                    }
                }else{
                    if($reflectionType->isBuiltin()){
                        $value = $this->c($value, (string)$reflectionType);
                    }
                }
                $params[] = $value;
            }
            $res = call_user_func([$instance, $this->action], ...$params);
        }catch (\Throwable $e){
            $res = call_user_func([$instance, 'onException'], $e);
        }
        return $res ?? false;
    }

    private function c($value, $type)
    {
        switch ($type){
            case 'string':
                return (string)($value);
            case 'int':
                return (int)($value);
            case 'array':
                return (array)($value);
        }
        return $value;
    }
}