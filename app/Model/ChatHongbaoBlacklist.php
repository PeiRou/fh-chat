<?php

namespace App\Model;


use SameClass\Service\Cache;

class ChatHongbaoBlacklist extends Base
{
    use Cache;

    protected $table = 'chat_hongbao_blacklist';

    public static function deleteHongbaoBlacklist(int $chat_hongbao_idx, int $user_id)
    {
        return self::where('chat_hongbao_idx', $chat_hongbao_idx)
            ->where('user_id', $user_id)
            ->skip(0)->take(1)
            ->delete();
    }

}
