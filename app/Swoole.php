<?php

namespace App;

use Illuminate\Support\Facades\Redis;

class Swoole
{
    private $timeout = 2;
    public function swooletest($type,$room,$data=array())
    {
        $param['type'] = $type;
        $param['room'] = $room;
        return $this->postSwoole($param,$data);
    }
    private function postSwoole($param,$data=array()){
        $param['type'] = isset($param['type'])?$param['type']:$data['type'];
        $param['room'] = isset($param['room'])?$param['room']:$data['room'];
        $this->ch = curl_init();
        //设置post数据
        curl_setopt($this->ch,CURLOPT_URL,env('WS_CURL',"https://0.0.0.0").":".env('WS_PORT',"2021")."/?type=".$param['type']."&room=".$param['room']);
        curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, false); // 跳过证书检查
        curl_setopt($this->ch,CURLOPT_RETURNTRANSFER,1);
        curl_setopt($this->ch,CURLOPT_POST,1);
        curl_setopt($this->ch,CURLOPT_POSTFIELDS,$data);
//        curl_setopt($this->ch, CURLOPT_TIMEOUT, $this->timeout);
        $redis = Redis::connection();
        $redis->select(5);
        $redis->setex('hbpost'.http_build_query($data),3,'on');
        $output = curl_exec($this->ch);
        if($redis->exists('hbpost'.http_build_query($data)))
            return 'ok';
        else
            return 'nok';
    }
}
