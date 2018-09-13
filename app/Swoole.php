<?php

namespace App;

use GuzzleHttp\Client;

class Swoole
{
    public function swooletest($type,$room)
    {
        $param['type'] = $type;
        $param['room'] = $room;
        return $this->postSwoole($param);
    }
    private function postSwoole($param){
//        $ch = curl_init();
        $this->ch = curl_init();
        //设置post数据
        curl_setopt($this->ch,CURLOPT_URL,env('WS_CURL',"http://127.0.0.1").":".env('WS_PORT',9500));
        curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, false); // 跳过证书检查
        curl_setopt($this->ch,CURLOPT_RETURNTRANSFER,1);
        curl_setopt($this->ch,CURLOPT_POST,1);
        curl_setopt($this->ch,CURLOPT_POSTFIELDS,$param);
        curl_setopt($this->ch, CURLOPT_TIMEOUT, $this->timeout);
        $output = curl_exec($this->ch);
        return $output;
        curl_close($ch);
//        $http = new Client();
//        $res = $http->request('POST',env('WS_CURL',"http://127.0.0.1").":".env('WS_PORT',9500)."?type=".$param['type']."&room=".$param['type']);
//        $json = json_decode((string) $res->getBody(), true);
        return $json;
    }
}
