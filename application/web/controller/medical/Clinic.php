<?php
namespace app\web\controller\medical;
use app\web\controller\Common;
use think\Request;
use think\Db;
/*诊所管理中心*/
class Clinic extends Common {
	public $pageHead;/*定义页面头部*/
	public $pageFoot;/*定义页面底部*/
	public $temp_id;/*模板ID*/
	
	/*构造方法*/
	public function _initialize() {
		/*重载父类构造方法*/
		parent::_initialize();		
	}

	/*诊所管理 start*/
	/*诊所列表*/
	public function lists(){
		/*查询省*
		$province = Db::name('areacode')->field('id,name')->where('pid=7 AND status=1')->select();
		$this->assign('province',$province);		
		/*查询内容*/
		/*省、市、区搜索*
		$province_id = !empty(input('province')) ? input('province') : 0;
		$this->assign('province_id', $province_id);
		if ($province_id > 0) {
			$where_list['province'] = $province_id;
		}	
		/*查询市*
		if($province_id > 0){
			$city = Db::name('areacode')->field('id,name')->where('pid='.$province_id.' AND status=1')->select();
			$this->assign('city',$city);
		}	
		$city_id = !empty(input('city')) ? input('city') : 0;
		$this->assign('city_id', $city_id);
		if ($city_id > 0) {
			$where_list['city'] = $city_id;
		}	
		/*查询区*
		if($city_id > 0){
			$area = Db::name('areacode')->field('id,name')->where('pid='.$city_id.' AND status=1')->select();
			$this->assign('area',$area);
		}	
		$area_id = !empty(input('area')) ? input('area') : 0;
		$this->assign('area_id', $area_id);
		if ($area_id > 0) {
			$where_list['area'] = $area_id;
		}						
		/*获取参数*/
		$limit = input('limit');
		$search = input('search');
		$this->assign('limit', $limit);
		$this->assign('search', $search);
		$pagelimit = !empty($limit) ? $limit : config('paginate.list_rows'); /*每页条数*/
		if (!empty($search)) {
			$where_list['concat(name,description)'] = array('like', '%' . $search . '%'); /*搜索*/
		}
		/*分页参数*/
		$paginate = array('query' => array( /*url额外参数*/
		//'province' => $province_id,
		//'city' => $city_id,
		//'area' => $area_id,
		'limit' => $limit, /*每页条数*/
		'search' => $search, /*搜索*/
		));
		/*条件*/
		$where_list['site_id'] = $this->site_id;
		$order = array('order' => 'desc', 'id' => 'desc');
		$list_total = Db::name('clinic')->field('id')->where($where_list)->select();
		$list_total_count = count($list_total);
		$list = Db::name('clinic')->where($where_list)->order($order)->paginate($pagelimit, false, $paginate);
		$lists = array();
		foreach ($list as $k => $v) {
			$lists[$k] = $v;
			/*解析省、市、区*
			foreach($v as $k2=>$v2){
				if ($k2 == 'province' && !empty($v2)) {
					$list_area = Db::name('areacode')->field('id,name')->where('id='.$v2)->find();
					$lists[$k]['province'] = $list_area['name'];
				}
				if ($k2 == 'city' && !empty($v2)) {
					$list_area = Db::name('areacode')->field('id,name')->where('id='.$v2)->find();
					$lists[$k]['city'] = $list_area['name'];
				}
				if ($k2 == 'area' && !empty($v2)) {
					$list_area = Db::name('areacode')->field('id,name')->where('id='.$v2)->find();
					$lists[$k]['area'] = $list_area['name'];
			}
			}						
			/*状态*/
			if ($v['status'] == 1) {
				$lists[$k]['status'] = '启用';
			} else {
				$lists[$k]['status'] = '禁用';
			}
		}
		$page = $list->render(); /*获取分页显示*/
		$this->assign('list', $lists);
		$this->assign('list_total_count',$list_total_count);
		$this->assign('page', $page);
		/*分配变量*/
		$this->assign('title', '诊所列表'); /*页面标题*/
		$this->assign('keywords', '诊所列表'); /*页面关键词*/
		$this->assign('description', '诊所列表'); /*页面描述*/
		/*调用模板*/
		return $this->fetch(TEMP_FETCH);
	}
	/*编辑诊所*/
	public function edit() {
		/*查询州*/
		$state = Db::name('areacode')->field('id,name')->where('pid=0 AND status=1')->select();
		$this->assign('state',$state);		
		/*获取参数*/
		$id = input('id');/*内容Id*/
		if ($id) {
			/*编辑*/
			/*查询参数*/
			$where['id'] = $id;
			$contents = Db::name('clinic')->where($where)->find();
			foreach ($contents as $k => $v) {
				$content[$k] = $v;
				/*解析图标、图片*/
				if ($k == 'ico' && !empty($content['ico'])) {
					$ico = explode(',', $v);
					$content['ico'] = array();
					foreach ($ico as $k2 => $v2) {						$basename = explode('/',$v2);
						$basename = array_pop($basename);
						$content['ico'][$k2] = array('filename' => $v2, 'basename' => $basename);
					}
				}
				if ($k == 'picture' && !empty($content['picture'])) {
					$picture = explode(',', $v);
					$content['picture'] = array();
					foreach ($picture as $k2 => $v2) {						$basename = explode('/',$v2);
						$basename = array_pop($basename);
						$content['picture'][$k2] = array('filename' => $v2,'basename' => $basename);
					}
				}
				if ($k == 'album' && !empty($content['album'])) {
					$picture = explode(',', $v);
					$content['album'] = array();
					foreach ($picture as $k2 => $v2) {						$basename = explode('/',$v2);
						$basename = array_pop($basename);
						$content['album'][$k2] = array('filename' => $v2,'basename' => $basename);
					}
				}
				if ($k == 'coords') {
					if(!empty($content['coords'])){
						/*解析坐标*/
						$coords = explode(',',$v);
						$locaiton_coords = array();
						$locaiton_coords['lat'] = $coords[0];
						$locaiton_coords['lng'] = $coords[1];
					}else{
						/*IP所在位置*/
						$locaiton_coords = array();
						if($_SESSION['az']['client']['city']['lat'] == '未知' || $_SESSION['az']['client']['city']['log'] == '未知'){
							$locaiton_coords['lat'] = 28.09953;
							$locaiton_coords['lng'] = 112.980773;
						}else{
							$locaiton_coords['lat'] = $_SESSION['az']['client']['city']['lat'];
							$locaiton_coords['lng'] = $_SESSION['az']['client']['city']['log'];							
						}			
					}
					$this->assign('locaiton_coords', $locaiton_coords);
				}			
				/*查询国*/
				if($k == 'state' && !empty($contents['state'])){
					$country = Db::name('areacode')->field('id,name')->where('pid='.$contents['state'].' AND status=1')->select();
					$this->assign('country',$country);
				}
				/*查询省*/
				if($k == 'country' && !empty($contents['country'])){
					$province = Db::name('areacode')->field('id,name')->where('pid='.$contents['country'].' AND status=1')->select();
					$this->assign('province',$province);
				}
				/*查询市*/
				if($k == 'province' && !empty($contents['province'])){
					$city = Db::name('areacode')->field('id,name')->where('pid='.$contents['province'].' AND status=1')->select();
					$this->assign('city',$city);
				}
				/*查询区*/
				if($k == 'city' && !empty($contents['city'])){
					$area = Db::name('areacode')->field('id,name')->where('pid='.$contents['city'].' AND status=1')->select();
					$this->assign('area',$area);
				}							
			}
			$this->assign('content', $content); /*内容*/
			/*组装post提交变量*/
			$submit = array('name' => '确认编辑', 'url' => '?id=' . $id);
			$this->assign('submit', $submit);
			/*分配变量*/
			$this->assign('title', '编辑诊所'); /*页面标题*/
			$this->assign('keywords', '编辑诊所'); /*页面关键词*/
			$this->assign('description', '编辑诊所'); /*页面描述*/
		} else {			
			/*添加*/
			/*查询省份*/
			$province = Db::name('areacode')->field('id,name')->where('pid=7 AND status=1')->select();
			$this->assign('province',$province);
			/*IP所在位置*/
			$locaiton_coords = array();
			if($_SESSION['az']['client']['city']['lat'] == '未知' || $_SESSION['az']['client']['city']['log'] == '未知'){
				$locaiton_coords['lat'] = 28.09953;
				$locaiton_coords['lng'] = 112.980773;
			}else{
				$locaiton_coords['lat'] = $_SESSION['az']['client']['city']['lat'];
				$locaiton_coords['lng'] = $_SESSION['az']['client']['city']['log'];							
			}
			$this->assign('locaiton_coords', $locaiton_coords);
			/*组装post提交变量*/
			$submit = array('name' => '确认添加', 'url' => '?a=add');
			$this->assign('submit', $submit);
			/*分配变量*/
			$this->assign('title', '添加诊所'); /*页面标题*/
			$this->assign('keywords', '添加诊所'); /*页面关键词*/
			$this->assign('description', '添加诊所'); /*页面描述*/
		}
		/*提交*/
		if ($_POST) {
			/*获取参数*/
			$nowtime = time();
			/*验证参数*/
			if (empty(input('name'))) {
				echo '<script>$(document).ready(function(){alertBox("名称不可为空..","'.$_SERVER['HTTP_REFERER'].'");})</script>';
			} else {
				/*组装参数*/
				$data = array(
					'site_id'		=> $this->site_id,/*站点ID*/
					'name' 			=> input('name'),/*名称*/
					'ico' 			=> input('ico'),/*图标*/
					'picture' 		=> input('picture'),/*缩略图*/
					'album' 		=> input('album'),/*相册*/
					'description' 	=> input('description'),/*简介*/
					'content' 		=> input('content'),/*内容*/
					'province' 		=> input('province'),/*省*/
					'city' 			=> input('city'),/*市*/
					'area' 			=> input('area'),/*区*/
					'address' 		=> input('address'),/*地址*/
					'coords' 		=> input('coords'),/*坐标*/
					'workday' 		=> input('workday'),/*上班时间*/
					'tel' 			=> input('tel'),/*电话*/
					'order' 		=> input('order'),/*排序 默认为100*/
					'status'		=> input('status'),/*状态 默认为1:启用|0:禁用*/
					'edittime' 		=> $nowtime,/*修改时间*/
				);
				if ($id) {
					/*编辑*/
					/*更新数据*/
					$result = Db::name('clinic')->where('id='.$id)->update($data);
					$alert_success = '恭喜，编辑成功！';
					$alert_error = '抱歉，编辑失败！';
					/*判断结果集*/
					if ($result) {
						echo '<script>$(document).ready(function(){alertBox("'.$alert_success.'","'.$_SERVER['HTTP_REFERER'].'");})</script>';
					} else {
						echo '<script>$(document).ready(function(){alertBox("'.$alert_error.'","'.$_SERVER['HTTP_REFERER'].'");})</script>';
					}					
				} else {
					/*添加*/
					$data['addtime'] = $nowtime; /*添加时间*/
					/*插入数据*/
					$result = Db::name('clinic')->insert($data);
					$alert_success = '恭喜，添加成功！';
					$alert_error = '抱歉，添加失败！';
					/*判断结果集*/
					if ($result) {
						echo '<script>$(document).ready(function(){alertBox("'.$alert_success.'","'.url('medical.clinic/lists').'");})</script>';
					} else {
						echo '<script>$(document).ready(function(){alertBox("'.$alert_error.'","'.$_SERVER['HTTP_REFERER'].'");})</script>';
					}					
				}
			}
		} else {
			/*调用模板*/
			return $this->fetch(TEMP_FETCH);
		}
	}
	/*删除诊所*/
	public function del() {
		/*接收参数*/
		$id = input('id'); /*诊所ID*/
		$result = Db::name('clinic')->where('id='.$id)->delete();
		if ($result) {
			echo '<script>$(document).ready(function(){alertBox("恭喜，删除成功！","'.$_SERVER['HTTP_REFERER'].'");})</script>';
		} else {
			echo '<script>$(document).ready(function(){alertBox("抱歉，删除失败！","'.$_SERVER['HTTP_REFERER'].'");})</script>';
		}
	}
	/*诊所管理 end*/

	/*备忘录管理 start*/
	/*备忘录列表*/
	public function memo(){						
		/*获取参数*/
		$t_start = input('t_start');/*开始时间*/
		$this->assign('t_start',$t_start);
		$t_start_data = strtotime($t_start);
		$this->assign('t_start_data',$t_start_data);
		$t_end = input('t_end');/*结束时间*/
		$this->assign('t_end',$t_end);
		$t_end_data = strtotime($t_end);
		$this->assign('t_end_data',$t_end_data);		
		$clinic_id = input('clinic_id');
		$this->assign('clinic_id',$clinic_id);
		if(empty($clinic_id)){
			echo '<meta charset="utf-8" />';
			echo '<script>$(document).ready(function(){alertBox("诊所参数缺失！","'.url('medical.clinic/lists').'");})</script>';
			die();
		}
		$limit = input('limit');
		$search = input('search');
		$this->assign('limit', $limit);
		$this->assign('search', $search);
		$pagelimit = !empty($limit) ? $limit : config('paginate.list_rows'); /*每页条数*/
		if (!empty($search)) {
			$where_list['concat(id,content)'] = array('like', '%' . $search . '%'); /*搜索*/
		}
		/*分页参数*/
		$paginate = array('query' => array( /*url额外参数*/
		'clinic_id' => $clinic_id,/*诊所ID*/
		't_start' => $t_start,
		't_end' => $t_end,
		'limit' => $limit, /*每页条数*/
		'search' => $search, /*搜索*/
		));
		/*条件*/
		$where_list2 = array();
		if(!empty($t_start_data) && empty($t_end_data)){
			$where_list['alarm'] = array('>=',$t_start_data);
		}else if(!empty($t_end_data) && empty($t_start_data)){
			$where_list['alarm'] = array('<=',$t_end_data);
		}else if(!empty($t_end_data) && !empty($t_end_data)){
			$where_list['alarm'] = array('>=',$t_start_data);
			$where_list2['alarm'] = array('<=',$t_end_data);
		}
		$where_list['site_id'] = $this->site_id;
		$where_list['clinic_id'] = $clinic_id;
		$order = array('alarm'=>'asc','order'=>'desc','id'=>'desc');
		$list_total = Db::name('clinic_memo')->field('id')->where($where_list)->select();
		$list_total_count = count($list_total);
		$list = Db::name('clinic_memo')->where($where_list)->where($where_list2)->order($order)->paginate($pagelimit, false, $paginate);
		$lists = array();
		foreach ($list as $k => $v) {
			$lists[$k] = $v;
			/*格式化时间*/
			$lists[$k]['alarm'] = date('Y/m/d H:i',$v['alarm']);	
			/*所属诊所*/
			if(!empty($v['clinic_id'])){
				$list_clinic = Db::name('clinic')->field('id,name')->where('id='.$clinic_id)->find();
				$lists[$k]['clinic_name'] = $list_clinic['name'];
			}	
			/*状态*/
			if ($v['status'] == 1) {
				$lists[$k]['status'] = '启用';
			} else {
				$lists[$k]['status'] = '禁用';
			}
		}
		$page = $list->render(); /*获取分页显示*/
		$this->assign('list', $lists);
		$this->assign('list_total_count',$list_total_count);
		$this->assign('page', $page);
		/*分配变量*/
		$this->assign('title', '备忘录列表'); /*页面标题*/
		$this->assign('keywords', '备忘录列表'); /*页面关键词*/
		$this->assign('description', '备忘录列表'); /*页面描述*/
		/*调用模板*/
		return $this->fetch(TEMP_FETCH);
	}
	/*编辑备忘录*/
	public function memoedit() {
		/*所属诊所*/
		$clinic_id = input('clinic_id');
		$this->assign('clinic_id',$clinic_id);
		if(empty($clinic_id)){
			echo '<meta charset="utf-8" />';
			echo '<script>$(document).ready(function(){alertBox("诊所参数缺失！","'.url('medical.clinic/lists').'");})</script>';
			die();
		}	
		/*获取参数*/
		$id = input('id');/*内容Id*/
		if ($id) {
			/*编辑*/
			/*查询参数*/
			$where['id'] = $id;
			$contents = Db::name('clinic_memo')->where($where)->find();
			foreach ($contents as $k => $v) {
				$content[$k] = $v;
				/*格式化时间*/
				if($k == 'alarm' && !empty($contents['alarm'])){
					$content['alarm'] = date('Y/m/d H:i',$contents['alarm']);
				}
			}
			$this->assign('content', $content); /*内容*/
			/*组装post提交变量*/
			$submit = array('name' => '确认编辑', 'url' => '?id=' . $id);
			$this->assign('submit', $submit);
			/*分配变量*/
			$this->assign('title', '编辑备忘录'); /*页面标题*/
			$this->assign('keywords', '编辑备忘录'); /*页面关键词*/
			$this->assign('description', '编辑备忘录'); /*页面描述*/
		} else {			
			/*添加*/
			/*组装post提交变量*/
			$submit = array('name' => '确认添加', 'url' => '?a=add');
			$this->assign('submit', $submit);
			/*分配变量*/
			$this->assign('title', '添加备忘录'); /*页面标题*/
			$this->assign('keywords', '添加备忘录'); /*页面关键词*/
			$this->assign('description', '添加备忘录'); /*页面描述*/
		}
		/*提交*/
		if ($_POST) {
			/*获取参数*/
			$nowtime = time();
			/*验证参数*/
			if (empty(input('alarm'))) {
				echo '<script>$(document).ready(function(){alertBox("提醒时间不可为空..","'.$_SERVER['HTTP_REFERER'].'");})</script>';
			}else if(empty(input('content'))){
				echo '<script>$(document).ready(function(){alertBox("内容不可为空..","'.$_SERVER['HTTP_REFERER'].'");})</script>';
			}else{
				/*组装参数*/
				$data = array(
					'site_id'		=> $this->site_id,/*站点ID*/
					'clinic_id' 	=> input('clinic_id'),/*诊所ID*/
					'user_id' 		=> $this->user_id,/*用户ID*/
					'alarm' 		=> strtotime(input('alarm')),/*提醒时间*/
					'content' 		=> input('content'),/*内容*/
					'order' 		=> input('order'),/*排序 默认为100*/
					'status'		=> input('status'),/*状态 默认为1:启用|0:禁用*/
					'edittime' 		=> $nowtime,/*修改时间*/
				);
				if ($id) {
					/*编辑*/
					/*更新数据*/
					$result = Db::name('clinic_memo')->where('id='.$id)->update($data);
					$alert_success = '恭喜，编辑成功！';
					$alert_error = '抱歉，编辑失败！';
					/*判断结果集*/
					if ($result) {
						echo '<script>$(document).ready(function(){alertBox("'.$alert_success.'","'.$_SERVER['HTTP_REFERER'].'");})</script>';
					} else {
						echo '<script>$(document).ready(function(){alertBox("'.$alert_error.'","'.$_SERVER['HTTP_REFERER'].'");})</script>';
					}					
				} else {
					/*添加*/
					$data['addtime'] = $nowtime; /*添加时间*/
					/*插入数据*/
					$result = Db::name('clinic_memo')->insert($data);
					$alert_success = '恭喜，添加成功！';
					$alert_error = '抱歉，添加失败！';
					/*判断结果集*/
					if ($result) {
						echo '<script>$(document).ready(function(){alertBox("'.$alert_success.'","'.url('medical.clinic/memo').'?clinic_id='.$clinic_id.'");})</script>';
					} else {
						echo '<script>$(document).ready(function(){alertBox("'.$alert_error.'","'.$_SERVER['HTTP_REFERER'].'");})</script>';
					}					
				}
			}
		} else {
			/*调用模板*/
			return $this->fetch(TEMP_FETCH);
		}
	}
	/*删除备忘录*/
	public function memodel() {
		/*接收参数*/
		$id = input('id'); /*备忘录ID*/
		$result = Db::name('clinic_memo')->where('id='.$id)->delete();
		if ($result) {
			echo '<script>$(document).ready(function(){alertBox("恭喜，删除成功！","'.$_SERVER['HTTP_REFERER'].'");})</script>';
		} else {
			echo '<script>$(document).ready(function(){alertBox("抱歉，删除失败！","'.$_SERVER['HTTP_REFERER'].'");})</script>';
		}
	}
	/*备忘录管理 end*/
	
