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
//        if($param['type']=='betInfo')
//            return 'ok';
        $redis = Redis::connection();
        $redis->select(5);
        $redis->setex('hbpost'.http_build_query($data),3,'on');
        $output = curl_exec($this->ch);
        if($redis->exists('hbpost'.http_build_query($data)))
            return 'ok';
        else
            return 'nok';
    }

    public static function getSwoole($action, $param = [])
    {
        $url = env('WS_CURL',"https://0.0.0.0").":".env('WS_PORT',"2021").'/'.$action;
        return json_decode(self::curl_get_content($url, $param), 1) ?? null;
    }
    public static function get($action, $param = [])
    {
        $url = env('WS_CURL',"https://0.0.0.0").":".env('WS_PORT',"2021").'/'.$action;
        return self::curl_get_content($url, $param);
    }
    public static function post($url, $param = [],$data = [])
    {
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL,env('WS_CURL',"https://0.0.0.0").":".env('WS_PORT',"2021").'/'.$url."?".http_build_query($param));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 跳过证书检查
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch,CURLOPT_POST,1);
        curl_setopt($ch,CURLOPT_POSTFIELDS,$data);
        $output = curl_exec($ch);
        curl_close($ch);
        return $output;
    }

    public static function curl_get_content($url, $data = [], $conn_timeout=7, $timeout=10, $user_agent=null)
    {
        count($data) > 0 && ($url = $url.'?'.http_build_query($data));
        $headers = array(
            "Accept-Charset: utf-8;q=1"
        );
        if ($user_agent === null) {
            $user_agent = 'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/31.0.1650.57 Safari/537.36';
        }
        $headers[] = $user_agent;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $conn_timeout);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_ENCODING, "");
        $res = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_errno($ch);
        if (($err) || ($httpcode !== 200)) {
            writeLog('error', static::class.'_'.__FUNCTION__.'_'.'   http状态码：'.$httpcode.'失败信息：'.$err.'返回信息：'.$res);
        }

        curl_close($ch);
        return $res;
    }
}
