<?php if(!defined('ROOT')) die('Access denied.');

class c_myquestions extends Admin{

	public function __construct($path){
		parent::__construct($path);

		$this->groupid = $this->admin['grid']; //组id
		$this->groupname = $this->admin['groupname']; //组名称

		$this->isTeamer = $this->checkTeamAccess(); //是否为组长

		$this->team_filename = ROOT . "config/team_settings.php";
	}

	//更新常见问题缓存
	private function refresh_team_setting(){
		//解决PHP7 Opcache开启时无法实时更新设置的问题
		if(function_exists('opcache_reset')) {
			@opcache_reset();
		}

		$team_settings = @require($this->team_filename);

		//获取数据
		$team_questions = APP::$DB->getAll("SELECT qid, grid, title FROM " . TABLE_PREFIX . "question WHERE activated = 1 ORDER BY grid ASC, sort DESC");

		$team_settings['questions'] = $team_questions;

		$contents = "<?php

//客服组缓存配置文件

return " . var_export($team_settings, true) . ";


?>";

		@file_put_contents($this->team_filename, $contents, LOCK_EX);
	}


	//添加
	public function save(){
		$this->checkTeamAction(); //验证组长权限

		$groupid = $this->groupid;
		$title = ForceStringFrom('title');

		if(!$title) $errors[] = '请填写常见问题内容!';

		if(isset($errors)) Error($errors, '添加常见问题');

		APP::$DB->exe("INSERT INTO " . TABLE_PREFIX . "question (grid, activated, title) VALUES ('$groupid', 1, '$title')");

		$lastid = APP::$DB->insert_id;
		APP::$DB->exe("UPDATE " . TABLE_PREFIX . "question SET sort = '$lastid' WHERE qid = '$lastid' AND grid = '$groupid' ");

		$this->refresh_team_setting(); //更新缓存

		Success('myquestions');
	}

	//添加
	public function add(){
		$this->checkTeamAction(); //验证组长权限

		if(!is_writeable($this->team_filename)){
			$errors = '请将客服组配置文件: <br>config/team_settings.php <br>设置为可写, 即属性设置为: 777';
		}

		if(isset($errors)) Error($errors, '常见问题管理错误');

		SubMenu('添加常见问题', array(array('常见问题列表', 'myquestions'), array('添加常见问题', 'myquestions/add', 1)));

		$need_info = '&nbsp;&nbsp;<font class=red>* 必填项</font>';

		echo '<form method="post" action="'.BURL('myquestions/save').'">';

		TableHeader('常见问题信息:');

		TableRow(array('<b>提示:</b>', '<font class="orange" style="font-size:16px;">1. 常见问题是指访客在对话窗口输入2个或以上字符后, 自动搜索常见问题供访客选择, 方便其快速输入<br>2. 每个客服组的常见问题相互独立, 即本组访客只能搜索到本组的常见问题<br>3. 访客如需搜索多个关键字, 可用空格分隔输入内容</font>'));

		TableRow(array('<b>问题内容(不支持html)</b>', '<input type="text" name="title" value="" size="60">' . $need_info));

		TableFooter();

		PrintSubmit('添加常见问题');
	}


