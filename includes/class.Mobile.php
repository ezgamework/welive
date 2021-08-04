<?php if(!defined('ROOT')) die('Access denied.');

class Mobile extends MobileAuth{
	protected $ajax = array(); //用于ajax数据收集与输出
	public $json; //ajax时的JSON对象

	public function __construct($path = ''){

		parent::__construct($path);

		if($path[1] == 'ajax') { //任意控制器的动作为ajax时, 执行ajax动作, 禁止输出页头, 页尾及数据库访问错误

			APP::$DB->printerror = false; //ajax数据库访问不打印错误信息
			$this->ajax['s'] = 1; //初始化ajax返回数据, s表示状态
			$this->ajax['i'] = ''; //i指ajax提示信息
			$this->ajax['d'] = ''; //d指ajax返回的数据
			$this->json = new JSON;

			if(!$this->admin){//管理员验证不成功, 直接输出ajax信息, 并终止ajax其它程序程序运行

				$this->ajax['s'] = 0;
				$this->ajax['i'] = "管理员授权错误! 请确认已成功登录后台.";
				die($this->json->encode($this->ajax));
			}

		}elseif($path[1] == 'logout'){

			$this->logout(); //无论哪个控制器, 只要是logout动作, admin用户退出
		}

	}


}

?>