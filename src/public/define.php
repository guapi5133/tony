<?php

//环境变量
const PHP_ENV      = 'PHP_ENV_';
const PHP_ENV_FILE = '/usr/share/nginx/html/config.ini';
if (is_file(PHP_ENV_FILE))
{
    $ini = @parse_ini_file(PHP_ENV_FILE, TRUE);
    foreach ($ini as $k => $v)
    {
        if (is_array($v))
        {
            foreach ($v as $key => $value)
            {
                putenv(PHP_ENV . strtoupper($k . '_' . $key) . '=' . $value);
            }
        }
        else
        {
            putenv(PHP_ENV . strtoupper($k) . '=' . $v);
        }
    }
}
define("SERVER_ENV", getenv(PHP_ENV . strtoupper('server')) ?: 'local');//local本地环境,dev开发环境,test测试环境,pre预发布环境,prod生产环境

//文件夹目录定义
const DS = DIRECTORY_SEPARATOR;
define('APP_NAME', basename($_SERVER['DOCUMENT_ROOT']));
define('ROOT_DIR', dirname(__DIR__, 2) . DS);
const SRC_DIR       = ROOT_DIR . 'src' . DS;
const VENDOR_DIR    = ROOT_DIR . 'vendor' . DS;
const PROTECTED_DIR = SRC_DIR . 'protected' . DS;
const PUBLIC_DIR    = SRC_DIR . 'public' . DS . APP_NAME . DS;
const APPS_DIR      = PROTECTED_DIR . 'Apps' . DS;
const CONFIG_DIR    = PROTECTED_DIR . 'Config' . DS;
const LIB_DIR       = PROTECTED_DIR . 'Lib' . DS;
const LOG_PATH      = SRC_DIR . 'runtime' . DS;
const ASSET_DIR     = SRC_DIR . 'public' . DS . 'Asset' . DS;

//环境配置
$env = include(CONFIG_DIR . 'env.php');
define('DOMAIN_API', $env[SERVER_ENV]['domain_api']);
define('DOMAIN_ADM_API', $env[SERVER_ENV]['domain_adm_api']);
define('DOMAIN_ASSET', $env[SERVER_ENV]['domain_asset']);
define('DOMAIN_WEB', $env[SERVER_ENV]['domain_web']);
define('DOMAIN_ADM', $env[SERVER_ENV]['domain_adm']);

//命名空间
const NAME_SPACE = 'Apps';

//通用配置
const PAGE_SIZE      = 20;  //每页条数
const STATUS_ENABLE  = 1;   //数据库记录状态--正常
const STATUS_DISABLE = 98;  //数据库记录状态--禁用
const STATUS_DELETE  = 99;  //数据库记录状态--删除

//用户状态
const USER_STATUS = [
    STATUS_ENABLE  => '正常',
    STATUS_DISABLE => '禁用',
    STATUS_DELETE  => '删除',
];

//是否开启调试模式
const DEBUG = FALSE;

//是否输出SQL语句，需要开启调试模式
const SQL_DEBUG = FALSE;