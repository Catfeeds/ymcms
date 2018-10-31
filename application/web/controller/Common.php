<?php
namespace app\web\controller;
use think\Controller;
use think\Request;
use think\Url;
use think\Db;
class Common extends Controller {
	
	/*公共变量*/
	public $site;
	public $m_index;/*入口(index.php)*/
	public $theme; /*模板主题*/
	public $user_id; /*用户ID*/
	public $group_ids; /*用户组集*/
	public $site_ids; /*用户站点集*/
	public $group_id; /*当前用户组*/
	public $site_id; /*当前站点*/
	public $site_domain;/*站点域名*/
	public $is_mobile;/*是否移动端*/
	public $system_image;
	
	/*构造方法*/
	public function _initialize() {
		if(strpos('/index.php',$_SERVER['REQUEST_URI']) !== false){
			$this->m_index = '/index.php'; /*入口*/
		}else{
			$this->m_index = ''; /*入口*/
		}
		$this->assign('m_index',$this->m_index);
		/**调用公共脚本类库 过滤ajax传参数混淆**/
		$public_static = '/public/static';
		if (!Request::instance()->isAjax()) {
			echo '<script src="' . $public_static . '/html/js/jquery-1.9.1.min.js"></script>';
			echo '<script src="' . $public_static . '/html/js/public.js"></script>';
		}
		/*开启session*/
		/*session_start();*/
		/*登录验证*/
		$admin_login = session('admin_login');
		$is_login = isset($admin_login['is_login']) ? $admin_login['is_login'] : 0;
		if (!$admin_login || $admin_login['is_login'] != 100) {
			$this->redirect('/index.php/web/base/login.html');
			//$this->redirect($this->m_index.url('base/login'));
		}
		$request = Request::instance();	
		/*赋值公共变量*/
		$this->theme = 'temp_1'; /*模板主题*/
		$this->user_id = $admin_login['user_id']; /*用户ID*/
		$this->group_id = $admin_login['group_id']; /*用户组ID*/
		$this->site_id = $admin_login['site_id']; /*用户组ID*/
		$this->site_ids = $admin_login['site_ids']; /*站点ID*/
		$this->group_ids = $admin_login['group_ids']; /*用户组集*/
		$this->assign('user_name', $admin_login['user_name']);
		/*将客户端信息写进SESSION*/
		session_client_put();
		if (!$this->site_id) {
			$this->redirect($this->m_index.url('base/server'));
		}
		/*定义模板文件路径*/
		define('DIR_TEMP', '/application/web/view/' . $this->theme);
		$this->assign('dir_temp', DIR_TEMP);
		if (!Request::instance()->isAjax() && $request->action() != 'tempfit' ) {
			echo '<link rel="stylesheet" href="'.DIR_TEMP.'/file/css/public.css" />';
		}		
		/*模块公共头部文件*/
		define('TEMP_HEADER', $this->theme . '/public/header');
		$this->assign('temp_header', TEMP_HEADER);
		/*模块公共底部文件*/
		define('TEMP_FOOTER', $this->theme . '/public/footer');
		$this->assign('temp_footer', TEMP_FOOTER);
		/*模块公共侧边栏文件*/
		define('TEMP_ASIDE', $this->theme . '/public/aside');
		$this->assign('temp_aside', TEMP_ASIDE);
		/*方法调用模板路径*/
		$controller_dirstr = str_replace('.','/',$request->controller());
		define('TEMP_FETCH','./application/'.$request->module().'/view/'.$this->theme.'/'.strtolower($controller_dirstr).'/'.$request->action().'.html');
		/*查询当前站点信息*/
		$site = Db::name('site')->where('id='.$this->site_id)->find();
		$this->assign('site', $site);
		$this->site = $site;
		//define('SITE',$site);
		$isMobile = isMobile();/*判断终端*/
		$this->is_mobile = $isMobile;			
		/*组装站点URL*/
		$server_domain = config('system_domain');
		$site_url = 'http://';
		if (!empty($site['domain_top'])) {
			$site_domain = 'www.'.$site['domain_top'];
		} else if (!empty($site['domain_two'])) {
			$site_domain = $site['domain_two'].'.'.$server_domain;
		} else if (!empty($site['no'])) {
			$site_domain = $site['no'].'.'.$server_domain;
		}
		$site_url = $site_url.$site_domain;
		$system_url = 'http://www.'.$server_domain;
		$this->site_domain = $site_domain;
		$this->assign('server_domain', $server_domain);
		$this->assign('site_domain', $site_domain);
		$this->assign('system_url', $system_url);
		$this->assign('site_url', $site_url);
		/*站点目录*/
		$sitedir = '.' . config('view_replace_str.__SPACE__') . '/site_' . $this->site_id . '/';
		if (!file_exists($sitedir)) {
			mkdir($sitedir);
		}
		define('SITEDIR',trim($sitedir,'.'));
		$this->assign('sitedir', trim($sitedir,'.'));
		$this->assign('dir_site', trim($sitedir,'.'));
		/*公共管理菜单 start*/
		/*查询用户组信息及权限*/
		$group = Db::name('group')->field('name,tree,type')->where('id=' . $this->group_id)->find();
		$group_name = $group['name'];
		$this->assign('group_name', $group_name);
		$group_type = $group['type'];
		/*验证用户组管理权限*/
		if ($group_type != 2 && $group_type != 3) {
			$this->redirect('base/login');
		}
		$group_tree = json_decode($group['tree'],true);
		$group_tree_str = '';
		foreach ($group_tree as $k => $v) {
			$group_tree_str.= $v.',';
		}
		$group_tree_str = trim($group_tree_str,',');
		/*查询拓扑树*/
		if($group_type != 3){
			$where_tree['path'] = array('like','%,'.$site['server_id'].',%');
		}
		$where_tree['id'] = array('in', $group_tree_str);
		$tree = Db::name('tree')->where($where_tree)->select();
		$nav = array(); /*菜单变量*/
		foreach ($tree as $k => $v) {
			if(strpos($v['url'],'http') !== false){
			}else{
				/*APP文档*/
				if($v['url'] == '/app/doc'){
					$tree[$k]['url'] = '/index.php/'.$request->module().$v['url'].'/id/'.$v['id'].'.html';
				}else{
					$tree[$k]['url'] = '/index.php/'.$request->module().$v['url'].'.html';
				}
			}
			$id = $v['id'];
			$pid = $v['pid'];
			/*添加当前菜单标识*/
			$on_location = strtolower('/'.$request->controller().'/'.$request->action()); /*当前所在方法*/
			$nav_url = strtolower($v['url']); /*菜单url*/
			if($request->controller() == 'App'){
				if(!empty(input('id')) && $v['id'] == input('id')){
					$tree[$k]['on'] = 100;
				}
			}else{
				if(!empty($nav_url) && !empty($on_location)){
					$nav_url = trim($nav_url,'list');
					$nav_url = trim($nav_url,'my');
					if(strpos($on_location,$nav_url) !== false){
						$tree[$k]['on'] = 100;
					}
				}
			}
			/*站点管理员*/
			if ($group_type == 2) {
				$tree[$k]['path'] = str_replace("0,1,","",$v['path']);
			}
			/*重组层次关系*/
			$nav_arr = explode(',', trim($tree[$k]['path'],','));
			$nav_count = count($nav_arr);
			if ($nav_count == 2) {
				/*二级菜单*/
				$nav[$pid]['nav_sub'][$id] = $tree[$k];
			} else if ($nav_count == 1) {
				/*顶级菜单*/
				$nav[$id] = $tree[$k];
			}
		}
		$this->assign('nav',$nav);
		/*校验管理员访问权限*/
		$page_visit_allow = 0;
		$page_admin_home = '/index.php/web/center/index.html';
		if($_SERVER['REQUEST_URI'] == $page_admin_home){
			$page_visit_allow = 1;/*首页*/
		}else{
			if(!empty($nav)){
				foreach($nav as $k=>$v){
					if(!empty($v['url']) && $v['url'] == $_SERVER['REQUEST_URI']){
						$page_visit_allow = 1;/*一级栏目*/
					}else{
						if(!empty($v['on']) && $v['on'] == 100){
							$page_visit_allow = 1;/*一级栏目*/
						}else{	
							if(!empty($v['nav_sub'])){
								foreach($v['nav_sub'] as $k2=>$v2){
									if(!empty($v2['url']) && $v2['url'] == $_SERVER['REQUEST_URI']){
										$page_visit_allow = 1;/*二级栏目*/
									}else{
										if(!empty($v2['on']) && $v2['on'] == 100){
											$page_visit_allow = 1;/*二级栏目*/
										}
									}
								}
							}
						}
					}
				}
			}
		}
		if($page_visit_allow == 0){
			/*放行的操作类或展示类方法*/
			$allow_action = array('filetp','ueup','uploadFile','fileups','myTrim','username_check','changearea','changeserver','getgoods','getgoodsedit','days');
			if(!in_array($request->action(),$allow_action)){
				echo '<meta charset="utf-8" />';
				echo '<script>$(document).ready(function(){alertBox("抱歉，您没有操作该功能的权限！","'.$this->m_index.url('web/center/index').'");})</script>';
				die();
			}
		}
		/*公共管理菜单 end*/
		/*面包屑 start*/
		/*查询出最后一个当前on菜单*/
		$bread_path = '';
		$tree_on_id = '';
		foreach ($tree as $k => $v) {
			$bread_on = isset($v['on']) ? $v['on'] : 0;
			if ($bread_on == 100) {
				$bread_path = $v['path'] . $v['id'];
				$tree_on_id = $v['id'];
			}
		}
		$bread_arr = explode(',', $bread_path);
		array_shift($bread_arr);
		/*从拓扑树中取出面包屑内容*/
		$bread = array();
		$treeon = array();
		foreach ($bread_arr as $k => $v) {
			foreach ($tree as $k2 => $v2) {
				if ($v == $v2['id']) {
					$bread[$k] = $v2;
					if ($tree_on_id == $v2['id']) {
						$treeon = $v2;
					}
				}
			}
		}
		$this->assign('bread', $bread); /*面包屑*/
		$this->assign('treeon', $treeon); /*当前栏目*/
		/*面包屑 end*/
		$this->assign('pagelimit', config('paginate.list_rows')); /*默认列表页面条数*/
		/*个人信息*/
		$user = Db::name('user')->field('name,nickname,picture,status')->where('id='.$this->user_id)->find();
		if($user['status'] != 1){
			echo '<meta charset="utf-8" />';
			echo '<script>$(document).ready(function(){alertBox("抱歉，该账号已禁用！","'.url('web/base/login').'");})</script>';
			die();
		}
		$user_picture_arr = explode(',',$user['picture']);
		$user['picture'] = $user_picture_arr[0];
		$this->assign('user',$user); /*当前栏目*/
		/*为空时的替换图片*/
		$this->assign('imageempty', config('view_replace_str.__IMAGEEMPTY__'));
		/*系统图片路径前缀*/
		$system_image = 'http://'.config('system_domain').config('view_replace_str.__SYSTEM__').'/image';
		$this->system_image = $system_image;
		$this->assign('system_image',$system_image);		
	}


