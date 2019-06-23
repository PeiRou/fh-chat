<?php
/**
 * Created by PhpStorm.
 * User: ashen
 * Date: 19-3-25
 * Time: 下午6:06
 */

return [
    'SERVER_NAME' => "fh-chat_swoole",
    'MAIN_SERVER' => [
        'SETTING' => [
            'worker_num' => 2,
            'task_worker_num' => 4,
            'max_request' => 1000,
            'task_max_request' => 500,
            'enable_coroutine' => true,
            'task_enable_coroutine' => true,
        ],
    ],
    'mysql' => [
        'config' => [
            'host' => env('DB_HOST','10.16.56.98'),
            'port' => env('DB_PORT','3306'),
            'user' => env('DB_USERNAME','root'),
            'password' => env('DB_PASSWORD','123456'),
            'database' => env('DB_DATABASE','wx-db')
        ],
        'member' => [
            'max_number' => env('MAX_NUMBER',10),
            'min_number' => env('MIN_NUMBER',5)
        ]
    ],
    'chat' => [ //聊天室聊天记录配置
        'config' => [
            'host' => env('CHAT_HOST','10.16.56.98'),
            'port' => env('CHAT_PORT','3306'),
            'user' => env('CHAT_USERNAME','root'),
            'password' => env('CHAT_PASSWORD','123456'),
            'database' => env('CHAT_DATABASE','zoe')
        ],
        'member' => [
            'max_number' => env('MAX_NUMBER',1),
            'min_number' => env('MIN_NUMBER',1)
        ]
    ],
    'redis' => [
        'config' => [
            'host' => env('REDIS_HOST','127.0.0.1'),
            'port' => env('REDIS_PORT','6379')
        ],
        'member' => [
            'max_number' => env('MAX_NUMBER',50),
            'min_number' => env('MIN_NUMBER',5)
        ]
    ],
    'REDISPOOL'         => [
        'host'       => env('REDIS_HOST', '127.0.0.1'),
        'port'       => env('REDIS_PORT', '6379'),
        'auth'          => '',
        'POOL_MAX_NUM'  => '6',
        'POOL_TIME_OUT' => '6',
    ],
    'MYSQLPOOL' => [
        'host'          => env('DB_HOST'),
        'port'          => env('DB_PORT', '3306'),
        'user'          => env('DB_USERNAME', 'forge'),
        'timeout'       => '5',
        'charset'       => 'utf8',
        'password'      => env('DB_PASSWORD', ''),
        'database'      => env('DB_DATABASE', 'forge'),
        'POOL_MAX_NUM'  => '6',
        'POOL_TIME_OUT' => '0.1',
    ],
    'MYSQLPOOL2' => [
        'host'          => env('DB_HOST2'),
        'port'          => env('DB_PORT2', '3306'),
        'user'          => env('DB_USERNAME2', 'forge'),
        'timeout'       => '5',
        'charset'       => 'utf8',
        'password'      => env('DB_PASSWORD2', ''),
        'database'      => env('DB_DATABASE2', 'forge'),
        'POOL_MAX_NUM'  => '6',
        'POOL_TIME_OUT' => '0.1',
    ],

];
