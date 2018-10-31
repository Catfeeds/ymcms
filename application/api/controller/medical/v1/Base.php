<?php
/**
 * 用户端-接口
 */
namespace app\api\controller\medical\v1;
use app\api\controller\Common;
use app\common\lib\IAuth;
use think\Controller;
use app\common\lib\exception\ApiException;
use app\common\lib\Aes;
use app\common\lib\Alisms;
use app\common\lib\Upload;
use think\Db;

class Base extends Common{

    /**
     * 客户端初始化接口
     * 检测APP是否需要升级
     */
    public function version() {
		$sitetype = input('sitetype');
		if(empty($sitetype)){
			return show(config('code.error'),'终端类型不合法',array());
		}
        /*apptype去ent_version查询*/
		$app_version_where['sitetype'] = $sitetype;
		$app_version_where['apptype'] = $this->headers['apptype'];
		$app_version_where['status'] = 1;
		$app_version_field = array('version','apptype','apk_url','upgrade_point');
		$app_version = Db::name('app_version')->field($app_version_field)->where($app_version_where)->order('version desc')->find();
        if(empty($app_version)) {
            return new ApiException('app version error');
        }
		/*版本校验*/
        if($app_version["version"] > $this->headers['version']) {
            $app_version['is_update'] = $app_version['is_force'] == 1 ? 2 : 1;
        }else{
            $app_version['is_update'] = 0;/*0:不更新 | 1:需要更新 | 2:强制更新*/
        }
        /*记录用户的基本信息 用于统计*/
		$nowtime = time();
        $actives = array(
			'site_id' => $this->site_id,
            'version' => $this->headers['version'],
            'apptype' => $this->headers['apptype'],
            'did' 	  => $this->headers['did'],
			'os' 	  => $this->headers['os'],
			'model'   => $this->headers['model'],
			'sitetype'=> $sitetype,
			'addtime' => $nowtime,
			'edittime'=> $nowtime
        );
        try{
			$result_insert = Db::name('app_active')->insert($actives);
        }catch (\Exception $e) {
            //Log::write();
        }
		/*返回结果集*/
        return show(config('code.success'),'ok',$app_version);
    }
	
    /**
     * identify
     * 设置短信验证码
     */
    public function identify() {
		/*请求方式校验*/
        if(!request()->isPost()) {
            return show(config('code.error'),'您提交数据不合法',array());
        }
        /*检验数据*/
		$type = input('type');
		if(empty($type)){
			return show(config('code.error'),'短信类型不合法',array());
		}
		$phone = input('phone');
        $validate = validate('Identify');
        if(!$validate->check(input('post.'))){
            return show(config('code.error'),$validate->getError(),array());
        }
		/*发送短信*/
        if(Alisms::getInstance()->setSmsIdentify($phone,$type)) {
            return show(config('code.success'),'ok',array());
        }else {
            return show(config('code.error'),"error",array());
        }
    }	
	
	/**
	 * 登录
	 */
    public function login(){
		/*POST请求方式校验*/
        if(!request()->isPost()){
            return show(config('code.error'),'您没有权限','');
        }
		/*参数校验*/
		$type 		= input('type');
		$phone 		= input('phone');
		$password 	= input('password');
        if(empty($type)){
            return show(config('code.error'),'身份类型不合法','');
        }
        if(empty($phone)){
            return show(config('code.error'),'手机不合法','');
        }
        if(empty($password)){
            return show(config('code.error'),'密码不合法','');
        }
		/*生成token*/
        $token = IAuth::setAppLoginToken($phone);
        $data = array(
            'token' => $token,
            'time_out' => strtotime("+".config('app.login_time_out_day')." days"),
        );
        /*查询这个手机号是否存在*/
		$where['site_ids']	= 1;
		$where['phone']		= $phone;
		$user = Db::name('user')->where($where)->find();
        if($user) {
			if($type == 1 && $user['group_ids'] != 2){
				return show(config('code.error'),'身份类型不匹配','');/*用户*/
			}else if($type == 2 && $user['group_ids'] != 4){
				return show(config('code.error'),'身份类型不匹配','');/*医生*/
			}else if($type == 3 && $user['group_ids'] != 5){
				return show(config('code.error'),'身份类型不匹配','');/*专家*/
			}
            if(!empty($password)) {
                /*判定用户的密码*/
				$reg_time = $user['addtime'];
				$password = md5(md5($reg_time.$password));
                if($password != $user['password']) {
                	return show(config('code.error'),'密码不正确',array());
                }
            }
			$result_update = Db::name('user')->where($where)->update($data);
			if($result_update){
				$Aes = new Aes();
				$result = array(
					'user_id'   => $user['id'],
					'token' 	=> $Aes->encrypt($token."||".$user['id']),
					'time_out'  => $data['time_out'],
				);
				return show(config('code.success'),'ok',$result);
			}else {
				return show(config('code.error'),'error',array());
			}			
        }else{
            return show(config('code.error'),'用户不存在',array());
        }
    }

