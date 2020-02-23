<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/6/6
 * Time: 16:33
 */

namespace App\Socket\Model;


class ChatRoles extends Base
{
    protected static $DB_READ_FUNCTION = ['first'];

    protected static function first($db, $param = [])
    {
        return self::HandleCacheData(function() use($db, $param){
            if(isset($param['role'])){
                switch ($param['role']){
                    case 2://如果是会员
                        $db->where("type",2)->where("level",$param['level']);
                        break;
                    default:
                        $db->where("type",$param['role']);
                        break;
                }
            }
            return $db->getOne('chat_roles', ['bg_color1, bg_color2, font_color']);
        }, 5);
    }

}