<?php
namespace app\api\controller\medical\v1;
use app\api\controller\Common;
use think\Controller;
use app\common\lib\exception\ApiException;
use app\common\lib\IAuth;
use think\Db;
/*用户端*/
class User extends Common{

    /**
     * 首页
     */
    public function index(){
		/*获取参数*/
		$coords = !empty(input('coords'))?input('coords'):'28.101888,112.988175';
		$coords_arr = explode(',',$coords);
		if(empty($coords_arr[0])|| empty($coords_arr[1])){
			return show(config('code.error'),'坐标非法',array());
		}
		$log = $coords_arr[1];/*经度*/
		$lat = $coords_arr[0];/*纬度*/
		$tab = !empty(input('tab'))?input('tab'):1;/*选项卡*/
		$sline = !empty(input('sline'))?input('sline'):0;
		$limit = !empty(input('limit'))?input('limit'):config('default_limit');		
		/*查询聊天名师*/
		$where_list['site_id'] = $this->site_id;
		$where_list['status']  = 2;/*状态，审核通过*/
		$search = input('search');/*搜索*/
		if(!empty($search)) {
			$where_list['concat(name,description,content)'] = array('like','%'.$search.'%');
		}		
		/*排序*/	
		if($tab == 1){
			/*综合推荐*/
			$order = '';
		}else if($tab == 2){
			/*离我最近*/
			$order = '';
		}else if($tab == 3){
			/*最新上线*/
			$order = 'addtime desc,id desc';
		}
		/*条数*/
		$limit = $sline.','.$limit;
		/*字段*/
		$field = 'id,name,picture,address,coords';
		/*查询聊天名师*/
		$total = Db::name('clinic')->where($where_list)->count();
		$list = Db::name('clinic')->field('id,coords')->where($where_list)->select();
		$lists = array();
		foreach($list as $k => $v){
			$lists[$k] = $v;
			/*计算用户与诊所的距离*/
			$clinic_coords = explode(',',$v['coords']);
			if(empty($clinic_coords[0])|| empty($clinic_coords[1])){
				$lists[$k]['distance'] = "999999991";//'未知诊所坐标';
			}else if(empty($log)|| empty($lat)){
				$lists[$k]['distance'] = "999999992";//'未知用户坐标';
			}else{
				$lists[$k]['distance'] = getDistance($log,$lat,$clinic_coords[1],$clinic_coords[0]);
			}
		}
		foreach($lists as $k=>$v){
			$listsf[(string)$v['distance']] = $v['id'];
		}
		if(!empty($listsf)){
			ksort($listsf);
			$listsf_idstr = '';
			foreach($listsf as $k=>$v){
				$listsf_idstr .= ','.$v;
			}
			$listsf_idstr = trim($listsf_idstr,',');
			$where_tablis['id'] = array('in',$listsf_idstr);
			$tablist = Db::name('clinic')->field($field)->where($where_tablis)->order($order)->limit($limit)->select();
			foreach($tablist as $k => $v){
				/*计算用户与诊所的距离*/
				$clinic_coords = explode(',',$v['coords']);
				if(empty($clinic_coords[0])|| empty($clinic_coords[1])){
					$tablist[$k]['distance'] = "999999991";//'未知诊所坐标';
				}else if(empty($log)|| empty($lat)){
					$tablist[$k]['distance'] = "999999992";//'未知用户坐标';
				}else{
					$tablist[$k]['distance'] = getDistance($log,$lat,$clinic_coords[1],$clinic_coords[0]);
				}
				/*查找诊所医生*/
				$where_doctor['clinic_id'] = $v['id'];
				$where_doctor['group_ids'] = 4;/*用户类型：医生*/
				$where_doctor['status']    = 2;/*状态，审核通过*/
				$order_doctor = 'is_online desc,edittime desc';
				$doctor_count = Db::name('user')->where($where_doctor)->count();
				$doctor_arr = Db::name('user')->field('nickname,picture,is_online')->where($where_doctor)->order($order_doctor)->limit('3')->select();
				foreach($doctor_arr as $k2=>$v2){
					$doctor_picture_arr = explode(',',$v2['picture']);
					$doctor_arr[$k2]['picture'] = $doctor_picture_arr[0];
				}	
				$tablist[$k]['doctor_count'] = $doctor_count;
				$tablist[$k]['doctor'] = $doctor_arr;	
			}
			if($tab == 1){
				/*综合推荐,基于最近诊所结果下的医生数量*/
				$tablist = sort_array($tablist,'doctor_count','desc');
			}
			/*处理结果集*/
			$result_data = array(
				'table' => array('综合推荐','离我最近','最新上线'),
				'total' => $total,
				'list'	=> $tablist
			);
			return show(config('code.success'),'ok',$result_data);
		}else{
			return show(config('code.error'),'ok，暂无数据',array());
		}
	}

