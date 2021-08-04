<?php if(!defined('ROOT')) die('Access denied.');

class c_usergroup extends Admin{

	public function __construct($path){
		parent::__construct($path);

		$this->team_filename = ROOT . "config/team_settings.php";

		$this->CheckAction(); //权限验证
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

		//读取客服组配置文件
		$team_settings = @require($this->team_filename);
		$team_id = 0;

		$team_welcomes = array();
		if(isset($team_settings['welcomes'])) $team_welcomes = $team_settings['welcomes'];

		//新增或保存更新单条记录
		if($action == 'save_usergroup'){

			$id = ForceIntFrom('id');
			$sort = ForceIntFrom('sort');
			$activated = ForceIntFrom('activated');
			$groupname = ForceStringFrom('groupname');
			$groupname_en = ForceStringFrom('groupname_en');
			$description = ForceStringFrom('description');

			//处理中英文问候语
			$welcome = $_POST['welcome'];
			$welcome_en = $_POST['welcome_en'];

			$welcome = $this->Clear_string_for_js($welcome); //去掉换行符, 兼容JS变量调用
			$welcome = str_replace('"', "'", $welcome); //将引双引号替换成单引号

			$welcome_en = $this->Clear_string_for_js($welcome_en); //去掉换行符, 兼容JS变量调用
			$welcome_en = str_replace('"', "'", $welcome_en); //将引双引号替换成单引号

			$team_id = $id;

			if(!$id){ //新增
				if(!$groupname){
					$this->ajax['s'] = 0; //ajax操作失败
					$this->ajax['i'] = '请填写客服组名称!';
					die($this->json->encode($this->ajax));
				}

				if(!$groupname_en){
					$this->ajax['s'] = 0; //ajax操作失败
					$this->ajax['i'] = '请填写客服组英文名称!';
					die($this->json->encode($this->ajax));
				}

				APP::$DB->exe("INSERT INTO " . TABLE_PREFIX . "group (sort, activated, groupname, groupname_en, description) VALUES ('$sort', '$activated', '$groupname', '$groupname_en', '$description')");

				$lastid = APP::$DB->insert_id;
				$team_id = $lastid;

				$this->ajax['id'] = $lastid;
				$this->ajax['sort'] = $sort;

				if(!$sort){
					$this->ajax['sort'] = $lastid;
					APP::$DB->exe("UPDATE " . TABLE_PREFIX . "group SET sort = '$lastid' WHERE id = '$lastid'");
				}

			}else{ //保存更新

				if($id == 1) $activated = 1; //默认分类不可禁用

				APP::$DB->exe("UPDATE " . TABLE_PREFIX . "group SET sort = '{$sort}',
					activated = '{$activated}',					
					" . Iif($groupname, "groupname = '{$groupname}', "). "
					" . Iif($groupname_en, "groupname_en = '{$groupname_en}', "). "
					description = '{$description}'
					WHERE id = '{$id}'");
			}

			//更新客服组配置文件中的问候语
			if($team_id){
				$team_welcomes[$team_id] = array("welcome" => "{$welcome}", "welcome_en" => "{$welcome_en}");
			}else{
				$team_id = 0;
			}
		}


		//删除单条记录
		if($action == 'delete_usergroup'){
			$id = ForceIntFrom('id');

			//默认分类不可删除, 有用户的组不可删除
			if($id == 1 OR APP::$DB->getOne("SELECT aid FROM " . TABLE_PREFIX . "admin WHERE grid='$id'")) die($this->json->encode($this->ajax)); 

			APP::$DB->exe("DELETE FROM " . TABLE_PREFIX . "group WHERE id = '{$id}'");

			//删除客服组问候语
			$team_id = $id;
			if(isset($team_welcomes[$team_id])){
				unset($team_welcomes[$team_id]);
			}else{
				$team_id = 0;
			}
		}


		//保存更新新客服组配置文件
		if($team_id){
			//解决PHP7 Opcache开启时无法实时更新设置的问题
			if(function_exists('opcache_reset')) {
				@opcache_reset();
			}

			$team_settings['welcomes'] = $team_welcomes;

			$contents = "<?php

//客服组缓存配置文件

return " . var_export($team_settings, true) . ";


?>";

			@file_put_contents($this->team_filename, $contents, LOCK_EX);
		}


		die($this->json->encode($this->ajax));
	}


	public function index(){

		if(!is_writeable($this->team_filename)){
			$errors = '请将客服组配置文件: <br>config/team_settings.php <br>设置为可写, 即属性设置为: 777';
		}

		if(isset($errors)) Error($errors, '客服组管理错误');
		
		//读取客服组配置文件
		$team_settings = @require($this->team_filename);

		$team_welcomes = array();
		if(isset($team_settings['welcomes'])) $team_welcomes = $team_settings['welcomes'];

		//排序
		$order = ForceStringFrom('o');
        switch($order)
        {
            case 'users.down':
				$orderby = " users DESC ";
				break;

            case 'users.up':
				$orderby = " users ASC ";
				break;

            case 'sort.down':
				$orderby = " g.sort DESC ";
				break;

            case 'sort.up':
				$orderby = " g.sort ASC ";
				break;

            case 'activated.down':
				$orderby = " g.activated DESC ";
				break;

            case 'activated.up':
				$orderby = " g.activated ASC ";
				break;

            case 'id.down':
				$orderby = " g.id DESC ";
				break;

			default:
				$orderby = " g.sort, g.id ";			
				$order = "id.up";
				break;
		}

		SubMenu('客服组管理', array(array('客服组列表', 'usergroup', 1), array('客服管理', 'users')));

		$getusergroups = APP::$DB->query("SELECT g.*, COUNT(a.aid) AS users FROM " . TABLE_PREFIX . "group g LEFT JOIN " . TABLE_PREFIX . "admin a ON (a.grid = g.id) GROUP BY g.id ORDER BY {$orderby}");


		ShowTips('<ul>
		<li><b>状态</b>: 如果某客服组设置为禁用状态，那么此客服组将无法对外提供在线服务，即使该组下的客服已经登录.</li>
		<li><b>成员</b>: 每个客服组至少需要添加一个组长或管理员，否则该组有关设置操作无法完成，如：“开启无人值守”等.</li>
		<li><b>服务</b>: 每个客服组独立对外提供服务，即本组的设置或状态不会影响其它客服组. </li>
		<li><b>调用</b>: 每个客服组均可单独调用，调用代码如：...... welive.js?g=88 或 welive-new.js?g=88，其中88为客服组的id号.</li>
		<li><b>欢迎</b>: 每个客服组均可设置独立的访客问候语(支持html)，如果未设置将显示“系统设置”中设置的问候语.</li>
		</ul>', '客服组说明');
		
		TableHeader('客服组列表');
		TableRow(array('<a class="do-sort" for="id">id</a>', '<a class="do-sort" for="users">客服人数</a>', '<a class="do-sort" for="sort">排序</a>', '<a class="do-sort" for="activated">状态</a>', '名称', '<font class=red>英文</font>名称', '简要描述', '问候语', '<font class=red>英文</font>问候语', '保存更新', '删除'), 'tr0');

		TableRow(array('<input type="hidden" name="id" value="0">&nbsp;',
		'&nbsp;',
		'<input type="text" size="2" name="sort">',
		'<select name="activated"><option value="1">正常</option><option class="red" value="0">禁用</option></select>',
		'<input type="text" name="groupname" value="" size="20">&nbsp;<font class=red>*</font>',
		'<input type="text" name="groupname_en" value="" size="20">&nbsp;<font class=red>*</font>',
		'<input type="text" name="description" value="" size="20">',
		'<textarea style="width:300px;height:60px;" name="welcome"></textarea>',
		'<textarea style="width:300px;height:60px;" name="welcome_en"></textarea>',
		'<img src="'. SYSDIR .'public/img/add.png" class="add_item" style="width:26px;cursor: pointer;" title="添加客服组">',
		'&nbsp;'));

		while($usergroup = APP::$DB->fetch($getusergroups)){
			TableRow(array('<input type="hidden" name="id" value="'.$usergroup['id'].'"><font class=grey>' . $usergroup['id'] . '</font>',

			$usergroup['users'],

			'<input type="text" name="sort" value="' . $usergroup['sort'] . '" size="2">',

			'<select name="activated"' . Iif(!$usergroup['activated'], ' class=red') . Iif($usergroup['id'] == 1, ' disabled') . '><option value="1">正常</option><option class="red" value="0" ' . Iif(!$usergroup['activated'], 'SELECTED') . '>禁用</option></select>',

			'<input type="text" name="groupname" value="' . $usergroup['groupname'] . '" size="20">',

			'<input type="text" name="groupname_en" value="' . $usergroup['groupname_en'] . '" size="20">',

			'<input type="text" name="description" value="' . $usergroup['description'] . '" size="20">',

			'<textarea style="width:300px;height:60px;" name="welcome">' . Iif(isset($team_welcomes[$usergroup['id']]), $team_welcomes[$usergroup['id']]['welcome']) . '</textarea>',
			'<textarea style="width:300px;height:60px;" name="welcome_en">' . Iif(isset($team_welcomes[$usergroup['id']]), $team_welcomes[$usergroup['id']]['welcome_en']) . '</textarea>',

			'<img src="'. SYSDIR .'public/img/save.png" class="save_item" style="width:26px;cursor: pointer;" title="保存客服组">',

			Iif($usergroup['users'] OR $usergroup['id'] == 1, '&nbsp;', '<img src="'. SYSDIR .'public/img/delete.png" class="delete_item" style="width:26px;cursor: pointer;" title="删除客服组">')));
		}

		TableFooter();

		echo '<script type="text/javascript">
			$(function(){
				var url = "' . BURL("usergroup") . '";

				format_sort(url, "' . $order . '");

				//添加客服组
				$(".add_item").click(function(e){
					var obj = $(this);
					var item = obj.parent().parent();

					var id = item.find("[name=\'id\']").val();
					var sort = $.trim(item.find("[name=\'sort\']").val());
					var activated = $.trim(item.find("[name=\'activated\']").val());
					var groupname = $.trim(item.find("[name=\'groupname\']").val());
					var groupname_en = $.trim(item.find("[name=\'groupname_en\']").val());
					var welcome = $.trim(item.find("[name=\'welcome\']").val());
					var welcome_en = $.trim(item.find("[name=\'welcome_en\']").val());
					var description = $.trim(item.find("[name=\'description\']").val());

					if(groupname == "" || groupname_en == ""){
						showInfo("请填用户组名称及英文名称.", "", "", 2);
					}else{

						if(!ajax_isOk) return false;

						obj.attr("src", "'. SYSDIR .'public/img/saving.gif");

						$.ajax({
							url: "' . BURL('usergroup/ajax?action=save_usergroup') . '",
							data: {id:id, sort:sort, activated:activated, groupname:groupname, groupname_en:groupname_en, welcome:welcome, welcome_en:welcome_en, description:description},
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
								item.find("[name=\'activated\']").val("1");
								item.find("[name=\'groupname\']").val("");
								item.find("[name=\'groupname_en\']").val("");
								item.find("[name=\'welcome\']").val("");
								item.find("[name=\'welcome_en\']").val("");
								item.find("[name=\'description\']").val("");

								var id= data.id;
								var sort = data.sort;

								item.after(\'<tr><td class="td"><input type="hidden" name="id" value="\' + id + \'">\' + id + \'</td>\' + 
								\'<td class="td">0</td>\' + 
								\'<td class="td"><input type="text" name="sort" value="\' + sort + \'" size="2"></td>\' + 
								\'<td class="td"><select name="activated"\' + (activated==0? " class=red" : "") + \'><option value="1">可用</option><option class="red" value="0"\' + (activated==0? " SELECTED" : "") + \'>禁用</option></select></td>\' + 
								\'<td class="td"><input type="text" name="groupname" value="\' + groupname + \'" size="20"></td>\' + 
								\'<td class="td"><input type="text" name="groupname_en" value="\' + groupname_en + \'" size="20"></td>\' + 
								\'<td class="td"><input type="text" name="description" value="\' + description + \'" size="20"></td>\' + 
								\'<td class="td"><textarea style="width:300px;height:60px;" name="welcome">\' + welcome + \'</textarea></td>\' + 
								\'<td class="td"><textarea style="width:300px;height:60px;" name="welcome_en">\' + welcome_en + \'</textarea></td>\' + 
								\'<td class="td"><img src="'. SYSDIR .'public/img/save.png" class="save_item" id="save_item_\' + id + \'" style="width:26px;cursor: pointer;" title="保存客服组"></td>\' + 
								\'<td class="td"><img src="'. SYSDIR .'public/img/delete.png" class="delete_item" id="delete_item_\' + id + \'" style="width:26px;cursor: pointer;" title="删除客服组"></td>\' + 
								\'</tr>\');

								$("#save_item_" + id).click(function(e){
									save_item($(this));

									e.preventDefault();
									return false;
								});

								$("#delete_item_" + id).click(function(e){
									delete_item($(this));

									e.preventDefault();
									return false;
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
					var sort = item.find("[name=\'sort\']").val();
					var activated = item.find("[name=\'activated\']").val();
					var groupname = item.find("[name=\'groupname\']").val();
					var groupname_en = item.find("[name=\'groupname_en\']").val();
					var welcome = item.find("[name=\'welcome\']").val();
					var welcome_en = item.find("[name=\'welcome_en\']").val();
					var description = item.find("[name=\'description\']").val();

					ajax("' . BURL('usergroup/ajax?action=save_usergroup') . '", {id:id, sort:sort, activated:activated, groupname:groupname, groupname_en:groupname_en, welcome:welcome, welcome_en:welcome_en, description:description}, function(data){
						setTimeout(function(){
							obj.attr("src", "'. SYSDIR .'public/img/save.png");
						}, 500); //0.5秒切换, 否则太快没效果
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
					var id = item.find("input:first").val();

					ajax("' . BURL('usergroup/ajax?action=delete_usergroup') . '", {id:id}, function(data){
						if(id != 1) item.remove();
					});
				}

			});
		</script>';
	}

	//去掉空白及换行函数
	private function Clear_string_for_js($str) 
	{ 
		$str = str_replace(PHP_EOL, '', $str); //去掉换行符, 兼容JS变量调用
		$str = preg_replace("/\t/", '', $str); //使用正则表达式替换内容，如：换行
		$str = preg_replace("/\r\n/", '', $str); 
		$str = preg_replace("/\r/", '', $str); 
		$str = preg_replace("/\n/", '', $str); 
		return trim($str); //返回字符串
	}

} 

?>