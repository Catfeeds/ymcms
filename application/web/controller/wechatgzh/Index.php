<?php
namespace app\web\controller\wechatgzh;
use think\Controller;
use think\Request;
use think\Db;
use app\common\lib\Upload;
/*欢迎页面*/
class Index extends Controller {

	/*首页*/
	public function index(){
		/*分配变量*/
		$this->assign('title','管理首页');/*页面标题*/
		$this->assign('keywords','管理首页');/*页面关键词*/
		$this->assign('description','管理首页');/*页面描述*/
	}
}