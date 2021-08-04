<?php if(!defined('ROOT')) die('Access denied.');

class c_avatar extends Admin{

	public function __construct($path){
		parent::__construct($path);
	}

	public function ajax(){
		$result = array();
		$result['success'] = false;
		$result['msg'] = '';

		$avatarpath = ROOT . 'avatar/';
		$userid = $this->admin['aid'];

		if(!is_writable($avatarpath)){
			$result['msg'] =  'avatar目录不可写!';
			die($this->json->encode($result));
		}

		$imgbase64 = Iif(isset($_POST['imgbase64']), $_POST['imgbase64']); //图片文件编码内容不过虑
		if(!$imgbase64 OR strlen($imgbase64) > 1024 * 1024){
			$result['msg'] = '无数据或图片大小超过1M!'; //不允许超过1M
			die($this->json->encode($result));
		}

		$avatar = $avatarpath . "$userid.jpg"; //头像绝对路径及文件名

		if(file_put_contents($avatar, base64_decode($imgbase64))){
			//返回大头像的URL
			$result['msg'] = SYSDIR . "avatar/$userid.jpg?" . time(); //加一个参数方便更新原头像
			$result['success'] = true;
			die($this->json->encode($result));
		}

		$result['msg'] = '保存头像文件失败, 请重试!';
		die($this->json->encode($result));
	}

	public function index(){
		$userid = $this->admin['aid'];

		$isAdmin = $this->CheckAccess();

		SubMenu('上传头像');

echo '<link rel="stylesheet" type="text/css" href="'. SYSDIR .'public/jquery.cropper.css">
<script type="text/javascript" src="'. SYSDIR .'public/jquery.cropper.js"></script>
<style type="text/css">
.page-btn{
    color: #fff;
    display: inline-block;
    outline: none;
    padding:6px 16px !important;
    background: #8C85E6 !important;
    border:solid 1px #8C85E6;
    border-radius: 3px;
    font-size: 14px;
	cursor:pointer;
	margin-right:10px;
}
.page-btn:hover{background: #655bdd !important;}
.tailoring-container{
    width: 100%;
    height: 100%;
}
.tailoring-container .tailoring-content{
    width: 768px;
    height: 560px;
    background: #fff;
    padding: 10px;
}
.tailoring-content-one{
    height: 40px;
    width: 100%;
}
.tailoring-content-two{
    width: 100%;
    height: 460px;
    padding: 5px 0;
	overflow:hidden;
}
.tailoring-box-parcel{
    width: 520px;
    height: 450px;
    border: solid 1px #ddd;
	float:left;
}
.preview-box-parcel{
    box-sizing: border-box;
    display: inline-block;
    width: 228px;
    height: 450px;
    padding: 4px 14px;
}
.preview-box-parcel p{
    color: #555;
}
.previewImg{
    width: 200px;
    height: 200px;
    overflow: hidden;
}
.preview-box-parcel .square{
    margin-top: 10px;
    border: solid 1px #ddd;
	border-radius: 3px;
}
.preview-box-parcel .circular{
    border-radius: 100%;
    margin-top: 10px;
    border: solid 1px #ddd;
}
.tailoring-content-three{
    width: 100%;
    height: 40px;
    padding-top: 10px;
}
.sureCut{float: right;margin-right:80px;}
</style>';



TableHeader('我的头像');

echo '<tr><td class="td" style="padding:18px;vertical-align:top;width:80px;"><a href="' . Iif($isAdmin, BURL("users/edit?aid=$userid"), BURL("myprofile")) . '"><img src="' . GetAvatar($userid) . '" class="avatar" title="当前头像"></a></td>
<td class="td last" style="padding:18px 0;">
<div class="tailoring-container">
	<div class="tailoring-content">
		<div class="tailoring-content-one">
			<label for="chooseImg" class="page-btn">
				<input type="file" accept="image/jpg,image/jpeg,image/png" name="file" id="chooseImg" style="display: none;" onchange="selectImageFile(this);">
				选择图片
			</label>
		</div>
		<div class="tailoring-content-two">
			<div class="tailoring-box-parcel">
				<img id="tailoringImg">
			</div>
			<div class="preview-box-parcel">
				<p>图片预览：</p>
				<div class="square previewImg"></div>
				<div class="circular previewImg"></div>
			</div>
		</div>
		<div class="tailoring-content-three">
			<button class="page-btn cropper-reset-btn">复位</button>
			<button class="page-btn cropper-rotate-btn">旋转</button>
			<button class="page-btn cropper-scaleX-btn">换向</button>
			<button class="page-btn sureCut" id="sureCut">上传头像</button>
		</div>
	</div>
</div>
</td></tr>';

TableFooter();

echo '<script type="text/javascript">
var selectedImage = 0;

//图像上传
function selectImageFile(file){
	if(!file.files || !file.files[0]) return;

	var reader = new FileReader();
	reader.onload = function(evt) {
		selectedImage = 1;

		var replaceSrc = evt.target.result;
		//更换cropper的图片
		$("#tailoringImg").cropper("replace", replaceSrc, false); //默认false，适应高度，不失真
	}
	reader.readAsDataURL(file.files[0]);
}

//cropper图片裁剪
$("#tailoringImg").cropper({
	aspectRatio: 1/1,//默认比例
	preview: ".previewImg",//预览视图
	guides: false,  //裁剪框的虚线(九宫格)
	autoCropArea: 0.5,  //0-1之间的数值，定义自动剪裁区域的大小，默认0.8
	movable: true, //是否允许移动图片
	dragCrop: true,  //是否允许移除当前的剪裁框，并通过拖动来新建一个剪裁框区域
	movable: true,  //是否允许移动剪裁框
	resizable: true,  //是否允许改变裁剪框的大小
	zoomable: true,  //是否允许缩放图片大小
	mouseWheelZoom: true,  //是否允许通过鼠标滚轮来缩放图片
	touchDragZoom: true,  //是否允许通过触摸移动来缩放图片
	rotatable: true,  //是否允许旋转图片
	crop: function(e) {
		// 输出结果数据裁剪图像。
	}
});

//旋转
$(".cropper-rotate-btn").on("click", function(){
	if(!selectedImage) return;
	$("#tailoringImg").cropper("rotate", 45);
});

//复位
$(".cropper-reset-btn").on("click",function(){
	if(!selectedImage) return;
	$("#tailoringImg").cropper("reset");
});

//换向
var flagX = true;
$(".cropper-scaleX-btn").on("click", function(){
	if(!selectedImage) return;
	if(flagX){
		$("#tailoringImg").cropper("scaleX", -1);
		flagX = false;
	}else{
		$("#tailoringImg").cropper("scaleX", 1);
		flagX = true;
	}
	flagX != flagX;
});

//裁剪后的处理
$("#sureCut").on("click", function(){
	if(!selectedImage) return;
	if($("#tailoringImg").attr("src") == null ){
		return false;
	}else{
		var cas = $("#tailoringImg").cropper("getCroppedCanvas", {width: 80, height: 80}); //获取被裁剪后的canvas
		var base64url = cas.toDataURL("image/jpeg"); //转换为base64地址形式
		var img_data = base64url.substr(base64url.indexOf(",") + 1); //截取实际文件内容

		//ajax上传
		ajax("' . BURL('avatar/ajax') . '", {imgbase64: img_data}, function(data){
			if(data.success){
				$(".avatar").attr("src", data.msg);
				showInfo("呵呵, 您的头像已保存!", "上传头像", "", 1);
			}else{
				showInfo(data.msg, "保存头像失败");
			}
		});
	}
});
</script>';
	}
} 

?>