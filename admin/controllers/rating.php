<?php if(!defined('ROOT')) die('Access denied.');

class c_rating extends Admin{

	public function __construct($path){
		parent::__construct($path);

		$this->CheckAction();
	}


	//快速删除评价
	public function fastdelete(){
		$days = ForceIntFrom('days');

		if(!$days) Error('请选择删除期限!');

		$time = time() - $days * 24 * 3600;

		APP::$DB->exe("DELETE FROM " . TABLE_PREFIX . "rating WHERE time < $time");

		Success('rating');
	}

	//批量更新评价
	public function updaterating(){
		$page = ForceIntFrom('p', 1);   //页码
		$search = ForceStringFrom('s');
		$from = ForceStringFrom('f');
		$time = ForceStringFrom('t');
		$order = ForceStringFrom('o');

		$deleterids = Iif(isset($_POST['deleterids']), $_POST['deleterids'], array());

		for($i = 0; $i < count($deleterids); $i++){
			$rid = ForceInt($deleterids[$i]);
			APP::$DB->exe("DELETE FROM " . TABLE_PREFIX . "rating WHERE rid = '$rid'");
		}

		Success('rating?p=' . $page. FormatUrlParam(array('s'=>urlencode($search), 'f'=>$from, 't'=>$time, 'o'=>$order)));
	}


	public function index(){
		$NumPerPage = 20;
		$page = ForceIntFrom('p', 1);
		$search = ForceStringFrom('s');
		$from = ForceIntFrom('f');
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
            case 'score.down':
				$orderby = " score DESC ";
				break;

            case 'score.up':
				$orderby = " score ASC ";
				break;

            case 'aid.down':
				$orderby = " aid DESC ";
				break;

            case 'aid.up':
				$orderby = " aid ASC ";
				break;

            case 'gid.down':
				$orderby = " gid DESC ";
				break;

            case 'gid.up':
				$orderby = " gid ASC ";
				break;

            case 'time.down':
				$orderby = " time DESC ";
				break;

            case 'time.up':
				$orderby = " time ASC ";
				break;

            case 'rid.up':
				$orderby = " rid ASC ";
				break;

			default:
				$orderby = " rid DESC ";			
				$order = "rid.down";
				break;
		}

		//获取所有客服
		$admins = array();
		$getadmins = APP::$DB->query("SELECT a.aid, a.fullname, gr.groupname FROM " . TABLE_PREFIX . "admin a LEFT JOIN " . TABLE_PREFIX . "group gr ON gr.id = a.grid");
		while($a = APP::$DB->fetch($getadmins)){
			$admins[$a['aid']] = $a;
		}

		SubMenu('评价列表', array(array('评价列表', 'rating', 1)));

		TableHeader('搜索及快速删除');

