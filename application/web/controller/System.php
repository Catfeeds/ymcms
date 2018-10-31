<?php
namespace app\web\controller;
use think\Request;
use think\Db;
use app\common\lib\Upload;
/*系统管理中心*/
class System extends Common
{
	/*构造方法*/
	public function _initialize()
	{
		/*重载父类构造方法*/
		parent::_initialize();
	}

	/*TP的文件简单的同步批量上传方法*/
	public function filetp($array){
		$file_ico = array();
		foreach($array as $k){
			/*移动到框架应用根目录/public/upload/system 目录下*/
			$path = ROOT_PATH.'public'.DS.'upload'.DS.'system'.DS.'image';
			$info = $k->move($path);
			if($info){
				/*文件成功上传后 获取上传信息*/
				$file_ico[] = $path.$info->getFilename();
			}
		}
		$json_ico = json_encode($file_ico);	
		return $json_ico;	
	}
	/*ueditor上传方法*/
	public function ueup(){
		$ueditor_php = '.'.config('view_replace_str.__UEDITOR__').'/php/';
		$CONFIG = json_decode(preg_replace("/\/\*[\s\S]+?\*\//", "", file_get_contents($ueditor_php.'config.json')), true);
		$action = $_GET['action'];
		/*file_put_contents('./testlog_'.time().'.tex',json_encode($CONFIG).'##11122345@@'.$action,FILE_APPEND);*/
	}
	/*文件上传方法*/
	function uploadFile($file,$randclass){ 
		/*七牛上传*
		$filename = Upload::fileput($file);	
		if(!empty($filename)){
			$file_newname = explode('/',$filename);
			$file_newname = array_pop($file_newname);
			$result['filename'] = $filename;
			$result['oldname'] = $file_newname;
			$result['randclass'] = $randclass;
			return $result;
		}else{
			return '';
		}
		*/
		
		$files = $file;
		/*上传路径*/
		$datedir = '/'.date('Ymd',time()).'/';
		$destinationPath = '.'.config('view_replace_str.__SYSTEM__').'/image'.$datedir;    
		if (!file_exists($destinationPath)){
			mkdir($destinationPath , 0777);
		}
		/*重命名*/
		$oldname = iconv('utf-8' , 'gb2312' , basename($files['name']));
		$fileName = date('YmdHis') . '_' . $oldname;
		if (move_uploaded_file($files['tmp_name'], $destinationPath . $fileName)) {
			$filename = iconv('gb2312' , 'utf-8' , $fileName);
			$result['filename'] = $this->system_image.$datedir.$filename;
			// $result['filename'] = '/public/upload/system/image'.$datedir.$filename;
			// $result['filename'] = $datedir.$filename;
			$result['oldname'] = $oldname;
			$result['randclass'] = $randclass;
			return $result;
		}
		return '';
		
	}
	/*文件上传（iframe异步实现）*/
	public function fileups(){
		$randclass = input('randclass');
		$inpname = input('inpname');
		// var_dump($_FILES);die();
		$inpname = str_replace('.', '_', $inpname);
		$file = $_FILES[$inpname];
		$result = $this->uploadFile($file,$randclass); 
		$filename = $result['filename'];
		$oldname = $result['oldname'];
		$randclass = $result['randclass'];
		echo "<script type='text/javascript'>window.top.window.stopUpload('{$filename}','{$oldname}','{$randclass}');</script>";			
	}
	/*会员名唯一校验*/
	public function username_check() {
		$name = input('name');
		$user = Db::name('user')->field('id')->where('name="'.$name.'"')->find();
		if (empty($user)) {
			$data['status'] = 100;
			$data['msg'] = '该会员名不存在，可以使用！';
		} else {
			$data['status'] = 200;
			$data['msg'] = '会员名已被占用，请重新设置！';
		}
		$json = json_encode($data);
		return $json;
	}
	/*AJAX选择城市,地区 城市联动*/
	public function changearea() {
		$pid = input('id');
		$areacode = Db::name('areacode')->field('id,name')->where('pid=' . $pid . ' AND status=1')->select();
		$optionHtml = '';
		foreach ($areacode as $k => $v) {
			$optionHtml.= '<option value="' . $v['id'] . '">' . $v['name'] . '</option>';
		}
		if (!empty($areacode)) {
			$data['status'] = 100;
			$data['msg'] = $optionHtml;
		} else {
			$data['status'] = 200;
			$data['msg'] = '城市联动数据获取出错';
		}
		$json = json_encode($data);
		return $json;
	}
	/*AJAX选择应用 模板联动*/
	public function changeserver() {
		$id = input('id');
		$server = Db::name('temp')->field('id,name')->where('server_id=' . $id . ' AND status=1')->select();
		$optionHtml = '';
		foreach ($server as $k => $v) {
			$optionHtml.= '<option value="' . $v['id'] . '">' . $v['name'] . '</option>';
		}
		if (!empty($server)) {
			$data['status'] = 100;
			$data['msg'] = $optionHtml;
		} else {
			$data['status'] = 200;
			$data['msg'] = '该应用暂无模板';
		}
		$json = json_encode($data);
		return $json;
	}


	/*拓扑管理 start*/
	/*拓扑列表*/
    public function treelist()
    {
		/*查询应用*/
		$where_tree['type'] = 1;/*类型：内容列表*/
		$where_tree['pid'] = 1;
		$tree_server = Db::name('tree')->where($where_tree)->select();
		$this->assign('server',$tree_server);
		/*查询节点*/
		$tree_id = !empty(input('id'))?input('id'):0;
		/*分页参数*/
		$paginate = array(
			'query' => array(/*url额外参数*/
			'id' => $tree_id/*类型ID*/
			)
		);
		/*条件*/
		$where_tree_list['type'] = 0;/*类型：菜单*/
		if($tree_id){
			$where_tree_list['path'] = array('like','%,'.$tree_id.',%');
		}else{
			$where_tree_list['path'] = array('like',$tree_id.',%');
			/*过滤子级非菜单项*/
			$tree_type = Db::name('tree')->field('id')->where('type=1')->select();
			$tree_type1_ids = '';
			foreach($tree_type as $k=>$v){
				$tree = Db::name('tree')->select();;
				foreach($tree as $k2=>$v2){
					if(strstr($v2['path'],','.$v['id'].',')){
						$tree_type1_ids .= $v2['id'].',';
					}
				}
			}
			$tree_type1_ids = trim($tree_type1_ids,',');
			$where_tree_list['id'] = array('notin',$tree_type1_ids);
		}
		$this->assign('id',$tree_id);
		$list = Db::name('tree')->where($where_tree_list)->order('concat(`path`,`id`)')->paginate(config('paginate.list_rows')+100,false,$paginate);
		$lists = array();
		foreach($list as $k=>$v){
			$lists[$k] = $v;
			/*名称*/
			if($tree_id){
				$path = str_replace('0,1,','',$v['path']);
			}else{
				$path = $v['path'];
			}
			$lists[$k]['path'] = $path;//重组path
			$path_arr = explode(',',trim($path,','));
			$path_arr_count = count($path_arr);
			$lists[$k]['path_count'] = $path_arr_count;//层级数
			$path_str = '';
			if($path_arr_count > 1){
				for($i=1;$i<$path_arr_count;$i++){
					$path_str .= '&nbsp;';
				}
				$path_str .= $path_str.'└';
			}
			$lists[$k]['name'] = $path_str.$v['name'];
			/*状态*/
			if($v['status'] == 1){
				$lists[$k]['status'] = '启用';
			}else{
				$lists[$k]['status'] = '禁用';
			}
		}
		$page = $list->render();/*获取分页显示*/
		$this->assign('list',$lists);
		$this->assign('page',$page);
		/*分配变量*/
		$this->assign('title','拓扑列表');/*页面标题*/
		$this->assign('keywords','拓扑列表');/*页面关键词*/
		$this->assign('description','拓扑列表');/*页面描述*/
		/*调用模板*/
		return $this->fetch(TEMP_FETCH);
    }
	/*编辑节点*/
    public function treeedit()
    {
		/*查询应用*/
		$where_server['pid'] = 1;
		$tree_server = Db::name('tree')->where($where_server)->select();
		$this->assign('server',$tree_server);		
		/*获取参数*/
		$tree_id = input('id');/*节点Id*/
		$server_id = !empty(input('server_id'))?input('server_id'):0;/*应用ID*/
		if($tree_id){
			/*编辑*/
			/*查询参数*/
			$where_tree['id'] = $tree_id;
			$tree = Db::name('tree')->where($where_tree)->find();
			foreach($tree as $k=>$v){
				/*解析图标、图片*/
				if($k == 'ico' && !empty($tree['ico'])){
					$ico = explode(',',$v);
					$tree['ico'] = array();
					foreach($ico as $k2=>$v2){						$basename = explode('/',$v2);
						$basename = array_pop($basename);
						$tree['ico'][$k2] = array(
							'filename' => $v2,
							'basename' => $basename
						);
					}
				}
				if($k == 'picture' && !empty($tree['picture'])){
					$picture = explode(',',$v);
					$tree['picture'] = array();
					foreach($picture as $k2=>$v2){						$basename = explode('/',$v2);
						$basename = array_pop($basename);
						$tree['picture'][$k2] = array(
							'filename' => $v2,
							'basename' => $basename
						);
					}
				}
			}
			foreach($tree_server as $k=>$v){
				if(strstr($tree['path'],','.$v['id'].',')){
					$server_id = $v['id'];/*应用ID*/	
				}
			}
			$pid = $tree['pid'];/*父菜单*/
			$this->assign('content',$tree);/*内容*/			
			/*组装post提交变量*/
			$submit = array(
				'name' => '确认编辑',
				'url' => '?id='.$tree_id.'&server_id='.$server_id
			);
			$this->assign('submit',$submit);
			/*分配变量*/
			$this->assign('title','编辑节点');/*页面标题*/
			$this->assign('keywords','编辑节点');/*页面关键词*/
			$this->assign('description','编辑节点');/*页面描述*/			
		}else{
			/*添加*/
			/*接收参数*/
			$pid = !empty(input('pid'))?input('pid'):0;/*父菜单*/
			/*组装post提交变量*/
			$submit = array(
				'name' => '确认添加',
				'url' => '?server_id='.$server_id
			);
			$this->assign('submit',$submit);	
			/*分配变量*/
			$this->assign('title','添加节点');/*页面标题*/
			$this->assign('keywords','添加节点');/*页面关键词*/
			$this->assign('description','添加节点');/*页面描述*/		
		}
		/*查询菜单*/
		/*条件*/
		$server_id = isset($server_id)?$server_id:0;
		if($server_id){
			$where_tree_list['path'] = array('like','%,'.$server_id.',%');
		}else{
			$where_tree_list['pid'] = 0;
		}
		$this->assign('server_id',$server_id);
		$this->assign('pid',$pid);
		$list = Db::name('tree')->where($where_tree_list)->order('concat(`path`,`id`)')->select();
		$lists = array();
		foreach($list as $k=>$v){
			$lists[$k] = $v;
			/*名称*/
			if($server_id){
				$path = str_replace('0,1,','',$v['path']);
			}else{
				$path = $v['path'];
			}
			$lists[$k]['path'] = $path;//重组path
			$path_arr = explode(',',trim($path,','));
			$path_arr_count = count($path_arr);
			$lists[$k]['path_count'] = $path_arr_count;//层级数
			$path_str = '';
			if($path_arr_count > 1){
				for($i=1;$i<$path_arr_count;$i++){
					$path_str .= '&nbsp;';
				}
				$path_str .= $path_str.'└';
			}
			$lists[$k]['name'] = $path_str.$v['name'];
		}
		$this->assign('list',$lists);		
		/*提交*/
		if($_POST){
			/*获取参数*/
			$nowtime = time();
			/*验证参数*/
			if(empty(input('name'))){
				echo '<script>$(document).ready(function(){alertBox("名称不可为空..","'.$_SERVER['HTTP_REFERER'].'");})</script>';
			}else{
				$path = Db::name('tree')->field('path')->where('id='.input('pid'))->find();
				$path = $path['path'].input('pid').',';					
				/*组装参数*/
				$data = array(
					'pid'		=> input('pid'),/*父结点ID*/
					'path'		=> $path,/*路径（如：0,1,2,）*/
					'code'		=> input('code'),/*代号(字母表达)*/
					'name'		=> input('name'),/*名称*/
					'ico'		=> input('ico'),/*图标*/
					'picture'	=> input('picture'),/*缩略图*/
					'url'		=> input('url'),/*链接*/
					'description'=>input('description'),/*简介*/
					'content'	=> input('content'),/*内容*/
					'order'		=> input('order'),/*排序  默认为100*/
					'status'	=> input('status'),/*状态  默认为1:启用|0:禁用*/
					'edittime'	=> $nowtime,/*修改时间*/
				);			
				if($tree_id){
					/*编辑*/
					/*更新数据*/
					$result = Db::name('tree')->where('id='.$tree_id)->update($data);	
					$alert_success = '恭喜，编辑成功！';
					$alert_error = '抱歉，编辑失败！';					
				}else{
					/*添加*/
					$data['addtime'] = $nowtime;/*添加时间*/
					/*插入数据*/
					$result = Db::name('tree')->insert($data);
					$alert_success = '恭喜，添加成功！';
					$alert_error = '抱歉，添加失败！';										
				}
				/*判断结果集*/
				if($result){
					echo '<script>$(document).ready(function(){alertBox("'.$alert_success.'","'.$_SERVER['HTTP_REFERER'].'");})</script>';
				}else{
					echo '<script>$(document).ready(function(){alertBox("'.$alert_error.'","'.$_SERVER['HTTP_REFERER'].'");})</script>';
				}
			}
		}else{
			/*调用模板*/
			return $this->fetch(TEMP_FETCH);
		}
    }
	/*删除节点*/
    public function treedel()
    {
		/*接收参数*/
		$tree_id = input('id');/*节点ID*/
		/*判断是否存在子节点*/
		$where_tree_list['path'] = array('like','%,'.$tree_id.',%');
		$tree_sub = Db::name('tree')->where($where_tree_list)->find();
		if($tree_sub){
			/*存在子类*/
			echo '<script>$(document).ready(function(){alertBox("抱歉，存在子节点，不允许删除！","'.$_SERVER['HTTP_REFERER'].'");})</script>';
		}else{
			/*不存在子类*/
			$result = Db::name('tree')->where('id='.$tree_id)->delete();
			if($result){
				echo '<script>$(document).ready(function(){alertBox("恭喜，删除成功！","'.$_SERVER['HTTP_REFERER'].'");})</script>';
			}else{
				echo '<script>$(document).ready(function(){alertBox("抱歉，删除失败！","'.$_SERVER['HTTP_REFERER'].'");})</script>';
			}
		}
    }
	/*切换应用(AJAX)*/
	public function change_server(){
		/*接收参数*/
		$tree_id = input('server_id');/*应用ID*/	
		$pid = !empty(input('pid'))?input('pid'):0;/*父菜单*/
		/*条件*/
		if($tree_id){
			$where_tree_list['path'] = array('like','%,'.$tree_id.',%');
		}else{
			$tree_id = 0;
			$where_tree_list['pid'] = 0;
		}
		$this->assign('server_id',$tree_id);
		$this->assign('pid',$pid);
		$list = Db::name('tree')->where($where_tree_list)->order('concat(`path`,`id`)')->select();
		$lists = array();
		$list_html = '<option value="'.$tree_id.'">顶级菜单</option>';
		foreach($list as $k=>$v){
			$lists[$k] = $v;
			/*名称*/
			if($tree_id){
				$path = str_replace('0,1,','',$v['path']);
			}else{
				$path = $v['path'];
			}
			$lists[$k]['path'] = $path;//重组path
			$path_arr = explode(',',trim($path,','));
			$path_arr_count = count($path_arr);
			$lists[$k]['path_count'] = $path_arr_count;//层级数
			$path_str = '';
			if($path_arr_count > 1){
				for($i=1;$i<$path_arr_count;$i++){
					$path_str .= '&nbsp;';
				}
				$path_str .= $path_str.'└';
			}
			$lists[$k]['name'] = $path_str.$v['name'];
			if($path_arr_count > 1){
			  	$disabled = 'disabled';
			}else{
				$disabled = '';
			}
			$list_html .= '<option value="'.$v['id'].'" '.$disabled.'>'.$lists[$k]['name'].'</option>';
		}
		$result['status'] = 100;
		$result['msg'] = $list_html;		
		/*返回JSON数据*/
		return json_encode($result);
	}
	/*拓扑管理 end*/
	
