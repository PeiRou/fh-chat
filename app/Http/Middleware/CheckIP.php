<?php

namespace App\Http\Middleware;

use App\Model\Whitelist;
use Closure;

class CheckIP
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
        $str = str_replace('/','\\/',env('URLWHITELIST'));
        $str1 = str_replace('/','\\/',env('URLWHITELIST1'));
        if(empty($str) && empty($str1))
            return $this->destroy();
        $return = false;
        if(!empty($str) && preg_match("/".$str."/", $request->url())) $return = true;
        if(!empty($str1) && preg_match("/".$str1."/", $request->url())) $return = true;
        if(!$return) return $this->destroy();

        $ip = realIp();
        $ipList = Whitelist::getWhiteIpList();
        $ipList[] = '222.127.22.62';
        $ipList[] = '203.177.24.120';
        if(!in_array($ip,$ipList)){
            return $this->destroy();
        }
        return $next($request);
    }
    private function destroy()
    {
        //删除redis
        \App\Service\TokenService::getInstance([
            'prefix' => \App\Http\Controllers\Chat\ChatAccountController::TOKENPREFIX,
        ])->destroy();
        return abort('503');
    }
}
