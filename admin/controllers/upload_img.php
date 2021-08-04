<?php if(!defined('ROOT')) die('Access denied.');

class c_upload_img extends Admin{

	public function __construct($path){
		parent::__construct($path);

	}

	//ajax动作集合, 能过action判断具体任务
    public function ajax(){
		
		$action = ForceStringFrom('action');

		$img_path = ROOT . 'upload/img/'; //保存图片的目录
		$this->ajax['s'] = 0; //ajax状态初始化为失败

		if($action == 'delete'){ //管理员ajax操作删除图片

			//ajax权限验证
			if(!$this->CheckAccess()){
				$this->ajax['i'] = '您没有权限管理上传的图片文件!';
				die($this->json->encode($this->ajax));
			}

			$dirname = ForceStringFrom('dirname');
			$current_dir = DisplayDate('', 'Ym'); //当月保存图片的文件夹名称

			//不允许删除系统默认的语言文件
			if($dirname == $current_dir){
				$this->ajax['i'] = '当月保存图片的文件夹无法删除!';
			}else{
				if($this->removedir($img_path . $dirname))	{
					$this->ajax['s'] = 1;
				}else{
					$this->ajax['i'] = "无法删除 {$dirname}/ 文件夹! 文件夹不可写或文件不存在.";
				}
			}

			die($this->json->encode($this->ajax));

		}elseif($action == 'upload'){ //给客人发送图片s
			@set_time_limit(0);  //解除时间限制

			$img_array = array("image/jpg" => '.jpg', "image/jpeg" => '.jpg', "image/png" => '.png', "image/gif" => '.gif');

			$img_type = strtolower(ForceStringFrom('img_type'));
			$img_base64 = $_POST['img_base64']; //图片文件编码内容不过虑

			//文件目录是否可写
			if(!is_writable($img_path)){
				$this->ajax['i'] = "上传图片失败, 目录不可写";
				die($this->json->encode($this->ajax));
			}

			//验证数据是否存在
			if(!$img_type){
				$this->ajax['i'] = "上传图片失败, 非法操作";
				die($this->json->encode($this->ajax));
			}


			//文件限制不能大于4M(前端js是按文件大小限制为1024 * 4000, 此处对应字节数约为1024 * 8000)
			if(!$img_base64 OR (strlen($img_base64) > 1024 * 8000)){
				$this->ajax['i'] = "上传失败, 图片文件不能超过4M";
				die($this->json->encode($this->ajax));
			}

			//图片文件类型限制
			if(!array_key_exists($img_type, $img_array)) {
				$this->ajax['i'] = "上传失败, 图片文件格式无效";
				die($this->json->encode($this->ajax));
			}


			//以当前时间的小时数为最后目录
			$current_dir = gmdate('Ym/d', time() + (3600 * ForceInt(APP::$_CFG['Timezone']))) . "/"; 
			if(!file_exists($img_path . $current_dir)){
				mkdir($img_path . $current_dir, 0777, true);
				@chmod($img_path . $current_dir, 0777);
			}

			//产生一个独特的文件名称, 包括小时目录
			$filename = $current_dir . md5(uniqid(COOKIE_KEY.microtime())) . $img_array[$img_type];

			if(file_put_contents($img_path . $filename, base64_decode($img_base64))){
				$this->ajax['s'] = 1;
				$this->ajax['i'] = $filename; //返回文件名, 包含时间日期目录
				die($this->json->encode($this->ajax));
			}

			$this->ajax['i'] = "上传图片文件失败";
			die($this->json->encode($this->ajax));

		}elseif($action == 'uploadfile'){ //给客人发送文件
			@set_time_limit(0);  //解除时间限制

			$file_chunk_size = 1048888; //文件片大小限制 1M(1048576), 比前端JS限制稍大200Byte

			$file_path = ROOT . 'upload/file/'; //保存文件的目录
			$file_invalid_ext = array("exe", "php", "bat", "sys", "jpg", "gif", "bmp", "png", "jpeg");

			$file_content = isset($_POST['fileData']) ? $_POST['fileData'] : "";
			$file_content = base64_decode($file_content);

			$file_index = ForceIntFrom('fileIndex'); //文件分片索引

			//文件大小限制
			if(!$file_content OR (strlen($file_content) > $file_chunk_size)){
				$this->ajax_msg(0, "非法上传");
			}

			//处理其它分片文件(1为第一片)
			if($file_index > 1){
				$filename = ForceStringFrom('fileName');

				$file_path_name = $file_path . $filename;
				@clearstatcache($file_path_name); //清除文件缓存状态

				if(!$filename OR !file_exists($file_path_name)) $this->ajax_msg(0, "非法上传");

				if(filesize($file_path_name) > intval(APP::$_CFG['UploadLimit'])*1024*1028){ //允许超过4K字节
					@unlink($file_path_name); //删除已上传的文件
					$this->ajax_msg(0, "上传失败, 文件不能超过: " . APP::$_CFG['UploadLimit'] . "M");
				}

				//追加写入原文件
				if(file_put_contents($file_path_name, $file_content, FILE_APPEND)) {
					$this->ajax_msg(1, $filename); //返回文件名, 包含时间日期目录
				}else{
					@unlink($file_path_name); //删除已上传的文件
					$this->ajax_msg(0, "上传文件失败, 无法保存");
				}	
			}

			//处理第一片文件
			$file_ext = strtolower(ForceStringFrom('fileExt')); //文件后缀

			//文件目录是否可写
			if(!is_writable($file_path)){
				$this->ajax_msg(0, "目录不可写");
			}

			//验证数据是否存在
			if(!$file_ext){
				$this->ajax_msg(0, "上传失败, 文件后缀缺失");
			}

			//文件类型限制
			if(in_array($file_ext, $file_invalid_ext)) {
				$this->ajax_msg(0, "上传失败, 文件格式不允许");
			}

			//以当前时间的小时数为最后目录
			$current_dir = gmdate('Ym/d', time() + (3600 * ForceInt(APP::$_CFG['Timezone']))) . "/"; 
			if(!file_exists($file_path . $current_dir)){
				mkdir($file_path . $current_dir, 0777, true);
				@chmod($file_path . $current_dir, 0777);
			}

			//产生一个独特的文件名称, 包括小时目录
			$filename = $current_dir . md5(uniqid(COOKIE_KEY.microtime())) . ".{$file_ext}"; 

			if(file_put_contents($file_path . $filename, $file_content)){
				$this->ajax_msg(1, $filename); //返回文件名, 包含时间日期目录
			}else{
				$this->ajax_msg(0, "上传失败, 无法保存");
			}
		}
	}


