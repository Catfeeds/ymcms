<?php
namespace app\web\controller\medical;
use app\web\controller\Common;
use think\Request;
use think\Db;
/*用户管理中心*/
class User extends Common {
	public $pageHead;/*定义页面头部*/
	public $pageFoot;/*定义页面底部*/
	public $temp_id;/*模板ID*/
	
	/*构造方法*/
	public function _initialize() {
		/*重载父类构造方法*/
		parent::_initialize();		
	}

	/*用户管理 start*/
	/*用户列表*/
	public function lists(){
		/*获取参数*/
		$limit = input('limit');
		$search = input('search');
		$this->assign('limit',$limit);
		$this->assign('search',$search);
		$pagelimit = !empty($limit)?$limit:config('paginate.list_rows'); /*每页条数*/
		if(!empty($search)){
			$where_user_list['concat(name,nickname)'] = array('like','%'.$search.'%'); /*搜索*/
		}
		/*状态搜索*/
		$status = !empty(input('status'))?input('status'):0;
		$this->assign('status',$status);
		if($status != 0){
			$where_user_list['status'] = $status;
		}		
		/*查询管理员*/
		$where_user_list['group_ids'] = 2;/*用户*/
		/*分页参数*/
		$paginate = array('query' => array( /*url额外参数*/
			'limit' => $limit,		/*每页条数*/
			'search' => $search,	/*搜索*/
		));
		$list = Db::name('user')->where($where_user_list)->paginate($pagelimit, false, $paginate);
		$lists = array();
		foreach($list as $k => $v){
			$lists[$k] = $v;	
			/*头像*/
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
		$page = $list->render();/*获取分页显示*/
		$this->assign('list', $lists);
		$this->assign('page', $page);
		/*分配变量*/
		$this->assign('title', '用户列表'); /*页面标题*/
		$this->assign('keywords', '用户列表'); /*页面关键词*/
		$this->assign('description', '用户列表'); /*页面描述*/
		/*调用模板*/
		return $this->fetch(TEMP_FETCH);
	}
	/*编辑用户*/
	public function edit(){
		/*查询用户管理员组*/
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
			$this->assign('title', '编辑用户'); /*页面标题*/
			$this->assign('keywords', '编辑用户'); /*页面关键词*/
			$this->assign('description', '编辑用户'); /*页面描述*/
		} else {
			/*添加*/
			/*组装post提交变量*/
			$submit = array('name' => '确认添加', 'url' => '?a=add');
			$this->assign('submit', $submit);
			/*分配变量*/
			$this->assign('title', '添加用户'); /*页面标题*/
			$this->assign('keywords', '添加用户'); /*页面关键词*/
			$this->assign('description', '添加用户'); /*页面描述*/
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
						echo '<script>$(document).ready(function(){alertBox("'.$alert_success.'","'.url('user/lists').'");})</script>';
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
	/*删除用户*/
	public function del(){
		/*接收参数*/
		$user_id = input('id');/*用户ID*/
		$result = Db::name('user')->where('id='.$user_id)->delete();
		if ($result) {
			echo '<script>$(document).ready(function(){alertBox("恭喜，删除成功！","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
		} else {
			echo '<script>$(document).ready(function(){alertBox("抱歉，删除失败！","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
		}
	}
	/*诊所管理 end*/

	/*在线咨询记录管理 start*/
	/*在线咨询列表*/
	public function chat_doctor(){
		/*用户ID*/
		$id = input('id');
		$this->assign('id',$id);
		if(empty($id)){
			echo '<meta charset="utf-8" />';
			echo '<script>$(document).ready(function(){alertBox("用户参数缺失！","'.url('medical.clinic/doctor').'?clinic_id='.$clinic_id.'");})</script>';
			die();
		}		
		$expert = Db::name('user')->field('nickname,picture')->where('id='.$id)->find();
		$expert_picture_arr = explode(',',$expert['picture']);
		$expert['picture'] = $expert_picture_arr[0];	
		$this->assign('expert',$expert);		
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
		$where_user_list['user_to_type'] = 2;/*信息接受人类型*/
		/*分页参数*/
		$paginate = array('query' => array( /*url额外参数*/
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
					$query->where('user_from_type',3)->whereor('user_to_type',3);/*用户*
				})->paginate($pagelimit,false,$paginate);
		*/			
		$lists = array();
		foreach($list as $k => $v){
			$lists[$k] = $v;
			/*查询用户身份信息*/
			$expert = Db::name('user')->field('nickname,picture')->where('id='.$v['user_to_id'])->find();
			$expert_picture_arr = explode(',',$expert['picture']);
			$lists[$k]['nickname'] = $expert['nickname'];
			$lists[$k]['picture'] = $expert_picture_arr[0];
			/*获取最近一条聊天信息*/
			$GLOBALS['expert_id'] = $v['user_to_id'];
			$chat_expert_list = Db::name('chat')->where('type=1')->where(function($query){
						$query->where('user_from_id',$GLOBALS['expert_id'])->whereor('user_to_id',$GLOBALS['expert_id']);
					})->where(function($query){
						$query->where('user_from_id',input('id'))->whereor('user_to_id',input('id'));/*用户*/
					})->order('edittime desc,id desc')->find();
			$lists[$k]['chat'] = $chat_expert_list;		
		}
		$page = $list->render();/*获取分页显示*/
		$this->assign('list',$lists);
		$this->assign('page',$page);
		/*分配变量*/
		$this->assign('title','在线咨询列表'); /*页面标题*/
		$this->assign('keywords','在线咨询列表'); /*页面关键词*/
		$this->assign('description','在线咨询列表'); /*页面描述*/
		/*调用模板*/
		return $this->fetch(TEMP_FETCH);
	}
	/*删除与该用户的所有聊天*/
	public function chat_doctordel(){
		/*接收参数*/
		$GLOBALS['user_id'] = input('user_id');/*用户ID*/
		$GLOBALS['doctor_id'] = input('doctor_id');/*医生ID*/
		$result = Db::name('chat')->where(function($query){
						$query->where('user_from_id',$GLOBALS['user_id'])->whereor('user_to_id',$GLOBALS['user_id']);/*用户id*/
					})->where(function($query){
						$query->where('user_from_id',$GLOBALS['doctor_id'])->whereor('user_to_id',$GLOBALS['doctor_id']);/*医生id*/
					})->where(function($query){
						$query->where('user_from_type',1)->whereor('user_to_type',1);/*用户类型*/
					})->where(function($query){
						$query->where('user_from_type',2)->whereor('user_to_type',2);/*医生类型*/
					})->delete();
		if($result){
			echo '<script>$(document).ready(function(){alertBox("恭喜，删除成功！","'.$_SERVER['HTTP_REFERER'].'");})</script>';
		}else{
			echo '<script>$(document).ready(function(){alertBox("抱歉，删除失败！","'.$_SERVER['HTTP_REFERER'].'");})</script>';
		}
	}
	/*在线咨询列表*/
	public function chat_doctor_list(){	
		/*用户ID*/
		$member_id = input('member_id');
		$this->assign('member_id',$member_id);
		if(empty($member_id)){
			echo '<meta charset="utf-8" />';
			echo '<script>$(document).ready(function(){alertBox("用户参数缺失！","'.url('user/lists').'");})</script>';
			die();
		}		
		$member = Db::name('user')->field('nickname,picture')->where('id='.$member_id)->find();
		$member_picture_arr = explode(',',$member['picture']);
		$member['picture'] = $member_picture_arr[0];	
		$this->assign('member',$member);	
		/*医生ID*/
		$doctor_id = input('doctor_id');
		$this->assign('doctor_id',$doctor_id);
		if(empty($doctor_id)){
			echo '<meta charset="utf-8" />';
			echo '<script>$(document).ready(function(){alertBox("医生参数缺失！","'.url('user/lists').'");})</script>';
			die();
		}		
		$doctor = Db::name('user')->field('nickname,picture')->where('id='.$doctor_id)->find();
		$doctor_picture_arr = explode(',',$doctor['picture']);
		$doctor['picture'] = $doctor_picture_arr[0];	
		$this->assign('doctor',$doctor);		
		/*获取参数*/
		$GLOBALS['member_id'] = $member_id;/*用户ID*/
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
			'member_id' => $member_id,/*用户ID*/
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
					$query->where('user_from_id',$GLOBALS['member_id'])->whereor('user_to_id',$GLOBALS['member_id']);/*用户id*/
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
			if($v['user_from_id'] == $member_id){
				$lists[$k]['nickname'] = $member['nickname'];
				$lists[$k]['picture'] = $member['picture'];
				$lists[$k]['doctor_from'] = 100;
			}else if($v['user_from_id'] == $doctor_id && !empty($doctor['nickname'])){
				$lists[$k]['nickname'] = $doctor['nickname'];
				$lists[$k]['picture'] = $doctor['picture'];	
			}
		}
		$page = $list->render();/*获取分页显示*/
		$this->assign('list',$lists);
		$this->assign('page',$page);
		/*分配变量*/
		$this->assign('title','在线咨询聊天记录'); /*页面标题*/
		$this->assign('keywords','在线咨询聊天记录'); /*页面关键词*/
		$this->assign('description','在线咨询聊天记录'); /*页面描述*/
		/*调用模板*/
		return $this->fetch(TEMP_FETCH);
	}
	/*删除聊天记录*/
	public function chat_doctor_listdel() {
		/*接收参数*/
		$id = input('chat_id'); /*聊天ID*/
		$result = Db::name('chat')->where('id='.$id)->delete();
		if ($result) {
			echo '<script>$(document).ready(function(){alertBox("恭喜，删除成功！","'.$_SERVER['HTTP_REFERER'].'");})</script>';
		} else {
			echo '<script>$(document).ready(function(){alertBox("抱歉，删除失败！","'.$_SERVER['HTTP_REFERER'].'");})</script>';
		}
	}	
	/*在线咨询记录管理 end*/	
	/*电子病历*/
	public function medical_record(){
		/*用户ID*/
		$user_id = input('user_id');
		$this->assign('user_id',$user_id);
		if(empty($user_id)){
			echo '<meta charset="utf-8" />';
			echo '<script>$(document).ready(function(){alertBox("用户参数缺失！","'.url('user/lists').'");})</script>';
			die();
		}
		$user = Db::name('user')->field('nickname,picture')->where('id='.$user_id)->find();
		$user_picture_arr = explode(',',$user['picture']);
		$user['picture'] = $user_picture_arr[0];	
		$this->assign('member',$user);			
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
			'user_id' => $user_id,/*诊所ID*/
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
		$this->assign('title','电子病历列表'); /*页面标题*/
		$this->assign('keywords','电子病历列表'); /*页面关键词*/
		$this->assign('description','电子病历列表'); /*页面描述*/
		/*调用模板*/
		return $this->fetch(TEMP_FETCH);
	}
	/*编辑病历*/
	public function medical_recordedit(){
		/*用户ID*/
		$user_id = input('user_id');
		$this->assign('user_id',$user_id);
		if(empty($user_id)){
			echo '<meta charset="utf-8" />';
			echo '<script>$(document).ready(function(){alertBox("用户参数缺失！","'.url('user/lists').'");})</script>';
			die();
		}
		$user = Db::name('user')->field('nickname,picture')->where('id='.$user_id)->find();
		$user_picture_arr = explode(',',$user['picture']);
		$user['picture'] = $user_picture_arr[0];	
		$this->assign('member',$user);		
		/*获取参数*/
		$id = input('id'); /*病历ID*/
		if($id){
			/*编辑*/
			/*查询参数*/
			$where['id'] = $id;
			$content = Db::name('medical_record')->where($where)->find();
			foreach ($content as $k => $v){
				/*解析就诊时间*/
				if ($k == 'jz_time' && !empty($content['jz_time'])){
					$content['jz_time'] = date('Y/m/d H:i',$v);
				}
				/*解析开药时间*/
				if ($k == 'ky_time' && !empty($content['ky_time'])){
					$content['ky_time'] = date('Y/m/d H:i',$v);
				}				
				/*解析病历相册*/
				if ($k == 'bl_album' && !empty($content['bl_album'])){
					$bl_album = explode(',', $v);
					$content['bl_album'] = array();
					foreach ($bl_album as $k2=>$v2){						$basename = explode('/',$v2);
						$basename = array_pop($basename);
						$content['bl_album'][$k2] = array('filename'=>$v2,'basename'=>$basename);
					}
				}
				/*解析报告相册*/
				if ($k == 'bg_album' && !empty($content['bg_album'])){
					$bl_album = explode(',', $v);
					$content['bg_album'] = array();
					foreach ($bl_album as $k2=>$v2) {						$basename = explode('/',$v2);
						$basename = array_pop($basename);
						$content['bg_album'][$k2] = array('filename'=>$v2,'basename'=>$basename);
					}
				}
				/*解析病历相册*/
				if ($k == 'cf_album' && !empty($content['cf_album'])){
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
			$submit = array('name'=>'确认编辑','url'=>'?id='.$id.'&user_id='.$user_id);
			$this->assign('submit',$submit);
			/*分配变量*/
			$this->assign('title', '编辑病历'); /*页面标题*/
			$this->assign('keywords', '编辑病历'); /*页面关键词*/
			$this->assign('description', '编辑病历'); /*页面描述*/
		}else{
			/*添加*/
			/*组装post提交变量*/
			$submit = array('name'=>'确认添加','url'=>'?user_id='.$user_id.'&a=add');
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
						echo '<script>$(document).ready(function(){alertBox("'.$alert_success.'","'.url('user/medical_record').'?user_id='.$user_id.'");})</script>';
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

	/*用户预约管理 start*/
	/*用户预约列表*/
	public function subscribe(){
		/*用户ID*/
		$id = input('id');
		$this->assign('id',$id);
		if(empty($id)){
			echo '<meta charset="utf-8" />';
			echo '<script>$(document).ready(function(){alertBox("用户参数缺失！","'.url('user/lists').'");})</script>';
			die();
		}		
		$user = Db::name('user')->field('nickname,picture')->where('id='.$id)->find();
		$user_picture_arr = explode(',',$user['picture']);
		$user['picture'] = $user_picture_arr[0];	
		$this->assign('member',$user);		
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
		$where_user_list['user_id'] = $id;/*用户ID*/
		/*分页参数*/
		$paginate = array('query' => array( /*url额外参数*/
			'id' => $id,/*用户ID*/
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
			/*查询医生身份信息*/
			$user = Db::name('user')->field('nickname,picture')->where('id='.$v['user_to_id'])->find();
			$user_picture_arr = explode(',',$user['picture']);
			$lists[$k]['nickname'] = $user['nickname'];
			$lists[$k]['picture'] = $user_picture_arr[0];		
		}
		$page = $list->render();/*获取分页显示*/
		$this->assign('list',$lists);
		$this->assign('page',$page);
		/*分配变量*/
		$this->assign('title','用户预约列表'); /*页面标题*/
		$this->assign('keywords','用户预约列表'); /*页面关键词*/
		$this->assign('description','用户预约列表'); /*页面描述*/
		/*调用模板*/
		return $this->fetch(TEMP_FETCH);
	}
	/*编辑用户预约*/
	public function subscribeedit(){	
		/*用户ID*/
		$user_id = input('user_id');
		$this->assign('user_id',$user_id);
		if(empty($user_id)){
			echo '<meta charset="utf-8" />';
			echo '<script>$(document).ready(function(){alertBox("用户参数缺失！","'.url('user/lists').'");})</script>';
			die();
		}
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
			$submit = array('name' => '确认编辑', 'url' => '?id='.$id);
			$this->assign('submit', $submit);
			/*分配变量*/
			$this->assign('title', '编辑用户预约'); /*页面标题*/
			$this->assign('keywords', '编辑用户预约'); /*页面关键词*/
			$this->assign('description', '编辑用户预约'); /*页面描述*/
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
				echo '<script>$(document).ready(function(){alertBox("原因不可为空..","'.$_SERVER['HTTP_REFERER'].'");})</script>';
			} else {
				/*组装参数*/
				$data = array(
					'site_id' 		=> $this->site_id, /*站点ID*/
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
					echo '<script>$(document).ready(function(){alertBox("'.$alert_success.'","'.$_SERVER['HTTP_REFERER'].'");})</script>';
				}else{
					echo '<script>$(document).ready(function(){alertBox("'.$alert_error.'","'.$_SERVER['HTTP_REFERER'].'");})</script>';
				}
			}
		} else {
			/*调用模板*/
			return $this->fetch(TEMP_FETCH);
		}
	}	
	/*删除用户预约*/
	public function subscribedel(){
		/*接收参数*/
		$id = input('id');/*预约ID*/
		$result = Db::name('subscribe')->where('id='.$id)->delete();
		if($result){
			echo '<script>$(document).ready(function(){alertBox("恭喜，删除成功！","'.$_SERVER['HTTP_REFERER'].'");})</script>';
		}else{
			echo '<script>$(document).ready(function(){alertBox("抱歉，删除失败！","'.$_SERVER['HTTP_REFERER'].'");})</script>';
		}
	}	
	/*用户预约管理 end*/	

	/*健康计划管理 start*/
	/*健康计划列表*/
	public function health_plan(){	
		/*用户ID*/
		$id = input('id');
		$this->assign('id',$id);
		if(empty($id)){
			echo '<meta charset="utf-8" />';
			echo '<script>$(document).ready(function(){alertBox("用户参数缺失！","'.url('user/lists').'");})</script>';
			die();
		}
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
		$where_user_list['user_to_id'] = $id;/*用户ID*/
		/*分页参数*/
		$paginate = array('query' => array( /*url额外参数*/
			'id' => $id,/*用户ID*/
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
			/*查询用户身份信息*/
			$user = Db::name('user')->field('nickname,picture')->where('id='.$v['user_to_id'])->find();
			$user_picture_arr = explode(',',$user['picture']);
			$lists[$k]['nickname'] = $user['nickname'];
			$lists[$k]['picture'] = $user_picture_arr[0];
			/*查询医生身份信息*/
			$doctor = Db::name('user')->field('nickname,picture')->where('id='.$v['user_id'])->find();
			$doctor_picture_arr = explode(',',$doctor['picture']);
			$lists[$k]['doctor_nickname'] = $doctor['nickname'];
			$lists[$k]['doctor_picture'] = $doctor_picture_arr[0];					
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
		/*用户ID*/
		$user_id = input('user_id');
		$this->assign('user_id',$user_id);
		if(empty($user_id)){
			echo '<meta charset="utf-8" />';
			echo '<script>$(document).ready(function(){alertBox("用户参数缺失！","'.url('user/lists').'");})</script>';
			die();
		}		
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
			$submit = array('name' => '确认编辑', 'url' => '?id='.$id.'&user_id='.$user_id);
			$this->assign('submit', $submit);
			/*分配变量*/
			$this->assign('title', '编辑健康计划'); /*页面标题*/
			$this->assign('keywords', '编辑健康计划'); /*页面关键词*/
			$this->assign('description', '编辑健康计划'); /*页面描述*/
		} else {
			echo '<meta charset="utf-8" />';
			echo '<script>$(document).ready(function(){alertBox("计划参数缺失！","'.url('user/health_plan').'?id='.$user_id.'");})</script>';
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
					echo '<script>$(document).ready(function(){alertBox("计划参数缺失！","'.url('user/health_plan').'?id='.$user_id.'");})</script>';
					die();
				}
				/*判断结果集*/
				if($result){
					echo '<script>$(document).ready(function(){alertBox("'.$alert_success.'","'.$_SERVER['HTTP_REFERER'].'");})</script>';
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
	
	/*用药管理 start*/
	/*用药列表*/
	public function pharmacy(){
		/*用户ID*/
		$id = input('id');
		$this->assign('id',$id);
		if(empty($id)){
			echo '<meta charset="utf-8" />';
			echo '<script>$(document).ready(function(){alertBox("用户参数缺失！","'.url('user/lists').'");})</script>';
			die();
		}
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
		$where_user_list['user_id'] = $id;/*用户ID*/
		/*分页参数*/
		$paginate = array('query' => array( /*url额外参数*/
			'id' => $id,/*用户ID*/
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
		$list = Db::name('pharmacy')->where($where_user_list)->where($where_user_list2)->order('time desc,id desc')->paginate($pagelimit,false,$paginate);			
		$lists = array();
		foreach($list as $k => $v){
			$lists[$k] = $v;
			/*查询用户身份信息*/
			$user = Db::name('user')->field('nickname,picture')->where('id='.$v['user_id'])->find();
			$user_picture_arr = explode(',',$user['picture']);
			$lists[$k]['nickname'] = $user['nickname'];
			$lists[$k]['picture'] = $user_picture_arr[0];
			/*治疗方案*/
			if(!empty($v['content'])){
				$lists[$k]['content'] = json_decode($v['content'],true);
			}		
		}
		$page = $list->render();/*获取分页显示*/
		$this->assign('list',$lists);
		$this->assign('page',$page);
		/*分配变量*/
		$this->assign('title','用药列表'); /*页面标题*/
		$this->assign('keywords','用药列表'); /*页面关键词*/
		$this->assign('description','用药列表'); /*页面描述*/
		/*调用模板*/
		return $this->fetch(TEMP_FETCH);
	}
	/*删除用药*/
	public function pharmacydel() {
		/*接收参数*/
		$id = input('id');/*用药ID*/
		$result = Db::name('pharmacy')->where('id='.$id)->delete();
		if($result){
			echo '<script>$(document).ready(function(){alertBox("恭喜，删除成功！","'.$_SERVER['HTTP_REFERER'].'");})</script>';
		}else{
			echo '<script>$(document).ready(function(){alertBox("抱歉，删除失败！","'.$_SERVER['HTTP_REFERER'].'");})</script>';
		}
	}	
	/*医生预约管理 end*/	
	
}