<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ChatRoles extends Base
{

    protected $table = 'chat_roles';

    public static function getList()
    {
        return self::select('level', 'name')->get();
    }

}
