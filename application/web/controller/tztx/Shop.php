<?php
namespace app\web\controller\tztx;
use app\web\controller\Common;
use think\Request;
use think\Db;
/*商品管理*/
class Shop extends Common {

	/*构造方法*/
	public function _initialize()
	{
		/*重载父类构造方法*/
		parent::_initialize();
	}
	/*分类管理 start*/
	/*分类列表*/
	public function catelist(){		
		/*查询分类*/
		/*获取参数*/
		$limit = input('limit');
		$search = input('search');
		$this->assign('limit', $limit);
		$this->assign('search', $search);
		$pagelimit = !empty($limit) ? $limit : config('paginate.list_rows'); /*每页条数*/
		if (!empty($search)) {
			$where_list['concat(name)'] = array('like', '%' . $search . '%'); /*搜索*/
		}
		/*分页参数*/
		$paginate = array('query' => array( /*url额外参数*/
			'limit' => $limit,/*每页条数*/
			'search' => $search,/*搜索*/
		));
		/*条件*/
		$where_list['status'] = array('neq', 9);
		$list = Db::name('wine_shop_cate')->where($where_list)->order('concat(`path`,`id`)')->paginate($pagelimit, false, $paginate);
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
	public function cateedit(){
		/*查询父级分类*/
		$where_category['status'] = 1;
		$where_category['pid'] = 0;
		$list = Db::name('wine_shop_cate')->where($where_category)->order('concat(`path`,`id`)')->select();
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
			$content = Db::name('wine_shop_cate')->where($where)->find();
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
					$path = Db::name('wine_shop_cate')->field('path')->where('id=' . input('pids'))->find();
					$data['path'] = $path['path'] . input('pids') . ',';
				}				
				if ($id) {
					/*编辑*/
					$result = Db::name('wine_shop_cate')->where('id='.$id)->update($data);
					$alert_success = '恭喜，编辑成功！';
					$alert_error = '抱歉，编辑失败！';
				} else {
					/*添加*/
					$data['addtime'] = $nowtime; /*添加时间*/
					/*插入数据*/
					$result = Db::name('wine_shop_cate')->insert($data);
					$alert_success = '恭喜，添加成功！';
					$alert_error = '抱歉，添加失败！';
				}
				/*判断结果集*/
				if ($result) {
					echo '<script>$(document).ready(function(){alertBox("'.$alert_success.'","'.url('tztx.shop/catelist').'");})</script>';
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
	public function catedel() {
		/*接收参数*/
		$id = input('id'); /*分类ID*/
		/*判断是否存在子分类*/
		$where['path'] = array('like', '%,' . $id . ',%');
		$where['id'] = array('notin', $id);
		$content = Db::name('wine_shop_cate')->where($where)->find();
		if ($content) {
			/*存在子类*/
			echo '<script>$(document).ready(function(){alertBox("抱歉，存在子分类，不允许删除！","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
		} else {
			/*不存在子类*/
			$result = Db::name('wine_shop_cate')->where('id='.$id)->delete();
			if ($result) {
				echo '<script>$(document).ready(function(){alertBox("恭喜，删除成功！","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
			} else {
				echo '<script>$(document).ready(function(){alertBox("抱歉，删除失败！","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
			}
		}
	}
	/*分类管理 end*/

	/*产品管理 start*/
	/*产品列表*/
	public function goodslist(){	
		/*查询内容*/
		/*获取参数*/
		$limit = input('limit');
		$search = input('search');
		$this->assign('limit', $limit);
		$this->assign('search', $search);
		$pagelimit = !empty($limit) ? $limit : config('paginate.list_rows'); /*每页条数*/
		if (!empty($search)) {
			$where_list['concat(name)'] = array('like', '%' . $search . '%'); /*搜索*/
		}
		/*查询分类信息*/
		$category_where['status'] = 1;
		$category = Db::name('wine_shop_cate')->where($category_where)->order('concat(`path`,`id`)')->select();
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
		$where_list['status'] = array('neq', 9); /*搜索*/
		/*分页参数*/
		$paginate = array('query' => array( /*url额外参数*/
			'category' => $category_id, /*搜索*/
			'limit' => $limit, /*每页条数*/
			'search' => $search, /*搜索*/
		));
		/*条件*/
		$order = array('order' => 'desc', 'id' => 'desc');
		/*判断查询还是导出*/
		if (input('excel') == 1) {
				$field = 'id,name,price_enter,price,cate_id,price_original,total,sales,description,choice,status';
				$list = Db::name('wine_shop_goods')->field($field)->where($where_list)->order($order)->select();
			} else{
				$field = 'id,name,picture,price,cate_id,status';
				$list = Db::name('wine_shop_goods')->where($where_list)->order($order)->paginate($pagelimit,false,$paginate);
			}
		$lists = array();
		foreach ($list as $k => $v) {
			$lists[$k] = $v;
			/*所属分类*/
			if(!empty($v['cate_id'])){
				$category_lisone = Db::name('wine_shop_cate')->field('name')->where('id='.$v['cate_id'])->find();
				$lists[$k]['category'] = $category_lisone['name'];
				/*导出删除多余的数据*/
				unset($lists[$k]['cate_id']);
			}
			/*产品图片*/
			if(!empty($v['picture'])){
				$list_picture_arr = explode(',',$v['picture']);
				$lists[$k]['picture'] = $list_picture_arr[0];
			}				
			/*状态*/
			if ($v['status'] == 1) {
				$lists[$k]['status'] = '发布中';
			} else {
				$lists[$k]['status'] = '保存';
			}
		}
		/*导出*/
		if (input('excel') == 1) {
			$excelHead = array('ID','商品名','进货价','出售价','原价','库存','销量','简介','是否精选 1.精选 0.否','状态','分类名');
			array_unshift($lists,$excelHead);
			/*写数据文件*/
			$this->create_xls($lists,'商品信息'.date('Y-m-d',time()));
		}
		$page = $list->render(); /*获取分页显示*/
		$this->assign('list', $lists);
		$this->assign('page', $page);
		/*分配变量*/
		$this->assign('title', '商品列表'); /*页面标题*/
		$this->assign('keywords', '商品列表'); /*页面关键词*/
		$this->assign('description', '商品列表'); /*页面描述*/
		/*调用模板*/
		return $this->fetch(TEMP_FETCH);
	}
	/*编辑产品*/
	public function goodsedit(){		
		/*筛选条件*/
		$screen = Db::name('wine_screen')->field('id,name,type')->select();
		$this->assign('screen', $screen);
		/*所属分类*/
		$where_category['status'] = 1;
		$list = Db::name('wine_shop_cate')->field('id,name,path,pid')->where($where_category)->order('concat(`path`,`id`)')->select();
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
		/*运费模板*
		$where_sendtemp['status'] = 1;
		$sendtemp = Db::name('sendtemp')->field('id,name')->where($where_sendtemp)->order('`order` desc,`id` desc')->select();
		$this->assign('sendtemp', $sendtemp);
		*/
		/*获取参数*/
		$id = input('id'); /*产品库Id*/
		if ($id) {
			/*编辑*/
			/*查询参数*/
			$where['id'] = $id;
			$contents = Db::name('wine_shop_goods')->where($where)->find();
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
			}
			/*规格参数*/
			$spec = Db::name('wine_shop_spec')->where('gid', $id)->select();
			$this->assign('spec', $spec); /*规格参数*/
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
				die();
			} else {
				/*组装参数*/
				$data = array(
					'cate_id' => input('category'), /*分类ID*/
					//'sendtemp_id' => input('sendtemp'), /*运费模板ID*/
					//'type'		=> json_encode($_POST['type']),/*类型json集((0,颜色[红、黄]),(1,材质[棉、麻]))*/
					//'attr' => json_encode($_POST['attr']), /*属性json集((0,人群[皆宜]),(1,爱好[微应用]))*/
					// 'spec' => input('spec'), /*规格 1.启用 | 0.禁用*/
					// 'val' => json_encode($_POST['val']), /*参数json集((0,人群[皆宜]),(1,爱好[微应用]))*/
					'name' => input('name'), /*名称*/
					'ico' => input('ico'), /*图标*/
					'picture' => input('picture'), /*缩略图*/
					'price' => input('price'), /*售价*/
					'price_original' => input('price_original'), /*原价*/
					'price_enter' => input('price_enter'), /*进价*/
					'total' => input('total'), /*进价*/
					'description' => input('description'), /*简介*/
					'content' => input('content'), /*内容*/
					//'video'		=> input('video'),/*视频*/
					//'resource'	=> input('resource'),/*资源*/
					'sales' => input('sales'), /*销量*/
					'visitor' => input('visitor'), /*访问量*/
					'order' => input('order'), /*排序  默认为100*/
					'status' => input('status'), /*状态  默认为1:启用|0.待发布*/
					'screen_xx' => input('screen_xx'), /*筛选条件 香型*/
					'screen_pp' => input('screen_pp'), /*筛选条件 品牌*/
					'screen_lb' => input('screen_lb'), /*筛选条件 类别*/
					'screen_cd' => input('screen_cd'), /*筛选条件 产地*/
					'screen_zl' => input('screen_zl'), /*筛选条件 种类*/
					'edittime' => $nowtime, /*修改时间*/
					'fx' => input('fx'), /*独立分销*/
					'choice' => input('choice'), /*独立分销*/
				);
				if (input('fx') == 1) {
					$commission1 = input('commission1');
					$commission2 = input('commission2');
					if (!isset($commission1) || !isset($commission2)) {
						echo '<script>$(document).ready(function(){alertBox("分销参数不能为空..","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
						die();
					}
					$data['commission1'] = $commission1;
					$data['commission2'] = $commission2;
				}
				if ($id) {
					/*编辑*/
					/*更新数据*/
					$result = Db::name('wine_shop_goods')->where('id',$id)->update($data);
					$log_in='编辑ID为'.$id.' 的商品';
					$alert_success = '恭喜，编辑成功！';
					$alert_error = '抱歉，编辑失败！';
				} else {
					/*添加*/
					$data['addtime'] = $nowtime; /*添加时间*/
					/*插入数据*/
					$result = Db::name('wine_shop_goods')->insertGetId($data);
					$log_in='添加ID为'.$result.' 的商品';
					$alert_success = '恭喜，添加成功！';
					$alert_error = '抱歉，添加失败！';
				}
				/*判断结果集*/
				if ($result) {
					/*规格ID*
					$id = $id ? $id : $result;
					if (input('spec') == 1) {
						$val = input('val/a');
						Db::name('wine_shop_spec')->where('gid', $id)->delete();
						foreach ($val as $v) {
							$val_data = $v;
							$val_data['gid'] = $id;
							Db::name('wine_shop_spec')->insert($val_data);
						}
					}*/
					$this->write_log($log_in);
					echo '<script>$(document).ready(function(){alertBox("'.$alert_success.'","'.url('tztx.shop/goodslist').'");})</script>';
				} else {
					echo '<script>$(document).ready(function(){alertBox("'.$alert_error.'","'.$_SERVER['HTTP_REFERER'].'");})</script>';
				}
			}
		} else {
			/*调用模板*/
			return $this->fetch(TEMP_FETCH);
		}
	}
	/*删除产品*/
	public function goodsdel() {
		/*接收参数*/
		$id = input('id'); /*内容ID*/
		/*判断是否存在评论*/
		// $where['content_id'] = $id;
		// $content = Db::name('comment')->where($where)->find();
		/*不存在子类*/
		$update['status'] = 9;
		$result = Db::name('wine_shop_goods')->where('id',$id)->update($update);
		if ($result) {
			$log_in='删除ID为'.$id.' 的商品';
			$this->write_log($log_in);
			echo '<script>$(document).ready(function(){alertBox("恭喜，删除成功！","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
		} else {
			echo '<script>$(document).ready(function(){alertBox("抱歉，删除失败！","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
		}
	}
	/*产品管理 end*/