    /**
     * 诊所-诊所介绍
     */
    public function clinic(){
		/*ID*/
		$id = input('clinic_id');
		if(empty($id)){
			return show(config('code.error'),'参数缺失',array());
		}
		$field = 'album,name,address,workday,tel,description,content';
		$result = Db::name('clinic')->field($field)->where('id='.$id)->find();
		if($result){
			return show(config('code.success'),'ok',$result);
		}else{
			return show(config('code.error'),'error',array());
		}	
	}

    /**
     * 诊所-医生
     */
    public function doctor(){
		/*ID*/
		$id = input('clinic_id');
		if(empty($id)){
			return show(config('code.error'),'参数缺失',array());
		}		
		/*获取参数*/	
		$sline = !empty(input('sline'))?input('sline'):0;
		$limit = !empty(input('limit'))?input('limit'):config('default_limit');
		/*查询*/
		$where_list['clinic_id'] = $id;
		$where_list['group_ids'] = 4;/*用户类型：医生*/
		$where_list['status']    = 2;/*状态，审核通过		
		/*排序*/	
		$order = 'is_online desc,edittime desc';
		/*条数*/
		$limit = $sline.','.$limit;
		/*字段*/
		$field = 'id,nickname,picture,is_online';
		/*查询*/
		$total = Db::name('user')->where($where_list)->count();
		$list = Db::name('user')->field($field)->where($where_list)->order($order)->limit($limit)->select();
		$lists = array();
		foreach($list as $k => $v){
			$lists[$k] = $v;
			/*统计评分指数*/
			$where_user_comment['user_to_id'] = $v['id'];/*被评人*/
			$total_star_avg = Db::table('ym_user_comment')->where($where_user_comment)->avg('total_star');/*综合评分平均分*/
			$total_level_avg = Db::table('ym_user_comment')->where($where_user_comment)->avg('total_level');/*医生水准平均分*/
			$total_smile_avg = Db::table('ym_user_comment')->where($where_user_comment)->avg('total_smile');/*医生态度平均分*/
			$lists[$k]['comment_avg'] = number_format(($total_star_avg+$total_level_avg+$total_smile_avg)/3*2,1);
		}
		/*处理结果集*/
		$result_data = array(
			'total' => $total,
			'list'	=> $lists
		);
		return show(config('code.success'),'ok',$result_data);
	}

    /**
     * 诊所-患者评论
     */
    public function user_comment(){
		/*ID*/
		$id = input('clinic_id');
		if(empty($id)){
			return show(config('code.error'),'参数缺失',array());
		}		
		/*获取参数*/	
		$sline = !empty(input('sline'))?input('sline'):0;
		$limit = !empty(input('limit'))?input('limit'):config('default_limit');
		/*查询*/
		$where_doctor_list['clinic_id'] = $id;
		$where_doctor_list['group_ids'] = 4;/*用户类型：医生*/
		$where_doctor_list['status']    = 2;/*状态，审核通过*/
		$doctor_arr = Db::name('user')->field('id')->where($where_doctor_list)->select();
		$doctor_str = '';
		if(!empty($doctor_arr)){
			foreach($doctor_arr as $k=>$v){
				if($k==0){
					$doctor_str = $v['id'];
				}else{
					$doctor_str = ','.$v['id'];
				}
			}
		}
		$doctor_str = trim($doctor_str,',');
		$where_list['user_to_id'] = array('in',$doctor_str);		
		/*排序*/	
		$order = 'id desc';
		/*条数*/
		$limit = $sline.','.$limit;
		/*字段*/
		$field = 'id,user_id,user_to_id,total_star,total_level,total_smile,desc,content,addtime';
		/*查询聊天名师*/
		$total = Db::name('user_comment')->where($where_list)->count();
		$list = Db::name('user_comment')->field($field)->where($where_list)->order($order)->limit($limit)->select();
		$lists = array();
		foreach($list as $k => $v){
			$lists[$k] = $v;
			/*统计评分指数*/
			$lists[$k]['comment_avg'] = number_format(($v['total_star']+$v['total_level']+$v['total_smile'])/3*2,1);
			/*格式化时间*/
			$lists[$k]['addtime'] = date('Y-m-d',$v['addtime']);	
			/*查询用户身份信息*/
			$user = Db::name('user')->field('nickname')->where('id='.$v['user_id'])->find();
			$lists[$k]['user_nickname'] = $user['nickname'];	
			/*查询医生身份信息*/
			$doctor = Db::name('user')->field('nickname')->where('id='.$v['user_to_id'])->find();
			$lists[$k]['doctor_nickname'] = $doctor['nickname'];					
		}
		/*处理结果集*/
		$result_data = array(
			'total' => $total,
			'list'	=> $lists
		);
		return show(config('code.success'),'ok',$result_data);
	}

