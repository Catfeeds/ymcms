<?php
namespace app\web\controller\medical;
use app\web\controller\Common;
use think\Request;
use think\Db;
/*医药管理中心*/
class Company extends Common {
	public $pageHead;/*定义页面头部*/
	public $pageFoot;/*定义页面底部*/
	public $temp_id;/*模板ID*/
	
	/*构造方法*/
	public function _initialize() {
		/*重载父类构造方法*/
		parent::_initialize();	
	}

	/*分类管理 start*/
	/*分类列表*/
	public function category(){		
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
			'limit' => $limit,/*每页条数*/
			'search' => $search,/*搜索*/
		));
		/*条件*/
		$where_list['site_id'] = $this->site_id;
		$where_list['clinic_id'] = -2;
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
		/*查询频道*/
		$where_channel['site_id'] = array('IN','1,'.$this->site_id);
		$where_channel['status'] = 1;
		$channel = Db::name('channel')->field('id,name')->where($where_channel)->select();
		$this->assign('channel', $channel);
		/*查询父级分类*/
		$where_category['status'] = 1;
		$where_category['channel_id'] = 22;/*药品频道*/
		$where_category['clinic_id'] = -2;/*医药公司*/
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
		$this->assign('pids',$lists);
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
					'clinic_id' => -2,/*医药公司*/
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
					echo '<script>$(document).ready(function(){alertBox("'.$alert_success.'","'.url('medical.company/category').'");})</script>';
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
			$result = Db::name('category')->where('id='.$id)->delete();
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
		$category_where['clinic_id'] = -2;/*医药公司*/
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
			'limit' => $limit, /*每页条数*/
			'search' => $search, /*搜索*/
		));
		/*条件*/
		$where_list['site_id'] = $this->site_id;
		$order = array('order' => 'desc', 'id' => 'desc');
		$list = Db::name('content')->where($where_list)->order($order)->paginate($pagelimit,false,$paginate);
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
	/*编辑产品*/
	public function productedit(){		
		/*所属分类*/
		$where_category['site_id'] = $this->site_id;
		$where_category['clinic_id'] = -2;
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
				$data = array(
					'site_id' => $this->site_id, /*站点ID*/
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
				if ($id) {
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
					echo '<script>$(document).ready(function(){alertBox("'.$alert_success.'","'.url('medical.company/product').'");})</script>';
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

	/*订单管理 start*/
	/*订单列表*/
	public function order(){						
		/*获取参数*/
		$t_start = input('t_start');/*开始时间*/
		$this->assign('t_start',$t_start);
		$t_start_data = strtotime($t_start);
		$this->assign('t_start_data',$t_start_data);
		$t_end = input('t_end');/*结束时间*/
		$this->assign('t_end',$t_end);
		$t_end_data = strtotime($t_end);
		$this->assign('t_end_data',$t_end_data);
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
		$where_list['type'] = 2;/*进药订单*/
		$order = array('order'=>'desc','id'=>'desc');
		$list_total = Db::name('order')->field('id')->where($where_list)->select();
		$list_total_count = count($list_total);
		$list = Db::name('order')->where($where_list)->where($where_list2)->order($order)->paginate($pagelimit, false, $paginate);
		$lists = array();
		foreach ($list as $k => $v) {
			$lists[$k] = $v;
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
		$this->assign('title', '医药订单列表'); /*页面标题*/
		$this->assign('keywords', '医药订单列表'); /*页面关键词*/
		$this->assign('description', '医药订单列表'); /*页面描述*/
		/*调用模板*/
		return $this->fetch(TEMP_FETCH);
	}
	/*编辑进药订单*/
	public function orderedit() {
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
			$this->assign('title', '医药订单详情'); /*页面标题*/
			$this->assign('keywords', '医药订单详情'); /*页面关键词*/
			$this->assign('description', '医药订单详情'); /*页面描述*/
		} else {			
			echo '<meta charset="utf-8" />';
			echo '<script>$(document).ready(function(){alertBox("订单参数缺失！","'.url('medical.clinic/lists').'");})</script>';
			die();
		}
		/*调用模板*/
		return $this->fetch(TEMP_FETCH);
	}
	/*确认付款*/
	public function status2(){
		$now_time = time();
		/*接收参数*/
		$id = input('id');/*订单ID*/
		$data['status'] = 2;/*确认付款*/
		$data['edittime'] = $now_time;/*时间*/
		$result = Db::name('order')->where('id='.$id)->update($data);
		if ($result) {
			/*订单处理进度*/
			$data_leng_new = array(
				array(
					'status' 	=> 2,
					'msg'		=> '后台确认付款',
					'time'		=> $now_time
				)
			);
			$order = Db::name('order')->field('status_leng')->where('id='.$id)->find();
			if(!empty($order['status_leng'])){
				$data_leng_old = json_decode($order['status_leng'],true);
				array_push($data_leng_old,$data_leng_new[0]);
				$data_leng_new = $data_leng_old;
			}			
			$data_status['status_leng'] = json_encode($data_leng_new);
			$result_status = Db::name('order')->where('id='.$id)->update($data_status);
			echo '<script>$(document).ready(function(){alertBox("恭喜，确认付款成功！","'.$_SERVER['HTTP_REFERER'].'");})</script>';
		} else {
			echo '<script>$(document).ready(function(){alertBox("抱歉，确认付款失败！","'.$_SERVER['HTTP_REFERER'].'");})</script>';
		}
	}
	/*确认订单*/
	public function status3(){
		$now_time = time();
		/*接收参数*/
		$id = input('id');/*订单ID*/
		$data['status'] = 3;/*确认订单*/
		$data['edittime'] = $now_time;/*时间*/
		$result = Db::name('order')->where('id='.$id)->update($data);
		if ($result) {
			/*订单处理进度*/
			$data_leng_new = array(
				array(
					'status' 	=> 3,
					'msg'		=> '商家确认订单',
					'time'		=> $now_time
				)
			);
			$order = Db::name('order')->field('status_leng')->where('id='.$id)->find();
			if(!empty($order['status_leng'])){
				$data_leng_old = json_decode($order['status_leng'],true);
				array_push($data_leng_old,$data_leng_new[0]);
				$data_leng_new = $data_leng_old;
			}			
			$data_status['status_leng'] = json_encode($data_leng_new);
			$result_status = Db::name('order')->where('id='.$id)->update($data_status);
			echo '<script>$(document).ready(function(){alertBox("恭喜，确认订单成功！","'.$_SERVER['HTTP_REFERER'].'");})</script>';
		} else {
			echo '<script>$(document).ready(function(){alertBox("抱歉，确认订单失败！","'.$_SERVER['HTTP_REFERER'].'");})</script>';
		}
	}
	/*订单发货*/
	public function status4(){
		$now_time = time();
		/*接收参数*/
		$id = input('id');/*订单ID*/
		$data['status'] = 4;/*确认订单*/
		$data['edittime'] = $now_time;/*时间*/
		$result = Db::name('order')->where('id='.$id)->update($data);
		if ($result) {
			/*订单处理进度*/
			$data_leng_new = array(
				array(
					'status' 	=> 4,
					'msg'		=> '商家订单发货',
					'time'		=> $now_time
				)
			);
			$order = Db::name('order')->field('status_leng')->where('id='.$id)->find();
			if(!empty($order['status_leng'])){
				$data_leng_old = json_decode($order['status_leng'],true);
				array_push($data_leng_old,$data_leng_new[0]);
				$data_leng_new = $data_leng_old;
			}			
			$data_status['status_leng'] = json_encode($data_leng_new);
			$result_status = Db::name('order')->where('id='.$id)->update($data_status);
			echo '<script>$(document).ready(function(){alertBox("恭喜，订单发货成功！","'.$_SERVER['HTTP_REFERER'].'");})</script>';
		} else {
			echo '<script>$(document).ready(function(){alertBox("抱歉，订单发货失败！","'.$_SERVER['HTTP_REFERER'].'");})</script>';
		}
	}	
	/*订单收货*/
	public function status5(){
		$now_time = time();
		/*接收参数*/
		$id = input('id');/*订单ID*/
		$data['status'] = 5;/*确认订单*/
		$data['edittime'] = $now_time;/*时间*/
		$result = Db::name('order')->where('id='.$id)->update($data);
		if ($result) {
			/*订单处理进度*/
			$data_leng_new = array(
				array(
					'status' 	=> 5,
					'msg'		=> '商家后台确认收货',
					'time'		=> $now_time
				)
			);
			$order = Db::name('order')->field('status_leng')->where('id='.$id)->find();
			if(!empty($order['status_leng'])){
				$data_leng_old = json_decode($order['status_leng'],true);
				array_push($data_leng_old,$data_leng_new[0]);
				$data_leng_new = $data_leng_old;
			}			
			$data_status['status_leng'] = json_encode($data_leng_new);
			$result_status = Db::name('order')->where('id='.$id)->update($data_status);
			/*订单完成*/
			$now_time2 = time();
			$data2['status'] = 10;/*订单完成*/
			$data2['edittime'] = $now_time2;/*时间*/
			$result2 = Db::name('order')->where('id='.$id)->update($data2);
			/*订单处理进度*/
			$data_leng_new2 = array(
				array(
					'status' 	=> 10,
					'msg'		=> '订单完成',
					'time'		=> $now_time2
				)
			);
			$order2 = Db::name('order')->field('status_leng')->where('id='.$id)->find();
			if(!empty($order2['status_leng'])){
				$data_leng_old2 = json_decode($order2['status_leng'],true);
				array_push($data_leng_old2,$data_leng_new2[0]);
				$data_leng_new2 = $data_leng_old2;
			}			
			$data_status2['status_leng'] = json_encode($data_leng_new2);
			$result_status2 = Db::name('order')->where('id='.$id)->update($data_status2);						
			/*返回结果集*/
			echo '<script>$(document).ready(function(){alertBox("恭喜，订单收货成功！","'.$_SERVER['HTTP_REFERER'].'");})</script>';
		} else {
			echo '<script>$(document).ready(function(){alertBox("抱歉，订单收货失败！","'.$_SERVER['HTTP_REFERER'].'");})</script>';
		}
	}	
	/*订单关闭*/
	public function status12(){
		$now_time = time();
		/*接收参数*/
		$id = input('id');/*订单ID*/
		$data['status'] = 12;/*确认订单*/
		$data['edittime'] = $now_time;/*时间*/
		$result = Db::name('order')->where('id='.$id)->update($data);
		if ($result) {
			/*订单处理进度*/
			$data_leng_new = array(
				array(
					'status' 	=> 12,
					'msg'		=> '商家订单发货',
					'time'		=> $now_time
				)
			);
			$order = Db::name('order')->field('status_leng')->where('id='.$id)->find();
			if(!empty($order['status_leng'])){
				$data_leng_old = json_decode($order['status_leng'],true);
				array_push($data_leng_old,$data_leng_new[0]);
				$data_leng_new = $data_leng_old;
			}			
			$data_status['status_leng'] = json_encode($data_leng_new);
			$result_status = Db::name('order')->where('id='.$id)->update($data_status);
			echo '<script>$(document).ready(function(){alertBox("恭喜，订单关闭成功！","'.$_SERVER['HTTP_REFERER'].'");})</script>';
		} else {
			echo '<script>$(document).ready(function(){alertBox("抱歉，订单关闭失败！","'.$_SERVER['HTTP_REFERER'].'");})</script>';
		}
	}			
	/*删除医药订单*/
	public function orderdel(){
		$now_time = time();
		/*接收参数*/
		$id = input('id');/*订单ID*/
		$data['status'] = 13;/*删除订单*/
		$data['edittime'] = $now_time;/*时间*/
		$result = Db::name('order')->where('id='.$id)->update($data);
		//$result = Db::name('order')->where('id='.$id)->delete();
		if ($result) {
			/*订单处理进度*/
			$data_leng_new = array(
				array(
					'status' 	=> 13,
					'msg'		=> '删除订单',
					'time'		=> $now_time
				)
			);
			$order = Db::name('order')->field('status_leng')->where('id='.$id)->find();
			if(!empty($order['status_leng'])){
				$data_leng_old = json_decode($order['status_leng'],true);
				array_push($data_leng_old,$data_leng_new[0]);
				$data_leng_new = $data_leng_old;
			}			
			$data_status['status_leng'] = json_encode($data_leng_new);
			$result_status = Db::name('order')->where('id='.$id)->update($data_status);
			echo '<script>$(document).ready(function(){alertBox("恭喜，删除成功！","'.$_SERVER['HTTP_REFERER'].'");})</script>';
		} else {
			echo '<script>$(document).ready(function(){alertBox("抱歉，删除失败！","'.$_SERVER['HTTP_REFERER'].'");})</script>';
		}
	}
	/*进药订单管理 end*/
	
}