
/* WeLive mobile.js  @Copyright weensoft.cn */

//显示大图片
function show_img(me, width, height){
	var layer = $("#welive_big_img");
	var window_width =$(window).width();
	var window_height =$(window).height();

	var new_w = 1, new_h = 1, new_top = 0, new_left = 0;

	if(window_height < 1) window_height = 1;

	if(width/height >= window_width/window_height){
		new_w = width;
		if(new_w > window_width) new_w = window_width;
		new_h = height * new_w / width;
	}else{
		new_h = height;
		if(new_h > window_height) new_h = window_height;
		new_w = width * new_h / height;
	}

	new_w = parseInt(new_w);
	new_h = parseInt(new_h);

	new_left = parseInt((window_width - new_w)/2);
	new_top = parseInt((window_height - new_h)/2);

	//点击或者触控弹出层外的半透明遮罩层, 关闭弹出层
	layer.bind("click",  function(e) {
		layer.hide();
		$(this).unbind("click");
	});

	layer.children('.big_img_wrap').css({top: new_top, left: new_left, width: new_w, height: new_h}).html('<img src="' + me.src + '" style="width: ' + new_w + 'px;height: ' + new_h + 'px;">');

	layer.fadeIn(200);
}

//JQ闪动特效  ele: JQ要闪动的对象; cls: 闪动的类(className); times: 闪动次数
function shake(ele, cls, times){
	var i = 0, t = false, o = ele.attr("class")+" ", c = "", times = times||3;
	if(t) return;
	t= setInterval(function(){
		i++;
		c = i%2 ? o+cls : o;
		ele.attr("class",c);
		if(i==2*times){
			clearInterval(t);
			ele.removeClass(cls);
		}
	},200);
}

//表单验证
function validate_input(value, name){
	value = $.trim(value); //去掉空格, 并检查
	if(!value) return false;

	switch(name){
		case "fullname": var pattern = /^[\w\s\.\-\u0391-\uFFE5]{2,30}$/; break;
		case "email": var pattern = /^\w+([-+.]\w+)*@\w+([-.]\w+)+$/i; break;
		case "vvc": var pattern = /^[\d+]{1,4}$/; break;
		case "content":
			var len = value.length;
			if(len < 6 || len > 600) return false;
			break;
	}

	if(name && pattern){
		return pattern.test(value);
	}else{
		return true;  //没有正则比较时, 返回成功
	}
}

//Ajax封装
var ajax_isOk = 1;
function ajax(url, send_data, callback) {
	if(!ajax_isOk) return false;
	$.ajax({
		url: url,
		data: send_data,
		type: "post",
		cache: false,
		dataType: "json",
		beforeSend: function(){ajax_isOk = 0;},
		complete: function(){ajax_isOk = 1;},
		success: function(data){
			if(callback)	callback(data);
		},
		error: function(XHR, Status, Error) {
			welive.status = 1;
			file_temp_data = "";
			show_alert("ajax error!");
		}
	});
}

//设置cookie
function setCookie(n,val,d) {
	var e = "", chromeFix = "";

	if(d) {
		var dt = new Date();
		dt.setTime(dt.getTime() + parseInt(d)*24*60*60*1000);
		e = "; expires="+dt.toGMTString();
	}

	//修改chrome浏览器(v80以上)https下跨域记不住访客的问题
	if(WS_HEAD == "wss://") chromeFix = " SameSite=None; Secure;";
	document.cookie = n+"="+val+e+";" + chromeFix + " path=/";
}

//获取cookie
function getCookie(n) {
	var a = document.cookie.match(new RegExp("(^| )" + n + "=([^;]*)(;|$)"));
	if (a != null) return a[2];
	return '';
}

