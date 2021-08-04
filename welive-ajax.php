<?php  

define('ROOT', dirname(__FILE__).'/');  //系统程序根路径, 必须定义, 用于防翻墙

require(ROOT . 'includes/core.guest.php');  //加载核心文件
require(ROOT . 'includes/functions.ajax.php');  //加载需要的函数

if(!$_CFG['Actived']) die("Access denied");

//ajax操作
$ajax =  ForceIntFrom('ajax');
if(!$ajax) die("Access denied");


if($dbmysql == "mysqli"){
	$DB = new DBMysqli($dbusername, $dbpassword, $dbname,  $servername, false, false); //MSQLI, 不显示mysql查询错误
}else{
	$DB = new DBMysql($dbusername, $dbpassword, $dbname,  $servername, false, false);
}

$dbpassword = '';

$act = ForceStringFrom('act');

//访客上传图片
if($act == "uploadimg"){
	@set_time_limit(0);  //解除时间限制

	$img_path = ROOT . 'upload/img/'; //保存图片的目录
	$img_array = array("image/jpg" => '.jpg', "image/jpeg" => '.jpg', "image/png" => '.png', "image/gif" => '.gif');

	$key = ForceStringFrom('key');
	$code = ForceStringFrom('code');
	$decode = authcode($code, 'DECODE', $key);

	$gid = ForceIntFrom('gid');
	$img_type = strtolower(ForceStringFrom('img_type'));
	$img_base64 = $_POST['img_base64']; //图片文件编码内容不过虑

	//文件目录是否可写
	if(!is_writable($img_path)){
		ajax_msg(0, "目录不可写");
	}

	//验证数据是否存在
	if(!$gid OR !$img_type){
		ajax_msg(0, $langs['badcookie']);
	}

	//验证码是否过期
	if($decode != md5(WEBSITE_KEY . $_CFG['KillRobotCode'])){
		ajax_msg(0, $langs['badcookie']);
	}

	//文件限制不能大于4M(前端js是按文件大小限制为1024 * 4000, 此处对应字节数约为1024 * 8000)
	if(!$img_base64 OR (strlen($img_base64) > 1024 * 8000)){
		ajax_msg(0, $langs['img_limit']);
	}

	//图片文件类型限制
	if(!array_key_exists($img_type, $img_array)) {
		ajax_msg(0, $langs['img_badtype']);
	}

	//判断客人是否存在及是否有上传文件授权
	$session = ForceCookieFrom(COOKIE_USER . '_sess');
	if(!$session) $session = ForceStringFrom('sess'); //解决safari无法读取第三方cookie的问题(iframe)

	$guest = $DB->getOne("SELECT aid, upload, session FROM " . TABLE_PREFIX . "guest WHERE gid = '$gid'");
	if(!$guest OR !$guest['aid'] OR !$session OR $session != $guest['session']) {
		ajax_msg(0, $langs['no_upload_auth']);
	}

	//上传图片需要客服授权
	if($_CFG['AuthUpload'] AND !$guest['upload']){
		ajax_msg(0, $langs['no_upload_auth']);
	}

	//限制每小时最多仅可以上传20张图片
	$max_upload = 20;
	$sql_time = time() - 3600;
	$result = $DB->getOne("SELECT COUNT(mid) AS nums FROM " . TABLE_PREFIX . "msg WHERE type = 0 AND filetype = 1 AND fromid = '{$gid}' AND time > '{$sql_time}'");
	if($result AND $result['nums'] >= $max_upload){
		ajax_msg(0, $langs['upload_alert']);
	}


	//以当前时间的小时数为最后目录
	$current_dir = gmdate('Ym/d', time() + (3600 * ForceInt($_CFG['Timezone']))) . "/"; 
	if(!file_exists($img_path . $current_dir)){
		mkdir($img_path . $current_dir, 0777, true);
		@chmod($img_path . $current_dir, 0777);
	}

	//产生一个独特的文件名称, 包括小时目录
	$filename = $current_dir . md5(uniqid(COOKIE_KEY.microtime())) . $img_array[$img_type]; 

	if(file_put_contents($img_path . $filename, base64_decode($img_base64))){
		ajax_msg(1, $filename); //返回文件名, 包含时间日期目录
	}

	ajax_msg(0, $langs['upload_failed']);
}


