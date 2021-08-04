
//获取cookie
function welive_online_getCookie(n){
	var a = document.cookie.match(new RegExp("(^| )" + n + "=([^;]*)(;|$)"));
	if (a != null) return a[2];
	return "";
}

//原网站用户id ---用户接口
var welive_online_id = welive_online_getCookie("welive_id");

//原网站用户名或昵称fullname ---用户接口
var welive_online_fn = welive_online_getCookie("welive_fn");



//防机器人编码
var welive_online_code = 621276866;

//WeLive访客id
var welive_online_gid = welive_online_getCookie("welive_user");

//welive所在URL
var welive_online_url = document.getElementsByTagName("script");
welive_online_url = welive_online_url[welive_online_url.length-1].src;

var welive_online_groupid = parseInt(welive_online_url.substring(welive_online_url.indexOf("g=") + 2)); //进入指定的客服组id
welive_online_url = welive_online_url.substring(0, welive_online_url.indexOf("welive-new.js"));

//判断是否为移动设备
var welive_online_mobile = navigator.userAgent.match(/(iPhone|Android|iPod|ios|iPad|Windows ce|Windows mobile|Micromessenger|webOS|Ucweb|UCBrowser|BlackBerry|midp|rv:1.2.3.4)/i);

var welive_online_link = "";

//监听加载样式
window.addEventListener("load", function(){

	//当前页面URL
	var url = window.btoa(window.location.href);

	//根据设备跳转的链接不同
	if(welive_online_mobile){ //mobile
		welive_online_link = "mobile/welive-new.php";
	}else{ //web
		welive_online_link = "welive-new.php";
	}

	welive_online_link = welive_online_url + welive_online_link + "?a=" + welive_online_code + "&group=" + welive_online_groupid + "&gid=" + welive_online_gid + "&id=" + welive_online_id + "&fn=" + welive_online_fn + "&r=" + Math.random() + "&url=" + url;

	var welive_targets = document.getElementsByClassName("welive-new");

	for(var i = 0; i < welive_targets.length; i++) {

		welive_targets[i].onclick = function(e){
			e.preventDefault();

			var open_link = welive_online_link;
			var groupid = parseInt(this.getAttribute("group"));

			if(groupid){
				open_link = welive_online_link.replace(/&group=(\w*)&/g, "&group=" + groupid + "&");
			}

			window.open(open_link);
			return false;
		}
	}
});