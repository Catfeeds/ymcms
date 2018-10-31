<?php
namespace app\web\controller;
use think\Request;
use think\Db;
/*APP文档管理中心*/
class App extends Common
{	
	/*构造方法*/
	public function _initialize()
	{
		/*重载父类构造方法*/
		parent::_initialize();
	}
	
    public function doc()
    {
		$id = input('id');
		if(empty($id)){
			echo '<meta charset="utf-8" />';
			echo '<script>$(document).ready(function(){alertBox("文档参数缺失！","'.$_SERVER['HTTP_REFERER'].'");})</script>';
			die();
		}
		/*查询文档内容*/
		$tree = Db::name('tree')->where('id='.$id)->find();
		$this->assign('content',$tree);
		/*分配变量*/
		$this->assign('title',$tree['name']);/*页面标题*/
		$this->assign('keywords',$tree['name']);/*页面关键词*/
		$this->assign('description',$tree['name']);/*页面描述*/		
		/*调用模板*/
		return $this->fetch(TEMP_FETCH);
    }
}