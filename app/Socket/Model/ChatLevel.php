<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/6/6
 * Time: 16:33
 */

namespace App\Socket\Model;


class ChatLevel extends Base
{
    protected static $DB_READ_FUNCTION = ['get'];

    protected static function get($db, $param = [])
    {
        return $db->get('chat_level');
    }

}