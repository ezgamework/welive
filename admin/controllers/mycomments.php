<?php if(!defined('ROOT')) die('Access denied.');

class c_mycomments extends Admin{

	public function __construct($path){
		parent::__construct($path);

		$this->groupid = $this->admin['grid']; //组id
		$this->isTeamer = $this->checkTeamAccess(); //是否为组长
	}

	//ajax动作集合, 通过action判断具体任务
    public function ajax(){
		
		$action = ForceStringFrom('action');
		$groupid = $this->groupid;

		//保存单条记录
		if($action == 'mark_comment'  AND $this->isTeamer){

			$cid = ForceIntFrom('cid');

			APP::$DB->exe("UPDATE " . TABLE_PREFIX . "comment SET readed = 1 WHERE cid = '$cid' AND grid = '$groupid' ");
		}


		die($this->json->encode($this->ajax));
	}


	//批量更新留言
	public function updatecomments(){
		$groupid = $this->groupid;

		$page = ForceIntFrom('p', 1);   //页码
		$search = ForceStringFrom('s');
		$read = ForceStringFrom('r');
		$time = ForceStringFrom('t');
		$order = ForceStringFrom('o');

		if(IsPost('updatecomms') AND $this->isTeamer){
			$updatecids = Iif(isset($_POST['updatecids']), $_POST['updatecids'], array());

			for($i = 0; $i < count($updatecids); $i++){
				$cid = ForceInt($updatecids[$i]);
				APP::$DB->exe("UPDATE " . TABLE_PREFIX . "comment SET readed = 1 WHERE cid = '$cid' AND grid = '$groupid' ");
			}
		}

		//获取未读统计数据
		$comms = APP::$DB->getOne("SELECT COUNT(cid) AS nums FROM " . TABLE_PREFIX . "comment WHERE readed = 0 AND grid = '$groupid' ");

		//更新顶部提示信息
		echo '<script type="text/javascript">
			$(function(){
				var info_total = ' . $comms['nums'] . ';
				//将统计数据保存为cookie. 注: header已发送, 此页面不能使用php保存cookie
				setCookie("' . COOKIE_KEY . 'backinfos", info_total, 365);
			});
		</script>';


		Success('mycomments?p=' . $page. FormatUrlParam(array('s'=>urlencode($search), 'r'=>$read, 't'=>$time, 'o'=>$order)));
	}


	public function index(){
		$NumPerPage = 20;
		$page = ForceIntFrom('p', 1);
		$search = ForceStringFrom('s');
		$read = ForceIntFrom('r');
		$time = ForceStringFrom('t');

		$groupid = $this->groupid;

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
            case 'readed.down':
				$orderby = " readed DESC ";
				break;

            case 'readed.up':
				$orderby = " readed ASC ";
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

            case 'cid.up':
				$orderby = " cid ASC ";
				break;

			default:
				$orderby = " readed ASC, cid DESC ";			
				$order = "cid.down";
				break;
		}

		SubMenu('留言列表', array(array('留言列表', 'mycomments', 1)));

		TableHeader('搜索留言');
		TableRow('<center><form method="post" action="'.BURL('mycomments').'" name="searchcomments" style="display:inline-block;*display:inline;"><label>关键字:</label>&nbsp;<input type="text" name="s" size="12"  value="'.$search.'">&nbsp;&nbsp;&nbsp;<label>状态:</label>&nbsp;<select name="r"><option value="0">全部</option><option value="1" ' . Iif($read == '1', 'SELECTED') . ' class=red>未读</option><option value="2" ' . Iif($read == '2', 'SELECTED') . '>已读</option></select>&nbsp;&nbsp;&nbsp;<label>日期:</label>&nbsp;<input type="text" name="t" class="date-input" value="' . $time . '" size="8">&nbsp;&nbsp;&nbsp;&nbsp;<input type="submit" value="搜索留言" class="cancel"></form>');
		
		TableFooter();

		if($search){
			if(preg_match("/^[1-9][0-9]*$/", $search)){
				$s = ForceInt($search);
				$searchsql = " WHERE (cid = '$s' OR gid = '$s' OR phone LIKE '%$s%') "; //按ID搜索
				$title = "搜索数字为: <span class=note>$s</span> 的留言";
			}else{
				$searchsql = " WHERE (fullname LIKE '%$search%' OR email LIKE '%$search%' OR content LIKE '%$search%') ";
				$title = "搜索: <span class=note>$search</span> 的留言列表";
			}

			if($read) {
				if($read == 1 OR $read == 2){
					$searchsql .= " AND (readed = " . Iif($read == 1, 0, 1).") ";
					$title = "在 <span class=note>" .Iif($read == 1, '未读留言', '已读留言'). "</span> 中, " . $title;
				}
			}

			if($time) {
				$searchsql .= " AND time >= '$start_time' AND time < '$end_time' ";
			}
		}else if($read){
			$searchsql .= " WHERE (readed = " . Iif($read == 1, 0, 1).") ";
			$title = "全部 <span class=note>" .Iif($read == 1, '未读留言', '已读留言'). "</span> 列表";

			if($time) {
				$searchsql .= " AND time >= '$start_time' AND time < '$end_time' ";
			}

		}else if($time){
			$searchsql .= " WHERE time >= '$start_time' AND time < '$end_time' ";
			$title = "搜索日期: <span class=note>{$time}</span> 的留言列表";

		}else{
			$searchsql = " WHERE 1=1 ";
			$title = '本组全部留言列表';
		}

