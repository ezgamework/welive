<?php if(!defined('ROOT')) die('Access denied.');

class c_messages extends Admin{

	public function __construct($path){
		parent::__construct($path);

		$this->CheckAction();
	}


	//快速删除记录
	public function fastdelete(){
		$days = ForceIntFrom('days');

		if(!$days) Error('请选择删除期限!');

		$time = time() - $days * 24 * 3600;

		APP::$DB->exe("DELETE FROM " . TABLE_PREFIX . "msg WHERE time < $time");

		Success('messages');
	}

	//批量更新记录
	public function updatemessages(){
		$page = ForceIntFrom('p', 1);   //页码
		$search = ForceStringFrom('s');
		$from = ForceStringFrom('f');
		$groupid = ForceIntFrom('g');
		$time = ForceStringFrom('t');
		$order = ForceStringFrom('o');

		$deletemids = Iif(isset($_POST['deletemids']), $_POST['deletemids'], array());

		for($i = 0; $i < count($deletemids); $i++){
			$mid = ForceInt($deletemids[$i]);
			APP::$DB->exe("DELETE FROM " . TABLE_PREFIX . "msg WHERE mid = '$mid'");
		}

		Success('messages?p=' . $page. FormatUrlParam(array('s'=>urlencode($search), 'f'=>$from, 'g'=>$groupid, 't'=>$time, 'o'=>$order)));
	}


	public function index(){
		$NumPerPage = 20;
		$page = ForceIntFrom('p', 1);
		$search = ForceStringFrom('s');
		$from = ForceStringFrom('f');
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
            case 'fromid.down':
				$orderby = " fromid DESC ";
				break;

            case 'fromid.up':
				$orderby = " fromid ASC ";
				break;

            case 'toid.down':
				$orderby = " toid DESC ";
				break;

            case 'toid.up':
				$orderby = " toid ASC ";
				break;

            case 'grid.down':
				$orderby = " grid DESC ";
				break;

            case 'grid.up':
				$orderby = " grid ASC ";
				break;

            case 'time.down':
				$orderby = " time DESC ";
				break;

            case 'time.up':
				$orderby = " time ASC ";
				break;

            case 'mid.up':
				$orderby = " mid ASC ";
				break;

			default:
				$orderby = " mid DESC ";			
				$order = "mid.down";
				break;
		}

		$usergroups = array();
		$getusergroups = APP::$DB->query("SELECT id, groupname FROM " . TABLE_PREFIX . "group ORDER BY id");
		while($g = APP::$DB->fetch($getusergroups)) {
			$usergroups[$g['id']] = $g['groupname'];
			$usergroup_options .= "<option value=\"$g[id]\" " . Iif($g['id'] == $groupid, 'SELECTED') . ">$g[groupname]</option>";
		}


		SubMenu('记录列表', array(array('记录列表', 'messages', 1), array('检索QQ、手机、微信访客', 'messages/filter')));

		TableHeader('搜索及快速删除');

