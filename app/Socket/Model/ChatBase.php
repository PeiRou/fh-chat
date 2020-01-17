<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/5/27
 * Time: 22:10
 */

namespace App\Socket\Model;



class ChatBase extends Base
{
    protected static $DB_READ_FUNCTION = ['getValue'];

    protected static function getValue($db, $value, $param = [])
    {
        return self::RedisCacheData(function()use($db, $param, $value){
            foreach ($param as $k=>$v)
                $db->where($k, $v);
            return $db->getOne('chat_base', [$value])[$value] ?? null;
        }, 30, true);
    }
}