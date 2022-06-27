<?php

$APP_G = [
    //读写数据库
    'db'      => [
        'dbtype'         => 'mysql',
        'host'           => env_get('db_host_master') ?: '127.0.0.1',
        'database'       => env_get('db_name') ?: 'default',
        'username'       => env_get('db_user') ?: 'root',
        'password'       => env_get('db_pass') ?: '',
        'port'           => env_get('db_port') ?: '3306',
        'charset'        => 'utf8mb4',
        'collation'      => 'utf8mb4_general_ci',
        'tb_prefix'      => 'qb_',
        'persistent'     => 'false',
        'driver_options' => [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
    ],
    //只读数据库
    'readdb'  => [
        'dbtype'         => 'mysql',
        'host'           => env_get('db_host_master') ?: '127.0.0.1',
        'database'       => env_get('db_name') ?: 'default',
        'username'       => env_get('db_user') ?: 'root',
        'password'       => env_get('db_pass') ?: '',
        'port'           => env_get('db_port') ?: '3306',
        'charset'        => 'utf8mb4',
        'collation'      => 'utf8mb4_general_ci',
        'tb_prefix'      => 'qb_',
        'persistent'     => 'false',
        'driver_options' => [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
    ],
    //第三方数据库，默认为读写均可，要读写分离就再写一个db
    'third'   => [
        'dbtype'         => 'mysql',
        'host'           => env_get('db_host_master') ?: '127.0.0.1',
        'database'       => env_get('db_name') ?: 'default',
        'username'       => env_get('db_user') ?: 'root',
        'password'       => env_get('db_pass') ?: '',
        'port'           => env_get('db_port') ?: '3306',
        'charset'        => 'utf8mb4',
        'collation'      => 'utf8mb4_general_ci',
        'tb_prefix'      => 'qb_',
        'persistent'     => 'false',
        'driver_options' => [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
    ],
    //redis
    'redis'   => [
        'type'    => 'single', //选择redis的模式
        'single'  => [
            'host'     => env_get('redis_host') ?: '127.0.0.1',
            'port'     => env_get('redis_port') ?: 6379,
            'password' => env_get('redis_pass') ?: NULL,
        ],
        'cluster' => [
            'server' => [
                env_get('redis_host') ?: '127.0.0.1',
            ],
            'option' => [
                'cluster'    => 'redis',
                'parameters' => [
                    'password' => env_get('redis_pass') ?: NULL,
                ],
            ],
        ],
    ],
    'mongodb' => [
        'type'     => 'mongodb',      // 数据库类型
        'host'     => '127.0.0.1', // 服务器地址
        'database' => 'default',  // 数据库
        'table'    => 'default',      // 集合（表名）
        'username' => 'root',         // 用户名
        'password' => '',     // 密码
        'port'     => 27017,        // 端口
    ],

    'sitename' => '', //项目名称
    'template' => 'default',
];

function env_get($name)
{
    return getenv(PHP_ENV . strtoupper($name));
}
