<?php if(!defined('ROOT')) die('Access denied.');

class c_upload_file extends Mobile{

	public function __construct($path){
		parent::__construct($path);

	}

	//ajax动作集合, 能过action判断具体任务
    public function ajax(){
		
		$action = ForceStringFrom('action');

		$img_path = ROOT . 'upload/img/'; //保存图片的目录
		$this->ajax['s'] = 0; //ajax状态初始化为失败

		if($action == 'uploadimg'){ //给客人发送图片s
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


	//ajax输出函数
	private function ajax_msg($status = 0, $msg = '', $arr = array()){
		$arr['s'] = $status;
		$arr['i'] = $msg;

		die($this->json->encode($arr));
	}


} 

?>