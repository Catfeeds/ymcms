<?php
namespace app\api\controller\medical\v1;
use app\api\controller\Common;
use think\Controller;
use app\common\lib\exception\ApiException;
use app\common\lib\Aes;
use app\common\lib\IAuth;
use app\common\lib\Alisms;
use think\Db;
/*专家端-登录*/
class Expertauth extends Auth{

    /**
     * 初始化
     */
    public function _initialize(){
		/*继承父类构造方法*/
        parent::_initialize();
		/*验证用户类型是否为专家*/
		$user = Db::name('user')->field('group_ids')->where('id='.$this->user['id'])->find();
        if(!empty($user)) {
			if($user['group_ids'] != 5){
				throw new ApiException('身份类型不匹配');/*专家*/
			}
		}else{
			return show(config('code.error'),'账号信息缺失',array());
		}		
    }

    /**
     * 个人中心-获取用户信息
     */
    public function read(){
		/*学历*/
		$user = Db::name('user')->field('education')->where('id='.$this->user['id'])->find();
		$this->user['education'] = $user['education'];
		return show(config('code.success'),'ok',$this->user);
	}

    /**
     * 个人中心-修改个人信息
     */
    public function useredit(){
		/*用户ID*/
		$id = $this->user['id'];
		if(empty($id)){
			return show(config('code.error'),'参数缺失',array());
		}
		$nowtime = time();
		if(!empty(input('nickname'))){
			$data['nickname'] = input('nickname');
		}
		if(!empty(input('realname'))){
			$data['realname'] = input('realname');
		}
		if(!empty(input('picture'))){
			$data['picture'] = input('picture');
		}
		if(!empty(input('sex'))){
			$data['sex'] = input('sex');
		}
		if(!empty(input('year'))){
			$data['year'] = input('year');
		}					
		if(!empty(input('month'))){
			$data['month'] = input('month');
		}	
		if(!empty(input('day'))){
			$data['day'] = input('day');
		}
		/*学历*/
		if(!empty(input('education'))){
			$data['education'] = input('education');
		}
		$data['edittime'] = $nowtime;
		$result = Db::name('user')->where('id='.$id)->update($data);
		if($result){
			return show(config('code.success'),'ok',$data);
		}else{
			return show(config('code.error'),'error',array());
		}	
	}

    /**
     * 个人中心-修改密码
     */
    public function passedit(){
		/*用户ID*/
		$id = $this->user['id'];
		if(empty($id) || empty(input('old_pass')) || empty(input('new_pass'))){
			return show(config('code.error'),'参数缺失',array());
		}
		$user = Db::name('user')->field('password,addtime')->where('id='.$id)->find();
		$nowtime = time();
		if(md5(md5($user['addtime'].input('old_pass'))) == $user['password']){
			$data['password'] = md5(md5($user['addtime'].input('new_pass')));
			$data['edittime'] = $nowtime;
			$result = Db::name('user')->where('id='.$id)->update($data);
			if($result){
				return show(config('code.success'),'ok',$data);
			}else{
				return show(config('code.error'),'error',array());
			}	
		}else{
			return show(2,'密码错误',array());
		}
	}	

	/**
	 * 个人中心-手机绑定
	 */
    public function phonebind(){
		/*参数校验*/
		$type 		= input('type');
		$phone 		= input('phone');
		$code	 	= input('code');
        if(empty($type)){
            return show(config('code.error'),'身份类型不合法','',404);
        }		
        if(empty($phone)){
            return show(config('code.error'),'手机不合法','',404);
        }
        if(empty($code)){
            return show(config('code.error'),'验证码不合法','',404);
        }else{
            /*validate严格校验*/
            $sms_code = Alisms::getInstance()->checkSmsIdentify($phone);
            if($code != $sms_code){
                return show(config('code.error'),'手机短信验证码错误','',404);
            }
		}
		/*校验类型*/
		if($type == 1){
			$group_id = 2;/*用户*/
		}else if($type == 2){
			$group_id = 4;/*医生*/
		}else if($type == 3){
			$group_id = 5;/*专家*/
		}		
        /*查询这个手机号是否存在*/
		$where['site_ids']	= 1;
		$where['group_ids']	= $group_id;
		$where['phone']		= $phone;
		$user = Db::name('user')->where($where)->find();
        if(empty($user)){
			return show(config('code.error'),'用户不存在',array());			
        }else{
			$nowtime = time();
			$data = array(
				'phone' 		=> $phone,
				'edittime' 		=> $nowtime,/*修改时间*/
			);
			$result_update = Db::name('user')->where($where)->update($data);
			if($result_update){
				return show(config('code.success'),'ok',$data);
			}else {
				return show(config('code.error'),'error',array());
			}
		}
	}

