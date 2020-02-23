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
            'worker_num' => env('WORKER_NUM', 2),
            'task_worker_num' => env('TASK_WORKER_NUM', 10),
            'max_request' => 15000,
            'task_max_request' => 10000,
            'enable_coroutine' => true,
            'task_enable_coroutine' => true,
//            'package_max_length' => 1024 * 1024 * 20
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
    'REDISPOOL1'         => [
        'host'       => env('REDIS_HOST_1', env('REDIS_HOST', '127.0.0.1')),
        'port'       => env('REDIS_PORT_1', env('REDIS_PORT', '6379')),
        'auth'          => '',
        'POOL_MAX_NUM'  => env('REDISPOOL1_POOL_MAX_NUM_1', 20),
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
        'POOL_MAX_NUM'  => '10',
        'POOL_TIME_OUT' => '0.1',
    ],
    'MYSQLPOOL2' => [
        'host'          => env('DB_HOST_1', env('DB_HOST', '3306')),
        'port'          => env('DB_PORT_1', env('DB_PORT', '3306')),
        'user'          => env('DB_USERNAME_1', env('DB_USERNAME', 'forge')),
        'timeout'       => '5',
        'charset'       => 'utf8',
        'password'      => env('DB_PASSWORD_1', env('DB_PASSWORD', '')),
        'database'      => env('DB_DATABASE_1', env('DB_DATABASE', 'forge')),
        'POOL_MAX_NUM'  => env('POOL_MAX_NUM_1', 20),
        'POOL_TIME_OUT' => '0.1',
    ],

];