	/*关注管理 start*/
	/*关注列表*/
	public function attention(){						
		/*获取参数*/
		$t_start = input('t_start');/*开始时间*/
		$this->assign('t_start',$t_start);
		$t_start_data = strtotime($t_start);
		$this->assign('t_start_data',$t_start_data);
		$t_end = input('t_end');/*结束时间*/
		$this->assign('t_end',$t_end);
		$t_end_data = strtotime($t_end);
		$this->assign('t_end_data',$t_end_data);		
		$clinic_id = input('clinic_id');
		$this->assign('clinic_id',$clinic_id);
		if(empty($clinic_id)){
			echo '<meta charset="utf-8" />';
			echo '<script>$(document).ready(function(){alertBox("诊所参数缺失！","'.url('medical.clinic/lists').'");})</script>';
			die();
		}
		$limit = input('limit');
		$search = input('search');
		$this->assign('limit', $limit);
		$this->assign('search', $search);
		$pagelimit = !empty($limit) ? $limit : config('paginate.list_rows'); /*每页条数*/
		if (!empty($search)) {
			$where_list['concat(user_id)'] = array('like', '%' . $search . '%'); /*搜索*/
		}
		/*分页参数*/
		$paginate = array('query' => array( /*url额外参数*/
		'clinic_id' => $clinic_id,/*诊所ID*/
		't_start' => $t_start,
		't_end' => $t_end,
		'limit' => $limit, /*每页条数*/
		'search' => $search, /*搜索*/
		));
		/*条件*/
		$where_list2 = array();
		if(!empty($t_start_data) && empty($t_end_data)){
			$where_list['edittime'] = array('>=',$t_start_data);
		}else if(!empty($t_end_data) && empty($t_start_data)){
			$where_list['edittime'] = array('<=',$t_end_data);
		}else if(!empty($t_end_data) && !empty($t_end_data)){
			$where_list['edittime'] = array('>=',$t_start_data);
			$where_list2['edittime'] = array('<=',$t_end_data);
		}
		$where_list['site_id'] = $this->site_id;
		$where_list['clinic_id'] = $clinic_id;
		$order = array('edittime'=>'desc','order'=>'desc','id'=>'desc');
		$list_total = Db::name('clinic_attention')->field('id')->where($where_list)->select();
		$list_total_count = count($list_total);
		$list = Db::name('clinic_attention')->where($where_list)->where($where_list2)->order($order)->paginate($pagelimit, false, $paginate);
		$lists = array();
		foreach ($list as $k => $v) {
			$lists[$k] = $v;	
			/*所属诊所*/
			if(!empty($v['clinic_id'])){
				$list_clinic = Db::name('clinic')->field('id,name')->where('id='.$clinic_id)->find();
				$lists[$k]['clinic_name'] = $list_clinic['name'];
			}
			/*用户信息*/
			if(!empty($v['user_id'])){
				$list_user = Db::name('user')->field('id,nickname,picture')->where('id='.$v['user_id'])->find();
				$lists[$k]['user_nickname'] = $list_user['nickname'];
				$list_user_picture_arr = explode(',',$list_user['picture']);
				$lists[$k]['user_picture'] = $list_user_picture_arr[0];
			}				
			/*状态*/
			if ($v['status'] == 1) {
				$lists[$k]['status'] = '已关注';
			}else if($v['status'] == 2){
				$lists[$k]['status'] = '取消关注';
			}
		}
		$page = $list->render(); /*获取分页显示*/
		$this->assign('list', $lists);
		$this->assign('list_total_count',$list_total_count);
		$this->assign('page', $page);
		/*分配变量*/
		$this->assign('title', '关注列表'); /*页面标题*/
		$this->assign('keywords', '关注列表'); /*页面关键词*/
		$this->assign('description', '关注列表'); /*页面描述*/
		/*调用模板*/
		return $this->fetch(TEMP_FETCH);
	}
	/*编辑关注*/
	public function attentionedit() {
		/*所属诊所*/
		$clinic_id = input('clinic_id');
		$this->assign('clinic_id',$clinic_id);
		if(empty($clinic_id)){
			echo '<meta charset="utf-8" />';
			echo '<script>$(document).ready(function(){alertBox("诊所参数缺失！","'.url('medical.clinic/lists').'");})</script>';
			die();
		}	
		/*获取参数*/
		$id = input('id');/*内容Id*/
		if ($id) {
			/*编辑*/
			/*查询参数*/
			$where['id'] = $id;
			$contents = Db::name('clinic_attention')->where($where)->find();
			foreach ($contents as $k => $v) {
				$content[$k] = $v;
			}
			$this->assign('content', $content); /*内容*/
			/*组装post提交变量*/
			$submit = array('name' => '确认编辑', 'url' => '?id=' . $id);
			$this->assign('submit', $submit);
			/*分配变量*/
			$this->assign('title', '编辑关注'); /*页面标题*/
			$this->assign('keywords', '编辑关注'); /*页面关键词*/
			$this->assign('description', '编辑关注'); /*页面描述*/
		} else {			
			/*添加*/
			/*组装post提交变量*/
			$submit = array('name' => '确认添加', 'url' => '?a=add');
			$this->assign('submit', $submit);
			/*分配变量*/
			$this->assign('title', '添加关注'); /*页面标题*/
			$this->assign('keywords', '添加关注'); /*页面关键词*/
			$this->assign('description', '添加关注'); /*页面描述*/
		}
		/*提交*/
		if ($_POST) {
			/*获取参数*/
			$nowtime = time();
			/*验证参数*/
			if (empty(input('user_id'))) {
				echo '<script>$(document).ready(function(){alertBox("用户ID不能为空..","'.$_SERVER['HTTP_REFERER'].'");})</script>';
			}else{
				/*组装参数*/
				$data = array(
					'site_id'		=> $this->site_id,/*站点ID*/
					'clinic_id' 	=> input('clinic_id'),/*诊所ID*/
					'user_id' 		=> input('user_id'),/*用户ID*/
					'order' 		=> 100,/*排序 默认为100*/
					'status'		=> input('status'),/*状态 默认为1:关注|2:取消关注*/
					'edittime' 		=> $nowtime,/*修改时间*/
				);
				if ($id) {
					/*编辑*/
					/*更新数据*/
					$result = Db::name('clinic_attention')->where('id='.$id)->update($data);
					$alert_success = '恭喜，编辑成功！';
					$alert_error = '抱歉，编辑失败！';
					/*判断结果集*/
					if ($result) {
						echo '<script>$(document).ready(function(){alertBox("'.$alert_success.'","'.$_SERVER['HTTP_REFERER'].'");})</script>';
					} else {
						echo '<script>$(document).ready(function(){alertBox("'.$alert_error.'","'.$_SERVER['HTTP_REFERER'].'");})</script>';
					}					
				} else {
					/*添加*/
					$data['addtime'] = $nowtime; /*添加时间*/
					/*插入数据*/
					$result = Db::name('clinic_attention')->insert($data);
					$alert_success = '恭喜，添加成功！';
					$alert_error = '抱歉，添加失败！';
					/*判断结果集*/
					if ($result) {
						echo '<script>$(document).ready(function(){alertBox("'.$alert_success.'","'.url('medical.clinic/attention').'?clinic_id='.$clinic_id.'");})</script>';
					} else {
						echo '<script>$(document).ready(function(){alertBox("'.$alert_error.'","'.$_SERVER['HTTP_REFERER'].'");})</script>';
					}					
				}
			}
		} else {
			/*调用模板*/
			return $this->fetch(TEMP_FETCH);
		}
	}
	/*删除关注*/
	public function attentiondel() {
		/*接收参数*/
		$id = input('id'); /*关注ID*/
		$result = Db::name('clinic_attention')->where('id='.$id)->delete();
		if ($result) {
			echo '<script>$(document).ready(function(){alertBox("恭喜，删除成功！","'.$_SERVER['HTTP_REFERER'].'");})</script>';
		} else {
			echo '<script>$(document).ready(function(){alertBox("抱歉，删除失败！","'.$_SERVER['HTTP_REFERER'].'");})</script>';
		}
	}
	/*关注管理 end*/	

	/*送药订单管理 start*/
	/*送药订单列表*/
	public function order_out(){						
		/*获取参数*/
		$t_start = input('t_start');/*开始时间*/
		$this->assign('t_start',$t_start);
		$t_start_data = strtotime($t_start);
		$this->assign('t_start_data',$t_start_data);
		$t_end = input('t_end');/*结束时间*/
		$this->assign('t_end',$t_end);
		$t_end_data = strtotime($t_end);
		$this->assign('t_end_data',$t_end_data);		
		$clinic_id = input('clinic_id');
		$this->assign('clinic_id',$clinic_id);
		if(empty($clinic_id)){
			echo '<meta charset="utf-8" />';
			echo '<script>$(document).ready(function(){alertBox("诊所参数缺失！","'.url('medical.clinic/lists').'");})</script>';
			die();
		}
		/*状态搜索*/
		$status = !empty(input('status'))?input('status'):0;
		$this->assign('status',$status);
		if($status != 0){
			$where_list['status'] = $status;
		}			
		$limit = input('limit');
		$search = input('search');
		$this->assign('limit', $limit);
		$this->assign('search', $search);
		$pagelimit = !empty($limit) ? $limit : config('paginate.list_rows'); /*每页条数*/
		if (!empty($search)) {
			$where_list['concat(no,title)'] = array('like', '%' . $search . '%'); /*搜索*/
		}
		/*分页参数*/
		$paginate = array('query' => array( /*url额外参数*/
		'clinic_id' => $clinic_id,/*诊所ID*/
		't_start' => $t_start,
		't_end' => $t_end,
		'status' => $status,	/*状态*/
		'limit' => $limit, /*每页条数*/
		'search' => $search, /*搜索*/
		));
		/*条件*/
		$where_list2 = array();
		if(!empty($t_start_data) && empty($t_end_data)){
			$where_list['addtime'] = array('>=',$t_start_data);
		}else if(!empty($t_end_data) && empty($t_start_data)){
			$where_list['addtime'] = array('<=',$t_end_data);
		}else if(!empty($t_end_data) && !empty($t_end_data)){
			$where_list['addtime'] = array('>=',$t_start_data);
			$where_list2['addtime'] = array('<=',$t_end_data);
		}
		$where_list['site_id'] = $this->site_id;
		$where_list['clinic_id'] = $clinic_id;
		$where_list['type'] = 1;/*送药订单*/
		$order = array('order'=>'desc','id'=>'desc');
		$list_total = Db::name('order')->field('id')->where($where_list)->select();
		$list_total_count = count($list_total);
		$list = Db::name('order')->where($where_list)->where($where_list2)->order($order)->paginate($pagelimit, false, $paginate);
		$lists = array();
		foreach ($list as $k => $v) {
			$lists[$k] = $v;	
			/*所属诊所*/
			if(!empty($v['clinic_id'])){
				$list_clinic = Db::name('clinic')->field('id,name')->where('id='.$clinic_id)->find();
				$lists[$k]['clinic_name'] = $list_clinic['name'];
			}	
			/*用户信息*/
			if(!empty($v['user_id'])){
				$list_user = Db::name('user')->field('id,nickname,picture')->where('id='.$v['user_id'])->find();
				$lists[$k]['user_nickname'] = $list_user['nickname'];
				$list_user_picture_arr = explode(',',$list_user['picture']);
				$lists[$k]['user_picture'] = $list_user_picture_arr[0];
			}			
			/*状态*/
			if (!empty($v['status'])) {
				$lists[$k]['status'] = orderStatusToStr($v['status']);
			}
		}
		$page = $list->render(); /*获取分页显示*/
		$this->assign('list', $lists);
		$this->assign('list_total_count',$list_total_count);
		$this->assign('page', $page);
		/*分配变量*/
		$this->assign('title', '送药订单列表'); /*页面标题*/
		$this->assign('keywords', '送药订单列表'); /*页面关键词*/
		$this->assign('description', '送药订单列表'); /*页面描述*/
		/*调用模板*/
		return $this->fetch(TEMP_FETCH);
	}
	/*编辑送药订单*/
	public function order_outedit() {
		/*所属诊所*/
		$clinic_id = input('clinic_id');
		$this->assign('clinic_id',$clinic_id);
		if(empty($clinic_id)){
			echo '<meta charset="utf-8" />';
			echo '<script>$(document).ready(function(){alertBox("诊所参数缺失！","'.url('medical.clinic/lists').'");})</script>';
			die();
		}	
		/*获取参数*/
		$id = input('id');/*内容Id*/
		if ($id) {
			/*编辑*/
			/*查询参数*/
			$where['id'] = $id;
			$contents = Db::name('order')->where($where)->find();
			foreach ($contents as $k => $v) {
				$content[$k] = $v;
				/*格式化时间*/
				if($k == 'status' && !empty($contents['status'])){
					$content['status'] = orderStatusToStr($content['status']);
				}
				/*订单处理进度*/
				if($k == 'status_leng' && !empty($contents['status_leng'])){
					$content['status_leng'] = json_decode($content['status_leng'],true);
				}				
			}
			$this->assign('content',$content); /*内容*/
			/*查询订单商品*/
			$goods_arr = json_decode($content['content'],true);
			if(!empty($goods_arr) && $goods_arr['type'] == 1){
				foreach($goods_arr['value'] as $k=>$v){
					$good_array = Db::name('content')->field('name,picture')->where('id='.$v['content_id'])->find();
					if(!empty($good_array)){
						foreach($good_array as $k2=>$v2){
							$goods_arr['value'][$k][$k2] = $v2;
						}
					}
				}
			}
			$goods = $goods_arr['value'];
			$this->assign('goods',$goods); /*商品*/
			/*组装post提交变量*/
			$submit = array('name' => '确认编辑', 'url' => '?id=' . $id);
			$this->assign('submit', $submit);
			/*分配变量*/
			$this->assign('title', '送药订单详情'); /*页面标题*/
			$this->assign('keywords', '送药订单详情'); /*页面关键词*/
			$this->assign('description', '送药订单详情'); /*页面描述*/
		} else {			
			echo '<meta charset="utf-8" />';
			echo '<script>$(document).ready(function(){alertBox("订单参数缺失！","'.url('medical.clinic/lists').'");})</script>';
			die();
		}
		/*调用模板*/
		return $this->fetch(TEMP_FETCH);
	}
	/*删除送药订单*/
	public function order_outdel() {
		/*接收参数*/
		$id = input('id'); /*送药订单ID*/
		$result = Db::name('order')->where('id='.$id)->delete();
		if ($result) {
			echo '<script>$(document).ready(function(){alertBox("恭喜，删除成功！","'.$_SERVER['HTTP_REFERER'].'");})</script>';
		} else {
			echo '<script>$(document).ready(function(){alertBox("抱歉，删除失败！","'.$_SERVER['HTTP_REFERER'].'");})</script>';
		}
	}
	/*送药订单管理 end*/

