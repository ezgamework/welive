<?php if(!defined('ROOT')) die('Access denied.');

class c_users extends Admin{

	public function __construct($path){
		parent::__construct($path);

		$this->CheckAction(); //权限验证
	}

	//按用户ID删除用户
	private function DeleteUser($aid){
		if(!$aid) return;

		//初始管理员及在线客服无法删除
		$re = APP::$DB->exe("DELETE FROM " . TABLE_PREFIX . "admin WHERE aid = '$aid' AND aid != 1 And online != 1");

		//删除常用短语
		if($re) APP::$DB->exe("DELETE FROM " . TABLE_PREFIX . "phrase WHERE aid = '$aid'");

		//删除头像
		@unlink(ROOT . "avatar/$aid.jpg");
	}


	//保存
	public function save(){
		$aid          = ForceIntFrom('aid');

		$type     = ForceIntFrom('type');
		$grid     = ForceIntFrom('grid');
		$activated       = ForceIntFrom('activated');
		$username        = ForceStringFrom('username');
		$password        = ForceStringFrom('password');
		$passwordconfirm = ForceStringFrom('passwordconfirm');

		$email           = ForceStringFrom('email');
		$fullname        = ForceStringFrom('fullname');
		$fullname_en        = ForceStringFrom('fullname_en');
		$post        = ForceStringFrom('post');
		$post_en        = ForceStringFrom('post_en');

		$deleteuser       = ForceIntFrom('deleteuser');

		if($deleteuser AND $aid != $this->admin['aid']){
			$this->DeleteUser($aid);
			Success('users'); //如果删除客服, 直接跳转
		}

		if(!$username){
			$errors[] = '请输入用户名!';
		}elseif(!IsName($username)){
			$errors[] = '用户名存在非法字符!';
		}elseif(APP::$DB->getOne("SELECT aid FROM " . TABLE_PREFIX . "admin WHERE username = '$username' AND aid != '$aid'")){
			$errors[] = '用户名已存在!';
		}

		if($aid){
			if(strlen($password) OR strlen($passwordconfirm)){
				if(strcmp($password, $passwordconfirm)){
					$errors[] = '两次输入的密码不相同!';
				}
			}
		}else{
			if(!$password){
				$errors[] = '请输入密码!';
			}elseif($password != $passwordconfirm){
				$errors[] = '两次输入的密码不相同!';
			}
		}

		if(!$email){
			$errors[] = '请输入Email地址!';
		}elseif(!IsEmail($email)){
			$errors[] = 'Email地址不规范!';
		}elseif(APP::$DB->getOne("SELECT aid FROM " . TABLE_PREFIX . "admin WHERE email = '$email' AND aid != '$aid'")){
			$errors[] = 'Email地址已占用!';
		}

		if(!$fullname) $errors[] = '请输入中文昵称!';
		if(!$fullname_en) $errors[] = '请输入英文昵称!';
		if(!$post) $errors[] = '请输入中文职位!';
		if(!$post_en) $errors[] = '请输入英文职位!';

		if(isset($errors)){
			Error($errors, Iif($aid, '编辑客服错误', '添加客服错误'));
		}else{
			if($aid){
				APP::$DB->exe("UPDATE " . TABLE_PREFIX . "admin SET username    = '$username',
				".Iif($aid != $this->admin['aid'], "type = '$type', activated = '$activated',")."
				grid = '$grid',
				".Iif($password, "password = '" . md5($password) . "',")."
				email       = '$email',
				fullname       = '$fullname',
				fullname_en       = '$fullname_en',
				post       = '$post',
				post_en       = '$post_en'										 
				WHERE aid      = '$aid'");

			}else{
				APP::$DB->exe("INSERT INTO " . TABLE_PREFIX . "admin (type, grid, activated, username, password, email, first, fullname, fullname_en, post, post_en) VALUES ('$type', '$grid', 1, '$username', '".md5($password)."', '$email', '".time()."', '$fullname', '$fullname_en', '$post', '$post_en')");
			}

			Success('users');
		}
	}

	//批量更新客服
	public function updateusers(){
		$page = ForceIntFrom('p', 1);   //页码
		$search = ForceStringFrom('s');
		$groupid = ForceIntFrom('g');
		$time = ForceStringFrom('t');
		$order = ForceStringFrom('o');

		$deleteaids = Iif(isset($_POST['deleteaids']), $_POST['deleteaids'], array());

		for($i = 0; $i < count($deleteaids); $i++){
			$aid = ForceInt($deleteaids[$i]);
			if($aid != $this->admin['aid']){
				$this->DeleteUser($aid);
			}
		}

		Success('users?p=' . $page. FormatUrlParam(array('s'=>urlencode($search), 'g'=>$groupid, 't'=>$time, 'o'=>$order)));
	}

	//编辑调用add
	public function edit(){
		$this->add();
	}

	//添加页面
	public function add(){
		$aid = ForceIntFrom('aid');

		if($aid){
			SubMenu('客服管理', array(array('客服列表', 'users'), array('添加客服', 'users/add'), array('编辑客服', 'users/edit?aid='.$aid, 1), array('客服组管理', 'usergroup')));
			
			$user = APP::$DB->getOne("SELECT * FROM " . TABLE_PREFIX . "admin WHERE aid = '$aid'");

			if(!$user) Error('您正在尝试编辑的客服不存在!', '编辑客服错误');
		}else{
			SubMenu('客服管理', array(array('客服列表', 'users'), array('添加客服', 'users/add', 1), array('客服组管理', 'usergroup')));

			$user = array('aid' => 0, 'type' => 0, 'grid' => 1, 'activated' => 1);
		}

		$getusergroups = APP::$DB->query("SELECT id, activated, groupname, groupname_en FROM " . TABLE_PREFIX . "group ORDER BY id");
		$usergroupselect = '<select name="grid">';
		while($g = APP::$DB->fetch($getusergroups)) {
			$usergroupselect .= '<option ' . Iif(!$g['activated'], ' class="red"') . ' value="' . $g['id'] . '" ' . Iif($user['grid'] == $g['id'], ' SELECTED') . '>' . "$g[groupname] ($g[groupname_en])</option>";
		}
		$usergroupselect .= '</select>';

		$need_info = '&nbsp;&nbsp;<font class=red>*</font>';
		$pass_info = Iif($aid, '&nbsp;&nbsp;<font class=grey>不修改请留空</font>', $need_info);

		echo '<form method="post" action="'.BURL('users/save').'">
		<input type="hidden" name="aid" value="' . $user['aid'] . '">';

		if($aid){
			TableHeader('编辑客服信息: <span class=note>' . $user['username'] . '</span>');
		}else{
			TableHeader('填写客服信息');
		}

		TableRow(array('<b>用户名:</b>', '<input type="text" name="username" value="'.$user['username'].'" size="20">' .$need_info . Iif($aid, "<font class=light><img src='" . GetAvatar($user['aid']) . "' class='avatar wh30' style='margin-left:60px'>")));

		$Radio = new Radio;
		$Radio->Name = 'type';
		$Radio->SelectedID = $user['type'];
		$Radio->AddOption(1, '<font class=redb>管理员</font>', '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;');
		$Radio->AddOption(2, '<font class=blueb>组长</font>', '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;');
		$Radio->AddOption(0, '<b>客服</b>', '&nbsp;&nbsp;');

		if($aid != $this->admin['aid']){
			TableRow(array('<b>类型:</b>', $Radio->Get()));
			TableRow(array('<b>客服组:</b>', $usergroupselect));

			if($aid){
				$Radio ->Clear();
				$Radio->Name = 'activated';
				$Radio->SelectedID = $user['activated'];
				$Radio->AddOption(1, '正常', '&nbsp;&nbsp;&nbsp;&nbsp;');
				$Radio->AddOption(0, '禁止', '&nbsp;&nbsp;&nbsp;&nbsp;');

				TableRow(array('<b>账号:</b>', $Radio->Get()));

				if($user['aid'] != $this->admin['aid'] && $user['aid'] != 1 && $user['online'] != 1){
					TableRow(array('<b>删除此客服?</b>', '<input type="checkbox" name="deleteuser" value="1">&nbsp;<font class=redb>慎选!</font>'));
				}

			}
		}else{
			$Radio->Attributes = 'disabled';
			TableRow(array('<b>类型:</b>', $Radio->Get()));
			TableRow(array('<b>客服组:</b>', $usergroupselect));
		}

		TableRow(array('<b>密码:</b>', '<input type="text" name="password" size="20">'.$pass_info));
		TableRow(array('<b>确认密码:</b>', '<input type="text" name="passwordconfirm" size="20">'.$pass_info));
		TableRow(array('<b>Email地址:</b>', '<input type="text" name="email" value="'.$user['email'].'" size="20">'.$need_info));

		TableRow(array('<b>昵称 (<font class=blue>中文</font>):</b>', '<input type="text" name="fullname" value="'.$user['fullname'].'" size="20">'.$need_info));
		TableRow(array('<b>昵称 (<font class=red>英文</font>):</b>', '<input type="text" name="fullname_en" value="'.$user['fullname_en'].'" size="20">'.$need_info));
		TableRow(array('<b>职位 (<font class=blue>中文</font>):</b>', '<input type="text" name="post" value="'.$user['post'].'" size="20">'.$need_info));
		TableRow(array('<b>职位 (<font class=red>英文</font>):</b>', '<input type="text" name="post_en" value="'.$user['post_en'].'" size="20">'.$need_info));

		TableFooter();

		PrintSubmit(Iif($aid, '保存更新', '添加客服'));
	}

	public function index(){

		$NumPerPage = 10;
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
            case 'online.down':
				$orderby = " online DESC ";
				break;

            case 'online.up':
				$orderby = " online ASC ";
				break;

            case 'guests.down':
				$orderby = " guests DESC ";
				break;

            case 'guests.up':
				$orderby = " guests ASC ";
				break;

            case 'avg_score.down':
				$orderby = " avg_score DESC ";
				break;

            case 'avg_score.up':
				$orderby = " avg_score ASC ";
				break;

            case 'username.down':
				$orderby = " username DESC ";
				break;

            case 'username.up':
				$orderby = " username ASC ";
				break;

            case 'type.down':
				$orderby = " type DESC ";
				break;

            case 'type.up':
				$orderby = " type ASC ";
				break;

            case 'activated.down':
				$orderby = " activated DESC ";
				break;

            case 'activated.up':
				$orderby = " activated ASC ";
				break;

            case 'grid.down':
				$orderby = " grid DESC ";
				break;

            case 'grid.up':
				$orderby = " grid ASC ";
				break;

            case 'logins.down':
				$orderby = " logins DESC ";
				break;

            case 'logins.up':
				$orderby = " logins ASC ";
				break;

            case 'first.down':
				$orderby = " first DESC ";
				break;

            case 'first.up':
				$orderby = " first ASC ";
				break;

            case 'last.down':
				$orderby = " last DESC ";
				break;

            case 'last.up':
				$orderby = " last ASC ";
				break;

            case 'aid.up':
				$orderby = " aid ASC ";
				break;

			default:
				$orderby = " grid DESC, activated DESC, aid DESC ";			
				$order = "aid.down";
				break;
		}


		$usergroups = array();

		$getusergroups = APP::$DB->query("SELECT id, activated, groupname FROM " . TABLE_PREFIX . "group ORDER BY id");
		while($g = APP::$DB->fetch($getusergroups)) {
			$usergroups[$g['id']] = array('groupname' => $g['groupname'], 'activated' => $g['activated']);
			$usergroup_options .= "<option value=\"$g[id]\" " . Iif($g['id'] == $groupid, 'SELECTED') . ">$g[groupname]</option>";
		}

		SubMenu('客服管理', array(array('客服列表', 'users', 1), array('添加客服', 'users/add'), array('客服组管理', 'usergroup')));

		TableHeader('搜索客服');

		TableRow('<center><form method="post" action="'.BURL('users').'" name="searchrobot" style="display:inline-block;"><label>关键字:</label>&nbsp;<input type="text" name="s" size="14" value="'.$search.'">&nbsp;&nbsp;&nbsp;<label>客服组:</label>&nbsp;<select name="g"><option value="0">全部</option>' . $usergroup_options . '</select>&nbsp;&nbsp;&nbsp;<label>日期:</label>&nbsp;<input type="text" name="t" class="date-input" value="' . $time . '" size="8">&nbsp;&nbsp;&nbsp;&nbsp;<input type="submit" value="搜索客服" class="cancel"></form></center>');
		
		TableFooter();


		if($search){
			$searchsql = " WHERE (a.username LIKE '%$search%' OR a.email LIKE '%$search%' OR a.fullname LIKE '%$search%' OR a.fullname_en LIKE '%$search%' OR a.post LIKE '%$search%' OR a.post_en LIKE '%$search%') ";
			$title = "搜索: <span class=note>$search</span> 的客服列表";

			if($groupid) {
				$searchsql .= " AND a.grid = '$groupid' ";
			}

			if($time) {
				$searchsql .= " AND ((last >= '$start_time' AND last < '$end_time') OR (first >= '$start_time' AND first < '$end_time')) ";
			}

		}elseif($groupid){
			$searchsql .= " WHERE a.grid = '$groupid' ";

			if($time) {
				$searchsql .= " AND ((last >= '$start_time' AND last < '$end_time') OR (first >= '$start_time' AND first < '$end_time')) ";
			}

			$title = "所属客服组: <span class=note>" . $usergroups[$groupid]['groupname'] . "</span> 的客服列表";

		}else if($time){

			$searchsql .= " WHERE (last >= '$start_time' AND last < '$end_time') OR (first >= '$start_time' AND first < '$end_time') ";
			$title = "搜索日期: <span class=note>{$time}</span> 的客服列表";

		}else{
			$searchsql = '';
			$title = '全部客服列表';
		}

		$getusers = APP::$DB->query("SELECT a.*, ROUND(AVG(r.score), 2) AS avg_score, COUNT(r.rid) AS scores, 
													(select COUNT(gid)  FROM " . TABLE_PREFIX . "guest WHERE aid = a.aid) AS guests 		
													FROM " . TABLE_PREFIX . "admin a 
													LEFT JOIN " . TABLE_PREFIX . "rating r ON r.aid = a.aid
													" . $searchsql . "
													GROUP BY a.aid ORDER BY {$orderby} LIMIT $start, $NumPerPage");


		$maxrows = APP::$DB->getOne("SELECT COUNT(aid) AS value FROM " . TABLE_PREFIX . "admin a " . $searchsql);

		echo '<script type="text/javascript" src="'.SYSDIR.'public/laydate/laydate.js"></script>
		<form method="post" action="'.BURL('users/updateusers').'" name="usersform">
		<input type="hidden" name="p" value="'.$page.'">
		<input type="hidden" name="g" value="'.$groupid.'">
		<input type="hidden" name="s" value="'.$search.'">
		<input type="hidden" name="t" value="'.$time.'">
		<input type="hidden" name="o" value="'.$order.'">';

		TableHeader($title.'('.$maxrows['value'].'个)');

		TableRow(array('<a class="do-sort" for="aid">ID</a>', '<a class="do-sort" for="username">用户名</a>', '<a class="do-sort" for="online">服务状态</a>', '<a class="do-sort" for="guests">访客人数</a>', '<a class="do-sort" for="avg_score">平均得分<br>(评价次数)</a>', '<a class="do-sort" for="type">类型</a>', '<a class="do-sort" for="grid">客服组</a>', '<a class="do-sort" for="activated">账号</a>', '<a class="do-sort" for="logins">登录</a>', 'Email', '昵称 (中)', '职位 (中)', '昵称 (EN)', '职位 (EN)', '<a class="do-sort" for="first">注册日期</a>', '<a class="do-sort" for="last">最后登陆 (IP)</a>', '<input type="checkbox" id="checkAll" for="deleteaids[]"> <label for="checkAll">删除</label>'), 'tr0');

		while($user = APP::$DB->fetch($getusers)){

			TableRow(array($user['aid'],

			'<a title="编辑" href="'.BURL('users/edit?aid='.$user['aid']).'"><img src="' . GetAvatar($user['aid']) . '" class="avatar wh30"><b>' . Iif($user['activated'] == 1, $user['username'], "<font class=red><s>$user[username]</s></font>") . '</b></a>',

			Iif($user['online'], '<font class=blue>服务中...</font>', '<font class=grey>离席</font>'),

			$user['guests'],

			Iif($user['avg_score'], $user['avg_score'] . " (" .  $user['scores'] .  ")", "-"),

			Iif($user['type'] == 1, '<font class=red>管理员</font>', Iif($user['type'] == 2, '<font class=blue>组长</font>', '客服')),

			Iif($usergroups[$user['grid']]['activated'], "<font class=grey>" . $usergroups[$user['grid']]['groupname'] . "</font>", '<s class=red>' . $usergroups[$user['grid']]['groupname'] . '</s>'),

			Iif($user['activated'], '正常', '<font class=red>已禁止</font>'),

			$user['logins'],

			Iif($user['aid'] == $this->admin['aid'], $user['email'], '<a href="mailto:' . $user['email'] . '">' . $user['email'] . '</a>'),

			$user['fullname'],
			$user['post'],
			$user['fullname_en'],
			$user['post_en'],
			DisplayDate($user['first']),

			Iif($user['last'] == 0, '<span class="red">从未登陆</span>', DisplayDate($user['last'], '', 1)  . " ($user[lastip])"),

			Iif($user['aid'] != $this->admin['aid'] && $user['aid'] != 1 && $user['online'] != 1, '<input type="checkbox" name="deleteaids[]" value="' . $user['aid'] . '">')));
		}

		$totalpages = ceil($maxrows['value'] / $NumPerPage);

		if($totalpages > 1){
			TableRow(GetPageList(BURL('users'), $totalpages, $page, 10, 's', urlencode($search), 'g', $groupid, 't', $time, 'o', $order));
		}

		TableFooter();

		PrintSubmit('删除客服', '', 1, '确定删除所选客服吗?');

		//JS排序等
		echo '<script type="text/javascript">
			$(function(){
				var url = "' . BURL("users") . FormatUrlParam(array('p'=>$page, 's'=>urlencode($search), 'g'=>$groupid, 't'=>$time)) . '";

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