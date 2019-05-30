<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/5/28
 * Time: 21:26
 */

namespace App\Socket\Http\Controllers\Traits;


trait Login
{
    public function onRequest(?string $action): ?bool
    {
        if(!$token = $this->get('token'))
            return false;
        $this->user = \App\Socket\Pool\MysqlPool::invoke(function (\App\Socket\Pool\MysqlObject $db) use($token) {
            return $db->where('sess', $token)->getOne('chat_users');
        });
        if(empty($this->user))
            return false;
        $this->iRoomInfo = app('swoole')->getUsersess($token);
        return true;
    }
}