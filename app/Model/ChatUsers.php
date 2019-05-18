<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ChatUsers extends Base
{

    protected $table = 'chat_users';

    public static function SearchUsers($request)
    {
        $model = DB::table('chat_users')
            ->select('username', 'users_id')
            ->where(function($sql) use($request){
                isset($request->search['value']) && $sql->where('username', 'like', $request->search['value'].'%');
                isset($request->user_list) && $sql->whereNotIn('users_id',$request->user_list);
                isset($request->level) && $sql->where('level',$request->level);
            });
        if(isset($request->start, $request->length))
            $model->skip($request->start)->take($request->length > 1 ? $request->length : 50);
        $resCount = $model->count();

        $res = $model->orderBy('created_at', 'desc')->get();
        return compact('resCount', 'res');
    }

}