		$searchsql .= " AND grid = '$groupid' "; //限定为本组

		$getcomments = APP::$DB->query("SELECT * FROM " . TABLE_PREFIX . "comment ".$searchsql." ORDER BY {$orderby} LIMIT $start, $NumPerPage");

		$maxrows = APP::$DB->getOne("SELECT COUNT(cid) AS value FROM " . TABLE_PREFIX . "comment ".$searchsql);

		echo '<script type="text/javascript" src="'.SYSDIR.'public/laydate/laydate.js"></script>
		<form method="post" action="'.BURL('mycomments/updatecomments').'" name="commentsform">
		<input type="hidden" name="p" value="'.$page.'">
		<input type="hidden" name="s" value="'.$search.'">
		<input type="hidden" name="r" value="'.$read.'">
		<input type="hidden" name="t" value="'.$time.'">
		<input type="hidden" name="o" value="'.$order.'">';

		TableHeader($title.'('.$maxrows['value'].'个)');

		TableRow(array('<a class="do-sort" for="cid">ID</a>', '<a class="do-sort" for="readed">状态</a>', '姓名', '<a class="do-sort" for="gid">访客ID</a>', '客服组', 'Email', '电话', '留言内容', '标记单条', '<input type="checkbox" id="checkAll2" for="updatecids[]"> <label for="checkAll2">标记已读</label>', 'IP', '<a class="do-sort" for="time">留言时间</a>'), 'tr0');

		if($maxrows['value'] < 1){
			TableRow('<center><BR><font class=redb>未搜索到任何留言!</font><BR><BR></center>');
		}else{
			while($comm = APP::$DB->fetch($getcomments)){
				TableRow(array($comm['cid'],
				Iif($comm['readed'], '<font class=grey>已读</font>', '<font class=red>未读</font>'),
				Iif($comm['gid'], '<a title="编辑客人信息" href="'.BURL('myguests/edit?gid='.$comm['gid']).'">' . "$comm[fullname]</a>", $comm['fullname']),
				Iif($comm['gid'], $comm['gid'], '-'),
				"<font class=grey>" . $this->admin['groupname'] . "</font>",
				Iif($comm['email'], '<a href="mailto:' . $comm['email'] . '">' . $comm['email'] . '</a>'),
				$comm['phone'],
				nl2br($comm['content']),
				Iif(!$comm['readed'], '<img src="'. SYSDIR .'public/img/mark.png" ' . Iif($this->isTeamer, 'class="mark_item" title="标记已读"', 'title="组长可标记为已读"') . ' style="height:32px;cursor: pointer;">'),
				Iif(!$comm['readed'], '<input type="checkbox" name="updatecids[]" value="' . $comm['cid'] . '">'),
				'<a href="https://www.baidu.com/s?wd=' . $comm['ip'] . '" target="_blank">' . $comm['ip'] . '</a>',
				DisplayDate($comm['time'], '', 1)));
			}

			$totalpages = ceil($maxrows['value'] / $NumPerPage);

			if($totalpages > 1){
				TableRow(GetPageList(BURL('mycomments'), $totalpages, $page, 10, array('s'=>urlencode($search), 'r'=>$read, 't'=>$time, 'o'=>$order)));
			}

		}

		TableFooter();

		//获取未读留言数
		$comms = APP::$DB->getOne("SELECT COUNT(cid) AS nums FROM " . TABLE_PREFIX . "comment WHERE readed = 0 AND grid = '$groupid' ");

		$unreads = $comms['nums'];
		if($unreads > 99) $unreads = 99;

		//组长或管理员才可以标记为已读
		if($this->isTeamer){
			echo '<div class="submit"><input type="submit" name="updatecomms" value="标记已读" class="cancel" style="margin-right:28px"></div>';
		}

		echo '</form>
		<script type="text/javascript">
			$(function(){

				var url = "' . BURL("mycomments") . FormatUrlParam(array('p'=>$page, 's'=>urlencode($search), 'r'=>$read, 't'=>$time)) . '";

				format_sort(url, "' . $order . '");

				//日期选择器
				$(".date-input").each(function(){
					laydate.render({
						elem: this
					});
				});

				//保存单条记录
				$(".mark_item").click(function(e){
					var obj = $(this);
					obj.attr("src", "'. SYSDIR .'public/img/saving.gif");

					var item = $(this).parent().parent();

					var cid = item.find("td:first").html();

					ajax("' . BURL('mycomments/ajax') . '", {action: "mark_comment", cid: cid}, function(data){

						var new_num = parseInt($("#info_total").html()) - 1;

						if(new_num <= 0){
							new_num = 0;
							$("#info_total").html(new_num).hide();
						}else{
							$("#info_total").html(new_num);
						}
				
						setCookie("' . COOKIE_KEY . 'backinfos", new_num, 365);

						setTimeout(function(){
							item.find(".red").removeClass("red").html("<font class=grey>已读</font>");
							obj.css("visibility", "hidden").off("click");
							obj.parent().next("td").html("");
						}, 100); //0.1秒切换, 否则太快没效果
					});

					e.preventDefault();
					return false;
				});


				var info_total = ' . $unreads . ';

				if(info_total > 0){
					$("#topmenu #info_total").html(info_total).show();
				}else{
					$("#topmenu #info_total").html("").hide();
				}

				//将统计数据保存为cookie. 注: header已发送, 此页面不能使用php保存cookie
				setCookie("' . COOKIE_KEY . 'backinfos", info_total, 365);
			});
		</script>';
	}

} 

?>