	/*管理组管理 start*/
	/*管理组列表*/
    public function grouplist()
    {
		/*获取参数*/
		$limit = input('limit');
		$search = input('search');
		$this->assign('limit',$limit);
		$this->assign('search',$search);
		$pagelimit = !empty($limit)?$limit:config('paginate.list_rows');/*每页条数*/
		if(!empty($search)){
			$where_group_list['concat(name,description)'] = array('like','%'.$search.'%');/*搜索*/
		}
		/*查询管理组*/		
		$where_group_list['type'] = array('in','2,3');/*类型：系统管理员*/	
		/*分页参数*/
		$paginate = array(
			'query' => array(/*url额外参数*/
				'limit' => $limit,/*每页条数*/
				'search' => $search,/*搜索*/
			)
		);		
		$list = Db::name('group')->where($where_group_list)->paginate($pagelimit,false,$paginate);
		$lists = array();
		foreach($list as $k=>$v){
			$lists[$k] = $v;
			/*状态*/
			if($v['status'] == 1){
				$lists[$k]['status'] = '启用';
			}else{
				$lists[$k]['status'] = '禁用';
			}
		}
		$page = $list->render();/*获取分页显示*/
		$this->assign('list',$lists);
		$this->assign('page',$page);
		/*分配变量*/
		$this->assign('title','管理组列表');/*页面标题*/
		$this->assign('keywords','管理组列表');/*页面关键词*/
		$this->assign('description','管理组列表');/*页面描述*/
		/*调用模板*/
		return $this->fetch(TEMP_FETCH);
    }
	/*编辑节点*/
    public function groupedit()
    {	
		/*查询站点*/
		$site_all = Db::name('site')->field('id,name')->where('status=1')->select();/*查询所有站点*/		
		$this->assign('site_all',$site_all);/*内容*/	
		/*获取参数*/
		$group_id = input('id');/*管理组ID*/
		$this->assign('group_id',$group_id);	
		if($group_id){
			/*编辑*/
			/*查询参数*/
			$where_group['id'] = $group_id;
			$group = Db::name('group')->where($where_group)->find();
			foreach($group as $k=>$v){
				/*解析图标、图片*/
				if($k == 'ico' && !empty($group['ico'])){
					$ico = explode(',',$v);
					$group['ico'] = array();
					foreach($ico as $k2=>$v2){						$basename = explode('/',$v2);
						$basename = array_pop($basename);
						$group['ico'][$k2] = array(
							'filename' => $v2,
							'basename' => $basename
						);
					}
				}
				if($k == 'picture' && !empty($group['picture'])){
					$picture = explode(',',$v);
					$group['picture'] = array();
					foreach($picture as $k2=>$v2){						$basename = explode('/',$v2);
						$basename = array_pop($basename);
						$group['picture'][$k2] = array(
							'filename' => $v2,
							'basename' => $basename
						);
					}
				}
				/*解析权限集*/
				$group['tree'] = trim($group['tree'],'[');
				$group['tree'] = trim($group['tree'],']');
			}
			$this->assign('content',$group);/*内容*/	
			/*组装post提交变量*/
			$submit = array(
				'name' => '确认编辑',
				'url' => '?id='.$group_id
			);
			$this->assign('submit',$submit);
			/*分配变量*/
			$this->assign('title','编辑管理组');/*页面标题*/
			$this->assign('keywords','编辑管理组');/*页面关键词*/
			$this->assign('description','编辑管理组');/*页面描述*/			
		}else{
			/*添加*/
			/*组装post提交变量*/
			$submit = array(
				'name' => '确认添加',
				'url' => '?a=add'
			);
			$this->assign('submit',$submit);	
			/*分配变量*/
			$this->assign('title','添加管理组');/*页面标题*/
			$this->assign('keywords','添加管理组');/*页面关键词*/
			$this->assign('description','添加管理组');/*页面描述*/		
		}
		/*查询当前用户管理组可分配的权限*/
		$group_type = !empty($group['type'])?$group['type']:3;
		if($group_type == 3){
			/*系统管理员*/
			$where_tree_list['path'] = array('like','0,%');
		}else{
			/*网站管理员*/
			$site = Db::name('site')->field('server_id')->where('id='.$group['site_id'])->find();/*查询站点*/
			$where_tree_list['path'] = array('like','%,'.$site['server_id'].',%');
		}
		/*过滤子级非菜单项*/
		$tree_type = Db::name('tree')->field('id')->where('type=1')->select();
		$tree_type1_ids = '';
		foreach($tree_type as $k=>$v){
			$tree_type1_ids .= $v['id'].',';
			if($group_type == 3){
				/*系统管理员过滤非菜单项子集*/
				$tree = Db::name('tree')->select();
				foreach($tree as $k2=>$v2){
					if(strstr($v2['path'],','.$v['id'].',')){
						$tree_type1_ids .= $v2['id'].',';
					}
				}
			}
		}
		$tree_type1_ids = trim($tree_type1_ids,',');
		$where_tree_list['id'] = array('notin',$tree_type1_ids);	
		$group_tree = Db::name('tree')->field('id,name,pid,path')->where($where_tree_list)->select();
		/*编辑时查出已有权限*/
		foreach($group_tree as $k=>$v){
			if($group_id){
				if(!empty($group['tree'])){
					$group_tree_old = explode(',',$group['tree']);
					foreach($group_tree_old as $k2=>$v2){
						if($v2 == $v['id']){
							$group_tree[$k]['on'] = 100;
						}
					}
				}
			}
		}
		/*重组权限层级*/
		$group_tree_arr = array();		
		foreach($group_tree as $k=>$v){
			if($group_type == 3){
				/*系统管理员*/
				/*重组*/
				if($v['pid'] == 0){
					/*顶级*/
					$group_tree_arr[$v['id']] = $v;
				}else{
					/*子级*/
					$group_tree_arr[$v['pid']]['tree_sub'][$v['id']] = $v;
				}
			}else{
				/*网站管理员*/
				if($v['pid'] == $site['server_id']){
					/*顶级*/
					$group_tree_arr[$v['id']] = $v;
				}else{
					/*子级*/
					$group_tree_arr[$v['pid']]['tree_sub'][$v['id']] = $v;
				}
			}
		}
		/*分配权限变量*/
		$this->assign('tree',$group_tree_arr);	
		/*提交*/	
		if($_POST){
			/*获取参数*/
			$nowtime = time();
			/*组装权限集*/
			$treejson = explode(',',input('tree'));
			$treejson_str = '[';
			foreach($treejson as $k=>$v){
				if(!empty($v)){
					$treejson_str .= $v.',';
				}
			}
			$treejson_str = trim($treejson_str,',');
			$treejson_str .= ']';
			/*验证参数*/
			if(empty(input('name'))){
				echo '<script>$(document).ready(function(){alertBox("名称不可为空..","'.$_SERVER['HTTP_REFERER'].'");})</script>';
			}else{
				/*组装参数*/
				$data = array(
					'site_id'   => input('site_id'),
					'pid'		=> 0,/*父结点ID*/
					'path'		=> '0,',/*路径（如：0,1,2,）*/				
					'name'		=> input('name'),/*名称*/
					'ico'		=> input('ico'),/*图标*/
					'picture'	=> input('picture'),/*缩略图*/
					'description'=>input('description'),/*简介*/
					'content'	=> input('content'),/*内容*/
					'tree'		=> $treejson_str,/*权限集*/
					'type'		=> input('type'),/*类型 系统管理*/
					'order'		=> input('order'),/*排序  默认为100*/
					'status'	=> input('status'),/*状态  默认为1:启用|0:禁用*/
					'edittime'	=> $nowtime,/*修改时间*/
				);
				if($group_id){
					/*编辑*/
					/*更新数据*/
					$result = Db::name('group')->where('id='.$group_id)->update($data);	
					$alert_success = '恭喜，编辑成功！';
					$alert_error = '抱歉，编辑失败！';					
				}else{
					/*添加*/
					$data['addtime'] = $nowtime;/*添加时间*/
					/*插入数据*/
					$result = Db::name('group')->insert($data);
					$alert_success = '恭喜，添加成功！';
					$alert_error = '抱歉，添加失败！';										
				}
				/*判断结果集*/
				if($result){
					echo '<script>$(document).ready(function(){alertBox("'.$alert_success.'","'.url('system/grouplist').'");})</script>';
				}else{
					echo '<script>$(document).ready(function(){alertBox("'.$alert_error.'","'.$_SERVER['HTTP_REFERER'].'");})</script>';
				}
			}
		}else{
			/*调用模板*/
			return $this->fetch(TEMP_FETCH);		
		}
    }
	/*删除节点*/
    public function groupdel()
    {	
		/*接收参数*/
		$group_id = input('id');/*管理组ID*/
		/*判断是否存在管理员*/
		$group_user = Db::name('user')->where('group_ids='.$group_id)->find();
		if($group_user){
			/*存在子类*/
			echo '<script>$(document).ready(function(){alertBox("抱歉，存在管理员，不允许删除！","'.$_SERVER['HTTP_REFERER'].'");})</script>';
		}else{
			/*不存在子类*/
			$result = Db::name('group')->where('id='.$group_id)->delete();
			if($result){
				echo '<script>$(document).ready(function(){alertBox("恭喜，删除成功！","'.$_SERVER['HTTP_REFERER'].'");})</script>';
			}else{
				echo '<script>$(document).ready(function(){alertBox("抱歉，删除失败！","'.$_SERVER['HTTP_REFERER'].'");})</script>';
			}
		}
    }
	/*管理组管理 end*/

