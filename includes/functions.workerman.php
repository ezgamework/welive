<?php if(!defined('ROOT')) die('Access denied.');

//返回用户头像URL
function getAvatar($userid, $n = 0) {
	$filename = Iif(file_exists(ROOT . "avatar/$userid.jpg"), "$userid.jpg", "0.jpg");
	if($n) return $filename; //仅返回头像文件名
	return SYSDIR . "avatar/$filename";
}


//加密解密函数
function authCode($string, $operation = 'DECODE', $key = '', $expiry = 600) {
	$ckey_length = 4;
	$key = md5($key ? $key : 'default_key');
	$keya = md5(substr($key, 0, 16));
	$keyb = md5(substr($key, 16, 16));
	$keyc = $ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length): substr(md5(microtime()), -$ckey_length)) : '';

	$cryptkey = $keya.md5($keya.$keyc);
	$key_length = strlen($cryptkey);

	$string = $operation == 'DECODE' ? base64_decode(substr($string, $ckey_length)) : sprintf('%010d', $expiry ? $expiry + time() : 0).substr(md5($string.$keyb), 0, 16).$string;
	$string_length = strlen($string);

	$result = '';
	$box = range(0, 255);

	$rndkey = array();
	for($i = 0; $i <= 255; $i++) {
		$rndkey[$i] = ord($cryptkey[$i % $key_length]);
	}

	for($j = $i = 0; $i < 256; $i++) {
		$j = ($j + $box[$i] + $rndkey[$i]) % 256;
		$tmp = $box[$i];
		$box[$i] = $box[$j];
		$box[$j] = $tmp;
	}

	for($a = $j = $i = 0; $i < $string_length; $i++) {
		$a = ($a + 1) % 256;
		$j = ($j + $box[$a]) % 256;
		$tmp = $box[$a];
		$box[$a] = $box[$j];
		$box[$j] = $tmp;
		$result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
	}

	if($operation == 'DECODE') {
		if((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26).$keyb), 0, 16)) {
			return substr($result, 26);
		} else {
			return '';
		}
	} else {
		return $keyc.str_replace('=', '', base64_encode($result));
	}
}

// ##
function html($String) {
	return str_replace(array('&amp;','&#039;','&quot;','&lt;','&gt;'), array('&','\'','"','<','>'), $String);
}

// ##
function displayDate($timestamp = 0, $dateformat = '', $time = 0){
	if(!$dateformat) $dateformat = Events::$_CFG['DateFormat'] . Iif($time, ' H:i:s');
	return @gmdate($dateformat, Iif($timestamp, $timestamp, time()) + (3600 * intval(Events::$_CFG['Timezone'])));
}

// ##
function Iif($expression, $x, $y = ''){
	return $expression ? $x : $y;
}

//检测是否这数字或字母
function isAlnum($str){
   return preg_match("/^[[:alnum:]]+$/i", $str);
}

// ##
function passGen($length = 8){
	$str = 'abcdefghijkmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
	for ($i = 0, $passwd = ''; $i < $length; $i++)
		$passwd .= substr($str, mt_rand(0, strlen($str) - 1), 1);
	return $passwd;
}

// ##
function forceInt($InValue, $DefaultValue = 0) {
	$iReturn = intval($InValue);
	return ($iReturn == 0) ? $DefaultValue : $iReturn;
}

//强制进入mysql字符串数据过虑
function forceString($InValue, $DefaultValue = '') {
	if (is_string($InValue)) {
		$sReturn = escapeSql(trim($InValue));
		if (empty($sReturn) && strlen($sReturn) == 0) $sReturn = $DefaultValue;
	} else {
		$sReturn = escapeSql($DefaultValue);
	}
	return $sReturn;
}

//Mysql数据库过滤函数
function escapeSql($query_string) {
	if(ini_get('magic_quotes_sybase') && strtolower(ini_get('magic_quotes_sybase')) != "off") $query_string = stripslashes($query_string);

	$query_string = htmlspecialchars(str_replace (array('\0', '　'), '', $query_string), ENT_QUOTES);

	if(Events::$DB->type == "mysqli"){
		if(function_exists('mysqli_real_escape_string')) {
			$query_string = mysqli_real_escape_string(Events::$DB->conn, $query_string);
		}else{
			$query_string = addslashes($query_string);
		}
	}else{
		if(function_exists('mysql_real_escape_string')) {
			$query_string = mysql_real_escape_string($query_string);
		}else if(function_exists('mysql_escape_string')){
			$query_string = mysql_escape_string($query_string);
		}else{
			$query_string = addslashes($query_string);
		}
	}

	return $query_string;
}


//查找IP归属地
function convertIp($ip) {
	$return = '';
	if(preg_match("/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/", $ip)) {
		$iparray = explode('.', $ip);

		if($iparray[0] == 10 || $iparray[0] == 127 || ($iparray[0] == 192 && $iparray[1] == 168) || ($iparray[0] == 172 && ($iparray[1] >= 16 && $iparray[1] <= 31))) {
			$return = 'LAN';
		} elseif($iparray[0] > 255 || $iparray[1] > 255 || $iparray[2] > 255 || $iparray[3] > 255) {
			$return = '未知'; //无效的IP地址!
		} else {
			$return = convertipTiny($ip);
		}
	}

	return $return;
}

// ##
function convertipTiny($ip) {
	static $fp = NULL, $offset = array(), $index = NULL;

	$ipdot = explode('.', $ip);
	$ip    = pack('N', ip2long($ip));

	$ipdot[0] = (int)$ipdot[0];
	$ipdot[1] = (int)$ipdot[1];

	if($fp === NULL && $fp = @fopen(ROOT . 'includes/tinyipdata.dat', 'rb')) {
		$offset = @unpack('Nlen', @fread($fp, 4));
		$index  = @fread($fp, $offset['len'] - 4);
	}elseif($fp == FALSE) {
		return  '未知'; //IP数据库文件不可用
	}

	$length = $offset['len'] - 1028;
	$start  = @unpack('Vlen', $index[$ipdot[0] * 4] . $index[$ipdot[0] * 4 + 1] . $index[$ipdot[0] * 4 + 2] . $index[$ipdot[0] * 4 + 3]);

	for ($start = $start['len'] * 8 + 1024; $start < $length; $start += 8) {
		if ($index{$start} . $index{$start + 1} . $index{$start + 2} . $index{$start + 3} >= $ip) {
			$index_offset = @unpack('Vlen', $index{$start + 4} . $index{$start + 5} . $index{$start + 6} . "\x0");
			$index_length = @unpack('Clen', $index{$start + 7});
			break;
		}
	}

	@fseek($fp, $offset['len'] + $index_offset['len'] - 1024);
	if($index_length['len']) {
		return @fread($fp, $index_length['len']);
	} else {
		return '未知';
	}
}



?>