    /**
     * 系统消息列表
     */
    public function message(){
		/*用户ID*/
		$id = $this->user['id'];
		if(empty($id)){
			return show(config('code.error'),'参数缺失',array());
		}		
		/*获取参数*/
		$sline = !empty(input('sline'))?input('sline'):0;
		$limit = !empty(input('limit'))?input('limit'):config('default_limit');
		/*条数*/
		$limit = $sline.','.$limit;
		/*查询内容*/
		$where_list['type']  = array('in','3,4');/*1:用户端 | 2:医生端 | 3:专家端 | 4:所有终端*/	
		$where_list['status']  = 1;/*状态*/		
		/*排序*/	
		$order = '`order` desc,edittime desc';
		/*字段*/
		$field = 'id,name,description,picture,content,edittime';
		/*查询聊天名师*/
		$total = Db::name('message')->where($where_list)->count();
		$list = Db::name('message')->field($field)->where($where_list)->order($order)->limit($limit)->select();
		$lists = array();
		foreach($list as $k => $v){
			$lists[$k] = $v;
			/*格式化时间*/
			$lists[$k]['edittime'] = date('Y-m-d',$v['edittime']);	
			/*查看用户操作状态*/
			$where_message_log['user_id'] = $id;
			$where_message_log['message_id'] = $v['id'];
			$message_log = Db::name('message_log')->field('id,status')->where($where_message_log)->find();
			if(empty($message_log)){
				$lists[$k]['log_status'] = 0;/*未读*/
			}else{
				if($message_log['status']==1){
					$lists[$k]['log_status'] = 1;/*已读*/
				}else if($message_log['status']==2){
					$lists[$k]['log_status'] = 2;/*已删除*/
				}
			}
		}
		/*处理结果集*/
		$result_data = array(
			'total' => $total,
			'list'	=> $lists
		);
		return show(config('code.success'),'ok',$result_data);	
	}

    /**
     * 系统消息-未读统计
     */
    public function unmessage(){
		/*用户ID*/
		$id = $this->user['id'];
		if(empty($id)){
			return show(config('code.error'),'参数缺失',array());
		}
		/*查询该用户已读或删除的记录*/
		$where_message_log['user_id'] = $id;
		$where_message_log['status'] = array('>',0); 
		$read_arr = Db::name('message_log')->where($where_message_log)->count();
		/*查询系统通知总数目*/
		$where_list['type']  = array('in','3,4');/*1:用户端 | 2:医生端 | 3:专家端 | 4:所有终端*/	
		$where_list['status'] = 1;
		$total = Db::name('message')->where($where_list)->count();
		$total = $total - $read_arr;
		if($total < 0){
			$total = 0;
		}
		/*处理结果集*/
		$result_data = array(
			'total' => $total
		);
		return show(config('code.success'),'ok',$result_data);	
	}

    /**
     * 系统消息-标记已读(支持批量)
     */
    public function messagered(){
		$id = $this->user['id'];/*用户ID*/	
		$ids = input('message_id');/*ID支持批量*/
		if(empty($ids) || empty($id)){
			return show(config('code.error'),'参数缺失',array());
		}
		$ids = trim($ids,',');
		$now_time = time();
		$ids_arr = explode(',',$ids);
		if(!empty($ids_arr)){
			$result = 0;
			foreach($ids_arr as $k=>$v){
				/*查询用户记录情况*/
				$where['user_id'] = $id;
				$where['message_id'] = $v;
				$message_log = Db::name('message_log')->field('id,status')->where($where)->find();
				$data['status'] 	= 1;
				$data['edittime'] 	= $now_time;
				if(empty($message_log)){
					$data['user_id'] 	= $id;
					$data['message_id'] = $v;
					$data['addtime'] 	= $now_time;
					$result_insert = Db::name('message_log')->insert($data);
					$result = $result+$result_insert;
				}else{
					$result_update = Db::name('message_log')->where($where)->update($data);
					$result = $result+$result_update;
				}	
			}
			if($result){
				return show(config('code.success'),'ok',$result);
			}else{
				return show(config('code.error'),'error',array());
			}			
		}else{
			return show(config('code.error'),'参数格式错误',array());
		}
	}

