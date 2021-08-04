<?php if(!defined('ROOT')) die('Access denied.');

class c_index extends Admin{

    function index($path){
		$today = DisplayDate(); //今日日期
		ini_set('date.timezone', 'GMT'); //先设置为格林威治时区, 时区会影响strtotime函数将日期转为时间戳
		$start_time = intval(strtotime($today)) - 3600 * intval(APP::$_CFG['Timezone']); //再根据welive设置的时区转为UNIX时间戳
		$end_time = $start_time + 86400;
		
		//获取统计数据
		$basedata = APP::$DB->getOne("SELECT (select COUNT(cid)  FROM " . TABLE_PREFIX . "comment WHERE readed = 0) AS new_comments, 
		(select COUNT(gid) FROM " . TABLE_PREFIX . "guest) AS guests, 
		(select COUNT(gid) FROM " . TABLE_PREFIX . "guest WHERE last >= '$start_time' AND last < '$end_time') AS today_guests, 
		(select COUNT(mid) FROM " . TABLE_PREFIX . "msg) AS msgs, 
		(select COUNT(mid) FROM " . TABLE_PREFIX . "msg WHERE time >= '$start_time' AND time < '$end_time') AS today_msgs, 
		(select COUNT(rmid) FROM " . TABLE_PREFIX . "robotmsg) AS robotmsgs, 
		(select COUNT(rmid) FROM " . TABLE_PREFIX . "robotmsg WHERE time >= '$start_time' AND time < '$end_time') AS today_robotmsgs, 
		(select COUNT(rid)  FROM " . TABLE_PREFIX . "rating) AS ratings,
		(select ROUND(AVG(score), 2)  FROM " . TABLE_PREFIX . "rating) AS avg_score");

		$dir_size_img = $this->dirsize(ROOT . 'upload/img/'); //上传图片文件夹的大小
		$dir_size_img = number_format($dir_size_img / 1024000, 2);

		$dir_size_file = $this->dirsize(ROOT . 'upload/file/'); //上传普通文件的文件夹大小
		$dir_size_file = number_format($dir_size_file / 1024000, 2);

		SubMenu('欢迎进入 '.APP_NAME.' 管理中心', array(
			array('查看留言', 'comments'),
			array('管理客人', 'guests'),
			array('管理记录', 'messages'),
			array('管理评价', 'rating'),
			array('清理图片', 'upload_img'),
			array('清理文件', 'upload_file'),
			array('智能客服管理', 'robot')
		));


echo '<style type="text/css">
.statistics{padding:30px;font-size:18px;color:#666;}
.sta_item{margin-right:12px;width:240px;height:180px;border:2px solid #ddd;overflow:hidden;border-radius:7px;background:#fff;text-align:center;display:inline-block;}
.sta_item_top{padding:24px 0 14px 0;line-height:2em;}
.sta_item_top font{color:#00c1c1;font-size:24px;vertical-align:middle;}
.sta_item_bot{border-top:2px solid #ddd;padding: 20px 0;background:#f1f1f1;height:50px;overflow:hidde;white-space:nowrap;}
</style>
<div class="statistics">
	<div class="sta_item">
		<div class="sta_item_top">今日访客<br><font>' . $basedata['today_guests'] . '</font></div>
		<div class="sta_item_bot">访客总计&nbsp;&nbsp;' . $basedata['guests'] . Iif($basedata['guests'] > 10000, '<a class="alert-btn" href="' . BURL('guests') . '">清理</a>') . '</div>
	</div>		
	<div class="sta_item">
		<div class="sta_item_top">今日会话<br><font>' . $basedata['today_msgs'] . '</font></div>
		<div class="sta_item_bot">会话总计&nbsp;&nbsp;' . $basedata['msgs'] . Iif($basedata['msgs'] > 10000, '<a class="alert-btn" href="' . BURL('messages') . '">清理</a>') . '</div>
	</div>		
	<div class="sta_item">
		<div class="sta_item_top">今日机器人无解<br><font>' . $basedata['today_robotmsgs'] . '</font></div>
		<div class="sta_item_bot">无解总计&nbsp;&nbsp;' . $basedata['robotmsgs'] . Iif($basedata['robotmsgs'] > 1000, '<a class="alert-btn" href="' . BURL('robotmsgs') . '">处理</a>') . '</div>
	</div>		
	<div class="sta_item">
		<div class="sta_item_top">评价得分<br><font>' . Iif($basedata['avg_score'],  $basedata['avg_score'], 0). '</font></div>
		<div class="sta_item_bot">评价总计&nbsp;&nbsp;' . $basedata['ratings'] . Iif($basedata['ratings'] > 10000, '<a class="alert-btn" href="' . BURL('rating') . '">清理</a>') . '</div>
	</div>		
	<div class="sta_item">
		<div class="sta_item_top">图片总计<br><font>' . $dir_size_img . ' M</font>' . Iif($dir_size_img > 1000, '<a class="alert-btn" href="' . BURL('upload_img') . '">清理</a>') . '</div>
		<div class="sta_item_bot">文件总计&nbsp;&nbsp;' . $dir_size_file . ' M' . Iif($dir_size_file > 1000, '<a class="alert-btn" href="' . BURL('upload_file') . '">清理</a>') . '</div>
	</div>		
</div>';


		$welcome = '<ul><li>欢迎 <font class=orange>'.$this->admin['fullname'].'</font> 进入后台管理面板! 为了确保系统安全, 请在关闭前点击 <a href="#" class="logout">退出</a> 安全离开!</li>
		<li>您在使用 '.APP_NAME.'客服系统[商业版'.APP_VERSION.'] 时有任何问题, 请访问: <a href="http://www.weensoft.cn/bbs/" target="_blank">为因软件 weensoft.cn</a></li></ul>';

		ShowTips($welcome, '系统信息');

		BR(1);

		TableHeader('客服操作技巧说明');

		TableRow('<font class=grey>1)</font>&nbsp;&nbsp;将代码 <span class=note>&lt;script type="text/javascript" charset="UTF-8" src="' . BASEURL . 'welive.js"&gt;&lt;/script&gt;</span> 插入需要调用WeLive客服小面板的网页&lt;html&gt;内.<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;此方式将在当前页面打开对话窗口, 支持web和移动端页面. 如要单独调用某客服组, 请加参数如: <span class=note>......welive.js?g=客服组id</span>');

		TableRow('<font class=grey>2)</font>&nbsp;&nbsp;将代码 <span class=note>&lt;script type="text/javascript" charset="UTF-8" src="' . BASEURL . 'welive-new.js"&gt;&lt;/script&gt;</span> 插入需要在新窗口打开WeLive客服对话窗口的网页&lt;html&gt;内.<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;编辑此页面中的元素，为其添加样式：class="welive-new"，那么点击此元素将在新窗口中打开WeLive对话窗口，如：&lt;a class="welive-new"&gt;点击在新窗口打开WeLive&lt;/a&gt;<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;此方式支持web和移动端页面. 如要单独调用某客服组, 请加参数如: <span class=note>......welive-new.js?g=客服组id</span><br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;还可以在同一页面显示多个客服组按钮，如：&lt;a class="welive-new" group="8"&gt;进入id为8的客服组&lt;/a&gt; 或 &lt;a class="welive-new" group="9"&gt;进入id为9的客服组&lt;/a&gt; 等等.');

		TableRow('<font class=grey>3)</font>&nbsp;&nbsp;链接URL直接进入对话窗口(二维码): <span class=note>' . BASEURL . 'kefu.php?a=621276866&g=客服组id</span> 此方式支持web和移动端. 参阅：<a href="../" target="_blank">前台客服加载演示</a>');
		TableRow('<font class=grey>4)</font>&nbsp;&nbsp;与原网站用户接口：在引入WeLive客服系统JS文件的页面，将原网站的用户id和用户名(昵称)分别保存名称为:“welive_id”和“welive_fn”的<span class=note>Cookie</span>.');
		TableRow('<font class=grey>5)</font>&nbsp;&nbsp;客服窗口中, 按 Ctrl + Alt, 在客服交流区与当前客人小窗口间切换.');
		TableRow('<font class=grey>6)</font>&nbsp;&nbsp;客服窗口中, 按 Ctrl + 下箭头 或 Esc键, 关闭当前客人小窗口. 如果小窗口都关闭了, 自动切换到客服交流区.');
		TableRow('<font class=grey>7)</font>&nbsp;&nbsp;客服窗口中, 按 Ctrl + 上箭头, 展开关闭的客人小窗口.');
		TableRow('<font class=grey>8)</font>&nbsp;&nbsp;客服窗口中, 按 Ctrl + 左或右箭头, 在已展开的客人小窗口间切换.');
		TableRow('<font class=grey>9)</font>&nbsp;&nbsp;移动端客服登录地址: <span class=note>' . BASEURL . 'app/</span>, 后台管理及客服登录目录admin或app均可任修改.');
		TableRow('<font class=grey>10)</font>&nbsp;&nbsp;在客服窗口中的客服交流区, 管理员、客服组长可发送特殊指令:&nbsp;&nbsp;all --- 显示所有连接数;&nbsp;&nbsp;admin --- 显示所有客服及其客人数;&nbsp;&nbsp;guest --- 显示客人数;&nbsp;&nbsp;robot --- 显示机器人服务数据');
		TableRow('<font class=grey>11)</font>&nbsp;&nbsp;Linux服务器：在SSH命令行终端进入WeLive安装目录，运行 <span class=red>php start.php start -d</span> 启动Workerman，Workerman启动后可退出SSH终端.<br><div style="margin-left:30px;">运行 <span class=red>php start.php stop</span> 终止运行Workerman; 需要重启Workerman时, 可以先终止, 然后再启动.</div>');
		TableRow('<font class=grey>12)</font>&nbsp;&nbsp;Windows服务器：双击WeLive根目录下的 <span class=red>start-for-win.bat</span> 文件启动Workerman，而且打开的命令行窗口不能关闭(按 Ctrl + C 可关闭)，一旦关闭Socket服务将终止.');

		TableFooter();

		//更新顶部提示信息
		echo '<script type="text/javascript">
			$(function(){
				var info_total = ' . $basedata['new_comments'] . ';

				if(info_total > 0){
					$("#topuser dl#info_all").removeClass("none");
					$("#topuser #info_total").html(info_total);
					$("#topuser #info_comms").html(info_total).attr("class", "orangeb");

				}

				//将统计数据保存为cookie. 注: header已发送, 此页面不能使用php保存cookie
				setCookie("' . COOKIE_KEY . 'backinfos", info_total, 365);
			});
		</script>';
    }

	/**
	 * 文件夹大小
	 * @param $path
	 * @return int
	 */
	private function dirsize($path)
	{
		$size = 0;
		if(!is_dir($path)) return $size;

		$handle = opendir($path);
		while (($item = readdir($handle)) !== false) {
			if ($item == '.' || $item == '..') continue;
			$_path = $path . '/' . $item;
			if (is_file($_path)){
				$size += filesize($_path);
			}else{
				$size += $this->dirsize($_path);
			}
		}
		closedir($handle);
		return $size;
	}


}

?>