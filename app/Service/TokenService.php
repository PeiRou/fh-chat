<?php

namespace App\Service;

use App\Exceptions\ApiException;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Session;

class TokenService
{
    use Singleton;

    private $prefix = '';//token保存在redis的前缀
    private $table = 8; //默认操作redis库
    private $redis = Redis::class;
    private $code = 600;
    private $timeOut = 60 * 60 * 2;
    private $return = true;//验证失败是否返回 false就不返回错误直接抛出api异常 true的话返回false

    /**
     * 所有的参数都可以在初始化的时候设置
     */
    public function __construct($config = [])
    {
        foreach ($config as $k=>$v){
            $this->$k = $v;
        }
        $this->redis::connect();
        $this->redis::select($this->table);
    }
    public function setVal($key, $val){
        $this->$key = $val;
        if($key == 'table'){
            $this->redis::select($this->table);
        }
    }
    public function grantToken($id){
        $token = $this->getId();
        $this->saveCache($id, $token);
        return $token;
    }
    public function saveCache($id, $token){
        $data = (object)[];
        $data->key = $token;
        $data->uId = $id;
        $this->redis::select($this->table);
        $this->redis::setex('us_'.$this->prefix.md5($id), $this->timeOut, $token); //保存用户现在登录的token
        $this->redis::setex('usd_'.$this->prefix.$token, $this->timeOut, json_encode($data));//保存用户数据
    }

    /**
     * @param string $token
     * @param bool $save 是否更新token失效时间
     * @return id 返回唯一id  一般为用户id
     */
    public function checkToken($token = '', $save = false){
        empty($token) && $token = $this->getId();
        $this->redis::select($this->table);
        if(empty($data = json_decode($this->redis::get('usd_'.$this->prefix.$token))))
            return $this->ThrowOut('登录失效');
        if(empty($token = $this->redis::get('us_'.$this->prefix.md5($data->uId))))
            return $this->ThrowOut('用户信息失效');
        if($data->key !== $token){
            $this->redis::del('usd_'.$this->prefix.$data->key);
            return $this->ThrowOut('用户已在其它地方登录');
        }
        if($save)
            $this->saveCache($data->uId, $token);
        return $data->uId;
    }
    public function ThrowOut($msg = ''){
        $this->return && ThrowOut($this->code, $msg);
        return false;
    }
    public function destroy($token = '')
    {
        empty($token) && $token = $this->getId();
        $usdKey = 'usd_'.$this->prefix.$token;
        if($data = json_decode($this->redis::get($usdKey))){
            $this->redis::del($usdKey);
            $usKey = 'us_'.$this->prefix.md5($data->uId);
            $this->redis::del($usKey);
        }
    }
    private function getId(){
        return Session::getId();
    }
}