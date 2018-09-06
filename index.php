<?php
// 应用目录为当前目录
define('APP_PATH',__DIR__.'/');

//开启调试
define('APP_DEBUG',TRUE);

//加载框架文件
require(APP_PATH . 'fastphp/Fastphp.php');

//加载配置文件
$config = require(APP_PATH . 'config/config.php');

//实例化框架
(new fastphp\Fastphp($config))->run();