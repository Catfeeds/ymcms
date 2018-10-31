<?php
namespace app\api\controller\medical\v1;
use app\api\controller\Common;
use think\Controller;
use app\common\lib\exception\ApiException;
use app\common\lib\IAuth;
use think\Db;
/*医生端*/
class Doct extends Common{

    /**
     * 诊所注册
     */
    public function clinicadd(){	
		if(empty(input('name')) || empty(input('picture')) || empty(input('album')) || empty(input('business_pic')) || empty(input('license_pic')) || empty(input('identity_pic_a')) || empty(input('identity_pic_b')) || empty(input('purchase_pic')) || empty(input('province')) || empty(input('city')) || empty(input('area')) || empty(input('address')) || empty(input('coords')) || empty(input('tel'))){
			return show(config('code.error'),'参数缺失',array());
		}
		/*获取参数*/
		$nowtime = time();
		/*组装参数*/
		$data = array(
			'site_id'		=> $this->site_id,/*站点ID*/
			'name' 			=> input('name'),/*名称*/
			'picture' 		=> input('picture'),/*缩略图|logo*/
			'album' 		=> input('album'),/*相册|诊所照片*/
			'business_pic' 	=> input('business_pic'),/*营业执照*/
			'license_pic' 	=> input('license_pic'),/*医疗机构执业许可证*/
			'identity_pic_a'=> input('identity_pic_a'),/*被委托人身份证正面*/
			'identity_pic_b'=> input('identity_pic_b'),/*被委托人身份证反面*/
			'purchase_pic' 	=> input('purchase_pic'),/*采购委托书*/
			'province' 		=> input('province'),/*省*/
			'city' 			=> input('city'),/*市*/
			'area' 			=> input('area'),/*区*/
			'address' 		=> input('address'),/*地址*/
			'coords' 		=> input('coords'),/*坐标*/
			//'workday' 		=> input('workday'),/*上班时间*/
			'tel' 			=> input('tel'),/*电话*/
			//'order' 		=> input('order'),/*排序 默认为100*/
			//'status'		=> input('status'),/*状态 默认为1:启用|0:禁用*/
			'edittime' 		=> $nowtime,/*修改时间*/
		);
		if(!empty(input('description'))){
			$data['description'] = input('description');/*简介*/
		}
		if(!empty(input('content'))){
			$data['content'] = input('content');/*内容*/
		}			
		/*添加*/
		$data['addtime'] = $nowtime; /*添加时间*/
		/*插入数据*/
		$result = Db::name('clinic')->insert($data);
		$data['id'] = Db::name('clinic')->getLastInsID();
		/*判断结果集*/
		if($result){
			return show(config('code.success'),'ok',$data);
		}else{
			return show(config('code.error'),'error',array());
		}		
	}

   /**
     * 医学堂
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
		/*排序*/	
		$order = '`order` desc,edittime desc';
		/*选项卡*/
		if($tab == 2){
			$where_list['channel_id'] = array('in','25');/*视频*/
		}else if($tab == 1){
			$where_list['channel_id'] = array('in','21');/*文章*/
		}else{
			$where_list['channel_id'] = array('in','21,25');/*文章|视频*/
		}
		/*字段*/
		$category_id = 113;/*医学堂*/
		$field = 'id,name,description,picture,video,visitor,edittime';
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
			'title' => '医学堂',
			'table' => array('文章','视频'),
			'total' => $total,
			'list'	=> $lists
		);
		return show(config('code.success'),'ok',$result_data);
	}

    /**
     * 医学堂-内容详情
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
     * 医学堂-内容-同步游览量
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
     * 医学堂-内容评论列表
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
		if(empty(input('tab'))){
			return show(config('code.error'),'参数缺失',array());
		}		
		/*获取参数*/
		$sline = !empty(input('sline'))?input('sline'):0;
		$limit = !empty(input('limit'))?input('limit'):config('default_limit');
		/*条数*/
		$limit = $sline.','.$limit;		
		/*查询*/
		$where_list['site_id'] = $this->site_id;
		if(input('tab') == 1){
			$where_list['type']  = 1;/*类型  默认为1:医患圈|2:专家圈*/
		}else if(input('tab') == 2){
			$where_list['type']  = 2;/*类型  默认为1:医患圈|2:专家圈*/
		}else if(input('tab') == 3){
			$where_list['type']  = array('in','1,2');/*类型  默认为1:医患圈|2:专家圈*/
		}else{
			return show(config('code.error'),'选项卡参数非法',array());
		}
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