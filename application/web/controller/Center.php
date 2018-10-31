<?php
namespace app\web\controller;
use think\Request;
use think\Db;
class Center extends Common
{
	/*构造方法*/
	public function _initialize()
	{
		/*重载父类构造方法*/
		parent::_initialize();
	}
	
	/*后台首页*/
    public function index()
    {
		$request = Request::instance();
		/*获取当前应用信息*/
		$server = Db::name('tree')->field('code')->where('id='.$this->site['server_id'])->find();
		if(empty($server) || empty($server['code'])){
			echo '<meta charset="utf-8" />';
			echo '<script>$(document).ready(function(){alertBox("抱歉，应用参数缺失！","'.url('web/base/login').'");})</script>';
			die();
		}else{
			/*组装应用主页控制器路径*/
			$action_str = $server['code'].'.index/index';
			action($action_str);
			/*组装应用主页模板路径*/
			$controller_dirstr = str_replace('.','/',$request->controller());
			$fetch_temp = './application/'.$request->module().'/view/'.$this->theme.'/'.strtolower($server['code']).'/'.$request->action().'.html';
			return $this->fetch($fetch_temp);
		}
    }
	
}