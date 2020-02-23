<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/6/6
 * Time: 16:33
 */

namespace App\Socket\Model;


class ChatRegex extends Base
{
    protected static $DB_READ_FUNCTION = ['getList'];

    protected static function getList($db)
    {
        return self::HandleCacheData(function() use($db){
            return $db->get('chat_regex', null, ['regex']);
        }, 1);
    }

}