	/*进药订单管理 start*/
	/*进药订单列表*/
	public function order_in(){						
		/*获取参数*/
		$t_start = input('t_start');/*开始时间*/
		$this->assign('t_start',$t_start);
		$t_start_data = strtotime($t_start);
		$this->assign('t_start_data',$t_start_data);
		$t_end = input('t_end');/*结束时间*/
		$this->assign('t_end',$t_end);
		$t_end_data = strtotime($t_end);
		$this->assign('t_end_data',$t_end_data);		
		$clinic_id = input('clinic_id');
		$this->assign('clinic_id',$clinic_id);
		if(empty($clinic_id)){
			echo '<meta charset="utf-8" />';
			echo '<script>$(document).ready(function(){alertBox("诊所参数缺失！","'.url('medical.clinic/lists').'");})</script>';
			die();
		}
		/*状态搜索*/
		$status = !empty(input('status'))?input('status'):0;
		$this->assign('status',$status);
		if($status != 0){
			$where_list['status'] = $status;
		}			
		$limit = input('limit');
		$search = input('search');
		$this->assign('limit', $limit);
		$this->assign('search', $search);
		$pagelimit = !empty($limit) ? $limit : config('paginate.list_rows'); /*每页条数*/
		if (!empty($search)) {
			$where_list['concat(no,title)'] = array('like', '%' . $search . '%'); /*搜索*/
		}
		/*分页参数*/
		$paginate = array('query' => array( /*url额外参数*/
		'clinic_id' => $clinic_id,/*诊所ID*/
		't_start' => $t_start,
		't_end' => $t_end,
		'status' => $status,	/*状态*/
		'limit' => $limit, /*每页条数*/
		'search' => $search, /*搜索*/
		));
		/*条件*/
		$where_list2 = array();
		if(!empty($t_start_data) && empty($t_end_data)){
			$where_list['addtime'] = array('>=',$t_start_data);
		}else if(!empty($t_end_data) && empty($t_start_data)){
			$where_list['addtime'] = array('<=',$t_end_data);
		}else if(!empty($t_end_data) && !empty($t_end_data)){
			$where_list['addtime'] = array('>=',$t_start_data);
			$where_list2['addtime'] = array('<=',$t_end_data);
		}
		$where_list['site_id'] = $this->site_id;
		$where_list['clinic_id'] = $clinic_id;
		$where_list['type'] = 2;/*进药订单*/
		$order = array('order'=>'desc','id'=>'desc');
		$list_total = Db::name('order')->field('id')->where($where_list)->select();
		$list_total_count = count($list_total);
		$list = Db::name('order')->where($where_list)->where($where_list2)->order($order)->paginate($pagelimit, false, $paginate);
		$lists = array();
		foreach ($list as $k => $v) {
			$lists[$k] = $v;	
			/*所属诊所*/
			if(!empty($v['clinic_id'])){
				$list_clinic = Db::name('clinic')->field('id,name')->where('id='.$clinic_id)->find();
				$lists[$k]['clinic_name'] = $list_clinic['name'];
			}	
			/*用户信息*/
			if(!empty($v['user_id'])){
				$list_user = Db::name('user')->field('id,nickname,picture')->where('id='.$v['user_id'])->find();
				$lists[$k]['user_nickname'] = $list_user['nickname'];
				$list_user_picture_arr = explode(',',$list_user['picture']);
				$lists[$k]['user_picture'] = $list_user_picture_arr[0];
			}			
			/*状态*/
			if (!empty($v['status'])) {
				$lists[$k]['status'] = orderStatusToStr($v['status']);
			}
		}
		$page = $list->render(); /*获取分页显示*/
		$this->assign('list', $lists);
		$this->assign('list_total_count',$list_total_count);
		$this->assign('page', $page);
		/*分配变量*/
		$this->assign('title', '进药订单列表'); /*页面标题*/
		$this->assign('keywords', '进药订单列表'); /*页面关键词*/
		$this->assign('description', '进药订单列表'); /*页面描述*/
		/*调用模板*/
		return $this->fetch(TEMP_FETCH);
	}
	/*编辑进药订单*/
	public function order_inedit() {
		/*所属诊所*/
		$clinic_id = input('clinic_id');
		$this->assign('clinic_id',$clinic_id);
		if(empty($clinic_id)){
			echo '<meta charset="utf-8" />';
			echo '<script>$(document).ready(function(){alertBox("诊所参数缺失！","'.url('medical.clinic/lists').'");})</script>';
			die();
		}	
		/*获取参数*/
		$id = input('id');/*内容Id*/
		if ($id) {
			/*编辑*/
			/*查询参数*/
			$where['id'] = $id;
			$contents = Db::name('order')->where($where)->find();
			foreach ($contents as $k => $v) {
				$content[$k] = $v;
				/*格式化时间*/
				if($k == 'status' && !empty($contents['status'])){
					$content['status'] = orderStatusToStr($content['status']);
				}
				/*订单处理进度*/
				if($k == 'status_leng' && !empty($contents['status_leng'])){
					$content['status_leng'] = json_decode($content['status_leng'],true);
				}				
			}
			$this->assign('content',$content); /*内容*/
			/*查询订单商品*/
			$goods_arr = json_decode($content['content'],true);
			if(!empty($goods_arr) && $goods_arr['type'] == 1){
				foreach($goods_arr['value'] as $k=>$v){
					$good_array = Db::name('content')->field('name,picture')->where('id='.$v['content_id'])->find();
					if(!empty($good_array)){
						foreach($good_array as $k2=>$v2){
							$goods_arr['value'][$k][$k2] = $v2;
						}
					}
				}
			}
			$goods = $goods_arr['value'];
			$this->assign('goods',$goods); /*商品*/
			/*组装post提交变量*/
			$submit = array('name' => '确认编辑', 'url' => '?id=' . $id);
			$this->assign('submit', $submit);
			/*分配变量*/
			$this->assign('title', '进药订单详情'); /*页面标题*/
			$this->assign('keywords', '进药订单详情'); /*页面关键词*/
			$this->assign('description', '进药订单详情'); /*页面描述*/
		} else {			
			echo '<meta charset="utf-8" />';
			echo '<script>$(document).ready(function(){alertBox("订单参数缺失！","'.url('medical.clinic/lists').'");})</script>';
			die();
		}
		/*调用模板*/
		return $this->fetch(TEMP_FETCH);
	}
	/*删除进药订单*/
	public function order_indel() {
		/*接收参数*/
		$id = input('id'); /*进药订单ID*/
		$result = Db::name('order')->where('id='.$id)->delete();
		if ($result) {
			echo '<script>$(document).ready(function(){alertBox("恭喜，删除成功！","'.$_SERVER['HTTP_REFERER'].'");})</script>';
		} else {
			echo '<script>$(document).ready(function(){alertBox("抱歉，删除失败！","'.$_SERVER['HTTP_REFERER'].'");})</script>';
		}
	}
	/*进药订单管理 end*/

	/*医生管理 start*/
	/*医生列表*/
	public function doctor() {		
		/*所属诊所*/
		$clinic_id = input('clinic_id');
		$this->assign('clinic_id',$clinic_id);
		if(empty($clinic_id)){
			echo '<meta charset="utf-8" />';
			echo '<script>$(document).ready(function(){alertBox("诊所参数缺失！","'.url('medical.clinic/lists').'");})</script>';
			die();
		}
		/*获取参数*/
		$limit = input('limit');
		$search = input('search');
		$this->assign('limit', $limit);
		$this->assign('search', $search);
		$pagelimit = !empty($limit) ? $limit : config('paginate.list_rows'); /*每页条数*/
		if(!empty($search)){
			$where_user_list['concat(name,nickname)'] = array('like', '%' . $search . '%'); /*搜索*/
		}
		/*状态搜索*/
		$status = !empty(input('status')) ? input('status') : 0;
		$this->assign('status',$status);
		if($status != 0){
			$where_user_list['status'] = $status;
		}		
		/*查询管理员*/
		$where_user_list['group_ids'] = 4;/*医生*/
		$where_user_list['clinic_id'] = array('in',$clinic_id);/*诊所*/
		/*分页参数*/
		$paginate = array('query' => array( /*url额外参数*/
			'clinic_id' => $clinic_id,/*诊所ID*/
			'status' => $status,/*状态*/
			'limit' => $limit,/*每页条数*/
			'search' => $search,/*搜索*/
		));
		$list = Db::name('user')->where($where_user_list)->paginate($pagelimit, false, $paginate);
		$lists = array();
		foreach($list as $k => $v){
			$lists[$k] = $v;
			/*所属诊所*/
			if(!empty($v['clinic_id'])){
				$list_clinic = Db::name('clinic')->field('id,name')->where('id='.$clinic_id)->find();
				$lists[$k]['clinic_name'] = $list_clinic['name'];
			}	
			/*状态*/
			if ($v['status'] == 2) {
				$lists[$k]['status_name'] = '已审核';
			}else if($v['status'] == 1){
				$lists[$k]['status_name'] = '待审核';
			}else{
				$lists[$k]['status_name'] = '未知';
			}		
		}
		$page = $list->render();/*获取分页显示*/
		$this->assign('list', $lists);
		$this->assign('page', $page);
		/*分配变量*/
		$this->assign('title', '医生列表'); /*页面标题*/
		$this->assign('keywords', '医生列表'); /*页面关键词*/
		$this->assign('description', '医生列表'); /*页面描述*/
		/*调用模板*/
		return $this->fetch(TEMP_FETCH);
	}
	/*编辑医生*/
	public function doctoredit() {
		/*所属诊所*/
		$clinic_id = input('clinic_id');
		$this->assign('clinic_id',$clinic_id);
		if(empty($clinic_id)){
			echo '<meta charset="utf-8" />';
			echo '<script>$(document).ready(function(){alertBox("诊所参数缺失！","'.url('medical.clinic/lists').'");})</script>';
			die();
		}
		$clinic = Db::name('clinic')->field('name')->where('id='.$clinic_id)->find();
		$this->assign('clinic',$clinic);
		/*查询医生管理员组*/
		$where_group['site_id'] = $this->site_id;
		$where_group['type'] = 1;
		$where_group['status'] = 1;
		$group = Db::name('group')->field('id,name')->where($where_group)->select();
		$this->assign('group', $group);
		/*查询省*/
		$province = Db::name('areacode')->field('id,name')->where('pid=7 AND status=1')->select();
		$this->assign('province', $province);		
		/*查询行业*/
		$industry = Db::name('industry')->field('id,name')->where('status=1')->select();
		$this->assign('industry', $industry);
		/*获取参数*/
		$user_id = input('id'); /*会员ID*/
		if ($user_id) {
			/*编辑*/
			/*查询参数*/
			$where_user['id'] = $user_id;
			$user = Db::name('user')->where($where_user)->find();
			foreach ($user as $k => $v) {
				/*解析头像*/
				if ($k == 'picture' && !empty($user['picture'])) {
					$picture = explode(',', $v);
					$user['picture'] = array();
					foreach ($picture as $k2 => $v2) {						$basename = explode('/',$v2);
						$basename = array_pop($basename);
						$user['picture'][$k2] = array('filename' => $v2, 'basename' => $basename);
					}
				}
				/*解析身份证号*
				if ($k == 'identity' && !empty($user['identity'])) {
					$identity = json_decode($user['identity']);
					$user['identity'] = $identity->name;
				}
				/*解析手机号*
				if ($k == 'phone' && !empty($user['phone'])) {
					$phone = json_decode($user['phone']);
					$user['phone'] = $phone->name;
				}
				/*解析邮箱地址*
				if ($k == 'mail' && !empty($user['mail'])) {
					$mail = json_decode($user['mail']);
					$user['mail'] = $mail->name;
				}
				/*解析qq号码*
				if ($k == 'qq' && !empty($user['qq'])) {
					$qq = json_decode($user['qq']);
					$user['qq'] = $qq->name;
				}
				/*解析微信号*
				if ($k == 'wechat' && !empty($user['wechat'])) {
					$wechat = json_decode($user['wechat']);
					$user['wechat'] = $wechat->name;
				}
				/*查询市*/
				if ($k == 'province' && !empty($user['province'])) {
					$city = Db::name('areacode')->field('id,name')->where('pid=' . $user['province'] . ' AND status=1')->select();
					$this->assign('city', $city);
				}
				/*查询区*/
				if ($k == 'city' && !empty($user['city'])) {
					$area = Db::name('areacode')->field('id,name')->where('pid=' . $user['city'] . ' AND status=1')->select();
					$this->assign('area', $area);
				}
			}
			$this->assign('content', $user); /*内容*/
			/*组装post提交变量*/
			$submit = array('name' => '确认编辑', 'url' => '?id=' . $user_id);
			$this->assign('submit', $submit);
			/*分配变量*/
			$this->assign('title', '编辑医生'); /*页面标题*/
			$this->assign('keywords', '编辑医生'); /*页面关键词*/
			$this->assign('description', '编辑医生'); /*页面描述*/
		} else {
			/*添加*/
			/*组装post提交变量*/
			$submit = array('name' => '确认添加', 'url' => '?a=add');
			$this->assign('submit', $submit);
			/*分配变量*/
			$this->assign('title', '添加医生'); /*页面标题*/
			$this->assign('keywords', '添加医生'); /*页面关键词*/
			$this->assign('description', '添加医生'); /*页面描述*/
		}
		/*提交*/
		if ($_POST) {
			/*获取参数*/
			$nowtime = time();
			$password = input('password');
			/*验证参数*/
			if (empty(input('name'))) {
				echo '<script>$(document).ready(function(){alertBox("名称不可为空..","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
			}else if (input('password') != input('password2')) {
				echo '<script>$(document).ready(function(){alertBox("两次密码不一致..","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
			} else {
				/*重组身份证json*/
				$identity_arr = array('name' => input('identity'), 'status' => 0, 'edittime' => $nowtime,);
				$identity_json = json_encode($identity_arr);
				/*重组手机json*/
				$phone_arr = array('name' => input('phone'), 'status' => 0, 'edittime' => $nowtime,);
				$phone_json = json_encode($phone_arr);
				/*重组邮箱json*/
				$mail_arr = array('name' => input('mail'), 'status' => 0, 'edittime' => $nowtime,);
				$mail_json = json_encode($mail_arr);
				/*重组QQjson*/
				$qq_arr = array('name' => input('qq'), 'status' => 0, 'edittime' => $nowtime,);
				$qq_json = json_encode($qq_arr);
				/*重组微信号json*/
				$wechat_arr = array('name' => input('wechat'), 'status' => 0, 'edittime' => $nowtime,);
				$wechat_json = json_encode($wechat_arr);
				/*组装参数*/
				$data = array(
					'group_ids' 	=> input('group_id'), /*所属管理组*/
					'site_ids' 		=> input('site_id'), /*医生所属网站*/
					'clinic_id'		=> $clinic_id,/*诊所ID*/
					'name'			=> input('name'), /*名称*/
					'nickname' 		=> input('nickname'), /*昵称*/
					'picture' 		=> input('picture'), /*头像*/
					'sex' 			=> input('sex'), /*性别 默认女:0|男为1 */
					'industry' 		=> input('industry'), /*行业*/
					'year' 			=> input('year'), /*出生年*/
					'month' 		=> input('month'), /*出生月*/
					'day'			=> input('day'), /*出生日*/
					'identity' 		=> $identity_json, /*身份证号码JONS集（号码,认证状态1已认证0未认证,变更时间）*/
					'phone' 		=> $phone_json, /*手机JONS集（号码,认证状态1已认证0未认证,变更时间）*/
					'mail'			=> $mail_json, /*邮箱JONS集（号码,认证状态1已认证0未认证,变更时间）*/
					'qq' 			=> $qq_json, /*QQ-JONS集（号码,关联状态1已关联0未关联,变更时间）*/
					'wechat' 		=> $wechat_json, /*微信-JONS集（号码,关联状态1已关联0未关联,变更时间）*/
					'state' 		=> 1, /*州*/
					'country' 		=> 7, /*国*/
					'province'		=> input('province'), /*省*/
					'city' 			=> input('city'), /*市*/
					'area' 			=> input('area'), /*区*/
					'address' 		=> input('address'), /*地址*/
					'order' 		=> input('order'), /*排序  默认为100*/
					'status' 		=> input('status'), /*状态  默认为1:启用|0:禁用*/
					'edittime' 		=> $nowtime, /*修改时间*/
				);
				if ($user_id) {
					/*编辑*/
					/*更新密码*/
					if (!empty($password)) {
						$reg_time = $user['addtime'];
						$password = md5(md5($reg_time . $password));
						$data['password'] = $password;
					}
					/*更新数据*/
					$result = Db::name('user')->where('id='.$user_id)->update($data);
					$alert_success = '恭喜，编辑成功！';
					$alert_error = '抱歉，编辑失败！';
					/*判断结果集*/
					if ($result) {
						echo '<script>$(document).ready(function(){alertBox("'.$alert_success.'","'.$_SERVER['HTTP_REFERER'].'");})</script>';
					} else {
						echo '<script>$(document).ready(function(){alertBox("'.$alert_error.'","'.$_SERVER['HTTP_REFERER'].'");})</script>';
					}						
				} else {
					/*更新密码*/
					if (!empty($password)) {
						$reg_time = $nowtime;
						$password = md5(md5($reg_time . $password));
						$data['password'] = $password;
					}
					/*添加*/
					$data['money'] = 0; /*余额*/
					$data['integral'] = 0; /*积分*/
					$data['growth'] = 0; /*成长值*/
					$data['addtime'] = $nowtime; /*添加时间*/
					/*插入数据*/
					$result = Db::name('user')->insert($data);
					$alert_success = '恭喜，添加成功！';
					$alert_error = '抱歉，添加失败！';
					/*判断结果集*/
					if($result){
						echo '<script>$(document).ready(function(){alertBox("'.$alert_success.'","'.url('medical.clinic/doctor').'?clinic_id='.$clinic_id.'");})</script>';
					}else{
						echo '<script>$(document).ready(function(){alertBox("'.$alert_error.'","'.$_SERVER['HTTP_REFERER'].'");})</script>';
					}					
				}
			}
		} else {
			/*调用模板*/
			return $this->fetch(TEMP_FETCH);
		}
	}
	/*审核医生*/
	public function doctorcheck(){
		/*接收参数*/
		$user_id = input('id'); /*医生ID*/
		$data['status'] = 2; /*状态*/
		$result = Db::name('user')->where('id='.$user_id)->update($data);
		if ($result) {
			echo '<script>$(document).ready(function(){alertBox("恭喜，审核成功！","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
		} else {
			echo '<script>$(document).ready(function(){alertBox("抱歉，审核失败！","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
		}
	}
	/*删除医生*/
	public function doctordel(){
		/*接收参数*/
		$user_id = input('id'); /*医生ID*/
		$result = Db::name('user')->where('id='.$user_id)->delete();
		if ($result) {
			echo '<script>$(document).ready(function(){alertBox("恭喜，删除成功！","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
		} else {
			echo '<script>$(document).ready(function(){alertBox("抱歉，删除失败！","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
		}
	}
	/*医生管理 end*/

