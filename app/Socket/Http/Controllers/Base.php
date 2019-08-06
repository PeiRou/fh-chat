<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/5/27
 * Time: 20:57
 */

namespace App\Socket\Http\Controllers;

use App\Socket\Utility\HttpParser;

class Base
{
    public $parser;
    public function __construct(HttpParser $Parser)
    {
        $this->parser = $Parser;
        $this->mysqlPool = \App\Socket\Utility\Pool\PoolManager::getInstance()->getPool(\App\Socket\Pool\MysqlPool::class);
        $this->db = $this->mysqlPool->getObj();
    }

    public function __destruct()
    {
        $this->mysqlPool->recycleObj($this->db);
    }

    public function onException(\Throwable $throwable)
    {
        if($throwable instanceof \App\Exceptions\ApiException){
            $getMessage = $throwable->getMessage();
            $json = json_decode($getMessage, 1) ?? $getMessage;
            $this->show($json['code'], $json['msg'], $json['data']);
        }else{
            throw $throwable;
        }
    }
    public function __get($name)
    {
        if(isset($this->parser->$name))
            return $this->parser->$name;
        return null;
    }
    public function onRequest(?string $action): ?bool
    {
        return true;
    }


    public function get($key = ''){
        if(empty($key))
            return $this->parser->request->get;
        if(isset($this->parser->request->get[$key]))
            return $this->parser->request->get[$key];
        return null;
    }
    public function post($key = ''){
        if(empty($key))
            return $this->parser->request->post;
        if(isset($this->parser->request->post[$key]))
            return $this->parser->request->post[$key];
        return null;
    }

    public function show($code, $msg = '', $data = [], $isObject = true, int $httpCode = 200)
    {
        $isObject && ($data = (count($data) ? collect($data) : (object)[]));
        $data = [
            'code' => $code,
            'msg' => $msg,
            'data' => $data,
        ];

        $this->response->status($httpCode);
        $this->response->header('Content-type', 'application/json;charset=utf-8');
        $this->response->end(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}