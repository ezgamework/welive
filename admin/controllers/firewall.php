<?php if(!defined('ROOT')) die('Access denied.');

class c_firewall extends Admin{

	public function __construct($path){
		parent::__construct($path);

	}


	//ajax动作集合, 通过action判断具体任务
    public function ajax(){

		//ajax权限验证
		if(!$this->CheckAccess()){
			$this->ajax['s'] = 0; //ajax操作失败
			$this->ajax['i'] = '您没有权限管理封禁IP地址!';
			die($this->json->encode($this->ajax));
		}
		
		$myid = $this->admin['aid'];
		$action = ForceStringFrom('action');

		//新增或保存更新单条记录
		if($action == 'save_firewall'){

			$fid = ForceIntFrom('fid');
			$ip = ForceStringFrom('ip');
			$expire = ForceStringFrom('expire');

			if(!$ip OR !preg_match("/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/", $ip)){
				$this->ajax['s'] = 0; //ajax操作失败
				$this->ajax['i'] = 'IP地址不规范!';
				die($this->json->encode($this->ajax));
			}

			if(!$expire){
				$this->ajax['s'] = 0;
				$this->ajax['i'] = '封禁日期未填写!';
				die($this->json->encode($this->ajax));
			}

			if(APP::$DB->getOne("SELECT fid FROM " . TABLE_PREFIX . "firewall WHERE ip = '$ip' AND fid <> $fid ")){
				$this->ajax['s'] = 0;
				$this->ajax['i'] = '封禁的IP地址已存在, 请勿重复设置!';
				die($this->json->encode($this->ajax));
			}

			//处理封禁日期
			ini_set('date.timezone', 'GMT'); //先设置为格林威治时区, 时区会影响strtotime函数将日期转为时间戳
			$expire = intval(strtotime($expire)) - 3600 * intval(APP::$_CFG['Timezone']) + 86398; //再根据welive设置的时区转为UNIX时间戳 + 24小时 - 2秒

			$time_now = time();
			$this->ajax['status'] = Iif($time_now <= $expire, '<font class=red>封禁中</font>', '<font class=grey>已失效</font>');
			$this->ajax['time'] = DisplayDate($time_now, '', 1);

			if(!$fid){

				APP::$DB->exe("INSERT INTO " . TABLE_PREFIX . "firewall (aid, bans, ip, time, expire) VALUES ('$myid', 0, '$ip', '$time_now', '$expire')");

				$lastid = APP::$DB->insert_id;
				$this->ajax['fid'] = $lastid;

			}else{
				APP::$DB->exe("UPDATE " . TABLE_PREFIX . "firewall SET aid = '{$myid}', ip = '{$ip}', time = '{$time_now}', expire = '{$expire}' WHERE fid = '{$fid}'");
			}

		}

		//删除单条记录
		if($action == 'delete_firewall'){
			$fid = ForceIntFrom('fid');

			APP::$DB->exe("DELETE FROM " . TABLE_PREFIX . "firewall WHERE fid = '{$fid}'");
		}

		die($this->json->encode($this->ajax));
	}