	/*分类管理 start*/
	/*分类列表*/
	public function category(){
		/*所属诊所*/
		$clinic_id = input('clinic_id');
		$this->assign('clinic_id',$clinic_id);
		if(empty($clinic_id)){
			echo '<meta charset="utf-8" />';
			echo '<script>$(document).ready(function(){alertBox("诊所参数缺失！","'.url('medical.clinic/lists').'");})</script>';
			die();
		}
		$clinic = Db::name('clinic')->field('name')->where('id='.$clinic_id)->find();
		$this->assign('clinic',$clinic);		
		/*查询分类*/
		/*获取参数*/
		$limit = input('limit');
		$search = input('search');
		$this->assign('limit', $limit);
		$this->assign('search', $search);
		$pagelimit = !empty($limit) ? $limit : config('paginate.list_rows'); /*每页条数*/
		if (!empty($search)) {
			$where_list['concat(name,description)'] = array('like', '%' . $search . '%'); /*搜索*/
		}
		/*分页参数*/
		$paginate = array('query' => array( /*url额外参数*/
		'clinic_id' => $clinic_id,/*诊所ID*/
		'limit' => $limit,/*每页条数*/
		'search' => $search,/*搜索*/
		));
		/*条件*/
		$where_list['site_id'] = $this->site_id;
		$where_list['clinic_id'] = $clinic_id;
		$list = Db::name('category')->where($where_list)->order('concat(`path`,`id`)')->paginate($pagelimit, false, $paginate);
		$lists = array();
		foreach ($list as $k => $v) {
			$lists[$k] = $v;
			/*名称*/
			$path_arr = explode(',', trim($v['path'], ','));
			$path_arr_count = count($path_arr);
			$lists[$k]['path_count'] = $path_arr_count; //层级数
			$path_str = '';
			if ($path_arr_count > 1) {
				for ($i = 1;$i < $path_arr_count;$i++) {
					$path_str.= '&nbsp;';
				}
				$path_str.= $path_str . '└ ';
			}
			$lists[$k]['name'] = $path_str . $v['name'];
			/*状态*/
			if ($v['status'] == 1) {
				$lists[$k]['status'] = '启用';
			} else {
				$lists[$k]['status'] = '禁用';
			}
		}
		$page = $list->render(); /*获取分页显示*/
		$this->assign('list', $lists);
		$this->assign('page', $page);
		/*分配变量*/
		$this->assign('title', '分类列表'); /*页面标题*/
		$this->assign('keywords', '分类列表'); /*页面关键词*/
		$this->assign('description', '分类列表'); /*页面描述*/
		/*调用模板*/
		return $this->fetch(TEMP_FETCH);
	}
	/*编辑分类*/
	public function categoryedit(){
		/*所属诊所*/
		$clinic_id = input('clinic_id');
		$this->assign('clinic_id',$clinic_id);
		if(empty($clinic_id)){
			echo '<meta charset="utf-8" />';
			echo '<script>$(document).ready(function(){alertBox("诊所参数缺失！","'.url('medical.clinic/lists').'");})</script>';
			die();
		}
		$clinic = Db::name('clinic')->field('name')->where('id='.$clinic_id)->find();
		$this->assign('clinic',$clinic);		
		/*查询频道*/
		$where_channel['site_id'] = array('IN','1,'.$this->site_id);
		$where_channel['status'] = 1;
		$channel = Db::name('channel')->field('id,name')->where($where_channel)->select();
		$this->assign('channel', $channel);
		/*查询父级分类*/
		$where_category['status'] = 1;
		$where_category['channel_id'] = 22;/*药品频道*/
		$where_category['clinic_id'] = $clinic_id;	
		$list = Db::name('category')->where($where_category)->order('concat(`path`,`id`)')->select();
		$lists = array();
		foreach ($list as $k => $v) {
			$lists[$k] = $v;
			/*名称*/
			$path_arr = explode(',', trim($v['path'], ','));
			$path_arr_count = count($path_arr);
			$lists[$k]['path_count'] = $path_arr_count; //层级数
			$path_str = '';
			if ($path_arr_count > 1) {
				for ($i = 1;$i < $path_arr_count;$i++) {
					$path_str.= '&nbsp;';
				}
				$path_str.= $path_str . '└';
			}
			$lists[$k]['name'] = $path_str . $v['name'];
		}
		$this->assign('pids', $lists);
		/*获取参数*/
		$id = input('id'); /*分类Id*/
		if ($id) {
			/*编辑*/
			/*查询参数*/
			$where['id'] = $id;
			$content = Db::name('category')->where($where)->find();
			foreach ($content as $k => $v) {
				/*解析图标、图片*/
				if ($k == 'ico' && !empty($content['ico'])) {
					$ico = explode(',', $v);
					$content['ico'] = array();
					foreach ($ico as $k2 => $v2) {						$basename = explode('/',$v2);
						$basename = array_pop($basename);
						$content['ico'][$k2] = array('filename' => $v2, 'basename' => $basename);
					}
				}
				if ($k == 'picture' && !empty($content['picture'])) {
					$picture = explode(',', $v);
					$content['picture'] = array();
					foreach ($picture as $k2 => $v2) {						$basename = explode('/',$v2);
						$basename = array_pop($basename);
						$content['picture'][$k2] = array('filename' => $v2, 'basename' => $basename);
					}
				}
			}
			$this->assign('content', $content); /*内容*/
			/*组装post提交变量*/
			$submit = array('name' => '确认编辑', 'url' => '?id=' . $id);
			$this->assign('submit', $submit);
			/*分配变量*/
			$this->assign('title', '编辑分类'); /*页面标题*/
			$this->assign('keywords', '编辑分类'); /*页面关键词*/
			$this->assign('description', '编辑分类'); /*页面描述*/
		} else {
			/*添加*/
			$pid = input('pid');
			$this->assign('pid', $pid);
			/*组装post提交变量*/
			$submit = array('name' => '确认添加', 'url' => '?a=add');
			$this->assign('submit', $submit);
			/*分配变量*/
			$this->assign('title', '添加分类'); /*页面标题*/
			$this->assign('keywords', '添加分类'); /*页面关键词*/
			$this->assign('description', '添加分类'); /*页面描述*/
		}
		/*提交*/
		if ($_POST) {
			/*获取参数*/
			$nowtime = time();
			/*验证参数*/
			if (empty(input('name'))) {
				echo '<script>$(document).ready(function(){alertBox("名称不可为空..","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
			} else {
				/*组装参数*/
				$data = array(
					'site_id' => $this->site_id, /*站点ID*/
					'channel_id' => input('channel'), /*频道ID*/
					'clinic_id' => input('clinic_id'),/*诊所ID*/
					'pid' => input('pids'), /*父结点ID*/
					'name' => input('name'), /*名称*/
					'ico' => input('ico'), /*图标*/
					'picture' => input('picture'), /*缩略图*/
					'url' => input('url'), /*链接*/
					'description' => input('description'), /*简介*/
					'content' => input('content'), /*内容*/
					'order' => input('order'), /*排序  默认为100*/
					'status' => input('status'), /*状态  默认为1:启用|0:禁用*/
					'edittime' => $nowtime, /*修改时间*/
				);
				if(input('pids') == 0) {
					$data['path'] = '0,';
				}else{
					$path = Db::name('category')->field('path')->where('id=' . input('pids'))->find();
					$data['path'] = $path['path'] . input('pids') . ',';
				}				
				if ($id) {
					/*编辑*/
					$result = Db::name('category')->where('id='.$id)->update($data);
					$alert_success = '恭喜，编辑成功！';
					$alert_error = '抱歉，编辑失败！';
				} else {
					/*添加*/
					$data['addtime'] = $nowtime; /*添加时间*/
					/*插入数据*/
					$result = Db::name('category')->insert($data);
					$alert_success = '恭喜，添加成功！';
					$alert_error = '抱歉，添加失败！';
				}
				/*判断结果集*/
				if ($result) {
					echo '<script>$(document).ready(function(){alertBox("'.$alert_success.'","'.url('medical.clinic/category').'?clinic_id='.$clinic_id.'");})</script>';
				} else {
					echo '<script>$(document).ready(function(){alertBox("'.$alert_error.'","'.$_SERVER['HTTP_REFERER'].'");})</script>';
				}
			}
		} else {
			/*调用模板*/
			return $this->fetch(TEMP_FETCH);
		}
	}
	/*删除分类*/
	public function categorydel() {
		/*接收参数*/
		$id = input('id'); /*分类ID*/
		/*判断是否存在子分类*/
		$where['path'] = array('like', '%,' . $id . ',%');
		$where['id'] = array('notin', $id);
		$content = Db::name('category')->where($where)->find();
		if ($content) {
			/*存在子类*/
			echo '<script>$(document).ready(function(){alertBox("抱歉，存在子分类，不允许删除！","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
		} else {
			/*不存在子类*/
			$result = Db::name('category')->where('id=' . $id)->delete();
			if ($result) {
				echo '<script>$(document).ready(function(){alertBox("恭喜，删除成功！","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
			} else {
				echo '<script>$(document).ready(function(){alertBox("抱歉，删除失败！","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
			}
		}
	}
	/*分类管理 end*/
	
