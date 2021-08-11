<?php if(!defined('ROOT')) die('Access denied.');

class c_robotmsgs extends Admin{

	public function __construct($path){
		parent::__construct($path);

		$this->CheckAction();
	}

	//ajax动作集合, 通过action判断具体任务
    public function ajax(){
		//ajax权限验证
		if(!$this->CheckAccess()){
			$this->ajax['s'] = 0; //ajax操作失败
			$this->ajax['i'] = '您没有权限管理机器人无解记录!';
			die($this->json->encode($this->ajax));
		}
		
		$action = ForceStringFrom('action');

		//标记无解记录
		if($action == 'mark_robotmsg'){

			$rmid = ForceIntFrom('rmid');
			$remark = ForceIntFrom('remark');

			APP::$DB->exe("UPDATE " . TABLE_PREFIX . "robotmsg SET remark = '$remark' WHERE rmid = '$rmid'");
		}

		//保存无解回复
		elseif($action == 'save_msg'){
			$lang = ForceIntFrom('lang');
			$key = ForceIntFrom('forkey');
			$msg = $_POST['msg']; //无需mysql过滤, 支持html

			//解决PHP7 Opcache开启时无法实时更新设置的问题
			if(function_exists('opcache_reset')) {
				@opcache_reset();
			}

			$filename = ROOT . "config/settings.php";

			//修改./config/settings.php文件
			$fp = @fopen($filename, 'rb');
			$contents = @fread($fp, @filesize($filename));
			@fclose($fp);
			$contents = $oldcontents = trim($contents);

			//处理信息
			$msg = $this->Clear_string_for_js($msg); //去掉换行符, 兼容JS变量调用
			$msg = str_replace('"', "'", $msg); //将引双引号替换成单引号

			if($lang){ //中文
				$contents = preg_replace("/[$]_CFG\['RobotReplyArrCn'\]\[" . $key . "\]\s*\=\s*[\"'].*?[\"'];/is", "\$_CFG['RobotReplyArrCn'][$key] = \"$msg\";", $contents);
			}else{ //Englisth
				$contents = preg_replace("/[$]_CFG\['RobotReplyArrEn'\]\[" . $key . "\]\s*\=\s*[\"'].*?[\"'];/is", "\$_CFG['RobotReplyArrEn'][$key] = \"$msg\";", $contents);
			}

			if($contents != $oldcontents){
				$fp = @fopen($filename, 'w');
				@fwrite($fp, $contents);
				@fclose($fp);
			}
		}

		die($this->json->encode($this->ajax));
	}

	//快速删除记录
	public function fastdelete(){
		$days = ForceIntFrom('days');

		if(!$days) Error('请选择删除期限!');

		$time = time() - $days * 24 * 3600;

		APP::$DB->exe("DELETE FROM " . TABLE_PREFIX . "robotmsg WHERE time < $time");

		Success('robotmsgs');
	}

	//批量更新记录
	public function updaterobotmsgs(){
		$page = ForceIntFrom('p', 1);   //页码
		$search = ForceStringFrom('s');
		$groupid = ForceIntFrom('g');
		$time = ForceStringFrom('t');
		$order = ForceStringFrom('o');

		$deletermids = Iif(isset($_POST['deletermids']), $_POST['deletermids'], array());

		for($i = 0; $i < count($deletermids); $i++){
			$rmid = ForceInt($deletermids[$i]);
			APP::$DB->exe("DELETE FROM " . TABLE_PREFIX . "robotmsg WHERE rmid = '$rmid'");
		}

		Success('robotmsgs?p=' . $page. FormatUrlParam(array('s'=>urlencode($search), 'g'=>$groupid, 't'=>$time, 'o'=>$order)));
	}


