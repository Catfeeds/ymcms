<?php
namespace app\web\controller\medical;
use app\web\controller\Common;
use think\Request;
use think\Db;
use app\common\lib\Upload;
/*CMS内容管理系统*/
class Cms extends Common {
	public $pageHead;/*定义页面头部*/
	public $pageFoot;/*定义页面底部*/
	public $temp_id;/*模板ID*/
	
	/*构造方法*/
	public function _initialize() {
		/*重载父类构造方法*/
		parent::_initialize();		
	}

	/*TP的文件简单的同步批量上传方法*/
	public function filetp($array){
		$file_ico = array();
		foreach($array as $k){
			/*移动到框架应用根目录/public/upload/system目录下*/
			$path = 'http://www.'.config('system_domain').'/public/upload/system/image/'.date('Ymd').'/';
			$info = $k->move(ROOT_PATH.'public'.DS.'upload'.DS.'system'.DS.'image');
			if($info){
				/*文件成功上传后 获取上传信息*/
				$file_ico[] = $path.$info->getFilename();
			}
		}
		$json_ico = json_encode($file_ico);	
		return $json_ico;
	}
	/*频道管理 start*/
	/*频道列表*/
	public function channellist() {
		/*获取参数*/
		$limit = input('limit');
		$search = input('search');
		$this->assign('limit', $limit);
		$this->assign('search', $search);
		$pagelimit = !empty($limit) ? $limit : config('paginate.list_rows'); /*每页条数*/
		if (!empty($search)) {
			$where_list['concat(name,id)'] = array('like', '%' . $search . '%'); /*搜索*/
		}
		/*查询*/
		$where_list['id'] = array('gt', 0);
		$where_list['site_id'] = $this->site_id;
		//$whereOr_list['type'] = 1;/*系统频道*/
		/*分页参数*/
		$paginate = array('query' => array( /*url额外参数*/
			'limit'	 => $limit, /*每页条数*/
			'search' => $search, /*搜索*/
		));
		$list = Db::name('channel')->where($where_list)->paginate($pagelimit, false, $paginate);
		$lists = array();
		foreach ($list as $k => $v) {
			$lists[$k] = $v;
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
		$this->assign('title', '频道列表'); /*页面标题*/
		$this->assign('keywords', '频道列表'); /*页面关键词*/
		$this->assign('description', '频道列表'); /*页面描述*/
		/*调用模板*/
		return $this->fetch(TEMP_FETCH);
	}
	/*编辑分类*/
	public function channeledit() {
		/*获取参数*/
		$id = input('id'); /*分类Id*/
		if ($id) {
			/*编辑*/
			/*查询参数*/
			$where['id'] = $id;
			$content = Db::name('channel')->where($where)->find();
			$this->assign('content', $content); /*内容*/
			/*组装post提交变量*/
			$submit = array('name' => '确认编辑', 'url' => '?id=' . $id);
			$this->assign('submit', $submit);
			/*分配变量*/
			$this->assign('title', '编辑专题频道'); /*页面标题*/
			$this->assign('keywords', '编辑专题频道'); /*页面关键词*/
			$this->assign('description', '编辑专题频道'); /*页面描述*/
		} else {
			/*添加*/
			/*组装post提交变量*/
			$submit = array('name' => '确认添加', 'url' => '?a=add');
			$this->assign('submit', $submit);
			/*分配变量*/
			$this->assign('title', '添加专题频道'); /*页面标题*/
			$this->assign('keywords', '添加专题频道'); /*页面关键词*/
			$this->assign('description', '添加专题频道'); /*页面描述*/
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
				$data = array('name' => input('name'), /*名称*/
				'description' => input('description'), /*简介*/
				'temp_home' => input('temp_home'), /*频道首页模板*/
				'temp_list' => input('temp_list'), /*频道列表模板*/
				'temp_content' => input('temp_content'), /*频道详情模板*/
				'order' => input('order'), /*排序  默认为100*/
				'status' => input('status'), /*状态  默认为1:启用|0:禁用*/
				'edittime' => $nowtime, /*修改时间*/
				);
				if ($id) {
					/*编辑*/
					/*更新数据*/
					$result = Db::name('channel')->where('id=' . $id)->update($data);
					$alert_success = '恭喜，编辑成功！';
					$alert_error = '抱歉，编辑失败！';
				} else {
					/*添加*/
					$data['site_id'] = $this->site_id; /*站点ID*/
					$data['addtime'] = $nowtime; /*添加时间*/
					/*插入数据*/
					$result = Db::name('channel')->insert($data);
					$alert_success = '恭喜，添加成功！';
					$alert_error = '抱歉，添加失败！';
				}
				/*判断结果集*/
				if ($result) {
					echo '<script>$(document).ready(function(){alertBox("' . $alert_success . '","' . url('medical.cms/channellist') . '");})</script>';
				} else {
					echo '<script>$(document).ready(function(){alertBox("' . $alert_error . '","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
				}
			}
		} else {
			/*调用模板*/
			return $this->fetch(TEMP_FETCH);
		}
	}
	/*删除频道*/
	public function channeldel() {
		/*接收参数*/
		$id = input('id'); /*分类ID*/
		/*判断是否存在分类调用*/
		$where_list['channel_id'] = $id;
		$sub = Db::name('category')->where($where_list)->find();
		if ($sub) {
			/*存在不允许删除元素*/
			echo '<script>$(document).ready(function(){alertBox("抱歉，有分类正在调用，不允许删除！","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
		} else {
			/*不存在不允许删除元素*/
			$result = Db::name('channel')->where('id=' . $id)->delete();
			if ($result) {
				echo '<script>$(document).ready(function(){alertBox("恭喜，删除成功！","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
			} else {
				echo '<script>$(document).ready(function(){alertBox("抱歉，删除失败！","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
			}
		}
	}
	/*频道管理 end*/
	