	/*进行日志写入*/
	public function write_log($motion){
	       $unknown = 'unknown';
	       if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && $_SERVER['HTTP_X_FORWARDED_FOR'] && strcasecmp($_SERVER['HTTP_X_FORWARDED_FOR'], $unknown)) {
	           $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
	       } else if (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], $unknown)) {
	           $ip = $_SERVER['REMOTE_ADDR'];
	       }
	       if (false !== strpos($ip, ',')) {
	           $ip = reset(explode(',', $ip));
	       }
	       $request = Request::instance();	
			$controller = strtolower($request->controller());
			$action = $request->action();
	       $data=array(
	           'staff_name'=>$this->user_id,
	           'logon_ip'=>$ip,
	           'logon_time'=>time(),
	           'module'=>$controller.'->'.$action,
	           'motion'=>$motion
	       );
	       Db::name('wine_log')->insert($data);
	}

	
	/**
	 * 判断字段是否唯一
	 * @param  [string] $dataname   [数据库名]
	 * @param  [string] $field      [要判断的字段名]
	 * @param  [string] $where_info [条件内容]
	 * @return [type]             [description]
	 */
	public function valiinfo($dataname, $field, $where_info)
	{
		$result = Db::table($dataname)->field('id')->where($field,$where_info)->find();
		if ($result['id']) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * 分类ID 获取商品列表
	 * @return [type] [description]
	 */
    public function getgoods()
    {
        $category_id = input('id');
        if (!isset($category_id)) {
        	echo '<script>$(document).ready(function(){alertBox("参数缺失！","'.$_SERVER['HTTP_REFERER'].'");})</script>';
        	die();
        }
        /*分类搜索*/
        if ($category_id > 0) {
            /*组合当前分类及子分类ID集*/
            $category_where['status'] = 1;
            $category_where['path'] = array('like','%,'.$category_id.',%');
            $categorys = Db::name('wine_shop_cate')->where($category_where)->select();
            $category_str = $category_id;
            if ($categorys) {
                foreach ($categorys as $k => $v) {
                    $category_str.= ','.$v['id'];
                }
            }
        }else{
            $category_str = '';
            foreach($category as $k => $v) {
                $category_str.= ','.$v['id'];
            }
            $category_str = trim($category_str,',');
        }
        $where_list['cate_id'] = array('in', $category_str); /*搜索*/
        $where_list['status'] = array('eq', 1); /*状态*/
        /*条件*/
        $order = array('order' => 'desc', 'id' => 'desc');
        $field = 'id,name';
        $list = Db::name('wine_shop_goods')->field($field)->where($where_list)->order($order)->select();
        $lists = '';
        if (!empty($list)) {
        	$lists = '<option value="" disabled selected >下拉选择商品</option> ';
        }
        foreach ($list as $k => $v) {
            $lists .= ' <option value="'.$v['id'].'">'.$v['name'].'</option> ';
        }
        // var_dump($lists);
        // return json_encode($lists,JSON_UNESCAPED_UNICODE);
        return $lists;
    }

    /**
     * 根据商品ID 获取商品详情
     * @return [type] [description]
     */
    public function getgoodsedit()
    {
    	$id = input('id');
        if (!isset($id)) {
        	echo '<script>$(document).ready(function(){alertBox("参数缺失！","'.$_SERVER['HTTP_REFERER'].'");})</script>';
        	die();
        }
        /*字段*/
        $field = 'id,price_seckill,price,price_seckill_num,spec,total';
        /*条件*/
        $where_list['id'] = $id;
        /*查询*/ 
        $list = Db::name('wine_shop_goods')->field($field)->where($where_list)->find();
        $lists = array();
        if ($list['spec'] == 1) {
        	$lists = Db::name('wine_shop_spec')->where('gid', $id)->select();
        }
        $list['gg'] = $lists;
        echo json_encode($list,JSON_UNESCAPED_UNICODE);
    }

    /**
     * 获取筛选类型名称
     * 香型,品牌,类别,产地,种类
     * @return [type]       [description]
     */
    public function get_type($type)
    {
    	switch ($type) {
    		case '1':
    			$name = '香型';
    			break;
    		case '2':
    			$name = '品牌';
    			break;
    		case '3':
    			$name = '类别';
    			break;
    		case '4':
    			$name = '产地';
    			break;
    		case '5':
    			$name = '种类';
    			break;
    		default:
    			$name = '未知';
    			break;
    	}
    	return $name;
    }
    /**
     * 根据类型获取筛选字段
     * @param  [type] $type [description]
     * @return [type]       [description]
     */
    public function get_type_field($type)
    {
    	switch ($type) {
    		case '1':
    			$name = 'screen_xx';
    			break;
    		case '2':
    			$name = 'screen_pp';
    			break;
    		case '3':
    			$name = 'screen_lb';
    			break;
    		case '4':
    			$name = 'screen_cd';
    			break;
    		case '5':
    			$name = 'screen_zl';
    			break;
		}
    	return $name;
    }

    /**
     * 配送方式 1.立即送 2.预约送 3.快递送 4.自提
     * @param  [type] $type [description]
     * @return [type]       [description]
     */
    public function dispatching($type)
    {
    	switch ($type) {
    		case 1:
    			$content = '立即送';
    			break;
    		case 2:
    			$content = '预约送';
    			break;
    		case 3:
    			$content = '快递送';
    			break;
    		case 4:
    			$content = '自提';
    			break;
    	}
    	return $content;
    }

    /**
     * 自动确认收货
     */
    public function take()
    {
    	/*查询设定收货是时间*/
    	// $result = Db::name('site')->where('id', $this->site_id)->field('take')->find();
    	/*过期时间*/
    	$newtime = time();
    	/*条件*/
    	$where['express_time'] = array('lt', $newtime);
    	$where['status'] = array('eq', 3);
    	 /*查询*/
        $list = Db::name('wine_shop_order_goods')->field('ordersn,uid')->where($where)->select();
        foreach ($list as $k => $v) {
            /*结算佣金*/
            $this->ordersn_distribution($v['ordersn']);
            /*会员等级*/
            $this->user_grade($v['uid']);
        }
    	/*修改数据*/
    	$update = array(
    		'status' => 4,
    		'edittime' => time()
    		);
    	Db::name('wine_shop_order_goods')->where($where)->update($update);
    }
    
    /**
     * 自动取消未付款
     */
    public function takes()
    {
        /*过期时间*/
        $newtime = time()+5;
        /*条件*/
        $where['overtime'] = array('lt', $newtime);
        $where['status'] = array('eq', 1);
        /*修改数据*/
        $update = array(
            'status' => 6,
            'edittime' => time()
            );
        Db::name('wine_shop_order_goods')->where($where)->update($update);
    }

    /*进行表格导出*/
	protected function createtable($list,$filename,$header=array(),$index = array()){
	    ob_end_clean();
	    header("Content-type:application/vnd.ms-excel");
	    header("Content-Disposition:filename=".$filename.".xls");
	    $teble_header = implode("\t",$header);
	    $strexport = $teble_header."\r";
	    foreach ($list as $row){
	        foreach($index as $val){
	            $strexport.=$row[$val]."\t";
	        }
	        $strexport.="\r";
	    }
	    $strexport=iconv('UTF-8',"GB2312//IGNORE",$strexport);
	    exit($strexport);
	}

	/**
	 * 结算佣金
	 * @return [type]          [description]
	 */
	public function ordersn_distribution($ordersn = '66f041eccbc8e8fe0720180703jwtn23')
	{
		$field = 'commission1,commission2,status';
		/*分销信息*/
		$info = Db::name('wine_commission')->field($field)->find();
		if ($info['status'] == 0) {
			return 0;
		}
		/*固定字段*/
		$field = 'o.id,g.price,o.uid,g.fx,g.commission1,g.commission2';
		/*条件*/
		$where['o.ordersn'] = array('eq', $ordersn);
		$list = Db::name('wine_shop_goods_order')
					->alias('o')
					->join('wine_shop_goods g', 'o.goodsid = g.id')
					->field($field)
					->where($where)
					->select();
		if (!$list) {
			return 0;/*为空则结束*/
		}
		$money = 0;
		$moneys = 0;
		/*分销层级*/
		$cii = Db::name('wine_users')->where('id', $list[0]['uid'])->field('agentid')->find();
		if (empty($cii['agentid'])) {
			return 0;/*没有分销-直接结束*/
		}
		/*二级分销*/
		$re = 0;
		$cii_one = Db::name('wine_users')->where('id', $cii['agentid'])->field('agentid')->find();
		if (empty($cii['agentid'])) {
			$re = 1;/*没有分销-直接结束*/
		}
		foreach ($list as $v) {
			/*条件*/
			$where_order['id'] = array('eq', $v['id']);
			$where_order['ordersn'] = array('eq', $ordersn);
			/*修改数据*/
			if ($v['fx'] == 1) {
				/*独立分销*/
				$update['commission1'] = $v['commission1']*$v['price']/100;/*一级佣金*/
			} else {
				/*通用分销*/
				$update['commission1'] = $info['commission1']*$v['price']/100;/*一级佣金*/
			}
			if (!empty($re)) {
				if ($v['fx'] == 1) {
					/*独立分销*/
					$update['commission2'] = $v['commission2']*$v['price']/100;/*二级佣金*/
				} else {
					/*通用分销*/
					$update['commission2'] = $info['commission2']*$v['price']/100;/*二级佣金*/
				}
				$moneys += $update['commission2'];
			}
			$update['status_fx'] = 2;/*享有分销*/
			/*执行修改*/
			$result = Db::name('wine_shop_goods_order')->where($where_order)->update($update);
			/*累计金额*/
			$money += $update['commission1'];
		
		}
		/*一级分销用户信息-增加佣金*/
		Db::name('wine_users')->where('id', $cii['agentid'])->setInc('mabycommissiontotal',$money);/*佣金总额*/
		Db::name('wine_users')->where('id', $cii['agentid'])->setInc('commissiontotal',$money);/*可提现佣金额度*/
		/*二级分销用户信息-增加佣金*/
		if ($re) {
			Db::name('wine_users')->where('id', $cii_one['agentid'])->setInc('mabycommissiontotal',$moneys);/*佣金总额*/
			Db::name('wine_users')->where('id', $cii_one['agentid'])->setInc('commissiontotal',$moneys);/*可提现佣金额度*/
		}
		return 1;
	}
	
	/**
	 * 会员等级 - 分销商
	 */
	public function user_grade($uid)
	{
		/*修改数据*/
		$update['edittime'] = time();
		/*固定字段*/
		$field = 'agentlevel,ranks';
		/*条件*/
		$where['id'] = array('eq', $uid);
		/*查询用户信息*/
		$list = Db::name('wine_users')->field($field)->where($where)->find();
		/*条件*/
		$where_one['uid'] = $uid;
		$where_one['status'] = 4;
		$field = 'sum(price) as price';
		/*消费总价*/
		$price = Db::name('wine_shop_order_goods')->field($field)->where($where_one)->find();
		$price['price'] = $price['price'] ? $price['price'] : 0;
		/*条件*/
		$where_r['status'] = array('eq', 1);
		$field = 'id,quota';
		/*会员等级*/
		$number = 0;
		$lists = Db::name('wine_users_ranks')->field($field)->where($where_r)->select();
		foreach ($lists as $v) {
			if ($price['price'] > $v['quota']) {
				$number = $v['id'];
			}
		}
		if ($list['ranks'] < $number) {
			/*等级*/
			$update['ranks'] = $number;
		}
		/*分销商*/
		if ($list['agentlevel'] == 2) {
			$commission = Db::name('wine_commission')->field('ordermoney')->where('id', 1)->find();
			if ($price['price'] > $commission['ordermoney']) {
				$update['agentlevel'] = 1;
			}
		}
		Db::name('wine_users')->where('id', $uid)->update($update);
	}

	//导出excel的方法
	public function create_xls($data,$filename='simple.xls'){
		ini_set('max_execution_time', '0');
		include_once './vendor/excel/PHPExcel.php';
		$filename=str_replace('.xls', '', $filename).'.xls';
		$phpexcel = new \PHPExcel();
		$phpexcel->getProperties()
			->setCreator("Maarten Balliauw")
			->setLastModifiedBy("Maarten Balliauw")
			->setTitle("Office 2007 XLSX Test Document")
			->setSubject("Office 2007 XLSX Test Document")
			->setDescription("Test document for Office 2007 XLSX, generated using PHP classes.")
			->setKeywords("office 2007 openxml php")
			->setCategory("Test result file");
		$phpexcel->getActiveSheet()->fromArray($data);
		$phpexcel->getActiveSheet()->setTitle('Sheet1');
		$phpexcel->setActiveSheetIndex(0);
		ob_end_clean();//清除缓冲区,避免乱码
		header('Content-Type: application/vnd.ms-excel');
		header("Content-Disposition: attachment;filename=$filename");
		header('Cache-Control: max-age=0');
		header('Cache-Control: max-age=1');
		header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
		header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
		header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
		header ('Pragma: public'); // HTTP/1.0
		$objwriter = \PHPExcel_IOFactory::createWriter($phpexcel, 'Excel5');
		$objwriter->save('php://output');
		exit;
	}



}
