<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/6/6
 * Time: 16:33
 */

namespace App\Socket\Model;


class ChatHongbao extends Base
{

    protected static function getValue($db, $param = [], $value)
    {
        return self::RedisCacheData(function()use($db, $param, $value){
            foreach ($param as $k=>$v)
                $db->where($k, $v);
            return $db->getValue('chat_hongbao', $value);
        }, 10); //10秒缓存
    }

}