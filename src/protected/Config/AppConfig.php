<?php

$APP_G['project'] = '';//TODO 此处很关键、用于redis的前缀以及管理员用户密码加密的盐，不同项目需修改

$APP_G['header'] = [
    'account' => [
        //管理后台
        'admin'   => [
            'system'   => 'admin',
            'platform' => 'admin',
            'account'  => 'tjt95JGu9tPLsu4iU',//4107407e7fb0fd26264c43456d6b6e57
            'expire'   => 3600 * 24 * 2,
        ],
        'user'    => [ //用户端
                       'system'   => 'user',
                       'platform' => 'user',
                       'account'  => 'kjsNJY82hfy5sdnz',//46a5102c1dee489328b7ab895769334b
                       'expire'   => 3600 * 24 * 2,
        ],
        //默认
        'local'   => [
            'system'   => 'local',
            'platform' => 'admin',
            'account'  => 'q4lRYhrh87Sjadrr',//3f30d826749aeb8200166fb8b0d1725a
            'expire'   => 3600 * 24 * 30,
        ],
        'crontab' => [
            'system'   => 'crontab',
            'platform' => 'admin',
            'account'  => 'xu9143uPIioeHt33',//442b874bc688ad7ad1cddec17665593e
            'expire'   => 3600 * 24 * 30,
        ],
    ],
];

//是否开启了事务，如果开启了事务，读写分离无效，直接走 写库
$APP_G['transaction'] = FALSE;

//发起请求的渠道，安卓，苹果等
$APP_G['from_channnel'] = '';

$APP_G['controller'] = 'index';
$APP_G['action']     = 'index';
$APP_G['url']        = '/index/index';

$APP_G['redis_system_config_key'] = 'system_config'; //redis中存放全局变量的key名
