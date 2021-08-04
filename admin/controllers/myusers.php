<?php if(!defined('ROOT')) die('Access denied.');

class c_myusers extends Admin{

	public function __construct($path){
		parent::__construct($path);

		$this->groupid = $this->admin['grid']; //组id
		$this->isTeamer = $this->checkTeamAccess(); //是否为组长
	}


	//保存
	public function save(){
		$this->checkTeamAction(); //验证组长权限

		$aid          = ForceIntFrom('aid');
		$username        = ForceStringFrom('username');
		$password        = ForceStringFrom('password');
		$passwordconfirm = ForceStringFrom('passwordconfirm');
		$groupid = $this->groupid;

		$email           = ForceStringFrom('email');
		$fullname        = ForceStringFrom('fullname');
		$fullname_en        = ForceStringFrom('fullname_en');
		$post        = ForceStringFrom('post');
		$post_en        = ForceStringFrom('post_en');

		if(!$username){
			$errors[] = '请输入用户名!';
		}elseif(!IsName($username)){
			$errors[] = '用户名存在非法字符!';
		}elseif(APP::$DB->getOne("SELECT aid FROM " . TABLE_PREFIX . "admin WHERE username = '$username' AND aid != '$aid'")){
			$errors[] = '用户名已存在!';
		}

		if(strlen($password) OR strlen($passwordconfirm)){
			if(strcmp($password, $passwordconfirm)){
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
			APP::$DB->exe("UPDATE " . TABLE_PREFIX . "admin SET username    = '$username',
			".Iif($password, "password = '" . md5($password) . "',")."
			email       = '$email',
			fullname       = '$fullname',
			fullname_en       = '$fullname_en',
			post       = '$post',
			post_en       = '$post_en'										 
			WHERE aid      = '$aid' AND grid = '$groupid' AND type <> 1 ");

			Success('myusers');
		}
	}


	//编辑调用add
	public function edit(){
		$aid = ForceIntFrom('aid');
		$groupid = $this->groupid;

		SubMenu('客服管理', array(array('客服列表', 'myusers'), array('编辑客服', 'myusers/edit?aid='.$aid, 1)));
		
		$user = APP::$DB->getOne("SELECT * FROM " . TABLE_PREFIX . "admin WHERE aid = '$aid' AND grid = '$groupid' AND type <> 1 ");

		if(!$user) Error('您正在尝试编辑的客服不存在!', '编辑客服错误');

		$need_info = '&nbsp;&nbsp;<font class=red>*</font>';
		$pass_info = Iif($aid, '&nbsp;&nbsp;<font class=grey>不修改请留空</font>', $need_info);

		echo '<form method="post" action="'.BURL('myusers/save').'">
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
		$Radio->AddOption(2, '<font class=blueb>组长</font>', '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;');
		$Radio->AddOption(0, '<b>客服</b>', '&nbsp;&nbsp;');
		$Radio->Attributes = 'disabled';

		TableRow(array('<b>类型:</b>', $Radio->Get()));

		TableRow(array('<b>密码:</b>', '<input type="text" name="password" size="20">'.$pass_info));
		TableRow(array('<b>确认密码:</b>', '<input type="text" name="passwordconfirm" size="20">'.$pass_info));
		TableRow(array('<b>Email地址:</b>', '<input type="text" name="email" value="'.$user['email'].'" size="20">'.$need_info));

		TableRow(array('<b>昵称 (<font class=blue>中文</font>):</b>', '<input type="text" name="fullname" value="'.$user['fullname'].'" size="20">'.$need_info));
		TableRow(array('<b>昵称 (<font class=red>英文</font>):</b>', '<input type="text" name="fullname_en" value="'.$user['fullname_en'].'" size="20">'.$need_info));
		TableRow(array('<b>职位 (<font class=blue>中文</font>):</b>', '<input type="text" name="post" value="'.$user['post'].'" size="20">'.$need_info));
		TableRow(array('<b>职位 (<font class=red>英文</font>):</b>', '<input type="text" name="post_en" value="'.$user['post_en'].'" size="20">'.$need_info));

		TableFooter();

		if($this->isTeamer) PrintSubmit('保存更新');
	}

	public function index(){

		$NumPerPage = 10;
		$page = ForceIntFrom('p', 1);

		$groupid = $this->groupid;

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
				$orderby = " activated DESC, aid DESC ";			
				$order = "aid.down";
				break;
		}

		$usergroups = array();

		$getusergroups = APP::$DB->query("SELECT id, activated, groupname FROM " . TABLE_PREFIX . "group ORDER BY id");
		while($g = APP::$DB->fetch($getusergroups)) {
			$usergroups[$g['id']] = array('groupname' => $g['groupname'], 'activated' => $g['activated']);
		}

		SubMenu('客服管理', array(array('客服列表', 'myusers', 1)));

		$getusers = APP::$DB->query("SELECT a.*, ROUND(AVG(r.score), 2) AS avg_score, COUNT(r.rid) AS scores, 
													(select COUNT(gid)  FROM " . TABLE_PREFIX . "guest WHERE aid = a.aid) AS guests 		
													FROM " . TABLE_PREFIX . "admin a 
													LEFT JOIN " . TABLE_PREFIX . "rating r ON r.aid = a.aid
													WHERE a.grid = '$groupid'
													GROUP BY a.aid ORDER BY {$orderby} LIMIT $start, $NumPerPage");


		$maxrows = APP::$DB->getOne("SELECT COUNT(aid) AS value FROM " . TABLE_PREFIX . "admin WHERE grid = '$groupid' ");

		echo '<form method="post" action="'.BURL('myusers/updateusers').'" name="usersform">
		<input type="hidden" name="p" value="'.$page.'">';

		TableHeader('本组共有 '.$maxrows['value'].' 位客服');

		TableRow(array('<a class="do-sort" for="aid">ID</a>', '<a class="do-sort" for="username">用户名</a>', '<a class="do-sort" for="online">服务状态</a>', '<a class="do-sort" for="guests">访客人数</a>', '<a class="do-sort" for="avg_score">平均得分<br>(评价次数)</a>', '<a class="do-sort" for="type">类型</a>', '客服组', '<a class="do-sort" for="activated">账号</a>', '<a class="do-sort" for="logins">登录</a>', 'Email', '昵称 (中)', '职位 (中)', '昵称 (EN)', '职位 (EN)', '<a class="do-sort" for="first">注册日期</a>', '<a class="do-sort" for="last">最后登陆 (IP)</a>'), 'tr0');

		while($user = APP::$DB->fetch($getusers)){

			TableRow(array($user['aid'],

			Iif($user['type']==1, '<img src="' . GetAvatar($user['aid']) . '" class="avatar wh30"><b>' . Iif($user['activated'] == 1, $user['username'], "<font class=red><s>$user[username]</s></font>") . '</b>', '<a title="编辑" href="'.BURL('myusers/edit?aid='.$user['aid']).'"><img src="' . GetAvatar($user['aid']) . '" class="avatar wh30"><b>' . Iif($user['activated'] == 1, $user['username'], "<font class=red><s>$user[username]</s></font>") . '</b></a>'),

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

			Iif($user['last'] == 0, '<span class="red">从未登陆</span>', DisplayDate($user['last'], '', 1)  . " ($user[lastip])")));
		}

		$totalpages = ceil($maxrows['value'] / $NumPerPage);

		if($totalpages > 1){
			TableRow(GetPageList(BURL('myusers'), $totalpages, $page, 10, 'o', $order));
		}

		TableFooter();

		//JS排序等
		echo '<script type="text/javascript">
			$(function(){
				var url = "' . BURL("myusers") . FormatUrlParam(array('p'=>$page)) . '";

				format_sort(url, "' . $order . '");
			});
		</script>';
	}
} 

?>