	/**
	 * 筛选条件
	 */
	public function screen()
	{
		/*获取参数*/
		$limit = input('limit');
		$search = input('search');
		$this->assign('limit', $limit);
		$this->assign('search', $search);
		$pagelimit = !empty($limit) ? $limit : config('paginate.list_rows'); /*每页条数*/
		$where_list = array();
		if (!empty($search)) {
			$where_list['concat(name)'] = array('like', '%' . $search . '%'); /*搜索*/
		}
		/*分页参数*/
		$paginate = array('query' => array( /*url额外参数*/
			'limit' => $limit,/*每页条数*/
			'search' => $search,/*搜索*/
		));
		$list = Db::name('wine_screen')->where($where_list)->order('type asc')->paginate($pagelimit,false,$paginate);
		$lists = array();
		foreach ($list as $k => $v) {
			$lists[$k] = $v;
			$lists[$k]['type_name'] = $this->get_type($v['type']);
		}
		$page = $list->render(); /*获取分页显示*/
		$this->assign('list', $lists);
		$this->assign('page', $page);
		$this->assign('title', '添加产品'); /*页面标题*/
		$this->assign('keywords', '添加产品'); /*页面关键词*/
		$this->assign('description', '添加产品'); /*页面描述*/
		/*调用模板*/
		return $this->fetch(TEMP_FETCH);
	}

