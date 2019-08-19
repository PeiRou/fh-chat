<?php
namespace App\Socket\Http\Controllers\Traits;


trait Login
{
    public $user;
    public function onRequest(?string $action): ?bool
    {
        if((!$token = $this->get('token')) && (!$token = $this->post('token')) ){
            return false;
        }
        $this->user = \App\Socket\Pool\MysqlPool::invoke(function (\App\Socket\Pool\MysqlObject $db) use($token) {
            return $db->where('sess', $token)->getOne('chat_users');
        });

        if(empty($this->user)){
            return false;
        }
        if($this->user['chat_role'] == 1 && $this->is_role)
            ThrowOut(3, '游客无法使用此功能！');
        $this->user['userId'] = $this->user['users_id'];
        $this->iRoomInfo = app('swoole')->getUsersess($token);
        return true;
    }
}