	/**
	 * 注册
	 */
    public function register(){
		/*POST请求方式校验*/
        if(!request()->isPost()){
            return show(config('code.error'),'您没有权限','');
        }
		/*参数校验*/
		$type 		= input('type');
		$phone 		= input('phone');
		$password 	= input('password');
		$code	 	= input('code');
        if(empty($type)){
            return show(config('code.error'),'身份类型不合法','');
        }
        if(empty($phone)){
            return show(config('code.error'),'手机不合法','');
        }
        if(empty($password)){
            return show(config('code.error'),'密码不合法','');
        }
        if(empty($code)){
            return show(config('code.error'),'验证码不合法','');
        }else{
            /*validate严格校验*/
            $sms_code = Alisms::getInstance()->checkSmsIdentify($phone);
            if($code != $sms_code){
                return show(config('code.error'),'手机短信验证码错误','');
            }		
		}
        /*查询这个手机号是否存在*/
		$where['site_ids']	= 1;
		$where['phone'] = $phone;
		$user = Db::name('user')->where($where)->find();
        if($user){
			return show(config('code.error'),'用户已存在',array());			
        }else{
			/*生成token*/
			$token = IAuth::setAppLoginToken($phone);		
			/*注册数据*/
			if($type == 1){
				$group_id = 2;/*用户*/
			}else if($type == 2){
				$group_id = 4;/*医生*/
			}else if($type == 3){
				$group_id = 5;/*专家*/
			}
			/*密码*/	
			$nowtime = time();
			$password = md5(md5($nowtime.$password));			
			$data = array(
				'group_ids' 	=> $group_id,/*所属管理组*/
				'site_ids' 		=> 1,/*会员所属网站*/
				//'name' 			=> 'yb'.time().mt_rand(100000,999999),/*用户名*/
				'password' 		=> $password,/*密码*/
				'phone' 		=> $phone,/*手机*/
				'token' 		=> $token,
				'time_out' 		=> strtotime("+".config('app.login_time_out_day')." days"),
				'money' 		=> 0,/*余额*/
				'integral' 		=> 0,/*积分*/
				'growth' 		=> 0,/*成长值*/
				'addtime' 		=> $nowtime,/*添加时间*/
				'edittime' 		=> $nowtime,/*修改时间*/
			);
			if($type == 2){
				if(!empty(input('clinic_id'))){
					$data['clinic_id']	= input('clinic_id');
					$data['doctor_type']= 1;/*医生类型:管理员*/
					if(empty(input('name')) || empty(input('realname')) || empty(input('picture')) || empty(input('identity')) || empty(input('identity_pic_a')) || empty(input('identity_pic_b'))){
						return show(config('code.error'),'医生管理员信息缺失','');
					}					
					$data['name']		= input('name');/*帐号名*/
					$data['realname']	= input('realname');/*真实姓名*/
					$data['picture']	= input('picture');/*头像|管理员正面照片*/
					$data['identity']	= input('identity');/*身份证号码*/
					$data['identity_pic_a']= input('identity_pic_a');/*身份证正面*/
					$data['identity_pic_b']= input('identity_pic_b');/*身份证背面*/
					if(!empty(input('practitioners'))){
						$data['practitioners'] = input('practitioners');/*从业资格证*/
					}
					if(!empty(input('physician'))){
						$data['physician'] = input('physician');/*职业医师证书*/
					}
					if(!empty(input('pharmacist'))){
						$data['pharmacist']	= input('pharmacist');/*职业药师证书*/
					}
					/*用户名验证*/
					$namevalidate_where['site_ids']	= 1;
					$namevalidate_where['name'] = $data['name'];
					$namevalidate = Db::name('user')->where($namevalidate_where)->find();
					if($namevalidate){
						return show(config('code.error'),'该账号用户已存在',array());			
					}
				}else{
					return show(config('code.error'),'医生管理员诊所信息缺失','');
				}
			}else{
				$data['name'] = 'yb'.time().mt_rand(100000,999999);/*用户名*/
			}
			$result_insert = Db::name('user')->insert($data);
			$userId = Db::name('user')->getLastInsID();
			if($result_insert){
				$Aes = new Aes();
				$result = array(
					'user_id'   => $userId,
					'token' 	=> $Aes->encrypt($token."||".$user['id']),
					'time_out'  => $data['time_out'],
				);
				return show(config('code.success'),'ok',$result);
			}else {
				return show(config('code.error'),'error',array());
			}
        }
    }
	