//将json数据转换成json对象
function parseJSON(data) {
	if(window.JSON && window.JSON.parse) return window.JSON.parse(data);
	if(data === null) return data;
	if(typeof data === "string") {
		data = $.trim(data);
		if(data) {
			var rvalidchars = /^[\],:{}\s]*$/,
				rvalidbraces = /(?:^|:|,)(?:\s*\[)+/g,
				rvalidescape = /\\(?:["\\\/bfnrt]|u[\da-fA-F]{4})/g,
				rvalidtokens = /"[^"\\\r\n]*"|true|false|null|-?(?:\d+\.|)\d+(?:[eE][+-]?\d+|)/g;

			if(rvalidchars.test(data.replace(rvalidescape, "@").replace(rvalidtokens, "]").replace(rvalidbraces, ""))) {
				return (new Function("return " + data))();
			}
		}
	}
	return false;
}


//获得计算机当前时间
function addZeros(value, len) {
	var i;
	value = "" + value;
	if (value.length < len) {
		for (i=0; i<(len-value.length); i++)
			value = "0" + value;
	}
	return value;
}

function getLocalTime() {
	var date = new Date();

	return addZeros(date.getHours(), 2) + ':' + addZeros(date.getMinutes(), 2) + ':' + addZeros(date.getSeconds(), 2);
}


//显示警告信息
function show_alert(info, time) {
	alert_info.html(info).show();

	setTimeout(function() {
		alert_info.hide();
	}, time ? time : 4000);
}


//格式化输出信息
function format_output(data) {
	data = data.replace(/\\n/g, "<br/>");
	//生成URL链接
	data = data.replace(/((((https?|ftp):\/\/)|www\.)([\w\-]+\.)+[\w\.\/=\?%\-&~\':+!#;]*)/ig, function($1){return getURL($1);});
	//将表情代码换成图标路径
	data = data.replace(/\[:(\d*):\]/g, '<img src="' + SYSDIR + 'public/smilies/$1.png">').replace(/\\/g, '');
	//换行
	data = data.replace(/\&lt;br\/\&gt;/g, "<br/>");
	return data;
}

//格式化生成URL
function getURL(url, limit) {
	if(!limit) limit = 60;
	var urllink = '<a href="' + (url.substr(0, 4).toLowerCase() == 'www.' ? 'http://' + url : url) + '" target="_blank" title="' + url + '">';
	if(url.length > limit) {
		url = url.substr(0, 30) + ' ... ' + url.substr(url.length - 18);
	}
	urllink += url + '</a>';
	return urllink;
}

//插入表情符号
function insertSmilie(code) {
	code = '[:' + code + ':]';
	var obj = msger[0];

	var selection = document.selection;
	obj.focus();

	if(typeof obj.selectionStart != 'undefined') {
		var opn = obj.selectionStart + 0;
		obj.value = obj.value.substr(0, obj.selectionStart) + code + obj.value.substr(obj.selectionEnd);
	} else if(selection && selection.createRange) {
		var sel = selection.createRange();
		sel.text = code;
		sel.moveStart('character', -code.length);
	} else {
		obj.value += code;
	}
}


//socket连接
function welive_link(){
	welive.ws = new WebSocket(WS_HEAD + WS_HOST + ':'+ WS_PORT);
	welive.ws.onopen = function(){setTimeout(function(){welive_verify();}, 100);}; //连接成功后, 小延时再验证用户, 否则IE下刷新时发送数据失败
	welive.ws.onclose = function(){welive_close();};
	welive.ws.onmessage = function(get){welive_parseOut(get);};
}

//重连按钮动作
function welive_relink(me){
	welive_link();
	$(me).parents('.msg').remove();
	return false;
}


//记住访客id
function remember_guest(gid){

	if(!guest.gid || guest.gid != gid){

		guest.gid = gid; //新客人更新ID号, 重新连接时用

		setCookie(COOKIE_USER, gid, 365); //写cookie, 记住ID号

		var new_gid = getCookie(COOKIE_USER); 
		
		//写入后立即读出, 如果没有值, 说明浏览器禁止了第三方cookie, 使用hash方式实现跨域
		if(!new_gid){
			/*
			var ifrproxy = document.createElement('iframe');
			ifrproxy.style.display = 'none';
			ifrproxy.src = guest.fromurl + "#" + gid;
			document.body.appendChild(ifrproxy);
			*/

			$(document.body).append('<iframe id="welive_null" src="' + guest.fromurl + "#" + gid + '" style="width:1px;height:1px;overflow:hidden;display:none;"></iframe>');

			//18秒后删除
			setTimeout(function(){	$("#welive_null").remove();}, 18000);
		}
	}
}


//将客人发出的未读消息标记为已读
function remove_unread(){
	historyport.find("s.un").html(langs.readed).removeClass("un");
}

//解析数据并输出
function welive_parseOut(get){
	var d = false, type = 0, data = parseJSON(get.data);
	if(!data) return; //没有数据返回

	switch(data.x){

		case 5: //客人与客服对话
			if(data.a == 1){ //客服发来的
				welive.playSound = 1;
				type = 1; d = data.i;

				if(welive.is_robot){
					remove_unread(); //机器人工作, 访客发出信息返回时, 立即将“未读”改为“已读”

					setTimeout(function() {
						welive.status = 1; //机器人回复输出后才能发送第二条

						if(typeof data.av != 'undefined' && data.av != ''){
							clearTimeout(ttt_4);

							welive_op.find("#welive_avatar").attr("src", SYSDIR + "avatar/" + data.av);

							ttt_4 = setTimeout(function() {
								welive_op.find("#welive_avatar").attr("src", SYSDIR + "avatar/robot/0.png");
							}, 60000); //1分钟后头像恢复
						}

						historyport.children(".robot_typing").remove(); //清除机器人思考标志

						welive_output(d, type); //输出

					}, 800); //信息延迟显示

					return;
				}
			}else{ //自己发出的对话
				type = 2; d = welive.msg.replace(/</g, "&lt;").replace(/>/g, "&gt;"); //防止自己发js代码时发生显示错误
				if(!welive.is_robot) welive.status = 1; //发送完成允许发送第二条信息
				sender.removeClass('loading1');
			}

			break;

		case 8: //人工客服发来的标记已读信息

			remove_unread();
			return true;

			break;

		case 6: //客服特别操作及反馈信息
			switch(data.a){

				case 8: //客人登录成功
					welive.linked = 1; //连接成功
					welive.status = 1; //允许发信息
					welive.autolink = 1; //允许自动重连
					welive_relink_times = 8; //重连次数

					welive.is_robot = parseInt(data.irb); //系统是否为无人值守状态

					guest.fn = data.fn; //客人姓名
					guest.aid = data.aid; //更新客服的id, 重新连接时用
					guest.an = data.an; //客服姓名
					guest.au = parseInt(data.au); //上传授权, 强制转成数字1或0, 方便判断, JS里if("0") 是true, php里为false

					 //更新头像及身份
					welive_name = data.an;
					welive_duty = data.p;
					welive_op.find("#welive_avatar").attr("src", SYSDIR + "avatar/" + data.av);
					welive_op.find("#welive_name").html(welive_name);
					welive_op.find("#welive_duty").html(welive_duty);
					historyport.removeClass('loading');
					sender.removeClass('loading1');

					//如果有聊天记录时, 先输出记录
					var recs = '';
					$.each(data.re, function(i, rec){

						if(rec.ft == 1){//上传图片记录
							var img_arr = rec.m.split("|");
							var img_w = parseInt(img_arr[1]);
							var img_h = parseInt(img_arr[2]);

							if(img_w < 1) img_w = 1;
							var img_h_new = parseInt(img_h * 240 / img_w); //CSS样式中已确定宽度为240

							var rec_i = '<div class="sending_div" style="height:' + img_h_new + 'px;"><img src="' + SYSDIR + "upload/img/" + img_arr[0] + '" class="sending_img" onclick="show_img(this, ' + img_w + ', ' + img_h + ');"></div>';

						}else if(rec.ft == 2){ //上传的文件记录
							var file_arr = rec.m.split("|");

							if(rec.t == 1){ //客服的
								var rec_i = '<a href="' + SYSDIR + 'upload/file/' + file_arr[0] + '" target="_blank" download="' + file_arr[1] + '" class="down"><img src="' + SYSDIR + 'public/img/save.png">&nbsp;&nbsp;' + langs.click_download +  file_arr[1] + '</a>';
							}else{ //自己的
								var rec_i = file_arr[1] + "<br>... " + langs.upload_done;
							}
						}else{
							var rec_i = format_output(rec.m);
						}

						rec_i = rec_i.replace(/\\n/g, "<br/>");
						if(rec.t == 1){ //客服的
							if(rec.fid == guest.aid){
								var welive_duty_i = welive_duty;
							}else{
								var welive_duty_i = langs.welive;
							}

							recs += '<div class="msg l"><div class="a">' +  rec.f + ' - ' + welive_duty_i + '<i>' + rec.d + '</i></div><b></b><div class="b"><div class="i">' +rec_i + '</div></div></div>';

						}else{ //自己的

							recs += '<div class="msg r"><b class="welive_p_' + COLOR_STYLE + '"></b><div class="b welive_cb_' + COLOR_STYLE + '"><div class="i">' + rec_i + '</div></div><i>' + rec.d + '</i></div>';
						}
					});

					historyport.append('<div class="msg s"><div class="b"><div class="i">' + langs.connected + '</div></div></div>'); //连接成功

					if(recs != '') {
						recs += '<div class="msg s"><div class="b"><div class="i">' + langs.records + '</div></div></div>';
						historyport.append(recs); //输出
					}

					//初始化上传图片按钮样式
					if(welive.is_robot || (!guest.au && auth_upload)) $("#toolbar_photo").addClass("photo_off");

					remember_guest(data.gid); //记住访客id

					//产生一个session会话记录, 用于验证上传图片, 留言等, 以免产生非法操作
					if(data.sess){
						guest.sess = data.sess; //解决safari禁止第三方cookie的问题
						setCookie(COOKIE_USER + "_sess", data.sess, 0); //随进程消失
					}

					welive.playSound = 0; //连接成功后不播放声音, 因为无效
					type = 8; d = welcome;

					autoOffline(); //启动自动离线

					//非机器人服务时, 启动实时输入状态
					if(!welive.is_robot) {
						welive.temp = '';
						welive_runtime();
						trans_to_btn.hide();
					}else{
						trans_to_btn.show();
					}

					//启动心跳, 即每隔26秒自动发送一个特殊信息, 解决IE下30秒自动断线的问题
					ttt_1 = setInterval(function() {
						//只要连接状态, 均要发送心跳数据, 设置一个怪异的数字避免与自动离线的时间间隔重合, 避免在同一时间点上send数据上可能产生 -----幽灵bug
						welive_send({type: "ping"});
					}, 26125);

					break;

				case 1: //客服重新上线

					clearTimeout(welive.ttt_3); //清除客服离线时自动转接

					welive.status = 1;
					welive.playSound = 1;
					type = 3; d = guest.an + langs.aback;

					break;

				case 2: //客服离线
					welive.playSound = 1;
					welive.status = 0;
					type = 4; d = guest.an + langs.offline;

					//1分钟后发送请求重新分配客服的请求
					welive.ttt_3 = setTimeout(function(){
						welive_send({type: "g_handle", operate: "redistribute"});
					}, 59973);

					break;

				case 4: //重复连接返回的指令
					welive.status = 0;
					welive.autolink = 0; //不允许自动重连
					type = 4; d = langs.relinked + '<br><a onclick="welive_relink(this);" class="relink welive_color_' + COLOR_STYLE + '">' + langs.rebtn + '</a>';

					break;

				case 5: //客人自动离线返回的通知
					welive.status = 0;
					welive.autolink = 0; //不允许自动重连

					welive.playSound = 1;
					type = 4; d = langs.autooff + '<br><a onclick="welive_relink(this);" class="relink welive_color_' + COLOR_STYLE + '">' + langs.rebtn + '</a>';

					break;

				case 6: //被踢出
					welive.autolink = 0; //不允许自动重连

					welive.playSound = 1;
					type = 4; d = langs.kickout;

					break;

				case 7: //被禁言
					welive.status = 0;
					welive.autolink = 0; //不允许自动重连

					welive.playSound = 1;
					type = 4; d = langs.banned;

					break;

				case 9: //无客服在线时
					welive.status = 0;
					welive.autolink = 0; //不允许自动重连
					welive.linked = 0; //伪装成未连接, 在关闭连接时切换到留言板

					guest.group = data.i; //用于留言板展开时判断是否显示：客服组关闭或不存在信息

					break;

				case 10: //解除禁言
					welive.status = 1;
					welive.autolink = 1; //允许自动重连

					welive.playSound = 1;
					type = 3; d = langs.unbann;

					break;

				case 11: //被转接
					welive.status = 1;
					welive.autolink = 1; //允许自动重连

					if(welive.is_robot) clearTimeout(ttt_4); //转接前是机器人服务时, 阻止机器人头像延迟变换
					welive.is_robot = parseInt(data.irb); //系统是否为无人值守状态

					guest.aid = data.aid; //更新客服的id, 重新连接时用
					guest.an = data.an; //客服姓名
					guest.au = parseInt(data.au); //上传权限

					//初始化上传图片按钮样式
					$("#toolbar_photo").removeClass("photo_off");
					if(welive.is_robot || (!guest.au && auth_upload)) $("#toolbar_photo").addClass("photo_off");

					 //更新头像及身份
					welive_name = data.an;
					welive_duty = data.p;

					welive_op.find("#welive_avatar").attr("src", SYSDIR + "avatar/" + data.av);
					welive_op.find("#welive_name").html(welive_name);
					welive_op.find("#welive_duty").html(welive_duty);

					 //机器人服务时, 转人工服务按钮状态等
					if(welive.is_robot) {
						clearInterval(welive.ttt_2); //清除提交实时输入状态
						trans_to_btn.show();
					}else{
						welive.temp = '';
						welive_runtime(); //启动实时输入状态
						trans_to_btn.hide();
					}

					welive.playSound = 1;
					type = 3; d = langs.transfer + data.an;

					break;

				case 13: //请求回拨电话 返回

					welive.status = 1;
					$("#phone_num").val("");

					type = 2; d = '<div class="spec_info">' + welive.msg + '</div>';

					break;

				case 14: //评价返回

					if(data.s == "1"){
						welive.playSound = 1;
						type = 1; d = '<font color=red>' + langs.rating_thanks + '</font>[:16:]';
					}else{
						show_alert(langs.rating_limit, 6000);
						return false;
					}

					break;

				case 15: //请求转人工客服 返回

					welive.status = 1;

					if(data.s == "2"){
						show_alert(langs.trans_to_failed, 4000);
					}

					return true;

					break;
			}

			break;

		case 7: //上传图片等
			switch(data.a){

				case 2: //客人上传图片通知客服后返回的信息--表示上传成功

					//进度条消失(指最后一个上传图片的进度条)
					clearInterval(ttt_2);
					sending_mask.remove();

					welive.status = 1; //允许发送信息

					autoOffline();//再启动

					break;

				case 4: //客服发来的图片

					type = 1; //客服发来的
					welive.playSound = 1; //声音

					if(data.w <1) data.w = 1;
					var img_h = parseInt(data.h * 240 / data.w); //CSS样式中已确定宽度为240

					d = '<div class="sending_div" style="height:' + img_h + 'px;"><img src="' + SYSDIR + 'upload/img/' + data.i + '" class="sending_img" onclick="show_img(this, ' + data.w + ', ' + data.h + ');"></div>';

					break;

				case 1: //客服授权上传
					guest.au = 1;
					$("#toolbar_photo").removeClass("photo_off");

					welive.playSound = 1;
					type = 3; d = langs.got_upload_auth;

					break;

				case 3: //客服解除上传授权
					guest.au = 0;
					$("#toolbar_photo").addClass("photo_off");

					welive.playSound = 1;
					type = 4; d = langs.lost_upload_auth;

					break;

				case 5: //客人上传文件通知客服后返回的信息--表示上传成功

					//进度条消失(指最后一个上传文件的进度条)
					clearInterval(ttt_2);
					sending_mask.remove();
					historyport.find(".uploading_info:last").html("... " + langs.upload_done).css({"color":"blue", "font-weight":"bold"});

					welive.status = 1; //允许发送信息
					autoOffline();//再启动

					break;

				case 6: //客服上传文件
					type = 1; //客服发来的
					welive.playSound = 1; //声音

					d = '<a href="' + SYSDIR + 'upload/file/' + data.i + '" target="_blank" download="' + data.o + '" class="down"><img src="' + SYSDIR + 'public/img/save.png">&nbsp;&nbsp;'  + langs.click_download +  data.o + '</a>';

					break;

			}

			break;
	}

	welive_output(d, type); //输出
}

//交流输出信息
function welive_output(d, type){
	if(d === false || !type) return; //没有信息及类型返回

	if(welive.playSound){
		if(welive.sound) sounder.play();
		welive.playSound = 0;
	}

	//如果不用try, iframe跨域时运行出错
    try{
		//如果未展开, 有新信息时提示
		if(!window.parent.welive_opened && welive.linked){
			window.parent.welive_online_tips.innerHTML = parseInt(window.parent.welive_online_tips.innerHTML) + 1;
			window.parent.welive_online_tips.style.display = "block";
		}
     }catch(e){}


	switch(type){
		case 1: //客服
			d = '<div class="msg l"><div class="a">' + welive_name + ' - ' + welive_duty + '<i>' + getLocalTime() + '</i></div><b></b><div class="b"><div class="i">' + format_output(d) + '</div></div></div>';
			break;
		case 2: //客人
			d = '<div class="msg r"><b class="welive_p_' + COLOR_STYLE + '"></b><div class="b welive_cb_' + COLOR_STYLE + '"><div class="i">' + format_output(d) + '</div></div><i>' + getLocalTime() + '<br><s class="un">' + langs.unread + '</s></i></div>';
			break;
		case 3: //正常提示
			d = '<div class="msg s"><div class="b"><div class="i">' + d + '</div></div></div>';
			break;
		case 4: //错误提示
			d = '<div class="msg e"><div class="b"><div class="i">' + d + '</div></div></div>';
			break;
		case 8: //问候语, 不解析URL
			d = '<div class="msg l"><div class="a">' + welive_name + ' - ' + welive_duty + '<i>' + getLocalTime() + '</i></div><b></b><div class="b"><div class="i">' + d + '</div></div></div>';
			break;
	}

	historyport.append(d);

	//机器人工作时, 访客发送的信息显示后, 添加机器思考标志
	if(type == 2 && welive.is_robot){
		historyport.children(".robot_typing").remove();

		d = '<div class="msg l robot_typing"><div class="a">' + welive_name + ' - ' + welive_duty + '<i></i></div><b></b><div class="b"><div class="i"></div></div></div>';

		historyport.append(d);
	}

	scroll_bottom(); //滚动到底部 
}

//访客连接验证
function welive_verify(){
	welive.linked = 1; //websocket已连接
	welive_send({type: "login", from: "front", group: guest.group, gid: guest.gid, oid: guest.oid, fn: guest.fn, au: guest.au, aid: guest.aid, lang: guest.lang, key: SYSKEY, code: SYSCODE, fromurl: guest.fromurl, agent: guest.agent, mobile: 1});
}

//连接断开时执行
function welive_close(){
	welive.status = 0; //不允许发信息

	clearInterval(ttt_1); //连接断开后停止发送心跳数据
	clearInterval(welive.ttt_2); //更新输入状态
	clearTimeout(welive.ttt_3); //清除客服离线时自动转接

	if(welive.autolink){ //允许重连
		if(welive_relink_times > 0){
			welive_relink_times -= 1;
			welive_output(langs.failed, 4);
			setTimeout(function(){welive_link();}, 6000); //6秒后自动重连
		}else{
			welive_comment();
		}
	}else if(!welive.linked){ //之前没有连接, 表示首次连接失败时, 或者已连接但没有客服在线, 切换到留言页面, 不再重试连接
		welive_comment();
	}

	welive.linked = 0; //标记连接失败
}


//发送信息(直接)
function welive_send(d){
	var re = 0;

	if(welive.linked){
		re = 1;
		welive.ws.send(JSON.stringify(d)); //将json对象转换成字符串发送
	}

	return re; //回返是否成功
}

//发送信息
function welive_send_msg(){

	window.scrollTo(0,0); //兼容iphone微信浏览器输入框的bug

	//请回拨电话
	if(is_callback){ 
		send_callback_phone();
		return true;
	}

	if(welive.status) {

		var msg = $.trim(msger.val());

		if(msg){
			if(msg.length > 2048){
				show_alert(langs.msg_too_long, 2000);
				return false;
			}

			welive.temp = ''; //终止实时输入提交数据
			sender.addClass('loading1');
			welive.msg = msg; //先记录客人的发言

			msg = {type: "msg", sendto: "back", msg: msg};
			if(!welive_send(msg)) return false;

			msger.val('');
			welive.status = 0; //发送后，改变状态避免未完成时发送第二条信息

			autoOffline(); //信息发送完成后, 自动离线计时开始
		}
	}

	close_dialogs();
	search_result_hide(); //关闭搜索结果
}

//自动离线
function autoOffline(){
	if(! welive.linked) return; //如果未连接, 无需要自动离线

	if(welive.ttt_1) clearTimeout(welive.ttt_1);//清除自动离线

	welive.ttt_1 = setTimeout(function(){
		welive_send({type: "g_handle", operate: "offline"}); //发送一条自动离线指令
	}, offline_time);
}

//启动输入状态更新
function welive_runtime(){
	welive.ttt_2 = setInterval(function(){

		if(welive.status) {

			var msg = $.trim(msger.val());

			if(msg && msg != welive.temp){

				welive_send({type: "runtime", msg: msg});
				welive.temp = msg; //记录正在输入的信息

			//清空输入框后, 给客服发通知, 去掉输入状态
			}else if(!msg && welive.temp){

				welive_send({type: "runtime", msg: ""});
				welive.temp = '';
			}
		}

	}, update_time);
}

//进入留言板
function welive_comment(){
	shakeobj = function(obj){shake(obj, "shake");obj.focus();return false;};
	welive.sound = 0;

	historyport.removeClass('loading');
	drag_to_bottom = drag_to_bottom - 105;
	historyport.height($(window).height() - drag_to_bottom);
	$("#alert_info").css("bottom", "41px");

	historyport.html("");

	//客服组关闭或不存在
	if(guest.group == 88888888){
		historyport.html('<div class="team_off">' + langs.team_off + '</div>');
		$(".enter").html("");
		return;
	}

	$(".enter").html('').addClass('comment_enter').html('<div id="alert_info"></div><a class="sender comment_send welive_color_' + COLOR_STYLE + '" onclick="submit_comment();return false;">' + langs.submit + '</a>');

	welive_op.find("#welive_avatar").attr("src", SYSDIR + "mobile/img/welive.png");
	welive_op.find("#welive_name").html(langs.leavemsg);
	welive_op.find("#welive_duty").html(langs.nosuppert);

	var vid = 0;
	$.ajaxSetup({async: false}); //设置ajax为同步!!!
	ajax(SYSDIR + 'welive-ajax.php?ajax=1&act=vvc', {key:SYSKEY, code:SYSCODE}, function(data){
		vid = parseInt(data.s);
	});
	$.ajaxSetup({async: true});

	historyport.append('<div class="comment"><div class="comment_note">' + comment_note + '</div><form id="comment_form" onsubmit="return false;"><input type="hidden" name="act" value="comment"><input type="hidden" name="vid" value="' + vid + '"><input type="hidden" name="key" value="' + SYSKEY + '"><input type="hidden" name="code" value="' + SYSCODE + '"><li><s>' + langs.yourname + ':</s><input name="fullname" type="text"><i>*</i></li><li><s>' + langs.email + ':</s><input name="email" type="text"></li><li><s>' + langs.phone + ':</s><input name="phone" type="text"></li><li><s>' + langs.content + ':</s><textarea name="content"></textarea><i>*</i></li><li><s></s><img src="' + SYSDIR + 'welive-ajax.php?ajax=1&act=get&vid='+ vid +'" onclick="ChangeCaptcha(this);" title="' + langs.newcaptcha + '"> = <input name="vvc" type="text" class="vvc"><i>*</i></li></form></div>');
}

//更新验证码
function ChangeCaptcha(i){i.src= i.src + '&' + Math.random();}

//提交留言
function submit_comment(){
	$("#alert_info").hide(); //隐藏alert

	//使用cookie限制每天留言次数
	var welive_comms = getCookie(COOKIE_USER + "_comms");
	welive_comms = parseInt(welive_comms);

	if(!welive_comms || welive_comms < 1) welive_comms = 1;
	if(welive_comms > 5){
		show_alert(langs.comm_alert);
		return false;
	}

	var form = $("#comment_form");
	var fullname = form.find("input[name=fullname]");
	var email = form.find("input[name=email]");
	var content = form.find("textarea[name=content]");
	var vvc = form.find("input[name=vvc]");

	if(!validate_input(fullname.val(), 'fullname')) return shakeobj(fullname);
	//if(!validate_input(email.val(), 'email')) return shakeobj(email);
	if(!validate_input(content.val(), 'content')) return shakeobj(content);
	if(!validate_input(vvc.val(), 'vvc')) return shakeobj(vvc);

	ajax(SYSDIR + 'welive-ajax.php?ajax=1&grid=' + guest.group + '&gid=' + guest.gid, form.serialize(), function(data){
		if(data.s == 0){
			show_alert(langs.badcookie); //验证码过期

		}else if(data.s == 1){ //留言保存成功
			$(".enter").remove();
			historyport.html('<div class="comsaved">' + langs.saved + '</div>');

			setTimeout(function(){

				setCookie(COOKIE_USER + "_comms", (welive_comms + 1), 1); //写cookie, 记住第几次留言

				//如果不用try, iframe跨域时运行出错
				try{
					window.parent.welive_close_btn.click(); //3秒后自动关闭
				}catch(e){}

			}, 3000);

		}else if(data.s == 2){
			shakeobj(fullname);
		}else if(data.s == 3){
			shakeobj(email);
		}else if(data.s == 4){
			shakeobj(content);
		}else if(data.s == 5){
			shakeobj(vvc);
		}
	});

}

//读取图片文件
function readImageFile(file, obj){
	var reader = new FileReader();
	reader.onload = function(e) {
		var img = new Image();
		img.src = reader.result;

		img.onload = function() {
			var w = img.width,
				h = img.height;

			if(file.type.toLowerCase() == 'image/gif'){

				var base64 = e.target.result; //可保持gif动画效果

			}else{

				var canvas = document.createElement('canvas');

				var ctx = canvas.getContext('2d');
				$(canvas).attr({width: w,	height: h});
				ctx.drawImage(img, 0, 0, w, h);

				var base64 = canvas.toDataURL(file.type, 0.6); //canvas对JPG图片有压缩效果, gif, png
			}

			var result = {
				url: window.URL.createObjectURL(file),
				w: w,
				h: h,
				size: file.size,
				type: file.type,
				base64: base64.substr(base64.indexOf(',') + 1),
			};

			welive_send_img(result);
		};

		img.onerror = function() {
			$(obj).val("");
			show_alert(langs.img_badtype);
		};

	};

	reader.readAsDataURL(file);
}


//验证上传权限
function check_upload_auth(){
	if(welive.is_robot){
		show_alert(langs.no_upload_robot);
		return false;
	}else if(!guest.au && auth_upload){
		show_alert(langs.no_upload_auth);
		return false;
	}

	return true;
}

//传送图片, data对象属性: size文件大小(字节), type(文件类型), base64(图片纯净的base64代码)
function welive_send_img(data){
	$("#upload_img").val("");

	//验证上传权限
	if(!check_upload_auth()) return;

	//限定文件大小及类型
	if(data.size > 1024 * 4000){
		show_alert(langs.img_limit);
		return;
	}

	if($.inArray(data.type.toLowerCase(), ["image/jpg","image/jpeg","image/png","image/gif"]) < 0) {
		show_alert(langs.img_badtype);
		return;
	}

	if(welive.status && welive.linked) {

		welive.status = 0; //图片上传时不允许发送信息

		var sending_img_w = data.w; //图片的宽度
		var sending_img_h = data.h; //图片的宽度

		if(sending_img_w < 1) sending_img_w = 1;
		sending_mask_h = parseInt(sending_img_h * 240 / sending_img_w); //CSS样式中已确定宽度为240, 此处按css设置的值调整

		var img_str = '<div class="sending_div" style="height:' + sending_mask_h + 'px;"><img src="' + data.url + '" class="sending_img" onclick="show_img(this, ' + data.w + ', ' + data.h + ');"><div class="sending_mask" style="line-height:' + sending_mask_h + 'px;height:' + sending_mask_h + 'px;">' + langs.uploading + '</div></div>';

		var d = '<div class="msg r"><b class="welive_p_' + COLOR_STYLE + '"></b><div class="b welive_cb_' + COLOR_STYLE + '"><div class="i">' + img_str + '</div></div><i>' + getLocalTime() + '<br><s class="un">' + langs.unread + '</s></i></div>';
		historyport.append(d);
		scroll_bottom(); //滚动到底部

		//显示上传进度
		sending_mask = historyport.find(".sending_mask:last"); //仅处理最后一次上传的图片, jq1.2.6不支持last()
		ttt_2 = setInterval(function(){
			if(sending_mask_h < 40){
				clearInterval(ttt_2);
				return;
			}else{
				sending_mask_h -= 20;
				sending_mask.css({"height":sending_mask_h + "px", "line-height":sending_mask_h + "px"});
			}
		}, 100);

		$.ajaxSetup({async: true}); //设置ajax异步
		ajax(SYSDIR + 'welive-ajax.php?ajax=1&act=uploadimg', {gid: guest.gid, sess: guest.sess, key: SYSKEY, code: SYSCODE, img_type: data.type, img_base64: data.base64}, function(data){

			if(data.s == 1){ //上传成功后
				
				//返回的文件名传送给客服
 				welive_send({type: "g_handle", operate: "uploadimg", filename: data.i, width: sending_img_w, height: sending_img_h}); //w,h指图片的宽,高(像素)

			}else{
				clearInterval(ttt_2); //上传进度停止
				show_alert(data.i, 6000); //上传失败
				sending_mask.html(langs.upload_failed).css({"color":"red", "font-weight":"bold"});

				welive.status = 1; //允许发送信息
			}
		});
	}
}


//上传文件
function welive_upload_file(){

	var upload_file_input = $("#wl_uploadfile");

	if(upload_file_input[0]){
		upload_file_input[0].click(); //兼容IE
		return;
	}

	var filetype = ["exe", "cmd", "com", "bat", "sys"]; //不允许上传的文件后缀
	var imagetype = ["jpg", "gif", "png", "jpeg"]; //图片文件后缀

	$(".enter").append('<input type="file" id="wl_uploadfile" style="display:none;">');

	upload_file_input = $("#wl_uploadfile");

	upload_file_input.change(function(){
		try {
			if(!welive.status) return;

			var filepath = $.trim($(this).val());

			if(filepath == "") return;

			var file = this.files[0];
			var filename = file.name;

			var dot_index = filename.lastIndexOf(".");
			var file_ext = filename.substring(dot_index + 1).toLowerCase();

			if(filename == '' || dot_index < 1 || $.inArray(file_ext, filetype) > -1) {
				$(this).val("");
				show_alert(langs.bad_filetype);
				return false;
			}else if($.inArray(file_ext, imagetype) > -1){ //也可以传图片
				readImageFile(file, this); //读取并传送图片			
				return true;
			}

			//判断文件大小
			if (file.size > upload_filesize * 1024*1024) {
				$(this).val("");
				show_alert(langs.bad_filesize + upload_filesize + "M");
				return false;
			}

			var reader = new FileReader();

			reader.onerror = function(e) {
				show_alert(langs.upload_failed, 6000); //上传失败
			}

			var isIE = 0;
			var this_input = $(this);

			reader.onload = function(e) {
				//ajax上传
				welive.status = 0; //上传时不允许发送信息

				if(isIE){ //兼容IE
					var binary = "";
					var bytes = new Uint8Array(e.target.result);
					var length = bytes.byteLength;
					for (var i = 0; i < length; i++) {
						binary += String.fromCharCode(bytes[i]);
					}

					file_temp_data = binary; //记录文件内容
				}else{
					file_temp_data = reader.result; //记录文件内容
				}

				//上传进度条
				var d = '<div class="msg r"><b class="welive_p_' + COLOR_STYLE + '"></b><div class="b welive_cb_' + COLOR_STYLE + '"><div class="i">' + filename + '<br><div class="uploading_info">' + langs.uploading + '</div><div class="sending_div file_upload"><div class="uploading_mask"></div></div></div></div><i>' + getLocalTime() + '<br><s class="un">' + langs.unread + '</s></i></div>';

				historyport.append(d);
				scroll_bottom(); //滚动到底部

				sending_mask_h = 240;
				sending_mask = historyport.find(".uploading_mask:last"); //仅处理最后一次上传进度, jq1.2.6不支持last()
				ttt_2 = setInterval(function(){
					if(sending_mask_h < 40){
						clearInterval(ttt_2);
						return;
					}else{
						sending_mask_h -= 20;
						sending_mask.css({"width": sending_mask_h + "px"});
					}
				}, 100);

				//ajax切片上传文件
				var total_chunks = Math.ceil(file_temp_data.length / file_chunk_size); //总片数
				ajax_upload_file(1, total_chunks, file_ext, filename, "");

				this_input.val(""); //清除文件input, 否则无法重复上传相同文件
			};

			if (typeof reader.readAsBinaryString != "undefined") {
				reader.readAsBinaryString(file);
			} else {
				isIE = 1;
				reader.readAsArrayBuffer(file); //兼容IE
			}

		} catch(e) {
			//
		}
	});

	upload_file_input[0].click(); //兼容IE
}


//ajax切片上传文件
function ajax_upload_file(index, total, file_ext, oldname, file_name){

	if(file_temp_data == "") return; //无文件数据返回

	var start = (index - 1) * file_chunk_size; //每次传1M

	var curr_data = window.btoa(file_temp_data.substr(start, file_chunk_size)); //截取后转为Base64

	if(index === 1){ //第一片
		var json_d = {gid: guest.gid, sess: guest.sess, key: SYSKEY, code: SYSCODE, fileExt: file_ext, fileData: curr_data, fileIndex: index};
	}else{
		ajax_isOk = 1; //ajax状态需要设置为OK
		var json_d = {gid: guest.gid, sess: guest.sess, key: SYSKEY, code: SYSCODE, fileData: curr_data, fileName: file_name, fileIndex: index};
	}

	ajax(SYSDIR + 'welive-ajax.php?ajax=1&act=upload_file', json_d, function(data){

		if(data.s == 1){ //上传成功后
			//返回的文件名传送给客服
			var save_name = data.i;

			//全部切片上传完成
			if(index >= total){
				file_temp_data = ""; //清空临时数据释放内存
				welive_send({type: "g_handle", operate: "uploadfile", oldname: oldname, filename: save_name});
			}else{
				index += 1;
				ajax_upload_file(index, total, file_ext, oldname, save_name);
			}

		}else{
			file_temp_data = ""; //清空临时数据释放内存
			clearInterval(ttt_2); //上传进度停止
			show_alert(data.i, 6000); //上传失败

			historyport.find(".uploading_info:last").html("... " + langs.upload_failed).css({"color":"red", "font-weight":"bold"});

			welive.status = 1; //允许发送信息
			msger.focus();
		}
	});
}


//向客服发送回拨电话
function send_callback_phone(){
	close_dialogs();

	if(!welive.status || !welive.linked) return false; 

	var reg = /^[\s_#\-\+\(\)\*\d]{5,20}$/;
	var phone = $.trim($("#phone_num").val());
	
	if(!phone){
		show_alert(langs.phone_err_1, 2000);
		return false; 
	}

	if(!(reg.test(phone))){ 
		show_alert(langs.phone_err_2, 2000); 
		return false; 
	}

	welive.msg = langs.require_callback + ': ' + phone; //先记录

	//发送立即回电特殊请求
	welive.status = 0; //不允许发送其它信息
	welive_send({type: "g_handle", operate: "callback", msg: welive.msg});

	is_callback = 0;
	msger.show();
	sender.html(langs.send);
	$("#phone_num").hide();
	$("#toolbar_phone").removeClass("phone_hover");
}


//发送服务评价
function send_evaluate(){
	if(rating_star == 0){
		close_dialogs();
		show_alert(langs.select_star, 2000);
		return false;
	}

	if(welive.status){
		close_dialogs();

		var msg = $.trim($("#rating_advise").val());

		if(msg.length > 600){
			show_alert(langs.too_long, 2000);
			return false;
		}

		welive_send({type: "g_handle", operate: "rating", star: rating_star, msg: msg});
	}
}

//滚动到底部
function scroll_bottom(){
	historyport.scrollTop(20000); //滚动到底部
}

//关闭所有小对话窗口
function close_dialogs(){
	smilies_div.hide();
	alert_info.hide();
	star_rating.hide();

	$("#toolbar_emotion").removeClass("emotion_hover");
	$("#toolbar_evaluate").removeClass("evaluate_hover");
}

//解决无法自动播放声音的问题
function first_play(){
	if(welive.sound) sounder.play();
	document.body.removeEventListener("touchend", first_play);
}

//发送由机器人转接人工客服请求
function trans_to_support(){
	if(welive.status){
		welive.status = 0; //防止重复发送
		welive_send({type: "g_handle", operate: "trans_support"});
	}
}


//插入觉见问题
function insertQuestion(me) {
	var code = $(me).children("b").html();

	msger.val(code);
	search_result_hide();
}

//关闭觉见问题搜索结果
function search_result_hide(){
	if(q_search_result){
		q_search_result.hide();
		q_search_result = null;
	}
}

//搜索觉见问题, 输入框停留1秒开始搜索
function search_questions(me){
	clearTimeout(ttt_8);
	search_result_hide();

	if(all_questions.length < 1) return;

	var keyword = $.trim($(me).val());

	if(keyword.length < 2 || keyword.length > 16) return; //太长或太短均不搜索

	ttt_8 = setTimeout(function(){
		var result = "", tmp = "", keywords = keyword.split(/\s+/);

		all_questions.each(function(){
			var ok = 1;
			tmp = $(this).html();

			$.each(keywords, function(i, key){
				if(tmp.indexOf(key) < 0){
					ok = 0;
					return false;
				}
			});

			if(ok) result += '<li onclick="insertQuestion(this);"><i>●</i><b>' + tmp + '</b></li>';
		});

		if(result){
			q_search_result = q_search;
			q_search_result.html('<div class="q_search_title" onclick="search_result_hide();">' + langs.search_result + '<b>X</b></div>' + result).show();
		}

	}, 1000); //延迟1秒搜索
}


//定义全局变量
var WebSocket = window.WebSocket || window.MozWebSocket; //websocket

var ttt_1 = 0, ttt_2 = 0, ttt_3 = 0, ttt_4 = 0, rating_star = 0, is_callback = 0, sounder, sound_btn, sending_mask, sending_mask_h;
var welive_op, historyport, sender, msger, smilies_div, shakeobj, alert_info, star_rating, trans_to_btn;

var welive_name; //客服姓名
var welive_duty; //客服职位

var drag_to_bottom = 222; //historyport消息窗口离底部距离
var welive_relink_times = 8; //重连次数超过后转到留言页面

var file_chunk_size = 1048576; //切片大小 默认为1M
var file_temp_data = ""; //切片上传文件时使用

var ttt_8 = 0, q_search, q_search_result = null, all_questions = ""; //常见问题相关

//linked        1已连接,   0未连接
//status        1登录成功允许发信息,   0不允许发信息
//autolink     1允许重新连接,   0不允许重新连接
var welive = {ws:{}, linked: 0, status: 0, autolink: 0, ttt_1: 0, ttt_2: 0, ttt_3: 0, playSound: 0, ic: '', sound: 1, msg: '', temp: '', is_robot: 0};

var star_info = ['<img src="' + SYSDIR + 'mobile/img/star_icon1.png">' + langs.star_1, '<img src="' + SYSDIR + 'mobile/img/star_icon2.png">' + langs.star_2, '<img src="' + SYSDIR + 'mobile/img/star_icon3.png">' + langs.star_3, '<img src="' + SYSDIR + 'mobile/img/star_icon4.png">' + langs.star_4, '<img src="' + SYSDIR + 'mobile/img/star_icon5.png">' + langs.star_5];


$(function(){

	welive_op = $("#welive_operator");

	historyport = $(".viewport");
	smilies_div = $(".smilies_div");
	sender = $("#send_btn");
	msger = $(".msger");
	sounder = $("#welive_mp3")[0];
	sound_btn = $("#toolbar_sound");
	alert_info = $("#alert_info");
	star_rating = $("#starRating");
	trans_to_btn = $("#trans_to_btn");
	q_search = $(".q_search");

	//获取客人的gid
	var gid = parseInt(getCookie(COOKIE_USER));
	if(gid) guest.gid = gid;

	if(WS_HOST == "")	WS_HOST = document.domain; //先记录下来供websocket连接使用
	try{document.domain = welive_domain;}catch(e){} //设置主域名, 解决主域名相同时的跨域问题

	//socket连接
	welive_link(); 


	//移动端输入法按发送触发
	msger.keyup(function(e){
		if(e.keyCode ==13){
			welive_send_msg();
			$(this).blur();
		}
	}).bind("input propertychange", function(e){
		search_questions(this); //搜索常见问题
	});   

	//发送信息
	sender.click(function(e) {
		welive_send_msg();
		e.preventDefault();
	});

	//评价按钮动作
	$("#toolbar_evaluate").click(function(){
		smilies_div.hide();
		alert_info.hide();

		rating_star = 0;
		$("#starRating .star span").find('.high').css('z-index',0);
		$(".starInfo").html(langs.select_star);
		$("#starRating").toggle();

		$("#toolbar_emotion").removeClass("emotion_hover");
		if($(this).hasClass("evaluate_hover")){
			$(this).removeClass("evaluate_hover");
		}else{
			$(this).addClass("evaluate_hover");
		}
	});

	//星星打分
	$("#starRating .star span").click(function () {
		rating_star = parseInt($(this).attr("star_val"));

		$(this).prevAll().find('.high').css('z-index',1);
		$(this).find('.high').css('z-index',1);
		$(this).nextAll().find('.high').css('z-index',0);

		$('.starInfo').html(star_info[rating_star -1]);
	});

	//表情符号
	$("#toolbar_emotion").click(function(){
		alert_info.hide();
		star_rating.hide();
		smilies_div.toggle();

		$("#toolbar_evaluate").removeClass("evaluate_hover");
		if($(this).hasClass("emotion_hover")){
			$(this).removeClass("emotion_hover");
		}else{
			$(this).addClass("emotion_hover");
		}
	});

	//获取当前的声音状态
	var wl_soundoff = parseInt(getCookie('wl_soundoff'));
	if(wl_soundoff == 1){
		welive.sound = 0;
		sound_btn.addClass('sound_off');
	}

	//移动端声音处理, 先要播放一下声音,否则无法自动播放
	if(welive.sound) document.body.addEventListener("touchend", first_play); 

	//开关声音
	sound_btn.click(function(){
		if(welive.sound){
			welive.sound = 0;
			sound_btn.addClass('sound_off');

			setCookie('wl_soundoff', 1, 2); //关闭声音cookie保持2天
		
		}else{
			welive.sound = 1;
			sounder.play();
			sound_btn.removeClass('sound_off');
			setCookie('wl_soundoff', 0, 0);
		}
	});

	//监听上传图片控件
	$("#upload_img").change(function(){
		try {
			var file = this.files[0];
			readImageFile(file, this); //读取并传送图片
		} catch(e) {}
	});

	//上传图片按钮
	$("#toolbar_photo").click(function(){
		close_dialogs();

		//验证上传权限
		if(!check_upload_auth()) return;

		if(welive.status && welive.linked){
			//$("#upload_img").trigger("click");
			document.getElementById("upload_img").click(); //兼容IE
		}

		return false;
	});

	//上传文件按钮
	$("#toolbar_file").click(function(){
		close_dialogs();

		//验证上传权限
		if(!check_upload_auth()) return;

		if(welive.status && welive.linked) welive_upload_file();
	});

	//回拨电话按钮
	$("#toolbar_phone").click(function(){
		close_dialogs();

		if($(this).hasClass("phone_hover")){
			$(this).removeClass("phone_hover");
		}else{
			$(this).addClass("phone_hover");
		}

		if(is_callback){
			is_callback = 0;
			sender.html(langs.send);
			msger.show();
			$("#phone_num").hide();
		}else{
			is_callback = 1;
			sender.html(langs.phone_btn);
			msger.hide();
			$("#phone_num").show();
		}

	});


	//初始化消息窗口的高度, 延迟运行是因为iframe动画效果还未完全展开, 等到窗口的高度不正常
	setTimeout(function(){
		historyport.height($(window).height() - drag_to_bottom);

		$(window).resize(function(){//窗口大小改变时, 兼容safari
			historyport.height($(window).height() - drag_to_bottom);
			scroll_bottom();
		});

	}, 800);


	//iphone safari
	window.addEventListener("pagehide", function(){
		clearTimeout(welive.ttt_1);
		clearInterval(welive.ttt_2);
		clearTimeout(welive.ttt_3);
		clearInterval(ttt_1);
		clearInterval(ttt_2);
		clearInterval(ttt_3);
	}, false);

	window.onbeforeunload=function(event){clearTimeout(welive.ttt_1);clearInterval(welive.ttt_2);clearTimeout(welive.ttt_3);clearInterval(ttt_1);clearInterval(ttt_2);clearInterval(ttt_3);};

	//常见问题
	all_questions = $("#questions_div li");

});