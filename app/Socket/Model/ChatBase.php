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
    protected static function getValue($db, $value, $param = [])
    {
        return self::HandleCacheData(function()use($db, $param, $value){
            foreach ($param as $k=>$v)
                $db->where($k, $v);
            return $db->getOne('chat_base', [$value])[$value] ?? null;
        }, 5, true);
    }
}