	/*管理员管理 start*/
	/*管理员列表*/
    public function adminlist()
    {
		/*获取参数*/
		$limit = input('limit');
		$search = input('search');
		$this->assign('limit',$limit);
		$this->assign('search',$search);
		$pagelimit = !empty($limit)?$limit:config('paginate.list_rows');/*每页条数*/
		if(!empty($search)){
			$where_user_list['concat(name,nickname)'] = array('like','%'.$search.'%');/*搜索*/
		}
		/*查询管理员*/	
		$where_group['type'] = array('in','2,3');/*类型：系统管理员*/	
		$group = Db::name('group')->where($where_group)->select();
		$group_ids = '';
		foreach($group as $k=>$v){
			$group_ids .= $v['id'].',';
		}
		$group_ids = trim($group_ids,',');
		/*查询管理员*/		
		$where_user_list['group_ids'] = array('in',$group_ids);/*管理组：系统管理员*/	
		/*分页参数*/
		$paginate = array(
			'query' => array(/*url额外参数*/
				'limit' => $limit,/*每页条数*/
				'search' => $search,/*搜索*/
			)
		);		
		$list = Db::name('user')->where($where_user_list)->paginate($pagelimit,false,$paginate);
		$lists = array();
		foreach($list as $k=>$v){
			$lists[$k] = $v;
			/*状态*/
			if($v['status'] == 1){
				$lists[$k]['status'] = '启用';
			}else{
				$lists[$k]['status'] = '禁用';
			}
		}
		$page = $list->render();/*获取分页显示*/
		$this->assign('list',$lists);
		$this->assign('page',$page);
		/*分配变量*/
		$this->assign('title','管理员列表');/*页面标题*/
		$this->assign('keywords','管理员列表');/*页面关键词*/
		$this->assign('description','管理员列表');/*页面描述*/
		/*调用模板*/
		return $this->fetch(TEMP_FETCH);
    }
	/*编辑节点*/
    public function adminedit()
    {	
		/*查询系统管理员组*/
		$group_where['type'] = array('in','2,3');
		$group_where['status'] = 1;
		$group = Db::name('group')->field('id,name')->where($group_where)->select();
		$this->assign('group',$group);
		/*查询行业*/
		$industry = Db::name('industry')->field('id,name')->where('status=1')->select();
		$this->assign('industry',$industry);
		/*查询州*/
		$state = Db::name('areacode')->field('id,name')->where('pid=0 AND status=1')->select();
		$this->assign('state',$state);
		/*获取参数*/
		$user_id = input('id');/*管理员ID*/
		if($user_id){
			/*编辑*/
			/*查询参数*/
			$where_user['id'] = $user_id;
			$user = Db::name('user')->where($where_user)->find();
			foreach($user as $k=>$v){
				/*解析头像*/
				if($k == 'picture' && !empty($user['picture'])){
					$picture = explode(',',$v);
					$user['picture'] = array();
					foreach($picture as $k2=>$v2){						$basename = explode('/',$v2);
						$basename = array_pop($basename);
						$user['picture'][$k2] = array(
							'filename' => $v2,
							'basename' => $basename
						);
					}
				}
				/*解析身份证号*/
				if($k == 'identity' && !empty($user['identity'])){
					$identity = json_decode($user['identity']);
					$user['identity'] = $identity->name;
				}
				/*解析手机号*/
				if($k == 'phone' && !empty($user['phone'])){
					$phone = json_decode($user['phone']);
					$user['phone'] = $phone->name;
				}
				/*解析邮箱地址*/
				if($k == 'mail' && !empty($user['mail'])){
					$mail = json_decode($user['mail']);
					$user['mail'] = $mail->name;
				}
				/*解析qq号码*/
				if($k == 'qq' && !empty($user['qq'])){
					$qq = json_decode($user['qq']);
					$user['qq'] = $qq->name;
				}
				/*解析微信号*/
				if($k == 'wechat' && !empty($user['wechat'])){
					$wechat = json_decode($user['wechat']);
					$user['wechat'] = $wechat->name;
				}	
				/*查询国*/
				if($k == 'state' && !empty($user['state'])){
					$country = Db::name('areacode')->field('id,name')->where('pid='.$user['state'].' AND status=1')->select();
					$this->assign('country',$country);	
				}
				/*查询省*/
				if($k == 'country' && !empty($user['country'])){
					$province = Db::name('areacode')->field('id,name')->where('pid='.$user['country'].' AND status=1')->select();
					$this->assign('province',$province);	
				}
				/*查询市*/
				if($k == 'province' && !empty($user['province'])){
					$city = Db::name('areacode')->field('id,name')->where('pid='.$user['province'].' AND status=1')->select();
					$this->assign('city',$city);	
				}
				/*查询区*/
				if($k == 'city' && !empty($user['city'])){
					$area = Db::name('areacode')->field('id,name')->where('pid='.$user['city'].' AND status=1')->select();
					$this->assign('area',$area);	
				}
			}
			$this->assign('content',$user);/*内容*/	
			/*组装post提交变量*/
			$submit = array(
				'name' => '确认编辑',
				'url' => '?id='.$user_id
			);
			$this->assign('submit',$submit);
			/*分配变量*/
			$this->assign('title','编辑管理员');/*页面标题*/
			$this->assign('keywords','编辑管理员');/*页面关键词*/
			$this->assign('description','编辑管理员');/*页面描述*/			
		}else{
			/*添加*/
			/*组装post提交变量*/
			$submit = array(
				'name' => '确认添加',
				'url' => '?a=add'
			);
			$this->assign('submit',$submit);	
			/*分配变量*/
			$this->assign('title','添加管理员');/*页面标题*/
			$this->assign('keywords','添加管理员');/*页面关键词*/
			$this->assign('description','添加管理员');/*页面描述*/		
		}
		/*提交*/
		if($_POST){
			/*获取参数*/
			$nowtime = time();
			$password = input('password');
			/*验证参数*/
			if(empty(input('name'))){
				echo '<script>$(document).ready(function(){alertBox("名称不可为空..","'.$_SERVER['HTTP_REFERER'].'");})</script>';
			}else if(input('password') != input('password2')){
				echo '<script>$(document).ready(function(){alertBox("两次密码不一致..","'.$_SERVER['HTTP_REFERER'].'");})</script>';
			}else{											
				/*组装参数*/
				$data = array(
					'group_ids'	=> input('group_id'),/*所属管理组*/
					'site_ids'  => config('system_site'),/*用户管理站点*/			
					'name'		=> input('name'),/*名称*/
					'nickname'	=> input('nickname'),/*昵称*/
					'picture'	=> input('picture'),/*头像*/
					'sex'		=> input('sex'),/*性别 默认女:0|男为1 */
					'industry'	=> input('industry'),/*行业*/
					'year'		=> input('year'),/*出生年*/
					'month'		=> input('month'),/*出生月*/
					'day'		=> input('day'),/*出生日*/
					'state'		=> input('state'),/*州*/
					'country'	=> input('country'),/*国*/
					'province'	=> input('province'),/*省*/
					'city'		=> input('city'),/*市*/
					'area'		=> input('area'),/*区*/
					'address'	=> input('address'),/*地址*/
					'order'		=> input('order'),/*排序  认为100*/
					'status'	=> input('status'),/*状态  默认为1:启用|0:禁用*/
					'edittime'	=> $nowtime,/*修改时间*/
				);
				if($user_id){
					/*编辑*/
					/*更新密码*/
					if(!empty($password)){
						$reg_time = $user['addtime'];
						$password = md5(md5($reg_time.$password));
						$data['password'] = $password;
					}
					/*更新数据*/
					$result = Db::name('user')->where('id='.$user_id)->update($data);	
					$alert_success = '恭喜，编辑成功！';
					$alert_error = '抱歉，编辑失败！';					
				}else{
					/*更新密码*/
					if(!empty($password)){
						$reg_time = $nowtime;
						$password = md5(md5($reg_time.$password));
						$data['password'] = $password;
					}
					/*重组身份证json*/
					$identity_arr = array(
						'name' 		=> input('identity'),
						'status'	=> 0,
						'edittime'	=> $nowtime,
					);
					$identity_json = json_encode($identity_arr);
					/*重组手机json*/
					$phone_arr = array(
						'name' 		=> input('phone'),
						'status'	=> 0,
						'edittime'	=> $nowtime,
					);
					$phone_json = json_encode($phone_arr);
					/*重组邮箱json*/
					$mail_arr = array(
						'name' 		=> input('mail'),
						'status'	=> 0,
						'edittime'	=> $nowtime,
					);
					$mail_json = json_encode($mail_arr);
					/*重组QQjson*/
					$qq_arr = array(
						'name' 		=> input('qq'),
						'status'	=> 0,
						'edittime'	=> $nowtime,
					);
					$qq_json = json_encode($qq_arr);
					/*重组微信号json*/
					$wechat_arr = array(
						'name' 		=> input('wechat'),
						'status'	=> 0,
						'edittime'	=> $nowtime,
					);
					$wechat_json = json_encode($wechat_arr);
					/*添加*/
					$data['money']		= 0;/*余额*/
					$data['integral']	= 0;/*积分*/
					$data['growth']		= 0;/*成长值*/	
					$data['identity']	= $identity_json;/*身份证号码JONS集（号码,认证状态1已认证0未认证,变更时间）*/
					$data['phone']		= $phone_json;/*手机JONS集（号码,认证状态1已认证0未认证,变更时间）*/
					$data['mail']		= $mail_json;/*邮箱JONS集（号码,认证状态1已认证0未认证,变更时间）*/
					$data['qq']			= $qq_json;/*QQ-JONS集（号码,关联状态1已关联0未关联,变更时间）*/
					$data['wechat']		= $wechat_json;/*微信-JONS集（号码,关联状态1已关联0未关联,变更时间）*/									
					$data['addtime']	= $nowtime;/*添加时间*/
					//dump($data);die();
					/*插入数据*/
					$result = Db::name('user')->insert($data);
					$alert_success = '恭喜，添加成功！';
					$alert_error = '抱歉，添加失败！';										
				}
				/*判断结果集*/
				if($result){
					echo '<script>$(document).ready(function(){alertBox("'.$alert_success.'","'.url('system/adminlist').'");})</script>';
				}else{
					echo '<script>$(document).ready(function(){alertBox("'.$alert_error.'","'.$_SERVER['HTTP_REFERER'].'");})</script>';
				}
			}
		}else{
			/*调用模板*/
			return $this->fetch(TEMP_FETCH);		
		}
    }
	/*删除管理员*/
    public function admindel()
    {	
		/*接收参数*/
		$user_id = input('id');/*管理组ID*/
		$result = Db::name('user')->where('id='.$user_id)->delete();
		if($result){
			echo '<script>$(document).ready(function(){alertBox("恭喜，删除成功！","'.$_SERVER['HTTP_REFERER'].'");})</script>';
		}else{
			echo '<script>$(document).ready(function(){alertBox("抱歉，删除失败！","'.$_SERVER['HTTP_REFERER'].'");})</script>';
		}
    }		
	/*管理员管理 end*/

	/*用户管理 start*/
	/*用户列表*/
    public function userlist()
    {
		/*获取参数*/
		$limit = input('limit');
		$search = input('search');
		$this->assign('limit',$limit);
		$this->assign('search',$search);
		$pagelimit = !empty($limit)?$limit:config('paginate.list_rows');/*每页条数*/
		if(!empty($search)){
			$where_user_list['concat(name,nickname)'] = array('like','%'.$search.'%');/*搜索*/
		}
		/*查询管理组*/
		$where_group['type'] = array('in','1');/*类型：会员或网站管理员*/	
		$group = Db::name('group')->where($where_group)->select();
		$group_ids = '';
		foreach($group as $k=>$v){
			$group_ids .= $v['id'].',';
		}
		$group_ids = trim($group_ids,',');
		/*查询管理员*/
		$where_user_list['group_ids'] = array('in',$group_ids);/*管理组：会员或网站管理员*/	
		/*分页参数*/
		$paginate = array(
			'query' => array(/*url额外参数*/
				'limit' => $limit,/*每页条数*/
				'search' => $search,/*搜索*/
			)
		);
		$list = Db::name('user')->where($where_user_list)->paginate($pagelimit,false,$paginate);
		$lists = array();
		foreach($list as $k=>$v){
			$lists[$k] = $v;
			/*状态*/
			if($v['status'] == 1){
				$lists[$k]['status'] = '启用';
			}else{
				$lists[$k]['status'] = '禁用';
			}
		}
		$page = $list->render();/*获取分页显示*/
		$this->assign('list',$lists);
		$this->assign('page',$page);
		/*分配变量*/
		$this->assign('title','用户列表');/*页面标题*/
		$this->assign('keywords','用户列表');/*页面关键词*/
		$this->assign('description','用户列表');/*页面描述*/
		/*调用模板*/
		return $this->fetch(TEMP_FETCH);
    }
	/*编辑用户*/
    public function useredit()
    {	
		/*查询站点*/
		$site_all = Db::name('site')->field('id,name')->where('status=1')->select();/*查询所有站点*/		
		$this->assign('site_all',$site_all);/*内容*/		
		/*查询用户管理员组*/
		$where_group['type'] = array('in','1');
		$where_group['status'] = 1;
		$group = Db::name('group')->field('id,name')->where($where_group)->select();
		$this->assign('group',$group);
		/*查询行业*/
		$industry = Db::name('industry')->field('id,name')->where('status=1')->select();
		$this->assign('industry',$industry);
		/*查询州*/
		$state = Db::name('areacode')->field('id,name')->where('pid=0 AND status=1')->select();
		$this->assign('state',$state);
		/*获取参数*/
		$user_id = input('id');/*管理员ID*/
		if($user_id){
			/*编辑*/
			/*查询参数*/
			$where_user['id'] = $user_id;
			$user = Db::name('user')->where($where_user)->find();
			foreach($user as $k=>$v){
				/*解析头像*/
				if($k == 'picture' && !empty($user['picture'])){
					$picture = explode(',',$v);
					$user['picture'] = array();
					foreach($picture as $k2=>$v2){						
						$basename = explode('/',$v2);
						$basename = array_pop($basename);
						$user['picture'][$k2] = array(
							'filename' => $v2,
							'basename' => $basename
						);
					}
				}
				/*解析身份证号*
				if($k == 'identity' && !empty($user['identity'])){
					$identity = json_decode($user['identity']);
					$user['identity'] = $identity->name;
				}
				/*解析手机号*
				if($k == 'phone' && !empty($user['phone'])){
					$phone = json_decode($user['phone']);
					$user['phone'] = $phone->name;
				}
				/*解析邮箱地址*
				if($k == 'mail' && !empty($user['mail'])){
					$mail = json_decode($user['mail']);
					$user['mail'] = $mail->name;
				}
				/*解析qq号码*
				if($k == 'qq' && !empty($user['qq'])){
					$qq = json_decode($user['qq']);
					$user['qq'] = $qq->name;
				}
				/*解析微信号*
				if($k == 'wechat' && !empty($user['wechat'])){
					$wechat = json_decode($user['wechat']);
					$user['wechat'] = $wechat->name;
				}	
				/*查询国*/
				if($k == 'state' && !empty($user['state'])){
					$country = Db::name('areacode')->field('id,name')->where('pid='.$user['state'].' AND status=1')->select();
					$this->assign('country',$country);	
				}
				/*查询省*/
				if($k == 'country' && !empty($user['country'])){
					$province = Db::name('areacode')->field('id,name')->where('pid='.$user['country'].' AND status=1')->select();
					$this->assign('province',$province);	
				}
				/*查询市*/
				if($k == 'province' && !empty($user['province'])){
					$city = Db::name('areacode')->field('id,name')->where('pid='.$user['province'].' AND status=1')->select();
					$this->assign('city',$city);	
				}
				/*查询区*/
				if($k == 'city' && !empty($user['city'])){
					$area = Db::name('areacode')->field('id,name')->where('pid='.$user['city'].' AND status=1')->select();
					$this->assign('area',$area);	
				}																							
			}
			$this->assign('content',$user);/*内容*/	
			/*组装post提交变量*/
			$submit = array(
				'name' => '确认编辑',
				'url' => '?id='.$user_id
			);
			$this->assign('submit',$submit);
			/*分配变量*/
			$this->assign('title','编辑用户');/*页面标题*/
			$this->assign('keywords','编辑用户');/*页面关键词*/
			$this->assign('description','编辑用户');/*页面描述*/			
		}else{
			/*添加*/
			/*组装post提交变量*/
			$submit = array(
				'name' => '确认添加',
				'url' => '?a=add'
			);
			$this->assign('submit',$submit);	
			/*分配变量*/
			$this->assign('title','添加用户');/*页面标题*/
			$this->assign('keywords','添加用户');/*页面关键词*/
			$this->assign('description','添加用户');/*页面描述*/		
		}
		/*提交*/
		if($_POST){
			/*获取参数*/
			$nowtime = time();
			$password = input('password');
			/*验证参数*/
			if(empty(input('name'))){
				echo '<script>$(document).ready(function(){alertBox("名称不可为空..","'.$_SERVER['HTTP_REFERER'].'");})</script>';
			}else if(input('password') != input('password2')){
				echo '<script>$(document).ready(function(){alertBox("两次密码不一致..","'.$_SERVER['HTTP_REFERER'].'");})</script>';
			}else{											
				/*组装参数*/
				$data = array(
					'group_ids'	=> input('group_id'),/*所属管理组*/
					'site_ids'  => input('site_id'),/*用户所属网站*/
					'name'		=> input('name'),/*名称*/
					'nickname'	=> input('nickname'),/*昵称*/
					'picture'	=> input('picture'),/*头像*/
					'sex'		=> input('sex'),/*性别 默认女:0|男为1 */
					'industry'	=> input('industry'),/*行业*/
					'year'		=> input('year'),/*出生年*/
					'month'		=> input('month'),/*出生月*/
					'day'		=> input('day'),/*出生日*/
					'state'		=> input('state'),/*州*/
					'country'	=> input('country'),/*国*/
					'province'	=> input('province'),/*省*/
					'city'		=> input('city'),/*市*/
					'area'		=> input('area'),/*区*/
					'address'	=> input('address'),/*地址*/
					'order'		=> input('order'),/*排序  默认为100*/
					'status'	=> input('status'),/*状态  默认为1:启用|0:禁用*/
					'edittime'	=> $nowtime,/*修改时间*/
				);
				if($user_id){
					/*编辑*/
					/*更新密码*/
					if(!empty($password)){
						$reg_time = $user['addtime'];
						$password = md5(md5($reg_time.$password));
						$data['password'] = $password;
					}
					/*更新数据*/
					$result = Db::name('user')->where('id='.$user_id)->update($data);	
					$alert_success = '恭喜，编辑成功！';
					$alert_error = '抱歉，编辑失败！';
				}else{
					/*更新密码*/
					if(!empty($password)){
						$reg_time = $nowtime;
						$password = md5(md5($reg_time.$password));
						$data['password'] = $password;
					}					
					/*重组身份证json*/
					$identity_arr = array(
						'name' 		=> input('identity'),
						'status'	=> 0,
						'edittime'	=> $nowtime,
					);
					$identity_json = json_encode($identity_arr);
					/*重组手机json*/
					$phone_arr = array(
						'name' 		=> input('phone'),
						'status'	=> 0,
						'edittime'	=> $nowtime,
					);
					$phone_json = json_encode($phone_arr);
					/*重组邮箱json*/
					$mail_arr = array(
						'name' 		=> input('mail'),
						'status'	=> 0,
						'edittime'	=> $nowtime,
					);
					$mail_json = json_encode($mail_arr);
					/*重组QQjson*/
					$qq_arr = array(
						'name' 		=> input('qq'),
						'status'	=> 0,
						'edittime'	=> $nowtime,
					);
					$qq_json = json_encode($qq_arr);
					/*重组微信号json*/
					$wechat_arr = array(
						'name' 		=> input('wechat'),
						'status'	=> 0,
						'edittime'	=> $nowtime,
					);
					$wechat_json = json_encode($wechat_arr);						
					/*添加*/
					$data['money']		= 0;/*余额*/
					$data['integral']	= 0;/*积分*/
					$data['growth']		= 0;/*成长值*/	
					$data['identity']	= $identity_json;/*身份证号码JONS集（号码,认证状态1已认证0未认证,变更时间）*/
					$data['phone']		= $phone_json;/*手机JONS集（号码,认证状态1已认证0未认证,变更时间）*/
					$data['mail']		= $mail_json;/*邮箱JONS集（号码,认证状态1已认证0未认证,变更时间）*/
					$data['qq']			= $qq_json;/*QQ-JONS集（号码,关联状态1已关联0未关联,变更时间）*/
					$data['wechat']		= $wechat_json;/*微信-JONS集（号码,关联状态1已关联0未关联,变更时间）*/									
					$data['addtime']	= $nowtime;/*添加时间*/
					/*插入数据*/
					$result = Db::name('user')->insert($data);
					$alert_success = '恭喜，添加成功！';
					$alert_error = '抱歉，添加失败！';										
				}
				/*判断结果集*/
				if($result){
					echo '<script>$(document).ready(function(){alertBox("'.$alert_success.'","'.url('system/userlist').'");})</script>';
				}else{
					echo '<script>$(document).ready(function(){alertBox("'.$alert_error.'","'.$_SERVER['HTTP_REFERER'].'");})</script>';
				}
			}
		}else{
			/*调用模板*/
			return $this->fetch(TEMP_FETCH);
		}
    }
	/*删除用户*/
    public function userdel()
    {
		/*接收参数*/
		$user_id = input('id');/*用户ID*/
		$result = Db::name('user')->where('id='.$user_id)->delete();
		if($result){
			echo '<script>$(document).ready(function(){alertBox("恭喜，删除成功！","'.$_SERVER['HTTP_REFERER'].'");})</script>';
		}else{
			echo '<script>$(document).ready(function(){alertBox("抱歉，删除失败！","'.$_SERVER['HTTP_REFERER'].'");})</script>';
		}
    }		
	/*用户管理 end*/