	/**
	 * 忘记密码
	 */
    public function password(){
		/*POST请求方式校验*/
        if(!request()->isPost()){
            return show(config('code.error'),'您没有权限','');
        }
		/*参数校验*/
		$type 		= input('type');
		$phone 		= input('phone');
		$password 	= input('password');
		$code	 	= input('code');
        if(empty($type)){
            return show(config('code.error'),'身份类型不合法','');
        }
        if(empty($phone)){
            return show(config('code.error'),'手机不合法','');
        }
        if(empty($password)){
            return show(config('code.error'),'密码不合法','');
        }
        if(empty($code)){
            return show(config('code.error'),'验证码不合法','');
        }else{
            /*validate严格校验*/
            $sms_code = Alisms::getInstance()->checkSmsIdentify($phone);
            if($code != $sms_code){
                return show(config('code.error'),'手机短信验证码错误','');
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
			/*生成token*/
			$token = IAuth::setAppLoginToken($phone);		
			/*密码*/	
			$nowtime = time();
			$password = md5(md5($user['addtime'].$password));
			$data = array(
				'password' 		=> $password,/*密码*/
				'token' 		=> $token,
				'time_out' 		=> strtotime("+".config('app.login_time_out_day')." days"),
				'edittime' 		=> $nowtime,/*修改时间*/
			);
			$result_update = Db::name('user')->where($where)->update($data);
			if($result_update){
				$Aes = new Aes();
				$result = array(
					'user_id'   => $user['id'],
					'token' 	=> $Aes->encrypt($token."||".$user['id']),
					'time_out'  => $data['time_out'],
				);
				return show(config('code.success'),'ok',$result);
			}else {
				return show(config('code.error'),'error',array());
			}
        }
    }
	
	/**
	 * 退出登录
	 */
    public function logout(){
		/*获取token*/
		$access_user_token = input('access_user_token');
        if(empty($access_user_token)) {
            throw new ApiException('access_user_token缺失');
        }
		/*解密token*/
        $obj = new Aes();
        $accessUserToken = $obj->decrypt($access_user_token);
        if(empty($accessUserToken)) {
            throw new ApiException('access_user_token有误');
        }
		/*校验token*/
        if(!preg_match('/||/',$accessUserToken)) {
            throw new ApiException('access_user_token非法');
        }
		/*查询用户*/
        list($token,$id) = explode("||",$accessUserToken);
		$user_where['token'] = $token;
		$user_where['status'] = array('>',0);
		$user = Db::name('user')->field('id,time_out')->where($user_where)->find();
		if(!$user) {
			throw new ApiException('您没有登录');
        }
        /*判定时间是否过期*/
        if(time() > $user['time_out']) {
            throw new ApiException('登录失效');
        }
		/*清空token*/
		$data = array(
			'token' => '',
			'time_out' => ''
		);
		$result_update = Db::name('user')->where($user_where)->update($data);
		if($result_update){
			return show(config('code.success'),'ok',array());
		}else {
			return show(config('code.error'),'error',array());
		}
    }	

	/**
	 * 文件上传
	 */
    public function fileput(){
		$files = isset($_FILES['files'])?$_FILES['files']:'';
		if(empty($files)){
			return show(config('code.error'),'未提交上传文件','');
		}else{
			/*七牛上传*/
			$filename = Upload::fileput($files);	
			if($filename){
				return show(config('code.success'),'ok',$filename);
			}else {
				return show(config('code.error'),'error',array());
			}
		}
    }
	
	/**
	 * 用户名唯一性验证
	 */
    public function namevalidate(){
        if(empty(input('name'))){
            return show(config('code.error'),'参数缺失','');
        }
		$name = input('name');	
        /*查询这个用户名是否存在*/
		$where['site_ids']	= $this->site_id;
		$where['name'] = $name;
		$user = Db::name('user')->where($where)->find();
        if($user){
			return show(config('code.error'),'该用户已存在',array());			
        }else{
			return show(config('code.success'),'ok，该用户名可以使用',array());	
		}
	}
	
	/**
	 * 手机号唯一性验证
	 */
    public function phonevalidate(){
        if(empty(input('phone')) || !is_numeric(input('phone'))){
            return show(config('code.error'),'参数缺失','');
        }
		$phone = input('phone');			
        /*查询这个手机号是否存在*/
		$where['site_ids']	= $this->site_id;
		$where['phone'] = $phone;
		$user = Db::name('user')->where($where)->find();
        if($user){
			return show(config('code.error'),'该手机号已存在',array());			
        }else{
			return show(config('code.success'),'ok，该手机号可以使用',array());	
		}
	}	

    /**
     * 模块内容
     */
    public function block(){
		if(empty(input('block_id'))){
			return show(config('code.error'),'参数不合法',array());
		}
		$block_id = input('block_id');
        /*查询模块*/
		$where['id'] = $block_id;
		$where['status'] = 1;
		$field = 'type,name,title,description,ico,picture,content';
		$block = Db::name('block')->field($field)->where($where)->find();
		if(empty($block)){
			return show(config('code.error'),'该广告位不存在或已关闭',array());
		}
		if(!empty($block['content']) && !empty($block['type'])){
			if($block['type'] == 2){
				/*列表菜单*/
				$block['content'] = json_decode($block['content'],true);
				$block['content'] = $block['content']['type1'];
			}
		}
        if($block){
			return show(config('code.success'),'ok',$block);			
        }else{
			return show(config('code.error'),'error',array());
		}
    }

}