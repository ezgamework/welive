<?php if(!defined('ROOT')) die('Access denied.');

class MobileAuth{

	public $admin = null; //保存用户信息
	public $is_ajax = 0;

	public function __construct($path){
		if($path[1] == 'ajax') $this->is_ajax = 1;

		$this->check_auth(); //admin类构造时就进行授权
	}

	/**
	 * private 授权函数 auth
	 */
	private function check_auth(){
		$sessionid = ForceCookieFrom(COOKIE_ADMIN);
		$agent = md5(substr($_SERVER['HTTP_USER_AGENT'], 0, 252) . WEBSITE_KEY);

		if($sessionid AND IsAlnum($sessionid)){//登录成功验证cookie授权
			$sql = "SELECT s.sid, a.*
						FROM " . TABLE_PREFIX . "session s
						LEFT JOIN " . TABLE_PREFIX . "admin a ON a.aid = s.aid
						WHERE s.sid    = '$sessionid'
						AND s.agent = '$agent'
						AND a.activated = 1";

			$userinfo = APP::$DB->getOne($sql);

			if(!$userinfo OR !$userinfo['aid']){ //用户不合法, 清除cookie, 重新登录
				setcookie(COOKIE_ADMIN, '', 0, '/');

				if(!$this->is_ajax) $this->login(); //ajax不输出登录窗口
			}else{
				$this->admin = $userinfo; //授权成功, 执行后面的程序
				$this->admin['agent'] = $agent; //用于socket连接时验证
				$this->admin['password'] = '';
			}
		}else{
			if(!$this->is_ajax) $this->login(); //ajax不输出登录窗口
		}
	}

	/**
	 * private 输出用户登录窗口 login
	 */
	private function login(){
		$info = '';

		if(IsPost('submit')){
			$username = ForceStringFrom('username');

			$info = $this->check();
		}else{
			$username = ForceCookieFrom("welive_login_name");
		}

		$info = Iif($info, "<font color='#ff3300'>$info</font>", '请输入用户名和密码.');

		$key = PassGen(8);
		$code = authcode(md5(WEBSITE_KEY), 'ENCODE', $key, 1800);
		$cookievalue = md5(WEBSITE_KEY . $key . APP::$_CFG['KillRobotCode']);


		echo '<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="viewport" content="width=device-width, initial-scale=1,minimum-scale=1, maximum-scale=1, user-scalable=no">
<title>' . APP_NAME . ' - 管理登录</title>
<link rel="shortcut icon" href="public/img/favicon.ico" type="image/x-icon">
<link rel="stylesheet" type="text/css" href="public/login.css">
</head>
<body>
<div id="logo">
	<img src="public/img/login_logo.png" alt="'.APP_NAME.'"> 
</div>
<div id="login">
	<form id="loginform" action="" method="post">
		<input type="hidden" name="key" value="'.$key.'">
		<input type="hidden" name="code" value="'.$code.'">
		<p id="info">' . $info . '</p>
		<div class="control-group">
			<span class="icon-user"></span><input name="username" placeholder="Username" type="text" autocomplete="off" value="' . $username. '">
		</div>

		<div class="control-group">
			<span class="icon-lock"></span><input name="password" placeholder="Password" type="password">
		</div>

		<div class="remember-me">
				<input name="remember" value="1" type="checkbox" id="rm"><label for="rm"> 记住我</label>
		</div>

		<div class="login-btn">
			<input id="login-btn" value="登 录" type="submit" name="submit" onclick="setSafeCookie();return true;">
		</div>
	</form>

</div>

<div id="login-copyright">
	'.date("Y").' &copy; ' . APP_NAME . ' <a href="' . APP_URL . '" target="_blank">为因软件 weensoft.cn</a>
</div>

<script type="text/javascript" src="' . SYSDIR . 'public/jquery.331.js"></script>

<script>

	function setSafeCookie() {
		document.cookie = "' . COOKIE_SAFE . '=' . $cookievalue . '; path=/";
	}

	function reset_window(){
		var margin_top = ($(window).height() - $("#logo").height() - $("#login").height() - $("#login-copyright").height() - 58)/2;

		if(margin_top < 58) margin_top = 8;
		$("#logo").css("margin-top", margin_top+"px");
	}

	$(function(){
		reset_window();

		$(window).on("orientationchange", function(){	reset_window();});
	});

</script>
</body>
</html>';

		exit(); //终止程序继续运行  important !!!!!
	}


