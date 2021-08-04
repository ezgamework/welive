<?php if(!defined('ROOT')) die('Access denied.');

class c_upload_file extends Admin{

	public function __construct($path){
		parent::__construct($path);

	}

	//ajax动作集合, 能过action判断具体任务
    public function ajax(){
		
		$action = ForceStringFrom('action');

		$file_path = ROOT . 'upload/file/'; //保存文件的目录
		$this->ajax['s'] = 0; //ajax状态初始化为失败

		if($action == 'delete'){ //管理员ajax操作删除文件

			//ajax权限验证
			if(!$this->CheckAccess()){
				$this->ajax['i'] = '您没有权限管理上传的文件!';
				die($this->json->encode($this->ajax));
			}

			$dirname = ForceStringFrom('dirname');
			$current_dir = DisplayDate('', 'Ym'); //当月保存文件的文件夹名称

			//不允许删除系统默认的语言文件
			if($dirname == $current_dir){
				$this->ajax['i'] = '当月保存文件的文件夹无法删除!';
			}else{
				if($this->removedir($file_path . $dirname))	{
					$this->ajax['s'] = 1;
				}else{
					$this->ajax['i'] = "无法删除 {$dirname}/ 文件夹! 文件夹不可写或不存在.";
				}
			}

			die($this->json->encode($this->ajax));

		}

	}


	public function index(){
		$this->CheckAction();

		$file_path = ROOT . 'upload/file/'; //保存文件的目录
		$current_dir = DisplayDate('', 'Ym'); //当月保存文件的文件夹名称


		SubMenu('上传文件清理', array(array('上传文件清理', 'upload_file', 1)));

		TableHeader('全部文件夹列表');
		TableRow('<b>文件夹根目录:</b> ' . BASEURL . 'upload/file/');

		$folders = array();

		if(is_dir($file_path)){
			$handle  = opendir($file_path);

			while(false !== ($file = readdir($handle))){
				if($file != '.' AND  $file != '..' AND is_dir($file_path . $file)){
				  $folders[] = $file;
				}
			}

			closedir($handle);
		}

		$columncount = 0;

		echo '<td class="td last"><table width="100%" border="0" cellpadding="5" cellspacing="0" id="upload_imgs_tb">';

		for($i = 0; $i < count($folders); $i++){
			$columncount++;
			$dir_size = $this->dirsize($file_path . $folders[$i]); //当前文件夹的大小

			if($dir_size == 0){
				$dir_size = '空';
			}else{
				$dir_size = number_format($dir_size / 1024000, 2) . 'M';
			}

			if($columncount == 1){
				echo '<tr><td colspan="4">&nbsp;</td></tr><tr>';
			}

			echo '<td width="25%">';

			echo '<table width="100%" border="0" cellpadding="0" cellspacing="0">
			<tr>
			<td width="10" valign="top" style="padding-right: 15px;"><img style="border:1px solid #e8e8e8; padding:3px;" src="'.SYSDIR .'public/img/folder.gif" /></td>
			<td valign="top">文件夹名称: <b>' . $folders[$i] . '/</b><br />文件夹大小: <b>' . $dir_size . '</b><br /><br />' . Iif($folders[$i] != $current_dir, '<a file="' . $folders[$i] . '" class="link-btn ajax">删除此文件夹</a>', '<font class=grey>当月上传文件夹不能删除</font>') . '</td>
			</tr>
			</table>';

			echo '</td>';

			if($columncount == 4){
				echo '</tr><tr><td colspan="4">&nbsp;</td></tr>';
				$columncount = 0;
			}
		}

		if($columncount != 0 && $columncount != 4){
			while($columncount < 4){
				$columncount++;
				echo '<td width="20%">&nbsp;</td>';
			}
			echo '</tr><tr><td colspan="4">&nbsp;</td></tr>';
		}

		echo '</table> 
		<script type="text/javascript">
			$(function(){
				$("#main a.ajax").click(function(e){
					var _me=$(this);
					var dirname = _me.attr("file");
					showDialog("确定删除upload/file/目录下的: <font color=red><b>" + dirname + "</b></font> 文件夹及其下的所有文件吗?", "确认操作", function(){
						ajax("' . BURL('upload_file/ajax?action=delete') . '", {dirname: dirname}, function(data){
							showInfo("所选文件夹删除成功.", "Ajax操作", "", 4, 1);
							_me.parent().parent().hide();
						});
					});

					e.preventDefault();
				});
			});
		</script>
		</td>';

		TableFooter();
		
	}


	/**
	 * 文件夹大小
	 * @param $path
	 * @return int
	 */
	private function dirsize($path)
	{
		$size = 0;
		if(!is_dir($path)) return $size;

		$handle = opendir($path);
		while (($item = readdir($handle)) !== false) {
			if ($item == '.' || $item == '..') continue;
			$_path = $path . '/' . $item;
			if (is_file($_path)){
				$size += filesize($_path);
			}else{
				$size += $this->dirsize($_path);
			}
		}
		closedir($handle);
		return $size;
	}


	/**
	 * 删除文件夹
	 * @param $path
	 * @return bool
	 */
	private function removedir($path)
	{
		if(!is_dir($path)) return false;

		$handle = opendir($path);
		while (($item = readdir($handle)) !== false) {
			if ($item == '.' || $item == '..') continue;
			$_path = $path . '/' . $item;
			if (is_file($_path)) unlink($_path);
			if (is_dir($_path)) $this->removedir($_path);
		}
		closedir($handle);
		return rmdir($path);
	}

	//ajax输出函数
	private function ajax_msg($status = 0, $msg = '', $arr = array()){
		$arr['s'] = $status;
		$arr['i'] = $msg;

		die($this->json->encode($arr));
	}

} 

?>