    /**
     * 诊所-医生-医生介绍
     */
    public function doctorcon(){
		/*ID*/
		$id = input('doctor_id');
		if(empty($id)){
			return show(config('code.error'),'参数缺失',array());
		}
		$field = 'clinic_id,realname,nickname,picture,description';
		$user = Db::name('user')->field($field)->where('id='.$id)->find();
		/*查询诊所名称*/
		$clinic = Db::name('clinic')->field('name')->where('id='.$user['clinic_id'])->find();
		$user['clinic_name'] = $clinic['name'];
		/*评分指数*/
		$where_user_comment['user_to_id'] = $id;/*被评人*/
		$user['total_star_avg'] = Db::table('ym_user_comment')->where($where_user_comment)->avg('total_star');/*综合评分平均分*/
		$user['total_level_avg'] = Db::table('ym_user_comment')->where($where_user_comment)->avg('total_level');/*医生水准平均分*/
		$user['total_smile_avg'] = Db::table('ym_user_comment')->where($where_user_comment)->avg('total_smile');/*医生态度平均分*/	
		$user['total_star_avg'] = number_format($user['total_star_avg']*2,1);/*综合评分指数*/
		$user['total_level_avg'] = number_format($user['total_level_avg']*2,1);/*医生水准指数*/
		$user['total_smile_avg'] = number_format($user['total_smile_avg']*2,1);/*医生态度指数*/	
		if($user){
			return show(config('code.success'),'ok',$user);
		}else{
			return show(config('code.error'),'error',array());
		}	
	}

    /**
     * 诊所-医生-患者评论
     */
    public function doctor_comment(){
		/*ID*/
		$id = input('doctor_id');
		if(empty($id)){
			return show(config('code.error'),'参数缺失',array());
		}	
		/*获取参数*/
		$sline = !empty(input('sline'))?input('sline'):0;
		$limit = !empty(input('limit'))?input('limit'):config('default_limit');
		/*查询*/
		$where_list['user_to_id'] = array('in',$id);		
		/*排序*/
		$order = 'id desc';
		/*条数*/
		$limit = $sline.','.$limit;
		/*字段*/
		$field = 'id,user_id,user_to_id,total_star,total_level,total_smile,desc,content,addtime';
		/*查询聊天名师*/
		$total = Db::name('user_comment')->where($where_list)->count();
		$list = Db::name('user_comment')->field($field)->where($where_list)->order($order)->limit($limit)->select();
		$lists = array();
		foreach($list as $k => $v){
			$lists[$k] = $v;
			/*统计评分指数*/
			$lists[$k]['comment_avg'] = number_format(($v['total_star']+$v['total_level']+$v['total_smile'])/3*2,1);
			/*格式化时间*/
			$lists[$k]['addtime'] = date('Y-m-d',$v['addtime']);	
			/*查询用户身份信息*/
			$user = Db::name('user')->field('nickname')->where('id='.$v['user_id'])->find();
			$lists[$k]['user_nickname'] = $user['nickname'];	
			/*查询医生身份信息*/
			$doctor = Db::name('user')->field('nickname')->where('id='.$v['user_to_id'])->find();
			$lists[$k]['doctor_nickname'] = $doctor['nickname'];					
		}
		/*处理结果集*/
		$result_data = array(
			'total' => $total,
			'list'	=> $lists
		);
		return show(config('code.success'),'ok',$result_data);
	}