 	/**
	 * 登录验证
	 */
   private function check(){
		$username = ForceStringFrom('username');
		$password = ForceStringFrom('password');
		$remember = ForceIntFrom('remember');
		$key = ForceStringFrom('key');
		$code = ForceStringFrom('code');
		$decode = authcode($code, 'DECODE', $key);

		$cookievalue = ForceCookieFrom(COOKIE_SAFE);

		$timenow = time();
		setcookie("welive_login_name", $username, $timenow+3600*24*365, '/'); //记住用户名

		if(!strlen($username) OR !strlen($password)){
			$error = '请输入用户名和密码!';
		}elseif(!isName($username)){
			$error = '用户名存在非法字符!';
		}elseif($cookievalue != md5(WEBSITE_KEY . $key . APP::$_CFG['KillRobotCode'])){
			$error = '验证码不正确!';
		}elseif($decode != md5(WEBSITE_KEY)){
			$error = '验证码过期, 请重新登录!';
		}else{
			$password = md5($password);

			$user = APP::$DB->getOne("SELECT a.aid, a.type FROM " . TABLE_PREFIX . "admin a WHERE a.username = '$username' AND a.password = '$password' AND a.activated = 1");

			if(!$user['aid']){
				$error = '用户不存在或密码错误!';
			}else{//授权成功, 执行相关操作
				$userip = GetIP();
				$sessionid = md5(uniqid($user['aid'] . COOKIE_KEY));
				$agent = md5(substr($_SERVER['HTTP_USER_AGENT'], 0, 252) . WEBSITE_KEY);

				APP::$DB->exe("INSERT INTO " . TABLE_PREFIX . "session (sid, aid, ip, agent, time)
						  VALUES ('$sessionid', '$user[aid]', '$userip', '$agent', '$timenow')");

				APP::$DB->exe("UPDATE " . TABLE_PREFIX . "admin SET online = 0, last = '$timenow', lastip = '$userip', logins = (logins + 1)  WHERE aid = '$user[aid]'");

				$time = Iif($remember, $timenow+3600*24*30, 0);
				setcookie(COOKIE_ADMIN, $sessionid, $time, '/');

				Redirect(); //登录验证成功后跳转到首页
			}
		}

		return $error; //提交数据有错误或验证用户失败, 返回错误信息在登录中显示
	}


	/**
	 * public 退出登录函数logout
	 */
    public function logout(){
		$sessionid = ForceCookieFrom(COOKIE_ADMIN);
		setcookie(COOKIE_ADMIN, '', 0, '/'); //清除cookie

		if($sessionid AND IsAlnum($sessionid)){
			APP::$DB->exe("DELETE FROM " . TABLE_PREFIX . "session WHERE sid = '$sessionid'"); //后台用户退出时删除当前的session
		}

		APP::$DB->exe("DELETE FROM " . TABLE_PREFIX . "vvc WHERE time < " . (time() - 3600*8)); //删除8小时前的验证码
		APP::$DB->exe("DELETE FROM " . TABLE_PREFIX . "session WHERE time < " . (time() - 3600*24*30)); //删除30天前的session

		Redirect(); //退出后跳转到后台首页
	} 

	/**
	 * public 操作权限验证函数 CheckAccess 无输出(用于Ajax)
	 */
	public function CheckAccess() {
		if($this->admin && $this->admin['type'] == 1) return true; //系统管理员
		return false;
	}

	/**
	 * public 操作授权验证输出并输出错误信息
	 */
	public function CheckAction() {
		if(!$this->CheckAccess()){
			Error('您没有进行本次操作的权限!', '权限错误');
		}
	}
}

?>