		TableRow('<center><form method="post" action="'.BURL('rating').'" name="searchrating" style="display:inline-block;*display:inline;"><label>关键字:</label>&nbsp;<input type="text" name="s" size="12"  value="'.$search.'">&nbsp;&nbsp;&nbsp;<label>分类:</label>&nbsp;<select name="f"><option value="0">全部</option><option value="1" ' . Iif($from == '1', 'SELECTED') . ' class=red>按客人ID搜索</option><option value="2" ' . Iif($from == '2', 'SELECTED') . '>按客服ID搜索</option></select>&nbsp;&nbsp;&nbsp;<label>日期:</label>&nbsp;<input type="text" name="t" class="date-input" value="' . $time . '" size="8">&nbsp;&nbsp;&nbsp;&nbsp;<input type="submit" value="搜索评价" class="cancel"></form>

		<form method="post" action="'.BURL('rating/fastdelete').'" name="fastdelete" style="display:inline-block;margin-left:80px;*display:inline;"><label>快速删除评价:</label>&nbsp;<select name="days"><option value="0">请选择 ...</option><option value="360">12个月前的对话评价</option><option value="180">&nbsp;6 个月前的对话评价</option><option value="90">&nbsp;3 个月前的对话评价</option><option value="30">&nbsp;1 个月前的对话评价</option></select>&nbsp;&nbsp;&nbsp;&nbsp;<input type="submit" value="快速删除" class="save" onclick="var _me=$(this);showDialog(\'确定删除所选评价吗?\', \'确认操作\', function(){_me.closest(\'form\').submit();});return false;"></form></center>');
		
		TableFooter();

		if($search){
			if($from == 2){
				$searchsql = " WHERE aid = '$search' ";
				$title = "搜索客服ID号为: <span class=note>$search</span> 的评价";
			}elseif($from == 1){
				$searchsql = " WHERE gid = '$search' ";
				$title = "搜索客人ID号为: <span class=note>$search</span> 的评价";
			}else{
				$searchsql = " WHERE (gid = '$search' OR aid = '$search' OR msg LIKE '%$search%') ";
				$title = "搜索: <span class=note>$search</span> 的评价";
			}

			if($time) {
				$searchsql .= " AND time >= '$start_time' AND time < '$end_time' ";
			}
		}else if($time){
			$searchsql .= " WHERE time >= '$start_time' AND time < '$end_time' ";
			$title = "搜索日期: <span class=note>{$time}</span> 的评价列表";
		}else{
			$searchsql = '';
			$title = '全部评价列表';
		}

		$getrating = APP::$DB->query("SELECT * FROM " . TABLE_PREFIX . "rating ".$searchsql." ORDER BY {$orderby} LIMIT $start,$NumPerPage");

		$maxrows = APP::$DB->getOne("SELECT COUNT(rid) AS value FROM " . TABLE_PREFIX . "rating ".$searchsql);

		echo '<script type="text/javascript" src="'.SYSDIR.'public/laydate/laydate.js"></script>
		<form method="post" action="'.BURL('rating/updaterating').'" name="ratingform">
		<input type="hidden" name="p" value="'.$page.'">
		<input type="hidden" name="s" value="'.$search.'">
		<input type="hidden" name="f" value="'.$from.'">
		<input type="hidden" name="t" value="'.$time.'">
		<input type="hidden" name="o" value="'.$order.'">';

		TableHeader($title.'('.$maxrows['value'].'个)');
		TableRow(array('<a class="do-sort" for="rid">ID</a>', '<a class="do-sort" for="gid">客人ID</a>', '客服组', '<a class="do-sort" for="aid">被评价客服(ID)</a>', '<a class="do-sort" for="score">星星数</a>', '意见建议', '<a class="do-sort" for="time">评价时间</a>', '<input type="checkbox" id="checkAll" for="deleterids[]"> <label for="checkAll">删除</label>'), 'tr0');

		if($maxrows['value'] < 1){
			TableRow('<center><BR><font class=redb>未搜索到任何评价!</font><BR><BR></center>');
		}else{
			while($msg = APP::$DB->fetch($getrating)){
				TableRow(array($msg['rid'],

				"<a title=\"编辑客人\" href=\"" . BURL('guests/edit?gid='.$msg['gid']) . "\">$msg[gid]</a>",

				"<font class=grey>" . Iif(isset($admins[$msg['aid']]), $admins[$msg['aid']]['groupname'], "-") . "</font>",

				Iif(isset($admins[$msg['aid']]), "<a title=\"编辑客服\" href=\"" . BURL('users/edit?aid='.$msg['aid']) . "\">" . $admins[$msg['aid']]['fullname'] . "($msg[aid])</a>", Iif($msg['aid'] == 888888, "机器人(888888)", "未知客服($msg[aid])")),

				$msg['score'],

				$msg['msg'],

				DisplayDate($msg['time'], '', 1),

				'<input type="checkbox" name="deleterids[]" value="' . $msg['rid'] . '">'));
			}

			$totalpages = ceil($maxrows['value'] / $NumPerPage);

			if($totalpages > 1){
				TableRow(GetPageList(BURL('rating'), $totalpages, $page, 10, array('s'=>urlencode($search), 'f'=>$from, 't'=>$time, 'o'=>$order)));
			}

		}

		TableFooter();

		PrintSubmit('删除评价', '', 1, '确定删除所选评价吗?');

		//JS排序等
		echo '<script type="text/javascript">
			$(function(){
				var url = "' . BURL("rating") . FormatUrlParam(array('p'=>$page, 's'=>urlencode($search), 'f'=>$from, 't'=>$time)) . '";

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