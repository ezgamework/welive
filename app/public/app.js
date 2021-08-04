
/* WeLive app.js  @Copyright weensoft.cn */

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
			file_temp_data = "";
			show_alert("Ajax错误", 4000);
		}
	});
}

//显示大图片
function show_img(me, width, height){
	var layer = $("#welive_big_img");

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
	layer.on("click",  function(e) {
		layer.hide();
		$(this).off("click");
	});

	layer.children('.big_img_wrap').css({top: new_top, left: new_left, width: new_w, height: new_h}).html('<img src="' + me.src + '" style="width: ' + new_w + 'px;height: ' + new_h + 'px;">');

	layer.fadeIn(200);
}


//弹出对话框 callback: 确认按钮回调函数
function welive_popup(title, callback, info, special) {
	var layer = $("#welive_popup");
	var layerwrap = layer.children('.popup_wrap');

	if(title) layerwrap.children('.popup_title').html(title);
	if(info) layerwrap.children('.popup_content').html(info);

	//确认按钮
	layerwrap.children('.button_ok').on("click",  function(e){
		layer.hide();
		$(this).off("click");

		if(typeof(callback) == "function") callback();
	});

	//取消按钮
	layerwrap.children('.button_cancel').on("click",  function(e){
		layer.hide();

		layerwrap.children('.popup_title').html("");
		layerwrap.children('.popup_content').html("");

		layerwrap.children('.button_ok').off("click");

		$(this).off("click");
	});

	//点击或者触控弹出层外的半透明遮罩层, 关闭弹出层
	layer.on("click",  function(e) {
		if(e.target == this) layerwrap.children('.button_cancel').trigger("click");
	});

	layer.fadeIn(200);

	//屏幕居中
	layerwrap.css({'margin-top': -layerwrap.outerHeight()/2});

	//转接客人弹出层专用
	if(special && special == "transfer"){
		layer.find(".transfer_list").children("i").on("click",  function(){
			layer.find(".select").removeClass("select");
			$(this).addClass("select");			
		});
	}
}


//显示警告信息
function show_alert(info, time) {
	if(!info) return;

	alert_info.html(info).show();

	setTimeout(function() {
		alert_info.hide();
	}, time ? time : 2000);
}

//获得计算机当前时间
function getLocalTime() {
	var date = new Date();

	function addZeros(value, len) {
		var i;
		value = "" + value;
		if (value.length < len) {
			for (i=0; i<(len-value.length); i++)
				value = "0" + value;
		}
		return value;
	}
	return addZeros(date.getHours(), 2) + ':' + addZeros(date.getMinutes(), 2) + ':' + addZeros(date.getSeconds(), 2);
}