		TableRow('<center><form method="post" action="'.BURL('messages').'" name="searchmessages" style="display:inline-block;*display:inline;"><label>关键字:</label>&nbsp;<input type="text" name="s" size="12"  value="'.$search.'">&nbsp;&nbsp;&nbsp;<label>分类:</label>&nbsp;<select name="f"><option value="0">全部</option><option value="1" ' . Iif($from == '1', 'SELECTED') . ' class=red>客人的发言</option><option value="2" ' . Iif($from == '2', 'SELECTED') . '>客服的发言</option></select>&nbsp;&nbsp;&nbsp;<label>客服组:</label>&nbsp;<select name="g"><option value="0">全部</option>' . $usergroup_options . '</select>&nbsp;&nbsp;&nbsp;<label>日期:</label>&nbsp;<input type="text" name="t" class="date-input" value="' . $time . '" size="8">&nbsp;&nbsp;&nbsp;&nbsp;<input type="submit" value="搜索记录" class="cancel"></form>

		<form method="post" action="'.BURL('messages/fastdelete').'" name="fastdelete" style="display:inline-block;margin-left:80px;*display:inline;"><label>快速删除记录:</label>&nbsp;<select name="days"><option value="0">请选择 ...</option><option value="360">12个月前的对话记录</option><option value="180">&nbsp;6 个月前的对话记录</option><option value="90">&nbsp;3 个月前的对话记录</option><option value="30">&nbsp;1 个月前的对话记录</option></select>&nbsp;&nbsp;&nbsp;&nbsp;<input type="submit" value="快速删除" class="save" onclick="var _me=$(this);showDialog(\'确定删除所选记录吗?\', \'确认操作\', function(){_me.closest(\'form\').submit();});return false;"></form></center>');
		
		TableFooter();

		if($search){
			if(preg_match("/^[1-9][0-9]*$/", $search)){
				$s = ForceInt($search);
				$searchsql = " WHERE (mid = '$s' OR fromid = '$s' OR toid = '$s') "; //按ID搜索
				$title = "搜索ID号为: <span class=note>$s</span> 的记录";
			}else{
				$searchsql = " WHERE (fromname LIKE '%$search%' OR toname LIKE '%$search%' OR msg LIKE '%$search%') ";
				$title = "搜索: <span class=note>$search</span> 的记录列表";
			}

			if($from) {
				if($from == 1 OR $from == 2){
					$searchsql .= " AND type = " . Iif($from == 1, 0, 1)." ";
					$title = "在 <span class=note>" .Iif($from == 1, '客人的发言', '客服的发言'). "</span> 中, " . $title;
				}
			}

			if($groupid) {
				$searchsql .= " AND grid = '$groupid' ";
			}

			if($time) {
				$searchsql .= " AND time >= '$start_time' AND time < '$end_time' ";
			}
		}else if($from){
			$searchsql .= " WHERE type = " . Iif($from == 1, 0, 1)." ";
			$title = "全部 <span class=note>" .Iif($from == 1, '客人的发言', '客服的发言'). "</span> 列表";

			if($groupid) {
				$searchsql .= " AND grid = '$groupid' ";
			}

			if($time) {
				$searchsql .= " AND time >= '$start_time' AND time < '$end_time' ";
			}
		}else if($groupid){
			$searchsql .= " WHERE grid = '$groupid' ";
			$title = "所属客服组: <span class=note>{$usergroups[$groupid]}</span> 的记录列表";

			if($time) {
				$searchsql .= " AND time >= '$start_time' AND time < '$end_time' ";
			}
		}else if($time){
			$searchsql .= " WHERE time >= '$start_time' AND time < '$end_time' ";
			$title = "搜索日期: <span class=note>{$time}</span> 的记录列表";
		}else{
			$searchsql = '';
			$title = '全部记录列表';
		}

		$getmessages = APP::$DB->query("SELECT * FROM " . TABLE_PREFIX . "msg ".$searchsql." ORDER BY {$orderby} LIMIT $start,$NumPerPage");

		$maxrows = APP::$DB->getOne("SELECT COUNT(mid) AS value FROM " . TABLE_PREFIX . "msg ".$searchsql);

		echo '<script type="text/javascript" src="'.SYSDIR.'public/laydate/laydate.js"></script>
		<form method="post" action="'.BURL('messages/updatemessages').'" name="messagesform">
		<input type="hidden" name="p" value="'.$page.'">
		<input type="hidden" name="s" value="'.$search.'">
		<input type="hidden" name="f" value="'.$from.'">
		<input type="hidden" name="g" value="'.$groupid.'">
		<input type="hidden" name="t" value="'.$time.'">
		<input type="hidden" name="o" value="'.$order.'">';

		TableHeader($title.'('.$maxrows['value'].'个)');

		echo '<tr class="tr0"><td class=td><a class="do-sort" for="mid">ID</a></td><td class=td><a class="do-sort" for="fromid">发送人</a></td><td class=td><a class="do-sort" for="toid">接收人</a></td><td class=td><a class="do-sort" for="grid">客服组</a></td><td class="td" width="50%">对话内容</td><td class=td><a class="do-sort" for="time">记录时间</a></td><td class="td last"><input type="checkbox" id="checkAll" for="deletemids[]"> <label for="checkAll">删除</label></td></tr>';

		if($maxrows['value'] < 1){
			TableRow('<center><BR><font class=redb>未搜索到任何记录!</font><BR><BR></center>');
		}else{
			while($msg = APP::$DB->fetch($getmessages)){
				TableRow(array($msg['mid'],

				"<a title=\"编辑\" href=\"" . Iif($msg['type'], BURL('users/edit?aid='.$msg['fromid']), BURL('guests/edit?gid='.$msg['fromid'])) . "\">$msg[fromname]</a>",

				"<a title=\"编辑\" href=\"" . Iif($msg['type'], BURL('guests/edit?gid='.$msg['toid']), BURL('users/edit?aid='.$msg['toid'])) . "\">$msg[toname]</a>",

				"<font class=grey>" . Iif(isset($usergroups[$msg['grid']]), $usergroups[$msg['grid']], '/') . "</font>",

				Iif($msg['filetype'] == '1', $this->getImage($msg['msg']), Iif($msg['filetype'] == '2', $this->getFile($msg['msg']), getSmile($msg['msg']))),

				DisplayDate($msg['time'], '', 1),

				'<input type="checkbox" name="deletemids[]" value="' . $msg['mid'] . '">'));
			}

			$totalpages = ceil($maxrows['value'] / $NumPerPage);

			if($totalpages > 1){
				TableRow(GetPageList(BURL('messages'), $totalpages, $page, 10, array('s'=>urlencode($search), 'f'=>$from, 'g'=>$groupid, 't'=>$time, 'o'=>$order)));
			}

		}

		TableFooter();

		PrintSubmit('删除记录', '', 1, '确定删除所选记录吗?');

		//JS排序等
		echo '<script type="text/javascript">
			$(function(){
				var url = "' . BURL("messages") . FormatUrlParam(array('p'=>$page, 's'=>urlencode($search), 'f'=>$from, 'g'=>$groupid, 't'=>$time)) . '";

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


	public function filter(){
		$NumPerPage = 20;
		$page = ForceIntFrom('p', 1);
		$groupid = ForceIntFrom('g');
		$time = ForceStringFrom('t');

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
            case 'fromid.down':
				$orderby = " fromid DESC ";
				break;

            case 'fromid.up':
				$orderby = " fromid ASC ";
				break;

            case 'toid.down':
				$orderby = " toid DESC ";
				break;

            case 'toid.up':
				$orderby = " toid ASC ";
				break;

            case 'grid.down':
				$orderby = " grid DESC ";
				break;

            case 'grid.up':
				$orderby = " grid ASC ";
				break;

            case 'time.down':
				$orderby = " time DESC ";
				break;

            case 'time.up':
				$orderby = " time ASC ";
				break;

            case 'mid.up':
				$orderby = " mid ASC ";
				break;

			default:
				$orderby = " mid DESC ";			
				$order = "mid.down";
				break;
		}

		$usergroups = array();
		$getusergroups = APP::$DB->query("SELECT id, groupname FROM " . TABLE_PREFIX . "group ORDER BY id");
		while($g = APP::$DB->fetch($getusergroups)) {
			$usergroups[$g['id']] = $g['groupname'];
			$usergroup_options .= "<option value=\"$g[id]\" " . Iif($g['id'] == $groupid, 'SELECTED') . ">$g[groupname]</option>";
		}


		SubMenu('记录列表', array(array('记录列表', 'messages'), array('检索QQ、手机、微信访客', 'messages/filter', 1)));

		TableHeader('搜索对话内容中含有QQ、手机、微信的客人');

		TableRow('<center><form method="post" action="'.BURL('messages/filter').'" name="searchmessages" style="display:inline-block;*display:inline;"><label>客服组:</label>&nbsp;<select name="g"><option value="0">全部</option>' . $usergroup_options . '</select>&nbsp;&nbsp;&nbsp;<label>日期:</label>&nbsp;<input type="text" name="t" class="date-input" value="' . $time . '" size="8">&nbsp;&nbsp;&nbsp;&nbsp;<input type="submit" value="搜索客人" class="cancel"></form></center>');
		
		TableFooter();

		$searchsql = " WHERE type =0 AND filetype <> 1 AND filetype <> 2 AND (msg REGEXP('[1-9][0-9]{4,}') OR msg REGEXP('[a-zA-Z]([-_a-zA-Z0-9]{5,19})') OR msg REGEXP('[1][2,3,4,5,6,7,8,9][0-9]{9}')) ";
		$title = '搜索到的客人列表';

		if($groupid){
			$searchsql .= " AND grid = '$groupid' ";
			$title = "所属客服组: <span class=note>{$usergroups[$groupid]}</span> 的客人列表";

			if($time) {
				$searchsql .= " AND time >= '$start_time' AND time < '$end_time' ";
			}
		}else if($time){
			$searchsql .= " AND time >= '$start_time' AND time < '$end_time' ";
			$title = "搜索日期: <span class=note>{$time}</span> 的客人列表";
		}

		$getmessages = APP::$DB->query("SELECT * FROM " . TABLE_PREFIX . "msg ".$searchsql." ORDER BY {$orderby} LIMIT $start,$NumPerPage");

		$maxrows = APP::$DB->getOne("SELECT COUNT(mid) AS value FROM " . TABLE_PREFIX . "msg ".$searchsql);

		echo '<script type="text/javascript" src="'.SYSDIR.'public/laydate/laydate.js"></script>';

		TableHeader($title.'('.$maxrows['value'].'个)');

		echo '<tr class="tr0"><td class=td><a class="do-sort" for="mid">ID</a></td><td class=td><a class="do-sort" for="fromid">客人</a></td><td class=td><a class="do-sort" for="toid">客服</a></td><td class=td><a class="do-sort" for="grid">客服组</a></td><td class="td" width="50%">可能含有QQ、手机、微信的对话内容</td><td class=td last><a class="do-sort" for="time">记录时间</a></td></tr>';

		if($maxrows['value'] < 1){
			TableRow('<center><BR><font class=redb>未搜索到任何客人!</font><BR><BR></center>');
		}else{
			while($msg = APP::$DB->fetch($getmessages)){
				$info = preg_replace("/[1-9][0-9]{4,}|[a-zA-Z]([-_a-zA-Z0-9]{5,19})|[1][2,3,4,5,6,7,8,9][0-9]{9}/", '<font color=red>${0}</font>', $msg['msg']);

				TableRow(array($msg['mid'],

				"<a title=\"编辑\" href=\"" . BURL('guests/edit?gid='.$msg['fromid']) . "\">$msg[fromname]</a>",

				"<a title=\"编辑\" href=\"" . BURL('users/edit?aid='.$msg['toid']) . "\">$msg[toname]</a>",

				"<font class=grey>" . Iif(isset($usergroups[$msg['grid']]), $usergroups[$msg['grid']], '/') . "</font>",

				getSmile($info),

				DisplayDate($msg['time'], '', 1)));
			}

			$totalpages = ceil($maxrows['value'] / $NumPerPage);

			if($totalpages > 1){
				TableRow(GetPageList(BURL('messages/filter'), $totalpages, $page, 10, array('g'=>$groupid, 't'=>$time, 'o'=>$order)));
			}

		}

		TableFooter();

		//JS排序等
		echo '<script type="text/javascript">
			$(function(){
				var url = "' . BURL("messages/filter") . FormatUrlParam(array('p'=>$page, 'g'=>$groupid, 't'=>$time)) . '";

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


	//解析图片
	private function getImage($msg){
		if(!$msg) return '';

		$img = explode('|', $msg);

		$img_src = $img[0];
		$img_w = $img[1];
		$img_h = $img[2];

		$show_h = 80;

		if(!$img_h) $img_h = 1;

		if(($img_w / $img_h) > 1){
			if($img_w < 1) $img_w = 1;
			$show_h = ForceInt($img_h * 80 / $img_w);
		}

		return '<img src="' . SYSDIR . 'upload/img/' . $img_src . '"  class="img_upload" style="height:' . $show_h . 'px;"  onclick="show_big_img(this, ' . $img_w . ', ' . $img_h . ');">';
	}

	//解析文件下载
	private function getFile($msg){
		if(!$msg) return '';

		$file = explode('|', $msg);

		return '<a href="' . SYSDIR . 'upload/file/' . $file[0] . '" target="_blank" download="' . $file[1] . '" class="down"><img src="' . SYSDIR . 'public/img/save.png">&nbsp;&nbsp;点击下载: ' . $file[1] . '</a>';
	}

} 

?>