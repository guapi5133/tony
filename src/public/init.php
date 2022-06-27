<?php

header("Content-Type:text/html; charset=utf-8");
ini_set('date.timezone', 'Asia/Shanghai');
ini_set('date.default_latitude', 31.5167);
ini_set('date.default_longitude', 121.4500);

error_reporting(E_ALL ^ E_NOTICE);

require_once __DIR__ . '/define.php';
require_once VENDOR_DIR . 'autoload.php';
require_once CONFIG_DIR . 'database.php';
require_once CONFIG_DIR . 'AppConfig.php';