<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/5/28
 * Time: 21:52
 */

namespace App\Socket\Http\Controllers\Traits;


trait BackLogin
{
    //后台请求， 只能是内网，而且token要生效
    public function onRequest(?string $action): ?bool
    {
        $ip = $this->request->server['remote_addr'];
        $patterns = [
//            '/10\.16\..*\..*/',
            '/10\..*\..*\..*/',
            '/192\.168\..*\..*/',
            '/127\.0\..*\..*/',
            '/222.127.22.62/',
            '/203.177.24.120/'
        ];
        $is = true;
        foreach ($patterns as $v)
            preg_match($v, $ip) && $is = false;
        if($is) return false;
        if(!$token = $this->get('token'))
            return false;
        $data = \App\Socket\Pool\RedisPool::invoke(function (\App\Socket\Pool\RedisObject $redis) use($token) {
            $redis->select(8);
            if(empty($data = json_decode($redis->get('usd_chat_'.$token))))
                return false;
            if(empty($token = $redis->get('us_chat_'.md5($data->uId))))
                return false;
            if($data->key !== $token)
                return false;
            return $data;
        });
        if(!$data) return false;
        $uId = $data->uId;
        $this->adminUser = \App\Socket\Pool\MysqlPool::invoke(function (\App\Socket\Pool\MysqlObject $db) use($uId) {
            return $db->where('sa_id', $uId)->getOne('chat_sa');
        });
        if(empty($this->adminUser))
            return false;
        return true;
    }
}