   /**
     * 健康学堂
     */
    public function cms(){
		/*获取参数*/
		$tab = !empty(input('tab'))?input('tab'):1;/*选项卡*/
		$sline = !empty(input('sline'))?input('sline'):0;
		$limit = !empty(input('limit'))?input('limit'):config('default_limit');
		/*条数*/
		$limit = $sline.','.$limit;		
		/*查询*/
		$where_list['site_id'] = $this->site_id;
		$where_list['status']  = 1;/*状态*/
		$search = input('search');/*搜索*/
		if(!empty($search)) {
			$where_list['concat(name,description)'] = array('like','%'.$search.'%');
		}		
		if($tab == 4){
			/*查询分类*/
			$where_list['channel_id'] = array('in','21,25');/*文章或视频*/	
			/*排序*/	
			$order = '`order` desc,id desc';
			/*字段*/
			$field = 'id,name,description,picture';
			/*查询聊天名师*/
			$total = Db::name('category')->where($where_list)->count();
			$list = Db::name('category')->field($field)->where($where_list)->order($order)->limit($limit)->select();
			$lists = array();
			foreach($list as $k => $v){
				$lists[$k] = $v;
			}			
		}else{		
			/*排序*/	
			$order = '`order` desc,edittime desc';
			/*选项卡*/
			if($tab == 1){
				$order = 'edittime desc,`order` desc';
				$where_list['channel_id'] = array('in','21,25');/*文章或视频*/
			}else if($tab == 2){
				$where_list['channel_id'] = array('in','25');/*视频*/
			}else if($tab == 3){
				$where_list['channel_id'] = array('in','21');/*文章*/
			}
			/*字段*/
			$category_id = 112;/*健康学堂*/
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
			$field = 'id,name,description,picture,video,visitor,edittime';
			/*查询聊天名师*/
			$total = Db::name('content')->where($where_list)->count();
			$list = Db::name('content')->field($field)->where($where_list)->order($order)->limit($limit)->select();
			$lists = array();
			foreach($list as $k => $v){
				$lists[$k] = $v;
				/*格式化时间*/
				$lists[$k]['edittime'] = date('Y-m-d',$v['edittime']);
				/*查询点赞数量*/
				$where_like['content_id'] = $v['id']; 
				$where_like['status'] = 1;/*已赞*/
				$lists[$k]['total_like'] = Db::name('like')->where($where_like)->count();
				/*查询点赞数量*/
				$where_comment['content_id'] = $v['id']; 
				$where_comment['status'] = 1;/*启用*/
				$lists[$k]['total_comment'] = Db::name('comment')->where($where_comment)->count();
			}
		}
		/*处理结果集*/
		$result_data = array(
			'title' => '健康学堂',
			'table' => array('最新','视频','文章','专题'),
			'total' => $total,
			'list'	=> $lists
		);
		return show(config('code.success'),'ok',$result_data);
	}

    /**
     * 健康学堂-分类-内容列表
     */
    public function cmslis(){
		/*ID*/
		$id = input('category_id');
		if(empty($id)){
			return show(config('code.error'),'参数缺失',array());
		}			
		/*获取参数*/
		$sline = !empty(input('sline'))?input('sline'):0;
		$limit = !empty(input('limit'))?input('limit'):config('default_limit');
		/*条数*/
		$limit = $sline.','.$limit;
		/*查询内容*/
		$where_list['site_id'] = $this->site_id;
		$where_list['status']  = 1;/*状态*/
		$search = input('search');/*搜索*/
		if(!empty($search)) {
			$where_list['concat(name,description)'] = array('like','%'.$search.'%');
		}		
		/*排序*/	
		$order = '`order` desc,edittime desc';
		$where_list['category_id'] = $id;/*分类*/
		$where_list['channel_id'] = array('in','21,25');/*文章或视频*/
		/*字段*/
		$field = 'id,name,description,picture,video,visitor,edittime';
		/*查询聊天名师*/
		$total = Db::name('content')->where($where_list)->count();
		$list = Db::name('content')->field($field)->where($where_list)->order($order)->limit($limit)->select();
		$lists = array();
		foreach($list as $k => $v){
			$lists[$k] = $v;
			/*格式化时间*/
			$lists[$k]['edittime'] = date('Y-m-d',$v['edittime']);
			/*查询点赞数量*/
			$where_like['content_id'] = $v['id']; 
			$where_like['status'] = 1;/*已赞*/
			$lists[$k]['total_like'] = Db::name('like')->where($where_like)->count();
			/*查询点赞数量*/
			$where_comment['content_id'] = $v['id']; 
			$where_comment['status'] = 1;/*启用*/
			$lists[$k]['total_comment'] = Db::name('comment')->where($where_comment)->count();			
		}
		/*处理结果集*/
		$result_data = array(
			'total' => $total,
			'list'	=> $lists
		);
		return show(config('code.success'),'ok',$result_data);
	}

