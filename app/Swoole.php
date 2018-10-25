<?php

namespace App;


class Swoole
{
    private $timeout = 30;
    public function swooletest($type,$room,$data=array())
    {
        $param['type'] = $type;
        $param['room'] = $room;
        $this->postSwoole($param,$data);
    }
    private function postSwoole($param,$data=array()){
        $this->ch = curl_init();
        //设置post数据
        curl_setopt($this->ch,CURLOPT_URL,env('WS_CURL',"http://127.0.0.1")."/dows/?type=".$param['type']."&room=".$param['room'].http_build_query($data));
        curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, false); // 跳过证书检查
        curl_setopt($this->ch,CURLOPT_RETURNTRANSFER,1);
        curl_setopt($this->ch,CURLOPT_POST,1);
        curl_setopt($this->ch,CURLOPT_POSTFIELDS,$data);
        curl_setopt($this->ch, CURLOPT_TIMEOUT, $this->timeout);
        $output = curl_exec($this->ch);
        return $output;
    }
}
