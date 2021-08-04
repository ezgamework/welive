<?php if(!defined('ROOT')) die('Access denied.');

class c_index extends Mobile{

	public function __construct($path){
		parent::__construct($path);

	}


	public function index(){

		$smilies = ''; //表情图标

		for($i = 0; $i < 24; $i++){
			$smilies .= '<img src="' . SYSDIR . 'public/smilies/' . $i . '.png" ontouchend="insertS(' . $i . ');">';
		}

		$myid = $this->admin['aid'];
		$phrases = ''; //中文常用短语
		$getphrases = APP::$DB->getAll("SELECT msg, msg_en FROM " . TABLE_PREFIX . "phrase WHERE aid = '$myid' AND activated =1 ORDER BY pid ASC");

		foreach($getphrases AS $k => $phrase){
			$phrases .= '<li onclick="insertPhrase(this);"><i>●</i><b>' . $phrase['msg'] . '</b></li>';
			$phrases .= '<li onclick="insertPhrase(this);"><i>●</i><b>' . $phrase['msg_en'] . '</b></li>';
		}

		echo '<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="viewport" content="width=device-width, initial-scale=1,minimum-scale=1, maximum-scale=1, user-scalable=no">
<title>WeLive在线客服</title>
<link rel="shortcut icon" href="public/img/favicon.ico" type="image/x-icon">
<link rel="stylesheet" type="text/css" href="public/swiper.min.css">
<link rel="stylesheet" type="text/css" href="public/app.css?v=' . APP_VERSION . '">
<script type="text/javascript" src="' . SYSDIR . 'public/jquery.331.js"></script>
<script type="text/javascript" src="public/swiper.min.js"></script>
<script type="text/javascript">
var BASEURL = "' . BASEURL . '",
SYSDIR = "' . SYSDIR . '",
WS_HEAD = "' . WS_HEAD . '",
WS_HOST = "' . WS_HOST . '",
WS_PORT = "' . WS_PORT . '",
auth_upload = ' .  ForceInt(APP::$_CFG['AuthUpload']) . ',
upload_filesize = ' . APP::$_CFG['UploadLimit'] . ',
admin ={id: ' . $this->admin['aid'] . ', type: ' . $this->admin['type'] . ', sid: "' . $this->admin['sid'] . '", fullname: "' . $this->admin['fullname'] . '", post: "' . $this->admin['post'] . '", agent: "' . $this->admin['agent'] . '"};
</script>
</head>
<body>
<div class="container">

	<div class="main_top guest_top swiper-container">
		<div class="guest_info"><i id="guest_unreads">0</i>/<i id="guest_onlines">0</i>/<i id="guest_totals">0</i></div>
		<div class="swiper-wrapper"></div>
	</div>

	<div class="main_top support_top loading">
		<div class="header">
			<img src="public/img/support.png" class="avatar">
			<div class="name">客服群</div>
			<div class="online"><i id="support_onlines">0</i> 位在线</div>
		</div>
		<div class="viewport">
			<div class="msg"><i class="i">服务器连接中 ...</i></div>
		</div>
	</div>

	<div class="main_bottom">
		<div class="tool_bar">
			<div id="guest_toolbar" class="none">
				<i id="" class="emotion"></i>
				<i id="" class="toolbar_sound sound_on"></i>
				<i id="toolbar_upload" class="upload_file"></i>
				<i id="toolbar_close" class="close"></i>
				<i id="toolbar_transfer" class="transfer"></i>
				<i id="toolbar_banned" class="banned_on"></i>
				<i id="toolbar_auth" class="none"></i>
			</div>
			<div id="support_toolbar">
				<i id="" class="emotion"></i>
				<i id="" class="toolbar_sound sound_on"></i>
				<i id="toolbar_exit" class="exit"></i>
				<i id="toolbar_serving" class="serving_on"></i>
				<i id="toolbar_man" class="man_on"></i>
			</div>
		</div>

		<div class="bottom_swiper swiper-container">
			<div class="swiper-wrapper">
				<div class="swiper-slide">
					<textarea name="msg_input" id="msg_input"></textarea>
				</div>
				<div class="guest_list swiper-slide no_guest">
					<div class="swiper-container guest_list_swiper">
						<div class="swiper-wrapper"></div>
					</div>
				</div>

				<div id="support_list" class="support_list swiper-slide">
					<div class="swiper-container support_list_swiper">
						<div class="swiper-wrapper"><i class="swiper-slide"><img src="public/img/guest.jpg"><b>登录中</b></i></div>
					</div>
				</div>

			</div>
		</div>

		<div class="btn">
			<a id="guest_btn" class="guest_btn guest_unreads"></a>
			<a id="send_btn" class="unlink">发 送</a>
			<a id="support_btn" class="support_btn curr">· · ·</a>
		</div>
	</div>
	<div id="guest_tips" class=""></div>
	<div id="alert_info"></div>
	<div id="confirm_btn"></div>
	<div class="g_search"></div>
	<div class="smilies_div"><div class="smilies_wrap">' . $smilies . '</div></div>
	<div style="width:1px;height:1px;display:none;overflow:hidden;"><audio src="public/sound1.mp3" id="guest_sounder"></audio></div>
	<div style="width:1px;height:1px;display:none;overflow:hidden;"><audio src="public/sound2.mp3" id="support_sounder"></audio></div>
</div>

<div id="welive_popup" class="welive_popup">
	<div class="popup_wrap">
		<h4 class="popup_title"></h4>
		<p class="popup_content"></p>
		<div class="button_ok">确 定</div>
		<div class="button_cancel">取 消</div>
	</div>
</div>
<div id="welive_big_img" class="welive_popup"><div class="big_img_wrap"></div></div>
<input type="file" name="file" id="upload_input" style="width:1px;height:1px;display:none;overflow:hidden;">
<div class="phrases_div" style="display:none"><div class="phrases_wrap">' . $phrases . '</div></div>

<script type="text/javascript" src="public/app.js?v=' . APP_VERSION . '"></script>
</body>
</html>';
	}

} 

?>