    /**
     * 系统消息-消息删除(支持批量)
     */
    public function messagedel(){
		$id = $this->user['id'];/*用户ID*/
		$ids = input('message_id');/*ID支持批量*/
		if(empty($ids) || empty($id)){
			return show(config('code.error'),'参数缺失',array());
		}
		$ids = trim($ids,',');
		$now_time = time();
		$ids_arr = explode(',',$ids);
		if(!empty($ids_arr)){
			$result = 0;
			foreach($ids_arr as $k=>$v){
				/*查询用户记录情况*/
				$where['user_id'] = $id;
				$where['message_id'] = $v;
				$message_log = Db::name('message_log')->field('id,status')->where($where)->find();
				$data['status'] 	= 2;
				$data['edittime'] 	= $now_time;
				if(empty($message_log)){
					$data['user_id'] 	= $id;
					$data['message_id'] = $v;
					$data['addtime'] 	= $now_time;
					$result_insert = Db::name('message_log')->insert($data);
					$result = $result+$result_insert;
				}else{
					$result_update = Db::name('message_log')->where($where)->update($data);
					$result = $result+$result_update;
				}	
			}
			if($result){
				return show(config('code.success'),'ok',$result);
			}else{
				return show(config('code.error'),'error',array());
			}			
		}else{
			return show(config('code.error'),'参数格式错误',array());
		}
	}

    /**
     * 在线|离线状态查询
     */
    public function is_online(){
		/*用户ID*/
		$id = $this->user['id'];
		if(empty($id)){
			return show(config('code.error'),'参数缺失',array());
		}
		$user = Db::name('user')->field('is_online')->where('id='.$id)->find();
		if(empty($user)){
			return show(config('code.error'),'用户信息缺失',array());
		}
		$is_online = !empty($user['is_online'])?$user['is_online']:0;
		return show(config('code.success'),'ok',$is_online);
	}

    /**
     * 在线|离线状态切换
     */
    public function is_onlineedit(){
		/*用户ID*/
		$id = $this->user['id'];
		if(empty($id)){
			return show(config('code.error'),'参数缺失',array());
		}
		$user = Db::name('user')->field('is_online')->where('id='.$id)->find();
		if(empty($user)){
			return show(config('code.error'),'用户信息缺失',array());
		}
		$is_online = !empty($user['is_online'])?$user['is_online']:0;
		if($is_online == 1){
			$data['is_online'] = 0;
		}else{
			$data['is_online'] = 1;
		}
		$nowtime = time();
		$data['edittime'] = $nowtime;
		$where['id'] = $id;
		$result = Db::name('user')->where($where)->update($data);
		if($result){
			return show(config('code.success'),'ok',$data);
		}else{
			return show(config('code.error'),'error',array());
		}
	}