	/*应用管理 start*/
	/*应用列表*/
    public function serverlist()
    {
		/*获取参数*/
		$limit = input('limit');
		$search = input('search');
		$this->assign('limit',$limit);
		$this->assign('search',$search);
		$pagelimit = !empty($limit)?$limit:config('paginate.list_rows');/*每页条数*/
		if(!empty($search)){
			$where_list['concat(name,description)'] = array('like','%'.$search.'%');/*搜索*/
		}
		/*查询*/
		$where_list['type'] = 1;/*类型：内容列表*/
		$where_list['pid'] = 1;
		/*分页参数*/
		$paginate = array(
			'query' => array(/*url额外参数*/
				'limit' => $limit,/*每页条数*/
				'search' => $search,/*搜索*/
			)
		);		
		$list = Db::name('tree')->where($where_list)->paginate($pagelimit,false,$paginate);
		$lists = array();
		foreach($list as $k=>$v){
			$lists[$k] = $v;
			/*状态*/
			if($v['status'] == 1){
				$lists[$k]['status'] = '启用';
			}else{
				$lists[$k]['status'] = '禁用';
			}
		}
		$page = $list->render();/*获取分页显示*/
		$this->assign('list',$lists);
		$this->assign('page',$page);
		/*分配变量*/
		$this->assign('title','应用列表');/*页面标题*/
		$this->assign('keywords','应用列表');/*页面关键词*/
		$this->assign('description','应用列表');/*页面描述*/
		/*调用模板*/
		return $this->fetch(TEMP_FETCH);
    }	
	/*编辑应用*/
    public function serveredit()
    {
		/*获取参数*/
		$tree_id = input('id');/*节点Id*/
		if($tree_id){
			/*编辑*/
			/*查询参数*/
			$where_tree['id'] = $tree_id;
			$tree = Db::name('tree')->where($where_tree)->find();
			foreach($tree as $k=>$v){
				/*解析图标、图片*/
				if($k == 'ico' && !empty($tree['ico'])){
					$ico = explode(',',$v);
					$tree['ico'] = array();
					foreach($ico as $k2=>$v2){						$basename = explode('/',$v2);
						$basename = array_pop($basename);
						$tree['ico'][$k2] = array(
							'filename' => $v2,
							'basename' => $basename
						);
					}
				}
				if($k == 'picture' && !empty($tree['picture'])){
					$picture = explode(',',$v);
					$tree['picture'] = array();
					foreach($picture as $k2=>$v2){						$basename = explode('/',$v2);
						$basename = array_pop($basename);
						$tree['picture'][$k2] = array(
							'filename' => $v2,
							'basename' => $basename
						);
					}
				}
			}
			$this->assign('content',$tree);/*内容*/		
			/*组装post提交变量*/
			$submit = array(
				'name' => '确认编辑',
				'url' => '?id='.$tree_id
			);
			$this->assign('submit',$submit);
			/*分配变量*/
			$this->assign('title','编辑应用');/*页面标题*/
			$this->assign('keywords','编辑应用');/*页面关键词*/
			$this->assign('description','编辑应用');/*页面描述*/			
		}else{
			/*添加*/
			/*组装post提交变量*/
			$submit = array(
				'name' => '确认添加',
				'url' => '?a=add'
			);
			$this->assign('submit',$submit);	
			/*分配变量*/
			$this->assign('title','添加应用');/*页面标题*/
			$this->assign('keywords','添加应用');/*页面关键词*/
			$this->assign('description','添加应用');/*页面描述*/		
		}
		/*提交*/
		if($_POST){
			/*获取参数*/
			$nowtime = time();
			/*验证参数*/
			if(empty(input('name'))){
				echo '<script>$(document).ready(function(){alertBox("名称不可为空..","'.$_SERVER['HTTP_REFERER'].'");})</script>';
			}else{
				/*组装参数*/
				$data = array(
					'pid'		=> '1',/*父结点ID*/
					'path'		=> '0,1,',/*路径（如：0,1,2,）*/
					'code'		=> input('code'),/*代号(字母表达)*/
					'name'		=> input('name'),/*名称*/
					'ico'		=> input('ico'),/*图标*/
					'picture'	=> input('picture'),/*缩略图*/
					'url'		=> input('url'),/*链接*/
					'description'=>input('description'),/*简介*/
					'content'	=> input('content'),/*内容*/
					'type'		=> 1,/*类型：内容列表*/
					'order'		=> input('order'),/*排序  默认为100*/
					'status'	=> input('status'),/*状态  默认为1:启用|0:禁用*/
					'edittime'	=> $nowtime,/*修改时间*/
				);
				if($tree_id){
					/*编辑*/
					/*更新数据*/
					$result = Db::name('tree')->where('id='.$tree_id)->update($data);	
					$alert_success = '恭喜，编辑成功！';
					$alert_error = '抱歉，编辑失败！';					
				}else{
					/*添加*/
					$data['addtime'] = $nowtime;/*添加时间*/
					/*插入数据*/
					$result = Db::name('tree')->insert($data);
					$alert_success = '恭喜，添加成功！';
					$alert_error = '抱歉，添加失败！';										
				}
				/*判断结果集*/
				if($result){
					echo '<script>$(document).ready(function(){alertBox("'.$alert_success.'","'.url('system/serverlist').'");})</script>';
				}else{
					echo '<script>$(document).ready(function(){alertBox("'.$alert_error.'","'.$_SERVER['HTTP_REFERER'].'");})</script>';
				}
			}
		}else{
			/*调用模板*/
			return $this->fetch(TEMP_FETCH);		
		}
    }
	/*删除节点*/
    public function serverdel()
    {	
		/*接收参数*/
		$tree_id = input('id');/*节点ID*/
		/*判断是否存在站点*/
		$site = Db::name('site')->where('server_id='.$tree_id)->find();
		if($site){
			echo '<script>$(document).ready(function(){alertBox("抱歉，应用下存在站点，不允许删除！","'.$_SERVER['HTTP_REFERER'].'");})</script>';
		}else{
			/*判断是否存在子节点*/
			$where_tree_list['path'] = array('like','%,'.$tree_id.',%');
			$tree_sub = Db::name('tree')->where($where_tree_list)->find();
			if($tree_sub){
				/*存在子类*/
				echo '<script>$(document).ready(function(){alertBox("抱歉，存在子栏目，不允许删除！","'.$_SERVER['HTTP_REFERER'].'");})</script>';
			}else{
				/*不存在子类*/
				$result = Db::name('tree')->where('id='.$tree_id)->delete();
				if($result){
					echo '<script>$(document).ready(function(){alertBox("恭喜，删除成功！","'.$_SERVER['HTTP_REFERER'].'");})</script>';
				}else{
					echo '<script>$(document).ready(function(){alertBox("抱歉，删除失败！","'.$_SERVER['HTTP_REFERER'].'");})</script>';
				}
			}
		}
    }	
	/*应用管理 end*/

