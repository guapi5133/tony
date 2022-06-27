<?php

//引入配置文件
require dirname(__DIR__) . '/init.php';

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
header("Access-Control-Allow-Origin: $origin");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT, DELETE");
header('Access-Control-Allow-Headers: Content-Type, Content-Length, Accept, X-Requested-With, Authorization');
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') return 204;//如果是options请求，结束

//框架入口
use eBaocd\Router\Router;

Router::display();
