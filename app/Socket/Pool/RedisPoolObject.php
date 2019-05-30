<?php

namespace App\Socket\Pool;

use App\Socket\Utility\Pool\PoolObjectInterface;

class RedisPoolObject extends \Redis implements PoolObjectInterface
{
    function gc()
    {
        $this->close();
    }
    function objectRestore()
    {
        // TODO: Implement objectRestore() method.
    }
    function beforeUse(): bool
    {
        return true;
    }
}