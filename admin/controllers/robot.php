<?php if(!defined('ROOT')) die('Access denied.');

class c_robot extends Admin{

	public function __construct($path){
		parent::__construct($path);

		$this->CheckAction();
	}


	//ajax动作集合, 通过action判断具体任务
    public function ajax(){
		//ajax权限验证
		if(!$this->CheckAccess()){
			$this->ajax['s'] = 0; //ajax操作失败
			$this->ajax['i'] = '您没有权限管理自动回复内容!';
			die($this->json->encode($this->ajax));
		}
		
		$action = ForceStringFrom('action');
		if($action == 'set_robot'){

			//解决PHP7 Opcache开启时无法实时更新设置的问题
			if(function_exists('opcache_reset')) {
				@opcache_reset();
			}

			$RobotName = ForceStringFrom('RobotName');
			$RobotName_en = ForceStringFrom('RobotName_en');
			$RobotPost = ForceStringFrom('RobotPost');
			$RobotPost_en = ForceStringFrom('RobotPost_en');

			$filename = ROOT . "config/settings.php";

			//修改./config/settings.php文件
			$fp = @fopen($filename, 'rb');
			$contents = @fread($fp, @filesize($filename));
			@fclose($fp);
			$contents = $oldcontents = trim($contents);

			$contents = preg_replace("/[$]_CFG\['RobotName'\]\s*\=\s*[\"'].*?[\"'];/is", "\$_CFG['RobotName'] = \"$RobotName\";", $contents);
			$contents = preg_replace("/[$]_CFG\['RobotName_en'\]\s*\=\s*[\"'].*?[\"'];/is", "\$_CFG['RobotName_en'] = \"$RobotName_en\";", $contents);
			$contents = preg_replace("/[$]_CFG\['RobotPost'\]\s*\=\s*[\"'].*?[\"'];/is", "\$_CFG['RobotPost'] = \"$RobotPost\";", $contents);
			$contents = preg_replace("/[$]_CFG\['RobotPost_en'\]\s*\=\s*[\"'].*?[\"'];/is", "\$_CFG['RobotPost_en'] = \"$RobotPost_en\";", $contents);

			if($contents != $oldcontents){
				$fp = @fopen($filename, 'w');
				@fwrite($fp, $contents);
				@fclose($fp);
			}
		}

		if($action == 'get_robot_score'){

			$get_rating = APP::$DB->getOne("SELECT ROUND(AVG(score), 2) AS avg_score, COUNT(rid) AS scores FROM " . TABLE_PREFIX . "rating WHERE aid = 888888 GROUP BY aid");

			$get_guest = APP::$DB->getOne("SELECT COUNT(gid) AS guests FROM " . TABLE_PREFIX . "guest WHERE aid = 888888");

			$this->ajax['score'] = Iif($get_rating, $get_rating['avg_score'], 0);
			$this->ajax['num'] = Iif($get_rating, $get_rating['scores'], 0);
			$this->ajax['guest'] = Iif($get_guest, $get_guest['guests'], 0);
		}


		//新增或保存更新单条记录
		if($action == 'save_robot'){

			$id = ForceIntFrom('id');
			$sort = ForceIntFrom('sort');
			$activated = ForceIntFrom('activated');
			$keyword = trim(ForceStringFrom('keyword'), ",");
			$msg = ForceStringFrom('msg');
			$avatar = ForceIntFrom('avatar');
			$kn = count(explode(',', $keyword));

			if(!$id){
				if(!$keyword){
					$this->ajax['s'] = 0; //ajax操作失败
					$this->ajax['i'] = '请填写关键字!';
					die($this->json->encode($this->ajax));
				}

				if(!$msg){
					$this->ajax['s'] = 0; //ajax操作失败
					$this->ajax['i'] = '请填写自动回复内容!';
					die($this->json->encode($this->ajax));
				}

				APP::$DB->exe("INSERT INTO " . TABLE_PREFIX . "robot (activated, sort, kn, keyword, msg, avatar) VALUES (1,'$sort', '$kn', '$keyword', '$msg', '$avatar')");

				$lastid = APP::$DB->insert_id;

				$this->ajax['i'] = $lastid;
				$this->ajax['av'] = $avatar;
				$this->ajax['sort'] = $sort;

				if(!$sort){
					$this->ajax['sort'] = $lastid;
					APP::$DB->exe("UPDATE " . TABLE_PREFIX . "robot SET sort = '$lastid' WHERE id = '$lastid'");
				}

			}else{
				APP::$DB->exe("UPDATE " . TABLE_PREFIX . "robot SET activated = '{$activated}',
					sort = '{$sort}',
					" . Iif($keyword, "kn = '{$kn}', "). "
					" . Iif($keyword, "keyword = '{$keyword}', "). "
					" . Iif($msg, "msg = '{$msg}', "). "
					avatar = '{$avatar}'
					WHERE id = '{$id}'");
			}

			//根据关键词删除匹配成功的机器人无解记录
			$this->delete_robotmsg($keyword);
		}


		die($this->json->encode($this->ajax));
	}

	//添加
	public function save(){

		$keyword = trim(ForceStringFrom('keyword'), ",");

		$msg = ForceStringFrom('msg');
		$avatar = ForceIntFrom('avatar');

		if(!$keyword) $errors[] = '请填写关键字!';
		if(!$msg) $errors[] = '请填写自动回复内容!';

		if(isset($errors)) Error($errors, '添加自动回复');

		APP::$DB->exe("INSERT INTO " . TABLE_PREFIX . "robot (activated, keyword, msg, avatar) VALUES (1, '$keyword', '$msg', '$avatar')");

		$lastid = APP::$DB->insert_id;
		$kn = count(explode(',', $keyword));

		APP::$DB->exe("UPDATE " . TABLE_PREFIX . "robot SET sort = '$lastid',  kn = '$kn' WHERE id = '$lastid'");

		//根据关键词删除匹配成功的机器人无解记录
		$this->delete_robotmsg($keyword);

		Success('robot');
	}

	//添加
	public function add(){

		SubMenu('机器人客服管理', array(array('机器人自动回复列表', 'robot'), array('添加自动回复', 'robot/add', 1), array('修改机器人信息', '')));

		$need_info = '&nbsp;&nbsp;<font class=red>* 必填项</font>';


		echo '<link rel="stylesheet" type="text/css" href="'. SYSDIR .'public/jquery.wSelect.css">
		<script src="'. SYSDIR .'public/jquery.wSelect.js" type="text/javascript"></script>
		<form method="post" action="'.BURL('robot/save').'">';

		TableHeader('添加自动回复:');

		TableRow(array('<b>提示:</b>', '<font class="orange" style="font-size:16px;line-height:26px;">1. 新添加或重新编辑后的自动回复后, 需要在无人值守状态关闭后再重新开启才能生效.<br>2. 关键字个数越多的自动回复内容, 优先匹配. 关键字个数相同的回复内容, 排序数字大的先匹配. 排序数字相同时ID大的(即后添加的)先匹配.<br>3. 所以有时添加了自动回复, 但在列表页首页看不到, 那是因为它排序在后面的分页中.<br>4. 如果关键字是: aa|bb, 表示匹配aa或bb, 就自动回复本条内容, 算1个关键字; 如果关键字是: xx|yy, zz, 算2个关键字, 匹配xx, zz或yy, zz均算成功, 以此类推. <br>5. 注意关键字里的特别字符“|”和“,”必须是英文标点符号才有效.<br>6. 机器人自动回复内容对所有客服组均有效，但各客服组的无人值守状态是独立设置的.</font>'));

		TableRow(array('<b>关键字:</b>', '<input type="text" name="keyword" value="" size="60">' . $need_info . '&nbsp;&nbsp;&nbsp;<font class=grey>(注: 多个关键字请用英文逗号隔开, "或"的情况用英文"|"号隔开. )</font>'));
		TableRow(array('<b>回复内容:</b>', '<textarea name="msg" class="msg_tip" id="input_msg" style="width:658px;height:40px;" ></textarea>' . $need_info));

		TableRow(array('<b>变换头像:</b>', '<select name="avatar" class="robot_avatar_add">
					<option value="0">默认&nbsp;</option>
					<option value="1" data-icon="'. SYSDIR .'avatar/robot/1.png">&nbsp;</option>
					<option value="2" data-icon="'. SYSDIR .'avatar/robot/2.png">&nbsp;</option>
					<option value="3" data-icon="'. SYSDIR .'avatar/robot/3.png">&nbsp;</option>
					<option value="4" data-icon="'. SYSDIR .'avatar/robot/4.png">&nbsp;</option>
					<option value="5" data-icon="'. SYSDIR .'avatar/robot/5.png">&nbsp;</option>
					<option value="6" data-icon="'. SYSDIR .'avatar/robot/6.png">&nbsp;</option>
					<option value="7" data-icon="'. SYSDIR .'avatar/robot/7.png">&nbsp;</option>
					<option value="8" data-icon="'. SYSDIR .'avatar/robot/8.png">&nbsp;</option>
					<option value="9" data-icon="'. SYSDIR .'avatar/robot/9.png">&nbsp;</option>
					<option value="10" data-icon="'. SYSDIR .'avatar/robot/10.png">&nbsp;</option>
					<option value="11" data-icon="'. SYSDIR .'avatar/robot/11.png">&nbsp;</option>
					<option value="12" data-icon="'. SYSDIR .'avatar/robot/12.png">&nbsp;</option>
					<option value="13" data-icon="'. SYSDIR .'avatar/robot/13.png">&nbsp;</option>
					<option value="14" data-icon="'. SYSDIR .'avatar/robot/14.png">&nbsp;</option>
				</select>'));



		TableFooter();

		PrintSubmit('添加自动回复');

		$this->print_script();
	}


	//批量更新自动回复
	public function updaterobot(){
		$page = ForceIntFrom('p', 1);   //页码
		$search = ForceStringFrom('s');
		$groupid = ForceIntFrom('g');
		$order = ForceStringFrom('o');

		if(IsPost('updaterobot')){
			$sorts   = $_POST['sorts'];
			$activateds   = $_POST['activateds'];
			$msgs   = $_POST['msgs'];
			$keywords   = $_POST['keywords'];
			$avatars   = $_POST['avatars'];
			$ids = Iif(isset($_POST['ids']), $_POST['ids'], array());

			for($i = 0; $i < count($ids); $i++){
				$id = ForceInt($ids[$i]);
				$kn = count(explode(',', $keywords[$i]));

				$keyword = trim(ForceString($keywords[$i]), ",");
				$msg = ForceString($msgs[$i]);

				if(!$keyword OR !$msg) continue;

				APP::$DB->exe("UPDATE " . TABLE_PREFIX . "robot SET activated = '" . ForceInt($activateds[$i]) . "',
					sort = '" . ForceInt($sorts[$i]) . "',
					kn = '" . $kn . "',
					keyword = '" . $keyword . "',
					msg = '" . $msg . "',
					avatar = '" . ForceInt($avatars[$i]) . "' 
					WHERE id = '$id'");

				//根据关键词删除匹配成功的机器人无解记录
				$this->delete_robotmsg($keyword);

			}

		}else{
			$deleteids = Iif(isset($_POST['deleteids']), $_POST['deleteids'], array());

			for($i = 0; $i < count($deleteids); $i++){
				$id = ForceInt($deleteids[$i]);
				APP::$DB->exe("DELETE FROM " . TABLE_PREFIX . "robot WHERE id = '$id'");
			}
		}

		Success('robot?p=' . $page. FormatUrlParam(array('s'=>urlencode($search), 'g'=>$groupid, 'o'=>$order)));
	}


	public function index(){
		$NumPerPage = 10;
		$page = ForceIntFrom('p', 1);
		$search = ForceStringFrom('s');
		$groupid = ForceStringFrom('g');

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
				$orderby = " kn DESC, sort DESC, id DESC ";			
				$order = "sort.down";
				break;
		}


		SubMenu('机器人客服管理', array(array('机器人自动回复列表', 'robot', 1), array('添加自动回复', 'robot/add'), array('修改机器人信息', '')));

		TableHeader('搜索自动回复');

		TableRow('<center><form method="post" action="'.BURL('robot').'" name="searchrobot" style="display:inline-block;"><label>关键字:</label>&nbsp;<input type="text" name="s" size="14"  value="'.$search.'">&nbsp;&nbsp;&nbsp;&nbsp;<label>状态:</label>&nbsp;<select name="g"><option value="0">全部</option><option value="1" ' . Iif($groupid == '1', 'SELECTED') . '>可用</option><option value="2" ' . Iif($groupid == '2', 'SELECTED') . ' class=red>已禁用</option></select>&nbsp;&nbsp;&nbsp;&nbsp;<input type="submit" value="搜索自动回复" class="cancel"></form></center>');
		
		TableFooter();

		if($search){
			$searchsql = " WHERE (keyword LIKE '%$search%' OR msg LIKE '%$search%') ";
			$title = "搜索: <span class=note>$search</span> 的自动回复列表";

			if($groupid) {
				if($groupid == 1 OR $groupid == 2){
					$searchsql .= " AND activated = " . Iif($groupid == 1, 1, 0)." ";
					$title = "在 <span class=note>" .Iif($groupid == 1, '可用的自动回复', '已禁用的自动回复'). "</span> 中, " . $title;
				}
			}
		}else if($groupid){
			if($groupid == 1 OR $groupid == 2){
				$searchsql .= " WHERE activated = " . Iif($groupid == 1, 1, 0)." ";
				$title = "全部 <span class=note>" .Iif($groupid == 1, '可用的自动回复', '已禁用的自动回复'). "</span> 列表";
			}
		}else{
			$searchsql = '';
			$title = '全部自动回复列表';
		}

		$getrobot = APP::$DB->query("SELECT * FROM " . TABLE_PREFIX . "robot ".$searchsql." ORDER BY {$orderby} LIMIT $start,$NumPerPage");

		$maxrows = APP::$DB->getOne("SELECT COUNT(id) AS value FROM " . TABLE_PREFIX . "robot ".$searchsql);

		echo '<link rel="stylesheet" type="text/css" href="'. SYSDIR .'public/jquery.wSelect.css">
		<script src="'. SYSDIR .'public/jquery.wSelect.js" type="text/javascript"></script>
		<form method="post" action="'.BURL('robot/updaterobot').'" name="robotsform">
		<input type="hidden" name="p" value="'.$page.'">
		<input type="hidden" name="s" value="'.$search.'">
		<input type="hidden" name="g" value="'.$groupid.'">
		<input type="hidden" name="o" value="'.$order.'">';

		TableHeader($title.'('.$maxrows['value'].'个)');

		echo '<tr class="tr0"><td class="td"><a class="do-sort" for="sort">排序</a></td><td class="td"><a class="do-sort" for="activated">状态</a></td><td class="td" width="2%">关键字</td><td class="td" width="10%">回复内容</td><td class="td" width="10%">变换头像</td><td class="td">保存更新</td><td class="td last"><input type="checkbox" id="checkAll" for="deleteids[]"> <label for="checkAll">删除</label></td></tr>';

		TableRow(array('<input type="hidden" name="new_id" value="0"><input type="text" size="4" name="sort">',
		'<select disabled><option value="1">可用</option></select>',
		'<input type="text" name="keyword" value="" size="40">',
		'<textarea name="msg" style="width:500px;height:40px;padding:0 6px;" class="msg_tip" id="input_0"></textarea>',
		'<select name="avatar" class="robot_avatar">
			<option value="1" data-icon="'. SYSDIR .'avatar/robot/1.png">&nbsp;</option>
			<option value="2" data-icon="'. SYSDIR .'avatar/robot/2.png">&nbsp;</option>
			<option value="3" data-icon="'. SYSDIR .'avatar/robot/3.png">&nbsp;</option>
			<option value="4" data-icon="'. SYSDIR .'avatar/robot/4.png">&nbsp;</option>
			<option value="5" data-icon="'. SYSDIR .'avatar/robot/5.png">&nbsp;</option>
			<option value="6" data-icon="'. SYSDIR .'avatar/robot/6.png">&nbsp;</option>
			<option value="7" data-icon="'. SYSDIR .'avatar/robot/7.png">&nbsp;</option>
			<option value="8" data-icon="'. SYSDIR .'avatar/robot/8.png">&nbsp;</option>
			<option value="9" data-icon="'. SYSDIR .'avatar/robot/9.png">&nbsp;</option>
			<option value="10" data-icon="'. SYSDIR .'avatar/robot/10.png">&nbsp;</option>
			<option value="11" data-icon="'. SYSDIR .'avatar/robot/11.png">&nbsp;</option>
			<option value="12" data-icon="'. SYSDIR .'avatar/robot/12.png">&nbsp;</option>
			<option value="13" data-icon="'. SYSDIR .'avatar/robot/13.png">&nbsp;</option>
			<option value="14" data-icon="'. SYSDIR .'avatar/robot/14.png">&nbsp;</option>
			<option value="0" selected="selected">默认&nbsp;</option>
		</select>',
		'<img src="'. SYSDIR .'public/img/add.png" class="add_item" style="width:26px;cursor: pointer;" title="添加自动回复">',
		'<input type="checkbox" disabled>'));

		if($maxrows['value'] < 1){
			TableRow('<center><BR><font class=redb>未搜索到任何自动回复!</font><BR><BR></center>');
		}else{


			while($phrase = APP::$DB->fetch($getrobot)){
				TableRow(array('<input type="hidden" name="ids[]" value="'.$phrase['id'].'"><input type="text" name="sorts[]" value="' . $phrase['sort'] . '" size="4">',

				'<select name="activateds[]"' . Iif(!$phrase['activated'], ' class=red'). '><option value="1">可用</option><option class="red" value="0" ' . Iif(!$phrase['activated'], 'SELECTED') . '>禁用</option></select>',

				'<input type="text" name="keywords[]" value="' . $phrase['keyword'] . '" size="40">',

				'<textarea name="msgs[]" class="msg_tip" id="input_' . $phrase['id'] . '" style="width:500px;height:40px;padding:0 6px;">' . $phrase['msg'] . '</textarea>',

				'<select name="avatars[]" class="robot_avatar">
					<option value="1" data-icon="'. SYSDIR .'avatar/robot/1.png"' . Iif($phrase['avatar'] == 1, ' selected="selected"') . '>&nbsp;</option>
					<option value="2" data-icon="'. SYSDIR .'avatar/robot/2.png"' . Iif($phrase['avatar'] == 2, ' selected="selected"') . '>&nbsp;</option>
					<option value="3" data-icon="'. SYSDIR .'avatar/robot/3.png"' . Iif($phrase['avatar'] == 3, ' selected="selected"') . '>&nbsp;</option>
					<option value="4" data-icon="'. SYSDIR .'avatar/robot/4.png"' . Iif($phrase['avatar'] == 4, ' selected="selected"') . '>&nbsp;</option>
					<option value="5" data-icon="'. SYSDIR .'avatar/robot/5.png"' . Iif($phrase['avatar'] == 5, ' selected="selected"') . '>&nbsp;</option>
					<option value="6" data-icon="'. SYSDIR .'avatar/robot/6.png"' . Iif($phrase['avatar'] == 6, ' selected="selected"') . '>&nbsp;</option>
					<option value="7" data-icon="'. SYSDIR .'avatar/robot/7.png"' . Iif($phrase['avatar'] == 7, ' selected="selected"') . '>&nbsp;</option>
					<option value="8" data-icon="'. SYSDIR .'avatar/robot/8.png"' . Iif($phrase['avatar'] == 8, ' selected="selected"') . '>&nbsp;</option>
					<option value="9" data-icon="'. SYSDIR .'avatar/robot/9.png"' . Iif($phrase['avatar'] == 9, ' selected="selected"') . '>&nbsp;</option>
					<option value="10" data-icon="'. SYSDIR .'avatar/robot/10.png"' . Iif($phrase['avatar'] == 10, ' selected="selected"') . '>&nbsp;</option>
					<option value="11" data-icon="'. SYSDIR .'avatar/robot/11.png"' . Iif($phrase['avatar'] == 11, ' selected="selected"') . '>&nbsp;</option>
					<option value="12" data-icon="'. SYSDIR .'avatar/robot/12.png"' . Iif($phrase['avatar'] == 12, ' selected="selected"') . '>&nbsp;</option>
					<option value="13" data-icon="'. SYSDIR .'avatar/robot/13.png"' . Iif($phrase['avatar'] == 13, ' selected="selected"') . '>&nbsp;</option>
					<option value="14" data-icon="'. SYSDIR .'avatar/robot/14.png"' . Iif($phrase['avatar'] == 14, ' selected="selected"') . '>&nbsp;</option>
					<option value="0"' . Iif(!$phrase['avatar'], ' selected="selected"') . '>默认&nbsp;</option>
				</select>',

				'<img src="'. SYSDIR .'public/img/save.png" class="save_item" style="width:26px;cursor: pointer;" title="保存更新">',

				'<input type="checkbox" name="deleteids[]" value="' . $phrase['id'] . '">'));
			}

			$totalpages = ceil($maxrows['value'] / $NumPerPage);

			if($totalpages > 1){
				TableRow(GetPageList(BURL('robot'), $totalpages, $page, 10, 's', urlencode($search), 'g', $groupid, 'o', $order));
			}

		}

		TableFooter();

		echo '<div class="submit"><input type="submit" name="updaterobot" value="保存更新" class="cancel" style="margin-right:28px"><input type="submit" name="deleterobot" value="删除自动回复" class="save" onclick="var _me=$(this);showDialog(\'确定删除所选自动回复吗?\', \'确认操作\', function(){_me.closest(\'form\').submit();});return false;"></div></form>
		<script type="text/javascript">
			$(function(){
				var url = "' . BURL("robot") . FormatUrlParam(array('p'=>$page, 's'=>urlencode($search), 'g'=>$groupid)) . '";

				format_sort(url, "' . $order . '");
			});		
		</script>';

		$this->print_script();
	}


	private function print_script(){
		$this->smilies = ''; //表情图标
		for($i = 0; $i < 24; $i++){
			$this->smilies .= '<img src="' . SYSDIR . 'public/smilies/' . $i . '.png" onclick="insertSmilie(' . $i . ', \'towhere\');">';
		}

		echo '<div id="robot_div" class="robot_div" style="display:none;">
			<div style="text-align:center;">服务访客总人数：<b id="robot_guest" style="color:red;font-size:26px;">0</b><i style="margin-left:30px;"></i>机器人评价得分：<b id="robot_score" style="color:red;font-size:26px;">0</b><i style="margin-left:30px;"></i>评价总人数：<b id="robot_ratings" style="color:red;font-size:26px;">0</b></div>
			<form id="robot_info">
			<div><b class=grey>机器人名称(中文)：</b><input type="text" name="RobotName" value="' . APP::$_CFG['RobotName'] .  '" size="14"><i></i><b class=grey>机器人名称(English)：</b><input type="text" name="RobotName_en" value="' . APP::$_CFG['RobotName_en'] .  '" size="24"></div>
			<div><b class=grey>机器人职位(中文)：</b><input type="text" name="RobotPost" value="' . APP::$_CFG['RobotPost'] .  '" size="14"><i></i><b class=grey>机器人职位(English)：</b><input type="text" name="RobotPost_en" value="' . APP::$_CFG['RobotPost_en'] .  '" size="24"></div>
			<div style="color:red;height:40px;line-height:40px;">注：机器人信息修改后，需要重启workerman才能生效</div>
			<div class=btn><input type="submit" value="保存更新" class="save" id="robot_save_btn"><input type="submit" value=" 取 消 " class="cancel" id="robot_cancel_btn"></div>
			</form>		
		</div>
		<div class="smilies_div" style="display:none"><div class="smilies_wrap">' . $this->smilies . '</div></div>
		<script type="text/javascript">

			$(function(){
				$(".robot_avatar").wSelect();
				$(".robot_avatar_add").wSelect({direction:"right"});	

				$(".msg_tip").tipTip({content: "", keepAlive:true, activation:"click", maxWidth:"380px", defaultPosition:"top", edgeOffset:-1,
					enter:function(id){
						var content = $(".smilies_div").html().replace(/towhere/ig, id);
						$("#tiptip_content").html(content);
					}
				});

				$(".link-btn:last").click(function(e){
					if($("#robot_score").html() == "0"){
						ajax("' . BURL('robot/ajax?action=get_robot_score') . '", "", function(data){
							$("#robot_guest").html(data.guest);
							$("#robot_score").html(data.score);
							$("#robot_ratings").html(data.num);							
						});
					}

					$("#robot_div").hide().slideDown(200);
					e.preventDefault();
					return false;
				});

				$(document).click(function(e){
					$("#robot_div").hide();
				});

				$("#robot_div").click(function(e){
					e.preventDefault();
					return false;
				});


				$("#robot_cancel_btn").click(function(e){
					$("#robot_div").hide();
					e.preventDefault();
					return false;
				});

				$("#robot_save_btn").click(function(e){
					ajax("' . BURL('robot/ajax?action=set_robot') . '", $("#robot_info").serialize(), function(data){
						//$(this).hide();

						showInfo("机器人信息设置成功.", "Ajax操作", "", 2, 1);
					});

					e.preventDefault();
					return false;
				});

				//添加自动回复
				$(".add_item").click(function(e){
					var obj = $(this);
					var item = $(this).parent().parent();

					var id = item.find("input:first").val();
					var sort = $.trim(item.find("[name=\'sort\']").val());
					var keyword = $.trim(item.find("[name=\'keyword\']").val());
					var msg = $.trim(item.find("[name=\'msg\']").val());
					var avatar = item.find("[name=\'avatar\']").val();

					if(keyword == "" || msg == ""){
						showInfo("请填写关键字和自动回复内容.", "", "", 2);
					}else{

						if(!ajax_isOk) return false;

						obj.attr("src", "'. SYSDIR .'public/img/saving.gif");

						$.ajax({
							url: "' . BURL('robot/ajax?action=save_robot') . '",
							data: {id:id, sort:sort, keyword:keyword, msg:msg, avatar:avatar},
							type: "post",
							cache: false,
							dataType: "json",
							beforeSend: function(){ajax_isOk = 0;},
							complete: function(){ajax_isOk = 1;},
							success: function(data){

								if(data.s == 0){
									obj.attr("src", "'. SYSDIR .'public/img/add.png");
									showInfo(data.i, "Ajax操作失败");
									return false;
								}

								item.find("[name=\'sort\']").val("");
								item.find("[name=\'keyword\']").val("");
								item.find("[name=\'msg\']").val("");

								item.find(".robot_avatar").find("option:last").prop("selected", true);
								item.find(".wSelect-selected").removeClass("wSelect-option-icon").prop("style", "").html("默认&nbsp;");
								item.find(".wSelect-option-selected").removeClass("wSelect-option-selected");
								item.find(".wSelect-option-last").addClass("wSelect-option-selected");

								var id= data.i;
								var av = data.av;
								var sort = data.sort;

								item.after(\'<tr><td class="td"><input type="hidden" name="ids[]" value="\' + id + \'"><input type="text" name="sorts[]" value="\' + sort + \'" size="4"></td>\' + 
								\'<td class="td"><select name="activateds[]"><option value="1">可用</option><option class="red" value="0">禁用</option></select></td>\' + 
								\'<td class="td"><input type="text" name="keywords[]" value="\' + keyword + \'" size="40"></td>\' + 
								\'<td class="td"><textarea name="msgs[]" class="msg_tip" id="input_\' + id + \'" style="width:500px;height:40px;padding:0 6px;">\' + msg + \'</textarea></td>\' + 
								\'<td class="td"><select name="avatars[]" class="robot_avatar" id="robot_avatar_\' + id + \'">\' + 
									\'<option value="1" data-icon="'. SYSDIR .'avatar/robot/1.png" \' +  (av == 1? " selected=selected" : "")  + \'>&nbsp;</option>\' + 
									\'<option value="2" data-icon="'. SYSDIR .'avatar/robot/2.png" \' +  (av == 2? " selected=selected" : "")  + \'>&nbsp;</option>\' + 
									\'<option value="3" data-icon="'. SYSDIR .'avatar/robot/3.png" \' +  (av == 3? " selected=selected" : "")  + \'>&nbsp;</option>\' + 
									\'<option value="4" data-icon="'. SYSDIR .'avatar/robot/4.png" \' +  (av == 4? " selected=selected" : "")  + \'>&nbsp;</option>\' + 
									\'<option value="5" data-icon="'. SYSDIR .'avatar/robot/5.png" \' +  (av == 5? " selected=selected" : "")  + \'>&nbsp;</option>\' + 
									\'<option value="6" data-icon="'. SYSDIR .'avatar/robot/6.png" \' +  (av == 6? " selected=selected" : "")  + \'>&nbsp;</option>\' + 
									\'<option value="7" data-icon="'. SYSDIR .'avatar/robot/7.png" \' +  (av == 7? " selected=selected" : "")  + \'>&nbsp;</option>\' + 
									\'<option value="8" data-icon="'. SYSDIR .'avatar/robot/8.png" \' +  (av == 8? " selected=selected" : "")  + \'>&nbsp;</option>\' + 
									\'<option value="9" data-icon="'. SYSDIR .'avatar/robot/9.png" \' +  (av == 9? " selected=selected" : "")  + \'>&nbsp;</option>\' + 
									\'<option value="10" data-icon="'. SYSDIR .'avatar/robot/10.png" \' +  (av == 10? " selected=selected" : "")  + \'>&nbsp;</option>\' + 
									\'<option value="11" data-icon="'. SYSDIR .'avatar/robot/11.png" \' +  (av == 11? " selected=selected" : "")  + \'>&nbsp;</option>\' + 
									\'<option value="12" data-icon="'. SYSDIR .'avatar/robot/12.png" \' +  (av == 12? " selected=selected" : "")  + \'>&nbsp;</option>\' + 
									\'<option value="13" data-icon="'. SYSDIR .'avatar/robot/13.png" \' +  (av == 13? " selected=selected" : "")  + \'>&nbsp;</option>\' + 
									\'<option value="14" data-icon="'. SYSDIR .'avatar/robot/14.png" \' +  (av == 14? " selected=selected" : "")  + \'>&nbsp;</option>\' + 
									\'<option value="0" \' +  (av == 0? " selected=selected" : "")  + \'>默认&nbsp;</option>\' + 
								\'</select></td>\' + 
								\'<td class="td"><img src="'. SYSDIR .'public/img/save.png" class="save_item" id="save_item_\' + id + \'" style="width:26px;cursor: pointer;" title="保存更新"></td>\' + 
								\'<td class="td last"><input type="checkbox" name="deleteids[]" value="\' + id + \'"></td>\' + 
								\'</tr>\');

								$("#robot_avatar_" + id).wSelect();
								$("#save_item_" + id).click(function(e){
									save_item($(this));

									e.preventDefault();
									return false;
								});

								$("#input_" + id).tipTip({content: "", keepAlive:true, activation:"click", maxWidth:"380px", defaultPosition:"top", edgeOffset:-1,
									enter:function(id){
										var content = $(".smilies_div").html().replace(/towhere/ig, id);
										$("#tiptip_content").html(content);
									}
								});

								obj.attr("src", "'. SYSDIR .'public/img/add.png");

							},
							error: function(XHR, Status, Error) {
								showInfo("数据: " + XHR.responseText + "<br>状态: " + Status + "<br>错误: " + Error + "<br>", "Ajax错误");
							}
						});
					}

					e.preventDefault();
					return false;
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

					var id = item.find("input:first").val();
					var sort = item.find("[name=\'sorts[]\']").val();
					var activated = item.find("[name=\'activateds[]\']").val();
					var keyword = item.find("[name=\'keywords[]\']").val();
					var msg = item.find("[name=\'msgs[]\']").val();
					var avatar = item.find("[name=\'avatars[]\']").val();

					ajax("' . BURL('robot/ajax?action=save_robot') . '", {id:id, sort:sort, activated:activated, keyword:keyword, msg:msg, avatar:avatar}, function(data){
						setTimeout(function(){
							obj.attr("src", "'. SYSDIR .'public/img/save.png");
						}, 500); //0.5秒切换, 否则太快没效果
					});
				}


			});
		</script>';

	}


	//根据关键词删除匹配成功的机器人无解记录
	private function delete_robotmsg($keyword){

		if(!$keyword) return false;

		$word_arr = explode(',', $keyword); //先将关键字切成数组
		$query_str = "";

		foreach($word_arr AS $word){

			$word = trim($word);
			if(!$word) continue;

			$query_str_in = "";
			$w_arr = explode('|', $word); //切成数组

			foreach($w_arr AS $item){

				$item = trim($item);
				if(!$item) continue;

				$query_str_in .= " msg LIKE '%$item%' OR ";
			}

			$query_str_in = trim(trim($query_str_in), "OR");

			if($query_str_in){
				$query_str .= " ($query_str_in) AND ";
			}
		}

		$query_str = trim(trim($query_str), "AND");

		if($query_str){
			APP::$DB->exe("DELETE FROM " . TABLE_PREFIX . "robotmsg WHERE " . $query_str);
		}
	}

} 

?>