	/*分类管理 start*/
	/*分类列表*/
	public function categorylist() {
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
			'limit' => $limit, /*每页条数*/
			'search' => $search, /*搜索*/
		));
		/*条件*/
		$where_list['site_id'] = $this->site_id;
		$where_list['channel_id'] = array('neq',22);
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
	public function categoryedit() {
		/*查询频道*/
		$where_channel['site_id'] = array('IN','1,'.$this->site_id);
		$where_channel['id'] = array('neq',22);
		$where_channel['status'] = 1;
		$channel = Db::name('channel')->field('id,name')->where($where_channel)->select();
		$this->assign('channel', $channel);
		/*查询父级分类*/
		$where_category['status'] = 1;
		$where_category['channel_id'] = array('neq',22);
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
				if (input('pids') == 0) {
					$data['path'] = '0,';
				} else {
					$path = Db::name('category')->field('path')->where('id=' . input('pids'))->find();
					$data['path'] = $path['path'] . input('pids') . ',';
				}				
				if ($id) {
					/*编辑*/
					$result = Db::name('category')->where('id=' . $id)->update($data);
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
					echo '<script>$(document).ready(function(){alertBox("' . $alert_success . '","' . url('medical.cms/categorylist') . '");})</script>';
				} else {
					echo '<script>$(document).ready(function(){alertBox("' . $alert_error . '","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
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
	
	/*内容管理 start*/
	/*内容列表*/
	public function contentlist() {
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
		$list = Db::name('content')->where($where_list)->order($order)->paginate($pagelimit, false, $paginate);
		$lists = array();
		foreach ($list as $k => $v) {
			$lists[$k] = $v;
			/*所属分类*/
			if(!empty($v['category_id'])){
				$category_lisone = Db::name('category')->field('name')->where('id='.$v['category_id'])->find();
				$lists[$k]['category'] = $category_lisone['name'];
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
		$this->assign('title', '内容列表'); /*页面标题*/
		$this->assign('keywords', '内容列表'); /*页面关键词*/
		$this->assign('description', '内容列表'); /*页面描述*/
		/*调用模板*/
		return $this->fetch(TEMP_FETCH);
	}
	/*编辑内容*/
	public function contentedit() {
		/*查询频道*/
		$where_channel['site_id'] = array('IN','1,'.$this->site_id);
		$where_channel['id'] = array('neq',22);
		$where_channel['status'] = 1;
		$channel = Db::name('channel')->field('id,name')->where($where_channel)->select();
		$this->assign('channel', $channel);		
		/*所属分类*/
		$where_category['site_id'] = $this->site_id;
		$where_category['channel_id'] = array('neq',22);
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
		$id = input('id'); /*内容Id*/
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
					foreach ($ico as $k2 => $v2) {
						$basename = explode('/',$v2);
						$basename = array_pop($basename);
						$content['ico'][$k2] = array('filename' => $v2, 'basename' => $basename);
					}
				}
				if ($k == 'picture' && !empty($content['picture'])) {
					$picture = explode(',', $v);
					$content['picture'] = array();
					foreach ($picture as $k2 => $v2) {
						$basename = explode('/',$v2);
						$basename = array_pop($basename);
						$content['picture'][$k2] = array('filename' => $v2, 'basename' => $basename);
					}
				}
				/*解析视频*/
				if ($k == 'video' && !empty($content['video'])) {
					$video = explode(',',$content['video']);
					$content['video'] = $video[0];
				}				
			}
			$this->assign('content', $content); /*内容*/
			/*组装post提交变量*/
			$submit = array('name' => '确认编辑', 'url' => '?id=' . $id);
			$this->assign('submit', $submit);
			/*分配变量*/
			$this->assign('title', '编辑内容'); /*页面标题*/
			$this->assign('keywords', '编辑内容'); /*页面关键词*/
			$this->assign('description', '编辑内容'); /*页面描述*/
		} else {
			/*添加*/
			/*组装post提交变量*/
			$submit = array('name' => '确认添加', 'url' => '?a=add');
			$this->assign('submit', $submit);
			/*分配变量*/
			$this->assign('title', '添加内容'); /*页面标题*/
			$this->assign('keywords', '添加内容'); /*页面关键词*/
			$this->assign('description', '添加内容'); /*页面描述*/
		}
		/*提交*/
		if ($_POST) {
			/*获取参数*/
			$nowtime = time();
			/*验证参数*/
			if (empty(input('name'))) {
				echo '<script>$(document).ready(function(){alertBox("名称不可为空..","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
			} else {
				/*上传文件*/				
				$files_video = request()->file('video');/*获取表单上传文件*/
				if(!empty($files_video)){
					/*七牛上传*/
					$video = Upload::fileput($_FILES['video']);
					//$video = $this->filetp($files_video);;
				}else{
					$video = '';
				}
				/*组装参数*/
				$data = array(
					'site_id' 		=> $this->site_id, /*站点ID*/
					'channel_id' 	=> input('channel'), /*频道ID*/
					'category_id' 	=> input('category'), /*分类ID*/
					'name' 			=> input('name'), /*名称*/
					'ico' 			=> input('ico'), /*图标*/
					'picture' 		=> input('picture'), /*缩略图*/
					'description' 	=> input('description'), /*简介*/
					'content' 		=> input('content'), /*内容*/
					'video'			=> $video,/*视频*/
					'url' 			=> input('url'), /*链接*/
					'author' 		=> input('author'), /*作者*/
					'source' 		=> input('source'), /*来源*/
					'visitor' 		=> input('visitor'), /*访问量*/
					'order' 		=> input('order'), /*排序  默认为100*/
					'status' 		=> input('status'), /*状态  默认为1:启用|0:禁用*/
					'edittime' 		=> $nowtime, /*修改时间*/
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
					echo '<script>$(document).ready(function(){alertBox("' . $alert_success . '","' . url('medical.cms/contentlist') . '");})</script>';
				} else {
					echo '<script>$(document).ready(function(){alertBox("' . $alert_error . '","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
				}
			}
		} else {
			/*调用模板*/
			return $this->fetch(TEMP_FETCH);
		}
	}
	/*删除内容*/
	public function contentdel() {
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
	/*内容管理 end*/
	
	/*模块管理 start*/
	/*模块列表*/
	public function blocklist() {
		/*查询模块*/
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
		'limit' => $limit, /*每页条数*/
		'search' => $search, /*搜索*/
		));
		/*条件*/
		$where_list['site_id'] = $this->site_id;
		$order = array('order' => 'desc', 'id' => 'desc');
		$list = Db::name('block')->where($where_list)->order($order)->paginate($pagelimit, false, $paginate);
		$lists = array();
		foreach ($list as $k => $v) {
			$lists[$k] = $v;
			foreach($v as $k2=>$v2){
				if($k2 == 'temp_id'){
					$temp = Db::name('temp')->where('id='.$v2)->find();
					$lists[$k]['temp_name'] = $temp['name'];
					if($temp['screen'] == 1){
						$lists[$k]['screen_name'] = '电脑PC';
					}else{
						$lists[$k]['screen_name'] = '移动WAP';
					}
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
		$this->assign('page', $page);
		/*分配变量*/
		$this->assign('title', '模块列表'); /*页面标题*/
		$this->assign('keywords', '模块列表'); /*页面关键词*/
		$this->assign('description', '模块列表'); /*页面描述*/
		/*调用模板*/
		return $this->fetch(TEMP_FETCH);
	}
	/*编辑模块*/
	public function blockedit() {
		/*查询频道*/
		$where_channel['status'] = 1;
		$where_channel['site_id'] = array('IN','1,'.$this->site_id);
		$channel = Db::name('channel')->field('id,name')->where($where_channel)->select();
		$this->assign('channel', $channel);		
		/*站点无限级分类*/
		$where_list['site_id'] = $this->site_id;
		$where_list['status'] = 1;
		$list = Db::name('category')->field('id,path,name')->where($where_list)->order('concat(`path`,`id`)')->select();
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
		}
		$this->assign('categoryList', $lists);				
		/*获取参数*/
		$temp_id = input('tid');
		$id = input('id'); /*模块Id*/
		$this->assign('block_id', $id);
		if ($id) {
			$temp_id = input('tid');
			$this->assign('temp_id', $temp_id);
			$s_url = url('medical.cms/tempfit').'?id='.$temp_id;
			/*编辑*/ 
			/*查询参数*/
			$where['id'] = $id;
			$content = Db::name('block')->where($where)->find();
			foreach ($content as $k => $v) {
				/*解析图标、图片*/
				if ($k == 'ico' && !empty($content['ico'])) {
					$ico = explode(',', $v);
					$content['ico'] = array();
					foreach ($ico as $k2 => $v2) {						
						$basename = explode('/',$v2);
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
			/*解析内容*/
			if (!empty($content['type'])) {
				if (!empty($content['content'])) {
					/*列表菜单*/
					if ($content['type'] == 2){
						$content_con = myTrim($content['content']);
						$contentlist = json_decode($content_con);
						$content['content2'] = array();
						foreach ($contentlist as $k => $v) {
							if(is_string($v) || is_int($v)){
								$content['content2'][$k] = $v;
							}else{
								foreach($v as $k2=>$v2){
									$content['content2'][$k][$k2] = $v2;
								}
							}
						}
						/*解析自定义列表*/
						if(!empty($content['content2']['type1'])){
							$content2_type1 = array();
							foreach($content['content2']['type1'] as $k=>$v){
								foreach($v as $k2=>$v2){
									$content2_type1[$k][$k2] = $v2;	
								}
							}
						}
						$content['content2']['type1'] = array();
						$content['content2']['type1'] = $content2_type1;
					}
					/*自定义表单*/
					if ($content['type'] == 3){
						$content_con = myTrim($content['content']);
						$contentlist = json_decode($content_con);
						$content['content3'] = array();
						foreach ($contentlist as $k => $v) {
							$content['content3'][$k]['type'] = $v->type;
							$content['content3'][$k]['name'] = $v->name;
							$conOption = explode(',',$v->option);
							foreach($conOption as $k2=>$v2){
								$content['content3'][$k]['option'][$k2] = $v2;
							}
						}
					}					
				}
			}
			$this->assign('content', $content); /*模块*/
			/*组装post提交变量*/
			$submit = array('name' => '确认编辑','url'=>'?id='.$id.'&tid='.$temp_id);
			$this->assign('submit', $submit);
			/*分配变量*/
			$this->assign('title', '编辑模块'); /*页面标题*/
			$this->assign('keywords', '编辑模块'); /*页面关键词*/
			$this->assign('description', '编辑模块'); /*页面描述*/
		} else {
			/*添加*/
			/*组装post提交变量*/
			$submit = array('name' => '确认添加', 'url' => '?a=add');
			$this->assign('submit', $submit);
			/*分配变量*/
			$this->assign('title', '添加模块'); /*页面标题*/
			$this->assign('keywords', '添加模块'); /*页面关键词*/
			$this->assign('description', '添加模块'); /*页面描述*/
		}
		/*提交*/
		if ($_POST) {
			/*获取参数*/
			$nowtime = time();
			if (input('type') == 1 || input('type') == 4 || input('type') == -1 || input('type') == -2) {
				/*单图文*/
				$content = input('content');
			} else if (input('type') == 2) {
				/*列表菜单*/
				$content = myTrim(input('content2'));
			} else if (input('type') == 3) {
				/*自定义菜单*/
				$content = myTrim(input('content3'));
			}
			/*验证参数*/
			if (empty(input('name'))) {
				echo '<script>$(document).ready(function(){alertBox("名称不可为空..","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
			} else {
				/*组装参数*/
				$data = array(
					'name' => input('name'), /*名称*/
					'title' => input('title'), /*标题*/
					'description' => input('description'), /*简介*/
					'content' => $content, /*内容*/
					'ico' => input('ico'), /*图标*/
					'picture' => input('picture'), /*缩略图*/
					'type' => input('type'), /*类型 1单模块 | 2列表模块 | 3自定义表单*/
					'order' => input('order'), /*排序  默认为100*/
					'status' => input('status'), /*状态  默认为1:启用|0:禁用*/
					'edittime' => $nowtime, /*修改时间*/
				);
				if ($id) {
					/*编辑*/
					/*更新数据*/
					$result = Db::name('block')->where('id=' . $id)->update($data);
					$alert_success = '恭喜，编辑成功！';
					$alert_error = '抱歉，编辑失败！';
				} else {
					/*添加*/
					$data['site_id'] = $this->site_id; /*站点ID*/
					$site_info = $this->site;
					$data['style'] = 'left:100px;top:100px;width:100px;height:100px;'; /*样式*/
					$data['temp_id'] = 1; /*模板ID*/
					$data['plugin'] = 0; /*用户模板*/
					$data['addtime'] = $nowtime; /*添加时间*/
					/*插入数据*/
					$result = Db::name('block')->insert($data);
					$alert_success = '恭喜，添加成功！';
					$alert_error = '抱歉，添加失败！';
				}
				/*判断结果集*/
				if ($result) {
					$s_url = url('medical.cms/blocklist');
					echo '<script>$(document).ready(function(){alertBox("' . $alert_success . '","'.$s_url.'");})</script>';
				} else {
					echo '<script>$(document).ready(function(){alertBox("' . $alert_error . '","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
				}
			}
		} else {
			/*调用模板*/
			return $this->fetch(TEMP_FETCH);
		}
	}
	/*设置模块*/
	public function blocksave() {
		$temp_id = input('tid');		
		/*获取参数*/
		$id = input('id'); /*模块Id*/
		if($id){		
			/*编辑*/
			/*查询参数*/
			$where['id'] = $id;
			$content = Db::name('block')->field('style')->where($where)->find();
			/*解析CSS样式转换成数组*/
			if (!empty($content['style'])) {
				$styleStr = trim($content['style'],';');
				$styleStr = explode(';',$styleStr);
				foreach($styleStr as $k=>$v){
					$styleValue[$k] = explode(':',$v);
				}
				foreach($styleValue as $k=>$v){
					$key = trim($v[0]);
					$value = trim($v[1]);
					$style[$key] = $value;
				}
			}
			$this->assign('content',$style); /*模块*/
			/*组装post提交变量*/
			$submit = array('name'=>'提交设置','url'=>'?id='.$id.'&tid='.$temp_id);
			$this->assign('submit', $submit);
		}else{
			echo '<script>$(document).ready(function(){alertBox("模块参数缺失..");})</script>';
		}
		/*提交*/
		if($_POST){
			$s_url = url('medical.cms/tempfit').'?id='.$temp_id;
			/*获取并组装参数*/
			$backcolor = input('background-color');
			$styleStr = 'background-color:'.$backcolor.';';/*背景颜色*/
			$backimgArr = explode('/',input('background-image'));
			$backimgLast = array_pop($backimgArr);
			if($backimgLast != 'undefined'){
				$styleStr .= 'background-image:url('.input('background-image').');';/*背景图片*/
			}
			$styleStr .= 'background-position:'.input('background-position').';';/*背景位置*/
			$styleStr .= 'background-repeat:'.input('background-repeat').';';/*背景重复*/
			$styleStr .= 'border-style:'.input('border-style').';';/*边框样式*/
			$styleStr .= 'border-color:'.input('border-color').';';/*边框颜色*/
			$styleStr .= 'border-width:'.input('border-width').';';/*边框宽度*/
			$styleStr .= 'margin:'.input('margin').';';/*外边距*/
			$styleStr .= 'padding:'.input('padding').';';/*内边距*/
			$styleStr .= 'opacity:'.input('opacity').';';/*透明度*/
			$styleStr .= 'z-index:'.input('z-index').';';/*层级*/
			$styleStr .= 'color:'.input('color').';';/*文字颜色*/
			$styleStr .= 'left:'.input('left').';';/*左边距*/
			$styleStr .= 'top:'.input('top').';';/*上边距*/
			$styleStr .= 'width:'.input('width').';';/*宽*/
			$styleStr .= 'height:'.input('height').';';/*高*/
			$data['style'] = $styleStr;
			/*编辑*/
			/*更新数据*/
			$result = Db::name('block')->where('id='.$id)->update($data);
			$alert_success = '恭喜，编辑成功！';
			$alert_error = '抱歉，编辑失败！';
			/*判断结果集*/
			if($result){
				echo '<script>$(document).ready(function(){alertBox("' . $alert_success . '","' .$s_url. '");})</script>';
			} else {
				echo '<script>$(document).ready(function(){alertBox("' . $alert_error . '","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
			}
		}else{
			/*分配变量*/
			$this->assign('title', '模块设置'); /*页面标题*/
			$this->assign('keywords', '模块设置'); /*页面关键词*/
			$this->assign('description', '模块设置'); /*页面描述*/			
			/*调用模板*/
			return $this->fetch(TEMP_FETCH);
		}
	}	
	/*删除模块*/
	public function blockdel() {
		/*接收参数*/
		$id = input('id'); /*模块ID*/
		$result = Db::name('block')->where('id=' . $id)->delete();
		if ($result) {
			echo '<script>$(document).ready(function(){alertBox("恭喜，删除成功！","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
		} else {
			echo '<script>$(document).ready(function(){alertBox("抱歉，删除失败！","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
		}
	}
	/*表单模块列表*/
	public function formlist() {
		/*查询模块*/
		/*获取参数*/
		$block_id = input('id');
		$this->assign('block_id', $block_id);
		if($block_id){
			$limit = input('limit');
			$search = input('search');
			$this->assign('limit', $limit);
			$this->assign('search', $search);
			$pagelimit = !empty($limit) ? $limit : config('paginate.list_rows'); /*每页条数*/
			if (!empty($search)) {
				$where_list['content'] = array('like', '%' . $search . '%'); /*搜索*/
			}
			/*分页参数*/
			$paginate = array('query' => array( /*url额外参数*/
				'id' => $block_id, /*模块ID*/
				'limit' => $limit, /*每页条数*/
				'search' => $search, /*搜索*/
			));
			/*条件*/
			$where_list['block_id'] = $block_id;
			$order = array('order' => 'desc', 'id' => 'desc');
			$list = Db::name('form')->where($where_list)->order($order)->paginate($pagelimit, false, $paginate);
			$lists = array();
			foreach ($list as $k => $v) {
				$lists[$k] = $v;
				/*内容*/
				$lists[$k]['content'] = trim(trim(trim(trim($v['content'],'['),']'),'{'),'}');
			}
			$page = $list->render(); /*获取分页显示*/
			$this->assign('list', $lists);
			$this->assign('page', $page);
			/*分配变量*/
			$this->assign('title', '表单内容列表'); /*页面标题*/
			$this->assign('keywords', '表单内容列表'); /*页面关键词*/
			$this->assign('description', '表单内容列表'); /*页面描述*/
			/*调用模板*/
			return $this->fetch(TEMP_FETCH);
		}else{
			echo '<script>$(document).ready(function(){alertBox("表单模块参数缺失！","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
		}
	}
	/*查看表单内容*/
	public function formedit() {
		/*获取参数*/
		$id = input('id'); /*模块Id*/
		$this->assign('id', $id);
		if ($id) {
			/*编辑*/ 
			/*查询参数*/
			$where['id'] = $id;
			$content = Db::name('form')->where($where)->find();
			/*解析内容*/
			if (!empty($content['content'])) {
				$contentObj = json_decode($content['content']);
				foreach($contentObj as $k=>$v){
					foreach($v as $k2=>$v2){
						$contentArr[$k2] = $v2;	
					}
				}
				$content['content'] = $contentArr;
			}
			$this->assign('content', $content); /*模块*/
			/*分配变量*/
			$this->assign('title', '查看表单内容'); /*页面标题*/
			$this->assign('keywords', '查看表单内容'); /*页面关键词*/
			$this->assign('description', '查看表单内容'); /*页面描述*/
			/*调用模板*/
			return $this->fetch(TEMP_FETCH);			
		}else{
			echo '<script>$(document).ready(function(){alertBox("表单模块参数缺失！","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
		}
	}	
	/*删除模块*/
	public function formdel() {
		/*接收参数*/
		$id = input('id'); /*表单内容ID*/
		$result = Db::name('form')->where('id=' . $id)->delete();
		if ($result) {
			echo '<script>$(document).ready(function(){alertBox("恭喜，删除成功！","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
		} else {
			echo '<script>$(document).ready(function(){alertBox("抱歉，删除失败！","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
		}
	}	
	/*模块管理 end*/

	/*模板广场 start*/
	/*模板列表*/
	public function templist() {
		/*查询行业*/
		$industry = Db::name('industry')->field('id,name')->where('status=1')->order('`order` desc,`id` desc')->select();
		$this->assign('industry',$industry);
		/*查询风格*/
		$style = Db::name('style')->field('id,name')->where('status=1')->order('`order` desc,`id` desc')->select();
		$this->assign('style',$style);		
		/*查询颜色*/
		$color = Db::name('color')->field('id,name')->where('status=1')->order('`order` desc,`id` desc')->select();
		$this->assign('color',$color);			
		/*查询站点信息*/
		$site = Db::name('site')->field('server_id,temp_ids')->where('id='.$this->site_id)->find();
		$this->assign('site_on',$site);
		/*查询模板*/
		/*获取参数*/
		$limit = input('limit');/*条数*/
		$search = input('search');/*关键词*/
		$industry_id = input('industry_id');/*行业ID*/
		$style_id = input('style_id');/*风格ID*/
		$color_id = input('color_id');/*颜色ID*/
		$this->assign('limit', $limit);
		$this->assign('search', $search);
		$this->assign('industry_id', $industry_id);
		$this->assign('style_id', $style_id);
		$this->assign('color_id', $color_id);
		$pagelimit = !empty($limit) ? $limit : config('paginate.list_rows'); /*每页条数*/
		if (!empty($search)) {
			$where_list['concat(name,description)'] = array('like', '%' . $search . '%'); /*搜索*/
		}
		if (!empty($industry_id)) {
			$where_list['industry_ids'] = array('like', '%' . $industry_id . '%'); /*行业*/
		}
		if (!empty($style_id)) {
			$where_list['style_ids'] = array('like', '%' . $style_id . '%'); /*风格*/
		}
		if (!empty($color_id)) {
			$where_list['color_ids'] = array('like', '%' . $color_id . '%'); /*颜色*/
		}	
		/*分页参数*/
		$paginate = array('query' => array( /*url额外参数*/
			'limit' 	=> $limit,/*每页条数*/
			'search' 	=> $search,/*搜索*/
			'industry_id' => $industry_id,/*行业ID*/
			'style_id' 	=> $style_id,/*风格ID*/
			'color_id' 	=> $color_id,/*颜色ID*/
		));
		/*条件*/
		$where_list['server_id'] = $site['server_id'];
		$where_list['type'] = 2;/*类型:系统模板*/
		$where_list['status'] = 1;/*状态:启用*/
		$order = array('order' => 'desc', 'id' => 'desc');
		$list = Db::name('temp')->where($where_list)->order($order)->paginate($pagelimit, false, $paginate);
		$lists = array();
		foreach ($list as $k => $v) {
			$lists[$k] = $v;
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
		$this->assign('title', '模板广场'); /*页面标题*/
		$this->assign('keywords', '模板广场'); /*页面关键词*/
		$this->assign('description', '模板广场'); /*页面描述*/
		/*调用模板*/
		return $this->fetch(TEMP_FETCH);
	}
	/*使用模板*/
	public function tempuse(){
		$temp_id = input('id');/*模板ID*/
		$temp = Db::name('temp')->where('id='.$temp_id)->find();
		$site = Db::name('site')->where('id='.$this->site_id)->find();
		$temp_ids_arr = json_decode($site['temp_ids'],true);
		if($temp['screen'] == 1){
			/*PC*/
			$array = array(
				'pc' => $temp_id,
				'wap'=> $temp_ids_arr['wap']
			);
		}else if($temp['screen'] == 2){
			/*wap*/
			$array = array(
				'pc' => $temp_ids_arr['pc'],
				'wap'=> $temp_id
			);			
		}
		$newdata['temp_ids'] = json_encode($array);
		$result = Db::name('site')->where('id='.$this->site_id)->update($newdata);
		if($result){
			echo '<script>$(document).ready(function(){alertBox("恭喜，使用成功！","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
		}else{
			echo '<script>$(document).ready(function(){alertBox("抱歉，使用失败！","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
		}
	}
	/*我的模板*/
	public function tempmy(){		
		/*查询站点信息*/
		$site = Db::name('site')->field('server_id,temp_ids')->where('id='.$this->site_id)->find();
		$this->assign('site_on',$site);
		$usetemp = json_decode($site['temp_ids'],true);
		$this->assign('usetemp',$usetemp);
		/*查询PC模板*/
		$where_list['server_id'] = $site['server_id'];
		$where_list['site_id'] = $this->site_id;/*站点*/
		$where_list['screen'] = 1;/*类型:PC模板*/
		$where_list['status'] = 1;/*状态:启用*/
		$order = array('order' => 'desc', 'id' => 'desc');
		$list_pc = Db::name('temp')->where($where_list)->order($order)->select();
		$this->assign('list_pc', $list_pc);
		/*查询WAP模板*/
		$where_list['server_id'] = $site['server_id'];
		$where_list['site_id'] = $this->site_id;/*站点*/
		$where_list['screen'] = 2;/*类型:WAP模板*/
		$where_list['status'] = 1;/*状态:启用*/
		$order = array('order' => 'desc', 'id' => 'desc');
		$list_wap = Db::name('temp')->where($where_list)->order($order)->select();
		$this->assign('list_wap', $list_wap);		
		/*分配变量*/
		$this->assign('title', '我的模板'); /*页面标题*/
		$this->assign('keywords', '我的模板'); /*页面关键词*/
		$this->assign('description', '我的模板'); /*页面描述*/
		/*调用模板*/
		return $this->fetch(TEMP_FETCH);
	}
	/*模板装修*/
	public function tempfit(){
		/*获取参数*/
		$site_id = $this->site_id;
		$temp_id = input('id');
		/*查询模板信息*/
		$temp = Db::name('temp')->where('id='.$temp_id)->find();			
		/*为系统站点开放模块 start*/
		if($site_id == 1){
			/*PC模板推介*/
			$temp_pc_where['type'] = 2;/*系统模板*/
			$temp_pc_where['screen'] = 1;/*PC端*/
			$temp_pc_where['status'] = 1;/*状态*/
			$temp_pc = Db::name('temp')->field('id,name,picture,description')->where($temp_pc_where)->limit(12)->select();
			$this->assign('temp_pc',$temp_pc);
			/*WAP模板推介*/
			$temp_wap_where['type'] = 2;/*系统模板*/
			$temp_wap_where['screen'] = 2;/*WAP端*/
			$temp_wap_where['status'] = 1;/*状态*/
			$temp_wap = Db::name('temp')->field('id,name,picture,description')->where($temp_wap_where)->limit(12)->select();
			$this->assign('temp_wap',$temp_wap);
		}
		/*为系统站点开放模块 end*/
		/*频道查询*/
		$page_channel_where['site_id'] = array('IN','1,'.$site_id);
		$page_channel = Db::name('channel')->field('id,name')->where($page_channel_where)->select();		
		$page_id = input('page_id');
		/*当频道ID省略或不存在时自动匹配首页*/
		if(empty($page_id)){
			$page_id = -1;
		}
		$page_home = 0;
		foreach($page_channel as $k=>$v){
			if($v['id'] == $page_id){
				$page_home = 1;
			}
		}
		if($page_home == 0){
			$page_id = -1;
		}
		/*装修模块分类选项卡*/
		$btype = !empty(input('btype'))?input('btype'):0;
		$this->assign('btype',$btype);
		$this->assign('temp_id',$temp_id);
		$this->assign('page_id',$page_id);		
		/*分配变量*/
		$this->assign('title','首页');/*页面标题*/
		$this->assign('keywords','首页');/*页面关键词*/
		$this->assign('description','首页');/*页面描述*/		
		/*查询系统插件模块*/
		$block_system_where['status'] = 1;/*状态*/
		$block_system_where['plugin'] = 1;/*非系统插件*/
		if($btype > 0){
			$block_system_where['type'] = $btype;/*类型*/
		}
		$block_system = Db::name('block')->where($block_system_where)->select();
		/*解析模块*/
		$html = $this->pageHead;/*页面头部*/
		/*装修菜单工具栏*/
		$html .= '<div class="fithead">';
		$html .= '<div class="fitnav">';
		$html .= '<div class="fitlogo"><span class="logimg"><img src="{$site.picture}" alt="" />&nbsp;</span><span class="logtex">模板装修</span></div>';
		$html .= '<ul>';
		$html .= '<li {if condition="$btype == 0 "}class="on"{/if}><a href="?id='.$temp_id.'&page_id='.$page_id.'&btype=0">全部</a></li>';
		$html .= '<li {if condition="$btype == 1 "}class="on"{/if}><a href="?id='.$temp_id.'&page_id='.$page_id.'&btype=1">图文</a></li>';
		$html .= '<li {if condition="$btype == 2 "}class="on"{/if}><a href="?id='.$temp_id.'&page_id='.$page_id.'&btype=2">列表</a></li>';
		$html .= '<li {if condition="$btype == 3 "}class="on"{/if}><a href="?id='.$temp_id.'&page_id='.$page_id.'&btype=3">表单</a></li>';
		$html .= '<li {if condition="$btype == 4 "}class="on"{/if}><a href="?id='.$temp_id.'&page_id='.$page_id.'&btype=4">更多</a></li>';		
		$html .= '</ul>';
		$html .= '<div class="black"><a href="'.url('medical.cms/tempmy').'"><img src="__IMAGE__/ico_white/arrow_left.png" />返回</a></div>';
		$html .= '<select class="pagechange" temp_id="{$temp_id}" btype="{$btype}">';
		$html .= '<option value="-1" {if condition="$page_id == -1 "}selected{/if}>首页</option>';
		foreach($page_channel as $k=>$v){
			$html .= '<option value="'.$v['id'].'" {if condition="$page_id == '.$v['id'].' "}selected{/if}>'.$v['name'].'</option>';
		}
		$html .= '</select>';		
		$html .= '</div>';
		$html .= '<div class="fitclone">';
		$html .= '<ul>';
		foreach($block_system as $k=>$v){
			$html .= '<li id="'.$v['id'].'">';
			$html .= '<div class="box">';
			$html .= '<p class="imgtop"></p><p class="img"><img src="/public/upload/space/site_1/'.$v['picture'].'" /></p>';/*缩略图*/
			$html .= '<p class="nam">'.$v['name'].'</p>';/*名称*/
			$html .= '<p class="des">'.$v['description'].'</p>';/*简介*/
			$html .= '</div>';
			$html .= '</li>';
		}
		$html .= '</ul>';
		$html .= '</div>';
		$html .= '</div>';
		/*主体内容 start*/
		if($temp['screen'] == 2){
			$html .= '<div class="phone_box" style="" site_id="'.$site_id.'" temp_id="'.$temp_id.'">';
			$html .= '<div class="main main_wap" style="" site_id="'.$site_id.'" temp_id="'.$temp_id.'">';
		}else{
			$html .= '<div class="main" style="" site_id="'.$site_id.'" temp_id="'.$temp_id.'">';
		}
		/*查询模板展示模块 start*/
		$block_all_where['temp_id'] = $temp_id;/*模板ID*/
		$block_all_where['status'] = 1;/*状态*/
		$block_all_where['plugin'] = 0;/*非系统插件*/
		$block_all_where['page'] = array('IN',$page_id.',-2');/*频道页面*/
		$block_all = Db::name('block')->where($block_all_where)->select();	
		/*解析[#block#]自定义标签 start*/
		foreach($block_all as $k=>$v){
			foreach($v as $k_tag_bid=>$v_tag_bid){
				if($k_tag_bid == 'html'){
					$blockandid = "block".$v['id'];/*组装模块参数*/
					$block_all[$k]['html'] = str_replace("[#block#]",$blockandid,$v['html']);/*转化模块html中绑定参数*/
				}
			}
		}
		/*解析[#block#]自定义标签 end*/
		foreach($block_all as $k=>$v){					
			$html .= '<div class="block" id="'.$v['id'].'" style="'.$v['style'].'" type="'.$v['type'].'">';
			$data = $v['content'];
			/*判断内容是否为空，为空则使用默认填充
			if(!empty($v['content'])){
				$data = $v['content'];
			}else{
				$data = $v['data'];
			}
			*/
			/*判断模块类型*/
			if($v['type']==1){
				/*单页图文*/
				$html .= $data;
			}else if($v['type']==2){
				/*解析JSON信息*/
				$contentlist = json_decode($data);
				$content2 = array();
				foreach ($contentlist as $k2 => $v2) {
					if(is_string($v2) || is_int($v2)){
						$content2[$k2] = $v2;
					}else{
						foreach($v2 as $k3=>$v3){
							$content2[$k2][$k3] = $v3;
						}
					}
				}
				/*解析自定义列表*/
				if(!empty($content2['type1'])){
					$content2_type1 = array();
					foreach($content2['type1'] as $k2=>$v2){
						foreach($v2 as $k3=>$v3){
							$content2_type1[$k2][$k3] = $v3;	
						}
					}
				}
				$content2['type1'] = array();/*将对象解析为数组*/
				$content2['type1'] = $content2_type1;
				/*判断调用自定义列表还是系统内容*/
				if($content2['status']==2){
					$content2Type2Category 	= $content2['type2']['category'];/*所属分类*/
					$content2Type2Order 	= $content2['type2']['order'];/*排序*/
					if($content2Type2Order == 'time'){
						$content2Type2Order = 'edittime desc';
					}else if($content2Type2Order == 'visitor'){
						$content2Type2Order = 'visitor desc';
					}else if($content2Type2Order == 'sales'){
						$content2Type2Order = 'sales desc';
					}
					$content2Type2Limit 	= $content2['type2']['limit'];/*条数*/
					/*系统内容*/
					if($content2['type2']['page'] == 1){
						/*分页*/
						
					}else{
						/*不分页*/
						$content2Type2Where['category_id'] = array('in',$content2Type2Category);
						$content2Type2Content = Db::name('content')->where($content2Type2Where)->order($content2Type2Order)->limit($content2Type2Limit)->select();
						$content2Info = $content2Type2Content;
					}
				}else{
					/*自定义列表*/
					$content2Info = $content2['type1'];
				}
				/*为变量标识模块ID*/
				$block_id = 'block'.$v['id'];
				$this->assign($block_id,$content2Info);	
				$html .= $v['html'];			
			}else if($v['type']==3){
				/*自定义表单*/
				$data = json_decode($data);
				/*将json转化成数组*/
				foreach($data as $k2=>$v2){
					foreach($v2 as $k3=>$v3){
						$content3[$k2][$k3] = $v3;
						if($k3 == 'option'){
							$conv3option = explode(',',$v3);
							$content3[$k2][$k3] = $conv3option;
						}
					}
				}
				/*为变量标识模块ID*/
				$block_id = 'block'.$v['id'];
				$this->assign($block_id,$content3);
				$html .= $v['html'];			
			}else if($v['type']==4){
				/*更多*/
				$html .= $v['html'];			
			}else if($v['type']==-1){
				/*系统频道列表*/
				$html .= $v['html'];			
			}
			$html .= '</div>';	
		}
		/*查询模板展示模块 end*/
		/*查询频道列表 start*/
		if($page_id == 32){
			/*系统站点-模板市场*/
			/*查询行业*/
			$industry = Db::name('industry')->field('id,name')->where('status=1')->order('`order` desc,`id` desc')->select();
			$this->assign('industry',$industry);
			/*查询风格*/
			$style = Db::name('style')->field('id,name')->where('status=1')->order('`order` desc,`id` desc')->select();
			$this->assign('style',$style);		
			/*查询颜色*/
			$color = Db::name('color')->field('id,name')->where('status=1')->order('`order` desc,`id` desc')->select();
			$this->assign('color',$color);			
			/*模板使用类型终端*/
			$usetype = array(
				array('id' => 1,'name' => '电脑PC'),
				array('id' => 2,'name' => '手机WAP')
			);
			$this->assign('usetype',$usetype);						
			/*查询站点信息*/
			$site = Db::name('site')->field('server_id,temp_ids')->where('id='.$this->site_id)->find();
			$this->assign('site_on',$site);
			/*查询模板*/
			/*获取参数*/
			$limit = input('limit');/*条数*/
			$search = input('search');/*关键词*/
			$industry_id = input('industry_id');/*行业ID*/
			$style_id = input('style_id');/*风格ID*/
			$color_id = input('color_id');/*颜色ID*/
			$usetype_id = input('usetype_id');/*使用类型ID*/
			$this->assign('limit', $limit);
			$this->assign('search', $search);
			$this->assign('industry_id', $industry_id);
			$this->assign('style_id', $style_id);
			$this->assign('color_id', $color_id);
			$this->assign('usetype_id', $usetype_id);
			$pagelimit = !empty($limit) ? $limit : config('paginate.list_rows'); /*每页条数*/
			if (!empty($search)) {
				$where_list['concat(name,description)'] = array('like', '%' . $search . '%'); /*搜索*/
			}
			if (!empty($industry_id)) {
				$where_list['industry_ids'] = array('like', '%' . $industry_id . '%'); /*行业*/
			}
			if (!empty($style_id)) {
				$where_list['style_ids'] = array('like', '%' . $style_id . '%'); /*风格*/
			}
			if (!empty($color_id)) {
				$where_list['color_ids'] = array('like', '%' . $color_id . '%'); /*颜色*/
			}
			if (!empty($usetype_id)) {
				$where_list['screen'] = $usetype_id; /*使用类型终端*/
			}					
			/*分页参数*/
			$paginate = array('query' => array( /*url额外参数*/
				'limit' 	=> $limit,/*每页条数*/
				'search' 	=> $search,/*搜索*/
				'industry_id' => $industry_id,/*行业ID*/
				'style_id' 	=> $style_id,/*风格ID*/
				'color_id' 	=> $color_id,/*颜色ID*/
				'usetype_id' => $usetype_id,/*颜色ID*/
			));
			/*条件*/
			$where_list['server_id'] = $site['server_id'];
			$where_list['type'] = 2;/*类型:系统模板*/
			$where_list['status'] = 1;/*状态:启用*/
			$order = array('order' => 'desc', 'id' => 'desc');
			$list = Db::name('temp')->where($where_list)->order($order)->paginate($pagelimit, false, $paginate);
			$lists = array();
			foreach ($list as $k => $v) {
				$lists[$k] = $v;
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
		}else{
			/*获取参数*/
			$limit = 12;
			$pagelimit = !empty($limit) ? $limit : config('paginate.list_rows'); /*每页条数*/
			/*分页参数*/
			$paginate = array('query' => array( /*url额外参数*/ ));
			/*查询频道下分类字符串集*/
			$categoty_where['channel_id'] 	= $page_id; 
			$categoty_where['site_id'] 		= $this->site_id;
			$categoryArr = Db::name('category')->field('id')->where($categoty_where)->select();
			$categoryStr = '';
			foreach($categoryArr as $k=>$v){
				if(!empty($categoryStr)){
					$categoryStr .= ','.$v['id'];
				}else{
					$categoryStr .= $v['id'];
				}
			}
			/*条件*/
			$where_list['site_id'] = $this->site_id;
			if(!empty($categoryStr)){
				$where_list['category_id'] = array('IN',$categoryStr);
			}
			$list = Db::name('content')->where($where_list)->order('edittime')->paginate($pagelimit, false, $paginate);
			$lists = array();
			foreach ($list as $k => $v) {
				$lists[$k] = $v;
			}
			$page = $list->render(); /*获取分页显示*/
			$this->assign('list', $lists);
			$this->assign('page', $page);
		}
		/*查询频道列表 end*/
		if($this->is_mobile){
			$html .= '</div></div>';
		}else{
			$html .= '</div>';
		}
		/*主体内容 end*/
		$pageUrlVal = '?id='.$temp_id.'&page_id='.$page_id.'&btype='.$btype;		
		$html .= '<link rel="stylesheet" href="{$dir_temp}/file/css/tempfit.css" />';/*装修CSS*/
		$html .= '<script>';
					/*创建模块*/
		$html .= 	'function tempfitBlockClone(site_id,temp_id,page_channel,block_id,style){';
		$html .=		'$.post("{:url(\'medical.cms/tempfitBlockClone\')}",{';
		$html .=			'site_id : site_id,';		/*站点ID*/
		$html .=			'temp_id : temp_id,';		/*模板ID*/
		$html .=			'page_channel : page_channel,';/*调用页面*/
		$html .=			'block_id : block_id,';		/*模块ID*/
		$html .=			'style : style';			/*样式*/
		$html .=		'},function(data){';
		$html .=			'data = eval("("+data+")");';
		$html .=			'if(data.status == 100){';
		$html .=				'alertBox(data.msg,"'.$pageUrlVal.'");';
		$html .=			'}else{';
		$html .=				'alertBox(data.msg,"'.$pageUrlVal.'");';
		$html .=			'}';
		$html .=		'});';
		$html .=	'}';
					/*保存模块*/
		$html .= 	'function tempfitBlockSave(block_id,top,left,width,height){';
		$html .=		'$.post("{:url(\'medical.cms/tempfitBlockSave\')}",{';
		$html .=			'block_id : block_id,';	/*模块ID*/
		$html .=			'top : top,';/*上*/
		$html .=			'left : left,';/*左*/
		$html .=			'width : width,';/*宽*/						
		$html .=			'height : height';/*高*/
		$html .=		'},function(data){';
		$html .=			'data = eval("("+data+")");';
		$html .=			'if(data.status == 100){';
		$html .=				'alertBox(data.msg,"'.$pageUrlVal.'");';
		$html .=			'}else{';
		$html .=				'alertBox(data.msg,"'.$pageUrlVal.'");';
		$html .=			'}';
		$html .=		'});';
		$html .=	'}';		
		$html .= '</script>';
		$html .= '<script src="{$dir_temp}/file/js/tempfit.js"></script>';/*装修JS*/
		$html .= $this->pageFoot;/*页面底部*/		
		/*渲染内容*/
		return $this->display($html);		
		/*调用模板*/
		/*return $this->fetch(TEMP_FETCH);*/
	}
	/*AJAX创建模块*/
	public function tempfitBlockClone(){
		/*接受参数*/
		$site_id = input('site_id');/*站点ID*/
		$temp_id = input('temp_id');/*模板ID*/
		$block_id = input('block_id');/*模块ID*/
		$page = input('page_channel');/*调用页面*/
		$style = input('style').'width:200px;height:200px;';/*模块样式*/
		$nowtime = time();
		/*查询插件模块*/
		$plugin = Db::name('block')->where('id='.$block_id)->find();
		if($plugin['page'] == -2){
			$page = -2;
		} 
		$data = array(
			'site_id' 	=> $site_id,/*站点ID*/
			'temp_id' 	=> $temp_id,/*模板ID*/
			'page' 		=> $page,/*调用页面*/
			'name' 		=> $plugin['name'],/*名称*/
			'title' 	=> $plugin['title'],/*标题*/
			'description'=>$plugin['description'],/*简介*/
			'content' 	=> $plugin['content'],/*内容*/
			'data' 		=> $plugin['data'],/*默认数据*/
			'type' 		=> $plugin['type'],/*类型 1单模块 | 2列表模块 | 3自定义表单*/
			'html'		=> $plugin['html'],
			'style'		=> $style,
			'plugin'	=> 0,/*非系统插件*/
			'order'		=> 100,/*排序*/
			'status'	=> 1,/*状态*/
			'addtime' 	=> time(),
			'edittime' 	=> time()
		);
		if(!$site_id || !$temp_id){
			$result['status'] = 300;
			$result['msg'] = '站点或模板参数缺失..';			
		}else{
			/*创建模块*/
			$insertResult = Db::name('block')->insert($data);
			$blockLastId = Db::name('block')->getLastInsID();
			/*更新模块html*/
			if($insertResult){
				$result['status'] = 100;
				$result['msg'] = '恭喜，创建成功！';	
			}else{
				$result['status'] = 200;
				$result['msg'] = '抱歉，创建失败..';	
			}
		}
		/*返回JSON数据*/
		return json_encode($result);	
	}
	/*AJAX保存模块*/
	public function tempfitBlockSave(){
		/*接受参数*/
		$block_id = input('block_id');/*模块ID*/
		$top = input('top');/*上*/
		$left = input('left');/*左*/
		$width = input('width');/*宽*/
		$height = input('height');/*高*/
		$nowtime = time();
		/*查询模块原始样式*/
		$content = Db::name('block')->field('style')->where('id='.$block_id)->find();
		/*解析CSS样式转换成数组*/
		if (!empty($content['style'])) {
			$styleStr = trim($content['style'],';');
			$styleStr = explode(';',$styleStr);
			foreach($styleStr as $k=>$v){
				$styleValue[$k] = explode(':',$v);
			}
			foreach($styleValue as $k=>$v){
				$key = trim($v[0]);
				$value = trim($v[1]);
				$style[$key] = $value;
			}
		}
		/*重组样式字符串*/
		$styleStr = '';
		foreach($style as $k=>$v){
			if($k=='top'){
				$styleStr .= $k.':'.$top.';';
			}else if($k=='left'){
				$styleStr .= $k.':'.$left.';';
			}else if($k=='width'){
				$styleStr .= $k.':'.$width.';';
			}else if($k=='height'){
				$styleStr .= $k.':'.$height.';';
			}else{
				$styleStr .= $k.':'.$v.';';
			}
		}	
		/*组装数据*/
		$data = array(
			'style'		=> $styleStr,
			'edittime' 	=> $nowtime
		);
		$saveResult = Db::name('block')->where('id='.$block_id)->update($data);
		if($saveResult){
			$result['status'] = 100;
			$result['msg'] = '恭喜，保存成功！';	
		}else{
			$result['status'] = 200;
			$result['msg'] = '抱歉，保存失败..';	
		}
		/*返回JSON数据*/
		return json_encode($result);	
	}	
	/*模板广场 end*/
	
	/*机构管理 start*/
	/*机构列表*/
	public function grouplist() {
		/*获取参数*/
		$limit = input('limit');
		$search = input('search');
		$this->assign('limit', $limit);
		$this->assign('search', $search);
		$pagelimit = !empty($limit) ? $limit : config('paginate.list_rows');/*每页条数*/
		if (!empty($search)) {
			$where_group_list['concat(name,description)'] = array('like', '%' . $search . '%');/*搜索*/
		}
		/*查询机构*/
		$where_group_list['site_id'] = $this->site_id;
		$where_group_list['type'] = 2; /*类型：站点管理员*/
		/*分页参数*/
		$paginate = array('query' => array( /*url额外参数*/
		'limit' => $limit, /*每页条数*/
		'search' => $search, /*搜索*/
		));
		$list = Db::name('group')->where($where_group_list)->paginate($pagelimit, false, $paginate);
		$lists = array();
		foreach ($list as $k => $v) {
			$lists[$k] = $v;
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
		$this->assign('title', '机构列表'); /*页面标题*/
		$this->assign('keywords', '机构列表'); /*页面关键词*/
		$this->assign('description', '机构列表'); /*页面描述*/
		/*调用模板*/
		return $this->fetch(TEMP_FETCH);
	}
	/*编辑节点*/
	public function groupedit() {
		/*查询站点*/
		$site_all = Db::name('site')->field('id,name')->where('status=1')->select(); /*查询所有站点*/
		$this->assign('site_all', $site_all); /*内容*/
		/*获取参数*/
		$group_id = input('id'); /*机构ID*/
		if ($group_id) {
			/*编辑*/
			/*查询参数*/
			$where_group['id'] = $group_id;
			$group = Db::name('group')->where($where_group)->find();
			foreach ($group as $k => $v) {
				/*解析图标、图片*/
				if ($k == 'ico' && !empty($group['ico'])) {
					$ico = explode(',', $v);
					$group['ico'] = array();
					foreach ($ico as $k2 => $v2) {						$basename = explode('/',$v2);
						$basename = array_pop($basename);
						$group['ico'][$k2] = array('filename' => $v2, 'basename' => $basename);
					}
				}
				if ($k == 'picture' && !empty($group['picture'])) {
					$picture = explode(',', $v);
					$group['picture'] = array();
					foreach ($picture as $k2 => $v2) {						$basename = explode('/',$v2);
						$basename = array_pop($basename);
						$group['picture'][$k2] = array('filename' => $v2, 'basename' => $basename);
					}
				}
				/*解析权限集*/
				$group['tree'] = trim($group['tree'], '[');
				$group['tree'] = trim($group['tree'], ']');
			}
			$this->assign('content', $group); /*内容*/
			/*组装post提交变量*/
			$submit = array('name' => '确认编辑', 'url' => '?id=' . $group_id);
			$this->assign('submit', $submit);
			/*分配变量*/
			$this->assign('title', '编辑机构'); /*页面标题*/
			$this->assign('keywords', '编辑机构'); /*页面关键词*/
			$this->assign('description', '编辑机构'); /*页面描述*/
		} else {
			/*添加*/
			/*组装post提交变量*/
			$submit = array('name' => '确认添加', 'url' => '?a=add');
			$this->assign('submit', $submit);
			/*分配变量*/
			$this->assign('title', '添加机构'); /*页面标题*/
			$this->assign('keywords', '添加机构'); /*页面关键词*/
			$this->assign('description', '添加机构'); /*页面描述*/
		}
		/*查询当前用户机构可分配的权限*/
		$group_type = !empty($group['type']) ? $group['type'] : 2;
		if ($group_type == 3) {
			/*系统管理员*/
			$where_tree_list['path'] = array('like', '0,%');
		} else {
			/*网站管理员*/
			$site = Db::name('site')->field('server_id')->where('id=' . $this->site_id)->find(); /*查询站点*/
			$where_tree_list['path'] = array('like', '%,' . $site['server_id'] . ',%');
		}
		/*过滤子级非菜单项*/
		$tree_type = Db::name('tree')->field('id')->where('type=1')->select();
		$tree_type1_ids = '';
		foreach ($tree_type as $k => $v) {
			$tree_type1_ids.= $v['id'] . ',';
			if ($group_type == 3) {
				/*系统管理员过滤非菜单项子集*/
				$tree = Db::name('tree')->select();
				foreach ($tree as $k2 => $v2) {
					if (strstr($v2['path'], ',' . $v['id'] . ',')) {
						$tree_type1_ids.= $v2['id'] . ',';
					}
				}
			}
		}
		$tree_type1_ids = trim($tree_type1_ids, ',');
		$where_tree_list['id'] = array('notin', $tree_type1_ids);
		$group_tree = Db::name('tree')->field('id,name,pid,path')->where($where_tree_list)->select();
		/*编辑时查出已有权限*/
		foreach ($group_tree as $k => $v) {
			if ($group_id) {
				if (!empty($group['tree'])) {
					$group_tree_old = explode(',', $group['tree']);
					foreach ($group_tree_old as $k2 => $v2) {
						if ($v2 == $v['id']) {
							$group_tree[$k]['on'] = 100;
						}
					}
				}
			}
		}
		/*重组权限层级*/
		$group_tree_arr = array();
		foreach ($group_tree as $k => $v) {
			if ($group_type == 3) {
				/*系统管理员*/
				/*重组*/
				if ($v['pid'] == 0) {
					/*顶级*/
					$group_tree_arr[$v['id']] = $v;
				} else {
					/*子级*/
					$group_tree_arr[$v['pid']]['tree_sub'][$v['id']] = $v;
				}
			} else {
				/*网站管理员*/
				if ($v['pid'] == $site['server_id']) {
					/*顶级*/
					$group_tree_arr[$v['id']] = $v;
				} else {
					/*子级*/
					$group_tree_arr[$v['pid']]['tree_sub'][$v['id']] = $v;
				}
			}
		}
		/*分配权限变量*/
		$this->assign('tree', $group_tree_arr);
		/*提交*/
		if ($_POST) {
			/*获取参数*/
			$nowtime = time();
			/*组装权限集*/
			$treejson = explode(',', input('tree'));
			$treejson_str = '[';
			foreach ($treejson as $k => $v) {
				if (!empty($v)) {
					$treejson_str.= $v . ',';
				}
			}
			$treejson_str = trim($treejson_str, ',');
			$treejson_str.= ']';
			/*验证参数*/
			if (empty(input('name'))) {
				echo '<script>$(document).ready(function(){alertBox("名称不可为空..","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
			} else {
				/*组装参数*/
				$data = array('site_id' => $this->site_id, 'pid' => 0, /*父结点ID*/
				'path' => '0,', /*路径（如：0,1,2,）*/
				'name' => input('name'), /*名称*/
				'ico' => input('ico'), /*图标*/
				'picture' => input('picture'), /*缩略图*/
				'description' => input('description'), /*简介*/
				'content' => input('content'), /*内容*/
				'tree' => $treejson_str, /*权限集*/
				'type' => 2, /*类型 站点管理*/
				'order' => input('order'), /*排序  默认为100*/
				'status' => input('status'), /*状态  默认为1:启用|0:禁用*/
				'edittime' => $nowtime, /*修改时间*/
				);
				if ($group_id) {
					/*编辑*/
					/*更新数据*/
					$result = Db::name('group')->where('id=' . $group_id)->update($data);
					$alert_success = '恭喜，编辑成功！';
					$alert_error = '抱歉，编辑失败！';
				} else {
					/*添加*/
					$data['addtime'] = $nowtime; /*添加时间*/
					/*插入数据*/
					$result = Db::name('group')->insert($data);
					$alert_success = '恭喜，添加成功！';
					$alert_error = '抱歉，添加失败！';
				}
				/*判断结果集*/
				if ($result) {
					echo '<script>$(document).ready(function(){alertBox("' . $alert_success . '","' . url('medical.cms/grouplist') . '");})</script>';
				} else {
					echo '<script>$(document).ready(function(){alertBox("' . $alert_error . '","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
				}
			}
		} else {
			/*调用模板*/
			return $this->fetch(TEMP_FETCH);
		}
	}
	/*删除节点*/
	public function groupdel() {
		/*接收参数*/
		$group_id = input('id'); /*机构ID*/
		/*判断是否存在管理员*/
		$group_user = Db::name('user')->where('group_ids=' . $group_id)->find();
		if ($group_user) {
			/*存在子类*/
			echo '<script>$(document).ready(function(){alertBox("抱歉，存在管理员，不允许删除！","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
		} else {
			/*不存在子类*/
			$result = Db::name('group')->where('id=' . $group_id)->delete();
			if ($result) {
				echo '<script>$(document).ready(function(){alertBox("恭喜，删除成功！","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
			} else {
				echo '<script>$(document).ready(function(){alertBox("抱歉，删除失败！","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
			}
		}
	}
	/*机构管理管理 end*/
	
	/*员工管理 start*/
	/*员工列表*/
	public function adminlist() {
		/*获取参数*/
		$limit = input('limit');
		$search = input('search');
		$this->assign('limit', $limit);
		$this->assign('search', $search);
		$pagelimit = !empty($limit) ? $limit : config('paginate.list_rows'); /*每页条数*/
		if (!empty($search)) {
			$where_user_list['concat(name,nickname)'] = array('like', '%' . $search . '%'); /*搜索*/
		}
		/*查询员工*/
		$where_group['site_id'] = $this->site_id;
		$where_group['type'] = 2; /*类型：站点员工*/
		$group = Db::name('group')->where($where_group)->select();
		$group_ids = '';
		foreach ($group as $k => $v) {
			$group_ids.= $v['id'] . ',';
		}
		$group_ids = trim($group_ids, ',');
		/*查询员工*/
		$where_user_list['site_ids'] = $this->site_id; /*管理组：站点员工*/
		$where_user_list['group_ids'] = array('in',$group_ids);
		/*分页参数*/
		$paginate = array('query' => array( /*url额外参数*/
			'limit' => $limit, /*每页条数*/
			'search' => $search, /*搜索*/
		));
		$list = Db::name('user')->where($where_user_list)->where(function ($query) {
			$query->where('site_ids', $this->site_id)->whereor('site_ids', 'like', $this->site_id . ',%');
		})->whereOr(function ($query) {
			$query->where('site_ids', 'like', '%,' . $this->site_id . ',%')->whereOr('site_ids', 'like', '%,' . $this->site_id);
		})->paginate($pagelimit, false, $paginate);
		$lists = array();
		foreach ($list as $k => $v) {
			$lists[$k] = $v;
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
		$this->assign('title', '员工列表'); /*页面标题*/
		$this->assign('keywords', '员工列表'); /*页面关键词*/
		$this->assign('description', '员工列表'); /*页面描述*/
		/*调用模板*/
		return $this->fetch(TEMP_FETCH);
	}
	/*编辑节点*/
	public function adminedit() {
		/*查询系统员工组*/
		$group_where['site_id'] = $this->site_id;
		$group_where['type'] = 2;
		$group_where['status'] = 1;
		$group = Db::name('group')->field('id,name')->where($group_where)->select();
		$this->assign('group', $group);
		/*查询行业*/
		$industry = Db::name('industry')->field('id,name')->where('status=1')->select();
		$this->assign('industry', $industry);
		/*查询州*
		$state = Db::name('areacode')->field('id,name')->where('pid=0 AND status=1')->select();
		$this->assign('state', $state);
		*/
		/*查询省**/	
		$province = Db::name('areacode')->field('id,name')->where('pid=7 AND status=1')->select();
		$this->assign('province', $province);
		/*获取参数*/
		$user_id = input('id'); /*员工ID*/
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
				/*解析身份证号*/
				if ($k == 'identity' && !empty($user['identity'])) {
					$identity = json_decode($user['identity']);
					$user['identity'] = $identity->name;
				}
				/*解析手机号*/
				if ($k == 'phone' && !empty($user['phone'])) {
					$phone = json_decode($user['phone']);
					$user['phone'] = $phone->name;
				}
				/*解析邮箱地址*/
				if ($k == 'mail' && !empty($user['mail'])) {
					$mail = json_decode($user['mail']);
					$user['mail'] = $mail->name;
				}
				/*解析qq号码*/
				if ($k == 'qq' && !empty($user['qq'])) {
					$qq = json_decode($user['qq']);
					$user['qq'] = $qq->name;
				}
				/*解析微信号*/
				if ($k == 'wechat' && !empty($user['wechat'])) {
					$wechat = json_decode($user['wechat']);
					$user['wechat'] = $wechat->name;
				}
				/*查询国
				if ($k == 'state' && !empty($user['state'])) {
					$country = Db::name('areacode')->field('id,name')->where('pid=' . $user['state'] . ' AND status=1')->select();
					$this->assign('country', $country);
				}
				/*查询省*
				if ($k == 'country' && !empty($user['country'])) {
					$province = Db::name('areacode')->field('id,name')->where('pid=' . $user['country'] . ' AND status=1')->select();
					$this->assign('province', $province);
				}
				*/
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
			$this->assign('title', '编辑员工'); /*页面标题*/
			$this->assign('keywords', '编辑员工'); /*页面关键词*/
			$this->assign('description', '编辑员工'); /*页面描述*/
		} else {
			/*添加*/
			/*组装post提交变量*/
			$submit = array('name' => '确认添加', 'url' => '?a=add');
			$this->assign('submit', $submit);
			/*分配变量*/
			$this->assign('title', '添加员工'); /*页面标题*/
			$this->assign('keywords', '添加员工'); /*页面关键词*/
			$this->assign('description', '添加员工'); /*页面描述*/
		}
		/*提交*/
		if ($_POST) {
			/*获取参数*/
			$nowtime = time();
			$password = input('password');
			/*验证参数*/
			if (empty(input('name'))) {
				echo '<script>$(document).ready(function(){alertBox("名称不可为空..","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
			} else if (input('password') != input('password2')) {
				echo '<script>$(document).ready(function(){alertBox("两次密码不一致..","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
			} else {
				/*组装参数*/
				$data = array(
					'name' => input('name'), /*名称*/
					'nickname' => input('nickname'), /*昵称*/
					'picture' => input('picture'), /*头像*/
					'sex' => input('sex'), /*性别 默认女:0|男为1 */
					'industry' => input('industry'), /*行业*/
					'year' => input('year'), /*出生年*/
					'month' => input('month'), /*出生月*/
					'day' => input('day'), /*出生日*/
					'state' => input('state'), /*州*/
					'country' => input('country'), /*国*/
					'province' => input('province'), /*省*/
					'city' => input('city'), /*市*/
					'area' => input('area'), /*区*/
					'address' => input('address'), /*地址*/
					'order' => input('order'), /*排序  默认为100*/
					'status' => input('status'), /*状态  默认为1:启用|0:禁用*/
					'edittime' => $nowtime, /*修改时间*/
				);
				if ($user_id) {
					$data['group_ids'] = input('group_id'); /*所属管理组*/
					/*编辑*/
					/*更新密码*/
					if (!empty($password)) {
						$reg_time = $user['addtime'];
						$password = md5(md5($reg_time . $password));
						$data['password'] = $password;
					}
					/*更新数据*/
					$result = Db::name('user')->where('id=' . $user_id)->update($data);
					$alert_success = '恭喜，编辑成功！';
					$alert_error = '抱歉，编辑失败！';
				} else {
					$data['group_ids'] = input('group_id'); /*所属管理组*/
					$data['site_ids'] = $this->site_id; /*用户管理站点*/
					/*更新密码*/
					if (!empty($password)) {
						$reg_time = $nowtime;
						$password = md5(md5($reg_time . $password));
						$data['password'] = $password;
					}
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
					/*添加*/
					$data['money'] = 0; /*余额*/
					$data['integral'] = 0; /*积分*/
					$data['growth'] = 0; /*成长值*/
					$data['identity'] = $identity_json; /*身份证号码JONS集（号码,认证状态1已认证0未认证,变更时间）*/
					$data['phone'] = $phone_json; /*手机JONS集（号码,认证状态1已认证0未认证,变更时间）*/
					$data['mail'] = $mail_json; /*邮箱JONS集（号码,认证状态1已认证0未认证,变更时间）*/
					$data['qq'] = $qq_json; /*QQ-JONS集（号码,关联状态1已关联0未关联,变更时间）*/
					$data['wechat'] = $wechat_json; /*微信-JONS集（号码,关联状态1已关联0未关联,变更时间）*/
					$data['addtime'] = $nowtime; /*添加时间*/
					//dump($data);die();
					/*插入数据*/
					$result = Db::name('user')->insert($data);
					$alert_success = '恭喜，添加成功！';
					$alert_error = '抱歉，添加失败！';
				}
				/*判断结果集*/
				if ($result) {
					echo '<script>$(document).ready(function(){alertBox("' . $alert_success . '","' . url('medical.cms/adminlist') . '");})</script>';
				} else {
					echo '<script>$(document).ready(function(){alertBox("' . $alert_error . '","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
				}
			}
		} else {
			/*调用模板*/
			return $this->fetch(TEMP_FETCH);
		}
	}
	/*删除员工*/
	public function admindel() {
		/*接收参数*/
		$user_id = input('id'); /*管理组ID*/
		$result = Db::name('user')->where('id=' . $user_id)->delete();
		if ($result) {
			echo '<script>$(document).ready(function(){alertBox("恭喜，删除成功！","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
		} else {
			echo '<script>$(document).ready(function(){alertBox("抱歉，删除失败！","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
		}
	}
	/*员工管理 end*/
	
	/*会员管理 start*/
	/*会员列表*/
	public function userlist() {
		/*获取参数*/
		$limit = input('limit');
		$search = input('search');
		$this->assign('limit', $limit);
		$this->assign('search', $search);
		$pagelimit = !empty($limit) ? $limit : config('paginate.list_rows'); /*每页条数*/
		if (!empty($search)) {
			$where_user_list['concat(name,nickname)'] = array('like', '%' . $search . '%'); /*搜索*/
		}
		/*查询机构*/
		$where_group['site_id'] = $this->site_id;
		$where_group['type'] = 1; /*类型：会员或网站管理员*/
		$group = Db::name('group')->where($where_group)->select();
		$group_ids = '';
		foreach ($group as $k => $v) {
			$group_ids.= $v['id'] . ',';
		}
		$group_ids = trim($group_ids, ',');
		/*查询管理员*/
		$where_user_list['group_ids'] = array('in', $group_ids);
		/*分页参数*/
		$paginate = array('query' => array( /*url额外参数*/
			'limit' => $limit, /*每页条数*/
			'search' => $search, /*搜索*/
		));
		$list = Db::name('user')->where($where_user_list)->paginate($pagelimit, false, $paginate);
		$lists = array();
		foreach ($list as $k => $v) {
			$lists[$k] = $v;
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
		$this->assign('title', '会员列表'); /*页面标题*/
		$this->assign('keywords', '会员列表'); /*页面关键词*/
		$this->assign('description', '会员列表'); /*页面描述*/
		/*调用模板*/
		return $this->fetch(TEMP_FETCH);
	}
	/*编辑会员*/
	public function useredit() {
		/*查询会员管理员组*/
		$where_group['site_id'] = $this->site_id;
		$where_group['type'] = 1;
		$where_group['status'] = 1;
		$group = Db::name('group')->field('id,name')->where($where_group)->select();
		$this->assign('group', $group);
		/*查询行业*/
		$industry = Db::name('industry')->field('id,name')->where('status=1')->select();
		$this->assign('industry', $industry);
		/*查询州*/
		$state = Db::name('areacode')->field('id,name')->where('pid=0 AND status=1')->select();
		$this->assign('state', $state);
		/*获取参数*/
		$user_id = input('id'); /*管理员ID*/
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
				/*解析身份证号*/
				if ($k == 'identity' && !empty($user['identity'])) {
					$identity = json_decode($user['identity']);
					$user['identity'] = $identity->name;
				}
				/*解析手机号*/
				if ($k == 'phone' && !empty($user['phone'])) {
					$phone = json_decode($user['phone']);
					$user['phone'] = $phone->name;
				}
				/*解析邮箱地址*/
				if ($k == 'mail' && !empty($user['mail'])) {
					$mail = json_decode($user['mail']);
					$user['mail'] = $mail->name;
				}
				/*解析qq号码*/
				if ($k == 'qq' && !empty($user['qq'])) {
					$qq = json_decode($user['qq']);
					$user['qq'] = $qq->name;
				}
				/*解析微信号*/
				if ($k == 'wechat' && !empty($user['wechat'])) {
					$wechat = json_decode($user['wechat']);
					$user['wechat'] = $wechat->name;
				}
				/*查询国*/
				if ($k == 'state' && !empty($user['state'])) {
					$country = Db::name('areacode')->field('id,name')->where('pid=' . $user['state'] . ' AND status=1')->select();
					$this->assign('country', $country);
				}
				/*查询省*/
				if ($k == 'country' && !empty($user['country'])) {
					$province = Db::name('areacode')->field('id,name')->where('pid=' . $user['country'] . ' AND status=1')->select();
					$this->assign('province', $province);
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
			$this->assign('title', '编辑会员'); /*页面标题*/
			$this->assign('keywords', '编辑会员'); /*页面关键词*/
			$this->assign('description', '编辑会员'); /*页面描述*/
		} else {
			/*添加*/
			/*组装post提交变量*/
			$submit = array('name' => '确认添加', 'url' => '?a=add');
			$this->assign('submit', $submit);
			/*分配变量*/
			$this->assign('title', '添加会员'); /*页面标题*/
			$this->assign('keywords', '添加会员'); /*页面关键词*/
			$this->assign('description', '添加会员'); /*页面描述*/
		}
		/*提交*/
		if ($_POST) {
			/*获取参数*/
			$nowtime = time();
			$password = input('password');
			/*验证参数*/
			if (empty(input('name'))) {
				echo '<script>$(document).ready(function(){alertBox("名称不可为空..","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
			} else if (input('password') != input('password2')) {
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
				$data = array('group_ids' => input('group_id'), /*所属管理组*/
				'site_ids' => input('site_id'), /*会员所属网站*/
				'name' => input('name'), /*名称*/
				'nickname' => input('nickname'), /*昵称*/
				'picture' => input('picture'), /*头像*/
				'sex' => input('sex'), /*性别 默认女:0|男为1 */
				'industry' => input('industry'), /*行业*/
				'year' => input('year'), /*出生年*/
				'month' => input('month'), /*出生月*/
				'day' => input('day'), /*出生日*/
				'identity' => $identity_json, /*身份证号码JONS集（号码,认证状态1已认证0未认证,变更时间）*/
				'phone' => $phone_json, /*手机JONS集（号码,认证状态1已认证0未认证,变更时间）*/
				'mail' => $mail_json, /*邮箱JONS集（号码,认证状态1已认证0未认证,变更时间）*/
				'qq' => $qq_json, /*QQ-JONS集（号码,关联状态1已关联0未关联,变更时间）*/
				'wechat' => $wechat_json, /*微信-JONS集（号码,关联状态1已关联0未关联,变更时间）*/
				'state' => input('state'), /*州*/
				'country' => input('country'), /*国*/
				'province' => input('province'), /*省*/
				'city' => input('city'), /*市*/
				'area' => input('area'), /*区*/
				'address' => input('address'), /*地址*/
				'order' => input('order'), /*排序  默认为100*/
				'status' => input('status'), /*状态  默认为1:启用|0:禁用*/
				'edittime' => $nowtime, /*修改时间*/
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
					$result = Db::name('user')->where('id=' . $user_id)->update($data);
					$alert_success = '恭喜，编辑成功！';
					$alert_error = '抱歉，编辑失败！';
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
				}
				/*判断结果集*/
				if ($result) {
					echo '<script>$(document).ready(function(){alertBox("' . $alert_success . '","' . url('medical.cms/userlist') . '");})</script>';
				} else {
					echo '<script>$(document).ready(function(){alertBox("' . $alert_error . '","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
				}
			}
		} else {
			/*调用模板*/
			return $this->fetch(TEMP_FETCH);
		}
	}
	/*删除会员*/
	public function userdel() {
		/*接收参数*/
		$user_id = input('id'); /*会员ID*/
		$result = Db::name('user')->where('id=' . $user_id)->delete();
		if ($result) {
			echo '<script>$(document).ready(function(){alertBox("恭喜，删除成功！","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
		} else {
			echo '<script>$(document).ready(function(){alertBox("抱歉，删除失败！","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
		}
	}
	/*会员管理 end*/
	
	/*订单管理 start*/
	/*订单列表*/
	public function orderlist() {
		/*查询订单*/
		/*获取参数*/
		$limit = input('limit');
		$search = input('search');
		$this->assign('limit', $limit);
		$this->assign('search', $search);
		$pagelimit = !empty($limit) ? $limit : config('paginate.list_rows'); /*每页条数*/
		if (!empty($search)) {
			$where_list['concat(no,content)'] = array('like', '%' . $search . '%'); /*搜索*/
		}
		/*分页参数*/
		$paginate = array('query' => array( /*url额外参数*/
		'limit' => $limit, /*每页条数*/
		'search' => $search, /*搜索*/
		));
		/*条件*/
		$where_list['site_id'] = $this->site_id;
		$order = array('order' => 'desc', 'id' => 'desc');
		$list = Db::name('order')->where($where_list)->order($order)->paginate($pagelimit, false, $paginate);
		$lists = array();
		foreach ($list as $k => $v) {
			$lists[$k] = $v;		
			/*状态*/
			$status = json_decode($v['status'],true);
			$lists[$k]['status'] = array_pop($status);
		}
		$page = $list->render(); /*获取分页显示*/
		$this->assign('list', $lists);
		$this->assign('page', $page);
		/*分配变量*/
		$this->assign('title', '订单列表'); /*页面标题*/
		$this->assign('keywords', '订单列表'); /*页面关键词*/
		$this->assign('description', '订单列表'); /*页面描述*/
		/*调用模板*/
		return $this->fetch(TEMP_FETCH);
	}
	/*编辑订单*/
	public function orderedit() {
		/*运费模板*/
		$where_sendtemp['site_id'] = $this->site_id;
		$where_sendtemp['status'] = 1;
		$sendtemp = Db::name('sendtemp')->field('id,name')->where($where_sendtemp)->order('`order` desc,`id` desc')->select();
		$this->assign('sendtemp', $sendtemp);
		/*获取参数*/
		$id = input('id'); /*订单Id*/
		/*编辑*/
		/*查询参数*/
		$where['id'] = $id;
		$contents = Db::name('order')->where($where)->find();
		foreach ($contents as $k => $v) {
			$content[$k] = $v;
		}
		$this->assign('content', $content); /*订单*/
		/*组装post提交变量*/
		$submit = array('name' => '确认编辑', 'url' => '?id=' . $id);
		$this->assign('submit', $submit);
		/*分配变量*/
		$this->assign('title', '编辑订单'); /*页面标题*/
		$this->assign('keywords', '编辑订单'); /*页面关键词*/
		$this->assign('description', '编辑订单'); /*页面描述*/
		/*提交*/
		if ($_POST) {
			/*获取参数*/
			$nowtime = time();
			/*验证参数*/
			if (empty(input('order'))) {
				echo '<script>$(document).ready(function(){alertBox("排序不可为空..","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
			} else {
				/*组装参数*/
				$data = array('order' => input('order'), /*排序  默认为100*/
				'edittime' => $nowtime, /*修改时间*/
				);
				/*编辑*/
				/*更新数据*/
				$result = Db::name('order')->where('id=' . $id)->update($data);
				$alert_success = '恭喜，编辑成功！';
				$alert_error = '抱歉，编辑失败！';
				/*判断结果集*/
				if ($result) {
					echo '<script>$(document).ready(function(){alertBox("' . $alert_success . '","' . url('medical.cms/orderlist') . '");})</script>';
				} else {
					echo '<script>$(document).ready(function(){alertBox("' . $alert_error . '","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
				}
			}
		} else {
			/*调用模板*/
			return $this->fetch(TEMP_FETCH);
		}
	}
	/*删除订单*/
	public function orderdel() {
		/*接收参数*/
		$id = input('id'); /*订单ID*/
		$where['id'] = $id;
		$order = Db::name('order')->field('status')->where($where)->find();
		$orderStatus = json_decode($order['status'],true);
		/*不存在子类*/
		$statusNow = array(
			'code' => 13,/*订单状态码 13:已删除*/
			'time' => time(),/*变更时间*/
			'msg'  => '已删除'/*变更时间*/			
		);
		array_push($orderStatus,$statusNow);/*将最新状态插入数组末尾*/
		$data['status'] = json_encode($orderStatus);
		$result = Db::name('order')->where('id='.$id)->update($data);
		if ($result) {
			echo '<script>$(document).ready(function(){alertBox("恭喜，删除成功！","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
		} else {
			echo '<script>$(document).ready(function(){alertBox("抱歉，删除失败！","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
		}
	}
	/*订单管理 end*/
	
	/*评论管理 start*/
	/*评论列表*/
	public function commentlist() {
		/*查询评论*/
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
		'limit' => $limit, /*每页条数*/
		'search' => $search, /*搜索*/
		));
		/*条件*/
		$where_list['site_id'] = $this->site_id;
		$where_list['status'] = array('neq', 9);
		$order = array('order' => 'desc', 'id' => 'desc');
		$list = Db::name('comment')->where($where_list)->order($order)->paginate($pagelimit, false, $paginate);
		$lists = array();
		foreach ($list as $k => $v) {
			$lists[$k] = $v;
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
		$this->assign('title', '评论列表'); /*页面标题*/
		$this->assign('keywords', '评论列表'); /*页面关键词*/
		$this->assign('description', '评论列表'); /*页面描述*/
		/*调用模板*/
		return $this->fetch(TEMP_FETCH);
	}
	/*编辑评论*/
	public function commentedit() {
		/*运费模板*/
		$where_sendtemp['site_id'] = $this->site_id;
		$where_sendtemp['status'] = 1;
		$sendtemp = Db::name('sendtemp')->field('id,name')->where($where_sendtemp)->order('`order` desc,`id` desc')->select();
		$this->assign('sendtemp', $sendtemp);
		/*获取参数*/
		$id = input('id'); /*评论Id*/
		/*编辑*/
		/*查询参数*/
		$where['id'] = $id;
		$contents = Db::name('comment')->where($where)->find();
		foreach ($contents as $k => $v) {
			$content[$k] = $v;
		}
		$this->assign('content', $content); /*评论*/
		/*组装post提交变量*/
		$submit = array('name' => '确认编辑', 'url' => '?id=' . $id);
		$this->assign('submit', $submit);
		/*分配变量*/
		$this->assign('title', '编辑评论'); /*页面标题*/
		$this->assign('keywords', '编辑评论'); /*页面关键词*/
		$this->assign('description', '编辑评论'); /*页面描述*/
		/*提交*/
		if ($_POST) {
			/*获取参数*/
			$nowtime = time();
			/*验证参数*/
			if (empty(input('order'))) {
				echo '<script>$(document).ready(function(){alertBox("排序不可为空..","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
			} else {
				/*组装参数*/
				$data = array('pid' => 0, /*父结点ID*/
				'path' => '0,', /*父结点ID*/
				'name' => input('name'), /*名称*/
				'ico' => input('ico'), /*图标*/
				'picture' => input('picture'), /*缩略图*/
				'url' => input('url'), /*链接*/
				'description' => input('description'), /*简介*/
				'content' => input('content'), /*内容*/
				'reply' => input('reply'), /*回复*/
				'order' => input('order'), /*排序  默认为100*/
				'status' => input('status'), /*状态  默认为1:启用|0:禁用*/
				'edittime' => $nowtime, /*修改时间*/
				);
				/*编辑*/
				/*更新数据*/
				$result = Db::name('comment')->where('id=' . $id)->update($data);
				$alert_success = '恭喜，编辑成功！';
				$alert_error = '抱歉，编辑失败！';
				/*判断结果集*/
				if ($result) {
					echo '<script>$(document).ready(function(){alertBox("' . $alert_success . '","' . url('medical.cms/commentlist') . '");})</script>';
				} else {
					echo '<script>$(document).ready(function(){alertBox("' . $alert_error . '","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
				}
			}
		} else {
			/*调用模板*/
			return $this->fetch(TEMP_FETCH);
		}
	}
	/*删除评论*/
	public function commentdel() {
		/*接收参数*/
		$id = input('id'); /*评论ID*/
		$result = Db::name('comment')->where('id=' . $id)->delete();
		if ($result) {
			echo '<script>$(document).ready(function(){alertBox("恭喜，删除成功！","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
		} else {
			echo '<script>$(document).ready(function(){alertBox("抱歉，删除失败！","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
		}
	}
	/*评论管理 end*/
	
	/*销量统计 start*/
	public function sales() {
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
		/*分页参数*/
		$paginate = array('query' => array( /*url额外参数*/
		'limit' => $limit, /*每页条数*/
		'search' => $search, /*搜索*/
		));
		/*条件*/
		$where_list['site_id'] = $this->site_id;
		$order = array('sales' => 'desc');
		$list = Db::name('content')->where($where_list)->order($order)->paginate($pagelimit, false, $paginate);
		$lists = array();
		foreach ($list as $k => $v) {
			$lists[$k] = $v;
		}
		$page = $list->render(); /*获取分页显示*/
		$this->assign('list', $lists);
		$this->assign('page', $page);
		/*分配变量*/
		$this->assign('title', '销量统计'); /*页面标题*/
		$this->assign('keywords', '销量统计'); /*页面关键词*/
		$this->assign('description', '销量统计'); /*页面描述*/
		/*调用模板*/
		return $this->fetch(TEMP_FETCH);
	}
	/*销量统计 end*/
	
	/*销量统计 start*/
	public function visitor() {
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
		/*分页参数*/
		$paginate = array('query' => array( /*url额外参数*/
		'limit' => $limit, /*每页条数*/
		'search' => $search, /*搜索*/
		));
		/*条件*/
		$where_list['site_id'] = $this->site_id;
		$order = array('visitor' => 'desc');
		$list = Db::name('content')->where($where_list)->order($order)->paginate($pagelimit, false, $paginate);
		$lists = array();
		foreach ($list as $k => $v) {
			$lists[$k] = $v;
		}
		$page = $list->render(); /*获取分页显示*/
		$this->assign('list', $lists);
		$this->assign('page', $page);
		/*分配变量*/
		$this->assign('title', '访问量统计'); /*页面标题*/
		$this->assign('keywords', '访问量统计'); /*页面关键词*/
		$this->assign('description', '访问量统计'); /*页面描述*/
		/*调用模板*/
		return $this->fetch(TEMP_FETCH);
	}
	/*销量统计 end*/
	
	/*SEO优化管理 start*/
	public function seo() {
		/*获取参数*/
		$site_id = $this->site_id; /*节点Id*/
		if ($site_id) {
			/*编辑*/
			/*查询参数*/
			$where_site['id'] = $site_id;
			$site = Db::name('site')->where($where_site)->find();
			/*分配变量*/
			$this->assign('content', $site); /*内容*/
			/*组装post提交变量*/
			$submit = array('name' => '确认编辑', 'url' => '?id=' . $site_id);
			$this->assign('submit', $submit);
		}
		if ($_POST) {
			/*获取参数*/
			$nowtime = time();
			/*验证参数*/
			if (empty(input('keywords'))) {
				echo '<script>$(document).ready(function(){alertBox("关键词不可为空..","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
			} else {
				/*组装参数*/
				$data = array(
					'title' => input('title'), /*标题*/
					'keywords' => input('keywords'), /*关键词*/
					'description' => input('description'), /*简介*/
					'author' => input('author'), /*所有者*/
					'edittime' => $nowtime, /*修改时间*/
				);
				/*编辑*/
				/*更新数据*/
				$result = Db::name('site')->where('id=' . $site_id)->update($data);
				$alert_success = '恭喜，编辑成功！';
				$alert_error = '抱歉，编辑失败！';
				/*判断结果集*/
				if ($result) {
					echo '<script>$(document).ready(function(){alertBox("' . $alert_success . '","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
				} else {
					echo '<script>$(document).ready(function(){alertBox("' . $alert_error . '","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
				}
			}
		}
		/*分配变量*/
		$this->assign('title', 'SEO优化'); /*页面标题*/
		$this->assign('keywords', 'SEO优化'); /*页面关键词*/
		$this->assign('description', 'SEO优化'); /*页面描述*/
		/*调用模板*/
		return $this->fetch(TEMP_FETCH);
	}
	/*SEO优化管理 end*/
	
	/*域名设置管理 start*/
	public function domain() {
		/*获取参数*/
		$site_id = $this->site_id; /*节点Id*/
		if ($site_id) {
			/*编辑*/
			/*查询参数*/
			$where_site['id'] = $site_id;
			$site = Db::name('site')->where($where_site)->find();
			/*分配变量*/
			$this->assign('content', $site); /*内容*/
			/*组装post提交变量*/
			$submit = array('name' => '确认编辑', 'url' => '?id=' . $site_id);
			$this->assign('submit', $submit);
		}
		if ($_POST) {
			/*获取参数*/
			$nowtime = time();
			/*组装参数*/
			$data = array('domain_two' => input('domain_two'), /*二级域名*/
			'domain_top' => input('domain_top'), /*顶级域名*/
			'edittime' => $nowtime, /*修改时间*/
			);
			/*编辑*/
			/*更新数据*/
			$result = Db::name('site')->where('id=' . $site_id)->update($data);
			$alert_success = '恭喜，编辑成功！';
			$alert_error = '抱歉，编辑失败！';
			/*判断结果集*/
			if ($result) {
				echo '<script>$(document).ready(function(){alertBox("' . $alert_success . '","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
			} else {
				echo '<script>$(document).ready(function(){alertBox("' . $alert_error . '","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
			}
		}
		/*分配变量*/
		$this->assign('title', '域名设置'); /*页面标题*/
		$this->assign('keywords', '域名设置'); /*页面关键词*/
		$this->assign('description', '域名设置'); /*页面描述*/
		/*调用模板*/
		return $this->fetch(TEMP_FETCH);
	}
	/*域名设置管理 end*/
	

	
	/*七牛存储管理 start*/
	public function qiniu(){
		/*获取参数*/
		$site_id = $this->site_id; /*节点Id*/
		if ($site_id) {
			/*编辑*/
			/*查询参数*/
			$where_site['id'] = $site_id;
			$site = Db::name('site')->field('qiniu')->where($where_site)->find();
			$content = array();
			if(!empty($site['qiniu'])){
				$content = json_decode($site['qiniu'],true);
			}
			/*分配变量*/
			$this->assign('content',$content); /*内容*/
			/*组装post提交变量*/
			$submit = array('name' => '确认编辑', 'url' => '?id='.$site_id);
			$this->assign('submit', $submit);
		}
		/*提交*/
		if ($_POST) {
			/*获取参数*/
			$nowtime = time();
			/*验证参数*/
			if (empty(input('access'))) {
				echo '<script>$(document).ready(function(){alertBox("ACCESS_KEY不可为空..","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
			} else {
				/*组装参数*/
				$data = array(
					'area' => input('area'),/*区域*/
					'access' => input('access'),
					'secret' => input('secret'),
					'bucket' => input('bucket'),
					'url' => input('url'),
					'status' => input('status'), /*状态  默认为1:启用|0:禁用*/
				);
				/*编辑*/
				/*更新数据*/
				$data_up['qiniu'] = json_encode($data);
				$result = Db::name('site')->where('id='.$site_id)->update($data_up);
				$alert_success = '恭喜，编辑成功！';
				$alert_error = '抱歉，编辑失败！';
				/*判断结果集*/
				if ($result) {
					echo '<script>$(document).ready(function(){alertBox("'.$alert_success.'","'.$_SERVER['HTTP_REFERER'].'");})</script>';
				} else {
					echo '<script>$(document).ready(function(){alertBox("'.$alert_error.'","'.$_SERVER['HTTP_REFERER'].'");})</script>';
				}
			}
		}
		/*分配变量*/
		$this->assign('title', '七牛存储'); /*页面标题*/
		$this->assign('keywords', '七牛存储'); /*页面关键词*/
		$this->assign('description', '七牛存储'); /*页面描述*/
		/*调用模板*/
		return $this->fetch(TEMP_FETCH);
	}
	/*七牛存储管理 end*/	
	
	/*语种管理 start*/
	public function language() {
		/*获取参数*/
		$limit = input('limit');
		$search = input('search');
		$this->assign('limit', $limit);
		$this->assign('search', $search);
		$pagelimit = !empty($limit) ? $limit : config('paginate.list_rows'); /*每页条数*/
		if (!empty($search)) {
			$where_list['concat(name,description)'] = array('like', '%' . $search . '%'); /*搜索*/
		}
		/*查询*/
		$where_list['id'] = array('gt', 0);
		$where_list['status'] = 1;
		/*分页参数*/
		$paginate = array('query' => array( /*url额外参数*/
		'limit' => $limit, /*每页条数*/
		'search' => $search, /*搜索*/
		));
		$list = Db::name('language')->where($where_list)->paginate($pagelimit, false, $paginate);
		$lists = array();
		foreach ($list as $k => $v) {
			$lists[$k] = $v;
		}
		$page = $list->render(); /*获取分页显示*/
		$this->assign('list', $lists);
		$this->assign('page', $page);
		/*分配变量*/
		$this->assign('title', '语种管理'); /*页面标题*/
		$this->assign('keywords', '语种管理'); /*页面关键词*/
		$this->assign('description', '语种管理'); /*页面描述*/
		/*调用模板*/
		return $this->fetch(TEMP_FETCH);
	}
	/*语种管理 end*/
	
	/*物流跟踪接口 start*/
	public function send() {
		//物流跟踪查询
		$id = isset($_GET['id'])?$_GET['id']:0;
		if($id != 0){
			$send = Db::name('send')->field('code')->where('id='.$id)->find();
			$send_code = $send['code'];
			$send_no = $_GET['no'];
		}else{
			$send_code = 'yunda';
			$send_no = '1202247993797';
		}
		$array['send_code'] = $send_code;
		$array['send_no'] = $send_no;
		$logistics = new \logistics\logistics(); /*实例化物流跟踪类*/
		$logisticsResult = $logistics->logisticsSearch($array); /*调起跟踪*/
		//判断结果集
		dump($logisticsResult);
		//echo json_encode($logisticsResult);	
	}
	/*物流跟踪接口 end*/
	
	/*邮件设置 start*/
	public function email() {		
		/**
		 * 调用邮件发送方法:
		 *		$email = new \email\Mail();//实例化邮件类
		 *		$sendResult = $email->sendMail($data);//发送邮件
		 * 参数一: $array['re_name']	 	| 发件人、收件人回复邮件时被接受信息“邮箱名称”
		 * 参数二: $array['re_address']	| 发件人、收件人回复邮件时被接受信息“邮箱地址”
		 * 参数三: $array['address']	 	| 收件人邮箱地址
		 * 参数四: $array['titles	'] 		| 邮件标题
		 * 参数五: $array['message']	 	| 邮件内容
		 * 参数六: $array['file']		 	| 邮件附件
		 */
		$site_id = $this->site_id; /*站点Id*/
		if ($_POST) {
			/*存储发件设置*/
			$send['email_re_name'] = input('re_name');
			$send['email_re_address'] = input('re_address');
			$result = Db::name('site')->where('id=' . $site_id)->update($send);
			/*获取邮件内容*/
			$data['re_name'] = input('re_name');
			$data['re_address'] = input('re_address');
			$data['address'] = input('address');
			$data['title'] = input('title');
			$data['message'] = input('message');
			$data['file'] = SITEDIR . input('file');
			$email = new \email\Mail(); /*实例化邮件类*/
			$sendResult = $email->sendMail($data); /*发送邮件*/
			/*判断结果集*/
			if ($sendResult) {
				echo '<script>$(document).ready(function(){alertBox("恭喜！邮件发送成功！","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
			} else {
				echo '<script>$(document).ready(function(){alertBox("抱歉！邮件发送失败！","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
			}
		} else {
			/*查询发件设置*/
			$content = Db::name('site')->field('email_re_name,email_re_address')->where('id=' . $site_id)->find();
			$this->assign('content',$content);
			/*组装post提交变量*/
			$submit = array('name' => '保存并发送邮件', 'url' => '?a=edit');
			$this->assign('submit', $submit);
			/*分配变量*/
			$this->assign('title', '邮件设置'); /*页面标题*/
			$this->assign('keywords', '邮件设置'); /*页面关键词*/
			$this->assign('description', '邮件设置'); /*页面描述*/
			/*调用模板*/
			return $this->fetch(TEMP_FETCH);
		}
	}
	/*邮件设置 end*/
	
	/*短信设置 start*/
	public function msg() {
		$site_id = $this->site_id; /*站点Id*/
		if ($_POST) {
			/*获取短信信息*/
			$data['phone'] = input('phone');
			$data['content'] = input('content');
			/*引入阿里短信API*/
			include_once './vendor/alisms/aliyun-php-sdk-core/Config.php';
			include_once './vendor/alisms/Dysmsapi/Request/V20170525/SendSmsRequest.php';
			$accessKeyId = "pFgWNEdUNgG5W4jP";//阿里云短信keyId
			$accessKeySecret = "oc55qRyjJHtdtL8WNooqpQGP0F0Mmn";//阿里云短信keysecret
			//短信API产品名
			$product = "Dysmsapi";
			//短信API产品域名
			$domain = "dysmsapi.aliyuncs.com";
			//暂时不支持多Region
			$region = "cn-hangzhou";
			//初始化访问的acsCleint
			$profile = \DefaultProfile::getProfile($region, $accessKeyId, $accessKeySecret);
			\DefaultProfile::addEndpoint("cn-hangzhou", "cn-hangzhou", $product, $domain);
			$acsClient= new \DefaultAcsClient($profile);
			$request = new \Dysmsapi\Request\V20170525\SendSmsRequest;
			$request->setPhoneNumbers("15570899143");//必填-短信接收号码
			$request->setSignName("奥站");//必填-短信签名
			//必填-短信模板Code
			$request->setTemplateCode("SMS_76545064");
			//选填-假如模板中存在变量需要替换则为必填(JSON格式)
			$request->setTemplateParam("{\"regcode\":\"456654\"}");//短信签名内容:
			//选填-发送短信流水号
			//$request->setOutId("1234");
			//发起访问请求
			$resp = $acsClient->getAcsResponse($request);
			//短信发送成功返回True，失败返回false
			if ($resp && $resp->Code == 'OK') {
				/*剩余数量减1*/
				$site_result = Db::name('site')->where('id=' . $site_id)->setDec('msg', 1);
				echo '<script>$(document).ready(function(){alertBox("恭喜！短信发送成功！","' . $_SERVER['HTTP_REFERER'] . '");})</script>';	
			} else {
				echo '<script>$(document).ready(function(){alertBox("抱歉!'.$result['content'].'..","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
			}
			//$msg = new \msg\Msg(); /*实例化短信类*/
			//$result = $msg->sendMsg($data); /*发送短信*/
			/*判断结果集*
			if ($result['status'] == 1) {
				/*剩余数量减1
				$site_result = Db::name('site')->where('id=' . $site_id)->setDec('msg', 1);
				echo '<script>$(document).ready(function(){alertBox("恭喜！短信发送成功！","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
			} else {
				echo '<script>$(document).ready(function(){alertBox("抱歉!'.$result['content'].'..","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
			}
			*/
		} else {
			/*查询剩余条数*/
			$content = Db::name('site')->field('msg')->where('id=' . $site_id)->find();
			$this->assign('content',$content);
			/*分配变量*/
			$this->assign('title', '短信设置'); /*页面标题*/
			$this->assign('keywords', '短信设置'); /*页面关键词*/
			$this->assign('description', '短信设置'); /*页面描述*/
			/*调用模板*/
			return $this->fetch(TEMP_FETCH);
		}
	}
	/*短信设置 end*/	
	
	/*登录日志管理 start*/
	/*登录日志列表*/
	public function logs() {
		/*读取日志*/
		$log = new \loginfo\Log(); /*实例化日志类*/
		if (!empty(input('timestart')) && !empty(input('timeend'))) {
			$timestart = strtotime(input('timestart'));
			$timeend = strtotime(input('timeend'));
			$this->assign('timestart', input('timestart'));
			$this->assign('timeend', input('timeend'));
			$loglist = $log->readLog('login', 0, $timestart, $timeend); /*读取日志*/
		} else {
			$loglist = $log->readLog('login'); /*读取日志*/
		}
		/*条件*/
		$list = array();
		if (is_array($loglist)) {
			foreach ($loglist as $k => $v) {
				/*if ($v['user'] == $this->user_id && $v['site'] == $this->site_id) {*/
				if ($v['site'] == $this->site_id) {
					$list[$k] = $v;
				}
			}
		}
		$this->assign('list', $list);
		/*分配变量*/
		$this->assign('title', '登录日志'); /*页面标题*/
		$this->assign('keywords', '登录日志'); /*页面关键词*/
		$this->assign('description', '登录日志'); /*页面描述*/
		/*调用模板*/
		return $this->fetch(TEMP_FETCH);
	}
	/*登录日志管理 end*/
	
	/*系统消息管理 start*/
	/*系统消息列表*/
	public function message() {
		/*查询系统消息*/
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
			'limit' => $limit, /*每页条数*/
			'search' => $search, /*搜索*/
		));
		/*条件*/
		$where_list['status'] = 1;
		$list = Db::name('message')->where($where_list)->order('id desc')->paginate($pagelimit,false,$paginate);
		$lists = array();
		foreach ($list as $k => $v) {
			$lists[$k] = $v;
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
		$this->assign('title', '系统消息列表'); /*页面标题*/
		$this->assign('keywords', '系统消息列表'); /*页面关键词*/
		$this->assign('description', '系统消息列表'); /*页面描述*/
		/*调用模板*/
		return $this->fetch(TEMP_FETCH);
	}
	/*编辑系统消息*/
	public function messageedit() {
		/*获取参数*/
		$id = input('id'); /*Id*/
		if ($id) {
			/*编辑*/
			/*查询参数*/
			$where['id'] = $id;
			$content = Db::name('message')->where($where)->find();
			foreach ($content as $k => $v) {
				/*解析图标、图片*/
				if ($k == 'ico' && !empty($content['ico'])) {
					$ico = explode(',', $v);
					$content['ico'] = array();
					foreach ($ico as $k2 => $v2) {						
						$basename = explode('/',$v2);
						$basename = array_pop($basename);
						$content['ico'][$k2] = array('filename' => $v2, 'basename' => $basename);
					}
				}
				if ($k == 'picture' && !empty($content['picture'])) {
					$picture = explode(',', $v);
					$content['picture'] = array();
					foreach ($picture as $k2 => $v2) {						
						$basename = explode('/',$v2);
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
			$this->assign('title', '编辑系统消息'); /*页面标题*/
			$this->assign('keywords', '编辑系统消息'); /*页面关键词*/
			$this->assign('description', '编辑系统消息'); /*页面描述*/
		} else {
			/*添加*/
			/*组装post提交变量*/
			$submit = array('name' => '确认添加','url' => '?a=add');
			$this->assign('submit', $submit);
			/*分配变量*/
			$this->assign('title', '添加系统消息'); /*页面标题*/
			$this->assign('keywords', '添加系统消息'); /*页面关键词*/
			$this->assign('description', '添加系统消息'); /*页面描述*/
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
					'type' => input('type'),
					'name' => input('name'), /*名称*/
					'ico' => input('ico'), /*图标*/
					'picture' => input('picture'), /*缩略图*/
					'description' => input('description'), /*简介*/
					'content' => input('content'), /*内容*/
					'order' => input('order'), /*排序  默认为100*/
					'status' => input('status'), /*状态  默认为1:启用|0:禁用*/
					'edittime' => $nowtime, /*修改时间*/
				);			
				if ($id) {
					/*编辑*/
					$result = Db::name('message')->where('id='.$id)->update($data);
					$alert_success = '恭喜，编辑成功！';
					$alert_error = '抱歉，编辑失败！';
				} else {
					/*添加*/
					$data['addtime'] = $nowtime; /*添加时间*/
					/*插入数据*/
					$result = Db::name('message')->insert($data);
					$alert_success = '恭喜，添加成功！';
					$alert_error = '抱歉，添加失败！';
				}
				/*判断结果集*/
				if ($result) {
					echo '<script>$(document).ready(function(){alertBox("' . $alert_success . '","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
				} else {
					echo '<script>$(document).ready(function(){alertBox("' . $alert_error . '","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
				}
			}
		} else {
			/*调用模板*/
			return $this->fetch(TEMP_FETCH);
		}
	}
	/*删除系统消息*/
	public function messagedel() {
		/*接收参数*/
		$id = input('id'); /*分类ID*/
		$result = Db::name('message')->where('id='.$id)->delete();
		if ($result) {
			$result_log = Db::name('message_log')->where('message_id='.$id)->delete();
			echo '<script>$(document).ready(function(){alertBox("恭喜，删除成功！","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
		} else {
			echo '<script>$(document).ready(function(){alertBox("抱歉，删除失败！","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
		}
	}
	/*系统消息管理 end*/
	

}