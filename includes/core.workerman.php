<?php if(!defined('ROOT')) die('Access denied.');

// 屏蔽错误代码
error_reporting(0);
//error_reporting(E_ALL & ~E_NOTICE);


@include(ROOT . 'config/config.php');
require(ROOT . 'config/settings.php');
require(ROOT . 'includes/functions.workerman.php');
require(ROOT . 'includes/workerman/vendor/globaldata/Client.php');


// 设置超时时间
@set_time_limit(0);
@ignore_user_abort(true); //忽略用户断开连接, 服务器脚本仍运行

// 设置当前脚本可使用的最大内存
@ini_set('memory_limit', '2048M'); //2G


Events::$_CFG = $_CFG; //设置Events静态成员$_CFG为系统配置数组$_CFG

$arrCn = $_CFG['RobotReplyArrCn']; //中文无解回复数组
$arrEn = $_CFG['RobotReplyArrEn']; //English无解回复数组
if(!isset($arrCn) OR !is_array($arrCn) OR empty($arrCn)) $arrCn = array("什么意思?");
if(!isset($arrEn) OR !is_array($arrEn) OR empty($arrEn)) $arrEn = array("What do you mean?");


//设置机器人信息
Events::$robot = array(
	"aid" => 888888, 
	"client_id" => 1,  //socket连接id
	"avatar" => "robot/0.png",  //默认头像
	"fullname" => $_CFG['RobotName'], 
	"fullname_en" => $_CFG['RobotName_en'], 
	"post" => $_CFG['RobotPost'], 
	"post_en" => $_CFG['RobotPost_en'], 
	"no_answers" => $arrCn,
	"no_answers_en" => $arrEn,
	"keywords" => array(),  //自动回复关键字数组
	"msgs" => array(),   //自动回复内容数组
	"avatars" => array(),  //自动回复变换头像数组
	"open_teams" => array(),  //开启无人值守的客服组数组
);


//引入数据库类
if($dbmysql == "mysqli"){
	include(ROOT . 'includes/class.DBMysqli.php');
	Events::$DB = new DBMysqli($dbusername, $dbpassword, $dbname,  $servername, false, false); //mysqli 不输出错误(不使用die输出)
}else{
	include(ROOT . 'includes/class.DBMysql.php');
	Events::$DB = new DBMysql($dbusername, $dbpassword, $dbname,  $servername, false, false); //mysql
}

//设置mysql数据库wait_timeout, 否则当此socket进程长驻, 而mysql空闲8小时(默认), 此进程将无法连接数据库, 导致客服无法登录
Events::$DB->query("SET session wait_timeout=31536000"); //一年

$dbpassword = '';

?>