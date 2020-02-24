<?php
/**
 * Created by PhpStorm.
 * User: yf
 * Date: 2019-01-06
 * Time: 19:22
 */

namespace App\Socket\Utility\Pool;


use EasySwoole\Component\Context\ContextManager;
use App\Socket\Utility\Pool\Exception\PoolEmpty;
use App\Socket\Utility\Pool\Exception\PoolException;
use Swoole\Coroutine;

trait TraitInvoker
{
    public static function invoke(callable $call,float $timeout = null)
    {
        $pool = PoolManager::getInstance()->getPool(static::class);
        if($pool instanceof AbstractPool){
            $num = 0;
            do{
                $obj = $pool->getObj($timeout);
                if($obj) break;
                \co::sleep(0.5);
                $num ++;
                if($num >= 15) break;
            }while(true);
//            $obj = $pool->getObj($timeout);
            if($obj){
                try{
                    $ret = call_user_func($call,$obj);
                    return $ret;
                }catch (\Throwable $throwable){
                    throw $throwable;
                }finally{
                    $pool->recycleObj($obj);
                }
            }else{
                throw new PoolEmpty(static::class." pool is empty");
            }
        }else{
            throw new PoolException(static::class." convert to pool error");
        }
    }

    public static function defer($timeout = null)
    {
        $key = md5(static::class);
        $obj = ContextManager::getInstance()->get($key);
        if($obj){
            return $obj;
        }else{
            $pool = PoolManager::getInstance()->getPool(static::class);
            if($pool instanceof AbstractPool){
                $obj = $pool->getObj($timeout);
                if($obj){
                    Coroutine::defer(function ()use($pool,$obj){
                        $pool->recycleObj($obj);
                    });
                    ContextManager::getInstance()->set($key,$obj);
                    return $obj;
                }else{
                    throw new PoolEmpty(static::class." pool is empty");
                }
            }else{
                throw new PoolException(static::class." convert to pool error");
            }
        }
    }
}