	public function index(){
		$this->CheckAction();

		$myid = $this->admin['aid'];
		$myname = $this->admin['fullname'];

		$NumPerPage = 10;
		$page = ForceIntFrom('p', 1);
		$search = ForceStringFrom('s');
		$groupid = ForceStringFrom('g');
		$time = ForceStringFrom('t');

		if(IsGet('s')) $search = urldecode($search);

		if($time){
			ini_set('date.timezone', 'GMT'); //先设置为格林威治时区, 时区会影响strtotime函数将日期转为时间戳
			$start_time = intval(strtotime($time)) - 3600 * intval(APP::$_CFG['Timezone']); //再根据welive设置的时区转为UNIX时间戳
			$end_time = $start_time + 86400;
		}

		$start = $NumPerPage * ($page-1);

		$time_now = time();

		//排序
		$order = ForceStringFrom('o');
        switch($order)
        {
            case 'aid.down':
				$orderby = " aid DESC ";
				break;

            case 'aid.up':
				$orderby = " aid ASC ";
				break;

            case 'time.down':
				$orderby = " time DESC ";
				break;

            case 'time.up':
				$orderby = " time ASC ";
				break;

            case 'bans.down':
				$orderby = " bans DESC ";
				break;

            case 'bans.up':
				$orderby = " bans ASC ";
				break;

            case 'expire.down':
				$orderby = " expire DESC ";
				break;

            case 'expire.up':
				$orderby = " expire ASC ";
				break;

            case 'fid.up':
				$orderby = " fid ASC ";
				break;

			default:
				$orderby = " fid DESC ";			
				$order = "fid.down";
				break;
		}

		$admins = array();
		$getadmins = APP::$DB->query("SELECT aid, fullname FROM " . TABLE_PREFIX . "admin WHERE type = 1");
		while($a = APP::$DB->fetch($getadmins)){
			$admins[$a['aid']] = $a['fullname'];
		}

		SubMenu('防火墙管理', array(array('封禁IP地址列表', 'firewall', 1)));

		TableHeader('搜索封禁IP地址');

		TableRow('<center><form method="post" action="'.BURL('firewall').'" name="searchfirewall" style="display:inline-block;"><label>关键字:</label>&nbsp;<input type="text" name="s" size="14"  value="'.$search.'">&nbsp;&nbsp;&nbsp;<label>状态:</label>&nbsp;<select name="g"><option value="0">全部</option><option value="1" ' . Iif($groupid == '1', 'SELECTED') . ' class=red>封禁中</option><option value="2" ' . Iif($groupid == '2', 'SELECTED') . '>已失效</option></select>&nbsp;&nbsp;&nbsp;<label>日期:</label>&nbsp;<input type="text" name="t" class="date-input" value="' . $time . '" size="8">&nbsp;&nbsp;&nbsp;&nbsp;<input type="submit" value="搜索IP地址" class="cancel"></form></center>');
		
		TableFooter();

		if($search){
			if(preg_match("/^[1-9][0-9]*$/", $search)){
				$s = ForceInt($search);
				$searchsql = " WHERE (aid = '$s' OR bans = '$s' OR fid = '$s') "; //数字搜索
				$title = "搜索数字为: <span class=note>$s</span> 的IP地址列表";
			}else{
				$searchsql = " WHERE (ip LIKE '%$search%') ";
				$title = "搜索: <span class=note>$search</span> 的IP地址列表";
			}

			if($groupid) {

				if($groupid == 1){
					$searchsql .= " AND expire >= '$time_now' ";
					$title = "在 <span class=note>封禁中的IP地址</span> 中, " . $title;
				}else{
					$searchsql .= " AND expire < '$time_now' ";
					$title = "在 <span class=note>封禁已失效的IP地址</span> 中, " . $title;
				}
			}

			if($time) {
				$searchsql .= " AND ((time >= '$start_time' AND time < '$end_time') OR (expire >= '$start_time' AND expire < '$end_time')) ";
			}

		}else if($groupid){

			if($groupid == 1){
				$searchsql .= " WHERE expire >= '$time_now' ";
				$title = "全部 <span class=note>封禁中的IP地址</span> 列表";
			}else{
				$searchsql .= " WHERE expire < '$time_now' ";
				$title = "全部 <span class=note>封禁已失效的IP地址</span> 列表";
			}

			if($time) {
				$searchsql .= " AND ((time >= '$start_time' AND time < '$end_time') OR (expire >= '$start_time' AND expire < '$end_time')) ";
			}

		}else if($time){
			$searchsql .= " WHERE (time >= '$start_time' AND time < '$end_time') OR (expire >= '$start_time' AND expire < '$end_time') ";
			$title = "搜索日期: <span class=note>{$time}</span> 的IP地址列表";
		}else{
			$searchsql = '';
			$title = '全部封禁IP地址列表';
		}

		$getfirewall = APP::$DB->query("SELECT * FROM " . TABLE_PREFIX . "firewall ".$searchsql." ORDER BY {$orderby} LIMIT $start,$NumPerPage");

		$maxrows = APP::$DB->getOne("SELECT COUNT(fid) AS value FROM " . TABLE_PREFIX . "firewall ".$searchsql);

		echo '<script type="text/javascript" src="'.SYSDIR.'public/laydate/laydate.js"></script>';

		TableHeader($title.'('.$maxrows['value'].'个)');

		TableRow(array('<a class="do-sort" for="fid">id</a>', 'IP地址', '<a class="do-sort" for="expire">封禁至日期</a>', '状态', '保存更新', '删除', '<a class="do-sort" for="bans">封禁次数</a>', '<a class="do-sort" for="aid">管理员</a>', '<a class="do-sort" for="time">编辑时间</a>'), 'tr0');

		TableRow(array('<input type="hidden" name="fid" value="0">&nbsp;',
		'<input type="text" name="ip" value="" size="14">&nbsp;<font class=red>*</font>',
		'<input type="text" size="10" name="expire" class="date-input">&nbsp;<font class=red>*</font>',
		'&nbsp;',
		'<img src="'. SYSDIR .'public/img/add.png" class="add_item" style="width:26px;cursor: pointer;" title="添加封禁IP地址">',
		'&nbsp;',
		'&nbsp;',
		'&nbsp;',
		'&nbsp;'));

		if($maxrows['value'] < 1){
			TableRow('<center><BR><font class=redb>未搜索到任何封禁IP地址!</font><BR><BR></center>');
		}else{

			while($firewall = APP::$DB->fetch($getfirewall)){
				TableRow(array('<input type="hidden" name="fid" value="'.$firewall['fid'].'"><font class=grey>' . $firewall['fid'] . '</font>',

				'<input type="text" name="ip" value="' . $firewall['ip'] . '" size="14">',

				'<input type="text" size="10" name="expire" value="'.DisplayDate($firewall['expire']).'" class="date-input">',

				Iif($time_now <= $firewall['expire'], '<font class=red>封禁中</font>', '<font class=grey>已失效</font>'),

				'<img src="'. SYSDIR .'public/img/save.png" class="save_item" style="width:26px;cursor: pointer;" title="保存更新">',

				'<img src="'. SYSDIR .'public/img/delete.png" class="delete_item" style="width:26px;cursor: pointer;" title="册除IP">',

				$firewall['bans'],

				Iif(isset($admins[$firewall['aid']]), '<a title="编辑" href="'.BURL('users/edit?aid='.$firewall['aid']).'"><img src="' . GetAvatar($firewall['aid']) . '" class="avatar wh30">' .$admins[$firewall['aid']] . '</a>', '<font class=grey>未知</font>'),

				DisplayDate($firewall['time'], '', 1)));
			}

			$totalpages = ceil($maxrows['value'] / $NumPerPage);

			if($totalpages > 1){
				TableRow(GetPageList(BURL('firewall'), $totalpages, $page, 10, 's', urlencode($search), 'g', $groupid, 't', $time, 'o', $order));
			}

		}

		TableFooter();

		echo '<script type="text/javascript">

			$(function(){
				var url = "' . BURL("firewall") . FormatUrlParam(array('p'=>$page, 's'=>urlencode($search), 'g'=>$groupid, 't'=>$time)) . '";

				format_sort(url, "' . $order . '");

				//日期选择器
				$(".date-input").each(function(){
					laydate.render({
						elem: this
					});
				});

				//添加ip
				$(".add_item").click(function(e){
					var obj = $(this);
					var item = obj.parent().parent();

					var fid = item.find("[name=\'fid\']").val();
					var ip = $.trim(item.find("[name=\'ip\']").val());
					var expire = $.trim(item.find("[name=\'expire\']").val());

					if(ip == "" || expire == ""){
						showInfo("请填写IP地址和封禁至日期.", "", "", 2);
					}else{

						if(!ajax_isOk) return false;

						obj.attr("src", "'. SYSDIR .'public/img/saving.gif");

						$.ajax({
							url: "' . BURL('firewall/ajax?action=save_firewall') . '",
							data: {fid:fid, ip:ip, expire:expire},
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

								item.find("[name=\'ip\']").val("");
								item.find("[name=\'expire\']").val("");

								var fid= data.fid;
								var time = data.time;
								var status = data.status;

								item.after(\'<tr><td class="td"><input type="hidden" name="fid" value="\' + fid + \'">\' + fid + \'</td>\' + 
								\'<td class="td"><input type="text" name="ip" value="\' + ip + \'" size="14"></td>\' + 
								\'<td class="td"><input type="text" size="10" name="expire" value="\' + expire + \'" class="date-input-new"></td>\' + 
								\'<td class="td">\' + status + \'</td>\' + 
								\'<td class="td"><img src="'. SYSDIR .'public/img/save.png" class="save_item" id="save_item_\' + fid + \'" style="width:26px;cursor: pointer;" title="保存更新"></td>\' + 
								\'<td class="td"><img src="'. SYSDIR .'public/img/delete.png" class="delete_item" id="delete_item_\' + fid + \'" style="width:26px;cursor: pointer;" title="删除IP"></td>\' + 
								\'<td class="td">0</td>\' + 
								\'<td class="td"><a title="编辑" href="'.BURL('users/edit?aid='.$myid).'"><img src="' . GetAvatar($myid) . '" class="avatar wh30">' .$myname. '</a></td>\' + 
								\'<td class="td">\' + time + \'</td>\' + 
								\'</tr>\');

								$("#save_item_" + fid).click(function(e){
									save_item($(this));

									e.preventDefault();
									return false;
								});

								$("#delete_item_" + fid).click(function(e){
									delete_item($(this));

									e.preventDefault();
									return false;
								});

								obj.attr("src", "'. SYSDIR .'public/img/add.png");

								//日期选择器
								$(".date-input-new").each(function(){
									laydate.render({
										elem: this
									});
								});

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

					var fid = item.find("[name=\'fid\']").val();
					var ip = $.trim(item.find("[name=\'ip\']").val());
					var expire = $.trim(item.find("[name=\'expire\']").val());

					ajax("' . BURL('firewall/ajax?action=save_firewall') . '", {fid:fid, ip:ip, expire:expire}, function(data){
						var status = data.status;
						var time = data.time;

						setTimeout(function(){
							obj.attr("src", "'. SYSDIR .'public/img/save.png");

							obj.parent().prev().html(status);
							obj.parent().next().next().next().next().html(time);

						}, 300); //0.3秒切换, 否则太快没效果
					});
				}

				//删除单条记录
				$(".delete_item").click(function(e){
					delete_item($(this));

					e.preventDefault();
					return false;
				});

				function delete_item(obj){
					obj.attr("src", "'. SYSDIR .'public/img/saving.gif");

					var item = obj.parent().parent();
					var fid = item.find("input:first").val();

					ajax("' . BURL('firewall/ajax?action=delete_firewall') . '", {fid:fid}, function(data){
						item.remove();
					});
				}

			});
		</script>';

	}

} 

?>