    /**
     * 咨询-通讯录
     */
    public function maillist(){
		/*用户ID*/
		$id = $this->user['id'];
		if(empty($id)){
			return show(config('code.error'),'参数缺失',array());
		}
		$tab = 1;
		/*获取参数*/
		$sline = !empty(input('sline'))?input('sline'):0;
		$limit = !empty(input('limit'))?input('limit'):config('default_limit')+88;
		/*条数*/
		$limit = $sline.','.$limit;
		/*查询内容*/
		$where_maillist['user_id'] = $id;
		$where_maillist['status'] = 1;
		$maillist = Db::name('maillist')->field('user_to_id')->where($where_maillist)->select();	
		$user_to_id_str = '';
		foreach($maillist as $k=>$v){
			/*查询被添加人的身份*/
			$user = Db::name('user')->field('group_ids')->where('id='.$v['user_to_id'])->find();
			if($tab == 1 && !empty($user) && $user['group_ids']==4){
				$user_to_id_str .= ','.$v['user_to_id'];/*医生*/
			}
		}
		$user_to_id_str = trim($user_to_id_str,',');
		$where_maillist2['user_to_id'] = $id;
		$where_maillist2['status'] = 1;
		$maillist2 = Db::name('maillist')->field('user_id')->where($where_maillist2)->select();	
		foreach($maillist2 as $k=>$v){
			/*查询被添加人的身份*/
			$user = Db::name('user')->field('group_ids')->where('id='.$v['user_id'])->find();
			if($tab == 1 && !empty($user) && $user['group_ids']==4){
				$user_to_id_str .= ','.$v['user_id'];/*医生*/
			}
		}		
		$user_to_id_str = trim($user_to_id_str,',');
		$where_list['id'] = array('in',$user_to_id_str);
		$where_list['status']  = 2;/*状态*/		
		/*排序*/
		$order = '`realname` asc';
		/*字段*/
		$field = 'id,realname,picture,job';
		/*查询聊天名师*/
		$total = Db::name('user')->where($where_list)->count();
		$list = Db::name('user')->field($field)->where($where_list)->order($order)->limit($limit)->select();
		$lists = array();
		foreach($list as $k => $v){
			$lists[$k] = $v;
			/*解析图片*/
			$user_picture_arr = explode(',',$v['picture']);
			$lists[$k]['picture'] = $user_picture_arr[0];
			if(!empty($v['realname'])){
				$lists[$k]['AZ'] = getfirstchar($v['realname']);
			}else{
				$lists[$k]['AZ'] = 'WM';
			}
		}
		/*按字母分组
		$list_arr = array();
		$az_arr = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','WM');
		foreach($az_arr as $k2=>$v2){
			foreach($lists as $k=>$v){
				if($v['AZ'] == $v2){
					$list_arr[$v2][$k] = $v;
				}
			}
		}
		*/
		/*处理结果集*/
		$result_data = array(
			'total' => $total,
			'list'	=> $lists
		);
		return show(config('code.success'),'ok',$result_data);	
	}

    /**
     * 咨询-添加医生好友
     */
    public function cms_maillistadd(){
		/*用户ID*/
		$id = $this->user['id'];
		if(empty($id) || empty(input('doctor_id'))){
			return show(config('code.error'),'参数缺失',array());
		}
		$nowtime = time();
		$data = array(
			'user_id' 		=> $this->user['id'],
			'user_to_id' 	=> input('doctor_id'),
			'addtime' 		=> $nowtime,
			'edittime' 		=> $nowtime,	
		);
		/*查询*/	
		$where['user_id'] = $this->user['id'];
		$where['user_to_id'] = input('doctor_id');
		$where2['user_id'] = input('doctor_id');
		$where2['user_to_id'] = $this->user['id'];
		$GLOBALS['user_id'] = $id;
		$maillist = Db::name('maillist')->where(function($query) {
			$query->where('user_id',$GLOBALS['user_id'])->whereor('user_id',input('doctor_id'));
		})->where(function ($query) {
			$query->where('user_to_id',$GLOBALS['user_id'])->whereor('user_to_id',input('doctor_id'));
		})->find();			
		//$maillist = Db::name('maillist')->field('id,status')->where($where)->whereor($where2)->find();
		if(empty($maillist)){
			/*插入数据*/
			$result = Db::name('maillist')->insert($data);
			$data['id'] = Db::name('maillist')->getLastInsID();
		}else{
			if($maillist['status'] == 1){
				return show(config('code.success'),'ok,已经是好友',$data);
			}else{
				$data['status'] = 1;
				$result = Db::name('maillist')->where('id='.$maillist['id'])->update($data);
			}
		}
		if($result){
			return show(config('code.success'),'ok',$data);
		}else{
			return show(config('code.error'),'error',array());
		}
	}

    /**
     * 咨询-聊天
     */

