<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/6/13
 * Time: 17:53
 */

namespace App\Socket\Model;


class ChatHongbaoDt extends Base
{
    //会员是否领过这个红包  可以缓存长时间
    protected static function checkUserIsGetHongbao($db, $idx, $userId){
        return self::HandleCacheData(function()use($db, $idx, $userId){
            return $db->where('hongbao_idx', $idx)
                ->where('users_id', $userId)
                ->getOne('chat_hongbao_dt');
        }, 60 * 24 * 7, false);
    }

}