<?php
namespace app\web\controller;
use think\Controller;
use think\Request;
use think\Session;
use think\Db;
class Base extends Controller
{
	/*模板主题*/
	public $theme;
	public $m_index;/*入口(index.php)*/
	/*构造方法*/
	public function _initialize()
	{
		/*重载父类构造方法*/
		parent::_initialize();
		$this->m_index = '/index.php'; /*入口*/
		$this->assign('m_index',$this->m_index);
		//$_SESSION = array();die();
		/*开启session*/
		/*session_start();*/
        /**调用公共脚本类库 过滤ajax传参数混淆**/
		$public_static = '/public/static';
		if(!Request::instance()->isAjax()){
			echo '<script src="'.$public_static.'/html/js/jquery-1.9.1.min.js"></script>';
			echo '<script src="'.$public_static.'/html/js/public.js"></script>';
		}
		/*将客户端信息写进SESSION*/
		session_client_put();
		/*定义站点文件路径*/
		$admin_login = session('admin_login');
		$site_id = session('admin_login.site_id')?session('admin_login.site_id'):config('system_site');
		$dir_site = '/public/upload/space/site_'.$site_id;
		$this->assign('dir_site',$dir_site);
		/*赋值模板主题*/
		$this->theme = 'temp_1';
		/*定义模板文件路径*/
		define('DIR_TEMP','/application/web/view/'.$this->theme);
		$this->assign('dir_temp',DIR_TEMP);
		if (!Request::instance()->isAjax()) {
			echo '<link rel="stylesheet" href="'.DIR_TEMP.'/file/css/public.css" />';
		}
		/*模块公共头部文件*/
		define('TEMP_HEADER',$this->theme.'/public/header');
		$this->assign('temp_header',TEMP_HEADER);
		/*模块公共底部文件*/
		define('TEMP_FOOTER',$this->theme.'/public/footer');
		$this->assign('temp_footer',TEMP_FOOTER);		
		/*模块公共侧边栏文件*/
		define('TEMP_ASIDE',$this->theme.'/public/aside');
		$this->assign('temp_aside',TEMP_ASIDE);		
		/*方法调用模板路径*/
		$request = Request::instance();
		define('TEMP_FETCH','./application/'.$request->module().'/view/'.$this->theme.'/'.strtolower($request->controller()).'/'.$request->action().'.html');	
		/*站点目录*/
		$spacedir = '.'.config('view_replace_str.__SPACE__').'/';
		$this->assign('spacedir',trim($spacedir,'.'));		
	}

	/*登录*/
    public function login()
    {
		/*清空SESSION*/
		$_SESSION = array();
		/*分配变量*/
		$this->assign('title','登录');/*页面标题*/
		$this->assign('keywords','登录');/*页面关键词*/
		$this->assign('description','登录');/*页面描述*/		
		/*调用模板*/
		return $this->fetch(TEMP_FETCH);
    }
	
	/*登录验证 AJAX*/
	public function login_in(){
		/*接受参数*/
		$name = trim(input('name'));/*用户名称*/
		$password = trim(input('password'));/*密码*/
		if($name){
			if($password){
				/*查询用户*/	
				$where['name'] = $name;
				$user = Db::name('user')->where($where)->find();
				if($user){
					$reg_time = $user['addtime'];
					$password = md5(md5($reg_time.$password));						
					if($user['password'] == $password){
						/*SESSION写入登录信息*/
						$admin_login = array(
							'is_login' => 100,
							'user_id'  => $user['id'],
							'user_name'=> $user['name'],
							'group_ids'=> $user['group_ids'],
							'site_ids' => $user['site_ids'],
							'admin' => $user['admin']
						);
						if(count(explode(',',$user['site_ids'])) > 1){
							/*多站点登录*/
							session('admin_login',$admin_login);
							/*结果集*/
							$result['status'] = 100;
							$result['msg'] = '恭喜，登录成功！';										
						}else{
							/*验证站点管理员或系统管理员身份*/
							$group = Db::name('group')->field('type')->where('id='.$user['group_ids'])->find();/*用户组类型*/	
							if($group['type'] == 2 || $group['type'] == 3){		
								/*写日志*/
								$user_id = $user['id'];
								$user_name = $user['name'];
								$site_ids = $user['site_ids'];
								$admin = $user['admin'];
								$logdata = array(
									/*日志内容*/
									'name' => '登录系统管理'
								);
								$log = new \loginfo\Log();/*实例化日志类*/
								$result_log = $log->writeLog($user_id,$user_name,$site_ids,'login',$logdata);/*写入日志*/	
								if($result_log['status'] == 100){														
									/*单站点登录*/
									$admin_login['group_id'] = $user['group_ids'];
									$admin_login['site_id'] = $user['site_ids'];
									session('admin_login',$admin_login);
									/*结果集*/
									$result['status'] = 800;
									$result['msg'] = '恭喜，登录成功！';	
								}else{
									/*结果集*/
									$result['status'] = 700;
									$result['msg'] = '抱歉，信息写入失败！';								
								}
							}else{
								$result['status'] = 500;
								$result['msg'] = '抱歉，您无管理权限！';				
							}
						}
					}else{
						$result['status'] = 600;
						$result['msg'] = '抱歉，密码输入错误！';
					}
				}else{
					$result['status'] = 400;
					$result['msg'] = '抱歉，该用户不存在！';
				}
			}else{
				$result['status'] = 300;
				$result['msg'] = '抱歉，密码不可为空！';
			}
		}else{
			$result['status'] = 200;
			$result['msg'] = '抱歉，用户名不可为空！';
		}
		/*返回JSON数据*/
		return json_encode($result);
	}
	