	/*产品库管理 start*/
	/*产品库列表*/
	public function product(){
		/*所属诊所*/
		$clinic_id = input('clinic_id');
		$this->assign('clinic_id',$clinic_id);
		if(empty($clinic_id)){
			echo '<meta charset="utf-8" />';
			echo '<script>$(document).ready(function(){alertBox("诊所参数缺失！","'.url('medical.clinic/lists').'");})</script>';
			die();
		}
		$clinic = Db::name('clinic')->field('name')->where('id='.$clinic_id)->find();
		$this->assign('clinic',$clinic);		
		/*查询内容*/
		/*获取参数*/
		$limit = input('limit');
		$search = input('search');
		$this->assign('limit', $limit);
		$this->assign('search', $search);
		$pagelimit = !empty($limit) ? $limit : config('paginate.list_rows'); /*每页条数*/
		if (!empty($search)) {
			$where_list['concat(name,description)'] = array('like', '%' . $search . '%'); /*搜索*/
		}
		/*查询分类信息*/
		$category_where['site_id'] = $this->site_id;
		$category_where['clinic_id'] = $clinic_id;
		$category_where['status'] = 1;
		$category = Db::name('category')->where($category_where)->order('concat(`path`,`id`)')->select();
		foreach ($category as $k => $v) {
			/*名称*/
			$path_arr = explode(',', trim($v['path'], ','));
			$path_arr_count = count($path_arr);
			$category[$k]['path_count'] = $path_arr_count; //层级数
			$path_str = '';
			if ($path_arr_count > 1) {
				for ($i = 1;$i < $path_arr_count;$i++) {
					$path_str.= '&nbsp;';
				}
				$path_str.= $path_str . '└';
			}
			$category[$k]['name'] = $path_str . $v['name'];
		}
		$this->assign('category',$category); /*分配分类信息*/
		/*分类搜索*/
		$category_id = !empty(input('category')) ? input('category') : 0;
		$this->assign('category_id', $category_id);
		if ($category_id > 0) {
			/*组合当前分类及子分类ID集*/
			$category_where['site_id'] = $this->site_id;
			$category_where['status'] = 1;
			$category_where['path'] = array('like','%,'.$category_id.',%');
			$categorys = Db::name('category')->where($category_where)->select();
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
		$where_list['category_id'] = array('in', $category_str); /*搜索*/
		/*分页参数*/
		$paginate = array('query' => array( /*url额外参数*/
		'category' => $category_id, /*搜索*/
		'clinic_id' => $clinic_id,/*诊所ID*/
		'limit' => $limit, /*每页条数*/
		'search' => $search, /*搜索*/
		));
		/*条件*/
		$where_list['site_id'] = $this->site_id;
		$where_list['channel_id'] = array('in','22');/*药品*/
		$order = array('order' => 'desc', 'id' => 'desc');
		$list = Db::name('content')->where($where_list)->order($order)->paginate($pagelimit, false, $paginate);
		$lists = array();
		foreach ($list as $k => $v) {
			$lists[$k] = $v;
			/*所属分类*/
			if(!empty($v['category_id'])){
				$category_lisone = Db::name('category')->field('name')->where('id='.$v['category_id'])->find();
				$lists[$k]['category'] = $category_lisone['name'];
			}
			/*产品图片*/
			if(!empty($v['picture'])){
				$list_picture_arr = explode(',',$v['picture']);
				$lists[$k]['picture'] = $list_picture_arr[0];
			}				
			/*状态*/
			if ($v['status'] == 1) {
				$lists[$k]['status'] = '启用';
			} else {
				$lists[$k]['status'] = '禁用';
			}			
		}
		$page = $list->render(); /*获取分页显示*/
		$this->assign('list', $lists);
		$this->assign('page', $page);
		/*分配变量*/
		$this->assign('title', '产品列表'); /*页面标题*/
		$this->assign('keywords', '产品列表'); /*页面关键词*/
		$this->assign('description', '产品列表'); /*页面描述*/
		/*调用模板*/
		return $this->fetch(TEMP_FETCH);
	}
	/*编辑产品库*/
	public function productedit(){
		/*所属诊所*/
		$clinic_id = input('clinic_id');
		$this->assign('clinic_id',$clinic_id);
		if(empty($clinic_id)){
			echo '<meta charset="utf-8" />';
			echo '<script>$(document).ready(function(){alertBox("诊所参数缺失！","'.url('medical.clinic/lists').'");})</script>';
			die();
		}
		$clinic = Db::name('clinic')->field('name')->where('id='.$clinic_id)->find();
		$this->assign('clinic',$clinic);		
		/*所属分类*/
		$where_category['site_id'] = $this->site_id;
		$where_category['clinic_id'] = $clinic_id;
		$where_category['status'] = 1;
		$list = Db::name('category')->field('id,name,path,pid')->where($where_category)->order('concat(`path`,`id`)')->select();
		$lists = array();
		foreach ($list as $k => $v) {
			$lists[$k] = $v;
			/*名称*/
			$path_arr = explode(',', trim($v['path'], ','));
			$path_arr_count = count($path_arr) - 1;
			$lists[$k]['path_count'] = $path_arr_count; //层级数
			$path_str = '';
			if ($path_arr_count > 0) {
				for ($i = 1;$i < $path_arr_count;$i++) {
					$path_str.= '&nbsp;';
				}
				$path_str.= $path_str . '└ ';
			}
			$lists[$k]['name'] = $path_str . $v['name'];		
		}
		$this->assign('category', $lists);
		/*运费模板*/
		$where_sendtemp['site_id'] = $this->site_id;
		$where_sendtemp['status'] = 1;
		$sendtemp = Db::name('sendtemp')->field('id,name')->where($where_sendtemp)->order('`order` desc,`id` desc')->select();
		$this->assign('sendtemp', $sendtemp);
		/*获取参数*/
		$id = input('id'); /*产品库Id*/
		if ($id) {
			/*编辑*/
			/*查询参数*/
			$where['id'] = $id;
			$contents = Db::name('content')->where($where)->find();
			foreach ($contents as $k => $v) {
				$content[$k] = $v;
				/*解析图标、图片*/
				if ($k == 'ico' && !empty($content['ico'])) {
					$ico = explode(',', $v);
					$content['ico'] = array();
					foreach ($ico as $k2 => $v2) {						$basename = explode('/',$v2);
						$basename = array_pop($basename);
						$content['ico'][$k2] = array('filename' => $v2, 'basename' => $basename);
					}
				}
				if ($k == 'picture' && !empty($content['picture'])) {
					$picture = explode(',', $v);
					$content['picture'] = array();
					foreach ($picture as $k2 => $v2) {						$basename = explode('/',$v2);
						$basename = array_pop($basename);
						$content['picture'][$k2] = array('filename' => $v2, 'basename' => $basename);
					}
				}
				/*解析属性*/
				if ($k == 'attr' && !empty($content['attr'])) {
					$attr = json_decode($content['attr']);
					$content['attr'] = array();
					foreach ($attr as $k => $v) {
						$content['attr'][$k]['option'] = $v->option;
						$content['attr'][$k]['value'] = $v->value;
					}
				}
				/*解析参数*/
				if ($k == 'val' && !empty($content['val'])) {
					$val = json_decode($content['val']);
					$content['val'] = array();
					foreach ($val as $k => $v) {
						$content['val'][$k]['option'] = $v->option;
						$content['val'][$k]['value'] = $v->value;
					}
				}
			}
			$this->assign('content', $content); /*内容*/
			/*组装post提交变量*/
			$submit = array('name' => '确认编辑', 'url' => '?id=' . $id);
			$this->assign('submit', $submit);
			/*分配变量*/
			$this->assign('title', '编辑产品'); /*页面标题*/
			$this->assign('keywords', '编辑产品'); /*页面关键词*/
			$this->assign('description', '编辑产品'); /*页面描述*/
		} else {
			/*添加*/
			/*组装post提交变量*/
			$submit = array('name' => '确认添加', 'url' => '?a=add');
			$this->assign('submit', $submit);
			/*分配变量*/
			$this->assign('title', '添加产品'); /*页面标题*/
			$this->assign('keywords', '添加产品'); /*页面关键词*/
			$this->assign('description', '添加产品'); /*页面描述*/
		}
		/*提交*/
		if ($_POST) {
			/*获取参数*/
			$nowtime = time();
			/*验证参数*/
			if (empty(input('name'))) {
				echo '<script>$(document).ready(function(){alertBox("名称不可为空..","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
			} else {
				/*组装参数*/
				$data = array('site_id' => $this->site_id, /*站点ID*/
				'category_id' => input('category'), /*分类ID*/
				'sendtemp_id' => input('sendtemp'), /*运费模板ID*/
				//'type'		=> json_encode($_POST['type']),/*类型json集((0,颜色[红、黄]),(1,材质[棉、麻]))*/
				'attr' => json_encode($_POST['attr']), /*属性json集((0,人群[皆宜]),(1,爱好[微应用]))*/
				'val' => json_encode($_POST['val']), /*参数json集((0,人群[皆宜]),(1,爱好[微应用]))*/
				'name' => input('name'), /*名称*/
				'ico' => input('ico'), /*图标*/
				'picture' => input('picture'), /*缩略图*/
				'price' => input('price'), /*售价*/
				'price_original' => input('price_original'), /*原价*/
				'price_enter' => input('price_enter'), /*进价*/
				'description' => input('description'), /*简介*/
				'content' => input('content'), /*内容*/
				//'video'		=> input('video'),/*视频*/
				//'resource'	=> input('resource'),/*资源*/
				'url' => input('url'), /*链接*/
				'author' => input('author'), /*作者*/
				'source' => input('source'), /*来源*/
				'sales' => input('sales'), /*销量*/
				'visitor' => input('visitor'), /*访问量*/
				'order' => input('order'), /*排序  默认为100*/
				'status' => input('status'), /*状态  默认为1:启用|0:禁用*/
				'edittime' => $nowtime, /*修改时间*/
				);
				if($id){
					/*编辑*/
					/*更新数据*/
					$result = Db::name('content')->where('id=' . $id)->update($data);
					$alert_success = '恭喜，编辑成功！';
					$alert_error = '抱歉，编辑失败！';
				} else {
					/*添加*/
					$data['addtime'] = $nowtime; /*添加时间*/
					/*插入数据*/
					$result = Db::name('content')->insert($data);
					$alert_success = '恭喜，添加成功！';
					$alert_error = '抱歉，添加失败！';
				}
				/*判断结果集*/
				if ($result) {
					echo '<script>$(document).ready(function(){alertBox("'.$alert_success.'","'.url('medical.clinic/product').'?clinic_id='.$clinic_id.'");})</script>';
				} else {
					echo '<script>$(document).ready(function(){alertBox("'.$alert_error.'","'.$_SERVER['HTTP_REFERER'].'");})</script>';
				}
			}
		} else {
			/*调用模板*/
			return $this->fetch(TEMP_FETCH);
		}
	}
	/*删除产品库*/
	public function productdel() {
		/*接收参数*/
		$id = input('id'); /*内容ID*/
		/*判断是否存在评论*/
		$where['content_id'] = $id;
		$content = Db::name('comment')->where($where)->find();
		if ($content) {
			/*存在子类*/
			echo '<script>$(document).ready(function(){alertBox("抱歉，内容存在评论，不允许删除！","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
		} else {
			/*不存在子类*/
			$result = Db::name('content')->where('id=' . $id)->delete();
			if ($result) {
				echo '<script>$(document).ready(function(){alertBox("恭喜，删除成功！","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
			} else {
				echo '<script>$(document).ready(function(){alertBox("抱歉，删除失败！","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
			}
		}
	}
	/*产品库管理 end*/

	/*咨询专家记录管理 start*/
	/*咨询专家列表*/
	public function chat_expert(){	
		/*所属诊所*/
		$clinic_id = input('clinic_id');
		$this->assign('clinic_id',$clinic_id);
		if(empty($clinic_id)){
			echo '<meta charset="utf-8" />';
			echo '<script>$(document).ready(function(){alertBox("诊所参数缺失！","'.url('medical.clinic/lists').'");})</script>';
			die();
		}
		/*医生ID*/
		$id = input('id');
		$this->assign('id',$id);
		if(empty($id)){
			echo '<meta charset="utf-8" />';
			echo '<script>$(document).ready(function(){alertBox("医生参数缺失！","'.url('medical.clinic/doctor').'?clinic_id='.$clinic_id.'");})</script>';
			die();
		}		
		$doctor = Db::name('user')->field('nickname,picture')->where('id='.$id)->find();
		$doctor_picture_arr = explode(',',$doctor['picture']);
		$doctor['picture'] = $doctor_picture_arr[0];	
		$this->assign('doctor',$doctor);		
		/*获取参数*/
		$t_start = input('t_start');/*开始时间*/
		$this->assign('t_start',$t_start);
		$t_start_data = strtotime($t_start);
		$this->assign('t_start_data',$t_start_data);
		$t_end = input('t_end');/*结束时间*/
		$this->assign('t_end',$t_end);
		$t_end_data = strtotime($t_end);
		$this->assign('t_end_data',$t_end_data);	
		$limit = input('limit');
		$search = input('search');
		$this->assign('limit', $limit);
		$this->assign('search', $search);
		$pagelimit = !empty($limit) ? $limit : config('paginate.list_rows'); /*每页条数*/
		if(!empty($search)){
			$where_user_list['concat(user_from_id,user_to_id,content)'] = array('like', '%' . $search . '%'); /*搜索*/
		}		
		/*查询聊天名师*/
		$where_user_list['user_from_id'] = $id;/*信息发件人*/
		$where_user_list['user_to_type'] = 3;/*信息接受人类型*/
		/*分页参数*/
		$paginate = array('query' => array( /*url额外参数*/
			'clinic_id' => $clinic_id,/*诊所ID*/
			'id' => $id,/*诊所ID*/
			't_start' => $t_start,
			't_end' => $t_end,			
			'limit' => $limit,/*每页条数*/
			'search' => $search,/*搜索*/
		));
		/*条件*/
		$where_user_list2 = array();
		if(!empty($t_start_data) && empty($t_end_data)){
			$where_user_list['edittime'] = array('>=',$t_start_data);
		}else if(!empty($t_end_data) && empty($t_start_data)){
			$where_user_list['edittime'] = array('<=',$t_end_data);
		}else if(!empty($t_end_data) && !empty($t_end_data)){
			$where_user_list['edittime'] = array('>=',$t_start_data);
			$where_user_list2['edittime'] = array('<=',$t_end_data);
		}		
		$list = Db::name('chat')->distinct(true)->field('user_to_id')->where($where_user_list)->where($where_user_list2)->order('edittime desc,id desc')->paginate($pagelimit,false,$paginate);
		/*
		$list = Db::name('chat')->distinct(true)->field('user_to_id')->where(function($query){
					$query->where('user_from_id',input('id'))->whereor('user_to_id',input('id'));
				})->where(function($query){
					$query->where('user_from_type',3)->whereor('user_to_type',3);/*专家*
				})->paginate($pagelimit,false,$paginate);
		*/			
		$lists = array();
		foreach($list as $k => $v){
			$lists[$k] = $v;
			/*查询专家身份信息*/
			$expert = Db::name('user')->field('nickname,picture')->where('id='.$v['user_to_id'])->find();
			$expert_picture_arr = explode(',',$expert['picture']);
			$lists[$k]['nickname'] = $expert['nickname'];
			$lists[$k]['picture'] = $expert_picture_arr[0];
			/*获取最近一条聊天信息*/
			$GLOBALS['expert_id'] = $v['user_to_id'];
			$chat_expert_list = Db::name('chat')->where('type=1')->where(function($query){
						$query->where('user_from_id',$GLOBALS['expert_id'])->whereor('user_to_id',$GLOBALS['expert_id']);
					})->where(function($query){
						$query->where('user_from_id',input('id'))->whereor('user_to_id',input('id'));/*专家*/
					})->order('edittime desc,id desc')->find();
			$lists[$k]['chat'] = $chat_expert_list;		
		}
		$page = $list->render();/*获取分页显示*/
		$this->assign('list',$lists);
		$this->assign('page',$page);
		/*分配变量*/
		$this->assign('title','咨询专家列表'); /*页面标题*/
		$this->assign('keywords','咨询专家列表'); /*页面关键词*/
		$this->assign('description','咨询专家列表'); /*页面描述*/
		/*调用模板*/
		return $this->fetch(TEMP_FETCH);
	}
	/*删除与该专家的所有聊天*/
	public function chat_expertdel() {
		/*接收参数*/
		$GLOBALS['expert_id'] = input('expert_id');/*专家ID*/
		$GLOBALS['doctor_id'] = input('doctor_id');/*医生ID*/
		$result = Db::name('chat')->where(function($query){
						$query->where('user_from_id',$GLOBALS['expert_id'])->whereor('user_to_id',$GLOBALS['expert_id']);/*专家id*/
					})->where(function($query){
						$query->where('user_from_id',$GLOBALS['doctor_id'])->whereor('user_to_id',$GLOBALS['doctor_id']);/*医生id*/
					})->where(function($query){
						$query->where('user_from_type',3)->whereor('user_to_type',3);/*专家类型*/
					})->where(function($query){
						$query->where('user_from_type',2)->whereor('user_to_type',2);/*医生类型*/
					})->delete();
		if($result){
			echo '<script>$(document).ready(function(){alertBox("恭喜，删除成功！","'.$_SERVER['HTTP_REFERER'].'");})</script>';
		}else{
			echo '<script>$(document).ready(function(){alertBox("抱歉，删除失败！","'.$_SERVER['HTTP_REFERER'].'");})</script>';
		}
	}
	/*咨询专家列表*/
	public function chat_expert_list(){	
		/*所属诊所*/
		$clinic_id = input('clinic_id');
		$this->assign('clinic_id',$clinic_id);
		if(empty($clinic_id)){
			echo '<meta charset="utf-8" />';
			echo '<script>$(document).ready(function(){alertBox("诊所参数缺失！","'.url('medical.clinic/lists').'");})</script>';
			die();
		}	
		/*专家ID*/
		$expert_id = input('expert_id');
		$this->assign('expert_id',$expert_id);
		if(empty($expert_id)){
			echo '<meta charset="utf-8" />';
			echo '<script>$(document).ready(function(){alertBox("医生参数缺失！","'.url('medical.clinic/doctor').'?clinic_id='.$clinic_id.'");})</script>';
			die();
		}		
		$expert = Db::name('user')->field('nickname,picture')->where('id='.$expert_id)->find();
		$expert_picture_arr = explode(',',$expert['picture']);
		$expert['picture'] = $expert_picture_arr[0];	
		$this->assign('expert',$expert);	
		/*医生ID*/
		$doctor_id = input('doctor_id');
		$this->assign('doctor_id',$doctor_id);
		if(empty($doctor_id)){
			echo '<meta charset="utf-8" />';
			echo '<script>$(document).ready(function(){alertBox("医生参数缺失！","'.url('medical.clinic/doctor').'?clinic_id='.$clinic_id.'");})</script>';
			die();
		}		
		$doctor = Db::name('user')->field('nickname,picture')->where('id='.$doctor_id)->find();
		$doctor_picture_arr = explode(',',$doctor['picture']);
		$doctor['picture'] = $doctor_picture_arr[0];	
		$this->assign('doctor',$doctor);		
		/*获取参数*/
		$GLOBALS['expert_id'] = $expert_id;/*专家ID*/
		$GLOBALS['doctor_id'] = $doctor_id;/*医生ID*/		
		$t_start = input('t_start');/*开始时间*/
		$this->assign('t_start',$t_start);
		$t_start_data = strtotime($t_start);
		$this->assign('t_start_data',$t_start_data);
		$t_end = input('t_end');/*结束时间*/
		$this->assign('t_end',$t_end);
		$t_end_data = strtotime($t_end);
		$this->assign('t_end_data',$t_end_data);	
		$limit = input('limit');
		$search = input('search');
		$this->assign('limit', $limit);
		$this->assign('search', $search);
		$pagelimit = !empty($limit) ? $limit : config('paginate.list_rows'); /*每页条数*/
		if(!empty($search)){
			$where_user_list['concat(user_from_id,user_to_id,content)'] = array('like','%'.$search.'%'); /*搜索*/
		}
		/*分页参数*/
		$paginate = array('query' => array( /*url额外参数*/
			'clinic_id' => $clinic_id,/*诊所ID*/
			'expert_id' => $expert_id,/*专家ID*/
			'doctor_id' => $doctor_id,/*医生ID*/
			't_start' => $t_start,
			't_end' => $t_end,			
			'limit' => $limit,/*每页条数*/
			'search' => $search,/*搜索*/
		));
		/*条件*/
		$where_user_list['status'] = 1;
		$where_user_list2 = array();
		if(!empty($t_start_data) && empty($t_end_data)){
			$where_user_list['edittime'] = array('>=',$t_start_data);
		}else if(!empty($t_end_data) && empty($t_start_data)){
			$where_user_list['edittime'] = array('<=',$t_end_data);
		}else if(!empty($t_end_data) && !empty($t_end_data)){
			$where_user_list['edittime'] = array('>=',$t_start_data);
			$where_user_list2['edittime'] = array('<=',$t_end_data);
		}		
		$list = Db::name('chat')->where($where_user_list)->where($where_user_list2)->where(function($query){
					$query->where('user_from_id',$GLOBALS['expert_id'])->whereor('user_to_id',$GLOBALS['expert_id']);/*专家id*/
				})->where(function($query){
					$query->where('user_from_id',$GLOBALS['doctor_id'])->whereor('user_to_id',$GLOBALS['doctor_id']);/*医生id*/
				})->where(function($query){
					$query->where('user_from_type',3)->whereor('user_to_type',3);/*专家类型*/
				})->where(function($query){
					$query->where('user_from_type',2)->whereor('user_to_type',2);/*医生类型*/
				})->order('edittime asc,id asc')->paginate($pagelimit,false,$paginate);			
		$lists = array();
		foreach($list as $k => $v){
			$lists[$k] = $v;
			/*添加发信人名称、头像*/
			if($v['user_from_id'] == $expert_id){
				$lists[$k]['nickname'] = $expert['nickname'];
				$lists[$k]['picture'] = $expert['picture'];
			}else if($v['user_from_id'] == $doctor_id){
				$lists[$k]['nickname'] = $doctor['nickname'];
				$lists[$k]['picture'] = $doctor['picture'];	
				$lists[$k]['doctor_from'] = 100;	
			}
		}
		$page = $list->render();/*获取分页显示*/
		$this->assign('list',$lists);
		$this->assign('page',$page);
		/*分配变量*/
		$this->assign('title','咨询专家聊天记录'); /*页面标题*/
		$this->assign('keywords','咨询专家聊天记录'); /*页面关键词*/
		$this->assign('description','咨询专家聊天记录'); /*页面描述*/
		/*调用模板*/
		return $this->fetch(TEMP_FETCH);
	}
	/*删除聊天记录*/
	public function chat_expert_listdel() {
		/*接收参数*/
		$id = input('chat_id'); /*关注ID*/
		$result = Db::name('chat')->where('id='.$id)->delete();
		if ($result) {
			echo '<script>$(document).ready(function(){alertBox("恭喜，删除成功！","'.$_SERVER['HTTP_REFERER'].'");})</script>';
		} else {
			echo '<script>$(document).ready(function(){alertBox("抱歉，删除失败！","'.$_SERVER['HTTP_REFERER'].'");})</script>';
		}
	}	
	/*咨询专家记录管理 end*/	
		
	/*用户咨询记录管理 start*/
	/*用户咨询列表*/
	public function chat_user(){	
		/*所属诊所*/
		$clinic_id = input('clinic_id');
		$this->assign('clinic_id',$clinic_id);
		if(empty($clinic_id)){
			echo '<meta charset="utf-8" />';
			echo '<script>$(document).ready(function(){alertBox("诊所参数缺失！","'.url('medical.clinic/lists').'");})</script>';
			die();
		}
		/*医生ID*/
		$id = input('id');
		$this->assign('id',$id);
		if(empty($id)){
			echo '<meta charset="utf-8" />';
			echo '<script>$(document).ready(function(){alertBox("医生参数缺失！","'.url('medical.clinic/doctor').'?clinic_id='.$clinic_id.'");})</script>';
			die();
		}		
		$doctor = Db::name('user')->field('nickname,picture')->where('id='.$id)->find();
		$doctor_picture_arr = explode(',',$doctor['picture']);
		$doctor['picture'] = $doctor_picture_arr[0];	
		$this->assign('doctor',$doctor);		
		/*获取参数*/
		$t_start = input('t_start');/*开始时间*/
		$this->assign('t_start',$t_start);
		$t_start_data = strtotime($t_start);
		$this->assign('t_start_data',$t_start_data);
		$t_end = input('t_end');/*结束时间*/
		$this->assign('t_end',$t_end);
		$t_end_data = strtotime($t_end);
		$this->assign('t_end_data',$t_end_data);	
		$limit = input('limit');
		$search = input('search');
		$this->assign('limit', $limit);
		$this->assign('search', $search);
		$pagelimit = !empty($limit) ? $limit : config('paginate.list_rows'); /*每页条数*/
		if(!empty($search)){
			$where_user_list['concat(user_from_id,user_to_id,content)'] = array('like', '%' . $search . '%'); /*搜索*/
		}		
		/*查询聊天名师*/
		$where_user_list['user_from_id'] = $id;/*信息发件人*/
		$where_user_list['user_to_type'] = 1;/*信息接受人类型*/
		/*分页参数*/
		$paginate = array('query' => array( /*url额外参数*/
			'clinic_id' => $clinic_id,/*诊所ID*/
			'id' => $id,/*诊所ID*/
			't_start' => $t_start,
			't_end' => $t_end,			
			'limit' => $limit,/*每页条数*/
			'search' => $search,/*搜索*/
		));
		/*条件*/
		$where_user_list2 = array();
		if(!empty($t_start_data) && empty($t_end_data)){
			$where_user_list['edittime'] = array('>=',$t_start_data);
		}else if(!empty($t_end_data) && empty($t_start_data)){
			$where_user_list['edittime'] = array('<=',$t_end_data);
		}else if(!empty($t_end_data) && !empty($t_end_data)){
			$where_user_list['edittime'] = array('>=',$t_start_data);
			$where_user_list2['edittime'] = array('<=',$t_end_data);
		}		
		$list = Db::name('chat')->distinct(true)->field('user_to_id')->where($where_user_list)->where($where_user_list2)->order('edittime desc,id desc')->paginate($pagelimit,false,$paginate);			
		$lists = array();
		foreach($list as $k => $v){
			$lists[$k] = $v;
			/*查询专家身份信息*/
			$user = Db::name('user')->field('nickname,picture')->where('id='.$v['user_to_id'])->find();
			$user_picture_arr = explode(',',$user['picture']);
			$lists[$k]['nickname'] = $user['nickname'];
			$lists[$k]['picture'] = $user_picture_arr[0];
			/*获取最近一条聊天信息*/
			$GLOBALS['user_id'] = $v['user_to_id'];
			$chat_user_list = Db::name('chat')->where('type=1')->where(function($query){
						$query->where('user_from_id',$GLOBALS['user_id'])->whereor('user_to_id',$GLOBALS['user_id']);
					})->where(function($query){
						$query->where('user_from_id',input('id'))->whereor('user_to_id',input('id'));/*专家*/
					})->order('edittime desc,id desc')->find();
			$lists[$k]['chat'] = $chat_user_list;		
		}
		$page = $list->render();/*获取分页显示*/
		$this->assign('list',$lists);
		$this->assign('page',$page);
		/*分配变量*/
		$this->assign('title','用户咨询列表'); /*页面标题*/
		$this->assign('keywords','用户咨询列表'); /*页面关键词*/
		$this->assign('description','用户咨询列表'); /*页面描述*/
		/*调用模板*/
		return $this->fetch(TEMP_FETCH);
	}
	/*用户病历*/
	public function medical_record(){
		/*所属诊所*/
		$clinic_id = input('clinic_id');
		$this->assign('clinic_id',$clinic_id);
		if(empty($clinic_id)){
			echo '<meta charset="utf-8" />';
			echo '<script>$(document).ready(function(){alertBox("诊所参数缺失！","'.url('medical.clinic/lists').'");})</script>';
			die();
		}
		/*用户ID*/
		$user_id = input('user_id');
		$this->assign('user_id',$user_id);
		if(empty($user_id)){
			echo '<meta charset="utf-8" />';
			echo '<script>$(document).ready(function(){alertBox("用户参数缺失！","'.url('medical.clinic/doctor').'?clinic_id='.$clinic_id.'");})</script>';
			die();
		}
		$user = Db::name('user')->field('nickname,picture')->where('id='.$user_id)->find();
		$user_picture_arr = explode(',',$user['picture']);
		$user['picture'] = $user_picture_arr[0];	
		$this->assign('user',$user);	
		/*医生ID*/
		$doctor_id = input('doctor_id');
		$this->assign('doctor_id',$doctor_id);
		if(empty($doctor_id)){
			echo '<meta charset="utf-8" />';
			echo '<script>$(document).ready(function(){alertBox("医生参数缺失！","'.url('medical.clinic/doctor').'?clinic_id='.$clinic_id.'");})</script>';
			die();
		}		
		$doctor = Db::name('user')->field('nickname,picture')->where('id='.$doctor_id)->find();
		$doctor_picture_arr = explode(',',$doctor['picture']);
		$doctor['picture'] = $doctor_picture_arr[0];	
		$this->assign('doctor',$doctor);			
		/*获取参数*/
		$t_start = input('t_start');/*开始时间*/
		$this->assign('t_start',$t_start);
		$t_start_data = strtotime($t_start);
		$this->assign('t_start_data',$t_start_data);
		$t_end = input('t_end');/*结束时间*/
		$this->assign('t_end',$t_end);
		$t_end_data = strtotime($t_end);
		$this->assign('t_end_data',$t_end_data);	
		$limit = input('limit');
		$search = input('search');
		$this->assign('limit', $limit);
		$this->assign('search', $search);
		$pagelimit = !empty($limit) ? $limit : config('paginate.list_rows'); /*每页条数*/
		if(!empty($search)){
			$where_list['concat(name,content)'] = array('like','%'.$search.'%'); /*搜索*/
		}		
		/*查询用户病历*/
		$where_list['user_id'] = $user_id;
		/*分页参数*/
		$paginate = array('query' => array( /*url额外参数*/
			'clinic_id' => $clinic_id,/*诊所ID*/
			'user_id' => $user_id,/*诊所ID*/
			'doctor_id' => $doctor_id,/*医生ID*/
			't_start' => $t_start,
			't_end' => $t_end,			
			'limit' => $limit,/*每页条数*/
			'search' => $search,/*搜索*/
		));
		/*条件*/
		$where_list2 = array();
		if(!empty($t_start_data) && empty($t_end_data)){
			$where_list['jz_time'] = array('>=',$t_start_data);
		}else if(!empty($t_end_data) && empty($t_start_data)){
			$where_list['jz_time'] = array('<=',$t_end_data);
		}else if(!empty($t_end_data) && !empty($t_end_data)){
			$where_list['jz_time'] = array('>=',$t_start_data);
			$where_list2['jz_time'] = array('<=',$t_end_data);
		}		
		$list = Db::name('medical_record')->where($where_list)->where($where_list2)->order('jz_time desc,id desc')->paginate($pagelimit,false,$paginate);			
		$lists = array();
		foreach($list as $k => $v){
			$lists[$k] = $v;	
		}
		$page = $list->render();/*获取分页显示*/
		$this->assign('list',$lists);
		$this->assign('page',$page);
		/*分配变量*/
		$this->assign('title','用户病历列表'); /*页面标题*/
		$this->assign('keywords','用户病历列表'); /*页面关键词*/
		$this->assign('description','用户病历列表'); /*页面描述*/
		/*调用模板*/
		return $this->fetch(TEMP_FETCH);
	}
	/*编辑病历*/
	public function medical_recordedit(){
		/*所属诊所*/
		$clinic_id = input('clinic_id');
		$this->assign('clinic_id',$clinic_id);
		if(empty($clinic_id)){
			echo '<meta charset="utf-8" />';
			echo '<script>$(document).ready(function(){alertBox("诊所参数缺失！","'.url('medical.clinic/lists').'");})</script>';
			die();
		}
		$clinic = Db::name('clinic')->field('name')->where('id='.$clinic_id)->find();
		$this->assign('clinic',$clinic);
		/*用户ID*/
		$user_id = input('user_id');
		$this->assign('user_id',$user_id);
		if(empty($user_id)){
			echo '<meta charset="utf-8" />';
			echo '<script>$(document).ready(function(){alertBox("用户参数缺失！","'.url('medical.clinic/doctor').'?clinic_id='.$clinic_id.'");})</script>';
			die();
		}
		$user = Db::name('user')->field('nickname,picture')->where('id='.$user_id)->find();
		$user_picture_arr = explode(',',$user['picture']);
		$user['picture'] = $user_picture_arr[0];	
		$this->assign('user',$user);	
		/*医生ID*/
		$doctor_id = input('doctor_id');
		$this->assign('doctor_id',$doctor_id);
		if(empty($doctor_id)){
			echo '<meta charset="utf-8" />';
			echo '<script>$(document).ready(function(){alertBox("医生参数缺失！","'.url('medical.clinic/doctor').'?clinic_id='.$clinic_id.'");})</script>';
			die();
		}		
		/*获取参数*/
		$id = input('id'); /*病历ID*/
		if($id){
			/*编辑*/
			/*查询参数*/
			$where['id'] = $id;
			$content = Db::name('medical_record')->where($where)->find();
			foreach ($content as $k => $v) {
				/*解析就诊时间*/
				if ($k == 'jz_time' && !empty($content['jz_time'])) {
					$content['jz_time'] = date('Y/m/d H:i',$v);
				}
				/*解析开药时间*/
				if ($k == 'ky_time' && !empty($content['ky_time'])) {
					$content['ky_time'] = date('Y/m/d H:i',$v);
				}				
				/*解析病历相册*/
				if ($k == 'bl_album' && !empty($content['bl_album'])) {
					$bl_album = explode(',', $v);
					$content['bl_album'] = array();
					foreach ($bl_album as $k2=>$v2) {						$basename = explode('/',$v2);
						$basename = array_pop($basename);
						$content['bl_album'][$k2] = array('filename'=>$v2,'basename'=>$basename);
					}
				}
				/*解析报告相册*/
				if ($k == 'bg_album' && !empty($content['bg_album'])) {
					$bl_album = explode(',', $v);
					$content['bg_album'] = array();
					foreach ($bl_album as $k2=>$v2) {						$basename = explode('/',$v2);
						$basename = array_pop($basename);
						$content['bg_album'][$k2] = array('filename'=>$v2,'basename'=>$basename);
					}
				}
				/*解析病历相册*/
				if ($k == 'cf_album' && !empty($content['cf_album'])) {
					$bl_album = explode(',', $v);
					$content['cf_album'] = array();
					foreach ($bl_album as $k2=>$v2) {						$basename = explode('/',$v2);
						$basename = array_pop($basename);
						$content['cf_album'][$k2] = array('filename'=>$v2,'basename'=>$basename);
					}
				}								
			}
			$this->assign('content',$content); /*内容*/
			/*组装post提交变量*/
			$submit = array('name'=>'确认编辑','url'=>'?id='.$id.'&clinic_id='.$clinic_id.'&doctor_id='.$doctor_id.'&user_id='.$user_id);
			$this->assign('submit',$submit);
			/*分配变量*/
			$this->assign('title', '编辑病历'); /*页面标题*/
			$this->assign('keywords', '编辑病历'); /*页面关键词*/
			$this->assign('description', '编辑病历'); /*页面描述*/
		}else{
			/*添加*/
			/*组装post提交变量*/
			$submit = array('name'=>'确认添加','url'=>'?clinic_id='.$clinic_id.'&doctor_id='.$doctor_id.'&user_id='.$user_id.'&a=add');
			$this->assign('submit',$submit);
			/*分配变量*/
			$this->assign('title', '添加病历'); /*页面标题*/
			$this->assign('keywords', '添加病历'); /*页面关键词*/
			$this->assign('description', '添加病历'); /*页面描述*/
		}
		/*提交*/
		if ($_POST) {
			/*获取参数*/
			$nowtime = time();
			/*验证参数*/
			if (empty(input('name'))) {
				echo '<script>$(document).ready(function(){alertBox("名称不可为空..","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
			}else {
				/*组装参数*/
				$data = array(
					'name'			=> input('name'),/*名称*/
					'user_id'		=> $user_id,
					'clinic_name' 	=> input('clinic_name'),/*就诊机构*/
					'doctor_name' 	=> input('doctor_name'), /*问诊医生*/
					'sex' 			=> input('sex'), /*性别 默认女:0|男为1 */
					'age' 			=> input('age'), /*年龄*/
					'jz_time' 		=> strtotime(input('jz_time')), /*就诊时间*/
					'ky_time' 		=> strtotime(input('ky_time')), /*开药时间*/
					'bl_album'		=> input('bl_album'), /*病历相册*/
					'bg_album' 		=> input('bg_album'), /*报告相册*/
					'cf_album' 		=> input('cf_album'), /*处方相册*/
					'content' 		=> input('content'), /*内容*/
					'order' 		=> input('order'), /*排序  默认为100*/
					'status' 		=> input('status'), /*状态  默认为1:启用|0:禁用*/
					'edittime' 		=> $nowtime, /*修改时间*/
				);
				if($id){
					/*编辑*/
					/*更新数据*/
					$result = Db::name('medical_record')->where('id='.$id)->update($data);
					$alert_success = '恭喜，编辑成功！';
					$alert_error = '抱歉，编辑失败！';
					/*判断结果集*/
					if ($result) {
						echo '<script>$(document).ready(function(){alertBox("'.$alert_success.'","'.$_SERVER['HTTP_REFERER'].'");})</script>';
					}else{
						echo '<script>$(document).ready(function(){alertBox("'.$alert_error.'","'.$_SERVER['HTTP_REFERER'].'");})</script>';
					}					
				}else{
					/*添加*/
					$data['addtime'] = $nowtime; /*添加时间*/
					/*插入数据*/
					$result = Db::name('medical_record')->insert($data);
					$alert_success = '恭喜，添加成功！';
					$alert_error = '抱歉，添加失败！';
					/*判断结果集*/
					if($result){
						echo '<script>$(document).ready(function(){alertBox("'.$alert_success.'","'.url('medical.clinic/medical_record').'?clinic_id='.$clinic_id.'&doctor_id='.$doctor_id.'&user_id='.$user_id.'");})</script>';
					}else{
						echo '<script>$(document).ready(function(){alertBox("'.$alert_error.'","'.$_SERVER['HTTP_REFERER'].'");})</script>';
					}					
				}
			}
		} else {
			/*调用模板*/
			return $this->fetch(TEMP_FETCH);
		}
	}	
	/*删除用户病历*/
	public function medical_recorddel() {
		/*接收参数*/
		$id = input('id'); /*病历ID*/
		$result = Db::name('medical_record')->where('id='.$id)->delete();
		if ($result) {
			echo '<script>$(document).ready(function(){alertBox("恭喜，删除成功！","'.$_SERVER['HTTP_REFERER'].'");})</script>';
		} else {
			echo '<script>$(document).ready(function(){alertBox("抱歉，删除失败！","'.$_SERVER['HTTP_REFERER'].'");})</script>';
		}
	}		
	/*删除与该专家的所有聊天*/
	public function chat_userdel() {
		/*接收参数*/
		$GLOBALS['user_id'] = input('user_id');/*专家ID*/
		$GLOBALS['doctor_id'] = input('doctor_id');/*医生ID*/
		$result = Db::name('chat')->where(function($query){
						$query->where('user_from_id',$GLOBALS['user_id'])->whereor('user_to_id',$GLOBALS['user_id']);/*专家id*/
					})->where(function($query){
						$query->where('user_from_id',$GLOBALS['doctor_id'])->whereor('user_to_id',$GLOBALS['doctor_id']);/*医生id*/
					})->where(function($query){
						$query->where('user_from_type',3)->whereor('user_to_type',3);/*专家类型*/
					})->where(function($query){
						$query->where('user_from_type',2)->whereor('user_to_type',2);/*医生类型*/
					})->delete();
		if($result){
			echo '<script>$(document).ready(function(){alertBox("恭喜，删除成功！","'.$_SERVER['HTTP_REFERER'].'");})</script>';
		}else{
			echo '<script>$(document).ready(function(){alertBox("抱歉，删除失败！","'.$_SERVER['HTTP_REFERER'].'");})</script>';
		}
	}
	/*用户咨询列表*/
	public function chat_user_list(){	
		/*所属诊所*/
		$clinic_id = input('clinic_id');
		$this->assign('clinic_id',$clinic_id);
		if(empty($clinic_id)){
			echo '<meta charset="utf-8" />';
			echo '<script>$(document).ready(function(){alertBox("诊所参数缺失！","'.url('medical.clinic/lists').'");})</script>';
			die();
		}	
		/*专家ID*/
		$user_id = input('user_id');
		$this->assign('user_id',$user_id);
		if(empty($user_id)){
			echo '<meta charset="utf-8" />';
			echo '<script>$(document).ready(function(){alertBox("医生参数缺失！","'.url('medical.clinic/doctor').'?clinic_id='.$clinic_id.'");})</script>';
			die();
		}		
		$user = Db::name('user')->field('nickname,picture')->where('id='.$user_id)->find();
		$user_picture_arr = explode(',',$user['picture']);
		$user['picture'] = $user_picture_arr[0];	
		$this->assign('user',$user);	
		/*医生ID*/
		$doctor_id = input('doctor_id');
		$this->assign('doctor_id',$doctor_id);
		if(empty($doctor_id)){
			echo '<meta charset="utf-8" />';
			echo '<script>$(document).ready(function(){alertBox("医生参数缺失！","'.url('medical.clinic/doctor').'?clinic_id='.$clinic_id.'");})</script>';
			die();
		}		
		$doctor = Db::name('user')->field('nickname,picture')->where('id='.$doctor_id)->find();
		$doctor_picture_arr = explode(',',$doctor['picture']);
		$doctor['picture'] = $doctor_picture_arr[0];	
		$this->assign('doctor',$doctor);		
		/*获取参数*/
		$GLOBALS['user_id'] = $user_id;/*专家ID*/
		$GLOBALS['doctor_id'] = $doctor_id;/*医生ID*/		
		$t_start = input('t_start');/*开始时间*/
		$this->assign('t_start',$t_start);
		$t_start_data = strtotime($t_start);
		$this->assign('t_start_data',$t_start_data);
		$t_end = input('t_end');/*结束时间*/
		$this->assign('t_end',$t_end);
		$t_end_data = strtotime($t_end);
		$this->assign('t_end_data',$t_end_data);	
		$limit = input('limit');
		$search = input('search');
		$this->assign('limit', $limit);
		$this->assign('search', $search);
		$pagelimit = !empty($limit) ? $limit : config('paginate.list_rows'); /*每页条数*/
		if(!empty($search)){
			$where_user_list['concat(user_from_id,user_to_id,content)'] = array('like','%'.$search.'%'); /*搜索*/
		}
		/*分页参数*/
		$paginate = array('query' => array( /*url额外参数*/
			'clinic_id' => $clinic_id,/*诊所ID*/
			'user_id' => $user_id,/*专家ID*/
			'doctor_id' => $doctor_id,/*医生ID*/
			't_start' => $t_start,
			't_end' => $t_end,			
			'limit' => $limit,/*每页条数*/
			'search' => $search,/*搜索*/
		));
		/*条件*/
		$where_user_list['status'] = 1;
		$where_user_list2 = array();
		if(!empty($t_start_data) && empty($t_end_data)){
			$where_user_list['edittime'] = array('>=',$t_start_data);
		}else if(!empty($t_end_data) && empty($t_start_data)){
			$where_user_list['edittime'] = array('<=',$t_end_data);
		}else if(!empty($t_end_data) && !empty($t_end_data)){
			$where_user_list['edittime'] = array('>=',$t_start_data);
			$where_user_list2['edittime'] = array('<=',$t_end_data);
		}		
		$list = Db::name('chat')->where($where_user_list)->where($where_user_list2)->where(function($query){
					$query->where('user_from_id',$GLOBALS['user_id'])->whereor('user_to_id',$GLOBALS['user_id']);/*专家id*/
				})->where(function($query){
					$query->where('user_from_id',$GLOBALS['doctor_id'])->whereor('user_to_id',$GLOBALS['doctor_id']);/*医生id*/
				})->where(function($query){
					$query->where('user_from_type',1)->whereor('user_to_type',1);/*用户类型*/
				})->where(function($query){
					$query->where('user_from_type',2)->whereor('user_to_type',2);/*医生类型*/
				})->order('edittime asc,id asc')->paginate($pagelimit,false,$paginate);			
		$lists = array();
		foreach($list as $k => $v){
			$lists[$k] = $v;
			/*添加发信人名称、头像*/
			if($v['user_from_id'] == $user_id){
				$lists[$k]['nickname'] = $user['nickname'];
				$lists[$k]['picture'] = $user['picture'];
			}else if($v['user_from_id'] == $doctor_id){
				$lists[$k]['nickname'] = $doctor['nickname'];
				$lists[$k]['picture'] = $doctor['picture'];	
				$lists[$k]['doctor_from'] = 100;	
			}
		}
		$page = $list->render();/*获取分页显示*/
		$this->assign('list',$lists);
		$this->assign('page',$page);
		/*分配变量*/
		$this->assign('title','用户咨询聊天记录'); /*页面标题*/
		$this->assign('keywords','用户咨询聊天记录'); /*页面关键词*/
		$this->assign('description','用户咨询聊天记录'); /*页面描述*/
		/*调用模板*/
		return $this->fetch(TEMP_FETCH);
	}
	/*删除聊天记录*/
	public function chat_user_listdel() {
		/*接收参数*/
		$id = input('chat_id'); /*关注ID*/
		$result = Db::name('chat')->where('id='.$id)->delete();
		if ($result) {
			echo '<script>$(document).ready(function(){alertBox("恭喜，删除成功！","'.$_SERVER['HTTP_REFERER'].'");})</script>';
		} else {
			echo '<script>$(document).ready(function(){alertBox("抱歉，删除失败！","'.$_SERVER['HTTP_REFERER'].'");})</script>';
		}
	}	
	/*用户咨询记录管理 end*/
	
	/*培训统计 start*/
	/*培训统计列表*/
	public function cms_foot(){	
		/*所属诊所*/
		$clinic_id = input('clinic_id');
		$this->assign('clinic_id',$clinic_id);
		if(empty($clinic_id)){
			echo '<meta charset="utf-8" />';
			echo '<script>$(document).ready(function(){alertBox("诊所参数缺失！","'.url('medical.clinic/lists').'");})</script>';
			die();
		}
		/*医生ID*/
		$id = input('id');
		$this->assign('id',$id);
		if(empty($id)){
			echo '<meta charset="utf-8" />';
			echo '<script>$(document).ready(function(){alertBox("医生参数缺失！","'.url('medical.clinic/doctor').'?clinic_id='.$clinic_id.'");})</script>';
			die();
		}		
		$doctor = Db::name('user')->field('nickname,picture')->where('id='.$id)->find();
		$doctor_picture_arr = explode(',',$doctor['picture']);
		$doctor['picture'] = $doctor_picture_arr[0];	
		$this->assign('doctor',$doctor);		
		/*获取参数*/
		$t_start = input('t_start');/*开始时间*/
		$this->assign('t_start',$t_start);
		$t_start_data = strtotime($t_start);
		$this->assign('t_start_data',$t_start_data);
		$t_end = input('t_end');/*结束时间*/
		$this->assign('t_end',$t_end);
		$t_end_data = strtotime($t_end);
		$this->assign('t_end_data',$t_end_data);	
		$limit = input('limit');
		$search = input('search');
		$this->assign('limit', $limit);
		$this->assign('search', $search);
		$pagelimit = !empty($limit) ? $limit : config('paginate.list_rows'); /*每页条数*/
		if(!empty($search)){
			$where_list['concat(content_id,title)'] = array('like', '%' . $search . '%'); /*搜索*/
		}		
		/*查询聊天名师*/
		$where_list['user_id'] = $id;/*医生ID*/
		$where_list['site_id'] = $this->site_id;/*站点ID*/
		/*查询分类信息*/
		$category_where['site_id'] = $this->site_id;
		$category_where['channel_id'] = array('neq',22);
		$category_where['status'] = 1;
		$category = Db::name('category')->where($category_where)->order('concat(`path`,`id`)')->select();
		foreach ($category as $k => $v) {
			/*名称*/
			$path_arr = explode(',', trim($v['path'], ','));
			$path_arr_count = count($path_arr);
			$category[$k]['path_count'] = $path_arr_count; //层级数
			$path_str = '';
			if ($path_arr_count > 1) {
				for ($i = 1;$i < $path_arr_count;$i++) {
					$path_str.= '&nbsp;';
				}
				$path_str.= $path_str . '└';
			}
			$category[$k]['name'] = $path_str . $v['name'];
		}
		$this->assign('category', $category); /*分配分类信息*/
		/*分类搜索*/
		$category_id = !empty(input('category')) ? input('category') : 0;
		$this->assign('category_id', $category_id);
		if ($category_id > 0) {
			/*组合当前分类及子分类ID集*/
			$category_where['site_id'] = $this->site_id;
			$category_where['status'] = 1;
			$category_where['path'] = array('like', '%,' . $category_id . ',%');
			$categorys = Db::name('category')->where($category_where)->select();
			$category_str = $category_id;
			if ($categorys) {
				foreach ($categorys as $k => $v) {
					$category_str.= ',' . $v['id'];
				}
			}
		}else{
			$category_str = '';
			foreach($category as $k => $v) {
				$category_str.= ','.$v['id'];
			}
			$category_str = trim($category_str,',');
		}
		$where_list['category_id'] = array('in',$category_str); /*搜索*/		
		/*分页参数*/
		$paginate = array('query' => array( /*url额外参数*/
			'clinic_id' => $clinic_id,/*诊所ID*/
			'id' => $id,/*诊所ID*/
			't_start' => $t_start,
			't_end' => $t_end,			
			'limit' => $limit,/*每页条数*/
			'search' => $search,/*搜索*/
		));
		/*条件*/
		$where_list2 = array();
		if(!empty($t_start_data) && empty($t_end_data)){
			$where_list['edittime'] = array('>=',$t_start_data);
		}else if(!empty($t_end_data) && empty($t_start_data)){
			$where_list['edittime'] = array('<=',$t_end_data);
		}else if(!empty($t_end_data) && !empty($t_end_data)){
			$where_list['edittime'] = array('>=',$t_start_data);
			$where_list2['edittime'] = array('<=',$t_end_data);
		}		
		$list = Db::name('foot')->where($where_list)->where($where_list2)->order('edittime desc,id desc')->paginate($pagelimit,false,$paginate);			
		$lists = array();
		foreach($list as $k => $v){
			$lists[$k] = $v;
			/*查询内容信息*/
			$content = Db::name('content')->field('name,picture')->where('id='.$v['content_id'])->find();
			$content_picture_arr = explode(',',$content['picture']);
			$lists[$k]['name'] = $content['name'];
			$lists[$k]['picture'] = $content_picture_arr[0];	
		}
		$page = $list->render();/*获取分页显示*/
		$this->assign('list',$lists);
		$this->assign('page',$page);
		/*分配变量*/
		$this->assign('title','培训统计列表'); /*页面标题*/
		$this->assign('keywords','培训统计列表'); /*页面关键词*/
		$this->assign('description','培训统计列表'); /*页面描述*/
		/*调用模板*/
		return $this->fetch(TEMP_FETCH);
	}
	/*培训统计 end*/
	
	/*医生帖子管理 start*/
	/*医生帖子列表*/
	public function bbs_doctor(){
		/*所属诊所*/
		$clinic_id = input('clinic_id');
		$this->assign('clinic_id',$clinic_id);
		if(empty($clinic_id)){
			echo '<meta charset="utf-8" />';
			echo '<script>$(document).ready(function(){alertBox("诊所参数缺失！","'.url('medical.clinic/lists').'");})</script>';
			die();
		}
		$clinic = Db::name('clinic')->field('name')->where('id='.$clinic_id)->find();
		$this->assign('clinic',$clinic);	
		/*医生ID*/
		$doctor_id = input('doctor_id');
		$this->assign('doctor_id',$doctor_id);
		if(empty($doctor_id)){
			echo '<meta charset="utf-8" />';
			echo '<script>$(document).ready(function(){alertBox("医生参数缺失！","'.url('medical.clinic/doctor').'?clinic_id='.$clinic_id.'");})</script>';
			die();
		}		
		$doctor = Db::name('user')->field('nickname,picture')->where('id='.$doctor_id)->find();
		$doctor_picture_arr = explode(',',$doctor['picture']);
		$doctor['picture'] = $doctor_picture_arr[0];	
		$this->assign('doctor',$doctor);			
		/*查询内容*/
		/*获取参数*/
		$t_start = input('t_start');/*开始时间*/
		$this->assign('t_start',$t_start);
		$t_start_data = strtotime($t_start);
		$this->assign('t_start_data',$t_start_data);
		$t_end = input('t_end');/*结束时间*/
		$this->assign('t_end',$t_end);
		$t_end_data = strtotime($t_end);
		$this->assign('t_end_data',$t_end_data);		
		$limit = input('limit');
		$search = input('search');
		$this->assign('limit', $limit);
		$this->assign('search', $search);
		$pagelimit = !empty($limit) ? $limit : config('paginate.list_rows'); /*每页条数*/
		if (!empty($search)) {
			$where_list['concat(id,content)'] = array('like', '%' . $search . '%'); /*搜索*/
		}
		/*分页参数*/
		$paginate = array('query' => array( /*url额外参数*/
			'clinic_id' => $clinic_id,/*诊所ID*/
			'doctor_id' => $doctor_id,
			't_start' => $t_start,
			't_end' => $t_end,			
			'limit' => $limit, /*每页条数*/
			'search' => $search, /*搜索*/
		));
		/*条件*/
		$where_list2 = array();
		if(!empty($t_start_data) && empty($t_end_data)){
			$where_list['edittime'] = array('>=',$t_start_data);
		}else if(!empty($t_end_data) && empty($t_start_data)){
			$where_list['edittime'] = array('<=',$t_end_data);
		}else if(!empty($t_end_data) && !empty($t_end_data)){
			$where_list['edittime'] = array('>=',$t_start_data);
			$where_list2['edittime'] = array('<=',$t_end_data);
		}		
		$where_list['site_id'] = $this->site_id;
		$order = array('order' => 'desc', 'id' => 'desc');
		$list = Db::name('bbs_info')->where($where_list)->where($where_list2)->order($order)->paginate($pagelimit, false, $paginate);
		$lists = array();
		foreach ($list as $k => $v) {
			$lists[$k] = $v;
			/*类型*/
			if(!empty($v['type'])){
				if($v['type']==1){
					$lists[$k]['type'] = '医患圈';
				}else if($v['type']==2){
					$lists[$k]['type'] = '专家圈';
				}
			}
			/*相册*/
			if(!empty($v['album'])){
				$list_picture_arr = explode(',',$v['album']);
				$lists[$k]['album'] = $list_picture_arr[0];
			}				
			/*状态*/
			if ($v['status'] == 1) {
				$lists[$k]['status'] = '启用';
			} else {
				$lists[$k]['status'] = '禁用';
			}			
		}
		$page = $list->render(); /*获取分页显示*/
		$this->assign('list', $lists);
		$this->assign('page', $page);
		/*分配变量*/
		$this->assign('title', '医生帖子列表'); /*页面标题*/
		$this->assign('keywords', '医生帖子列表'); /*页面关键词*/
		$this->assign('description', '医生帖子列表'); /*页面描述*/
		/*调用模板*/
		return $this->fetch(TEMP_FETCH);
	}
	/*编辑医生帖子*/
	public function bbs_doctoredit(){
		/*所属诊所*/
		$clinic_id = input('clinic_id');
		$this->assign('clinic_id',$clinic_id);
		if(empty($clinic_id)){
			echo '<meta charset="utf-8" />';
			echo '<script>$(document).ready(function(){alertBox("诊所参数缺失！","'.url('medical.clinic/lists').'");})</script>';
			die();
		}
		$clinic = Db::name('clinic')->field('name')->where('id='.$clinic_id)->find();
		$this->assign('clinic',$clinic);
		/*医生ID*/
		$doctor_id = input('doctor_id');
		$this->assign('doctor_id',$doctor_id);
		if(empty($doctor_id)){
			echo '<meta charset="utf-8" />';
			echo '<script>$(document).ready(function(){alertBox("医生参数缺失！","'.url('medical.clinic/doctor').'?clinic_id='.$clinic_id.'");})</script>';
			die();
		}		
		$doctor = Db::name('user')->field('nickname,picture')->where('id='.$doctor_id)->find();
		$doctor_picture_arr = explode(',',$doctor['picture']);
		$doctor['picture'] = $doctor_picture_arr[0];	
		$this->assign('doctor',$doctor);	
		/*获取参数*/
		$id = input('id'); /*医生帖子Id*/
		if ($id) {
			/*编辑*/
			/*查询参数*/
			$where['id'] = $id;
			$contents = Db::name('bbs_info')->where($where)->find();
			foreach ($contents as $k => $v) {
				$content[$k] = $v;
				/*解析相册*/
				if ($k == 'album' && !empty($content['album'])) {
					$album = explode(',', $v);
					$content['album'] = array();
					foreach ($album as $k2 => $v2) {						$basename = explode('/',$v2);
						$basename = array_pop($basename);
						$content['album'][$k2] = array('filename' => $v2, 'basename' => $basename);
					}
				}
			}
			$this->assign('content', $content); /*内容*/
			/*组装post提交变量*/
			$submit = array('name' => '确认编辑', 'url' => '?id='.$id.'&clinic_id='.$clinic_id.'&doctor_id='.$doctor_id);
			$this->assign('submit', $submit);
			/*分配变量*/
			$this->assign('title', '编辑医生帖子'); /*页面标题*/
			$this->assign('keywords', '编辑医生帖子'); /*页面关键词*/
			$this->assign('description', '编辑医生帖子'); /*页面描述*/
		} else {
			/*添加*/
			/*组装post提交变量*/
			$submit = array('name' => '确认添加', 'url' => '?a=add&clinic_id='.$clinic_id.'&doctor_id='.$doctor_id);
			$this->assign('submit', $submit);
			/*分配变量*/
			$this->assign('title', '添加医生帖子'); /*页面标题*/
			$this->assign('keywords', '添加医生帖子'); /*页面关键词*/
			$this->assign('description', '添加医生帖子'); /*页面描述*/
		}
		/*提交*/
		if ($_POST) {
			/*获取参数*/
			$nowtime = time();
			/*验证参数*/
			if (empty(input('content'))) {
				echo '<script>$(document).ready(function(){alertBox("内容不可为空..","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
			} else {
				/*组装参数*/
				$data = array(
					'site_id' => $this->site_id, /*站点ID*/
					'user_id' => $doctor_id, /*医生ID*/
					'type' => input('type'),/*类型*/
					'album' => input('album'), /*相册*/
					'content' => input('content'), /*内容*/
					'order' => input('order'), /*排序  默认为100*/
					'status' => input('status'), /*状态  默认为1:启用|0:禁用*/
					'edittime' => $nowtime, /*修改时间*/
				);
				if ($id) {
					/*编辑*/
					/*更新数据*/
					$result = Db::name('bbs_info')->where('id=' . $id)->update($data);
					$alert_success = '恭喜，编辑成功！';
					$alert_error = '抱歉，编辑失败！';
				} else {
					/*添加*/
					$data['addtime'] = $nowtime; /*添加时间*/
					/*插入数据*/
					$result = Db::name('bbs_info')->insert($data);
					$alert_success = '恭喜，添加成功！';
					$alert_error = '抱歉，添加失败！';
				}
				/*判断结果集*/
				if ($result) {
					echo '<script>$(document).ready(function(){alertBox("'.$alert_success.'","'.url('medical.clinic/bbs_doctor').'?clinic_id='.$clinic_id.'&doctor_id='.$doctor_id.'");})</script>';
				} else {
					echo '<script>$(document).ready(function(){alertBox("'.$alert_error.'","'.$_SERVER['HTTP_REFERER'].'");})</script>';
				}
			}
		} else {
			/*调用模板*/
			return $this->fetch(TEMP_FETCH);
		}
	}
	/*删除医生帖子*/
	public function bbs_doctordel() {
		/*接收参数*/
		$id = input('id'); /*内容ID*/
		$result = Db::name('bbs_info')->where('id='.$id)->delete();
		if ($result) {
			echo '<script>$(document).ready(function(){alertBox("恭喜，删除成功！","'.$_SERVER['HTTP_REFERER'].'");})</script>';
		} else {
			echo '<script>$(document).ready(function(){alertBox("抱歉，删除失败！","'.$_SERVER['HTTP_REFERER'].'");})</script>';
		}
	}
	/*医生帖子管理 end*/

	/*帖子评论评论管理 start*/
	/*帖子评论列表*/
	public function bbs_comment(){	
		/*所属诊所*/
		$clinic_id = input('clinic_id');
		$this->assign('clinic_id',$clinic_id);
		if(empty($clinic_id)){
			echo '<meta charset="utf-8" />';
			echo '<script>$(document).ready(function(){alertBox("诊所参数缺失！","'.url('medical.clinic/lists').'");})</script>';
			die();
		}
		$clinic = Db::name('clinic')->field('name')->where('id='.$clinic_id)->find();
		$this->assign('clinic',$clinic);
		/*医生ID*/
		$doctor_id = input('doctor_id');
		$this->assign('doctor_id',$doctor_id);
		if(empty($doctor_id)){
			echo '<meta charset="utf-8" />';
			echo '<script>$(document).ready(function(){alertBox("医生参数缺失！","'.url('medical.clinic/doctor').'?clinic_id='.$clinic_id.'");})</script>';
			die();
		}		
		$doctor = Db::name('user')->field('nickname,picture')->where('id='.$doctor_id)->find();
		$doctor_picture_arr = explode(',',$doctor['picture']);
		$doctor['picture'] = $doctor_picture_arr[0];	
		$this->assign('doctor',$doctor);	
		/*帖子ID*/
		$id = input('id');
		$this->assign('id',$id);
		if(empty($id)){
			echo '<meta charset="utf-8" />';
			echo '<script>$(document).ready(function(){alertBox("帖子参数缺失！","'.url('medical.clinic/lists').'");})</script>';
			die();
		}		
		/*查询内容*/
		/*获取参数*/
		$t_start = input('t_start');/*开始时间*/
		$this->assign('t_start',$t_start);
		$t_start_data = strtotime($t_start);
		$this->assign('t_start_data',$t_start_data);
		$t_end = input('t_end');/*结束时间*/
		$this->assign('t_end',$t_end);
		$t_end_data = strtotime($t_end);
		$this->assign('t_end_data',$t_end_data);		
		$limit = input('limit');
		$search = input('search');
		$this->assign('limit', $limit);
		$this->assign('search', $search);
		$pagelimit = !empty($limit) ? $limit : config('paginate.list_rows'); /*每页条数*/
		if (!empty($search)) {
			$where_list['concat(id,content)'] = array('like', '%' . $search . '%'); /*搜索*/
		}
		/*分页参数*/
		$paginate = array('query' => array( /*url额外参数*/
			'clinic_id' => $clinic_id,/*诊所ID*/
			'doctor_id' => $doctor_id,		
			'id' => $id,/*诊所ID*/
			't_start' => $t_start,
			't_end' => $t_end,			
			'limit' => $limit, /*每页条数*/
			'search' => $search, /*搜索*/
		));
		/*条件*/
		$where_list2 = array();
		if(!empty($t_start_data) && empty($t_end_data)){
			$where_list['edittime'] = array('>=',$t_start_data);
		}else if(!empty($t_end_data) && empty($t_start_data)){
			$where_list['edittime'] = array('<=',$t_end_data);
		}else if(!empty($t_end_data) && !empty($t_end_data)){
			$where_list['edittime'] = array('>=',$t_start_data);
			$where_list2['edittime'] = array('<=',$t_end_data);
		}		
		$where_list['bbs_info_id'] = $id;
		$order = array('order' => 'desc', 'id' => 'desc');
		$list = Db::name('bbs_comment')->where($where_list)->where($where_list2)->order($order)->paginate($pagelimit, false, $paginate);
		$lists = array();
		foreach ($list as $k => $v) {
			$lists[$k] = $v;
			/*类型*/
			if(!empty($v['type'])){
				if($v['type']==1){
					$lists[$k]['type'] = '医患圈';
				}else if($v['type']==2){
					$lists[$k]['type'] = '专家圈';
				}
			}
			/*相册*/
			if(!empty($v['album'])){
				$list_picture_arr = explode(',',$v['album']);
				$lists[$k]['album'] = $list_picture_arr[0];
			}				
			/*状态*/
			if ($v['status'] == 1) {
				$lists[$k]['status'] = '启用';
			} else {
				$lists[$k]['status'] = '禁用';
			}			
		}
		$page = $list->render(); /*获取分页显示*/
		$this->assign('list', $lists);
		$this->assign('page', $page);
		/*分配变量*/
		$this->assign('title', '帖子评论列表'); /*页面标题*/
		$this->assign('keywords', '帖子评论列表'); /*页面关键词*/
		$this->assign('description', '帖子评论列表'); /*页面描述*/
		/*调用模板*/
		return $this->fetch(TEMP_FETCH);
	}
	/*删除帖子评论*/
	public function bbs_commentdel() {
		/*接收参数*/
		$id = input('id'); /*内容ID*/
		$result = Db::name('bbs_comment')->where('id='.$id)->delete();
		if ($result) {
			echo '<script>$(document).ready(function(){alertBox("恭喜，删除成功！","'.$_SERVER['HTTP_REFERER'].'");})</script>';
		} else {
			echo '<script>$(document).ready(function(){alertBox("抱歉，删除失败！","'.$_SERVER['HTTP_REFERER'].'");})</script>';
		}
	}
	/*医生帖子评论管理 end*/		
	
	/*医生预约管理 start*/
	/*医生预约列表*/
	public function subscribe(){	
		/*所属诊所*/
		$clinic_id = input('clinic_id');
		$this->assign('clinic_id',$clinic_id);
		if(empty($clinic_id)){
			echo '<meta charset="utf-8" />';
			echo '<script>$(document).ready(function(){alertBox("诊所参数缺失！","'.url('medical.clinic/lists').'");})</script>';
			die();
		}
		/*医生ID*/
		$id = input('id');
		$this->assign('id',$id);
		if(empty($id)){
			echo '<meta charset="utf-8" />';
			echo '<script>$(document).ready(function(){alertBox("医生参数缺失！","'.url('medical.clinic/doctor').'?clinic_id='.$clinic_id.'");})</script>';
			die();
		}		
		$doctor = Db::name('user')->field('nickname,picture')->where('id='.$id)->find();
		$doctor_picture_arr = explode(',',$doctor['picture']);
		$doctor['picture'] = $doctor_picture_arr[0];	
		$this->assign('doctor',$doctor);		
		/*获取参数*/
		$t_start = input('t_start');/*开始时间*/
		$this->assign('t_start',$t_start);
		$t_start_data = strtotime($t_start);
		$this->assign('t_start_data',$t_start_data);
		$t_end = input('t_end');/*结束时间*/
		$this->assign('t_end',$t_end);
		$t_end_data = strtotime($t_end);
		$this->assign('t_end_data',$t_end_data);	
		$limit = input('limit');
		$search = input('search');
		$this->assign('limit', $limit);
		$this->assign('search', $search);
		$pagelimit = !empty($limit) ? $limit : config('paginate.list_rows'); /*每页条数*/
		if(!empty($search)){
			$where_user_list['concat(name,content)'] = array('like', '%' . $search . '%'); /*搜索*/
		}		
		/*查询聊天名师*/
		$where_user_list['site_id'] = $this->site_id;/*站点ID*/
		$where_user_list['user_to_id'] = $id;/*医生ID*/
		/*分页参数*/
		$paginate = array('query' => array( /*url额外参数*/
			'clinic_id' => $clinic_id,/*诊所ID*/
			'id' => $id,/*诊所ID*/
			't_start' => $t_start,
			't_end' => $t_end,			
			'limit' => $limit,/*每页条数*/
			'search' => $search,/*搜索*/
		));
		/*条件*/
		$where_user_list2 = array();
		if(!empty($t_start_data) && empty($t_end_data)){
			$where_user_list['time'] = array('>=',$t_start_data);
		}else if(!empty($t_end_data) && empty($t_start_data)){
			$where_user_list['time'] = array('<=',$t_end_data);
		}else if(!empty($t_end_data) && !empty($t_end_data)){
			$where_user_list['time'] = array('>=',$t_start_data);
			$where_user_list2['time'] = array('<=',$t_end_data);
		}		
		$list = Db::name('subscribe')->where($where_user_list)->where($where_user_list2)->order('time desc,id desc')->paginate($pagelimit,false,$paginate);			
		$lists = array();
		foreach($list as $k => $v){
			$lists[$k] = $v;
			/*查询专家身份信息*/
			$user = Db::name('user')->field('nickname,picture')->where('id='.$v['user_id'])->find();
			$user_picture_arr = explode(',',$user['picture']);
			$lists[$k]['nickname'] = $user['nickname'];
			$lists[$k]['picture'] = $user_picture_arr[0];		
		}
		$page = $list->render();/*获取分页显示*/
		$this->assign('list',$lists);
		$this->assign('page',$page);
		/*分配变量*/
		$this->assign('title','医生预约列表'); /*页面标题*/
		$this->assign('keywords','医生预约列表'); /*页面关键词*/
		$this->assign('description','医生预约列表'); /*页面描述*/
		/*调用模板*/
		return $this->fetch(TEMP_FETCH);
	}
	/*编辑医生预约*/
	public function subscribeedit(){
		/*所属诊所*/
		$clinic_id = input('clinic_id');
		$this->assign('clinic_id',$clinic_id);
		if(empty($clinic_id)){
			echo '<meta charset="utf-8" />';
			echo '<script>$(document).ready(function(){alertBox("诊所参数缺失！","'.url('medical.clinic/lists').'");})</script>';
			die();
		}
		$clinic = Db::name('clinic')->field('name')->where('id='.$clinic_id)->find();
		$this->assign('clinic',$clinic);
		/*医生ID*/
		$doctor_id = input('doctor_id');
		$this->assign('doctor_id',$doctor_id);
		if(empty($doctor_id)){
			echo '<meta charset="utf-8" />';
			echo '<script>$(document).ready(function(){alertBox("医生参数缺失！","'.url('medical.clinic/doctor').'?clinic_id='.$clinic_id.'");})</script>';
			die();
		}		
		$doctor = Db::name('user')->field('nickname,picture')->where('id='.$doctor_id)->find();
		$doctor_picture_arr = explode(',',$doctor['picture']);
		$doctor['picture'] = $doctor_picture_arr[0];	
		$this->assign('doctor',$doctor);		
		/*获取参数*/
		$id = input('id'); /*医生帖子Id*/
		if($id){
			/*编辑*/
			/*查询参数*/
			$where['id'] = $id;
			$contents = Db::name('subscribe')->where($where)->find();
			foreach ($contents as $k => $v) {
				$content[$k] = $v;
				/*预约人*/
				if($k == 'user_id' && !empty($contents['user_id'])){
					$user = Db::name('user')->field('nickname')->where('id='.$contents['user_id'])->find();
					$content['user_nickname'] = $user['nickname'];
				}
				/*预约医生*/
				if($k == 'user_to_id' && !empty($contents['user_to_id'])){
					$doctor = Db::name('user')->field('nickname')->where('id='.$contents['user_to_id'])->find();
					$content['doctor_nickname'] = $doctor['nickname'];
				}	
				/*时间*/	
				if($k == 'time' && !empty($contents['time'])){
					$content['time'] = date('Y/m/d H:i',$contents['time']);
				}		
			}
			$this->assign('id',$id);
			$this->assign('content', $content); /*内容*/
			/*组装post提交变量*/
			$submit = array('name' => '确认编辑', 'url' => '?id='.$id.'&doctor_id='.$doctor_id.'&clinic_id='.$clinic_id);
			$this->assign('submit', $submit);
			/*分配变量*/
			$this->assign('title', '编辑医生预约'); /*页面标题*/
			$this->assign('keywords', '编辑医生预约'); /*页面关键词*/
			$this->assign('description', '编辑医生预约'); /*页面描述*/
		} else {
			echo '<meta charset="utf-8" />';
			echo '<script>$(document).ready(function(){alertBox("预约参数缺失！","'.url('medical.clinic/lists').'");})</script>';
			die();
		}
		/*提交*/
		if ($_POST) {
			/*获取参数*/
			$nowtime = time();
			/*验证参数*/
			if (empty(input('content'))) {
				echo '<script>$(document).ready(function(){alertBox("原因不可为空..","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
			} else {
				/*组装参数*/
				$data = array(
					'site_id' 		=> $this->site_id, /*站点ID*/
					'clinic_id' 	=> $clinic_id, /*诊所ID*/
					'user_id' 		=> input('user_id'),/*预约人ID*/
					'user_to_id' 	=> input('user_to_id'),/*医生ID*/
					'name' 			=> input('name'),/*姓名*/
					'tel' 			=> input('tel'),/*电话*/
					'sex' 			=> input('sex'),/*性别 默认女:0|男为1 */
					'time' 			=> strtotime(input('time')),/*类型*/
					'content' 		=> input('content'),/*原因*/
					'memo' 			=> input('memo'),/*备忘录*/
					'order' 		=> input('order'), /*排序  默认为100*/
					'status' 		=> input('status'), /*状态  默认为1:预约申请中|2:预约成功|3:预约被驳回|4:预约取消*/
					'edittime' 		=> $nowtime, /*修改时间*/
				);
				if($id){
					/*编辑*/
					/*更新数据*/
					$result = Db::name('subscribe')->where('id='.$id)->update($data);
					$alert_success = '恭喜，编辑成功！';
					$alert_error = '抱歉，编辑失败！';
				}else{
					echo '<meta charset="utf-8" />';
					echo '<script>$(document).ready(function(){alertBox("预约参数缺失！","'.url('medical.clinic/lists').'");})</script>';
					die();
				}
				/*判断结果集*/
				if($result){
					echo '<script>$(document).ready(function(){alertBox("'.$alert_success.'","'.url('medical.clinic/subscribe').'?id='.$doctor_id.'&clinic_id='.$clinic_id.'");})</script>';
				}else{
					echo '<script>$(document).ready(function(){alertBox("'.$alert_error.'","'.$_SERVER['HTTP_REFERER'].'");})</script>';
				}
			}
		} else {
			/*调用模板*/
			return $this->fetch(TEMP_FETCH);
		}
	}	
	/*删除医生预约*/
	public function subscribedel() {
		/*接收参数*/
		$id = input('id');/*预约ID*/
		$result = Db::name('subscribe')->where('id='.$id)->delete();
		if($result){
			echo '<script>$(document).ready(function(){alertBox("恭喜，删除成功！","'.$_SERVER['HTTP_REFERER'].'");})</script>';
		}else{
			echo '<script>$(document).ready(function(){alertBox("抱歉，删除失败！","'.$_SERVER['HTTP_REFERER'].'");})</script>';
		}
	}	
	/*医生预约管理 end*/
	
	/*健康计划管理 start*/
	/*健康计划列表*/
	public function health_plan(){	
		/*所属诊所*/
		$clinic_id = input('clinic_id');
		$this->assign('clinic_id',$clinic_id);
		if(empty($clinic_id)){
			echo '<meta charset="utf-8" />';
			echo '<script>$(document).ready(function(){alertBox("诊所参数缺失！","'.url('medical.clinic/lists').'");})</script>';
			die();
		}
		/*医生ID*/
		$id = input('id');
		$this->assign('id',$id);
		if(empty($id)){
			echo '<meta charset="utf-8" />';
			echo '<script>$(document).ready(function(){alertBox("医生参数缺失！","'.url('medical.clinic/doctor').'?clinic_id='.$clinic_id.'");})</script>';
			die();
		}		
		$doctor = Db::name('user')->field('nickname,picture')->where('id='.$id)->find();
		$doctor_picture_arr = explode(',',$doctor['picture']);
		$doctor['picture'] = $doctor_picture_arr[0];	
		$this->assign('doctor',$doctor);		
		/*获取参数*/
		$t_start = input('t_start');/*开始时间*/
		$this->assign('t_start',$t_start);
		$t_start_data = strtotime($t_start);
		$this->assign('t_start_data',$t_start_data);
		$t_end = input('t_end');/*结束时间*/
		$this->assign('t_end',$t_end);
		$t_end_data = strtotime($t_end);
		$this->assign('t_end_data',$t_end_data);	
		$limit = input('limit');
		$search = input('search');
		$this->assign('limit', $limit);
		$this->assign('search', $search);
		$pagelimit = !empty($limit) ? $limit : config('paginate.list_rows'); /*每页条数*/
		if(!empty($search)){
			$where_user_list['concat(name,title,content)'] = array('like', '%' . $search . '%'); /*搜索*/
		}		
		/*查询聊天名师*/
		$where_user_list['site_id'] = $this->site_id;/*站点ID*/
		$where_user_list['user_id'] = $id;/*医生ID*/
		/*分页参数*/
		$paginate = array('query' => array( /*url额外参数*/
			'clinic_id' => $clinic_id,/*诊所ID*/
			'id' => $id,/*诊所ID*/
			't_start' => $t_start,
			't_end' => $t_end,			
			'limit' => $limit,/*每页条数*/
			'search' => $search,/*搜索*/
		));
		/*条件*/
		$where_user_list2 = array();
		if(!empty($t_start_data) && empty($t_end_data)){
			$where_user_list['edittime'] = array('>=',$t_start_data);
		}else if(!empty($t_end_data) && empty($t_start_data)){
			$where_user_list['edittime'] = array('<=',$t_end_data);
		}else if(!empty($t_end_data) && !empty($t_end_data)){
			$where_user_list['edittime'] = array('>=',$t_start_data);
			$where_user_list2['edittime'] = array('<=',$t_end_data);
		}		
		$list = Db::name('health_plan')->where($where_user_list)->where($where_user_list2)->order('edittime desc,id desc')->paginate($pagelimit,false,$paginate);			
		$lists = array();
		foreach($list as $k => $v){
			$lists[$k] = $v;
			/*查询专家身份信息*/
			$user = Db::name('user')->field('nickname,picture')->where('id='.$v['user_to_id'])->find();
			$user_picture_arr = explode(',',$user['picture']);
			$lists[$k]['nickname'] = $user['nickname'];
			$lists[$k]['picture'] = $user_picture_arr[0];		
		}
		$page = $list->render();/*获取分页显示*/
		$this->assign('list',$lists);
		$this->assign('page',$page);
		/*分配变量*/
		$this->assign('title','健康计划列表'); /*页面标题*/
		$this->assign('keywords','健康计划列表'); /*页面关键词*/
		$this->assign('description','健康计划列表'); /*页面描述*/
		/*调用模板*/
		return $this->fetch(TEMP_FETCH);
	}
	/*编辑健康计划*/
	public function health_planedit(){
		/*所属诊所*/
		$clinic_id = input('clinic_id');
		$this->assign('clinic_id',$clinic_id);
		if(empty($clinic_id)){
			echo '<meta charset="utf-8" />';
			echo '<script>$(document).ready(function(){alertBox("诊所参数缺失！","'.url('medical.clinic/lists').'");})</script>';
			die();
		}
		$clinic = Db::name('clinic')->field('name')->where('id='.$clinic_id)->find();
		$this->assign('clinic',$clinic);
		/*医生ID*/
		$doctor_id = input('doctor_id');
		$this->assign('doctor_id',$doctor_id);
		if(empty($doctor_id)){
			echo '<meta charset="utf-8" />';
			echo '<script>$(document).ready(function(){alertBox("医生参数缺失！","'.url('medical.clinic/doctor').'?clinic_id='.$clinic_id.'");})</script>';
			die();
		}		
		$doctor = Db::name('user')->field('nickname,picture')->where('id='.$doctor_id)->find();
		$doctor_picture_arr = explode(',',$doctor['picture']);
		$doctor['picture'] = $doctor_picture_arr[0];	
		$this->assign('doctor',$doctor);		
		/*获取参数*/
		$id = input('id'); /*医生帖子Id*/
		if($id){
			/*编辑*/
			/*查询参数*/
			$where['id'] = $id;
			$contents = Db::name('health_plan')->where($where)->find();
			foreach ($contents as $k => $v) {
				$content[$k] = $v;
				/*计划人*/
				if($k == 'user_to_id' && !empty($contents['user_to_id'])){
					$user = Db::name('user')->field('nickname')->where('id='.$contents['user_to_id'])->find();
					$content['user_nickname'] = $user['nickname'];
				}
				/*计划医生*/
				if($k == 'user_id' && !empty($contents['user_id'])){
					$doctor = Db::name('user')->field('nickname')->where('id='.$contents['user_id'])->find();
					$content['doctor_nickname'] = $doctor['nickname'];
				}
			}
			$this->assign('id',$id);
			$this->assign('content', $content); /*内容*/
			/*组装post提交变量*/
			$submit = array('name' => '确认编辑', 'url' => '?id='.$id.'&doctor_id='.$doctor_id.'&clinic_id='.$clinic_id);
			$this->assign('submit', $submit);
			/*分配变量*/
			$this->assign('title', '编辑健康计划'); /*页面标题*/
			$this->assign('keywords', '编辑健康计划'); /*页面关键词*/
			$this->assign('description', '编辑健康计划'); /*页面描述*/
		} else {
			echo '<meta charset="utf-8" />';
			echo '<script>$(document).ready(function(){alertBox("预约参数缺失！","'.url('medical.clinic/lists').'");})</script>';
			die();
		}
		/*提交*/
		if ($_POST) {
			/*获取参数*/
			$nowtime = time();
			/*验证参数*/
			if (empty(input('content'))) {
				echo '<script>$(document).ready(function(){alertBox("原因不可为空..","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
			} else {
				/*组装参数*/
				$data = array(
					'site_id' 		=> $this->site_id, /*站点ID*/
					'clinic_id' 	=> $clinic_id, /*诊所ID*/
					'user_id' 		=> input('user_id'),/*计划医生ID*/
					'user_to_id' 	=> input('user_to_id'),/*计划人ID*/
					'title' 		=> input('title'),/*计划名称*/
					'name' 			=> input('name'),/*姓名*/
					'sex' 			=> input('sex'),/*性别 默认女:0|男为1 */
					'age' 			=> input('age'),/*年龄*/
					'height' 		=> input('height'),/*身高*/
					'blood' 		=> input('blood'),/*血型*/
					'content' 		=> input('content'),/*原因*/
					'order' 		=> input('order'), /*排序  默认为100*/
					'status' 		=> input('status'), /*状态  默认为1:预约申请中|2:预约成功|3:预约被驳回|4:预约取消*/
					'edittime' 		=> $nowtime, /*修改时间*/
				);
				if($id){
					/*编辑*/
					/*更新数据*/
					$result = Db::name('health_plan')->where('id='.$id)->update($data);
					$alert_success = '恭喜，编辑成功！';
					$alert_error = '抱歉，编辑失败！';
				}else{
					echo '<meta charset="utf-8" />';
					echo '<script>$(document).ready(function(){alertBox("预约参数缺失！","'.url('medical.clinic/lists').'");})</script>';
					die();
				}
				/*判断结果集*/
				if($result){
					echo '<script>$(document).ready(function(){alertBox("'.$alert_success.'","'.url('medical.clinic/subscribe').'?id='.$doctor_id.'&clinic_id='.$clinic_id.'");})</script>';
				}else{
					echo '<script>$(document).ready(function(){alertBox("'.$alert_error.'","'.$_SERVER['HTTP_REFERER'].'");})</script>';
				}
			}
		} else {
			/*调用模板*/
			return $this->fetch(TEMP_FETCH);
		}
	}	
	/*删除健康计划*/
	public function health_plandel() {
		/*接收参数*/
		$id = input('id');/*健康计划ID*/
		$result = Db::name('health_plan')->where('id='.$id)->delete();
		if($result){
			echo '<script>$(document).ready(function(){alertBox("恭喜，删除成功！","'.$_SERVER['HTTP_REFERER'].'");})</script>';
		}else{
			echo '<script>$(document).ready(function(){alertBox("抱歉，删除失败！","'.$_SERVER['HTTP_REFERER'].'");})</script>';
		}
	}	
	/*医生预约管理 end*/	
	
		
}