    /**
     * 健康学堂-内容详情
     */
    public function cmscon(){
		/*ID*/
		$id = input('content_id');
		if(empty($id)){
			return show(config('code.error'),'参数缺失',array());
		}
		$field = 'name,picture,description,content,video,author,source,visitor,edittime';
		$content = Db::name('content')->field($field)->where('id='.$id)->find();
		/*格式化时间*/
		$content['edittime'] = date('Y-m-d',$content['edittime']);
		/*查询点赞数量*/
		$where_like['content_id'] = $id; 
		$where_like['status'] = 1;/*已赞*/
		$content['total_like'] = Db::name('like')->where($where_like)->count();
		/*查询点赞数量*/
		$where_comment['content_id'] = $id; 
		$where_comment['status'] = 1;/*启用*/
		$content['total_comment'] = Db::name('comment')->where($where_comment)->count();	
		/*解析视频*/
		if(!empty($content['video'])){
			$video_arr = json_decode($content['video'],true);
			$content['video'] = $video_arr[0];
		}
		if($content){
			return show(config('code.success'),'ok',$content);
		}else{
			return show(config('code.error'),'error',array());
		}	
	}

    /**
     * 健康学堂-内容-同步游览量
     */
    public function cmscon_visitor(){
		/*ID*/
		$id = input('content_id');
		if(empty($id)){
			return show(config('code.error'),'参数缺失',array());
		}
		$result = Db::name('content')->where('id='.$id)->setInc('visitor');
		if($result){
			return show(config('code.success'),'ok',$result);
		}else{
			return show(config('code.error'),'error',array());
		}	
	}

    /**
     * 健康学堂-内容评论列表
     */
    public function cmscon_comment(){
		/*ID*/
		$id = input('content_id');
		if(empty($id)){
			return show(config('code.error'),'参数缺失',array());
		}
		/*获取参数*/
		$sline = !empty(input('sline'))?input('sline'):0;
		$limit = !empty(input('limit'))?input('limit'):config('default_limit');
		/*条数*/
		$limit = $sline.','.$limit;
		/*查询内容*/
		$where_list['content_id'] = $id;
		$where_list['status']  = 1;/*状态*/		
		/*排序*/	
		$order = '`order` desc,edittime desc';
		/*字段*/
		$field = 'id,name,description,picture,content,addtime';
		/*查询聊天名师*/
		$total = Db::name('comment')->where($where_list)->count();
		$list = Db::name('comment')->field($field)->where($where_list)->order($order)->limit($limit)->select();
		$lists = array();
		foreach($list as $k => $v){
			$lists[$k] = $v;
			/*格式化时间*/
			$lists[$k]['addtime'] = date('Y-m-d',$v['addtime']);		
		}
		/*处理结果集*/
		$result_data = array(
			'total' => $total,
			'list'	=> $lists
		);
		return show(config('code.success'),'ok',$result_data);	
	}