	/*站点管理 start*/
	/*站点列表*/
    public function sitelist()
    {
		/*获取参数*/
		$limit = input('limit');
		$search = input('search');
		$this->assign('limit',$limit);
		$this->assign('search',$search);
		$pagelimit = !empty($limit)?$limit:config('paginate.list_rows');/*每页条数*/
		if(!empty($search)){
			$where_list['concat(name,description)'] = array('like','%'.$search.'%');/*搜索*/
		}
		/*查询*/
		$where_list['id'] = array('gt',0);
		/*分页参数*/
		$paginate = array(
			'query' => array(/*url额外参数*/
				'limit' => $limit,/*每页条数*/
				'search' => $search,/*搜索*/
			)
		);
		$list = Db::name('site')->where($where_list)->paginate($pagelimit,false,$paginate);
		$lists = array();
		foreach($list as $k=>$v){
			$lists[$k] = $v;
			/*状态*/
			if($v['status'] == 1){
				$lists[$k]['status'] = '启用';
			}else{
				$lists[$k]['status'] = '禁用';
			}
		}
		$page = $list->render();/*获取分页显示*/
		$this->assign('list',$lists);
		$this->assign('page',$page);
		/*分配变量*/
		$this->assign('title','站点列表');/*页面标题*/
		$this->assign('keywords','站点列表');/*页面关键词*/
		$this->assign('description','站点列表');/*页面描述*/
		/*调用模板*/
		return $this->fetch(TEMP_FETCH);
    }
	/*编辑站点*/
    public function siteedit()
    {		
		/*查询应用*/
		$where_tree['type'] = 1;/*类型：内容列表*/
		$where_tree['pid'] = 1;
		$where_tree['status'] = 1;
		$tree_server = Db::name('tree')->where($where_tree)->select();
		$this->assign('server',$tree_server);
		/*查询语种*/
		$language = Db::name('language')->select();
		$this->assign('language',$language);				
		/*获取参数*/
		$site_id = input('id');/*节点Id*/
		if($site_id){
			/*编辑*/
			/*查询参数*/
			$where_site['id'] = $site_id;
			$site = Db::name('site')->where($where_site)->find();
			foreach($site as $k=>$v){
				/*解析图标、图片*/
				if($k == 'ico' && !empty($site['ico'])){
					$ico = explode(',',$v);
					$site['ico'] = array();
					foreach($ico as $k2=>$v2){						$basename = explode('/',$v2);
						$basename = array_pop($basename);
						$site['ico'][$k2] = array(
								'filename' => $v2,
								'basename' => $basename
						);
					}
				}
				if($k == 'picture' && !empty($site['picture'])){
					$picture = explode(',',$v);
					$site['picture'] = array();
					foreach($picture as $k2=>$v2){						$basename = explode('/',$v2);
						$basename = array_pop($basename);
						$site['picture'][$k2] = array(
								'filename' => $v2,
								'basename' => $basename
						);
					}
				}
				/*组合支付方式*/
				if($k == 'payment_ids' && !empty($site['payment_ids'])){
					$site['payment'] = explode(',',$site['payment_ids']);
				}
			}
			/*分配变量*/	
			$this->assign('content',$site);/*内容*/
			/*组装post提交变量*/
			$submit = array(
				'name' => '确认编辑',
				'url' => '?id='.$site_id
			);
			$this->assign('submit',$submit);
			/*分配变量*/
			$this->assign('title','编辑站点');/*页面标题*/
			$this->assign('keywords','编辑站点');/*页面关键词*/
			$this->assign('description','编辑站点');/*页面描述*/
		}else{
			/*添加*/
			/*组装post提交变量*/
			$submit = array(
				'name' => '确认添加',
				'url' => '?a=add'
			);
			$this->assign('submit',$submit);	
			/*分配变量*/
			$this->assign('title','添加站点');/*页面标题*/
			$this->assign('keywords','添加站点');/*页面关键词*/
			$this->assign('description','添加站点');/*页面描述*/		
		}
		/*查询支付*/
		$where_payment['status'] = 1;
		$tree_payment = Db::name('payment')->where($where_payment)->select();
		if(!empty($site['payment'])){
			foreach($tree_payment as $k=>$v){
				foreach($site['payment'] as $k2=>$v2){
					if($v['id'] == $v2){
						$tree_payment[$k]['on'] = 100;
					}
				}
			}
		}	
		$this->assign('payment',$tree_payment);	
		/*查询模板*/
		if(!empty($site['server_id'])){
			$where_temp['server_id'] = $site['server_id'];
		}
		$where_temp['status'] = 1;		
		$temp = Db::name('temp')->where($where_temp)->select();
		$this->assign('temp',$temp);		
		/*提交*/
		if($_POST){
			/*获取参数*/
			$nowtime = time();
			if(!empty($_POST['payment_ids'])){
				$payment_ids = implode(',',$_POST['payment_ids']);
			}else{
				$payment_ids = '';
			}
			/*验证参数*/
			if(empty(input('name'))){
				echo '<script>$(document).ready(function(){alertBox("名称不可为空..","'.$_SERVER['HTTP_REFERER'].'");})</script>';
			}else{
				/*组装参数*/
				$data = array(
					'server_id'	=> input('server_id'),/*应用ID*/
					'language_id'=>input('language_id'),/*语种ID*/
					'payment_ids'=>$payment_ids,/*支付ID字符集*/
					'name'		=> input('name'),/*名称*/
					'ico'		=> input('ico'),/*图标*/
					'picture'	=> input('picture'),/*缩略图*/
					'content'	=> input('content'),/*内容*/
					'keywords'	=> input('keywords'),/*关键词*/
					'description'=>input('description'),/*简介*/
					'author'	=> input('author'),/*所有者*/
					'order'		=> input('order'),/*排序  默认为100*/
					'status'	=> input('status'),/*状态  默认为1:启用|0:禁用*/
					'edittime'	=> $nowtime,/*修改时间*/
				);
				if($site_id){
					/*编辑*/
					/*更新数据*/
					$result = Db::name('site')->where('id='.$site_id)->update($data);	
					$alert_success = '恭喜，编辑成功！';
					$alert_error = '抱歉，编辑失败！';					
				}else{
					/*添加*/
					$data['addtime'] = $nowtime;/*添加时间*/
					/*插入数据*/
					$result = Db::name('site')->insert($data);
					/*更新站点编号*/
					$lastSiteId = Db::name('site')->getLastInsID();
					$save_site['no'] = 'a'.mt_rand(1000,9999).$lastSiteId;
					Db::name('site')->where('id',$lastSiteId)->update($save_site);					
					$alert_success = '恭喜，添加成功！';
					$alert_error = '抱歉，添加失败！';										
				}
				/*判断结果集*/
				if($result){
					echo '<script>$(document).ready(function(){alertBox("'.$alert_success.'","'.url('system/sitelist').'");})</script>';
				}else{
					echo '<script>$(document).ready(function(){alertBox("'.$alert_error.'","'.$_SERVER['HTTP_REFERER'].'");})</script>';
				}
			}
		}else{
			/*调用模板*/
			return $this->fetch(TEMP_FETCH);		
		}
    }
	/*删除站点*/
    public function sitedel()
    {	
		/*接收参数*/
		$site_id = input('id');/*节点ID*/
		/*判断是否存在用户*/
		$site = Db::name('user')->where('site_ids='.$site_id)->find();
		if($site){
			echo '<script>$(document).ready(function(){alertBox("抱歉，站点下存在使用的用户，不允许删除！","'.$_SERVER['HTTP_REFERER'].'");})</script>';
		}else{
			/*判断是否存在分类*/
			$where_category['site_id'] = $site_id;
			$category = Db::name('category')->where($where_category)->find();
			if($category){
				/*存在分类*/
				echo '<script>$(document).ready(function(){alertBox("抱歉，存在分类或内容，不允许删除！","'.$_SERVER['HTTP_REFERER'].'");})</script>';
			}else{
				/*不存在分类*/
				$result = Db::name('site')->where('id='.$site_id)->delete();
				if($result){
					echo '<script>$(document).ready(function(){alertBox("恭喜，删除成功！","'.$_SERVER['HTTP_REFERER'].'");})</script>';
				}else{
					echo '<script>$(document).ready(function(){alertBox("抱歉，删除失败！","'.$_SERVER['HTTP_REFERER'].'");})</script>';
				}
			}
		}
    }	
	/*站点管理 end*/

	/*语言管理 start*/
	/*语言列表*/
    public function languagelist()
    {
		/*获取参数*/
		$limit = input('limit');
		$search = input('search');
		$this->assign('limit',$limit);
		$this->assign('search',$search);
		$pagelimit = !empty($limit)?$limit:config('paginate.list_rows');/*每页条数*/
		if(!empty($search)){
			$where_list['concat(name,description)'] = array('like','%'.$search.'%');/*搜索*/
		}
		/*查询*/
		$where_list['id'] = array('gt',0);
		/*分页参数*/
		$paginate = array(
			'query' => array(/*url额外参数*/
				'limit' => $limit,/*每页条数*/
				'search' => $search,/*搜索*/
			)
		);		
		$list = Db::name('language')->where($where_list)->paginate($pagelimit,false,$paginate);
		$lists = array();
		foreach($list as $k=>$v){
			$lists[$k] = $v;
			/*状态*/
			if($v['status'] == 1){
				$lists[$k]['status'] = '启用';
			}else{
				$lists[$k]['status'] = '禁用';
			}
		}
		$page = $list->render();/*获取分页显示*/
		$this->assign('list',$lists);
		$this->assign('page',$page);
		/*分配变量*/
		$this->assign('title','语言列表');/*页面标题*/
		$this->assign('keywords','语言列表');/*页面关键词*/
		$this->assign('description','语言列表');/*页面描述*/
		/*调用模板*/
		return $this->fetch(TEMP_FETCH);
    }
	/*编辑语言*/
    public function languageedit()
    {
		/*获取参数*/
		$language_id = input('id');/*节点Id*/
		if($language_id){
			/*编辑*/
			/*查询参数*/
			$where_language['id'] = $language_id;
			$language = Db::name('language')->where($where_language)->find();
			foreach($language as $k=>$v){
				/*解析图标、图片*/
				if($k == 'ico' && !empty($language['ico'])){
					$ico = explode(',',$v);
					$language['ico'] = array();
					foreach($ico as $k2=>$v2){						$basename = explode('/',$v2);
						$basename = array_pop($basename);
						$language['ico'][$k2] = array(
							'filename' => $v2,
							'basename' => $basename
						);
					}
				}
				if($k == 'picture' && !empty($language['picture'])){
					$picture = explode(',',$v);
					$language['picture'] = array();
					foreach($picture as $k2=>$v2){						$basename = explode('/',$v2);
						$basename = array_pop($basename);
						$language['picture'][$k2] = array(
							'filename' => $v2,
							'basename' => $basename
						);
					}
				}
			}
			$this->assign('content',$language);/*内容*/		
			/*组装post提交变量*/
			$submit = array(
				'name' => '确认编辑',
				'url' => '?id='.$language_id
			);
			$this->assign('submit',$submit);
			/*分配变量*/
			$this->assign('title','编辑语种');/*页面标题*/
			$this->assign('keywords','编辑语种');/*页面关键词*/
			$this->assign('description','编辑语种');/*页面描述*/			
		}else{
			/*添加*/
			/*组装post提交变量*/
			$submit = array(
				'name' => '确认添加',
				'url' => '?a=add'
			);
			$this->assign('submit',$submit);
			/*分配变量*/
			$this->assign('title','添加语种');/*页面标题*/
			$this->assign('keywords','添加语种');/*页面关键词*/
			$this->assign('description','添加语种');/*页面描述*/
		}
		/*提交*/
		if($_POST){
			/*获取参数*/
			$nowtime = time();
			/*验证参数*/
			if(empty(input('name'))){
				echo '<script>$(document).ready(function(){alertBox("名称不可为空..","'.$_SERVER['HTTP_REFERER'].'");})</script>';
			}else{
				/*组装参数*/
				$data = array(
					'code'		=> input('code'),/*代号(字母表达)*/
					'name'		=> input('name'),/*名称*/
					'ico'		=> input('ico'),/*图标*/
					'picture'	=> input('picture'),/*缩略图*/
					'url'		=> input('url'),/*链接*/
					'description'=>input('description'),/*简介*/
					'content'	=> input('content'),/*内容*/
					'order'		=> input('order'),/*排序  默认为100*/
					'status'	=> input('status'),/*状态  默认为1:启用|0:禁用*/
					'edittime'	=> $nowtime,/*修改时间*/
				);
				if($language_id){
					/*编辑*/
					/*更新数据*/
					$result = Db::name('language')->where('id='.$language_id)->update($data);
					$alert_success = '恭喜，编辑成功！';
					$alert_error = '抱歉，编辑失败！';
				}else{
					/*添加*/
					$data['addtime'] = $nowtime;/*添加时间*/
					/*插入数据*/
					$result = Db::name('language')->insert($data);
					$alert_success = '恭喜，添加成功！';
					$alert_error = '抱歉，添加失败！';										
				}
				/*判断结果集*/
				if($result){
					echo '<script>$(document).ready(function(){alertBox("'.$alert_success.'","'.url('system/languagelist').'");})</script>';
				}else{
					echo '<script>$(document).ready(function(){alertBox("'.$alert_error.'","'.$_SERVER['HTTP_REFERER'].'");})</script>';
				}
			}
		}else{
			/*调用模板*/
			return $this->fetch(TEMP_FETCH);
		}
    }
	/*删除语言*/
    public function languagedel()
    {	
		/*接收参数*/
		$language_id = input('id');/*节点ID*/
		$result = Db::name('language')->where('id='.$language_id)->delete();
		if($result){
			echo '<script>$(document).ready(function(){alertBox("恭喜，删除成功！","'.$_SERVER['HTTP_REFERER'].'");})</script>';
		}else{
			echo '<script>$(document).ready(function(){alertBox("抱歉，删除失败！","'.$_SERVER['HTTP_REFERER'].'");})</script>';
		}
    }
	/*语言管理 end*/

	/*支付管理 start*/
	/*支付列表*/
    public function paymentlist()
    {
		/*获取参数*/
		$limit = input('limit');
		$search = input('search');
		$this->assign('limit',$limit);
		$this->assign('search',$search);
		$pagelimit = !empty($limit)?$limit:config('paginate.list_rows');/*每页条数*/
		if(!empty($search)){
			$where_list['concat(name,description)'] = array('like','%'.$search.'%');/*搜索*/
		}
		/*查询*/
		$where_list['id'] = array('gt',0);
		/*分页参数*/
		$paginate = array(
			'query' => array(/*url额外参数*/
				'limit' => $limit,/*每页条数*/
				'search' => $search,/*搜索*/
			)
		);
		$list = Db::name('payment')->where($where_list)->paginate($pagelimit,false,$paginate);
		$lists = array();
		foreach($list as $k=>$v){
			$lists[$k] = $v;
			/*状态*/
			if($v['status'] == 1){
				$lists[$k]['status'] = '启用';
			}else{
				$lists[$k]['status'] = '禁用';
			}
		}
		$page = $list->render();/*获取分页显示*/
		$this->assign('list',$lists);
		$this->assign('page',$page);
		/*分配变量*/
		$this->assign('title','支付列表');/*页面标题*/
		$this->assign('keywords','支付列表');/*页面关键词*/
		$this->assign('description','支付列表');/*页面描述*/
		/*调用模板*/
		return $this->fetch(TEMP_FETCH);
    }	
	/*编辑支付*/
    public function paymentedit()
    {		
		/*获取参数*/
		$payment_id = input('id');/*节点Id*/
		if($payment_id){
			/*编辑*/
			/*查询参数*/
			$where_payment['id'] = $payment_id;
			$payment = Db::name('payment')->where($where_payment)->find();
			foreach($payment as $k=>$v){
				/*解析图标、图片*/
				if($k == 'ico' && !empty($payment['ico'])){
					$ico = explode(',',$v);
					$payment['ico'] = array();
					foreach($ico as $k2=>$v2){						$basename = explode('/',$v2);
						$basename = array_pop($basename);
						$payment['ico'][$k2] = array(
							'filename' => $v2,
							'basename' => $basename
						);
					}
				}
				if($k == 'picture' && !empty($payment['picture'])){
					$picture = explode(',',$v);
					$payment['picture'] = array();
					foreach($picture as $k2=>$v2){						$basename = explode('/',$v2);
						$basename = array_pop($basename);
						$payment['picture'][$k2] = array(
							'filename' => $v2,
							'basename' => $basename
						);
					}
				}
			}
			$this->assign('content',$payment);/*内容*/		
			/*组装post提交变量*/
			$submit = array(
				'name' => '确认编辑',
				'url' => '?id='.$payment_id
			);
			$this->assign('submit',$submit);
			/*分配变量*/
			$this->assign('title','编辑支付');/*页面标题*/
			$this->assign('keywords','编辑支付');/*页面关键词*/
			$this->assign('description','编辑支付');/*页面描述*/			
		}else{
			/*添加*/
			/*组装post提交变量*/
			$submit = array(
				'name' => '确认添加',
				'url' => '?a=add'
			);
			$this->assign('submit',$submit);	
			/*分配变量*/
			$this->assign('title','添加支付');/*页面标题*/
			$this->assign('keywords','添加支付');/*页面关键词*/
			$this->assign('description','添加支付');/*页面描述*/		
		}
		/*提交*/
		if($_POST){
			/*获取参数*/
			$nowtime = time();
			/*验证参数*/
			if(empty(input('name'))){
				echo '<script>$(document).ready(function(){alertBox("名称不可为空..","'.$_SERVER['HTTP_REFERER'].'");})</script>';
			}else{
				/*组装参数*/
				$data = array(
					'code'		=> input('code'),/*代号(字母表达)*/
					'name'		=> input('name'),/*名称*/
					'ico'		=> input('ico'),/*图标*/
					'picture'	=> input('picture'),/*缩略图*/
					'description'=>input('description'),/*简介*/
					'content'	=> input('content'),/*内容*/
					'order'		=> input('order'),/*排序  默认为100*/
					'status'	=> input('status'),/*状态  默认为1:启用|0:禁用*/
					'edittime'	=> $nowtime,/*修改时间*/
				);
				if($payment_id){
					/*编辑*/
					/*更新数据*/
					$result = Db::name('payment')->where('id='.$payment_id)->update($data);	
					$alert_success = '恭喜，编辑成功！';
					$alert_error = '抱歉，编辑失败！';					
				}else{
					/*添加*/
					$data['addtime'] = $nowtime;/*添加时间*/
					/*插入数据*/
					$result = Db::name('payment')->insert($data);
					$alert_success = '恭喜，添加成功！';
					$alert_error = '抱歉，添加失败！';										
				}
				/*判断结果集*/
				if($result){
					echo '<script>$(document).ready(function(){alertBox("'.$alert_success.'","'.url('system/paymentlist').'");})</script>';
				}else{
					echo '<script>$(document).ready(function(){alertBox("'.$alert_error.'","'.$_SERVER['HTTP_REFERER'].'");})</script>';
				}
			}
		}else{
			/*调用模板*/
			return $this->fetch(TEMP_FETCH);		
		}
    }
	/*删除支付*/
    public function paymentdel()
    {
		/*接收参数*/
		$payment_id = input('id');/*节点ID*/
		$result = Db::name('payment')->where('id='.$payment_id)->delete();
		if($result){
			echo '<script>$(document).ready(function(){alertBox("恭喜，删除成功！","'.$_SERVER['HTTP_REFERER'].'");})</script>';
		}else{
			echo '<script>$(document).ready(function(){alertBox("抱歉，删除失败！","'.$_SERVER['HTTP_REFERER'].'");})</script>';
		}
    }	
	/*支付管理 end*/

