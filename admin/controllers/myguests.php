<?php if(!defined('ROOT')) die('Access denied.');

class c_myguests extends Admin{

	public function __construct($path){
		parent::__construct($path);

		$this->groupid = $this->admin['grid']; //组id
		$this->isTeamer = $this->checkTeamAccess(); //是否为组长
	}


	//保存
	public function save(){

		$gid          = ForceIntFrom('gid');
		$groupid = $this->groupid;
		$myid = $this->admin['aid'];

		$email           = ForceStringFrom('email');
		$fullname        = ForceStringFrom('fullname');
		$phone        = ForceStringFrom('phone');
		$address        = ForceStringFrom('address');
		$remark        = ForceStringFrom('remark');

		if($email AND !IsEmail($email)) Error('Email地址不规范', '编辑客人错误');

		APP::$DB->exe("UPDATE " . TABLE_PREFIX . "guest SET fullname    = '$fullname',
		address       = '$address',
		phone       = '$phone',
		email       = '$email',
		remark       = '$remark'
		WHERE gid   = '$gid' AND grid = '$groupid' " . Iif(!$this->isTeamer, " AND aid = '$myid' "));

		Success('myguests');
	}


	//编辑调用add
	public function edit(){
		$gid = ForceIntFrom('gid');
		$groupid = $this->groupid;
		$myid = $this->admin['aid'];

		SubMenu('编辑客人', array(array('客人列表', 'myguests'), array('编辑客人', 'myguests/edit?gid='.$gid, 1)));
		
		$user = APP::$DB->getOne("SELECT g.*, gr.groupname FROM " . TABLE_PREFIX . "guest g LEFT JOIN " . TABLE_PREFIX . "group gr ON gr.id = g.grid WHERE g.gid = '$gid' AND g.grid = '$groupid' " . Iif(!$this->isTeamer, " AND g.aid = '$myid' "));

		if(!$user) Error('您正在尝试编辑的客人不存在!', '编辑客人错误');

		echo '<form method="post" action="'.BURL('myguests/save').'">
		<input type="hidden" name="gid" value="' . $gid . '">';

		TableHeader('编辑客人信息: <span class=note>' . Iif($user['fullname'], $user['fullname'], Iif($user['lang'], "无名 (ID: $gid)", "None (ID: $gid)")) . '</span>');

		TableRow(array('<b>姓名:</b>', '<input type="text" name="fullname" value="'.$user['fullname'].'" size="20">'));
		TableRow(array('<b>用户ID:</b>', "WeLive访客ID：{$gid}&nbsp;&nbsp;&nbsp;&nbsp;原网站用户ID(Oid)：" . Iif($user['oid'], $user['oid'], "-")));
		TableRow(array('<b>客服组:</b>', $user['groupname']));
		TableRow(array('<b>来自页面</b>', "<a href=\"$user[fromurl]\" target=\"_blank\">$user[fromurl]</a>&nbsp;&nbsp;浏览器: $user[browser]" . Iif($user['mobile'], ' <img src="' . SYSDIR . 'public/img/mobile.png" style="height:20px;">')));
		TableRow(array('<b>访客信息:</b>', '<font class=light>意向分: '. $user['grade'] . "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;登录: $user[logins]&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;踢出: $user[kickouts]&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;上传授权: " . Iif($user['upload'], '有', '-') . "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;语言: " . Iif($user['lang'], '中文', 'English') . "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;归属地: $user[ipzone]($user[lastip])</font>"));
		TableRow(array('<b>Email:</b>', '<input type="text" name="email" value="'.$user['email'].'" size="40">'));
		TableRow(array('<b>电话:</b>', '<input type="text" name="phone" value="'.$user['phone'].'" size="40">'));
		TableRow(array('<b>地址:</b>', '<input type="text" name="address" value="'.$user['address'].'" size="40">'));
		TableRow(array('<b>备注:</b>', '<textarea name="remark" style="height:80px;width:400px;">' . $user['remark'] . '</textarea>'));

		TableFooter();

		PrintSubmit('保存更新');
	}

	public function index(){
		$NumPerPage = 20;
		$page = ForceIntFrom('p', 1);
		$letter = ForceStringFrom('k');
		$search = ForceStringFrom('s');
		$from = ForceStringFrom('f');
		$time = ForceStringFrom('t');

		$groupid = $this->groupid;
		$myid = $this->admin['aid'];

		if(IsGet('s')){
			$search = urldecode($search);
		}

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
            case 'gid.down':
				$orderby = " gid DESC ";
				break;

            case 'gid.up':
				$orderby = " gid ASC ";
				break;

            case 'oid.down':
				$orderby = " oid DESC ";
				break;

            case 'oid.up':
				$orderby = " oid ASC ";
				break;

            case 'logins.down':
				$orderby = " logins DESC ";
				break;

            case 'logins.up':
				$orderby = " logins ASC ";
				break;

            case 'upload.down':
				$orderby = " upload DESC ";
				break;

            case 'upload.up':
				$orderby = " upload ASC ";
				break;

            case 'phone.down':
				$orderby = " phone DESC ";
				break;

            case 'phone.up':
				$orderby = " phone ASC ";
				break;

            case 'last.up':
				$orderby = " last ASC ";
				break;

			default:
				$orderby = " last DESC ";			
				$order = "last.down";
				break;
		}

		$admins = array();
		$getadmins = APP::$DB->query("SELECT aid, fullname FROM " . TABLE_PREFIX . "admin WHERE grid='$groupid' ");
		while($a = APP::$DB->fetch($getadmins)){
			$admins[$a['aid']] = $a['fullname'];
		}
		$admins[888888] = "<font class=grey>机器人</font>"; //机器人

		SubMenu('客人列表', array(array('客人列表', 'myguests', 1)));

		TableHeader('快速查找客人');
		for($alphabet = 'a'; $alphabet != 'aa'; $alphabet++){
			$alphabetlinks .= '<a href="'.BURL('myguests?k=' . $alphabet) . '" title="' . strtoupper($alphabet) . '开头的客人" class="link_alphabet">' . strtoupper($alphabet) . '</a> &nbsp;';
		}

		TableRow('<center><b><a href="'.BURL('myguests').'" class="link_alphabet">全部客人</a>&nbsp;&nbsp;&nbsp;<a href="'.BURL('myguests?k=Other').'"  class="link_alphabet">中文名</a>&nbsp;&nbsp;&nbsp;' . $alphabetlinks . '</b></center>');
		TableFooter();

		TableHeader('搜索客人');
		TableRow('<center><form method="post" action="'.BURL('myguests').'" name="searchguests" style="display:inline-block;*display:inline;"><label>关键字:</label>&nbsp;<input type="text" name="s" size="12"  value="'.$search.'">&nbsp;&nbsp;&nbsp;&nbsp;<label>来源或意向:</label>&nbsp;<select name="f"><option value="0">全部</option><option value="cn" ' . Iif($from == 'cn', 'SELECTED') . ' class=blue>中文 (语言)</option><option value="en" ' . Iif($from == 'en', 'SELECTED') . ' class=red>EN (语言)</option><option value="5" ' . Iif($from == '5', 'SELECTED') . '>5分 (意向)</option><option value="4" ' . Iif($from == '4', 'SELECTED') . '>4分 (意向)</option><option value="3" ' . Iif($from == '3', 'SELECTED') . '>3分 (意向)</option><option value="2" ' . Iif($from == '2', 'SELECTED') . '>2分 (意向)</option><option value="1" ' . Iif($from == '1', 'SELECTED') . '>1分 (意向)</option><option value="web" ' . Iif($from == 'web', 'SELECTED') . ' class=blue>Web端</option><option value="mobile" ' . Iif($from == 'mobile', 'SELECTED') . ' class=red>移动端</option></select>&nbsp;&nbsp;&nbsp;&nbsp;<label>日期:</label>&nbsp;<input type="text" name="t" class="date-input" value="' . $time . '" size="8">&nbsp;&nbsp;&nbsp;&nbsp;<input type="submit" value="搜索客人" class="cancel"></form>');

		TableFooter();

		if($letter){
			if($letter == 'Other'){
				$searchsql = " WHERE (fullname <> '' AND fullname NOT REGEXP(\"^[a-zA-Z]\")) ";
				$title = '<span class=note>中文姓名</span> 的客人列表';
			}else{
				$searchsql = " WHERE (fullname LIKE '$letter%') ";
				$title = '<span class=note>'.strtoupper($letter) . '</span> 字母开头的客人列表';
			}
		}else if($search){
			if(preg_match("/^[1-9][0-9]*$/", $search)){
				$s = ForceInt($search);
				$searchsql = " WHERE (gid = '$s' OR aid = '$s' OR oid = '$s' OR phone LIKE '$s') "; //按ID搜索
				$title = "搜索数字为: <span class=note>$s</span> 的客人";
			}else{
				$searchsql = " WHERE (fullname LIKE '%$search%' OR browser LIKE '%$search%' OR fromurl LIKE '%$search%' OR ipzone LIKE '%$search%' OR remark LIKE '%$search%' OR lastip LIKE '%$search%') ";
				$title = "搜索: <span class=note>$search</span> 的客人列表";
			}

			if($from) {
				if($from == 'cn' OR $from == 'en'){
					$searchsql .= " AND lang = " . Iif($from == 'cn', 1, 0)." ";
					$title = "在 <span class=note>" .Iif($from == 'cn', '中文客人', '英文客人'). "</span> 中, " . $title;
				}elseif($from == 'mobile' OR $from == 'web'){
					$searchsql .= " AND mobile = " . Iif($from == 'mobile', 1, 0)." ";
					$title = "在 <span class=note>" .Iif($from == 'mobile', '来自移动端的客人', '来自Web端的客人'). "</span> 中, " . $title;
				}else{
					$searchsql .= " AND grade = '$from' ";
					$title = "在 <span class=note>意向为: ".$from."分</span> 中, " . $title;
				}
			}

			if($time) {
				$searchsql .= " AND last >= '$start_time' AND last < '$end_time' ";
			}

		}else if($from){
			if($from == 'cn' OR $from == 'en'){
				$searchsql .= " WHERE lang = " . Iif($from == 'cn', 1, 0)." ";
				$title = "全部 <span class=note>" .Iif($from == 'cn', '中文客人', '英文客人'). "</span> 列表";
			}elseif($from == 'mobile' OR $from == 'web'){
				$searchsql .= " WHERE mobile = " . Iif($from == 'mobile', 1, 0)." ";
				$title = "全部 <span class=note>" .Iif($from == 'mobile', '来自移动端的客人', '来自Web端的客人'). "</span> 列表";
			}else{
				$searchsql .= " WHERE grade = '$from' ";
				$title = "<span class=note>意向为: ".$from." 分</span> 的客人列表";
			}

			if($time) {
				$searchsql .= " AND last >= '$start_time' AND last < '$end_time' ";
			}

		}else if($time){
			$searchsql .= " WHERE last >= '$start_time' AND last < '$end_time' ";
			$title = "搜索日期: <span class=note>{$time}</span> 的客人列表";

		}else{
			$searchsql = " WHERE 1=1 ";
			if($this->isTeamer){
				$title = '本组全部客人列表';
			}else{
				$title = '我的全部客人列表';
			}
		}

		$searchsql .= " AND grid = '$groupid' "; //限定为本组

		//如果不是组长, 只能看自己的客人
		if(!$this->isTeamer){
			$title = '我的全部客人列表';
			$searchsql .= " AND aid = '$myid' ";
		}


		$getguests = APP::$DB->query("SELECT * FROM " . TABLE_PREFIX . "guest ".$searchsql." ORDER BY {$orderby} LIMIT $start, $NumPerPage");

		$maxrows = APP::$DB->getOne("SELECT COUNT(gid) AS value FROM " . TABLE_PREFIX . "guest ".$searchsql);

		echo '<script type="text/javascript" src="'.SYSDIR.'public/laydate/laydate.js"></script>';

		TableHeader($title.'('.$maxrows['value'].'个)');
		TableRow(array('<a class="do-sort" for="gid">ID</a>', '姓名', '<a class="do-sort" for="oid">Oid</a>', '意向分', '语言', '<a class="do-sort" for="logins">登录</a>', '踢出 (次)', '最后服务', '<a class="do-sort" for="upload">上传授权</a>', '浏览器', '来自页面', 'Email', '<a class="do-sort" for="phone">电话</a>', '地址', '备注', '归属地 (IP)', '<a class="do-sort" for="last">最后登陆</a>'), 'tr0');

		if($maxrows['value'] < 1){
			TableRow('<center><BR><font class=redb>未搜索到任何客人!</font><BR><BR></center>');
		}else{
			while($user = APP::$DB->fetch($getguests)){
				TableRow(array($user['gid'],
				'<a title="编辑" href="'.BURL('myguests/edit?gid='.$user['gid']).'"><b>' . Iif($user['fullname'], $user['fullname'], '<font class=grey>' . Iif($user['lang'], '无名', 'None') . '</font>') . '</b></a>',
				Iif($user['oid'], $user['oid'], "-"),
				$user['grade'],
				Iif($user['lang'], '中文', 'EN'),
				$user['logins'],
				$user['kickouts'],
				Iif(isset($admins[$user['aid']]), $admins[$user['aid']], '<font class=grey>未知</font>'),
				Iif($user['upload'], '<font class=blue>有</font>'),
				$user['browser'] . Iif($user['mobile'], ' <img src="' . SYSDIR . 'public/img/mobile.png" style="height:20px;">'),
				"<a href=\"$user[fromurl]\" target=\"_blank\">" . ShortTitle($user['fromurl'], 36) . "</a>",
				Iif($user['email'], '<a href="mailto:' . $user['email'] . '">' . $user['email'] . '</a>'),
				$user['phone'],
				$user['address'],
				ShortTitle($user['remark'], 48),
				$user['ipzone'] . " (<a href=\"https://www.baidu.com/s?wd=$user[lastip]\" target=\"_blank\">$user[lastip]</a>)",
				DisplayDate($user['last'], '', 1)));
			}

			$totalpages = ceil($maxrows['value'] / $NumPerPage);

			if($totalpages > 1){
				TableRow(GetPageList(BURL('myguests'), $totalpages, $page, 10, array('k'=>$letter, 's'=>urlencode($search), 'f'=>$from, 't'=>$time, 'o'=>$order)));
			}

		}

		TableFooter();


		//JS排序等
		echo '<script type="text/javascript">
			$(function(){
				var url = "' . BURL("myguests") . FormatUrlParam(array('p'=>$page, 'k'=>$letter, 's'=>urlencode($search), 'f'=>$from, 't'=>$time)) . '";

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

} 

?>