	/*退出登录 同步*/
	public function login_out_on(){
		/*接受参数*/
		$user_id = session('admin_login.user_id');/*用户ID*/
		$user_name = session('admin_login.user_name');/*用户名*/
		$site_id = session('admin_login.site_id');/*站点ID*/
		/*退出登录*/
		$session_prefix = config('session.prefix');
		$_SESSION[$session_prefix]['admin_login'] = array();
		/*写日志*/
		$logdata = array(
			/*日志内容*/
			'name' => '退出系统管理'
		);
		$log = new \loginfo\Log();/*实例化日志类*/
		$result_log = $log->writeLog($user_id,$user_name,$site_id,'login',$logdata);/*写入日志*/	
		if($result_log['status'] == 100){
			echo '<script>$(document).ready(function(){alertBox("您已安全退出！","'.url('base/login').'");})</script>';			
		}
	}
		
	/*接受游览器传递过来的分辨率信息 AJAX*/
	public function page_screen(){
		/*接受信息*/
		if(input('page_screen')){
			$page_screen = input('page_screen');
		}else{
			$page_screen = '未知';
		}
		/*写入SESSION*/
		session('client.screen',$page_screen);
		/*返回结果集*/
		if($page_screen){
			$result['status'] = 100;
			$result['msg'] = '分辨率提交成功！'.$page_screen;			
		}else{
			$result['status'] = 200;
			$result['msg'] = '分辨率提交失败！';
		}
		/*返回JSON数据*/
		return json_encode($result);
	}
	
	/*应用页面*/
    public function server()
    {
		/*查询管理员名下的站点字符集*/
		/*验证登录*/
		$admin_login = session('admin_login');
		$is_login = isset($admin_login['is_login'])?$admin_login['is_login']:0;		
		if(!$admin_login || $is_login != 100){
			$this->redirect('base/login');
		}
		$site_where['id'] = array('in',$admin_login['site_ids']);
		$site = Db::name('site')->field('server_id')->where($site_where)->select();
		$server_str = '';
		foreach($site as $k=>$v){
			$server_str .= $v['server_id'].',';
		}
		$server_str = trim($server_str,',');
		$server_on = explode(',',$server_str);
		/*查询所有应用*/
		$where_tree['type'] = 1;/*类型：内容列表*/
		$where_tree['pid'] = 1;
		$where_tree['status'] = 1;
		$server = Db::name('tree')->where($where_tree)->select();
		foreach($server as $k=>$v){
			foreach($server_on as $k2=>$v2){
				if($v['id'] == $v2){
					$server[$k]['on'] = 100;
				}
			}
		}
		$this->assign('list',$server);
		/*分配变量*/
		$this->assign('title','应用');/*页面标题*/
		$this->assign('keywords','应用');/*页面关键词*/
		$this->assign('description','应用');/*页面描述*/
		/*调用模板*/
		return $this->fetch(TEMP_FETCH);
    }

	/*站点页面*/
    public function site()
    {
		/*验证登录*/
		$admin_login = session('admin_login');
		$is_login = isset($admin_login['is_login'])?$admin_login['is_login']:0;		
		if(!$admin_login || $is_login != 100){
			$this->redirect('base/login');
		}
		/*应用ID*/
		$id = input('id');
		if($id){
			$where['server_id'] = $id;
			$where['id'] = array('in',$admin_login['site_ids']);
			$where['status'] = 1;
			$list = Db::name('site')->where($where)->select();
			foreach($list as $k=>$v){
				/*状态*/
				if($v['status'] == 1){
					$list[$k]['status'] = '启用';
				}else{
					$list[$k]['status'] = '禁用';
				}
			}
			$this->assign('list',$list);
		}else{
			echo '<script>$(document).ready(function(){alertBox("应用参数缺失！","'.url('base/server').'");})</script>';
		}
		/*分配变量*/
		$this->assign('server_id',$id);/*应用ID*/
		$this->assign('title','站点');/*页面标题*/
		$this->assign('keywords','站点');/*页面关键词*/
		$this->assign('description','站点');/*页面描述*/
		/*调用模板*/
		return $this->fetch(TEMP_FETCH);
    }
	
