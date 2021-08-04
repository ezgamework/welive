<?php if(!defined('ROOT')) die('Access denied.');

class c_myphrases extends Admin{

	public function __construct($path){
		parent::__construct($path);

	}

	//添加
	public function save(){

		$msg = ForceStringFrom('msg');
		$msg_en = ForceStringFrom('msg_en');

		if(!$msg) $errors[] = '请填写常用短语中文内容!';
		if(!$msg_en) $errors[] = '请填写常用短语英文内容!';

		if(isset($errors)) Error($errors, '添加常用短语');

		$myid = $this->admin['aid'];

		APP::$DB->exe("INSERT INTO " . TABLE_PREFIX . "phrase (aid, activated, msg, msg_en) VALUES ('$myid', 1, '$msg', '$msg_en')");

		$lastid = APP::$DB->insert_id;
		APP::$DB->exe("UPDATE " . TABLE_PREFIX . "phrase SET sort = '$lastid' WHERE pid = '$lastid'");

		Success('myphrases');
	}

	//添加
	public function add(){

		SubMenu('添加常用短语', array(array('常用短语列表', 'myphrases'), array('添加常用短语', 'myphrases/add', 1)));

		$need_info = '&nbsp;&nbsp;<font class=red>* 必填项</font>';

		echo '<form method="post" action="'.BURL('myphrases/save').'">';

		TableHeader('常用短语信息:');

		TableRow(array('<b>提示:</b>', '<font class="orange" style="font-size:16px;">1. 客服在与访客的对话窗口中输入2个以上字符并停顿1秒后, 系统将自动搜索常用短语供客服选择, 方便其快速回复<br>2. 如需搜索多个关键词, 可用空格分隔输入内容, 且多个关键词不分先后顺序<br>3. 每个客服的常用短语可相互独立, 互不影响 (注：当常用短语少于8条时, Web端不搜索)</font>'));

		TableRow(array('<b>短语内容 (<font class=blue>中文</font>):</b>', '<input type="text" name="msg" value="" size="80">' . $need_info));
		TableRow(array('<b>短语内容 (<font class=red>英文</font>):</b>', '<input type="text" name="msg_en" value="" size="80">' . $need_info));

		TableFooter();

		PrintSubmit('添加常用短语');
	}