	/*编辑筛选条件*/
	public function screenedit(){
		/*获取参数*/
		$id = input('id'); /*分类Id*/
		if ($id) {
			/*编辑*/
			/*查询参数*/
			$where['id'] = $id;
			$content = Db::name('wine_screen')->where($where)->find();
			$this->assign('content', $content); /*内容*/
			/*组装post提交变量*/
			$submit = array('name' => '确认编辑', 'url' => '?id=' . $id);
			$this->assign('submit', $submit);
			/*分配变量*/
			$this->assign('title', '编辑筛选条件'); /*页面标题*/
			$this->assign('keywords', '编辑筛选条件'); /*页面关键词*/
			$this->assign('description', '编辑筛选条件'); /*页面描述*/
		} else {
			/*组装post提交变量*/
			$submit = array('name' => '确认添加', 'url' => '?a=add');
			$this->assign('submit', $submit);
			/*分配变量*/
			$this->assign('title', '添加筛选条件'); /*页面标题*/
			$this->assign('keywords', '添加筛选条件'); /*页面关键词*/
			$this->assign('description', '添加筛选条件'); /*页面描述*/
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
					'type' => input('type'), /*类型*/
					'name' => input('name'), /*名称*/
					'edittime' => $nowtime, /*修改时间*/
				);				
				if ($id) {
					/*编辑*/
					$result = Db::name('wine_screen')->where('id',$id)->update($data);
					$log_in ='编辑ID为'.$id.' 的筛选条件';
					$alert_success = '恭喜，编辑成功！';
					$alert_error = '抱歉，编辑失败！';
				} else {
					/*添加*/
					$data['addtime'] = $nowtime; /*添加时间*/
					/*插入数据*/
					$result = Db::name('wine_screen')->insertGetId($data);
					$log_in ='添加ID为'.$result.' 的筛选条件';
					$alert_success = '恭喜，添加成功！';
					$alert_error = '抱歉，添加失败！';
				}
				/*判断结果集*/
				if ($result) {
					$this->write_log($log_in);
					echo '<script>$(document).ready(function(){alertBox("'.$alert_success.'","'.url('tztx.shop/screen').'");})</script>';
				} else {
					echo '<script>$(document).ready(function(){alertBox("'.$alert_error.'","'.$_SERVER['HTTP_REFERER'].'");})</script>';
				}
			}
		} else {
			/*调用模板*/
			return $this->fetch(TEMP_FETCH);
		}
	}
	/*删除筛选*/
	public function screendel() {
		/*接收参数*/
		$id = input('id'); /*内容ID*/
		$type = input('type'); /*类型ID*/
		/*判断是否正在使用该筛选条件*/
		$wh = $this->get_type_field($type);
		$where[$wh] = array('eq', $id);
		$where['status'] = array('neq', 9);
		$content = Db::name('wine_shop_goods')->where($where)->find();
		/*判断结果集*/
		if ($content) {
			$alert_success = '该条件正在被使用,请先暂停使用';
			echo '<script>$(document).ready(function(){alertBox("'.$alert_success.'","'.$_SERVER['HTTP_REFERER'].'");})</script>';
			die();
		}
		/*不存在子类*/
		$result = Db::name('wine_screen')->where('id',$id)->delete();
		if ($result) {
			$log_in='删除ID为'.$id.' 的筛选条件';
			$this->write_log($log_in);
			echo '<script>$(document).ready(function(){alertBox("恭喜，删除成功！","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
		} else {
			echo '<script>$(document).ready(function(){alertBox("抱歉，删除失败！","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
		}
	}

	/**
	 * 查看商品评论
	 * @return [type] [description]
	 */
	public function goods_content()
	{	
		/*获取参数*/
		$id = input('id');
		if (empty($id)) {
			echo '<script>$(document).ready(function(){alertBox("参数缺失","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
			die();
		}
		/*查询参数*/
		$limit = input('limit');
		$search = input('search');
		$pagelimit = !empty($limit) ? $limit : config('paginate.list_rows'); /*每页条数*/
		if (!empty($search)) {
			$where_list['concat(c.nickname,g.name)'] = array('like', '%' . $search . '%'); /*搜索*/
		}
		$where_list['c.goodsid'] = array('eq', $id);
		/*分页参数*/
		$paginate = array('query' => array( /*url额外参数*/
			'limit' => $limit,/*每页条数*/
			'search' => $search,/*搜索*/
			'id' => $id,/*商品ID*/
		));
		/*固定字段*/
		$field = 'c.id,g.name,g.picture,g.price,c.nickname,c.picture as tp,c.content,c.m_content,c.service,c.logistics,c.product,c.addtime';
		/*分页查询*/
		$list = Db::name('wine_shop_comment')
						->alias('c')
						->join('wine_shop_goods g', 'c.goodsid = g.id')
						->field($field)
						->where($where_list)
						->paginate($pagelimit,false,$paginate);
		
		$page = $list->render(); /*获取分页显示*/
		$this->assign('list', $list);
		$this->assign('page', $page);
		/*分配变量*/
		$this->assign('limit', $limit);
		$this->assign('search', $search);
		$this->assign('id', $id);
		$this->assign('title', '产品列表'); /*页面标题*/
		$this->assign('keywords', '产品列表'); /*页面关键词*/
		$this->assign('description', '产品列表'); /*页面描述*/
		/*调用模板*/
		return $this->fetch(TEMP_FETCH);
	}

	/**
	 * 修改商家评论
	 */
	public function goods_comment()
	{
		/*获取参数*/
		$prompt = input('prompt');
		$id = input('id');
		if (empty($prompt)||empty($id)) {
			echo '<script>$(document).ready(function(){alertBox("参数缺失","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
			die();
		}
		/*修改内容*/
		$update['m_content'] = $prompt;
		$result = Db::name('wine_shop_comment')->where('id', $id)->update($update);
		if ($result) {
			echo '<script>$(document).ready(function(){alertBox("恭喜，修改成功！","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
		} else {
			echo '<script>$(document).ready(function(){alertBox("恭喜，修改失败！","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
		}
	}

	/**
	 * 删除评论
	 */
	public function goods_commentdel()
	{
		/*获取参数*/
		$id = input('id');
		if (empty($id)) {
			echo '<script>$(document).ready(function(){alertBox("参数缺失","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
			die();
		}
		$result = Db::name('wine_shop_comment')->where('id', $id)->delete();
		if ($result) {
			echo '<script>$(document).ready(function(){alertBox("恭喜，删除成功！","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
		} else {
			echo '<script>$(document).ready(function(){alertBox("恭喜，删除失败！","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
		}
	}


}