	/*模板管理 start*/
	/*模板列表*/
    public function templist()
    {
		/*获取参数*/
		$limit = input('limit');
		$search = input('search');
		$this->assign('limit',$limit);
		$this->assign('search',$search);
		$pagelimit = !empty($limit)?$limit:config('paginate.list_rows');/*每页条数*/
		if(!empty($search)){
			$where_list['concat(name,description)'] = array('like','%'.$search.'%');/*搜索*/
		}
		/*查询*/
		$where_list['id'] = array('gt',0);
		/*分页参数*/
		$paginate = array(
			'query' => array(/*url额外参数*/
				'limit' => $limit,/*每页条数*/
				'search' => $search,/*搜索*/
			)
		);
		$list = Db::name('temp')->where($where_list)->paginate($pagelimit,false,$paginate);
		$lists = array();
		foreach($list as $k=>$v){
			$lists[$k] = $v;
			/*状态*/
			if($v['status'] == 1){
				$lists[$k]['status'] = '启用';
			}else{
				$lists[$k]['status'] = '禁用';
			}
		}
		$page = $list->render();/*获取分页显示*/
		$this->assign('list',$lists);
		$this->assign('page',$page);
		/*分配变量*/
		$this->assign('title','模板列表');/*页面标题*/
		$this->assign('keywords','模板列表');/*页面关键词*/
		$this->assign('description','模板列表');/*页面描述*/
		/*调用模板*/
		return $this->fetch(TEMP_FETCH);
    }
	/*编辑模板*/
    public function tempedit()
    {
		/*查询应用*/
		$where_tree['type'] = 1;/*类型：内容列表*/
		$where_tree['pid'] = 1;
		$where_tree['status'] = 1;
		$tree_server = Db::name('tree')->where($where_tree)->select();
		$this->assign('server',$tree_server);	
		/*获取参数*/
		$temp_id = input('id');/*节点Id*/
		if($temp_id){
			/*编辑*/
			/*查询参数*/
			$where_temp['id'] = $temp_id;
			$temp = Db::name('temp')->where($where_temp)->find();
			foreach($temp as $k=>$v){
				/*解析图标、图片*/
				if($k == 'ico' && !empty($temp['ico'])){
					$ico = explode(',',$v);
					$temp['ico'] = array();
					foreach($ico as $k2=>$v2){						$basename = explode('/',$v2);
						$basename = array_pop($basename);
						$temp['ico'][$k2] = array(
							'filename' => $v2,
							'basename' => $basename
						);
					}
				}
				if($k == 'picture' && !empty($temp['picture'])){
					$picture = explode(',',$v);
					$temp['picture'] = array();
					foreach($picture as $k2=>$v2){						$basename = explode('/',$v2);
						$basename = array_pop($basename);
						$temp['picture'][$k2] = array(
							'filename' => $v2,
							'basename' => $basename
						);
					}
				}
			}
			$this->assign('content',$temp);/*内容*/		
			/*组装post提交变量*/
			$submit = array(
				'name' => '确认编辑',
				'url' => '?id='.$temp_id
			);
			$this->assign('submit',$submit);
			/*分配变量*/
			$this->assign('title','编辑模板');/*页面标题*/
			$this->assign('keywords','编辑模板');/*页面关键词*/
			$this->assign('description','编辑模板');/*页面描述*/			
		}else{
			/*添加*/
			/*组装post提交变量*/
			$submit = array(
				'name' => '确认添加',
				'url' => '?a=add'
			);
			$this->assign('submit',$submit);	
			/*分配变量*/
			$this->assign('title','添加模板');/*页面标题*/
			$this->assign('keywords','添加模板');/*页面关键词*/
			$this->assign('description','添加模板');/*页面描述*/		
		}
		/*查询颜色*/
		$where_color['status'] = 1;
		$color = Db::name('color')->where($where_color)->select();
		if(!empty($temp['color_ids'])){
			$temp['color'] = explode(',',$temp['color_ids']);
			foreach($color as $k=>$v){
				foreach($temp['color'] as $k2=>$v2){
					if($v['id'] == $v2){
						$color[$k]['on'] = 100;
					}
				}
			}
		}
		$this->assign('color',$color);	
		/*查询行业*/
		$where_industry['status'] = 1;
		$industry = Db::name('industry')->where($where_industry)->select();
		if(!empty($temp['industry_ids'])){
			$temp['industry'] = explode(',',$temp['industry_ids']);
			foreach($industry as $k=>$v){
				foreach($temp['industry'] as $k2=>$v2){
					if($v['id'] == $v2){
						$industry[$k]['on'] = 100;
					}
				}
			}
		}	
		$this->assign('industry',$industry);
		/*查询风格*/
		$where_style['status'] = 1;
		$style = Db::name('style')->where($where_style)->select();
		if(!empty($temp['style_ids'])){
			$temp['style'] = explode(',',$temp['style_ids']);
			foreach($style as $k=>$v){
				foreach($temp['style'] as $k2=>$v2){
					if($v['id'] == $v2){
						$style[$k]['on'] = 100;
					}
				}
			}
		}
		$this->assign('style',$style);
		/*提交*/
		if($_POST){
			/*获取参数*/
			$nowtime = time();
			/*颜色*/
			if(!empty($_POST['color_ids'])){
				$color_ids = implode(',',$_POST['color_ids']);
			}else{
				$color_ids = '';
			}
			/*行业*/
			if(!empty($_POST['industry_ids'])){
				$industry_ids = implode(',',$_POST['industry_ids']);
			}else{
				$industry_ids = '';
			}
			/*风格*/
			if(!empty($_POST['style_ids'])){
				$style_ids = implode(',',$_POST['style_ids']);
			}else{
				$style_ids = '';
			}
			/*验证参数*/
			if(empty(input('name'))){
				echo '<script>$(document).ready(function(){alertBox("名称不可为空..","'.$_SERVER['HTTP_REFERER'].'");})</script>';
			}else{
				/*组装参数*/
				$data = array(
					'server_id' => input('server_id'),/*应用ID*/
					'color_ids'	=> $color_ids,/*颜色ID字符集*/
					'industry_ids'=>$industry_ids,/*行业ID字符集*/
					'style_ids'	=> $style_ids,/*风格ID字符集*/
					'name'		=> input('name'),/*名称*/
					'picture'	=> input('picture'),/*缩略图*/
					'url'		=> input('url'),/*链接*/
					'pirce'		=> input('pirce'),/*价格*/
					'description'=>input('description'),/*简介*/
					'order'		=> input('order'),/*排序  默认为100*/
					'status'	=> input('status'),/*状态  默认为1:启用|0:禁用*/
					'edittime'	=> $nowtime,/*修改时间*/
				);
				if($temp_id){
					/*编辑*/
					/*更新数据*/
					$result = Db::name('temp')->where('id='.$temp_id)->update($data);	
					$alert_success = '恭喜，编辑成功！';
					$alert_error = '抱歉，编辑失败！';
				}else{
					/*添加*/
					$data['addtime'] = $nowtime;/*添加时间*/
					/*插入数据*/
					$result = Db::name('temp')->insert($data);
					$alert_success = '恭喜，添加成功！';
					$alert_error = '抱歉，添加失败！';
				}
				/*判断结果集*/
				if($result){
					echo '<script>$(document).ready(function(){alertBox("'.$alert_success.'","'.url('system/templist').'");})</script>';
				}else{
					echo '<script>$(document).ready(function(){alertBox("'.$alert_error.'","'.$_SERVER['HTTP_REFERER'].'");})</script>';
				}
			}
		}else{
			/*调用模板*/
			return $this->fetch(TEMP_FETCH);
		}
    }
	/*删除模板*/
    public function tempdel()
    {
		/*接收参数*/
		$temp_id = input('id');/*节点ID*/
		$result = Db::name('temp')->where('id='.$temp_id)->delete();
		if($result){
			echo '<script>$(document).ready(function(){alertBox("恭喜，删除成功！","'.$_SERVER['HTTP_REFERER'].'");})</script>';
		}else{
			echo '<script>$(document).ready(function(){alertBox("抱歉，删除失败！","'.$_SERVER['HTTP_REFERER'].'");})</script>';
		}
    }
	/*模板管理 end*/

	/*颜色管理 start*/
	/*颜色列表*/
    public function colorlist()
    {
		/*获取参数*/
		$limit = input('limit');
		$search = input('search');
		$this->assign('limit',$limit);
		$this->assign('search',$search);
		$pagelimit = !empty($limit)?$limit:config('paginate.list_rows');/*每页条数*/
		if(!empty($search)){
			$where_list['concat(name,description)'] = array('like','%'.$search.'%');/*搜索*/
		}
		/*查询*/
		$where_list['id'] = array('gt',0);
		/*分页参数*/
		$paginate = array(
			'query' => array(/*url额外参数*/
				'limit' => $limit,/*每页条数*/
				'search' => $search,/*搜索*/
			)
		);
		$list = Db::name('color')->where($where_list)->paginate($pagelimit,false,$paginate);
		$lists = array();
		foreach($list as $k=>$v){
			$lists[$k] = $v;
			/*状态*/
			if($v['status'] == 1){
				$lists[$k]['status'] = '启用';
			}else{
				$lists[$k]['status'] = '禁用';
			}
		}
		$page = $list->render();/*获取分页显示*/
		$this->assign('list',$lists);
		$this->assign('page',$page);
		/*分配变量*/
		$this->assign('title','颜色列表');/*页面标题*/
		$this->assign('keywords','颜色列表');/*页面关键词*/
		$this->assign('description','颜色列表');/*页面描述*/
		/*调用模板*/
		return $this->fetch(TEMP_FETCH);
    }
	/*编辑颜色*/
    public function coloredit()
    {
		/*获取参数*/
		$color_id = input('id');/*节点Id*/
		if($color_id){
			/*编辑*/
			/*查询参数*/
			$where_color['id'] = $color_id;
			$color = Db::name('color')->where($where_color)->find();
			$this->assign('content',$color);/*内容*/
			/*组装post提交变量*/
			$submit = array(
				'name' => '确认编辑',
				'url' => '?id='.$color_id
			);
			$this->assign('submit',$submit);
			/*分配变量*/
			$this->assign('title','编辑颜色');/*页面标题*/
			$this->assign('keywords','编辑颜色');/*页面关键词*/
			$this->assign('description','编辑颜色');/*页面描述*/			
		}else{
			/*添加*/
			/*组装post提交变量*/
			$submit = array(
				'name' => '确认添加',
				'url' => '?a=add'
			);
			$this->assign('submit',$submit);	
			/*分配变量*/
			$this->assign('title','添加颜色');/*页面标题*/
			$this->assign('keywords','添加颜色');/*页面关键词*/
			$this->assign('description','添加颜色');/*页面描述*/
		}
		/*提交*/
		if($_POST){
			/*获取参数*/
			$nowtime = time();
			/*验证参数*/
			if(empty(input('name'))){
				echo '<script>$(document).ready(function(){alertBox("名称不可为空..","'.$_SERVER['HTTP_REFERER'].'");})</script>';
			}else{
				/*组装参数*/
				$data = array(
					'name'		=> input('name'),/*名称*/
					'value'		=> input('value'),/*颜色值*/
					'description'=>input('description'),/*简介*/
					'order'		=> input('order'),/*排序  默认为100*/
					'status'	=> input('status'),/*状态  默认为1:启用|0:禁用*/
					'edittime'	=> $nowtime,/*修改时间*/
				);
				if($color_id){
					/*编辑*/
					/*更新数据*/
					$result = Db::name('color')->where('id='.$color_id)->update($data);	
					$alert_success = '恭喜，编辑成功！';
					$alert_error = '抱歉，编辑失败！';	
				}else{
					/*添加*/
					$data['addtime'] = $nowtime;/*添加时间*/
					/*插入数据*/
					$result = Db::name('color')->insert($data);
					$alert_success = '恭喜，添加成功！';
					$alert_error = '抱歉，添加失败！';	
				}
				/*判断结果集*/
				if($result){
					echo '<script>$(document).ready(function(){alertBox("'.$alert_success.'","'.url('system/colorlist').'");})</script>';
				}else{
					echo '<script>$(document).ready(function(){alertBox("'.$alert_error.'","'.$_SERVER['HTTP_REFERER'].'");})</script>';
				}
			}
		}else{
			/*调用模板*/
			return $this->fetch(TEMP_FETCH);
		}
    }
	/*删除颜色*/
    public function colordel()
    {
		/*接收参数*/
		$color_id = input('id');/*节点ID*/
		$result = Db::name('color')->where('id='.$color_id)->delete();
		if($result){
			echo '<script>$(document).ready(function(){alertBox("恭喜，删除成功！","'.$_SERVER['HTTP_REFERER'].'");})</script>';
		}else{
			echo '<script>$(document).ready(function(){alertBox("抱歉，删除失败！","'.$_SERVER['HTTP_REFERER'].'");})</script>';
		}
    }
	/*颜色管理 end*/	
	
