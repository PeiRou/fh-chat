<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/7/16
 * Time: 16:16
 */

namespace App\Socket\Http\Controllers\Traits;


trait ApiException
{

    public function onException(\Throwable $throwable)
    {
        if($throwable instanceof \App\Exceptions\ApiException){
            $getMessage = $throwable->getMessage();
            $json = json_decode($getMessage, 1) ?? $getMessage;
            $this->show($json['code'], $json['msg'], $json['data']);
        }else{
            throw $throwable;
        }
    }
}