	/*站点管理*/
    public function siteedit()
    {
		/*验证登录*/
		$admin_login = session('admin_login');
		$is_login = isset($admin_login['is_login'])?$admin_login['is_login']:0;
		if(!$admin_login || $is_login != 100){
			$this->redirect('base/login');
		}
		/*站点ID*/
		$id = input('site_id');
		if($id){
			$site_all = explode(',',$admin_login['site_ids']);
			$group_all = explode(',',$admin_login['group_ids']);
			foreach($site_all as $k=>$v){
				if($v == $id){
					$group_id = $group_all[$k];
				}
			}
			/*验证站点管理员或系统管理员身份*/
			$group = Db::name('group')->field('type')->where('id='.$group_id)->find();/*用户组类型*/	
			if($group['type'] == 2 || $group['type'] == 3){
				/*写日志*/
				$user_id = $admin_login['user_id'];
				$user_name = $admin_login['user_name'];
				$site_ids = $id;
				$logdata = array(
					/*日志内容*/
					'name' => '登录系统管理'
				);
				$log = new \loginfo\Log();/*实例化日志类*/
				$result_log = $log->writeLog($user_id,$user_name,$site_ids,'login',$logdata);/*写入日志*/	
				if($result_log['status'] == 100){
					/*SESSION写入登录信息*/
					$admin_login = array(
						'is_login' => $admin_login['is_login'],
						'user_id'  => $admin_login['user_id'],
						'user_name'=> $admin_login['user_name'],
						'group_ids'=> $admin_login['group_ids'],
						'site_ids' => $admin_login['site_ids'],
						'group_id'=> $group_id,
						'site_id' => $id
					);
					session('admin_login',$admin_login);
					/*管理跳转*/
					$this->redirect(url('center/index'));
				}else{
					/*结果集*/
					echo '<script>$(document).ready(function(){alertBox("抱歉，信息写入失败！","'.url('base/login_out_on').'");})</script>';
				}
			}else{
				echo '<script>$(document).ready(function(){alertBox("抱歉，您非该站点管理员！","'.url('base/login_out_on').'");})</script>';
			}
		}else{
			echo '<script>$(document).ready(function(){alertBox("站点参数缺失！","'.url('base/server').'");})</script>';
		}
    }
	/*站点删除*/
    public function sitedel()
    {
		/*验证登录*/
		$admin_login = session('admin_login');
		$is_login = isset($admin_login['is_login'])?$admin_login['is_login']:0;		
		if(!$admin_login || $is_login != 100){
			$this->redirect('base/login');
		}	
		/*站点ID*/
		$id = input('site_id');
		if($id){
			$site_all = explode(',',$admin_login['site_ids']);
			$group_all = explode(',',$admin_login['group_ids']);
			foreach($site_all as $k=>$v){
				if($v == $id){
					$group_id = $group_all[$k];
				}
			}
			/*验证站点管理员或系统管理员身份*/
			$group = Db::name('group')->field('type')->where('id='.$group_id)->find();/*用户组类型*/	
			if($group['type'] == 2 || $group['type'] == 3){
				if($id != config('system_site')){
					/*判断是否存在用户*/
					$site = Db::name('user')->where('site_ids='.$id)->find();
					if($site){
						echo '<script>$(document).ready(function(){alertBox("抱歉，站点下存在使用的用户，不允许删除！","'.$_SERVER['HTTP_REFERER'].'");})</script>';
					}else{
						/*判断是否存在分类*/
						$where_category['site_id'] = $id;
						$category = Db::name('category')->where($where_category)->find();
						if($category){
							/*存在分类*/
							echo '<script>$(document).ready(function(){alertBox("抱歉，存在分类或内容，不允许删除！","'.$_SERVER['HTTP_REFERER'].'");})</script>';
						}else{
							/*不存在分类*/
							$result = Db::name('site')->where('id='.$id)->delete();
							if($result){
								echo '<script>$(document).ready(function(){alertBox("恭喜，删除成功！","'.$_SERVER['HTTP_REFERER'].'");})</script>';
							}else{
								echo '<script>$(document).ready(function(){alertBox("抱歉，删除失败！","'.$_SERVER['HTTP_REFERER'].'");})</script>';
							}
						}
					}
				}else{
					echo '<script>$(document).ready(function(){alertBox("系统站点，不允许删除！","'.url('base/server').'");})</script>';
				}
			}else{
				echo '<script>$(document).ready(function(){alertBox("抱歉，您非该站点管理员！","'.url('base/login_out_on').'");})</script>';
			}	
		}else{
			echo '<script>$(document).ready(function(){alertBox("站点参数缺失！","'.url('base/server').'");})</script>';
		}
    }
	
}