<?php

namespace App;


class Swoole
{
    public function swooletest($type,$room)
    {
        $param['type'] = $type;
        $param['room'] = $room;
        return $this->postSwoole($param);
    }
    private function postSwoole($param){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, env('WS_CURL',"http://127.0.0.1")."/dows");
        //设置post数据
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch,CURLOPT_POST,1);
        curl_setopt($ch,CURLOPT_POSTFIELDS,$param);
        $output = curl_exec($ch);
        curl_close($ch);
        return $output;
    }
}
