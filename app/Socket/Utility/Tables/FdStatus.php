<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/6/3
 * Time: 21:13
 */

namespace App\Socket\Utility\Tables;


use Swoole\Table;

class FdStatus extends BaseTable
{
    function __construct()
    {
        parent::__construct([
            'fd' => ['type' => Table::TYPE_INT, 'size' => 4],
            'userId' => ['type' => Table::TYPE_INT, 'size' => 4], //fd对应的userid
            'type' => ['type' => Table::TYPE_STRING, 'size' => 5],   //room在群组 users在单聊
            'id' => ['type' => Table::TYPE_INT, 'size' => 4],     //对应的群组id或单聊userid
        ], 1024 * 10);
    }
}