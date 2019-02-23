<?php

namespace App\Exceptions;

use Exception;

class ApiException extends Exception
{
    function __construct($msg='', $code = 200)
    {
        if(is_array($msg))
            $msg = json_encode($msg);
        if(is_object($msg))
            $msg = json_encode($msg);
        parent::__construct($msg, $code);
    }
}
