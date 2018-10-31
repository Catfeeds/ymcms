<?php
namespace app\web\controller\medical;
use app\web\controller\Common;
use think\Request;
use think\Db;
/*专家管理中心*/
class Expert extends Common {
	public $pageHead;/*定义页面头部*/
	public $pageFoot;/*定义页面底部*/
	public $temp_id;/*模板ID*/
	
	/*构造方法*/
	public function _initialize() {
		/*重载父类构造方法*/
		parent::_initialize();		
	}

	/*专家管理 start*/
	/*专家列表*/
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
		$where_user_list['group_ids'] = 5;/*专家*/
		/*分页参数*/
		$paginate = array('query' => array( /*url额外参数*/
			'status' => $status,	/*状态*/
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
		$this->assign('title', '专家列表'); /*页面标题*/
		$this->assign('keywords', '专家列表'); /*页面关键词*/
		$this->assign('description', '专家列表'); /*页面描述*/
		/*调用模板*/
		return $this->fetch(TEMP_FETCH);
	}
	/*编辑专家*/
	public function edit(){
		/*查询专家管理员组*/
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
			$this->assign('title', '编辑专家'); /*页面标题*/
			$this->assign('keywords', '编辑专家'); /*页面关键词*/
			$this->assign('description', '编辑专家'); /*页面描述*/
		} else {
			/*添加*/
			/*组装post提交变量*/
			$submit = array('name' => '确认添加', 'url' => '?a=add');
			$this->assign('submit', $submit);
			/*分配变量*/
			$this->assign('title', '添加专家'); /*页面标题*/
			$this->assign('keywords', '添加专家'); /*页面关键词*/
			$this->assign('description', '添加专家'); /*页面描述*/
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
						echo '<script>$(document).ready(function(){alertBox("'.$alert_success.'","'.url('expert/lists').'");})</script>';
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
	/*审核专家*/
	public function check(){
		/*接收参数*/
		$user_id = input('id');/*专家ID*/
		$data['status'] = 2; /*状态*/
		$result = Db::name('user')->where('id='.$user_id)->update($data);
		if ($result) {
			echo '<script>$(document).ready(function(){alertBox("恭喜，审核成功！","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
		} else {
			echo '<script>$(document).ready(function(){alertBox("抱歉，审核失败！","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
		}
	}	
	/*删除专家*/
	public function del(){
		/*接收参数*/
		$user_id = input('id');/*专家ID*/
		$result = Db::name('user')->where('id='.$user_id)->delete();
		if ($result) {
			echo '<script>$(document).ready(function(){alertBox("恭喜，删除成功！","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
		} else {
			echo '<script>$(document).ready(function(){alertBox("抱歉，删除失败！","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
		}
	}
	/*诊所管理 end*/

	/*咨询专家记录管理 start*/
	/*咨询专家列表*/
	public function chat_doctor(){
		/*专家ID*/
		$id = input('id');
		$this->assign('id',$id);
		if(empty($id)){
			echo '<meta charset="utf-8" />';
			echo '<script>$(document).ready(function(){alertBox("专家参数缺失！","'.url('medical.clinic/doctor').'?clinic_id='.$clinic_id.'");})</script>';
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
		$this->assign('title','医生咨询列表'); /*页面标题*/
		$this->assign('keywords','医生咨询列表'); /*页面关键词*/
		$this->assign('description','医生咨询列表'); /*页面描述*/
		/*调用模板*/
		return $this->fetch(TEMP_FETCH);
	}
	/*删除与该专家的所有聊天*/
	public function chat_doctordel(){
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
	public function chat_doctor_list(){	
		/*专家ID*/
		$expert_id = input('expert_id');
		$this->assign('expert_id',$expert_id);
		if(empty($expert_id)){
			echo '<meta charset="utf-8" />';
			echo '<script>$(document).ready(function(){alertBox("专家参数缺失！","'.url('medical.clinic/doctor').'?clinic_id='.$clinic_id.'");})</script>';
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
		$this->assign('title','医生咨询聊天记录'); /*页面标题*/
		$this->assign('keywords','医生咨询聊天记录'); /*页面关键词*/
		$this->assign('description','医生咨询聊天记录'); /*页面描述*/
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
	/*咨询专家记录管理 end*/	

	
		
}