	/*行业管理 start*/
	/*行业列表*/
    public function industrylist()
    {
		/*获取参数*/
		$limit = input('limit');
		$search = input('search');
		$this->assign('limit',$limit);
		$this->assign('search',$search);
		$pagelimit = !empty($limit)?$limit:config('paginate.list_rows');/*每页条数*/
		if(!empty($search)){
			$where_list['concat(name,description)'] = array('like','%'.$search.'%');/*搜索*/
		}
		/*查询*/
		$where_list['id'] = array('gt',0);
		/*分页参数*/
		$paginate = array(
			'query' => array(/*url额外参数*/
				'limit' => $limit,/*每页条数*/
				'search' => $search,/*搜索*/
			)
		);	
		$list = Db::name('industry')->where($where_list)->paginate($pagelimit,false,$paginate);
		$lists = array();
		foreach($list as $k=>$v){
			$lists[$k] = $v;
			/*状态*/
			if($v['status'] == 1){
				$lists[$k]['status'] = '启用';
			}else{
				$lists[$k]['status'] = '禁用';
			}
		}
		$page = $list->render();/*获取分页显示*/
		$this->assign('list',$lists);
		$this->assign('page',$page);
		/*分配变量*/
		$this->assign('title','行业列表');/*页面标题*/
		$this->assign('keywords','行业列表');/*页面关键词*/
		$this->assign('description','行业列表');/*页面描述*/
		/*调用模板*/
		return $this->fetch(TEMP_FETCH);
    }
	/*编辑颜色*/
    public function industryedit()
    {
		/*获取参数*/
		$industry_id = input('id');/*节点Id*/
		if($industry_id){
			/*编辑*/
			/*查询参数*/
			$where_industry['id'] = $industry_id;
			$industry = Db::name('industry')->where($where_industry)->find();
			$this->assign('content',$industry);/*内容*/		
			/*组装post提交变量*/
			$submit = array(
				'name' => '确认编辑',
				'url' => '?id='.$industry_id
			);
			$this->assign('submit',$submit);
			/*分配变量*/
			$this->assign('title','编辑行业');/*页面标题*/
			$this->assign('keywords','编辑行业');/*页面关键词*/
			$this->assign('description','编辑行业');/*页面描述*/
		}else{
			/*添加*/
			/*组装post提交变量*/
			$submit = array(
				'name' => '确认添加',
				'url' => '?a=add'
			);
			$this->assign('submit',$submit);
			/*分配变量*/
			$this->assign('title','添加行业');/*页面标题*/
			$this->assign('keywords','添加行业');/*页面关键词*/
			$this->assign('description','添加行业');/*页面描述*/
		}
		/*提交*/
		if($_POST){
			/*获取参数*/
			$nowtime = time();
			/*验证参数*/
			if(empty(input('name'))){
				echo '<script>$(document).ready(function(){alertBox("名称不可为空..","'.$_SERVER['HTTP_REFERER'].'");})</script>';
			}else{
				/*组装参数*/
				$data = array(
					'name'		=> input('name'),/*名称*/
					'description'=>input('description'),/*简介*/
					'order'		=> input('order'),/*排序  默认为100*/
					'status'	=> input('status'),/*状态  默认为1:启用|0:禁用*/
					'edittime'	=> $nowtime,/*修改时间*/
				);
				if($industry_id){
					/*编辑*/
					/*更新数据*/
					$result = Db::name('industry')->where('id='.$industry_id)->update($data);	
					$alert_success = '恭喜，编辑成功！';
					$alert_error = '抱歉，编辑失败！';
				}else{
					/*添加*/
					$data['addtime'] = $nowtime;/*添加时间*/
					/*插入数据*/
					$result = Db::name('industry')->insert($data);
					$alert_success = '恭喜，添加成功！';
					$alert_error = '抱歉，添加失败！';	
				}
				/*判断结果集*/
				if($result){
					echo '<script>$(document).ready(function(){alertBox("'.$alert_success.'","'.url('system/industrylist').'");})</script>';
				}else{
					echo '<script>$(document).ready(function(){alertBox("'.$alert_error.'","'.$_SERVER['HTTP_REFERER'].'");})</script>';
				}
			}
		}else{
			/*调用模板*/
			return $this->fetch(TEMP_FETCH);
		}
    }
	/*删除颜色*/
    public function industrydel()
    {
		/*接收参数*/
		$industry_id = input('id');/*节点ID*/
		$result = Db::name('industry')->where('id='.$industry_id)->delete();
		if($result){
			echo '<script>$(document).ready(function(){alertBox("恭喜，删除成功！","'.$_SERVER['HTTP_REFERER'].'");})</script>';
		}else{
			echo '<script>$(document).ready(function(){alertBox("抱歉，删除失败！","'.$_SERVER['HTTP_REFERER'].'");})</script>';
		}
    }
	/*颜色管理 end*/

	/*风格管理 start*/
	/*风格列表*/
    public function stylelist()
    {
		/*获取参数*/
		$limit = input('limit');
		$search = input('search');
		$this->assign('limit',$limit);
		$this->assign('search',$search);
		$pagelimit = !empty($limit)?$limit:config('paginate.list_rows');/*每页条数*/
		if(!empty($search)){
			$where_list['concat(name,description)'] = array('like','%'.$search.'%');/*搜索*/
		}
		/*查询*/
		$where_list['id'] = array('gt',0);
		/*分页参数*/
		$paginate = array(
			'query' => array(/*url额外参数*/
				'limit' => $limit,/*每页条数*/
				'search' => $search,/*搜索*/
			)
		);
		$list = Db::name('style')->where($where_list)->paginate($pagelimit,false,$paginate);
		$lists = array();
		foreach($list as $k=>$v){
			$lists[$k] = $v;
			/*状态*/
			if($v['status'] == 1){
				$lists[$k]['status'] = '启用';
			}else{
				$lists[$k]['status'] = '禁用';
			}
		}
		$page = $list->render();/*获取分页显示*/
		$this->assign('list',$lists);
		$this->assign('page',$page);
		/*分配变量*/
		$this->assign('title','行业列表');/*页面标题*/
		$this->assign('keywords','行业列表');/*页面关键词*/
		$this->assign('description','行业列表');/*页面描述*/
		/*调用模板*/
		return $this->fetch(TEMP_FETCH);
    }
	/*编辑风格*/
    public function styleedit()
    {
		/*获取参数*/
		$style_id = input('id');/*节点Id*/
		if($style_id){
			/*编辑*/
			/*查询参数*/
			$where_style['id'] = $style_id;
			$style = Db::name('style')->where($where_style)->find();
			$this->assign('content',$style);/*内容*/
			/*组装post提交变量*/
			$submit = array(
				'name' => '确认编辑',
				'url' => '?id='.$style_id
			);
			$this->assign('submit',$submit);
			/*分配变量*/
			$this->assign('title','编辑风格');/*页面标题*/
			$this->assign('keywords','编辑风格');/*页面关键词*/
			$this->assign('description','编辑风格');/*页面描述*/
		}else{
			/*添加*/
			/*组装post提交变量*/
			$submit = array(
				'name' => '确认添加',
				'url' => '?a=add'
			);
			$this->assign('submit',$submit);
			/*分配变量*/
			$this->assign('title','添加风格');/*页面标题*/
			$this->assign('keywords','添加风格');/*页面关键词*/
			$this->assign('description','添加风格');/*页面描述*/		
		}
		/*提交*/
		if($_POST){
			/*获取参数*/
			$nowtime = time();
			/*验证参数*/
			if(empty(input('name'))){
				echo '<script>$(document).ready(function(){alertBox("名称不可为空..","'.$_SERVER['HTTP_REFERER'].'");})</script>';
			}else{
				/*组装参数*/
				$data = array(
					'name'		=> input('name'),/*名称*/
					'description'=>input('description'),/*简介*/
					'order'		=> input('order'),/*排序  默认为100*/
					'status'	=> input('status'),/*状态  默认为1:启用|0:禁用*/
					'edittime'	=> $nowtime,/*修改时间*/
				);
				if($style_id){
					/*编辑*/
					/*更新数据*/
					$result = Db::name('style')->where('id='.$style_id)->update($data);	
					$alert_success = '恭喜，编辑成功！';
					$alert_error = '抱歉，编辑失败！';			
				}else{
					/*添加*/
					$data['addtime'] = $nowtime;/*添加时间*/
					/*插入数据*/
					$result = Db::name('style')->insert($data);
					$alert_success = '恭喜，添加成功！';
					$alert_error = '抱歉，添加失败！';										
				}
				/*判断结果集*/
				if($result){
					echo '<script>$(document).ready(function(){alertBox("'.$alert_success.'","'.url('system/stylelist').'");})</script>';
				}else{
					echo '<script>$(document).ready(function(){alertBox("'.$alert_error.'","'.$_SERVER['HTTP_REFERER'].'");})</script>';
				}
			}
		}else{
			/*调用模板*/
			return $this->fetch(TEMP_FETCH);
		}
    }
	/*删除风格*/
    public function styledel()
    {	
		/*接收参数*/
		$style_id = input('id');/*节点ID*/
		$result = Db::name('style')->where('id='.$style_id)->delete();
		if($result){
			echo '<script>$(document).ready(function(){alertBox("恭喜，删除成功！","'.$_SERVER['HTTP_REFERER'].'");})</script>';
		}else{
			echo '<script>$(document).ready(function(){alertBox("抱歉，删除失败！","'.$_SERVER['HTTP_REFERER'].'");})</script>';
		}
    }	
	/*风格管理 end*/

	/*物流公司管理 start*/
	/*物流公司列表*/
    public function sendlist()
    {
		/*获取参数*/
		$limit = input('limit');
		$search = input('search');
		$this->assign('limit',$limit);
		$this->assign('search',$search);
		$pagelimit = !empty($limit)?$limit:config('paginate.list_rows');/*每页条数*/
		if(!empty($search)){
			$where_list['concat(name,code)'] = array('like','%'.$search.'%');/*搜索*/
		}
		/*查询*/
		$where_list['id'] = array('gt',0);
		/*分页参数*/
		$paginate = array(
			'query' => array(/*url额外参数*/
				'limit' => $limit,/*每页条数*/
				'search' => $search,/*搜索*/
			)
		);
		$list = Db::name('send')->where($where_list)->paginate($pagelimit,false,$paginate);
		$lists = array();
		foreach($list as $k=>$v){
			$lists[$k] = $v;
			/*状态*/
			if($v['status'] == 1){
				$lists[$k]['status'] = '启用';
			}else{
				$lists[$k]['status'] = '禁用';
			}
		}
		$page = $list->render();/*获取分页显示*/
		$this->assign('list',$lists);
		$this->assign('page',$page);
		/*分配变量*/
		$this->assign('title','物流公司列表');/*页面标题*/
		$this->assign('keywords','物流公司列表');/*页面关键词*/
		$this->assign('description','物流公司列表');/*页面描述*/
		/*调用模板*/
		return $this->fetch(TEMP_FETCH);
    }
	/*编辑物流公司*/
    public function sendedit()
    {
		/*获取参数*/
		$send_id = input('id');/*节点Id*/
		if($send_id){
			/*编辑*/
			/*查询参数*/
			$where_send['id'] = $send_id;
			$send = Db::name('send')->where($where_send)->find();
			foreach($send as $k=>$v){
				/*解析图标、图片*/
				if($k == 'ico' && !empty($send['ico'])){
					$ico = explode(',',$v);
					$send['ico'] = array();
					foreach($ico as $k2=>$v2){						$basename = explode('/',$v2);
						$basename = array_pop($basename);
						$send['ico'][$k2] = array(
							'filename' => $v2,
							'basename' => $basename
						);
					}
				}
				if($k == 'picture' && !empty($send['picture'])){
					$picture = explode(',',$v);
					$send['picture'] = array();
					foreach($picture as $k2=>$v2){						$basename = explode('/',$v2);
						$basename = array_pop($basename);
						$send['picture'][$k2] = array(
							'filename' => $v2,
							'basename' => $basename
						);
					}
				}
			}
			$this->assign('content',$send);/*内容*/		
			/*组装post提交变量*/
			$submit = array(
				'name' => '确认编辑',
				'url' => '?id='.$send_id
			);
			$this->assign('submit',$submit);
			/*分配变量*/
			$this->assign('title','编辑物流公司');/*页面标题*/
			$this->assign('keywords','编辑物流公司');/*页面关键词*/
			$this->assign('description','编辑物流公司');/*页面描述*/			
		}else{
			/*添加*/
			/*组装post提交变量*/
			$submit = array(
				'name' => '确认添加',
				'url' => '?a=add'
			);
			$this->assign('submit',$submit);	
			/*分配变量*/
			$this->assign('title','添加物流公司');/*页面标题*/
			$this->assign('keywords','添加物流公司');/*页面关键词*/
			$this->assign('description','添加物流公司');/*页面描述*/		
		}
		/*提交*/
		if($_POST){
			/*获取参数*/
			$nowtime = time();
			/*验证参数*/
			if(empty(input('name'))){
				echo '<script>$(document).ready(function(){alertBox("名称不可为空..","'.$_SERVER['HTTP_REFERER'].'");})</script>';
			}else{
				/*组装参数*/
				$data = array(
					'code'		=> input('code'),/*代号(字母表达)*/
					'name'		=> input('name'),/*名称*/
					'tel'		=> input('tel'),/*电话*/
					'type'		=> input('type'),/*分类 1:国内快递|2:国际快递|3:物流*/
					'hot'		=> input('hot'),/*是否热门 默认为0:否|1:是 */
					'ico'		=> input('ico'),/*图标*/
					'picture'	=> input('picture'),/*缩略图*/
					'url'		=> input('url'),/*网址*/
					'description'=>input('description'),/*简介*/
					'content'	=> input('content'),/*内容*/
					'order'		=> input('order'),/*排序  默认为100*/
					'status'	=> input('status'),/*状态  默认为1:启用|0:禁用*/
					'edittime'	=> $nowtime,/*修改时间*/
				);
				if($send_id){
					/*编辑*/
					/*更新数据*/
					$result = Db::name('send')->where('id='.$send_id)->update($data);	
					$alert_success = '恭喜，编辑成功！';
					$alert_error = '抱歉，编辑失败！';					
				}else{
					/*添加*/
					$data['addtime'] = $nowtime;/*添加时间*/
					/*插入数据*/
					$result = Db::name('send')->insert($data);
					$alert_success = '恭喜，添加成功！';
					$alert_error = '抱歉，添加失败！';										
				}
				/*判断结果集*/
				if($result){
					echo '<script>$(document).ready(function(){alertBox("'.$alert_success.'","'.url('system/sendlist').'");})</script>';
				}else{
					echo '<script>$(document).ready(function(){alertBox("'.$alert_error.'","'.$_SERVER['HTTP_REFERER'].'");})</script>';
				}
			}
		}else{
			/*调用模板*/
			return $this->fetch(TEMP_FETCH);		
		}
    }
	/*删除物流公司*/
    public function senddel()
    {	
		/*接收参数*/
		$send_id = input('id');/*节点ID*/
		$result = Db::name('send')->where('id='.$send_id)->delete();
		if($result){
			echo '<script>$(document).ready(function(){alertBox("恭喜，删除成功！","'.$_SERVER['HTTP_REFERER'].'");})</script>';
		}else{
			echo '<script>$(document).ready(function(){alertBox("抱歉，删除失败！","'.$_SERVER['HTTP_REFERER'].'");})</script>';
		}
    }	
	/*物流公司管理 end*/