   /**
     * 社区-圈子
     */
    public function bbs_info(){
		/*获取参数*/
		$sline = !empty(input('sline'))?input('sline'):0;
		$limit = !empty(input('limit'))?input('limit'):config('default_limit');
		/*条数*/
		$limit = $sline.','.$limit;		
		/*查询*/
		$where_list['site_id'] = $this->site_id;
		$where_list['type']  = 1;/*类型  默认为1:医患圈|2:专家圈*/
		$where_list['status']  = 1;/*状态*/
		$search = input('search');/*搜索*/
		if(!empty($search)) {
			$where_list['concat(name,description)'] = array('like','%'.$search.'%');
		}			
		/*排序*/	
		$order = '`order` desc,edittime desc';
		/*字段*/
		$field = 'id,user_id,content,album,edittime';
		/*查询聊天名师*/
		$total = Db::name('bbs_info')->where($where_list)->count();
		$list = Db::name('bbs_info')->field($field)->where($where_list)->order($order)->limit($limit)->select();
		$lists = array();
		foreach($list as $k => $v){
			$lists[$k] = $v;
			/*格式化时间*/
			$lists[$k]['edittime'] = date('Y-m-d',$v['edittime']);
			/*查询点赞数量*/
			$where_like['bbs_info_id'] = $v['id']; 
			$where_like['status'] = 1;/*已赞*/
			$lists[$k]['total_like'] = Db::name('bbs_like')->where($where_like)->count();
			/*查询评论数量*/
			$where_comment['bbs_info_id'] = $v['id']; 
			$where_comment['status'] = 1;/*启用*/
			$lists[$k]['total_comment'] = Db::name('bbs_comment')->where($where_comment)->count();
			/*查询用户信息*/
			$user = Db::name('user')->field('realname,picture')->where('id='.$v['user_id'])->find();
			$user_picture_arr = explode(',',$user['picture']);
			$lists[$k]['user_info']['realname'] = $user['realname'];
			$lists[$k]['user_info']['picture'] = $user_picture_arr[0];			
			/*查询所有点赞用户*/
			$where_bbs_info['bbs_info_id'] = $v['id'];
			$where_bbs_info['status'] = 1;
			$bbs_like = Db::name('bbs_like')->field('user_id')->where($where_bbs_info)->select();
			if(!empty($bbs_like)){
				foreach($bbs_like as $k2=>$v2){
					/*查询用户身份信息*/
					$user = Db::name('user')->field('realname')->where('id='.$v2['user_id'])->find();
					$bbs_like[$k2]['realname'] = $user['realname'];			
				}
			}
			$lists[$k]['bbs_like'] = !empty($bbs_like)?$bbs_like:array();	
			$lists[$k]['bbs_comment'] = array();	
			/*查询信息评论列表*/
			$where_bbscomment_list['bbs_info_id'] = $v['id'];/*查询内容*/
			$where_bbscomment_list['status']  = 1;/*状态*/
			$list_bbscomment = Db::name('bbs_comment')->field('id,user_id,content,addtime')->where($where_bbscomment_list)->order('`order` desc,edittime desc')->limit(20)->select();
			if(!empty($list_bbscomment)){
				foreach($list_bbscomment as $k2 => $v2){
					/*查询用户身份信息*/
					$user = Db::name('user')->field('realname')->where('id='.$v2['user_id'])->find();
					$list_bbscomment[$k2]['realname'] = $user['realname'];						
					/*格式化时间*/
					$list_bbscomment[$k2]['addtime'] = date('Y-m-d',$v2['addtime']);		
				}
			}
			$lists[$k]['bbs_comment'] = !empty($list_bbscomment)?$list_bbscomment:array();	
		}
		/*处理结果集*/
		$result_data = array(
			'total' => $total,
			'list'	=> $lists
		);
		return show(config('code.success'),'ok',$result_data);
	}

   /**
     * 社区-信息-所有点赞
     */
    public function bbs_likeall(){
		/*ID*/
		$id = input('bbs_info_id');
		if(empty($id)){
			return show(config('code.error'),'参数缺失',array());
		}
		/*查询初始状态*/
		$where['bbs_info_id'] = $id;
		$where['status'] = 1;
		$bbs_like = Db::name('bbs_like')->field('user_id')->where($where)->select();
		/*查询用户身份信息*/
		foreach($bbs_like as $k=>$v){
			$user = Db::name('user')->field('realname')->where('id='.$v['user_id'])->find();
			$bbs_like[$k]['realname'] = $user['realname'];			
		}
		/*返回结果集*/
		if($bbs_like){
			return show(config('code.success'),'ok',$bbs_like);
		}else{
			return show(config('code.error'),'error',array());
		}		
	}

    /**
     * 社区-圈子信息评论列表
     */
    public function bbscon_comment(){
		/*ID*/
		$id = input('bbs_info_id');
		if(empty($id)){
			return show(config('code.error'),'参数缺失',array());
		}
		/*获取参数*/
		$sline = !empty(input('sline'))?input('sline'):0;
		$limit = !empty(input('limit'))?input('limit'):config('default_limit');
		/*条数*/
		$limit = $sline.','.$limit;
		/*查询内容*/
		$where_list['bbs_info_id'] = $id;
		$where_list['status']  = 1;/*状态*/		
		/*排序*/	
		$order = '`order` desc,edittime desc';
		/*字段*/
		$field = 'id,name,description,picture,content,addtime';
		/*查询聊天名师*/
		$total = Db::name('bbs_comment')->where($where_list)->count();
		$list = Db::name('bbs_comment')->field($field)->where($where_list)->order($order)->limit($limit)->select();
		$lists = array();
		foreach($list as $k => $v){
			$lists[$k] = $v;
			/*格式化时间*/
			$lists[$k]['addtime'] = date('Y-m-d',$v['addtime']);		
		}
		/*处理结果集*/
		$result_data = array(
			'total' => $total,
			'list'	=> $lists
		);
		return show(config('code.success'),'ok',$result_data);	
	}




}