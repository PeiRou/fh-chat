<?php

namespace App\Http\Middleware;

use App\LogHandle;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class CheckLogin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $TokenService = \App\Service\TokenService::getInstance([
            'prefix' => \App\Http\Controllers\Chat\ChatAccountController::TOKENPREFIX, //token保存在redis的前缀
            'return' => false //验证失败是否返回 true就不返回错误直接抛出api异常 false的话返回false
        ]);
        //如果路由文件用的是aApi就需要设置验证失败直接抛出api异常 不然就跳转页面
        if(preg_match("/^[api][\/]{0,1}.*/", $request->route()->uri)){
            $TokenService->setVal('return', 1); //设置单个配置 验证失败直接抛出api异常
            $request->sa_id = $TokenService->checkToken($request->token ?? '', 1);
        }else{
            if(!$request->sa_id = $TokenService->checkToken($request->token ?? '')){
                Session::flush();
                return redirect()->route('chat.login');
            }
        }
        $this->bind($request);
        //没有session更新session
//        if(empty(Session::get('account_id'))) app('\App\Http\Controllers\AGENT\AgentAccountController')->saveSession($request->user);

        return $next($request);
    }

    //绑定需要的参数
    private function bind($request){
        $request->adminInfo = DB::table('chat_sa')->where('account',\App\Http\Controllers\Chat\ChatAccountController::ADMIN)->first();
        $request->user = DB::table('chat_sa')->where('sa_id',$request->sa_id)->first();
    }
}
