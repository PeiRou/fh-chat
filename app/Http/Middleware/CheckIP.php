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

        if(!preg_match("/".$str."/", $request->url()) && !preg_match("/".$str1."/", $request->url())){
            return abort('503');
        }

        $ip = realIp();
        $ipList = Whitelist::getWhiteIpList();
        if(!in_array($ip,$ipList)){
            return abort('503');
        }
        return $next($request);
    }
}