//格式化输出信息
function format_output(data) {
	//生成URL链接
	data = data.replace(/((((https?|ftp):\/\/)|www\.)([\w\-]+\.)+[\w\.\/=\?%\-&~\':+!#;]*)/ig, function($1){return getURL($1);});
	//将表情代码换成图标路径
	data = data.replace(/\[:(\d*):\]/g, '<img src="' + SYSDIR + 'public/smilies/$1.png">').replace(/\\/g, '');
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
function insertS(code) {
	code = '[:' + code + ':]';
	var obj = msg_input[0];

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

	bottomSwiper.slideTo(0); //插入表情图标时切换到输入框swiper
}

//清理客服对话记录(保留50条)
function support_record_clear(){
	var rec = support_viewport.children("div");
	var len = rec.length;
	if(len >= 10){
		rec.slice(0, len - 5).remove();
		support_to_bottom(); //滚动到底部
	}
}

//更新客服数量(在原数量上加或减n)
function support_update_num(n){
	support_onlines += n;

	if(support_onlines < 0) support_onlines = 0;
	s_onlines.html(support_onlines);
}


//welive重置
function welive_reset(){

	welive.status = 0;
	clearTimeout(welive.ttt); //清除重连

	clearInterval(ttt_1); //停止发送心跳数据

	supportListSwiper.removeAllSlides();//清空客服在线列表
	support_unreads = 0; //客服未读数
	support_onlines = 0; //在线客服数量

	guestSwiper.removeAllSlides(); //清除所有访客对话窗口
	guestListSwiper.removeAllSlides(); //清空访客在线列表

	welive_guests = {};
	offline_guests = [];
	page_tmp = [];
	guest_activeid = 0;

	guest_unreads = 0; //访客信息未读数
	guest_onlines = 0; //访客在线数量
	guest_totals = 0; //访客总数量

	send_btn.addClass("unlink");

	support_toolbar.children("#toolbar_serving").removeClass("serving_off").addClass("serving_on");//挂起按钮恢复初始状态

	support_show();//切换到群聊区
}


//解析数据并输出
function welive_parseOut(data){
	var gid = 0, d = "", type = 0, data = $.parseJSON(data);

	if(data.g) data.g = parseInt(data.g); //统一将访客id转为数字

	msg_input.removeClass("loading"); //有数据返回时, 清除输入框的loading状态

	switch(data.x){
		case 4:  //客人实时输入状态
			switch(data.a){
				case 1:
					welive_runtime(data.g, data.i);
					break;

				case 2: //删除当前的输入状态
					welive_runtime(data.g); //不传送msg时

					break;
			}

			break;

		case 1: //客服对话
			var time = getLocalTime();

			if(data.aid == admin.id){ //自己的发言
				d = '<div class="msg r">';
			}else{
				welive.sound = 1; //播放声音

				d = '<div class="msg l">';
			}

			d += '<u><img src="' + SYSDIR + 'avatar/' + data.av + '"></u><div><i>' + data.n + ' - ' + data.p + '</i>' + format_output(data.i) + '</div><b>' + time + '</b></div>';

			support_output(d);
			break;

		case 2: //客服特别操作及反馈信息
			switch(data.a){
				case 1: //上线

					welive.sound = 1; //播放声音
					var status = '<a></a>'; //移动端状态

					d = '<div class="msg"><i class="i">' + data.n + ' 上线了</i></div>';

					support_update_num(1);

					support_output(d);

					supportListSwiper.appendSlide('<i id="' + data.ix + '" class="swiper-slide"><img src="' + SYSDIR + 'avatar/' + data.av + '"><b' + ((data.t==1 || data.t==2)? ' class=s' : '') + '>' + data.n + '</b>' + status + '</i>');

					break;

				case 2: //离线

					//解决客服重复连接时, 给新连接的窗口发送自己离线信息的问题(也就是说自己无法看到自己离线)
					if(data.aid == admin.id) return;

					d = '<div class="msg"><i class="i">' + data.i + ' 已离线</i></div>';

					support_update_num(-1);
					support_output(d);

					support_list.find("#" + data.ix).remove();
					supportListSwiper.updateSlides();

					break;

				case 3: //挂起
					welive.sound = 1; //播放声音
					var a = support_list.find("#" + data.ix);

					d = '<div class="msg"><i class="i">' + a.children("b").html() + ' 已挂起</i></div>';
					support_output(d);

					a.append('<u></u>');

					//自己挂起
					if(data.ix == welive.index){
						support_toolbar.children("#toolbar_serving").removeClass("serving_on").addClass("serving_off");
					}

					break;

				case 4: //解除挂起
					var a = support_list.find("#" + data.ix);

					d = '<div class="msg"><i class="i">' + a.children("b").html() + ' 解除挂起</i></div>';
					support_output(d);

					a.children("u").remove();

					if(data.ix == welive.index){
						support_toolbar.children("#toolbar_serving").removeClass("serving_off").addClass("serving_on");
					}

					break;

				case 5: //获取客人信息

					// 移动端精简此功能

					break;

				case 6: //保存客人信息后返回的结果

					// 移动端精简此功能

					break;

				case 7: //重复连接返回的指令

					d = '<div class="msg"><i class="i"><font color=red>重复登录, 此页面已废弃!!</font></i></div>';

					support_output(d);

					//重置welive, 不允许重连
					welive.autolink = 0;
					welive_reset();

					break;

				case 8: //客服连接验证成功
					
					welive.index = data.ix; //客服的socket连接索引值

					support_top.removeClass('loading');
					send_btn.removeClass("unlink");

					welive.status = 1;
					welive.autolink = 1;
					support_onlines = 0; //在线客服数清0

					//welive.sound = 1; //播放声音 无效, 播放不出来
					d = '<div class="msg"><i class="i">服务器连接成功</i></div>';

					support_output(d);

					//更新自己的客服列表
					var num = 0, status = '';

					supportListSwiper.removeAllSlides(); //清空客服在线列表

					welive.is_robot = parseInt(data.irb); //记录无人值守是否开启

					if(welive.is_robot){
						num += 1;
						supportListSwiper.appendSlide('<i id="robot818" class="swiper-slide"><img src="' + SYSDIR + 'avatar/robot/0.png"><b class=s>' + data.rn + '</b></i>');
					}

					$.each(data.al, function(n, a){
						num += 1;

						if(a.b == 1){
							status = '<u></u>'; //挂起状态(即busy)
						}

						if(a.fr == 1){
							status += '<a></a>'; //移动端状态
						}

						if(a.id == admin.id) admin.avatar = a.av; //记录自己的头像

						supportListSwiper.appendSlide('<i id="' + a.ix + '" class="swiper-slide"><img src="' + SYSDIR + 'avatar/' + a.av + '"><b' + ((a.t==1 || a.t==2)? ' class=s' : '') + '>' + a.n + '</b>' + status + '</i>');

					});

					support_update_num(num); //更新客服人数

					//重建所有在线客人
					$.each(data.gl, function(i, guest){
						guest_create(parseInt(guest.g), guest.n, parseInt(guest.l), parseInt(guest.au), parseInt(guest.mb), guest.re, guest.iz, guest.fr, 1); //创建已连接的客人, 在创建中输出信息, 参数1客服重新连接
					});

					//判断当前无人值守状态及按钮状态, 无人值守按钮非管理员也可以看到, 但无动作
					if(welive.is_robot){
						toolbar_man.removeClass("man_on").addClass("man_off");
					}else{
						toolbar_man.removeClass("man_off").addClass("man_on");
					}

					//启动心跳, 即每隔26秒自动发送一个特殊信息, 解决IE下30秒自动断线的问题
					ttt_1 = setInterval(function() {						
						welive_send({type:"ping"}); //只要连接状态, 均要发送心跳数据, 设置一个怪异的数字避免与自动离线的时间间隔重合, 避免在同一时间点上send数据上可能产生 -----幽灵bug
					}, 26125);

					break;

				case 10: //开启无人值守时
					welive.is_robot = parseInt(data.irb);

					//开启失败
					if(welive.is_robot == 2){
						welive.is_robot = 0;
						show_alert("开启无人值守失败<br>请先在后台 -> 智能 -> 机器人客服管理中添加自动回复内容", 6000);
						return false;
					}

					welive.sound = 1; //播放声音
					var a = support_list.find("#" + data.ix);

					d = '<div class="msg"><i class="i">' + a.children("b").html() + ' 已开启无人值守状态</i></div>';
					support_output(d);

					supportListSwiper.prependSlide('<i id="robot818" class="swiper-slide"><img src="' + SYSDIR + 'avatar/robot/0.png"><b class=s>' + data.n + '</b></i>');

					//无人值守按钮状态
					toolbar_man.removeClass("man_on").addClass("man_off");

					support_update_num(1);

					break;

				case 11: //关闭无人值守时

					welive.is_robot = parseInt(data.irb);

					welive.sound = 1; //播放声音
					var a = support_list.find("#" + data.ix);
					d = '<div class="msg"><i class="i">' + a.children("b").html() + ' 已关闭无人值守状态</i></div>';
					support_output(d);

					support_list.find("#robot818").remove();
					supportListSwiper.updateSlides();

					//无人值守按钮状态
					toolbar_man.removeClass("man_off").addClass("man_on");

					support_update_num(-1);

					break;

			}

			break;

		case 5:  //客人与客服对话
			gid = data.g;
			d = data.i;
			if(data.a == 1){
				type = 1; //自己发出的对话
			}else{
				welive.sound = 1; //声音等
				type = 2; //客人发来的
			}

			guest_output(gid, d, type);

			break;

		case 6: //访客登录, 客服特别操作等
			switch(data.a){
				case 8: //客人登录成功
					//data.g = gid
					//data.n = name 
					//data.l  = 1: 中文 0: 英文
					//data.re = 通话记录
					//data.au = 1: 上传授权 0: 无上传授权 
					//data.mb = 1: mobile 0: web
					guest_create(data.g, data.n, data.l, parseInt(data.au), data.mb, data.re, data.iz, data.fr, 0); //创建新客人, 在创建中输出信息

					break;

				case 3: //客人离线

					d = '此客人已离线';

					gid = data.g;

					if( typeof(welive_guests[gid]) == 'undefined' ) return; //客人不存在时返回, 否则js出错(被踢出时发生)

					guest_output(gid, d, 3);

					set_guest_offline(gid, '已离线 - '); //处理离线状态

					guest_statistics_update(-1, 'online'); //仅更新在线访客数量

					break;

				case 11: //转接客人返回信息
					if(data.i == 1){ //转接成功

						d = '此客人转接成功';

						gid = data.g;
						guest_output(gid, d, 3);

						set_guest_offline(gid, '已转接 - '); //转接后设置为离线状态

						guest_statistics_update(-1, 'online'); //仅更新在线访客数量

					}else{
						d = '此客人转接失败';
						
						gid = data.g;
						guest_output(gid, d, 3);
					}

					break;

				case 13: //客人请求回拨电话

					d = '<div class="a">' + data.i + '</div>';

					gid = data.g;
					welive.sound = 1;

					guest_output(gid, d, 2); //客人发来的

					break;
			}

			break;


		case 7: //客人发送图片

			switch(data.a){

				case 2: //客人上传图片通知客服后返回的信息--表示上传成功

					gid = data.g;

					welive.sound = 1; //声音

					if(data.w <1) data.w = 1;
					var img_h = parseInt(data.h * 200 / data.w); //CSS样式中已确定宽度为200

					d = '<img src="' + SYSDIR + 'upload/img/' + data.i + '" class="receive_img" style="height:' + img_h + 'px;" onclick="show_img(this, ' + data.w + ', ' + data.h + ');">';

					guest_output(gid, d, 2); //客人发来的

					break;

				case 4: //客服上传图片给客人成功后返回

					//因为可能同时有多个上传, 此处无法处理上传进度条等信息
					break;

				case 5: //客人上传文件

					gid = data.g;
					welive.sound = 1; //声音

					d = '<a href="' + SYSDIR + 'upload/file/' + data.i + '" target="_blank" download="' + data.o + '"><img src="' + SYSDIR + 'public/img/save.png">&nbsp;下载: ' +  data.o + '</a>';

					guest_output(gid, d, 2); //客人发来的

					break;

				case 6: //客服上传文件给客人成功后返回

					//因为可能同时有多个上传, 此处无法处理上传进度条等信息
					break;
			}

			break;
	}

}


// 创建客人
// lang = 1: 中文 0: 英文 || au = 1: 上传授权 0: 无上传授权  ||  mobile = 1: 移动端 0: web端  ||  records 最近对话记录  || fromurl 来自页面 || old=1表示客服重连
function guest_create(gid, name, lang, upload, mobile, records, ipzone, fromurl, old){

	var in_offline = $.inArray(gid, offline_guests);

	//来自URL
	if(fromurl){
		fromurl = ', 来自:<br>' + fromurl;
	}else{
		fromurl = '';
	}

	if(name == ''){
		name = ((lang == 1)? '访客'  : 'Guest') + gid;
	}

	name = name + " - " + ipzone;

	if(in_offline > -1){ //表示客人重新上线

		offline_guests.splice(in_offline, 1); //将其从离线数组中删除

		guest_statistics_update(1, 'online'); //仅更新在线访客数量

		guest_top_wrapper.children("#guest_" + gid).children(".header").removeClass("offline").children(".name").html(name); //对话窗口顶部头像取消离线样式, 及更新用户名

		//头像列表中取消离线样式
		guest_list.find("#gav_" + gid).removeClass("offline");

		//更新访客数据及按钮状态
		if(typeof(welive_guests[gid]) != 'undefined'){
			welive_guests[gid].au = upload; //更新上传授权

			if(welive_guests[gid].ban){
				welive_guests[gid].ban = 0; //重新连接后设置为非禁言状态

				if(guest_activeid == gid){
					guest_toolbar.children("#toolbar_banned").removeClass("banned_off").addClass("banned_on");
				}
			}
		}

		var d = "此客人重新上线" + fromurl;

		welive.sound = 0; //重新上线无声音
		guest_output(gid, d, 3);

	}else if( typeof(welive_guests[gid]) == 'undefined' ){ //新客人

		guest_statistics_update(1); //更新在线客人数及总数

		//添加访客对象: au保存上传授权, ban保存是否禁言, num保存新消息数量
		welive_guests[gid] = {au: upload, ban: 0, num: 0}; 

		var viewport_h = window_height - drag_to_bottom - 76; //新添加的viewport需要设定高度

		if(records){ //如果有对话记录先输出 
			var recs = '';

			$.each(records, function(i, rec){

				//上传图片记录
				if(rec.ft == 1){
					var img_arr = rec.m.split("|");
					var img_w = parseInt(img_arr[1]);
					var img_h = parseInt(img_arr[2]);

					if(img_w < 1) img_w = 1;
					var img_h_new = parseInt(img_h * 200 / img_w); //CSS样式中已确定宽度为200

					var rec_i = '<img src="' + SYSDIR + 'upload/img/' +img_arr[0] + '" class="receive_img" style="height:' + img_h_new + 'px;" onclick="show_img(this, ' + img_w + ', ' + img_h + ');">';

				//上传的文件记录
				}else if(rec.ft == 2){
					var file_arr = rec.m.split("|");
					var rec_i = '<a href="' + SYSDIR + 'upload/file/' + file_arr[0] + '" target="_blank" download="' + file_arr[1] + '"><img src="' + SYSDIR + 'public/img/save.png">&nbsp;下载: ' +  file_arr[1] + '</a>';
				}else{
					var rec_i = format_output(rec.m);
				}

				if(rec.t == 1){ //客服的
					recs += '<div class="msg r"><u><img src="' + SYSDIR + 'avatar/' + admin.avatar + '"></u><div>' + rec_i + '</div><b>' + rec.d + '</b></div>';

				}else{ //客人的

					recs += '<div class="msg l"><u><img src="public/img/guest.jpg"></u><div>' + rec_i + '</div><b>' + rec.d + '</b></div>';				
				}

			});
		}

		if(recs) recs += '<div class="msg"><i class="i">... 以上为最近对话记录</i></div>';

		//创建访客前先把已存在的分页全部记录下来, 因为swiper的分页会重写
		page_tmp = guest_tips.children("i");

		//添加访客窗口slide
		guestSwiper.appendSlide('<div id="guest_' + gid + '" class="guest swiper-slide" data-gid="' + gid + '"><div class="header"><img src="public/img/guest.png" class="avatar"><div class="name">' + name + '</div><b></b></div><div class="viewport" style="height: ' + viewport_h + 'px;">' + recs + '</div></div>');
		
		//添加访客头像slide
		guestListSwiper.appendSlide('<i id="gav_' + gid + '" class="swiper-slide"><u></u><b></b>' + (mobile ? '<a></a>' : '')+ '</i>');

		//第一个访客时
		if(guest_totals <= 1){
			guest_activeid = gid; //记录当前活动访客的gid

			guest_list.find("#gav_" + gid).addClass("curr"); //只有一个访客时头像变成当前活动
			guest_list.removeClass("no_guest");
			bottomSwiper.slideTo(0);

			//更新上传授权按钮状态
			if(auth_upload && upload){
				guest_toolbar.children("#toolbar_auth").removeClass("auth_on").addClass("auth_off"); //有授权显示解除授权按钮
			}

			//第一个访客时访客分页指示标志无法获取id, 这里更新
			guest_tips.children("i:first").attr("id", "gp_" + gid);
		}

		//向访客输出一条信息
		if(old === 1){ //指客服重新上线
			var d = admin.fullname + ' 重新上线, 已通知客人';
		}else{
			var d = "客人上线, 问候语已发送" + fromurl;
		}
		
		welive.sound = 1;
		guest_output(gid, d, 3);
	}
}


//关闭访客
function guest_close(){

	if(!guest_activeid) return;

	//删除访客
	function do_guest_delete(){
		guest_deleting = 1; //标记正在删除访客

		var gid = parseInt(guest_activeid);

		var in_offline = $.inArray(gid, offline_guests);

		if(in_offline > -1){
			offline_guests.splice(in_offline, 1); //将其从离线数组中删除

			guest_statistics_update(-1, 'total'); //已离线仅更新总数
		}else{
			guest_statistics_update(-1); //更新在线数及总数
		}

		//删除访客对象
		if(typeof(welive_guests[gid]) != 'undefined'){

			delete welive_guests[gid];
		}

		//如果统计更新后无访客, 也就是说被删除的是最后一个客人
		if(guest_totals <= 0){
			guest_list.addClass("no_guest");
			bottomSwiper.slideTo(1);

			page_tmp = [];
			guest_activeid = 0;

			guest_toolbar.children("#toolbar_banned").removeClass("banned_off").addClass("banned_on");
			if(auth_upload) guest_toolbar.children("#toolbar_banned").removeClass("auth_off").addClass("auth_on");

			//删除客人头像
			guest_list.find("#gav_" + gid).remove();
			guestListSwiper.updateSlides();

			//删除对话窗口
			guest_top_wrapper.children("#guest_" + gid).remove();
			guestSwiper.updateSlides(); //更新访客窗口

			guest_deleting = 0; //标记删除结束
		}else{
			//判断被删除访客是否排列在最后一个
			var this_index = guestSwiper.activeIndex;

			if(this_index >= guest_totals){ //当前客人排列在最后

				guestSwiper.slideTo(this_index - 1); //访客窗口切换到前一个窗口

			}else{

				guestSwiper.slideTo(this_index + 1); //访客窗口切换到下一个窗口

			}

			//删除客人头像
			guest_list.find("#gav_" + gid).remove();
			guestListSwiper.updateSlides();

			//删除对应的分页并全部记录下来, 因为swiper的分页会重写
			page_tmp = guest_tips.children("i");
			page_tmp.splice(this_index, 1);

			//删除访客窗口, 此时会自动更新分页. 延迟执行是为了等待切换窗口动画效果结束, 否则会产生错误
			setTimeout(function(){
				guestSwiper.removeSlide(this_index);
				guest_deleting = 0; //标记删除结束
			}, 300);
		}
	}


	//如果此访客在线, 警示
	if($.inArray(guest_activeid, offline_guests) < 0){

		welive_popup("此访客仍然在线，确定关闭他吗？", function(){
			
			//客人在线, 发送踢出请求
			welive_send({type: "s_handle", operate: "kickout", guestid: guest_activeid});

			do_guest_delete();
		});

	}else{
		do_guest_delete();
	}
}


//处理客人离线
function set_guest_offline(gid, title){

	gid = parseInt(gid);

	if(!gid) return false; //没有gid返回

	offline_guests.push(gid); //添加到离线客人数组中

	var obj = guest_top_wrapper.children("#guest_" + gid).children(".header").addClass("offline").children(".name"); //对话窗口顶部头像添加离线样式

	obj.html(title + obj.html());

	guest_list.find("#gav_" + gid).addClass("offline"); //头像列表中添加离线样式
}


//授权上传: status = 1 授权上传  0 解除授权
function guest_authupload(status){

	if(!guest_activeid || !auth_upload)  return;

	if(status){
		if($.inArray(guest_activeid, offline_guests) > -1)  return; //离线状态时无法授权

		welive_send({type: "s_handle", operate: "auth_upload", guestid: guest_activeid, auth: 1}); //授权请求
		guest_output(guest_activeid, '已授权上传图片或文件', 3);

		welive_guests[guest_activeid].au = 1; //记录为有授权

		guest_toolbar.children("#toolbar_auth").removeClass("auth_on").addClass("auth_off"); //显示解除授权按钮
	
	}else{ //离线状态时可以解除上传授权

		welive_send({type: "s_handle", operate: "auth_upload", guestid: guest_activeid, auth: 0}); //解除请求
		guest_output(guest_activeid, '上传权限已解除', 3);

		welive_guests[guest_activeid].au = 0;

		guest_toolbar.children("#toolbar_auth").removeClass("auth_off").addClass("auth_on");
	}
}


//禁止发言或解除禁言: status = 1 禁止发言  0 解除禁言
function guest_banned(status){

	if(!guest_activeid || $.inArray(guest_activeid, offline_guests) > -1)  return;

	if(status){

		welive_send({type: "s_handle", operate: "banned", guestid: guest_activeid, ban: 1}); //禁止发言
		guest_output(guest_activeid, '此客人已禁言, 但你仍然可以对其发言', 3);

		welive_guests[guest_activeid].ban = 1; //记录客人为禁言状态 

		guest_toolbar.children("#toolbar_banned").removeClass("banned_on").addClass("banned_off"); //临时变更按钮状态
	
	}else{

		welive_send({type: "s_handle", operate: "banned", guestid: guest_activeid, ban: 0}); //解除禁言

		guest_output(guest_activeid, '此客人已解除禁言', 3);

		welive_guests[guest_activeid].ban = 0; //记录客人为解除禁言状态 

		guest_toolbar.children("#toolbar_banned").removeClass("banned_off").addClass("banned_on");
	}
}


//转接客人
function guest_transfer(){
	if(!guest_activeid) return;

	if($.inArray(guest_activeid, offline_guests) > -1){
		show_alert("此客人已离线, 无法转接!");
		return;
	}

	var num = 0;
	var temp_div = $('<div class="support_list transfer_list"></div>');

	temp_div.html(supportListSwiper.$wrapperEl.html()).children('i').each(function(){
		var aix = $(this).attr('id');

		if(aix == welive.index || aix == "robot818"){
			$(this).remove(); //去掉自己和机器人
			return;
		}

		num += 1;
	});

	if(!num){
		show_alert("暂无其他客服可转接!");
		return;
	}

	welive_popup("请选择客服：", function(){

		//接收客服连接索引
		var to_aix = false;
		var welive_popup = $(document.body).children("#welive_popup");
		var to_obj = welive_popup.find(".select");

		if(to_obj.length){
			to_aix = to_obj.attr('id');
		}

		if(to_aix === false){		
			show_alert("未选择接收客服!");
		}else{
			welive_send({type: "s_handle", operate: "trans_guest", guestid: guest_activeid, aix: to_aix}); //发送转接请求
		}

		welive_popup.find(".popup_content").html(""); //清空转接客服列表, 以免产生混乱

	}, temp_div[0].outerHTML, "transfer");

}


//客人窗口输出信息
function guest_output(gid, d, type){

	if(!gid || !d) return false; //没有信息及gid返回

	//是否发声
	if(guest_sound && welive.sound){
		guest_sounder.play();
	}

	var viewport = guest_top_wrapper.children("#guest_" + gid).children(".viewport");

	if(type != 1) viewport.children("div.updating").remove(); //删除实时输入状态

	switch(type){
		case 1: //客服
			d = '<div class="msg r"><u><img src="' + SYSDIR + 'avatar/' + admin.avatar + '"></u><div>' + format_output(d) + '</div><b>' + getLocalTime() + '</b></div>';
			break;
		case 2: //客人
			if(guest_activeid == gid)  guest_activeid_Unread = 1; //标记当前窗口用户有未读消息

			d = '<div class="msg l"><u><img src="public/img/guest.jpg"></u><div>' + format_output(d) + '</div><b>' + getLocalTime() + '</b></div>';
			break;

		case 5: //客服发送图片给客人时, 输出信息不能格式化, 否则本地图片无法显示
			d = '<div class="msg r"><u><img src="' + SYSDIR + 'avatar/' + admin.avatar + '"></u><div>' + d + '</div><b>' + getLocalTime() + '</b></div>';
			break;

		default: //提示
			d = '<div class="msg"><i class="i">' + d + '</i></div>';
			break;
	}

	viewport.append(d);

	//仅当访客窗口可见且当前访客是活动访客时滚动到底部, 即当前访客窗口可见时
	if(active_window == 2 && guest_activeid == gid){

		viewport.scrollTop(100000);

	}else if(type != 1 && welive.sound){ //客服自己的发言不参与记数, 不发声的输出不参与计数(静默输出)

		guest_unread_update(gid, 1);//当前访客窗口不可见时增加未读数

	}

	welive.sound = 0; //临时关闭声音
}


//客人输入状态更新, 仅访客窗口可见时处理
function welive_runtime(gid, msg){
	if(!gid || gid != guest_activeid || active_window == 1) return;

	var viewport = guest_top_wrapper.children("#guest_" + gid).children(".viewport");

	//当访客更新了输入状态后, 输入框又清空时, 删除之前的输入状态
	if(!msg){
		viewport.children("div.updating").remove();
		return;
	}

	msg = format_output(msg) + ' <img src="public/img/typing.svg" class="typing">';

	var updating = viewport.children("div.updating");

	if(updating.length){
		updating.children("div").html(msg); //之前存在
	}else{
		viewport.append('<div class="updating msg l"><u><img src="public/img/guest.jpg"></u><div>' + msg + '</div></div>');
	}

	viewport.scrollTop(100000);
}


//更新访客总数和在线数量(在原数量上加或减n)
function guest_statistics_update(n, type){
	if(type == "online"){
		guest_onlines += n; //访客在线数量
		if(guest_onlines < 0) guest_onlines = 0;
		guest_onlines_obj.html(guest_onlines);

	}else if(type == "total"){

		guest_totals += n; //访客总数量
		if(guest_totals < 0) guest_totals = 0;
		guest_totals_obj.html(guest_totals);

	}else{
		guest_onlines += n; //访客在线数量
		guest_totals += n; //访客总数量

		if(guest_onlines < 0) guest_onlines = 0;
		if(guest_totals < 0) guest_totals = 0;

		guest_onlines_obj.html(guest_onlines);
		guest_totals_obj.html(guest_totals);
	}
}


//更新访客未读信息数
function guest_unread_update(gid, n){

	if(!gid || !n)  return false;

	//记录未读总数量
	guest_unreads += n;
	if(guest_unreads <= 0) guest_unreads = 0;

	guest_unreads_obj.html(guest_unreads);

	//处理guest_btn样式
	if(guest_unreads <= 0){

		guest_btn.html("· · ·").removeClass("new");

	}else if(n > 0 && (guest_activeid != gid || active_window == 1)) {

		guest_btn.html(guest_unreads).addClass("new"); //访客窗口不可见或有新消息的访客窗口不可见时, 访客按钮new

	}else{
		guest_btn.html(guest_unreads);
	}

	//独立记录访客的未读数量, 处理头像样式  处理分页器样式
	if(typeof(welive_guests[gid]) != 'undefined'){
		welive_guests[gid].num += n;

		if(welive_guests[gid].num <= 0) {

			welive_guests[gid].num = 0;

		}else if(n > 0 && guest_activeid != gid){

			guest_tips.children("#gp_" + gid).addClass("new").html(welive_guests[gid].num);
			guest_list.find("#gav_" + gid).removeClass("curr").addClass("new").children("u").html(welive_guests[gid].num);
		}
	}
}


//websocket连接
function welive_link(){
	welive.ws = new WebSocket(WS_HEAD + WS_HOST + ':'+ WS_PORT);
	welive.ws.onopen = function(){setTimeout(welive_verify, 100);}; //连接成功后, 小延时再验证用户, 否则IE下刷新时发送数据失败
	welive.ws.onclose = function(){welive_close();};
	welive.ws.onmessage = function(get){welive_parseOut(get.data);};
}


//客服连接验证
function welive_verify(){
	welive.status = 1;
	welive_send({type: "login", from: "backend", session_id: admin.sid, agent: admin.agent, admin_id: admin.id, mobile: 1});
}

//连接断开时执行
function welive_close(){
	welive_reset(); //重置welive

	//允许重新连接
	if(welive.autolink){
		welive.ttt = setTimeout(welive_link, 3000);

		show_alert("连接失败, 3秒后自动重试 ...");
		support_output('<div class="msg"><i class="i">连接失败, 3秒后自动重试 ...</i></div>');
	}
}


//给访客发送信息已读标记
function guest_send_readed(gid){
	gid = parseInt(gid);

	if($.inArray(gid, offline_guests) > -1) return false; //客人离线时不发送

	//当前活动访客
	if(guest_activeid == gid){
		if(guest_activeid_Unread){
			guest_activeid_Unread = 0;
			welive_send({type: "s_readed", gid: gid});
		}
	}else{
		welive_send({type: "s_readed", gid: gid});
	}
}


//直接推送信息
function welive_send(d){
	if(!d) return false;

	if(welive.status) {
		welive.ws.send(JSON.stringify(d)); //将json对象转换成字符串发送
	}

	return true;
}

//发送文本内容
function msg_send(msg){
	window.scrollTo(0,0); //兼容iphone微信浏览器输入框的bug

	if(!msg){
		msg = $.trim(msg_input.val());
	}

	if(msg){

		//访客窗口可见时且有访客时
		if(active_window == 2 && guest_activeid){

			//if($.inArray(guest_activeid, offline_guests) > -1) return false; //客人离线无法发送

			msg_input.addClass("loading");
			msg = {type: "msg", sendto: "front", guestid: guest_activeid, msg: msg};

		}else if(active_window == 1){//客服区可见时

			msg_input.addClass("loading");
			msg = {type: "msg", sendto: "team", msg: msg};
		}

		if(welive_send(msg)) msg_input.val(""); //发送成功时清空输入框
	}

	search_result_hide(); //关闭搜索结果
}



//客服输出信息
function support_output(d){

	if(!d) return; //没有信息返回
	
	//是否发声
	if(support_sound && welive.sound){
		support_sounder.play();
		welive.sound = 0; //播放完即临时关闭
	}

	support_viewport.append(d);

	//客服窗口可见时滚动到底部
	if(active_window == 1){
		support_to_bottom();

	}else{//客服窗口不可见时

		support_unreads += 1;
		support_btn.html(support_unreads).addClass("new");
	}
}


//上传图片监听
function sendImgFileListener(obj, fun) {
	$(obj).change(function() {
		try {
			var filepath = $.trim($(this).val());

			if(filepath == "") return;

			var img_filetype = ["jpg", "gif", "png", "jpeg"]; //图片文件后缀

			var file = this.files[0];

			var filename = file.name;

			var dot_index = filename.lastIndexOf(".");
			var file_ext = filename.substring(dot_index + 1).toLowerCase();

			if(filename == '' || dot_index < 1) {
				show_alert("文件类型错误", 4000);
				return false;
			}

			//非图片文件跳转到普通文件上传
			if($.inArray(file_ext, img_filetype) < 0) {
				welive_upload_file(file, filename, file_ext);
				return false;
			}

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

					fun(result);
				};

				img.onerror = function() {
					$(obj).val("");
					show_alert("图片文件格式无效!");
				};

			};

			reader.readAsDataURL(file);

		} catch(e) {
			//
		}
	});
}


//传送图片, data对象属性: size文件大小(字节), type(文件类型), base64(图片纯净的base64代码)
function welive_send_img(data){

	$("#upload_input").val("");

	//限定文件大小及类型
	if(data.size > 1024 * 4000){
		show_alert("图片文件不能超过4M");
		return;
	}

	if($.inArray(data.type.toLowerCase(), ["image/jpg","image/jpeg","image/png","image/gif"]) < 0) {
		show_alert("图片文件格式无效!");
		return;
	}

	if(!welive.status || !guest_activeid || $.inArray(guest_activeid, offline_guests) > -1) return;  //客人不在线或连接失败时返回


	var gid = guest_activeid;

	var sending_img_w = data.w; //图片的宽度
	var sending_img_h = data.h; //图片的宽度

	if(sending_img_w < 1) sending_img_w = 1;
	var sending_mask_h = parseInt(sending_img_h * 200 / sending_img_w); //CSS样式中已确定宽度

	var img_str = '<div class="sending_div" style="height:' + sending_mask_h + 'px;"><img src="' + data.url + '" class="sending_img" onclick="show_img(this, ' + data.w + ', ' + data.h + ');"><div class="sending_mask" style="line-height:' + sending_mask_h + 'px;height:' + sending_mask_h + 'px;">上传中 ...</div></div>';

	guest_output(guest_activeid, img_str, 5); //5表示客服发送图片给客人

	//显示上传进度
	var sending_mask = guest_top_wrapper.children("#guest_" + gid).children(".viewport").find(".sending_mask").last(); //仅处理最后一次上传的图片
	var ttt_2 = setInterval(function(){
		if(sending_mask_h < 40){
			clearInterval(ttt_2);
			return;
		}else{
			sending_mask_h -= 20;
			sending_mask.css({"height":sending_mask_h + "px", "line-height":sending_mask_h + "px"});
		}
	}, 100);

	ajax('./index.php?c=upload_file&a=ajax&action=uploadimg', {img_type: data.type, img_base64: data.base64}, function(data){
		clearInterval(ttt_2); //上传进度停止

		if(data.s == 1 && welive.status){ //上传成功后
			
			//返回的文件名发送给客人
			welive_send({type: "s_handle", operate: "uploadimg", guestid: gid, filename: data.i, width: sending_img_w, height: sending_img_h}); //w,h指图片的宽,高(像素)

			sending_mask.remove();

		}else{
			show_alert(data.i); //输出服务器返回的错误信息
			sending_mask.html("上传失败").css({"color":"red", "font-weight":"bold"});
		}
	});
}

//上传文件: file文件, filename原文件名, file_ext文件后缀
function welive_upload_file(file, filename, file_ext){
	//禁止同时上传多个文件
	if(file_temp_data){
		show_alert("正在上传中, 请稍候再传!", 4000);
		return false;
	}

	var filetype = ["exe", "php", "bat", "sys", "jpg", "gif", "png", "jpeg"]; //不允许上传的文件后缀

	if($.inArray(file_ext, filetype) > -1) {
		show_alert("文件格式无效!", 4000);
		return false;
	}

	//判断文件大小
	if (file.size > upload_filesize * 1024*1024) {
		show_alert("文件大小不能超过: " + upload_filesize + "M", 4000);
		return false;
	}

	var reader = new FileReader();

	reader.onerror = function(e) {
		show_alert("上传文件失败", 4000);
	}

	var isIE = 0;

	reader.onload = function(e) {
		//ajax上传

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

		var gid = guest_activeid;

		//添加上传进度条
		var file_str =  filename + '<br><div class="uploading_info">文件上传中 ...</div><div class="sending_div file_upload"><div class="uploading_mask"></div></div>';

		guest_output(gid, file_str, 5); //5表示客服发送图片或文件给客人

		//处理上传进度
		var sending_mask_h = 200;
		var sending_mask = guest_top_wrapper.children("#guest_" + gid).children(".viewport").find(".uploading_mask").last(); //仅处理最后一次上传的文件
		var ttt_2 = setInterval(function(){
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
		ajax_upload_file(gid, 1, total_chunks, file_ext, filename, "", ttt_2);

		$("#upload_input").val(""); //清除, 否则无法重复上传相同文件
	};

	if (typeof reader.readAsBinaryString != "undefined") {
		reader.readAsBinaryString(file);
	} else {
		isIE = 1;
		reader.readAsArrayBuffer(file); //兼容IE
	}
}

//ajax切片上传文件
function ajax_upload_file(gid, index, total, file_ext, oldname, file_name, ttt){

	if(file_temp_data == "") return; //无文件数据返回

	var start = (index - 1) * file_chunk_size; //每次传1M

	var curr_data = window.btoa(file_temp_data.substr(start, file_chunk_size)); //截取后转为Base64

	if(index === 1){ //第一片
		var json_d = {fileExt: file_ext, fileData: curr_data, fileIndex: index};
	}else{
		ajax_isOk = 1; //ajax状态需要设置为OK
		var json_d = {fileData: curr_data, fileName: file_name, fileIndex: index};
	}

	ajax('./index.php?c=upload_file&a=ajax&action=uploadfile', json_d, function(data){
		if(data.s == 1){ //上传成功后
			//返回的文件名传送给客服
			var save_name = data.i;

			//全部切片上传完成
			if(index >= total){
				if(ttt) clearInterval(ttt); //上传进度停止
				file_temp_data = ""; //清空临时数据释放内存
				guest_top_wrapper.children("#guest_" + gid).children(".viewport").find(".uploading_info:last").html("... 上传成功").css({"color":"blue", "font-weight":"bold"});
				
				//返回的文件名发送给客人
				welive_send({type: "s_handle", operate: "uploadfile", guestid: gid, oldname: oldname, filename: save_name});
				guest_top_wrapper.children("#guest_" + gid).children(".viewport").find(".uploading_mask").last().remove();
			}else{
				index += 1;
				ajax_upload_file(gid, index, total, file_ext, oldname, save_name, ttt);
			}

		}else{
			if(ttt) clearInterval(ttt); //上传进度停止
			file_temp_data = ""; //清空临时数据释放内存
			guest_output(gid, data.i, 4); //输出服务器返回的错误信息
			guest_top_wrapper.children("#guest_" + gid).children(".viewport").find(".uploading_info:last").html("... 上传失败").css({"color":"red", "font-weight":"bold"});
		}
	});
}


//客服设置无人值守状态: status = 0 开启无人值守  1 关闭无人值守
function set_man_status(status){

	if(status){
		welive_send({type: "s_handle", operate: "set_robot", value: 0}); //发送关闭无人值守请求
	}else{
		welive_send({type: "s_handle", operate: "set_robot", value: 1}); //发送开启无人值守请求
	}
}


//客服设置服务状态: status = 0 挂起  1 服务(解除挂起)
function set_serving_status(status){

	if(status){
		welive_send({type: "s_handle", operate: "hangup", value: 0}); //发送解除挂起请求
	}else{
		welive_send({type: "s_handle", operate: "hangup", value: 1}); //发送挂起请求
	}
}


//退出客服
function exit_welive(){

	function do_exit_welive(){
		welive.autolink = 0; //不允许重连
		welive_reset();
		document.location = './index.php?a=logout';
	}

	//如果还有访客在线, 禁止退出
	if(guest_onlines > 0){

		welive_popup("还有访客在线，确定退出吗？", do_exit_welive);

	}else{
		do_exit_welive();
	}
}



//切换到群聊区
function support_show(){

	//已在客服区时切换客服列表
	if(active_window == 1){
		toggle_list(); //已经显示时, toggle客服列表
		return;
	}

	active_window = 1; //客服群聊窗口处于活动状态

	support_unreads = 0; //客服未读数清0

	guest_top.css("visibility", "hidden");

	guest_list.hide();
	bottomSwiper.slideTo(0);

	support_top.width(1).addClass("moving").show().find(".viewport").css("visibility", "hidden");
	support_top.animate({width: "100%"}, 300, '', function(){$(this).removeClass("moving").find(".viewport").css("visibility", "visible");});

	if(guest_btn.html() == "· · ·") guest_btn.html("");

	guest_btn.removeClass("curr");
	support_btn.removeClass("new").addClass("curr").html("· · ·");

	support_toolbar.show();
	guest_toolbar.hide();

	support_to_bottom(); //消息窗口滚动底部

	if(guest_activeid_Unread) guest_send_readed(guest_activeid); //切换前给当前访客发送已读信号

	setTimeout(function(){
		support_list.show();
		bottomSwiper.updateSlides();
	}, 200); //延迟显示是为了等bottomSwiper切换到第一个slide结束, 减少视觉上的混乱

}

//切换到访客窗口
function guest_show(){

	//已在访客区时切换访客窗口
	if(active_window == 2){

		//有2个或以上访客时切换访客窗口
		if(guest_totals > 1){

			if(guestSwiper.isEnd){
				guest_direction = 0;
			}else if(guestSwiper.isBeginning){
				guest_direction = 1;
			}

			if(guest_direction){
				guestSwiper.slideNext();
			}else{
				guestSwiper.slidePrev();
			}
		}else{
			toggle_list(); //toggle客人列表
		}

		return;
	}

	active_window = 2; //访客窗口处于活动状态

	if(!guest_unreads) guest_btn.html("· · ·");

	support_top.hide();

	support_list.hide();
	bottomSwiper.slideTo(0);

	guest_top.width(1).addClass("moving").css("visibility", "visible");
	guest_top.animate({width: "100%"}, 300, '', function(){$(this).removeClass("moving");});

	guest_btn.addClass("curr");
	support_btn.removeClass("curr").html("");

	guest_toolbar.show();
	support_toolbar.hide();

	setTimeout(function(){
		guest_list.show();
		bottomSwiper.updateSlides();
	}, 200);

	//当有访客时
	if(guest_totals > 0){

		//当前访客窗口滚动到底部
		guest_to_bottom(guest_activeid);

		//更新未读数量(减量)
		if(typeof(welive_guests[guest_activeid]) != 'undefined' && welive_guests[guest_activeid].num > 0){
			if(guest_activeid_Unread) guest_send_readed(guest_activeid); //发送已读信号
			guest_unread_update(guest_activeid, -welive_guests[guest_activeid].num);
		}
	}

}

//访客窗口滚动到底部
function guest_to_bottom(gid){
	if(!gid) return;
	 guest_top_wrapper.children("#guest_" + gid).children(".viewport").scrollTop(100000);
}


//重置窗口大小
function reset_window(){
	window_width =$(window).width();
	window_height =$(window).height();

	container.height(window_height - 10);
	container.children(".main_top").height(window_height - drag_to_bottom);
	container.children(".main_top").find(".viewport").height(window_height - drag_to_bottom - 76);

	main_bottom.width(window_width - 20);
	guest_tips.width(window_width - 20);
}


//客服窗口滚动到底部
function support_to_bottom(){
	support_viewport.scrollTop(20000);
}


//toggle底部访客或客服列表swiper
function toggle_list(){
	if(bottomSwiper.activeIndex == 0){
		bottomSwiper.slideTo(1);
	}else{
		bottomSwiper.slideTo(0);
	}
}

//弹出确认框
function toggle_confirm(obj, content, right){
	smilies_div.hide(); //关闭表情图标
	tool_bar.find(".hover").removeClass("hover"); //去掉所有先中状态

	if(confirm_btn.is(":hidden")){

		confirm_btn.html(content).show().css("right", right+"px").data("for", $(obj).attr("id"));

		$(obj).addClass("hover");

	}else{

		var old_id = confirm_btn.data("for");

		if($(obj).attr("id") == old_id){
			confirm_btn.html("").data("for", "").hide();
			$(obj).removeClass("hover");
		}else{
			confirm_btn.html(content).show().css("right", right+"px").data("for", $(obj).attr("id"));
			$(obj).addClass("hover");
			$("#" + old_id).removeClass("hover");
		}

	}
}

//插入常用短语
function insertPhrase(me) {
	var code = $(me).children("b").html();

	var obj = msg_input[0];
	if(!obj) return;

	obj.value = code;
	search_result_hide();
}

//关闭常用短语搜索结果
function search_result_hide(){
	if(phrase_search){
		phrase_search.hide();
		phrase_search = null;
	}
}

//搜索常用短语, 输入框停留1秒开始搜索
function search_phrases(me){
	clearTimeout(ttt_8);
	search_result_hide();

	if(all_phrases.length < 1) return;

	var keyword = $.trim($(me).val());

	if(keyword.length < 2 || keyword.length > 16) return; //太长或太短均不搜索

	ttt_8 = setTimeout(function(){
		var result = "", tmp = "", keywords = keyword.split(/\s+/);

		all_phrases.each(function(){
			var ok = 1;
			tmp = $(this).html();

			$.each(keywords, function(i, key){
				if(tmp.indexOf(key) < 0){
					ok = 0;
					return false;
				}
			});

			if(ok) result += '<li onclick="insertPhrase(this);"><i>●</i><b>' + tmp + '</b></li>';
		});

		if(result){
			phrase_search = container.children(".g_search");
			phrase_search.html('<div class="g_search_title" onclick="search_result_hide();">常用短语 搜索结果：<b>X</b></div>' + result).show();
		}

	}, 1000); //延迟1秒搜索
}

/*===========================================================================*/


//全局变量
var drag_to_bottom = 160; //viewport消息窗口离底部距离
var window_width, window_height; //屏幕宽和高px

var guestSwiper, bottomSwiper, guestListSwiper, supportListSwiper; //滑动对象

var container, main_bottom, send_btn, msg_input, alert_info, smilies_div; //一些页面元素对象

var active_window = 1; //1表示客服群聊窗口活动中, 2表示访客窗口处于活动状态


//guest 相关
var welive_guests = {}; //访客对象, 格式: {gid1: {au: 上传授权, num: 未读数量}, gid2: {au: 上传授权, num: 未读数量}, ...........}

var offline_guests = []; //已离线的访客数组
var page_tmp = []; //临时保存分页的数组

var guest_activeid = 0; //当前活动访客的id号
var guest_activeid_Unread = 0; //当前活动访客的未读信息数

var guest_direction = 1; //单击访客按钮时, 1表示滚动到下一个, 0时表示滚动到上一个窗口

var guest_deleting = 0; //是否正在操作删除访客

var guest_unreads = 0; //访客信息未读数
var guest_onlines = 0; //访客在线数量
var guest_totals = 0; //访客总数量
var guest_sound = 1; //访客声音开关
var guest_top, guest_top_wrapper, guest_toolbar, guest_btn, guest_tips, guest_viewport, guest_sounder, guest_unreads_obj, guest_onlines_obj, guest_totals_obj; //访客相关页面元素对象


//support 相关
var support_unreads = 0; //客服未读数
var support_onlines = 0; //在线客服数量
var support_sound = 1; //客服声音开关
var support_top, support_btn, support_viewport, s_onlines, support_sounder, toolbar_man; //客服相关页面元素对象


//socket连接相关全局变量
var tttt = 0, ttt_1 = 0, ttt_8 = 0, phrase_search = null, all_phrases = "";
var welive = {ws:{}, index: 0, status: 0, autolink: 1, ttt: 0, sound: 0, is_robot: 0};

//websocket
var WebSocket = window.WebSocket || window.MozWebSocket;

var file_chunk_size = 1048576; //切片大小 默认为1M
var file_temp_data = ""; //切片上传文件时使用

//开始运行
$(function(){

	container = $(".container");
	main_bottom = $(".main_bottom");

	msg_input = $("#msg_input");
	send_btn = $("#send_btn");
	alert_info = $("#alert_info");
	tool_bar = main_bottom.children(".tool_bar");
	confirm_btn = container.children("#confirm_btn");
	smilies_div = container.children(".smilies_div");

	guest_top = $(".guest_top");
	guest_top_wrapper = guest_top.children(".swiper-wrapper");
	guest_toolbar = tool_bar.children("#guest_toolbar");
	guest_tips = $("#guest_tips");
	guest_btn = $("#guest_btn");
	guest_list = $(".guest_list");
	guest_sounder = $("#guest_sounder")[0]; //声音的js DOM元素
	guest_unreads_obj = $("#guest_unreads");
	guest_onlines_obj = $("#guest_onlines");
	guest_totals_obj = $("#guest_totals");

	support_top = $(".support_top");
	support_toolbar = tool_bar.children("#support_toolbar");
	support_btn = $("#support_btn");
	support_list = $("#support_list");
	support_viewport = support_top.children(".viewport"); //客服群聊输出窗口
	support_sounder = $("#support_sounder")[0]; //声音的js DOM元素
	s_onlines = $("#support_onlines"); //显示客服在线人数的JQ元素
	toolbar_man = support_toolbar.children("#toolbar_man"); //无人值守按钮
	all_phrases = $(".phrases_wrap li b"); //常用短语

	window_width =$(window).width();
	window_height =$(window).height();

	//初始化窗口大小
	reset_window();

	//横竖屏切换
	$(window).on("orientationchange", function(){reset_window();});

	//先要播放一下声音,否则无法自动播放
	container.one("touchend", function(){
		support_sounder.play();
		guest_sounder.play();
	}); 

	//访客窗口滑动
	guestSwiper = new Swiper('.guest_top', {
		touchRatio: 1.5, //一次仅可能滑动一个访客窗口(超过1.5容易一次划过两个窗口, 但当longSwipes设置为false时可以)
		spaceBetween: 40,
		centeredSlides: true, //当前slide居中
		simulateTouch: false, //关键鼠标模拟tap, 估计可以提升性能

		pagination: {
			el: '#guest_tips',
			bulletClass : 'i',
			bulletActiveClass: 'curr',
			renderBullet: function (index, className) {

				//插件太讨厌, 新添加一个slide时会重写所有的分页, 不得已而为之
				if(typeof(page_tmp[index]) == "undefined"){
					var gid = this.slides.eq(index).data("gid");				
					return '<i id="gp_' + gid + '" class="' + className + '">· · ·</i>';
				}else{
					return page_tmp[index].outerHTML;
				}

			},
		},

		effect: 'coverflow',
		coverflowEffect: {
			rotate: 50,
			stretch: 0,
			depth: 100,
			modifier: 1,
			slideShadows : true,
		},

		on: {
			slideChange: function () {

				if(this.slides.length <= 1) return true;

				//记录活动访客的id号, 必须将data来的gid转换成int, 否则在使用$.inArray函数时出错
				var gid = parseInt(this.slides.eq(this.activeIndex).data("gid"));

				if(guest_activeid_Unread) guest_send_readed(guest_activeid); //切换前给当前访客发送已读信号

				guest_to_bottom(gid); //当前访客窗口滚动到底部

				//切换到当前访客窗口时, 更新当前访客未读数量及按钮状态
				if(typeof(welive_guests[gid]) != 'undefined'){
					if(welive_guests[gid].num > 0) {
						guest_send_readed(gid); //发送已读信号
						guest_unread_update(gid, -welive_guests[gid].num); //更新未读数量(减量)
					}

					//禁言按钮状态
					if(welive_guests[gid].ban){
						guest_toolbar.children("#toolbar_banned").removeClass("banned_on").addClass("banned_off"); //解除禁言 
					}else{
						guest_toolbar.children("#toolbar_banned").removeClass("banned_off").addClass("banned_on");
					}

					//上传授权按钮状态
					if(auth_upload && welive_guests[gid].au){
						guest_toolbar.children("#toolbar_auth").removeClass("auth_on").addClass("auth_off"); //有上传权限时显示解除授权按钮
					}else if(auth_upload){
						guest_toolbar.children("#toolbar_auth").removeClass("auth_off").addClass("auth_on");
					}
				}

				guest_activeid = gid; //切换gid

				//处理分页器
				if(!guest_deleting){
					this.pagination.bullets.eq(this.previousIndex).html("0");
				}
				this.pagination.bullets.eq(this.activeIndex).removeClass("new").html("· · ·");

				//分页器超宽时处理
				var old_w = window_width - 20;
				var new_w = guest_tips.width();
				var rate = parseInt(new_w/old_w);
				var p_left = this.pagination.bullets.eq(this.activeIndex).offset().left;

				if( p_left > old_w && new_w <= rate * old_w){
					guest_tips.width((rate + 1)*old_w).css("left", -rate * old_w);				  
				}else if(p_left < 0 && new_w > (rate-1) * old_w ){
					guest_tips.width((rate-1)*old_w).css("left", -(rate - 2) * old_w);
				}

				//最后处理, 访客列表swiper状态
				guest_list.find("i.curr").removeClass("curr");
				guest_list.find("i.swiper-slide").eq(this.activeIndex).removeClass("new").addClass("curr").children("u").html("");

				guestListSwiper.slideTo(this.activeIndex);//滚动到当前头像
			},

			slideNextTransitionEnd: function(){
				if(!guest_direction) guest_direction = 1;
			},

			slidePrevTransitionEnd: function(){
				if(guest_direction) guest_direction = 0;
			},

		},

	});


	//底部输入框、客服、访客swiper
	bottomSwiper = new Swiper('.bottom_swiper', {
		touchRatio: 1,
		centeredSlides: true, //当前slide居中
		simulateTouch: false, //关键鼠标模拟tap, 估计可以提升性能

	});

	//底部swiper嵌套客人列表滑动
	guestListSwiper = new Swiper('.guest_list_swiper', {
		freeMode: true,
		slidesPerView:  "auto",
		simulateTouch: false,
		//centeredSlides: true,
		freeModeSticky: true,
		nested: true, //子swiper滑动见底或见顶时切换父swiper

		on:{
			click: function(){
				guestSwiper.slideTo(this.clickedIndex); //点击时切换访客窗口
			},
		},

	});

	//底部swiper嵌套客人列表滑动
	supportListSwiper = new Swiper('.support_list_swiper', {
		freeMode: true,
		slidesPerView:  "auto",
		simulateTouch: false,
		freeModeSticky : true,
		nested: true,
	});


	//初始化底部访客列表swiper, 不能在css中将guest_list设置为hidden, 否则滑动出错
	guest_list.hide();
	bottomSwiper.updateSlides();


	//发送信息
	send_btn.click(function(e) {
		var msg = $.trim(msg_input.val());

		if(msg){

			msg_send(msg);
	
		}else{
			toggle_list(); //toggle客服或客人列表swiper
		}

		e.preventDefault();
	});

	//切换群聊区
	support_btn.click(support_show);

	//切换访客窗口
	guest_btn.click(guest_show);

	//container点击时, 隐藏所有的弹出确认按钮或其它弹出层
	container.on("click", function(e){
		//判断当前访客是否需要发送已读信号
		if(active_window == 2){
			if(guest_activeid_Unread) guest_send_readed(guest_activeid);
		}

		confirm_btn.html("").hide();
		smilies_div.hide();
		tool_bar.find(".hover").removeClass("hover");
	});


	//初始化websocket连接
	if(WS_HOST == "")	WS_HOST = document.domain; //先记录下来供websocket连接使用
	welive_link();


	//移动端输入法按发送键触发
	msg_input.keyup(function(e){
		if(e.keyCode ==13){
			msg_send();
			$(this).blur();
		}
	}).on("input propertychange", function(e){
		search_phrases(this); //搜索常用短语
	});   


	//表情符号
	tool_bar.find(".emotion").on("click", function(e){

		confirm_btn.html("").hide(); //关闭确认按钮

		smilies_div.toggle();

		if($(this).hasClass("hover")){
			$(this).removeClass("hover");
		}else{
			tool_bar.find(".hover").removeClass("hover"); //去掉所有先中状态
			$(this).addClass("hover");
		}

		e.stopPropagation();
	});


	//声音按钮
	tool_bar.find(".toolbar_sound").on("click", function(e){

		//访客窗口可见时
		if(active_window == 2){
			if(guest_sound){
				guest_sound = 0;
				$(this).removeClass("sound_on").addClass("sound_off");
			}else{
				guest_sounder.play();
				guest_sound = 1;
				$(this).removeClass("sound_off").addClass("sound_on");
			}

		}else{//客服窗口可见时
			if(support_sound){
				support_sound = 0;
				$(this).removeClass("sound_on").addClass("sound_off");
			}else{
				support_sounder.play();
				support_sound = 1;
				$(this).removeClass("sound_off").addClass("sound_on");
			}
		}

		e.stopPropagation();
	});


	//上传图片
	guest_toolbar.children("#toolbar_upload").on("click", function(e){

		confirm_btn.html("").hide(); //关闭确认按钮
		smilies_div.hide(); //关闭表情图标

		tool_bar.find(".hover").removeClass("hover"); //去掉所有先中状态
		$(this).addClass("hover");

		//客人不在线时
		if(!guest_activeid || $.inArray(guest_activeid, offline_guests) > -1){
			show_alert("暂无访客, 无法发送图片或文件!");
		}else if(welive.status){
			//$("#upload_input").trigger("click");
			document.getElementById("upload_input").click(); //兼容IE
		}

		$(this).removeClass("hover");

		e.stopPropagation();
	});


	//挂起按钮
	support_toolbar.children("#toolbar_serving").on("click", function(e){

		if($(this).hasClass("serving_on")){
			toggle_confirm(this, '<div onclick="set_serving_status(0);">请求挂起</div>', 32);
		}else{
			toggle_confirm(this, '<div onclick="set_serving_status(1);">解除挂起</div>', 32);
		}

		e.stopPropagation();
	});


	//退出按钮
	support_toolbar.children("#toolbar_exit").on("click", function(e){
		toggle_confirm(this, '<div onclick="exit_welive();">退出客服</div>', 2);
		e.stopPropagation();
	});


	//管理员或组长专有
	if(admin.type != 0){

		//无人值守按钮动作
		toolbar_man.on("click", function(e){

			if($(this).hasClass("man_on")){
				toggle_confirm(this, '<div onclick="set_man_status(0);">开启无人值守</div>', 60);
			}else{
				toggle_confirm(this, '<div onclick="set_man_status(1);">关闭无人值守</div>', 60);
			}

			e.stopPropagation();
		});

	//非管理员的无人值守按钮只显示信息
	}else{
		
		toolbar_man.on("click", function(e){

			if($(this).hasClass("man_on")){
				toggle_confirm(this, '<div>无人值守未开启</div>', 56);
			}else{
				toggle_confirm(this, '<div>无人值守开启中</div>', 56);
			}

			e.stopPropagation();
		});
	}


	//上传授权, 当开启上传时需要授权功能是有效
	if(auth_upload){
		guest_toolbar.children("#toolbar_auth").removeClass("none").addClass("auth_on").on("click", function(e){

			if($(this).hasClass("auth_on")){
				toggle_confirm(this, '<div onclick="guest_authupload(1);">授权上传</div>', 126);
			}else{
				toggle_confirm(this, '<div onclick="guest_authupload(0);">解除授权</div>', 126);
			}

			e.stopPropagation();
		});
	}


	//禁止发言
	guest_toolbar.children("#toolbar_banned").on("click", function(e){

		if($(this).hasClass("banned_on")){
			toggle_confirm(this, '<div onclick="guest_banned(1);">禁止发言</div>', 78);
		}else{
			toggle_confirm(this, '<div onclick="guest_banned(0);">解除禁言</div>', 78);
		}

		e.stopPropagation();
	});


	//转接客人按钮
	guest_toolbar.children("#toolbar_transfer").on("click", function(e){
		toggle_confirm(this, '<div onclick="guest_transfer();">转接访客</div>', 32);
		e.stopPropagation();
	});


	//关闭访客按钮
	guest_toolbar.children("#toolbar_close").on("click", function(e){
		toggle_confirm(this, '<div onclick="guest_close();">关闭访客</div>', 2);
		e.stopPropagation();
	});


	//表情图标层阻止事件传播
	smilies_div.on("click", function(e){
		e.stopPropagation();
		return false;
	});

	//监听上传图片控件
	sendImgFileListener("#upload_input", function(data) {
		welive_send_img(data);
	});

	//离开当前页面时
	$(window).on("pagehide beforeunload", function(){
		welive.status=0;
		clearTimeout(welive.ttt);
		clearInterval(ttt_1);
	});

	//每10分钟清除过长的客服间对话记录
	setInterval(support_record_clear, 1000*600);

});