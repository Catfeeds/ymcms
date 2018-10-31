<?php
namespace app\api\controller\medical\v1;
use app\api\controller\Common;
use think\Controller;
use app\common\lib\exception\ApiException;
use app\common\lib\Aes;
use app\common\lib\IAuth;
use app\common\lib\Alisms;
use think\Db;
/*用户端-登录*/
class Userauth extends Auth{

    /**
     * 初始化
     */
    public function _initialize(){
		/*继承父类构造方法*/
        parent::_initialize();
		/*验证用户类型是否为用户*/
		$user = Db::name('user')->field('group_ids')->where('id='.$this->user['id'])->find();
        if(!empty($user)) {
			if($user['group_ids'] != 2){
				throw new ApiException('身份类型不匹配');/*用户*/
			}
		}else{
			return show(config('code.error'),'账号信息缺失',array());
		}
	}

    /**
     * 个人中心-获取用户信息
     */
    public function read(){
		/*非常隐私、需要加密处理
        $obj = new Aes();
        return show(config('code.success'),'ok',$obj->encrypt($this->user));
    	*/
		return show(config('code.success'),'ok',$this->user);
	}

    /**
     * 个人中心-我的预约
     */
    public function subscribe(){
		/*用户ID*/
		$id = $this->user['id'];
		/*获取参数*/	
		$sline = !empty(input('sline'))?input('sline'):0;
		$limit = !empty(input('limit'))?input('limit'):config('default_limit');
		/*查询聊天名师*/
		$where_list['site_id'] = $this->site_id;/*站点ID*/
		$where_list['user_id'] = $id;/*用户ID*/
		/*排序*/	
		$order = 'time desc,id desc';
		/*条数*/
		$limit = $sline.','.$limit;
		/*字段*/
		$field = 'id,user_id,user_to_id,time,addtime,status';
		/*查询聊天名师*/
		$total = Db::name('subscribe')->where($where_list)->count();
		$list = Db::name('subscribe')->field($field)->where($where_list)->order($order)->limit($limit)->select();
		$lists = array();
		foreach($list as $k => $v){
			$lists[$k] = $v;
			/*查询医生身份信息*/
			$user = Db::name('user')->field('nickname,picture')->where('id='.$v['user_to_id'])->find();
			//$user_picture_arr = explode(',',$user['picture']);
			$lists[$k]['nickname'] = $user['nickname'];
			//$lists[$k]['picture'] = $user_picture_arr[0];
			/*格式化时间*/
			$lists[$k]['time'] = date('Y-m-d',$v['time']);
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
     * 个人中心-取消预约
     */
    public function unsubscribe(){
		/*用户ID*/
		$id = input('subscribe_id');
		if(empty($id)){
			return show(config('code.error'),'参数缺失',array());
		}
		$data['status'] = 4;
		$result = Db::name('subscribe')->where('id='.$id)->update($data);
		if($result){
			return show(config('code.success'),'ok',array());
		}else{
			return show(config('code.error'),'error',array());
		}	
	}

    /**
     * 诊所-预约门诊
     */
    public function subscribeadd(){
		/*用户ID*/
		$id = $this->user['id'];		
		/*ID*/
		if(empty(input('clinic_id')) || empty(input('user_to_id')) || empty(input('name')) || empty(input('tel')) || empty(input('subscribe_time')) || empty(input('content')) || empty(input('age'))){
			return show(config('code.error'),'参数缺失',array());
		}
		$nowtime = time();
		$data = array(
			'site_id'		=> $this->site_id,
			'clinic_id' 	=> input('clinic_id'),/*诊所ID*/
			'user_id' 		=> $id,/*预约人ID */
			'user_to_id' 	=> input('user_to_id'),/*医生ID*/
			'name' 			=> input('name'),/*姓名*/
			'tel' 			=> input('tel'),/*电话*/
			'sex' 			=> !empty(input('sex'))?input('sex'):0,/*性别 默认女:0|男为1 */
			'time' 			=> input('subscribe_time'),/*时间,10位Unix时间戳*/
			'content' 		=> input('content'),/*原因*/
			'age' 			=> input('age'),/*年龄*/
			'addtime' 		=> $nowtime,
			'edittime' 		=> $nowtime,			
		);
		$result = Db::name('subscribe')->insert($data);
		$data['id'] = Db::name('subscribe')->getLastInsID();
		if($result){
			return show(config('code.success'),'ok',$data);
		}else{
			return show(config('code.error'),'error',array());
		}	
	}

    /**
     * 个人中心-就诊记录
     */
    public function medical(){
		/*用户ID*/
		$id = $this->user['id'];
		/*获取参数*/	
		$sline = !empty(input('sline'))?input('sline'):0;
		$limit = !empty(input('limit'))?input('limit'):config('default_limit');
		/*查询聊天名师*/
		$where_list['user_id'] = $id;/*用户ID*/
		/*排序*/	
		$order = 'addtime desc,id desc';
		/*条数*/
		$limit = $sline.','.$limit;
		/*字段*/
		$field = 'id,user_id,clinic_name,doctor_name,name,age,sex,jz_time,ky_time,content,bl_album,bg_album,cf_album,status,addtime';
		/*查询聊天名师*/
		$total = Db::name('medical_record')->where($where_list)->count();
		$list = Db::name('medical_record')->field($field)->where($where_list)->order($order)->limit($limit)->select();
		$lists = array();
		foreach($list as $k => $v){
			$lists[$k] = $v;
			/*格式化时间*/	
			$lists[$k]['jz_time'] = date('Y-m-d',$v['jz_time']);
			$lists[$k]['ky_time'] = date('Y-m-d',$v['ky_time']);
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
     * 个人中心-添加就诊记录
     */
    public function medicaladd(){
		/*ID*/
		if(empty(input('clinic_name')) || empty(input('doctor_name')) || empty(input('name'))){
			return show(config('code.error'),'参数缺失',array());
		}
		$nowtime = time();
		$data = array(
			'user_id' 		=> $this->user['id'],
			'clinic_name' 	=> input('clinic_name'),/*就诊机构*/
			'doctor_name' 	=> input('doctor_name'),/*问诊医生*/
			'name' 			=> input('name'),/*姓名*/
			'addtime' 		=> $nowtime,
			'edittime' 		=> $nowtime,			
		);
		if(!empty(input('age'))){
			$data['age'] = input('age');/*年龄*/
		}
		if(!empty(input('sex'))){
			$data['sex'] = input('sex');/*性别 默认女:0|男为1*/
		}
		if(!empty(input('jz_time'))){
			$data['jz_time'] = input('jz_time');/*就诊时间,10位Unix时间戳*/
		}
		if(!empty(input('ky_time'))){
			$data['ky_time'] = input('ky_time');/*开药时间,10位Unix时间戳*/
		}
		if(!empty(input('content'))){
			$data['content'] = input('content');/*就诊结果*/
		}
		if(!empty(input('bl_album'))){
			$data['bl_album'] = input('bl_album');/*病历相册,多图用逗号连接起来*/
		}
		if(!empty(input('bg_album'))){
			$data['bg_album'] = input('bg_album');/*报告相册,多图用逗号连接起来*/
		}
		if(!empty(input('cf_album'))){
			$data['cf_album'] = input('cf_album');/*处方相册,多图用逗号连接起来*/
		}				
		$result = Db::name('medical_record')->insert($data);
		$data['id'] = Db::name('medical_record')->getLastInsID();
		if($result){
			return show(config('code.success'),'ok',$data);
		}else{
			return show(config('code.error'),'error',array());
		}	
	}
	
    /**
     * 个人中心-修改就诊记录
     */
    public function medicaledit(){
		/*ID*/
		$id = input('medical_id');
		if(empty($id) || empty(input('clinic_name')) || empty(input('doctor_name')) || empty(input('name'))){
			return show(config('code.error'),'参数缺失',array());
		}
		$nowtime = time();
		$data = array(
			'clinic_name' 	=> input('clinic_name'),/*就诊机构*/
			'doctor_name' 	=> input('doctor_name'),/*问诊医生*/
			'name' 			=> input('name'),/*姓名*/
			'edittime' 		=> $nowtime,			
		);
		if(!empty(input('age'))){
			$data['age'] = input('age');/*年龄*/
		}
		if(!empty(input('sex'))){
			$data['sex'] = input('sex');/*性别 默认女:0|男为1*/
		}
		if(!empty(input('jz_time'))){
			$data['jz_time'] = input('jz_time');/*就诊时间,10位Unix时间戳*/
		}
		if(!empty(input('ky_time'))){
			$data['ky_time'] = input('ky_time');/*开药时间,10位Unix时间戳*/
		}
		if(!empty(input('content'))){
			$data['content'] = input('content');/*就诊结果*/
		}
		if(!empty(input('bl_album'))){
			$data['bl_album'] = input('bl_album');/*病历相册,多图用逗号连接起来*/
		}
		if(!empty(input('bg_album'))){
			$data['bg_album'] = input('bg_album');/*报告相册,多图用逗号连接起来*/
		}
		if(!empty(input('cf_album'))){
			$data['cf_album'] = input('cf_album');/*处方相册,多图用逗号连接起来*/
		}			
		$result = Db::name('medical_record')->where('id='.$id)->update($data);
		if($result){
			return show(config('code.success'),'ok',$data);
		}else{
			return show(config('code.error'),'error',array());
		}	
	}
	
    /**
     * 个人中心-删除就诊记录
     */
    public function medicaldel(){
		/*ID*/
		$id = input('medical_id');
		if(empty($id)){
			return show(config('code.error'),'参数缺失',array());
		}
		$result = Db::name('medical_record')->where('id='.$id)->delete();
		if($result){
			return show(config('code.success'),'ok',array());
		}else{
			return show(config('code.error'),'error',array());
		}	
	}

    /**
     * 个人中心-健康计划
     */
    public function health(){
		/*用户ID*/
		$id = $this->user['id'];
		/*获取参数*/	
		$sline = !empty(input('sline'))?input('sline'):0;
		$limit = !empty(input('limit'))?input('limit'):config('default_limit');
		/*查询聊天名师*/
		$where_list['user_to_id'] = $id;/*用户ID*/
		/*排序*/	
		$order = 'addtime desc,id desc';
		/*条数*/
		$limit = $sline.','.$limit;
		/*字段*/
		$field = 'id,user_id,user_to_id,title,name,addtime';
		/*查询聊天名师*/
		$total = Db::name('health_plan')->where($where_list)->count();
		$list = Db::name('health_plan')->field($field)->where($where_list)->order($order)->limit($limit)->select();
		$lists = array();
		foreach($list as $k => $v){
			$lists[$k] = $v;
			/*查询医生*/
			$user = Db::name('user')->field('nickname,picture')->where('id='.$v['user_id'])->find();
			$lists[$k]['nickname'] = $user['nickname'];			
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
     * 个人中心-健康计划详情
     */
    public function healthcon(){
		/*ID*/
		$id = input('health_id');
		if(empty($id)){
			return show(config('code.error'),'参数缺失',array());
		}
		$field = 'id,user_id,user_to_id,title,name,sex,age,height,blood,content,addtime';
		$data = Db::name('health_plan')->field($field)->where('id='.$id)->find();
		if($data){
			return show(config('code.success'),'ok',$data);
		}else{
			return show(config('code.error'),'error',array());
		}	
	}
	
    /**
     * 个人中心-健康计划进展
     */
    public function healthevo(){
		/*ID*/
		$id = input('health_id');
		if(empty($id)){
			return show(config('code.error'),'参数缺失',array());
		}
		$field = 'days,content,addtime';
		$data = Db::name('health_plan_evolve')->field($field)->where('health_plan_id='.$id)->select();
		if($data){
			return show(config('code.success'),'ok',$data);
		}else{
			return show(config('code.error'),'error',array());
		}	
	}		

    /**
     * 个人中心-用药记录
     */
    public function pharmacy(){
		/*用户ID*/
		$id = $this->user['id'];
		/*获取参数*/	
		$sline = !empty(input('sline'))?input('sline'):0;
		$limit = !empty(input('limit'))?input('limit'):config('default_limit');
		/*查询聊天名师*/
		$where_list['user_id'] = $id;/*用户ID*/
		/*排序*/	
		$order = 'addtime desc,id desc';
		/*条数*/
		$limit = $sline.','.$limit;
		/*字段*/
		$field = 'id,user_id,time,symptom,content,addtime';
		/*查询聊天名师*/
		$total = Db::name('pharmacy')->where($where_list)->count();
		$list = Db::name('pharmacy')->field($field)->where($where_list)->order($order)->limit($limit)->select();
		$lists = array();
		foreach($list as $k => $v){
			$lists[$k] = $v;		
			/*格式化时间*/	
			$lists[$k]['time'] = date('Y-m-d',$v['time']);
			$lists[$k]['addtime'] = date('Y-m-d',$v['addtime']);
			/*解析用药情况*/
			$lists[$k]['content'] = json_decode($v['content'],true);
		}
		/*处理结果集*/
		$result_data = array(
			'total' => $total,
			'list'	=> $lists
		);
		return show(config('code.success'),'ok',$result_data);
	}
	
    /**
     * 个人中心-添加用药记录
     */
    public function pharmacyadd(){
		/*ID*/
		if(empty(input('time')) || empty(input('symptom')) || empty(input('content'))){
			return show(config('code.error'),'参数缺失',array());
		}
		$nowtime = time();
		$data = array(
			'user_id' 		=> $this->user['id'],
			'time' 			=> input('time'),/*时间*/
			'symptom' 		=> input('symptom'),/*症状*/
			'content' 		=> input('content'),/*用药情况*/
			'addtime' 		=> $nowtime,
			'edittime' 		=> $nowtime,			
		);
		$result = Db::name('pharmacy')->insert($data);
		$data['id'] = Db::name('pharmacy')->getLastInsID();
		if($result){
			return show(config('code.success'),'ok',$data);
		}else{
			return show(config('code.error'),'error',array());
		}	
	}

    /**
     * 个人中心-家庭联系人
     */
    public function family_atten(){
		/*用户ID*/
		$id = $this->user['id'];
		/*获取参数*/	
		$sline = !empty(input('sline'))?input('sline'):0;
		$limit = !empty(input('limit'))?input('limit'):config('default_limit');
		/*查询聊天名师*/
		$where_list['user_id'] = $id;/*用户ID*/
		/*排序*/	
		$order = 'addtime desc,id desc';
		/*条数*/
		$limit = $sline.','.$limit;
		/*字段*/
		$field = 'id,user_id,name,relation,sex,age,address,phone,addtime';
		/*查询聊天名师*/
		$total = Db::name('family_atten')->where($where_list)->count();
		$list = Db::name('family_atten')->field($field)->where($where_list)->order($order)->limit($limit)->select();
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
     * 个人中心-添加家庭联系人
     */
    public function family_attenadd(){
		/*ID*/
		if(empty(input('name')) || empty(input('relation'))){
			return show(config('code.error'),'参数缺失',array());
		}
		$nowtime = time();
		$data = array(
			'user_id' 		=> $this->user['id'],
			'name' 			=> input('name'),/*姓名*/
			'relation' 		=> input('relation'),/*关系*/
			'addtime' 		=> $nowtime,
			'edittime' 		=> $nowtime,			
		);
		if(!empty(input('age'))){
			$data['age'] = input('age');/*年龄*/
		}
		if(!empty(input('sex'))){
			$data['sex'] = input('sex');/*性别 默认女:0|男为1*/
		}
		if(!empty(input('address'))){
			$data['address'] = input('address');/*地址*/
		}
		if(!empty(input('phone'))){
			$data['phone'] = input('phone');/*电话*/
		}			
		$result = Db::name('family_atten')->insert($data);
		$data['id'] = Db::name('family_atten')->getLastInsID();
		if($result){
			return show(config('code.success'),'ok',$data);
		}else{
			return show(config('code.error'),'error',array());
		}	
	}
	
    /**
     * 个人中心-修改家庭联系人
     */
    public function family_attenedit(){
		/*ID*/
		$id = input('family_atten_id');
		if(empty($id) || empty(input('name')) || empty(input('relation'))){
			return show(config('code.error'),'参数缺失',array());
		}
		$nowtime = time();
		$data = array(
			'name' 			=> input('name'),/*姓名*/
			'relation' 		=> input('relation'),/*关系*/
			'edittime' 		=> $nowtime,			
		);
		if(!empty(input('age'))){
			$data['age'] = input('age');/*年龄*/
		}
		if(!empty(input('sex'))){
			$data['sex'] = input('sex');/*性别 默认女:0|男为1*/
		}
		if(!empty(input('address'))){
			$data['address'] = input('address');/*地址*/
		}
		if(!empty(input('phone'))){
			$data['phone'] = input('phone');/*电话*/
		}						
		$result = Db::name('family_atten')->where('id='.$id)->update($data);
		if($result){
			return show(config('code.success'),'ok',$data);
		}else{
			return show(config('code.error'),'error',array());
		}	
	}
	
    /**
     * 个人中心-删除家庭联系人
     */
    public function family_attendel(){
		/*ID*/
		$id = input('family_atten_id');
		if(empty($id)){
			return show(config('code.error'),'参数缺失',array());
		}
		$result = Db::name('family_atten')->where('id='.$id)->delete();
		if($result){
			return show(config('code.success'),'ok',array());
		}else{
			return show(config('code.error'),'error',array());
		}	
	}

    /**
     * 个人中心-收货地址
     */
    public function address(){
		/*用户ID*/
		$id = $this->user['id'];
		/*获取参数*/	
		$sline = !empty(input('sline'))?input('sline'):0;
		$limit = !empty(input('limit'))?input('limit'):config('default_limit');
		/*查询聊天名师*/
		$where_list['user_id'] = $id;/*用户ID*/
		/*排序*/	
		$order = 'addtime desc,id desc';
		/*条数*/
		$limit = $sline.','.$limit;
		/*字段*/
		$field = 'id,user_id,receiver,tel,province,city,area,content,default,addtime';
		/*查询聊天名师*/
		$total = Db::name('address')->where($where_list)->count();
		$list = Db::name('address')->field($field)->where($where_list)->order($order)->limit($limit)->select();
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
     * 个人中心-添加收货地址
     */
    public function addressadd(){
		/*ID*/
		if(empty(input('receiver')) || empty(input('tel')) || empty(input('province')) || empty(input('city')) || empty(input('area')) || empty(input('content'))){
			return show(config('code.error'),'参数缺失',array());
		}
		$nowtime = time();
		$data = array(
			'user_id' 		=> $this->user['id'],
			'receiver' 		=> input('receiver'),/*收货人*/
			'tel' 			=> input('tel'),/*电话*/
			'province' 		=> input('province'),/*省*/
			'city' 			=> input('city'),/*市*/
			'area' 			=> input('area'),/*区*/
			'content' 		=> input('content'),/*详情地址*/
			'default' 		=> !empty(input('default'))?input('default'):0,/*默认 默认为0:否|1:是*/
			'addtime' 		=> $nowtime,
			'edittime' 		=> $nowtime,	
		);
		/*如果设置为默认地址，修改其它地址*/
		if($data['default'] == 1){
			$default_list_where['user_id'] = $this->user['id'];
			$default_list_where['default'] = 1;
			$default_list = Db::name('address')->field('id')->where($default_list_where)->select();
			foreach($default_list as $k=>$v){
				$default_data['default'] = 0;
				$default_result = Db::name('address')->where('id='.$v['id'])->update($default_data);
			}
		}
		/*插入数据*/
		$result = Db::name('address')->insert($data);
		$data['id'] = Db::name('address')->getLastInsID();
		if($result){
			return show(config('code.success'),'ok',$data);
		}else{
			return show(config('code.error'),'error',array());
		}	
	}
	
    /**
     * 个人中心-修改收货地址
     */
    public function addressedit(){
		/*ID*/
		$id = input('address_id');
		if(empty($id) || empty(input('receiver')) || empty(input('tel')) || empty(input('province')) || empty(input('city')) || empty(input('area')) || empty(input('content'))){
			return show(config('code.error'),'参数缺失',array());
		}
		$nowtime = time();
		$data = array(
			'receiver' 		=> input('receiver'),/*收货人*/
			'tel' 			=> input('tel'),/*电话*/
			'province' 		=> input('province'),/*省*/
			'city' 			=> input('city'),/*市*/
			'area' 			=> input('area'),/*区*/
			'content' 		=> input('content'),/*详情地址*/
			'default' 		=> !empty(input('default'))?input('default'):0,/*默认 默认为0:否|1:是*/
			'edittime' 		=> $nowtime,		
		);
		/*如果设置为默认地址，修改其它地址*/
		if($data['default'] == 1){
			$default_list_where['user_id'] = $this->user['id'];
			$default_list_where['default'] = 1;
			$default_list = Db::name('address')->field('id')->where($default_list_where)->select();
			foreach($default_list as $k=>$v){
				$default_data['default'] = 0;
				$default_result = Db::name('address')->where('id='.$v['id'])->update($default_data);
			}
		}		
		$result = Db::name('address')->where('id='.$id)->update($data);
		if($result){
			return show(config('code.success'),'ok',$data);
		}else{
			return show(config('code.error'),'error',array());
		}	
	}
	
    /**
     * 个人中心-删除收货地址
     */
    public function addressdel(){
		/*ID*/
		$id = input('address_id');
		if(empty($id)){
			return show(config('code.error'),'参数缺失',array());
		}
		$result = Db::name('address')->where('id='.$id)->delete();
		if($result){
			return show(config('code.success'),'ok',array());
		}else{
			return show(config('code.error'),'error',array());
		}	
	}

    /**
     * 个人中心-评价
     */
    public function user_comment(){
		/*用户ID*/
		$id = $this->user['id'];
		/*获取参数*/	
		$sline = !empty(input('sline'))?input('sline'):0;
		$limit = !empty(input('limit'))?input('limit'):config('default_limit');
		/*查询聊天名师*/
		$where_list['user_id'] = $id;/*用户ID*/
		/*排序*/	
		$order = 'addtime desc,id desc';
		/*条数*/
		$limit = $sline.','.$limit;
		/*字段*/
		$field = 'id,user_id,user_to_id,total_star,total_level,total_smile,content,addtime';
		/*查询聊天名师*/
		$total = Db::name('user_comment')->where($where_list)->count();
		$list = Db::name('user_comment')->field($field)->where($where_list)->order($order)->limit($limit)->select();
		$lists = array();
		foreach($list as $k => $v){
			$lists[$k] = $v;
			/*格式化时间*/	
			$lists[$k]['addtime'] = date('Y-m-d',$v['addtime']);
			/*查询用户身份信息*/
			$user = Db::name('user')->field('clinic_id,nickname,picture')->where('id='.$v['user_to_id'])->find();
			$user_picture_arr = explode(',',$user['picture']);
			$lists[$k]['nickname'] = $user['nickname'];
			$lists[$k]['picture'] = $user_picture_arr[0];
			/*查询医生诊所*/
			$clinic = Db::name('clinic')->field('name')->where('id='.$user['clinic_id'])->find();
			$lists[$k]['clinic_name'] = $clinic['name'];
		}
		/*处理结果集*/
		$result_data = array(
			'total' => $total,
			'list'	=> $lists
		);
		return show(config('code.success'),'ok',$result_data);
	}
	
    /**
     * 个人中心-删除评价
     */
    public function user_commentdel(){
		/*ID支持批量*/
		$ids = input('user_comment_id');
		if(empty($ids)){
			return show(config('code.error'),'参数缺失',array());
		}
		$ids = trim($ids,',');
		$where['id'] = array('in',$ids);
		$result = Db::name('user_comment')->where($where)->delete();
		if($result){
			return show(config('code.success'),'ok',array());
		}else{
			return show(config('code.error'),'error',array());
		}	
	}

    /**
     * 个人中心-系统信息(客服热线)
     */
    public function site(){
		/*ID*/
		$id = $this->site_id;
		if(empty($id)){
			return show(config('code.error'),'参数缺失',array());
		}
		$field = 'name,ico,tel';
		$result = Db::name('site')->field($field)->where('id='.$id)->find();
		if($result){
			return show(config('code.success'),'ok',$result);
		}else{
			return show(config('code.error'),'error',array());
		}	
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
     * 诊所-关注状态查询
     */
    public function clinic_attentionfind(){
		/*ID*/
		$id = input('clinic_id');
		if(empty($id) || empty($this->user['id'])){
			return show(config('code.error'),'参数缺失',array());
		}
		/*查询初始状态*/
		$where['user_id'] = $this->user['id'];
		$where['clinic_id'] = $id;
		$result = Db::name('clinic_attention')->field('id,status')->where($where)->find();
		/*返回结果集*/
		if($result){
			return show(config('code.success'),'ok',$result);
		}else{
			return show(config('code.error'),'error',array());
		}		
	}

    /**
     * 诊所-关注|取消关注
     */
    public function clinic_attention(){
		/*ID*/
		$id = input('clinic_id');
		if(empty($id) || empty($this->user['id'])){
			return show(config('code.error'),'参数缺失',array());
		}
		/*组装参数*/
		$nowtime = time();
		$data = array(
			'site_id'		=> $this->site_id,
			'user_id' 		=> $this->user['id'],/*用户ID*/
			'clinic_id' 	=> $id,/*诊所*/
			'edittime' 		=> $nowtime,			
		);
		/*查询初始状态*/
		$where['user_id'] = $this->user['id'];
		$where['clinic_id'] = $id;
		$clinic_attention = Db::name('clinic_attention')->field('id,status')->where($where)->find();
		if(empty($clinic_attention)){
			/*不存在*/
			$data['addtime'] = $nowtime;
			$data['status'] = 1;
			$result = Db::name('clinic_attention')->insert($data);
			$data['clinic_attention_id'] = Db::name('clinic_attention')->getLastInsID();
		}else{
			if($clinic_attention['status'] == 1){
				/*已经是关注状态*/
				$data['status'] = 0;
				$result = Db::name('clinic_attention')->where('id='.$clinic_attention['id'])->update($data);
			}else{
				/*取消关注状态*/
				$data['status'] = 1;
				$result = Db::name('clinic_attention')->where('id='.$clinic_attention['id'])->update($data);
			}
			$data['clinic_attention_id'] = $clinic_attention['id'];
		}
		/*返回结果集*/
		if($result){
			return show(config('code.success'),'ok',$data);
		}else{
			return show(config('code.error'),'error',array());
		}		
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
				$where_list['channel_id'] = array('in','25');/*文章*/
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
				/*查询用户点赞状态*/
				$like_where['user_id'] = $this->user['id'];
				$like_where['content_id'] = $v['id'];
				$like_result = Db::name('like')->field('status')->where($like_where)->find();
				if($like_result){
					$like_status = $like_result['status'];
				}else{
					$like_status = 0;
				}			
				$lists[$k]['like_status'] = $like_status;				
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
			/*查询用户点赞状态*/
			$like_where['user_id'] = $this->user['id'];
			$like_where['content_id'] = $v['id'];
			$like_result = Db::name('like')->field('status')->where($like_where)->find();
			if($like_result){
				$like_status = $like_result['status'];
			}else{
				$like_status = 0;
			}			
			$lists[$k]['like_status'] = $like_status;			
		}
		/*处理结果集*/
		$result_data = array(
			'total' => $total,
			'list'	=> $lists
		);
		return show(config('code.success'),'ok',$result_data);
	}


    /**
     * 健康学堂-内容点赞状态查询
     */
    public function cms_likefind(){
		/*ID*/
		$id = input('content_id');
		if(empty($id) || empty($this->user['id'])){
			return show(config('code.error'),'参数缺失',array());
		}
		/*查询初始状态*/
		$where['user_id'] = $this->user['id'];
		$where['content_id'] = $id;
		$result = Db::name('like')->field('id,status')->where($where)->find();
		/*返回结果集*/
		if($result){
			return show(config('code.success'),'ok',$result);
		}else{
			return show(config('code.error'),'error',array());
		}		
	}

    /**
     * 健康学堂-内容点赞|取消点赞
     */
    public function cms_like(){
		/*ID*/
		$id = input('content_id');
		if(empty($id) || empty($this->user['id'])){
			return show(config('code.error'),'参数缺失',array());
		}
		/*组装参数*/
		$nowtime = time();
		$data = array(
			'user_id' 		=> $this->user['id'],/*用户ID*/
			'content_id' 	=> $id,/*内容*/
			'edittime' 		=> $nowtime,			
		);
		/*查询初始状态*/
		$where['user_id'] = $this->user['id'];
		$where['content_id'] = $id;
		$like = Db::name('like')->field('id,status')->where($where)->find();
		if(empty($like)){
			/*不存在或为取消状态*/
			$data['addtime'] = $nowtime;
			$data['status'] = 1;
			$result = Db::name('like')->insert($data);
			$data['id'] = Db::name('like')->getLastInsID();
		}else{
			if($like['status'] == 1){
				/*已经是点赞状态*/
				$data['status'] = 0;
				$result = Db::name('like')->where('id='.$like['id'])->update($data);
			}else{
				/*取消点赞状态*/
				$data['status'] = 1;
				$result = Db::name('like')->where('id='.$like['id'])->update($data);
			}
			$data['like_id'] = $like['id'];
		}
		/*返回结果集*/
		if($result){
			return show(config('code.success'),'ok',$data);
		}else{
			return show(config('code.error'),'error',array());
		}		
	}

    /**
     * 健康学堂-内容评论添加
     */
    public function cms_commentadd(){
		/*ID*/
		if(empty(input('content_id')) || empty(input('content'))){
			return show(config('code.error'),'参数缺失',array());
		}
		$nowtime = time();
		$data = array(
			'user_id' 		=> $this->user['id'],
			'content_id' 	=> input('content_id'),
			'content' 		=> input('content'),/*内容*/
			'addtime' 		=> $nowtime,
			'edittime' 		=> $nowtime,	
		);
		if(!empty(input('name'))){
			$data['name'] = input('name');
		}
		if(!empty(input('picture'))){
			$data['picture'] = input('picture');
		}
		if(!empty(input('description'))){
			$data['description'] = input('description');
		}		
		/*插入数据*/
		$result = Db::name('comment')->insert($data);
		$data['id'] = Db::name('comment')->getLastInsID();
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
		/*查询聊天名师*/
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
						$query->where('user_from_type',1)->whereor('user_to_type',1);/*用户类型*/
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
			'user_from_type'=> 1,
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
			/*查询用户点赞状态*/
			$like_where['user_id'] = $this->user['id'];
			$like_where['bbs_info_id'] = $v['id'];
			$like_result = Db::name('bbs_like')->field('status')->where($like_where)->find();
			if($like_result){
				$like_status = $like_result['status'];
			}else{
				$like_status = 0;
			}			
			$lists[$k]['like_status'] = $like_status;
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
     * 社区-圈子-我发布的
     */
    public function bbs_infome(){
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
		/*查询*/
		$where_list['site_id'] = $this->site_id;
		$where_list['user_id'] = $id;		
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
			/*查询用户点赞状态*/
			$like_where['user_id'] = $this->user['id'];
			$like_where['bbs_info_id'] = $v['id'];
			$like_result = Db::name('bbs_like')->field('status')->where($like_where)->find();
			if($like_result){
				$like_status = $like_result['status'];
			}else{
				$like_status = 0;
			}			
			$lists[$k]['like_status'] = $like_status;
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
     * 社区-圈子-发圈
     */
    public function bbs_infoadd(){
		/*用户ID*/
		$id = $this->user['id'];
		if(empty($id) || (empty(input('album')) && empty(input('content')))){
			return show(config('code.error'),'参数缺失',array());
		}
		$nowtime = time();
		$data = array(
			'site_id'		=> $this->site_id,
			'user_id' 		=> $this->user['id'],
			'type'			=> 1,/*类型  默认为1:医患圈|2:专家圈*/
			'addtime' 		=> $nowtime,
			'edittime' 		=> $nowtime,	
		);
		if(!empty(input('album'))){
			$data['album'] = input('album');
		}
		if(!empty(input('content'))){
			$data['content'] = input('content');
		}		
		/*插入数据*/
		$result = Db::name('bbs_info')->insert($data);
		$data['id'] = Db::name('bbs_info')->getLastInsID();
		if($result){
			return show(config('code.success'),'ok',$data);
		}else{
			return show(config('code.error'),'error',array());
		}
	}

   /**
     * 社区-登录用户点赞状态查询
     */
    public function bbs_likefind(){
		/*ID*/
		$id = input('bbs_info_id');
		if(empty($id) || empty($this->user['id'])){
			return show(config('code.error'),'参数缺失',array());
		}
		/*查询初始状态*/
		$where['user_id'] = $this->user['id'];
		$where['bbs_info_id'] = $id;
		$result = Db::name('bbs_like')->field('id,status')->where($where)->find();
		/*返回结果集*/
		if($result){
			return show(config('code.success'),'ok',$result);
		}else{
			return show(config('code.error'),'error',array());
		}		
	}

    /**
     * 社区-信息点赞|取消点赞
     */
    public function bbs_like(){
		/*ID*/
		$id = input('bbs_info_id');
		if(empty($id) || empty($this->user['id'])){
			return show(config('code.error'),'参数缺失',array());
		}
		/*组装参数*/
		$nowtime = time();
		$data = array(
			'user_id' 		=> $this->user['id'],/*用户ID*/
			'bbs_info_id' 	=> $id,/*内容*/
			'edittime' 		=> $nowtime,			
		);
		/*查询初始状态*/
		$where['user_id'] = $this->user['id'];
		$where['bbs_info_id'] = $id;
		$bbs_like = Db::name('bbs_like')->field('id,status')->where($where)->find();
		if(empty($bbs_like)){
			/*不存在或为取消状态*/
			$data['addtime'] = $nowtime;
			$data['status'] = 1;
			$result = Db::name('bbs_like')->insert($data);
			$data['bbs_like_id'] = Db::name('bbs_like')->getLastInsID();
		}else{
			if($bbs_like['status'] == 1){
				/*已经是点赞状态*/
				$data['status'] = 0;
				$result = Db::name('bbs_like')->where('id='.$bbs_like['id'])->update($data);
			}else{
				/*取消点赞状态*/
				$data['status'] = 1;
				$result = Db::name('bbs_like')->where('id='.$bbs_like['id'])->update($data);
			}
			$data['bbs_like_id'] = $bbs_like['id'];
		}
		/*返回结果集*/
		if($result){
			return show(config('code.success'),'ok',$data);
		}else{
			return show(config('code.error'),'error',array());
		}		
	}

    /**
     * 社区-信息评论添加
     */
    public function bbs_commentadd(){
		/*ID*/
		if(empty(input('bbs_info_id')) || empty(input('content'))){
			return show(config('code.error'),'参数缺失',array());
		}
		$nowtime = time();
		$data = array(
			'user_id' 		=> $this->user['id'],
			'bbs_info_id' 	=> input('bbs_info_id'),
			'content' 		=> input('content'),/*内容*/
			'addtime' 		=> $nowtime,
			'edittime' 		=> $nowtime,	
		);
		if(!empty(input('name'))){
			$data['name'] = input('name');
		}
		if(!empty(input('picture'))){
			$data['picture'] = input('picture');
		}
		if(!empty(input('description'))){
			$data['description'] = input('description');
		}		
		/*插入数据*/
		$result = Db::name('bbs_comment')->insert($data);
		$data['id'] = Db::name('bbs_comment')->getLastInsID();
		if($result){
			return show(config('code.success'),'ok',$data);
		}else{
			return show(config('code.error'),'error',array());
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
		$where_list['type']  = array('in','1,4');/*1:用户端 | 2:医生端 | 3:专家端 | 4:所有终端*/	
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
		$where_list['type']  = array('in','1,4');/*1:用户端 | 2:医生端 | 3:专家端 | 4:所有终端*/	
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
     * 咨询-聊天-查询家庭医生申请状态
     */
    public function family_doctor_status(){
		/*ID*/
		$id = input('doctor_id');
		if(empty($id) || empty($this->user['id'])){
			return show(config('code.error'),'参数缺失',array());
		}
		/*查询初始状态*/
		$where['user_id'] = $this->user['id'];
		$where['user_to_id'] = $id;
		$family_doctor = Db::name('family_doctor')->field('status')->where($where)->find();
		if(empty($family_doctor)){
			/*不存在*/
			$data['family_doctor_status'] = 0;
		}else{
			$data['family_doctor_status'] = $family_doctor['status'];
		}
		/*返回结果集*/
		if($data){
			return show(config('code.success'),'ok',$data);
		}else{
			return show(config('code.error'),'error',array());
		}		
	}

    /**
     * 咨询-聊天-申请家庭医生
     */
    public function family_doctor(){
		/*ID*/
		$id = input('doctor_id');
		if(empty($id) || empty($this->user['id'])){
			return show(config('code.error'),'参数缺失',array());
		}
		/*查询诊所信息*/
		$doctor = Db::name('user')->field('clinic_id')->where('id='.$id)->find();
		/*组装参数*/
		$nowtime = time();
		$data = array(
			'clinic_id'		=> $doctor['clinic_id'],
			'user_id' 		=> $this->user['id'],/*用户ID*/
			'user_to_id' 	=> $id,/*内容*/
			'edittime' 		=> $nowtime,			
		);
		/*查询初始状态*/
		$where['user_id'] = $this->user['id'];
		$where['user_to_id'] = $id;
		$family_doctor = Db::name('family_doctor')->field('id,status')->where($where)->find();
		if(empty($family_doctor)){
			/*不存在*/
			$data['addtime'] = $nowtime;
			$data['status'] = 1;
			$result = Db::name('family_doctor')->insert($data);
			$data['family_doctor_id'] = Db::name('family_doctor')->getLastInsID();
		}else{
			if($family_doctor['status'] == 1){
				/*已是申请状态*/
				return show(config('code.success'),'ok,已处于申请中',$data);
			}else{
				/*其它状态*/
				$data['status'] = 1;
				$result = Db::name('family_doctor')->where('id='.$family_doctor['id'])->update($data);
			}
			$data['family_doctor_id'] = $family_doctor['id'];
		}
		/*返回结果集*/
		if($result){
			return show(config('code.success'),'ok',$data);
		}else{
			return show(config('code.error'),'error',array());
		}		
	}

    /**
     * 咨询-聊天-评价医生
     */
    public function doctor_comment(){
		/*ID*/
		$id = input('doctor_id');
		if(empty($id) || empty($this->user['id']) || empty(input('total_star')) || empty(input('total_level')) || empty(input('total_smile')) || empty(input('content'))){
			return show(config('code.error'),'参数缺失',array());
		}
		/*查询诊所信息*/
		$doctor = Db::name('user')->field('clinic_id')->where('id='.$id)->find();
		/*组装参数*/
		$nowtime = time();
		$data = array(
			'user_id' 		=> $this->user['id'],/*用户ID*/
			'user_to_id' 	=> $id,/*内容*/
			'total_star' 	=> input('total_star'),/*整体评价(星级1~5)*/
			'total_level' 	=> input('total_level'),/*医生水准(星级1~5)*/
			'total_smile' 	=> input('total_smile'),/*医生态度(星级1~5)*/
			'content' 		=> input('content'),/*内容*/
			'edittime' 		=> $nowtime
		);
		if(!empty(input('desc'))){
			$data['desc'] = input('desc');/*症状*/
		}
		/*不存在*/
		$data['addtime'] = $nowtime;
		$data['status'] = 1;
		$result = Db::name('user_comment')->insert($data);
		$data['id'] = Db::name('family_doctor')->getLastInsID();
		/*返回结果集*/
		if($result){
			return show(config('code.success'),'ok',$data);
		}else{
			return show(config('code.error'),'error',array());
		}		
	}

    /**
     * 咨询-接受药方并选取收货地址
     */
    public function order_address(){
		/*ID*/
		$id = input('order_id');
		if(empty($id) || empty(input('receiver')) || empty(input('tel')) || empty(input('address')) || empty(input('message'))){
			return show(config('code.error'),'参数缺失',array());
		}
		/*组装参数*/
		$now_time = time();
		$data = array(
			'receiver' 	=> input('receiver'),
			'tel' 		=> input('tel'),
			'address' 	=> input('address'),
			'edittime' 	=> $now_time
		);
		if(!empty(input('message'))){
			$data['message'] = input('message');
		}
		$data['status'] = 3;/*确认订单*/
		$data['edittime'] = $now_time;/*时间*/
		$result = Db::name('order')->where('id='.$id)->update($data);
		if($result){
			/*订单处理进度*/
			$data_leng_new = array(
				array(
					'status' => 3,
					'msg'	 => '用户确认订单',
					'time'	 => $now_time
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
			return show(config('code.success'),'ok',$data);
		} else {
			return show(config('code.error'),'error',array());
		}	
	}

   /**
     * 个人中心-我的药方列表(订单)
     */
    public function order(){
		/*获取参数*/
		$tab = !empty(input('tab'))?input('tab'):0;/*选项卡*/
		$sline = !empty(input('sline'))?input('sline'):0;
		$limit = !empty(input('limit'))?input('limit'):config('default_limit');
		/*条数*/
		$limit = $sline.','.$limit;		
		/*查询*/
		$where_list['type']	   = 1;//类型 1:送药订单（用户）|2:进药订单（批发医药公司）
		$where_list['user_id'] = $this->user['id'];/*用户ID*/	
		$where_list['site_id'] = $this->site_id;
		$where_list['status']  = array('in','3,4,5,10,12');/*状态*/
		$search = input('search');/*搜索*/
		if(!empty($search)) {
			$where_list['concat(name,description)'] = array('like','%'.$search.'%');
		}		
		$sline = !empty(input('sline'))?input('sline'):0;
		$limit = !empty(input('limit'))?input('limit'):config('default_limit');		
		/*查询内容*/
		$where_list['site_id'] = $this->site_id;
		$search = input('search');/*搜索*/
		if(!empty($search)) {
			$where_list['concat(name,description)'] = array('like','%'.$search.'%');
		}		
		/*排序*/	
		$order = 'id desc';
		/*选项卡*/
		if($tab == 1){
			$where_list['status'] = 3;
		}else if($tab == 2){
			$where_list['status'] = 4;
		}else if($tab == 3){
			$where_list['status'] = 10;
		}
		/*字段*/
		$field = 'id,no,title,total_money,status,addtime,content';
		/*查询*/
		$t_start = input('t_start');/*开始时间*/
		$t_start_data = $t_start;
		$t_end = input('t_end');/*结束时间*/
		$t_end_data = $t_end;		
		$where_list2 = array();
		if(!empty($t_start_data) && empty($t_end_data)){
			$where_list['addtime'] = array('>=',$t_start_data);
		}else if(!empty($t_end_data) && empty($t_start_data)){
			$where_list['addtime'] = array('<=',$t_end_data);
		}else if(!empty($t_end_data) && !empty($t_end_data)){
			$where_list['addtime'] = array('>=',$t_start_data);
			$where_list2['addtime'] = array('<=',$t_end_data);
		}
		$total = Db::name('order')->where($where_list)->count();
		$list = Db::name('order')->field($field)->where($where_list)->order($order)->limit($limit)->select();
		$lists = array();
		foreach($list as $k => $v){
			$lists[$k] = $v;
			/*格式化时间*/
			$lists[$k]['addtime'] = date('Y-m-d',$v['addtime']);
			/*状态*/
			if (!empty($v['status'])) {
				$lists[$k]['status'] = orderStatusToStr($v['status']);
			}
			/*查询订单商品*/
			$goods_arr = json_decode($v['content'],true);
			if(!empty($goods_arr) && $goods_arr['type'] == 1){
				foreach($goods_arr['value'] as $k2=>$v2){
					$good_array = Db::name('content')->field('name,picture')->where('id='.$v2['content_id'])->find();
					if(!empty($good_array)){
						foreach($good_array as $k3=>$v3){
							$goods_arr['value'][$k2][$k3] = $v3;
						}
					}
				}
			}
			$lists[$k]['goods'] = $goods_arr['value'];
			unset($lists[$k]['content']);
		}
		/*处理结果集*/
		$result_data = array(
			'title' => '我的药方',
			'table' => array('所有药方','待发货','已发货','已完成'),
			'total' => $total,
			'list'	=> $lists
		);
		return show(config('code.success'),'ok',$result_data);
	}

   /**
     * 个人中心-我的药方详情(订单)
     */
    public function ordercon(){
		$id = input('order_id');
		if(empty($id)){
			return show(config('code.error'),'参数缺失',array());
		}		
		$where['id'] = $id;
		$field = 'title,content,no,total_money,price,freight,receiver,tel,address,message,status,addtime,status_leng';
		$contents = Db::name('order')->field($field)->where($where)->find();
		foreach ($contents as $k => $v) {
			$content[$k] = $v;
			/*格式化状态*/
			if($k == 'status' && !empty($contents['status'])){
				$content['status'] = orderStatusToStr($content['status']);
			}
			/*订单处理进度*/
			if($k == 'status_leng' && !empty($contents['status_leng'])){
				$content['status_leng'] = json_decode($content['status_leng'],true);
			}
			/*格式化时间*/
			if($k == 'addtime' && !empty($contents['addtime'])){
				$content['addtime'] = date('Y-m-d',$contents['addtime']);
			}			
		}
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
		$content['goods'] = $goods_arr['value'];
		unset($content['content']);
		return show(config('code.success'),'ok',$content);
	}

   /**
     * 个人中心-我的药方-确认收货
     */
    public function status5(){
		/*接收参数*/
		$id = input('order_id');
		if(empty($id)){
			return show(config('code.error'),'参数缺失',array());
		}		
		$now_time = time();
		$data['status'] = 5;/*确认订单*/
		$data['edittime'] = $now_time;/*时间*/
		$result = Db::name('order')->where('id='.$id)->update($data);
		if($result){
			/*订单处理进度*/
			$data_leng_new = array(
				array(
					'status' 	=> 5,
					'msg'		=> '用户确认收货',
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
			return show(config('code.success'),'ok',$data);
		} else {
			return show(config('code.error'),'error',array());
		}
	}

    /**
     * 私密就诊记录查看申请列表
     */
    public function medical_use(){
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
		/*用户病历*/
		$medical_record_arr = Db::name('medical_record')->field('id')->where('user_id='.$id)->select();
		$midical_ids = '';
		if(!empty($medical_record_arr)){
			foreach($medical_record_arr as $k=>$v){
				$midical_ids .= ','.$v['id'];
			}
		}
		$midical_ids = trim($midical_ids,',');
		/*查询*/
		$where_list['medical_id']  = array('in',$midical_ids);/*患者病历ID集*/	
		$where_list['status']  = 1;/*状态*/
		/*排序*/	
		$order = '`order` desc,edittime desc';
		/*字段*/
		$field = 'id,medical_id,user_id,status,edittime';
		/*查询聊天名师*/
		$total = Db::name('medical_use')->where($where_list)->count();
		$list = Db::name('medical_use')->field($field)->where($where_list)->order($order)->limit($limit)->select();
		$lists = array();
		foreach($list as $k => $v){
			$lists[$k] = $v;
			/*格式化时间*/
			$lists[$k]['edittime'] = date('Y-m-d',$v['edittime']);	
			/*查看病历信息*/
			$where_medical_record['id'] = $v['medical_id'];
			$medical_record = Db::name('medical_record')->field('name')->where($where_medical_record)->find();
			if(!empty($medical_record)){
				$lists[$k]['medical_name'] = $medical_record['name'];
			}
			/*查询医生信息*/
			$user = Db::name('user')->field('realname,picture')->where('id='.$v['user_id'])->find();
			$user_picture_arr = explode(',',$user['picture']);
			$lists[$k]['doctor_info']['realname'] = $user['realname'];
			$lists[$k]['doctor_info']['picture'] = $user_picture_arr[0];				
		}
		/*处理结果集*/
		$result_data = array(
			'total' => $total,
			'list'	=> $lists
		);
		return show(config('code.success'),'ok',$result_data);	
	}

   /**
     * 私密就诊记录查看申请处理
     */
    public function medical_useedit(){
		/*家庭医生ID*/
		if(empty(input('medical_use_id')) || empty(input('type'))){
			return show(config('code.error'),'参数缺失',array());
		}
		$id = input('medical_use_id');
		$type = input('type');
		if($type == 2){
			/*同意*/
			$data['status'] = 2;
		}else if($type == 3){
			/*不同意*/
			$data['status'] = 3;
		}
		$data['edittime'] = time();
		$result = Db::name('medical_use')->where('id='.$id)->update($data);
		if($result){
			return show(config('code.success'),'ok',$result);
		}else{
			return show(config('code.error'),'error',array());
		}
	}



}