	/*地区管理 start*/
	/*地区列表*/
    public function areacodelist()
    {
		/*获取参数*/
		$limit = input('limit');
		$search = input('search');
		$this->assign('limit',$limit);
		$this->assign('search',$search);
		$pagelimit = !empty($limit)?$limit:config('paginate.list_rows');/*每页条数*/
		if(!empty($search)){
			$where_list['concat(name,code)'] = array('like','%'.$search.'%');/*搜索*/
		}
		/*查询*/
		$where_list['id'] = array('gt',0);
		/*分页参数*/
		$paginate = array(
			'query' => array(/*url额外参数*/
				'limit' => $limit,/*每页条数*/
				'search' => $search,/*搜索*/
			)
		);
		$list = Db::name('areacode')->where($where_list)->order('concat(`path`,`id`)')->paginate($pagelimit,false,$paginate);
		$lists = array();
		foreach($list as $k=>$v){
			$lists[$k] = $v;
			$path_str = '';
			if($v['level'] > 1){
				for($i=1;$i<$v['level'];$i++){
					$path_str .= '&nbsp;';
				}
				$path_str .= $path_str.'└';
			}
			$lists[$k]['name'] = $path_str.$v['name'];
			/*状态*/
			if($v['status'] == 1){
				$lists[$k]['status'] = '启用';
			}else{
				$lists[$k]['status'] = '禁用';
			}
		}
		$page = $list->render();/*获取分页显示*/
		$this->assign('list',$lists);
		$this->assign('page',$page);
		/*分配变量*/
		$this->assign('title','地区列表');/*页面标题*/
		$this->assign('keywords','地区列表');/*页面关键词*/
		$this->assign('description','地区列表');/*页面描述*/
		/*调用模板*/
		return $this->fetch(TEMP_FETCH);
    }
	/*编辑地区*/
    public function areacodeedit()
    {
		/*查询地区*/
		$areacode = Db::name('areacode')->field('id,level,name')->order('concat(`path`,`id`)')->select();
		foreach($areacode as $k=>$v){
			$path_str = '';
			if($v['level'] > 1){
				for($i=1;$i<$v['level'];$i++){
					$path_str .= '&nbsp;';
				}
				$path_str .= $path_str.'└';
			}
			$areacode[$k]['name'] = $path_str.$v['name'];
		}
		$this->assign('areacode',$areacode);
		/*获取参数*/
		$areacode_id = input('id');/*节点Id*/
		if($areacode_id){
			/*编辑*/
			/*查询参数*/
			$where_areacode['id'] = $areacode_id;
			$areacode = Db::name('areacode')->where($where_areacode)->find();
			$this->assign('content',$areacode);/*内容*/
			/*组装post提交变量*/
			$submit = array(
				'name' => '确认编辑',
				'url' => '?id='.$areacode_id
			);
			$this->assign('submit',$submit);
			/*分配变量*/
			$this->assign('title','编辑地区');/*页面标题*/
			$this->assign('keywords','编辑地区');/*页面关键词*/
			$this->assign('description','编辑地区');/*页面描述*/			
		}else{
			/*添加*/
			$pid = input('pid');/*节点Id*/
			$this->assign('pid',$pid);
			/*组装post提交变量*/
			$submit = array(
				'name' => '确认添加',
				'url' => '?a=add'
			);
			$this->assign('submit',$submit);	
			/*分配变量*/
			$this->assign('title','添加地区');/*页面标题*/
			$this->assign('keywords','添加地区');/*页面关键词*/
			$this->assign('description','添加地区');/*页面描述*/		
		}
		/*提交*/
		if($_POST){
			/*获取参数*/
			$nowtime = time();
			/*验证参数*/
			if(empty(input('name'))){
				echo '<script>$(document).ready(function(){alertBox("名称不可为空..","'.$_SERVER['HTTP_REFERER'].'");})</script>';
			}else{
				/*组装参数*/
				$data = array(
					'code'		=> input('code'),/*代号(字母表达)*/
					'name'		=> input('name'),/*名称*/
					'name_en'	=> input('name_en'),/*英文名称*/
					'name_pinyin'=>input('name_pinyin'),/*中文名称*/
					'name'		=> input('name'),/*名称*/
					'order'		=> input('order'),/*排序  默认为100*/
					'status'	=> input('status'),/*状态  默认为1:启用|0:禁用*/
					'edittime'	=> $nowtime,/*修改时间*/
				);
				if($areacode_id){
					/*编辑*/
					/*更新数据*/
					$result = Db::name('areacode')->where('id='.$areacode_id)->update($data);	
					$alert_success = '恭喜，编辑成功！';
					$alert_error = '抱歉，编辑失败！';					
				}else{
					/*添加*/
					$data['addtime'] = $nowtime;/*添加时间*/
					/*插入数据*/
					$result = Db::name('areacode')->insert($data);
					$areacodeId = Db::name('areacode')->getLastInsID();
					if(input('pid') == 0){
						$save['path'] = ','.$areacodeId.',';
						$save['level']= 1;
					}else{
						$path = Db::name('areacode')->field('path')->where('id='.input('pid'))->find();
						$save['pid'] = input('pid');
						$save['path'] = $path['path'].$areacodeId.',';
						$save['level']= count(explode(',',trim($save['path'],',')));
					}
					Db::name('areacode')->where('id='.$areacodeId)->update($save);	
					$alert_success = '恭喜，添加成功！';
					$alert_error = '抱歉，添加失败！';										
				}
				/*判断结果集*/
				if($result){
					echo '<script>$(document).ready(function(){alertBox("'.$alert_success.'","'.url('system/areacodelist').'?id='.input('server_id').'");})</script>';
				}else{
					echo '<script>$(document).ready(function(){alertBox("'.$alert_error.'","'.$_SERVER['HTTP_REFERER'].'");})</script>';
				}
			}
		}else{
			/*调用模板*/
			return $this->fetch(TEMP_FETCH);
		}
    }
	/*删除地区*/
    public function areacodedel()
    {	
		/*接收参数*/
		$areacode_id = input('id');/*节点ID*/
		/*判断是否存在子地区，*/
		$where_areacode_list['path'] = array('like','%,'.$areacode_id.',%');
		$where_areacode_list['id'] = array('notin',$areacode_id);
		$areacode_sub = Db::name('areacode')->where($where_areacode_list)->find();
		if($areacode_sub){
			/*存在子类*/
			echo '<script>$(document).ready(function(){alertBox("抱歉，存在子地区，不允许删除！","'.$_SERVER['HTTP_REFERER'].'");})</script>';
		}else{
			/*不存在子类*/
			$result = Db::name('areacode')->where('id='.$areacode_id)->delete();
			if($result){
				echo '<script>$(document).ready(function(){alertBox("恭喜，删除成功！","'.$_SERVER['HTTP_REFERER'].'");})</script>';
			}else{
				echo '<script>$(document).ready(function(){alertBox("抱歉，删除失败！","'.$_SERVER['HTTP_REFERER'].'");})</script>';
			}
		}
    }
	/*地区管理 end*/

	/*APP版本管理 start*/
	/*APP版本列表*/
    public function appversionlist()
    {
		/*获取参数*/
		$limit = input('limit');
		$search = input('search');
		$this->assign('limit',$limit);
		$this->assign('search',$search);
		$pagelimit = !empty($limit)?$limit:config('paginate.list_rows');/*每页条数*/
		if(!empty($search)){
			$where_list['concat(version,apptype)'] = array('like','%'.$search.'%');/*搜索*/
		}
		/*查询*/
		$where_list['id'] = array('gt',0);
		/*分页参数*/
		$paginate = array(
			'query' => array(/*url额外参数*/
				'limit' => $limit,/*每页条数*/
				'search' => $search,/*搜索*/
			)
		);
		$list = Db::name('app_version')->where($where_list)->order('version desc,id desc')->paginate($pagelimit,false,$paginate);
		$lists = array();
		foreach($list as $k=>$v){
			$lists[$k] = $v;
			/*状态*/
			if($v['status'] == 1){
				$lists[$k]['status'] = '启用';
			}else{
				$lists[$k]['status'] = '禁用';
			}
			/*终端类型*/
			if($v['sitetype'] == 1){
				$lists[$k]['sitetype'] = '终端1';
			}else if($v['sitetype'] == 2){
				$lists[$k]['sitetype'] = '终端2';
			}else if($v['sitetype'] == 3){
				$lists[$k]['sitetype'] = '终端3';
			}			
		}
		$page = $list->render();/*获取分页显示*/
		$this->assign('list',$lists);
		$this->assign('page',$page);
		/*分配变量*/
		$this->assign('title','APP版本列表');/*页面标题*/
		$this->assign('keywords','APP版本列表');/*页面关键词*/
		$this->assign('description','APP版本列表');/*页面描述*/
		/*调用模板*/
		return $this->fetch(TEMP_FETCH);
    }
	/*编辑APP版本*/
    public function appversionedit()
    {
		/*获取参数*/
		$appversion_id = input('id');/*节点Id*/
		if($appversion_id){
			/*编辑*/
			/*查询参数*/
			$where_appversion['id'] = $appversion_id;
			$appversion = Db::name('app_version')->where($where_appversion)->find();
			$this->assign('content',$appversion);/*内容*/
			/*组装post提交变量*/
			$submit = array(
				'name' => '确认编辑',
				'url' => '?id='.$appversion_id
			);
			$this->assign('submit',$submit);
			/*分配变量*/
			$this->assign('title','编辑APP版本');/*页面标题*/
			$this->assign('keywords','编辑APP版本');/*页面关键词*/
			$this->assign('description','编辑APP版本');/*页面描述*/			
		}else{
			/*添加*/
			/*组装post提交变量*/
			$submit = array(
				'name' => '确认添加',
				'url' => '?a=add'
			);
			$this->assign('submit',$submit);	
			/*分配变量*/
			$this->assign('title','添加APP版本');/*页面标题*/
			$this->assign('keywords','添加APP版本');/*页面关键词*/
			$this->assign('description','添加APP版本');/*页面描述*/
		}
		/*提交*/
		if($_POST){
			/*获取参数*/
			$nowtime = time();
			/*验证参数*/
			if(empty(input('version'))){
				echo '<script>$(document).ready(function(){alertBox("版本号不可为空..","'.$_SERVER['HTTP_REFERER'].'");})</script>';
			}else{
				/*组装参数*/
				$data = array(
					'site_id'		=> $this->site_id,
					'version'		=> input('version'),/*版本号，如"1.0.1"*/
					'apptype'		=> input('apptype'),/*app的类型，“ios”或者“android”二选一*/
					'is_force'		=> input('is_force'),/*是否强制升级 1:是 0:否 默认0*/
					'apk_url'		=> input('apk_url'),/*apk最新地址*/
					'upgrade_point'	=> input('upgrade_point'),/*升级提示*/		
					'sitetype'		=> input('sitetype'),/*终端*/		
					'order'			=> input('order'),/*排序 默认为100*/
					'status'		=> input('status'),/*状态 默认为1:启用|0:禁用*/
					'edittime'		=> $nowtime,/*修改时间*/
				);
				if($appversion_id){
					/*编辑*/
					/*更新数据*/
					$result = Db::name('app_version')->where('id='.$appversion_id)->update($data);	
					$alert_success = '恭喜，编辑成功！';
					$alert_error = '抱歉，编辑失败！';	
				}else{
					/*添加*/
					$data['addtime'] = $nowtime;/*添加时间*/
					/*插入数据*/
					$result = Db::name('app_version')->insert($data);
					$alert_success = '恭喜，添加成功！';
					$alert_error = '抱歉，添加失败！';	
				}
				/*判断结果集*/
				if($result){
					echo '<script>$(document).ready(function(){alertBox("'.$alert_success.'","'.url('system/appversionlist').'");})</script>';
				}else{
					echo '<script>$(document).ready(function(){alertBox("'.$alert_error.'","'.$_SERVER['HTTP_REFERER'].'");})</script>';
				}
			}
		}else{
			/*调用模板*/
			return $this->fetch(TEMP_FETCH);
		}
    }
	/*删除APP版本*/
    public function appversiondel()
    {
		/*接收参数*/
		$appversion_id = input('id');/*节点ID*/
		$result = Db::name('app_version')->where('id='.$appversion_id)->delete();
		if($result){
			echo '<script>$(document).ready(function(){alertBox("恭喜，删除成功！","'.$_SERVER['HTTP_REFERER'].'");})</script>';
		}else{
			echo '<script>$(document).ready(function(){alertBox("抱歉，删除失败！","'.$_SERVER['HTTP_REFERER'].'");})</script>';
		}
    }
	/*APP版本管理 end*/
	
	/*APP版本管理 start*/
	/*APP版本列表*/
    public function appactivelist()
    {
		/*获取参数*/
		$limit = input('limit');
		$search = input('search');
		$this->assign('limit',$limit);
		$this->assign('search',$search);
		$pagelimit = !empty($limit)?$limit:config('paginate.list_rows');/*每页条数*/
		if(!empty($search)){
			$where_list['concat(version,apptype)'] = array('like','%'.$search.'%');/*搜索*/
		}
		/*查询*/
		$where_list['id'] = array('gt',0);
		/*分页参数*/
		$paginate = array(
			'query' => array(/*url额外参数*/
				'limit' => $limit,/*每页条数*/
				'search' => $search,/*搜索*/
			)
		);
		$list = Db::name('app_active')->where($where_list)->order('addtime desc,id desc')->paginate($pagelimit,false,$paginate);
		$lists = array();
		foreach($list as $k=>$v){
			$lists[$k] = $v;
			/*终端类型*/
			if($v['sitetype'] == 1){
				$lists[$k]['sitetype'] = '终端1';
			}else if($v['sitetype'] == 2){
				$lists[$k]['sitetype'] = '终端2';
			}else if($v['sitetype'] == 3){
				$lists[$k]['sitetype'] = '终端3';
			}
			/*状态*/
			if($v['status'] == 1){
				$lists[$k]['status'] = '启用';
			}else{
				$lists[$k]['status'] = '禁用';
			}
		}
		$page = $list->render();/*获取分页显示*/
		$this->assign('list',$lists);
		$this->assign('page',$page);
		/*分配变量*/
		$this->assign('title','APP版本列表');/*页面标题*/
		$this->assign('keywords','APP版本列表');/*页面关键词*/
		$this->assign('description','APP版本列表');/*页面描述*/
		/*调用模板*/
		return $this->fetch(TEMP_FETCH);
    }
	/*删除APP版本*/
    public function appactivedel()
    {
		/*接收参数*/
		$appactive_id = input('id');/*节点ID*/
		$result = Db::name('app_active')->where('id='.$appactive_id)->delete();
		if($result){
			echo '<script>$(document).ready(function(){alertBox("恭喜，删除成功！","'.$_SERVER['HTTP_REFERER'].'");})</script>';
		}else{
			echo '<script>$(document).ready(function(){alertBox("抱歉，删除失败！","'.$_SERVER['HTTP_REFERER'].'");})</script>';
		}
    }
	/*APP版本管理 end*/	

}