	//批量更新常见问题
	public function updatemyquestions(){
		$this->checkTeamAction(); //验证组长权限

		if(!is_writeable($this->team_filename)){
			$errors = '请将客服组配置文件: <br>config/team_settings.php <br>设置为可写, 即属性设置为: 777';
		}

		if(isset($errors)) Error($errors, '常见问题管理错误');

		$page = ForceIntFrom('p', 1);   //页码
		$search = ForceStringFrom('s');
		$type = ForceIntFrom('t');
		$order = ForceStringFrom('o');
		$groupid = $this->groupid;

		$need_refresh = 0;

		if(IsPost('updatemyquestions')){
			$sorts   = $_POST['sorts'];
			$activateds   = $_POST['activateds'];
			$titles   = $_POST['titles'];
			$qids = Iif(isset($_POST['qids']), $_POST['qids'], array());

			for($i = 0; $i < count($qids); $i++){
				$need_refresh = 1;
				$qid = ForceInt($qids[$i]);
				APP::$DB->exe("UPDATE " . TABLE_PREFIX . "question SET sort = '" . ForceInt($sorts[$i]) . "',
					activated = '" . ForceInt($activateds[$i]) . "',
					title = '" . ForceString($titles[$i]) . "'			
					WHERE qid = '$qid' AND grid = '$groupid' ");
			}
		}else{
			$deleteqids = Iif(isset($_POST['deleteqids']), $_POST['deleteqids'], array());

			for($i = 0; $i < count($deleteqids); $i++){
				$need_refresh = 1;
				$qid = ForceInt($deleteqids[$i]);
				APP::$DB->exe("DELETE FROM " . TABLE_PREFIX . "question WHERE qid = '$qid' AND grid = '$groupid' ");
			}
		}

		if($need_refresh) $this->refresh_team_setting(); //更新缓存

		Success('myquestions?p=' . $page. FormatUrlParam(array('s'=>urlencode($search), 't'=>$type, 'o'=>$order)));
	}


	public function index(){
		$NumPerPage = 10;
		$page = ForceIntFrom('p', 1);
		$search = ForceStringFrom('s');
		$type = ForceStringFrom('t');
		$groupid = $this->groupid;

		if(IsGet('s')) $search = urldecode($search);

		$start = $NumPerPage * ($page-1);

		//排序
		$order = ForceStringFrom('o');
        switch($order)
        {
            case 'activated.down':
				$orderby = " activated DESC ";
				break;

            case 'activated.up':
				$orderby = " activated ASC ";
				break;

            case 'sort.down':
				$orderby = " sort DESC ";
				break;

            case 'sort.up':
				$orderby = " sort ASC ";
				break;

            case 'qid.up':
				$orderby = " qid ASC ";
				break;

			default:
				$orderby = " qid DESC ";			
				$order = "qid.down";
				break;
		}

		SubMenu('常见问题列表', array(array('常见问题列表', 'myquestions', 1), array('添加常见问题', 'myquestions/add')));

		TableHeader('搜索常见问题');

		TableRow('<center><form method="post" action="'.BURL('myquestions').'" name="searchmyquestions" style="display:inline-block;"><label>关键字:</label>&nbsp;<input type="text" name="s" size="14"  value="'.$search.'">&nbsp;&nbsp;&nbsp;<label>状态:</label>&nbsp;<select name="t"><option value="0">全部</option><option value="1" ' . Iif($type == '1', 'SELECTED') . '>可用</option><option value="2" ' . Iif($type == '2', 'SELECTED') . ' class=red>已禁用</option></select>&nbsp;&nbsp;&nbsp;&nbsp;<input type="submit" value="搜索常见问题" class="cancel"></form></center>');
		
		TableFooter();

		if($search){
			if(preg_match("/^[1-9][0-9]*$/", $search)){
				$s = ForceInt($search);
				$searchsql = " WHERE (qid = '$s' OR grid = '$s' OR sort = '$s' OR title LIKE '%$s%') "; //数字搜索
				$title = "搜索数字号为: <span class=note>$s</span> 的常见问题";
			}else{
				$searchsql = " WHERE (title LIKE '%$search%') ";
				$title = "搜索: <span class=note>$search</span> 的常见问题列表";
			}

			if($type) {
				if($type == 1 OR $type == 2){
					$searchsql .= " AND activated = " . Iif($type == 1, 1, 0)." ";
					$title = "在 <span class=note>" .Iif($type == 1, '有效的常见问题', '已禁用的常见问题'). "</span> 中, " . $title;
				}
			}

		}else if($type){
			if($type == 1 OR $type == 2){
				$searchsql .= " WHERE activated = " . Iif($type == 1, 1, 0)." ";
				$title = "全部 <span class=note>" .Iif($type == 1, '有效的常见问题', '已禁用的常见问题'). "</span> 列表";
			}
		}else{
			$searchsql = " WHERE 1=1 ";
			$title = '全部常见问题列表';
		}

		$searchsql .= " AND grid = '$groupid' "; //限定为本组

		$getmyquestions = APP::$DB->query("SELECT * FROM " . TABLE_PREFIX . "question ".$searchsql." ORDER BY {$orderby} LIMIT $start,$NumPerPage");

		$maxrows = APP::$DB->getOne("SELECT COUNT(qid) AS value FROM " . TABLE_PREFIX . "question ".$searchsql);

		echo '<form method="post" action="'.BURL('myquestions/updatemyquestions').'" name="myquestionsform">
		<input type="hidden" name="p" value="'.$page.'">
		<input type="hidden" name="s" value="'.$search.'">
		<input type="hidden" name="t" value="'.$type.'">
		<input type="hidden" name="o" value="'.$order.'">';

		TableHeader($title.'('.$maxrows['value'].'个)');
		TableRow(array('<a class="do-sort" for="qid">ID</a>', '<a class="do-sort" for="sort">排序</a>', '<a class="do-sort" for="activated">状态</a>', '客服组', '问题', '<input type="checkbox" id="checkAll" for="deleteqids[]"> <label for="checkAll">删除</label>'), 'tr0');

		if($maxrows['value'] < 1){
			TableRow('<center><BR><font class=redb>未搜索到任何常见问题!</font><BR><BR></center>');
		}else{
			while($question = APP::$DB->fetch($getmyquestions)){
				TableRow(array('<input type="hidden" name="qids[]" value="'.$question['qid'].'">' . $question['qid'],

				'<input type="text" name="sorts[]" value="' . $question['sort'] . '" size="4">',

				'<select name="activateds[]"' . Iif(!$question['activated'], ' class=red'). '><option value="1">可用</option><option class="red" value="0" ' . Iif(!$question['activated'], 'SELECTED') . '>禁用</option></select>',

				"<font class=grey>" . $this->groupname . "</font>",

				'<input type="text" name="titles[]" value="' . $question['title'] . '" size="60">',

				'<input type="checkbox" name="deleteqids[]" value="' . $question['qid'] . '">'));
			}

			$totalpages = ceil($maxrows['value'] / $NumPerPage);

			if($totalpages > 1){
				TableRow(GetPageList(BURL('myquestions'), $totalpages, $page, 10, 's', urlencode($search), 't', $type, 'o', $order));
			}

		}

		TableFooter();

		//组长编辑删除权限
		if($this->isTeamer){
			echo '<div class="submit"><input type="submit" name="updatemyquestions" value="保存更新" class="cancel" style="margin-right:28px"><input type="submit" name="deletemyquestions" value="删除常见问题" class="save" onclick="var _me=$(this);showDialog(\'确定删除所选常见问题吗?\', \'确认操作\', function(){_me.closest(\'form\').submit();});return false;"></div>';
		}

		echo '</form>
		<script type="text/javascript">
			$(function(){
				var url = "' . BURL("myquestions") . FormatUrlParam(array('p'=>$page, 's'=>urlencode($search), 't'=>$type)) . '";

				format_sort(url, "' . $order . '");
			});		
		</script>';

	}

} 

?>