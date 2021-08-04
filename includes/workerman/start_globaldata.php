<?php 

use \Workerman\Worker;


// 自动加载类
require_once __DIR__ . '/vendor/autoload.php';

// 加载GlobalData
require_once __DIR__ . '/vendor/globaldata/Server.php';



//GlobalData占用8421端口
$worker = new GlobalData\Server('0.0.0.0', 8418);


// 如果不是在根目录启动，则运行runAll方法
if(!defined('GLOBAL_START'))
{
    Worker::runAll();
}