	public function index(){
		$NumPerPage = 20;
		$page = ForceIntFrom('p', 1);
		$search = ForceStringFrom('s');
		$groupid = ForceIntFrom('g');
		$time = ForceStringFrom('t');

		if(IsGet('s')) $search = urldecode($search);

		if($time){
			ini_set('date.timezone', 'GMT'); //先设置为格林威治时区, 时区会影响strtotime函数将日期转为时间戳
			$start_time = intval(strtotime($time)) - 3600 * intval(APP::$_CFG['Timezone']); //再根据welive设置的时区转为UNIX时间戳
			$end_time = $start_time + 86400;
		}

		$start = $NumPerPage * ($page-1);

		//排序
		$order = ForceStringFrom('o');
        switch($order)
        {
            case 'time.down':
				$orderby = " time DESC ";
				break;

            case 'time.up':
				$orderby = " time ASC ";
				break;

            case 'remark.down':
				$orderby = " remark DESC ";
				break;

            case 'remark.up':
				$orderby = " remark ASC ";
				break;

            case 'grid.down':
				$orderby = " grid DESC ";
				break;

            case 'grid.up':
				$orderby = " grid ASC ";
				break;

            case 'fromid.down':
				$orderby = " fromid DESC ";
				break;

            case 'fromid.up':
				$orderby = " fromid ASC ";
				break;

            case 'rmid.up':
				$orderby = " rmid ASC ";
				break;

			default:
				$orderby = " rmid DESC ";			
				$order = "rmid.down";
				break;
		}

		$usergroups = array();
		$getusergroups = APP::$DB->query("SELECT id, groupname FROM " . TABLE_PREFIX . "group ORDER BY id");
		while($g = APP::$DB->fetch($getusergroups)) {
			$usergroups[$g['id']] = $g['groupname'];
			$usergroup_options .= "<option value=\"$g[id]\" " . Iif($g['id'] == $groupid, 'SELECTED') . ">$g[groupname]</option>";
		}

		SubMenu('机器人无解管理', array(array('无解记录列表', 'robotmsgs', 1), array('无解回复列表', 'robotmsgs/replies')));

		$info = '<ul><li>“机器人无解记录”是指开启无人值守后, 无法根据机器人自动回复内容匹配客人的发言时, 而记录下的客人的发言. 您可以根据对话内容添加或修改机器人自动回复, 以便提高其聪明度;</li>
		<li>&nbsp;&nbsp;当添加或修改机器人自动回复内容时, 如果某些机器人无解记录被正确匹配, 那么这些机器人无解记录将会被自动删除. 当然您也可以手动删除机器人无解记录.</li></ul>';

		ShowTips($info, '机器人无解记录说明');

		TableHeader('搜索及快速删除');

		TableRow('<center><form method="post" action="'.BURL('robotmsgs').'" name="searchrobotmsgs" style="display:inline-block;*display:inline;"><label>关键字:</label>&nbsp;<input type="text" name="s" size="12"  value="'.$search.'">&nbsp;&nbsp;&nbsp;<label>客服组:</label>&nbsp;<select name="g"><option value="0">全部</option>' . $usergroup_options . '</select>&nbsp;&nbsp;&nbsp;<label>日期:</label>&nbsp;<input type="text" name="t" class="date-input" value="' . $time . '" size="8">&nbsp;&nbsp;&nbsp;&nbsp;<input type="submit" value="搜索无解记录" class="cancel"></form>

		<form method="post" action="'.BURL('robotmsgs/fastdelete').'" name="fastdelete" style="display:inline-block;margin-left:80px;*display:inline;"><label>快速删除无解记录:</label>&nbsp;<select name="days"><option value="0">请选择 ...</option><option value="360">12个月前的无解记录</option><option value="180">&nbsp;6 个月前的无解记录</option><option value="90">&nbsp;3 个月前的无解记录</option><option value="30">&nbsp;1 个月前的无解记录</option><option value="1">&nbsp;1 天前的无解记录</option></select>&nbsp;&nbsp;&nbsp;&nbsp;<input type="submit" value="快速删除" class="save" onclick="var _me=$(this);showDialog(\'确定删除所选无解记录吗?\', \'确认操作\', function(){_me.closest(\'form\').submit();});return false;"></form></center>');
		
		TableFooter();

		if($search){
			if(preg_match("/^[1-9][0-9]*$/", $search)){
				$s = ForceInt($search);
				$searchsql = " WHERE rmid = '$s' OR fromid = '$s' "; //按ID搜索
				$title = "搜索ID号为: <span class=note>$s</span> 的无解记录";
			}else{
				$searchsql = " WHERE (fromname LIKE '%$search%' OR msg LIKE '%$search%') ";
				$title = "搜索: <span class=note>$search</span> 的无解记录列表";
			}
			if($groupid) {
				$searchsql .= " AND grid = '$groupid' ";
			}

			if($time) {
				$searchsql .= " AND time >= '$start_time' AND time < '$end_time' ";
			}
		}else if($groupid){
			$searchsql .= " WHERE grid = '$groupid' ";
			$title = "所属客服组: <span class=note>{$usergroups[$groupid]}</span> 的无解记录列表";

			if($time) {
				$searchsql .= " AND time >= '$start_time' AND time < '$end_time' ";
			}
		}else if($time){
			$searchsql .= " WHERE time >= '$start_time' AND time < '$end_time' ";
			$title = "搜索日期: <span class=note>{$time}</span> 的无解记录列表";
		}else{
			$searchsql = '';
			$title = '全部无解记录列表';
		}

		$getrobotmsgs = APP::$DB->query("SELECT * FROM " . TABLE_PREFIX . "robotmsg ".$searchsql." ORDER BY {$orderby} LIMIT $start,$NumPerPage");

		$maxrows = APP::$DB->getOne("SELECT COUNT(rmid) AS value FROM " . TABLE_PREFIX . "robotmsg ".$searchsql);

		echo '<script type="text/javascript">
			function remark_item(obj, rmid, remark){
				ajax("' . BURL('robotmsgs/ajax') . '", {action: "mark_robotmsg", rmid: rmid, remark: remark}, function(data){
					$(obj).siblings().show();
					$(obj).hide();
				});
			}
		</script>
		<script type="text/javascript" src="'.SYSDIR.'public/laydate/laydate.js"></script>
		<form method="post" action="'.BURL('robotmsgs/updaterobotmsgs').'" name="robotmsgform">
		<input type="hidden" name="p" value="'.$page.'">
		<input type="hidden" name="s" value="'.$search.'">
		<input type="hidden" name="g" value="'.$groupid.'">
		<input type="hidden" name="t" value="'.$time.'">
		<input type="hidden" name="o" value="'.$order.'">';

		TableHeader($title.'('.$maxrows['value'].'个)');

		echo '<tr class="tr0"><td class=td><a class="do-sort" for="rmid">ID</a></td><td class=td><a class="do-sort" for="fromid">发送人</a></td><td class=td><a class="do-sort" for="grid">客服组</a></td><td class=td>接收人</td><td class=td><a class="do-sort" for="remark">处理状态</a></td><td class="td" width="50%">对话内容</td><td class=td><a class="do-sort" for="time">记录时间</a></td><td class="td last"><input type="checkbox" id="checkAll" for="deletermids[]"> <label for="checkAll">删除</label></td></tr>';

		if($maxrows['value'] < 1){
			TableRow('<center><BR><font class=redb>未搜索到任何记录!</font><BR><BR></center>');
		}else{
			while($msg = APP::$DB->fetch($getrobotmsgs)){
				TableRow(array($msg['rmid'],

				"<a title=\"编辑\" href=\"" . Iif($msg['type'], BURL('users/edit?aid='.$msg['fromid']), BURL('guests/edit?gid='.$msg['fromid'])) . "\">$msg[fromname]</a>",

				"<font class=grey>" . Iif(isset($usergroups[$msg['grid']]), $usergroups[$msg['grid']], '-') . "</font>",

				"<font class=grey>机器人</font>",

				'<img src="'. SYSDIR .'public/img/mark.png" onclick="remark_item(this, ' . $msg['rmid'] . ', 0);" style="height:26px;cursor: pointer;' . Iif($msg['remark'], '', 'display:none;') . '" title="标记为未处理"><img src="'. SYSDIR .'public/img/unmark.png" onclick="remark_item(this, ' . $msg['rmid'] . ', 1);" style="height:26px;cursor: pointer;' . Iif($msg['remark'], 'display:none;') . '" title="标记为已处理">',

				getSmile($msg['msg']),

				DisplayDate($msg['time'], '', 1),

				'<input type="checkbox" name="deletermids[]" value="' . $msg['rmid'] . '">'));
			}

			$totalpages = ceil($maxrows['value'] / $NumPerPage);

			if($totalpages > 1){
				TableRow(GetPageList(BURL('robotmsgs'), $totalpages, $page, 10, array('s'=>urlencode($search), 'g'=>$groupid, 't'=>$time, 'o'=>$order)));
			}

		}

		TableFooter();

		PrintSubmit('删除无解记录', '', 1, '确定删除所选无解记录吗?');

		//JS排序等
		echo '<script type="text/javascript">
			$(function(){
				var url = "' . BURL("robotmsgs") . FormatUrlParam(array('p'=>$page, 's'=>urlencode($search), 'g'=>$groupid, 't'=>$time)) . '";

				format_sort(url, "' . $order . '");

				//日期选择器
				$(".date-input").each(function(){
					laydate.render({
						elem: this
					});
				});

			});
		</script>';

	}


	public function replies(){

		SubMenu('机器人无解管理', array(array('无解记录列表', 'robotmsgs'), array('无解回复列表', 'robotmsgs/replies', 1)));

		$info = '<ul><li>“机器人无解回复”是指访客与机器人对话时，无法根据机器人自动回复内容匹配访客的发言时，系统自动返回给访客的信息；</li>
		<li>&nbsp;&nbsp;系统会根据访客浏览器的语言，随机选择一条返回给访客。此功能的作用是使机器人无解回复时，显得不那么无聊；</li>
		<li>&nbsp;&nbsp;如需要在回复内容中插入链接，可直接插入链接的URL，系统会自动解析URL，请勿添加&lt;a&gt;标签；</li>
		<li>&nbsp;&nbsp;<font class=red>无解回复修改、保存更新后，Workerman需要重启才能生效</font>。如需要增加条目，可手动编辑 <font class=green>./config/settings.php</font> 文件。</li>
		</ul>';

		ShowTips($info, '机器人无解回复说明');

		TableHeader('机器人无解回复列表');

		echo '<tr class="tr0"><td class="td" style="width:60px;">Key</td><td class="td" style="width:80px;">语言</td><td class="td" width="10%">回复内容</td><td class="td last">&nbsp;&nbsp;保存</td></tr>';

		if(isset(APP::$_CFG['RobotReplyArrCn']) AND is_array(APP::$_CFG['RobotReplyArrCn']) AND !empty(APP::$_CFG['RobotReplyArrCn'])){
			$RobotReplyArrCn = APP::$_CFG['RobotReplyArrCn'];

			foreach($RobotReplyArrCn as $k => $msg){
				TableRow(array($k,
				'中文',
				'<textarea name="msg" style="width:600px;padding:6px;line-height:22px;" class="msg_tip" id="input_cn_' . $k . '">' . $msg . '</textarea>',
				'&nbsp;&nbsp;<img src="'. SYSDIR .'public/img/save.png" class="save_item" forkey="' . $k . '" lang="1" style="width:26px;cursor: pointer;" title="保存无解回复">'));
			}

			TableRow(array('&nbsp;', '&nbsp;', '&nbsp;', '&nbsp;'));
		}

		if(isset(APP::$_CFG['RobotReplyArrEn']) AND is_array(APP::$_CFG['RobotReplyArrEn']) AND !empty(APP::$_CFG['RobotReplyArrEn'])){
			$RobotReplyArrEn = APP::$_CFG['RobotReplyArrEn'];

			foreach($RobotReplyArrEn as $k => $msg){
				TableRow(array($k,
				'<font class=grey>En</font>',
				'<textarea name="msg" style="width:600px;padding:6px;line-height:22px;" class="msg_tip" id="input_en_' . $k . '">' . $msg . '</textarea>',
				'&nbsp;&nbsp;<img src="'. SYSDIR .'public/img/save.png" class="save_item" forkey="' . $k . '" lang="0" style="width:26px;cursor: pointer;" title="保存无解回复">'));
			}
		}

		TableFooter();

 		$this->print_script();

	}


	private function print_script(){
		$this->smilies = ''; //表情图标
		for($i = 0; $i < 24; $i++){
			$this->smilies .= '<img src="' . SYSDIR . 'public/smilies/' . $i . '.png" onclick="insertSmilie(' . $i . ', \'towhere\');">';
		}

		echo '<div class="smilies_div" style="display:none"><div class="smilies_wrap">' . $this->smilies . '</div></div>
		<script type="text/javascript">
			$(function(){
				$(".msg_tip").tipTip({content: "", keepAlive:true, activation:"click", maxWidth:"380px", defaultPosition:"top", edgeOffset:-1,
					enter:function(id){
						var content = $(".smilies_div").html().replace(/towhere/ig, id);
						$("#tiptip_content").html(content);
					}
				});

				//保存单条记录
				$(".save_item").click(function(e){
					save_item($(this));
					e.preventDefault();
					return false;
				});

				function save_item(obj){
					obj.attr("src", "'. SYSDIR .'public/img/saving.gif");
					var item = obj.parent().parent();
					var lang = obj.attr("lang");
					var forkey = obj.attr("forkey");
					var msg = item.find("[name=\'msg\']").val();

					ajax("' . BURL('robotmsgs/ajax?action=save_msg') . '", {forkey:forkey, lang:lang, msg:msg}, function(data){
						setTimeout(function(){
							obj.attr("src", "'. SYSDIR .'public/img/save.png");
						}, 500); //0.5秒切换, 否则太快没效果
					});
				}

			});
		</script>';

	}

	//去掉空白及换行函数
	private function Clear_string_for_js($str) 
	{
        $str = str_replace(PHP_EOL, '<br/>', $str); //去掉换行符, 兼容JS变量调用

		$str = preg_replace("/\t/", '', $str); //使用正则表达式替换内容，如：换行
		$str = preg_replace("/\r\n/", '', $str); 
		$str = preg_replace("/\r/", '', $str); 
		$str = preg_replace("/\n/", '', $str); 
		return trim($str); //返回字符串
	}

} 

?>