    public function chat(){
		/*用户ID*/
		$id = $this->user['id'];
		if(empty($id)){
			return show(config('code.error'),'参数缺失',array());
		}		
		/*获取参数*/	
		$sline = !empty(input('sline'))?input('sline'):0;
		$limit = !empty(input('limit'))?input('limit'):config('default_limit');
		/*查询聊天名师*/
		$where_user_list['user_from_id'] = $id;/*信息发件人*/
		$where_user_list['user_to_type'] = 2;/*信息接受人类型*/
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
		/*排序*/	
		$order = 'edittime desc,id desc';
		/*条数*/
		$limit = $sline.','.$limit;
		/*查询*/
		$total = Db::name('chat')->distinct(true)->field('user_to_id')->where($where_user_list)->where($where_user_list2)->select();
		$total = count($total);
		$list = Db::name('chat')->distinct(true)->field('user_to_id')->where($where_user_list)->where($where_user_list2)->order($order)->limit($limit)->select();
		$lists = array();
		foreach($list as $k => $v){
			$lists[$k] = $v;
			/*查询用户身份信息*/
			$expert = Db::name('user')->field('realname,picture')->where('id='.$v['user_to_id'])->find();
			$expert_picture_arr = explode(',',$expert['picture']);
			$lists[$k]['realname'] = $expert['realname'];
			$lists[$k]['picture'] = $expert_picture_arr[0];
			/*获取最近一条聊天信息*/
			$GLOBALS['expert_id'] = $v['user_to_id'];
			$GLOBALS['user_id'] = $id;
			$chat_expert_list = Db::name('chat')->field('id,user_from_id,user_from_type,user_to_id,user_to_type,type,content,addtime')->where('type=1')->where(function($query){
						$query->where('user_from_id',$GLOBALS['expert_id'])->whereor('user_to_id',$GLOBALS['expert_id']);
					})->where(function($query){
						$query->where('user_from_id',$GLOBALS['user_id'])->whereor('user_to_id',$GLOBALS['user_id']);/*用户*/
					})->order('edittime desc,id desc')->find();
			$lists[$k]['chat'] = $chat_expert_list;
			$lists[$k]['chat']['addtime'] = date('y-m-d',$chat_expert_list['addtime']);	
		}
		/*处理结果集*/
		$result_data = array(
			'total' => $total,
			'list'	=> $lists
		);
		return show(config('code.success'),'ok',$result_data);
	}

    /**
     * 咨询-聊天-删除
     */
    public function chatdel(){
		/*用户ID*/
		$id = $this->user['id'];
		if(empty($id) || empty(input('doctor_id'))){
			return show(config('code.error'),'参数缺失',array());
		}			
		/*接收参数*/
		$GLOBALS['user_id'] = $id;/*用户ID*/
		$GLOBALS['doctor_id'] = input('doctor_id');/*医生ID*/
		$result = Db::name('chat')->where(function($query){
						$query->where('user_from_id',$GLOBALS['user_id'])->whereor('user_to_id',$GLOBALS['user_id']);/*用户id*/
					})->where(function($query){
						$query->where('user_from_id',$GLOBALS['doctor_id'])->whereor('user_to_id',$GLOBALS['doctor_id']);/*医生id*/
					})->where(function($query){
						$query->where('user_from_type',3)->whereor('user_to_type',3);/*专家类型*/
					})->where(function($query){
						$query->where('user_from_type',2)->whereor('user_to_type',2);/*医生类型*/
					})->delete();
		if($result){
			return show(config('code.success'),'ok',$result);
		}else{
			return show(config('code.error'),'error',array());
		}
	}

    /**
     * 咨询-聊天-添加
     */
    public function chatadd(){
		/*用户ID*/
		$id = $this->user['id'];
		if(empty($id) || empty(input('doctor_id')) || empty(input('type')) || empty(input('content'))){
			return show(config('code.error'),'参数缺失',array());
		}
		$nowtime = time();
		$data = array(
			'user_from_id' 	=> $this->user['id'],
			'user_from_type'=> 3,
			'user_to_id' 	=> input('doctor_id'),
			'user_to_type' 	=> 2,
			'type' 			=> input('type'),
			'content' 		=> input('content'),
			'addtime' 		=> $nowtime,
			'edittime' 		=> $nowtime,	
		);
		/*插入数据*/
		$result = Db::name('chat')->insert($data);
		$data['id'] = Db::name('chat')->getLastInsID();
		if($result){
			return show(config('code.success'),'ok',$data);
		}else{
			return show(config('code.error'),'error',array());
		}
	}

}