//访客上传文件
elseif($act == "upload_file"){
	//验证是否开放上传文件功能
	if(!$_CFG['AllowUploadFile']){
		ajax_msg(0, "非法操作!");
	}

	@set_time_limit(0);  //解除时间限制

	$file_chunk_size = 1048888; //文件片大小限制 1M(1048576), 比前端JS限制稍大200Byte

	$file_path = ROOT . 'upload/file/'; //保存文件的目录
	$file_invalid_ext = array("exe", "php", "bat", "sys", "jpg", "gif", "bmp", "png", "jpeg");

	$file_content = isset($_POST['fileData']) ? $_POST['fileData'] : "";
	$file_content = base64_decode($file_content); //base64解码

	$file_index = ForceIntFrom('fileIndex'); //文件分片索引

	$gid = ForceIntFrom('gid');
	$key = ForceStringFrom('key');
	$code = ForceStringFrom('code');
	$decode = authcode($code, 'DECODE', $key);

	//验证码是否过期
	if(!$gid OR $decode != md5(WEBSITE_KEY . $_CFG['KillRobotCode'])){
		ajax_msg(0, $langs['badcookie']);
	}

	//判断客人是否存在及是否有上传文件授权
	$session = ForceCookieFrom(COOKIE_USER . '_sess');
	if(!$session) $session = ForceStringFrom('sess'); //解决safari无法读取第三方cookie的问题(iframe)

	$guest = $DB->getOne("SELECT aid, upload, session FROM " . TABLE_PREFIX . "guest WHERE gid = '$gid'");
	if(!$guest OR !$guest['aid'] OR !$session OR $session != $guest['session']) {
		ajax_msg(0, $langs['no_upload_auth']);
	}

	//上传需要客服授权
	if($_CFG['AuthUpload'] AND !$guest['upload']){
		ajax_msg(0, $langs['no_upload_auth']);
	}

	//文件大小限制
	if(!$file_content OR (strlen($file_content) > $file_chunk_size)){
		ajax_msg(0, $langs['badcookie']);
	}

	//处理其它分片文件(1为第一片)
	if($file_index > 1){
		$filename = ForceStringFrom('fileName');

		$file_path_name = $file_path . $filename;
		@clearstatcache($file_path_name); //清除文件缓存状态

		if(!$filename OR !file_exists($file_path_name)) ajax_msg(0, $langs['badcookie']);

		if(filesize($file_path_name) > intval($_CFG['UploadLimit'])*1024*1028){ //允许超过4K字节
			@unlink($file_path_name); //删除已上传的文件
			ajax_msg(0, $langs['bad_filesize'] . $_CFG['UploadLimit'] . "M");
		}

		//追加写入原文件
        if(file_put_contents($file_path_name, $file_content, FILE_APPEND)) {
			ajax_msg(1, $filename); //返回文件名, 包含时间日期目录
        }else{
			@unlink($file_path_name); //删除已上传的文件
			ajax_msg(0, $langs['upload_failed']);
		}	
	}

	//处理第一片文件
	$file_ext = strtolower(ForceStringFrom('fileExt')); //文件后缀

	//文件目录是否可写
	if(!is_writable($file_path)){
		ajax_msg(0, "目录不可写");
	}

	//文件类型限制
	if(!$file_ext OR in_array($file_ext, $file_invalid_ext)) {
		ajax_msg(0, $langs['bad_filetype']);
	}

	//限制每小时最多仅可以上传20个文件
	$max_upload = 20;
	$sql_time = time() - 3600;
	$result = $DB->getOne("SELECT COUNT(mid) AS nums FROM " . TABLE_PREFIX . "msg WHERE type = 0 AND filetype = 2 AND fromid = '{$gid}' AND time > '{$sql_time}'");
	if($result AND $result['nums'] >= $max_upload){
		ajax_msg(0, $langs['upload_alert']);
	}

	//以当前时间的小时数为最后目录
	$current_dir = gmdate('Ym/d', time() + (3600 * intval($_CFG['Timezone']))) . "/"; 
	if(!file_exists($file_path . $current_dir)){
		mkdir($file_path . $current_dir, 0777, true);
		@chmod($file_path . $current_dir, 0777);
	}

	//产生一个独特的文件名称, 包括小时目录
	$filename = $current_dir . md5(uniqid(COOKIE_KEY.microtime())) . ".{$file_ext}"; 

	if(file_put_contents($file_path . $filename, $file_content)){
		ajax_msg(1, $filename); //返回文件名, 包含时间日期目录
	}else{
		ajax_msg(0, $langs['upload_failed']);
	}
}


//访客留言
elseif($act == "comment"){
	$key = ForceStringFrom('key');
	$code = ForceStringFrom('code');
	$decode = authcode($code, 'DECODE', $key);
	if($decode != md5(WEBSITE_KEY . $_CFG['KillRobotCode'])){
		ajax_msg(); //验证码过期
	}

	$fullname = ForceStringFrom('fullname');
	$email = ForceStringFrom('email');
	$phone = ForceStringFrom('phone');
	$content = ForceStringFrom('content');
	$vid = ForceIntFrom('vid');
	$vvc = ForceIntFrom('vvc');

	if(!$fullname OR strlen($fullname) > 90){
		ajax_msg(2);
	//}elseif(!IsEmail($email)){ //不再检查
		//ajax_msg(3);
	}elseif(!$content OR strlen($content) > 1800){
		ajax_msg(4);
	}elseif(!checkVVC($vid, $vvc)){
		ajax_msg(5);
	}

	$grid = ForceIntFrom('grid', 1); //如果没有, 设置为默认客服组
	$gid = ForceIntFrom('gid');
	$ip = GetIP();

	if(preg_match("/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/", $ip)){
		$is_banned = $DB->getOne("SELECT fid FROM " . TABLE_PREFIX . "firewall WHERE ip = '$ip' AND expire > " . time());
		if($is_banned){
			$DB->exe("UPDATE " . TABLE_PREFIX . "firewall SET bans = (bans + 1) WHERE fid = " . $is_banned['fid']); //记录次数
			ajax_msg(); //伪装成验证码过期
		}
	}

	$DB->exe("INSERT INTO " . TABLE_PREFIX . "comment (grid, gid, fullname, ip, phone, email, content, time) VALUES ('$grid', '$gid', '$fullname', '$ip', '$phone', '$email', '$content', '".time()."')");
	ajax_msg(1);
}

//生成验证码, 返回vvc id
elseif($act == 'vvc'){
	$key = ForceStringFrom('key');
	$code = ForceStringFrom('code');
	$decode = authcode($code, 'DECODE', $key);

	if($decode != md5(WEBSITE_KEY . $_CFG['KillRobotCode'])){
		ajax_msg();
	}

	$status = createVVC();
	ajax_msg($status);
}

//获取验证码图片
elseif($act == 'get'){
	getVVC();
	die();
}


//ajax输出函数
function ajax_msg($status = 0, $msg = '', $arr = array()){
	$arr['s'] = $status;
	$arr['i'] = $msg;

	$json = new JSON;

	die($json->encode($arr));
}


?>