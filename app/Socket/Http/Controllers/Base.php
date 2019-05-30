<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/5/27
 * Time: 20:57
 */

namespace App\Socket\Http\Controllers;

use App\Socket\Utility\HttpParser;
use App\Socket\Utility\Room;

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

    public function onException(\Throwable $throwable):void
    {
        throw $throwable;
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

    public function show($code, $msg = '', $data = [], int $httpCode = 200)
    {
        $data = [
            'code' => $code,
            'msg' => $msg,
            'data' => (object)$data,
        ];

        $this->response->status($httpCode);
        $this->response->header('Content-type', 'application/json;charset=utf-8');
        $this->response->end(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}