	//批量更新常用短语
	public function updatemyphrases(){
		$myid = $this->admin['aid'];

		$page = ForceIntFrom('p', 1);   //页码
		$search = ForceStringFrom('s');
		$type = ForceIntFrom('t');
		$order = ForceStringFrom('o');

		if(IsPost('updatemyphrases')){
			$sorts   = $_POST['sorts'];
			$activateds   = $_POST['activateds'];
			$msgs   = $_POST['msgs'];
			$msg_ens   = $_POST['msg_ens'];
			$pids = Iif(isset($_POST['pids']), $_POST['pids'], array());

			for($i = 0; $i < count($pids); $i++){
				$pid = ForceInt($pids[$i]);
				APP::$DB->exe("UPDATE " . TABLE_PREFIX . "phrase SET sort = '" . ForceInt($sorts[$i]) . "',
					activated = '" . ForceInt($activateds[$i]) . "',
					msg = '" . ForceString($msgs[$i]) . "',
					msg_en = '" . ForceString($msg_ens[$i]) . "'					
					WHERE pid = '$pid' AND aid='$myid'");
			}
		}else{
			$deletepids = Iif(isset($_POST['deletepids']), $_POST['deletepids'], array());

			for($i = 0; $i < count($deletepids); $i++){
				$pid = ForceInt($deletepids[$i]);
				APP::$DB->exe("DELETE FROM " . TABLE_PREFIX . "phrase WHERE pid = '$pid' AND aid='$myid'");
			}
		}

		Success('myphrases?p=' . $page. FormatUrlParam(array('s'=>urlencode($search), 't'=>$type, 'o'=>$order)));
	}


	public function index(){
		$myid = $this->admin['aid'];

		$NumPerPage = 10;
		$page = ForceIntFrom('p', 1);
		$search = ForceStringFrom('s');
		$type = ForceStringFrom('t');

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

            case 'sort.up':
				$orderby = " sort ASC ";
				break;

			default:
				$orderby = " sort DESC ";			
				$order = "sort.down";
				break;
		}

		SubMenu('我的常用短语', array(array('常用短语列表', 'myphrases', 1), array('添加常用短语', 'myphrases/add')));

		TableHeader('搜索常用短语');

		TableRow('<center><form method="post" action="'.BURL('myphrases').'" name="searchmyphrases" style="display:inline-block;"><label>关键字:</label>&nbsp;<input type="text" name="s" size="14"  value="'.$search.'">&nbsp;&nbsp;&nbsp;<label>状态:</label>&nbsp;<select name="t"><option value="0">全部</option><option value="1" ' . Iif($type == '1', 'SELECTED') . '>可用</option><option value="2" ' . Iif($type == '2', 'SELECTED') . ' class=red>已禁用</option></select>&nbsp;&nbsp;&nbsp;&nbsp;<input type="submit" value="搜索常用短语" class="cancel"></form></center>');
		
		TableFooter();

		if($search){
			$searchsql = " WHERE (msg LIKE '%$search%' OR msg_en LIKE '%$search%') AND aid = '$myid' ";
			$title = "搜索: <span class=note>$search</span> 的常用短语列表";

			if($type) {
				if($type == 1 OR $type == 2){
					$searchsql .= " AND activated = " . Iif($type == 1, 1, 0)." ";
					$title = "在 <span class=note>" .Iif($type == 1, '可用的常用短语', '已禁用的常用短语'). "</span> 中, " . $title;
				}
			}
		}else if($type){
			if($type == 1 OR $type == 2){
				$searchsql .= " WHERE aid = '$myid' AND activated = " . Iif($type == 1, 1, 0)." ";
				$title = "全部 <span class=note>" .Iif($type == 1, '可用的常用短语', '已禁用的常用短语'). "</span> 列表";
			}
		}else{
			$searchsql = " WHERE aid = '$myid' ";
			$title = '我的全部常用短语列表';
		}

		$getmyphrases = APP::$DB->query("SELECT * FROM " . TABLE_PREFIX . "phrase ".$searchsql." ORDER BY {$orderby} LIMIT $start,$NumPerPage");

		$maxrows = APP::$DB->getOne("SELECT COUNT(pid) AS value FROM " . TABLE_PREFIX . "phrase ".$searchsql);

		echo '<form method="post" action="'.BURL('myphrases/updatemyphrases').'" name="myphrasesform">
		<input type="hidden" name="p" value="'.$page.'">
		<input type="hidden" name="s" value="'.$search.'">
		<input type="hidden" name="t" value="'.$type.'">
		<input type="hidden" name="o" value="'.$order.'">';

		TableHeader($title.'('.$maxrows['value'].'个)');

		TableRow(array('所属客服', '<a class="do-sort" for="sort">排序</a>', '<a class="do-sort" for="activated">状态</a>', '短语 (中)', '短语 (英)', '<input type="checkbox" id="checkAll" for="deletepids[]"> <label for="checkAll">删除</label>'), 'tr0');

		if($maxrows['value'] < 1){
			TableRow('<center><BR><font class=redb>未搜索到任何常用短语!</font><BR><BR></center>');
		}else{
			while($phrase = APP::$DB->fetch($getmyphrases)){
				TableRow(array('<input type="hidden" name="pids[]" value="'.$phrase['pid'].'">' . $this->admin['fullname'] . " (ID: $myid)",

				'<input type="text" name="sorts[]" value="' . $phrase['sort'] . '" size="4">',

				'<select name="activateds[]"' . Iif(!$phrase['activated'], ' class=red'). '><option value="1">可用</option><option class="red" value="0" ' . Iif(!$phrase['activated'], 'SELECTED') . '>禁用</option></select>',

				'<input type="text" name="msgs[]" value="' . $phrase['msg'] . '" size="60">',

				'<input type="text" name="msg_ens[]" value="' . $phrase['msg_en'] . '" size="60">',

				'<input type="checkbox" name="deletepids[]" value="' . $phrase['pid'] . '">'));
			}

			$totalpages = ceil($maxrows['value'] / $NumPerPage);

			if($totalpages > 1){
				TableRow(GetPageList(BURL('myphrases'), $totalpages, $page, 10, 's', urlencode($search), 't', $type, 'o', $order));
			}

		}

		TableFooter();

		echo '<div class="submit"><input type="submit" name="updatemyphrases" value="保存更新" class="cancel" style="margin-right:28px"><input type="submit" name="deletemyphrases" value="删除常用短语" class="save" onclick="var _me=$(this);showDialog(\'确定删除所选常用短语吗?\', \'确认操作\', function(){_me.closest(\'form\').submit();});return false;"></div></form>
		<script type="text/javascript">
			$(function(){
				var url = "' . BURL("myphrases") . FormatUrlParam(array('p'=>$page, 's'=>urlencode($search), 't'=>$type)) . '";

				format_sort(url, "' . $order . '");
			});		
		</script>';

	}

} 

?>