	public function index(){
		$this->CheckAction();

		$img_path = ROOT . 'upload/img/'; //保存图片的目录
		$current_dir = DisplayDate('', 'Ym'); //当月保存图片的文件夹名称


		SubMenu('上传图片清理', array(array('上传图片清理', 'upload_img', 1)));

		TableHeader('全部上传图片文件夹');
		TableRow('<b>图片文件夹目录:</b> ' . BASEURL . 'upload/img/');

		$folders = array();

		if(is_dir($img_path)){
			$handle  = opendir($img_path);

			while(false !== ($file = readdir($handle))){
				if($file != '.' AND  $file != '..' AND is_dir($img_path . $file)){
				  $folders[] = $file;
				}
			}

			closedir($handle);
		}

		$columncount = 0;

		echo '<td class="td last"><table width="100%" border="0" cellpadding="5" cellspacing="0" id="upload_imgs_tb">';

		for($i = 0; $i < count($folders); $i++){
			$columncount++;
			$dir_size = $this->dirsize($img_path . $folders[$i]); //当前文件夹的大小

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
					showDialog("确定删除upload/img/目录下的: <font color=red><b>" + dirname + "</b></font> 文件夹及其下的所有图片吗?", "确认操作", function(){
						ajax("' . BURL('upload_img/ajax?action=delete') . '", {dirname: dirname}, function(data){
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