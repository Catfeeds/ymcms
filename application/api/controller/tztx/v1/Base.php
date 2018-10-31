<?php
/**
 * 用户端-接口
 */
namespace app\api\controller\tztx\v1;
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
		$app_version_field = array('is_force','version','apptype','apk_url','upgrade_point');
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
		$phone 		= input('phone');
		$password 	= input('password');
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
		$where['phone']	= $phone;
		$user = Db::name('wine_users')->where($where)->find();
        if($user) {
            if(!empty($password)) {
                /*判定用户的密码*/
				$reg_time = $user['addtime'];
				$password = md5(md5($reg_time.$password));
                if($password != $user['password']) {
                	return show(config('code.error'),'密码不正确',array());
                }
            }
			$result_update = Db::name('wine_users')->where($where)->update($data);
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
		$phone 		= input('phone');
		$password 	= input('password');
		$code	 	= input('code');
		$retail	 	= input('retail');
        if(!empty($retail)){
        	/*正则替换*/
            $retail = preg_replace('/\D/', '', $retail);
            /*查询上级是否存在*/
            $user_info = Db::name('wine_users')->where('id', $retail)->count();
            if (empty($user_info)) {
            	$retail = null;
            }
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
		$where['phone'] = $phone;
		$user = Db::name('wine_users')->where($where)->find();
        if($user){
			return show(config('code.error'),'用户已存在',array());			
        }else{
			/*生成token*/
			$token = IAuth::setAppLoginToken($phone);		
			/*注册数据*/
			/*密码*/	
			$nowtime = time();
			$password = md5(md5($nowtime.$password));			
			$data = array(
				//'name' 			=> 'yb'.time().mt_rand(100000,999999),/*用户名*/
				'password' 		=> $password,/*密码*/
				'phone' 		=> $phone,/*手机*/
				'token' 		=> $token,
				'time_out' 		=> strtotime("+".config('app.login_time_out_day')." days"),
				'money' 		=> 0,/*余额*/
				'retail'		=> $retail,/*邀请人*/
				'agentid'		=> $retail,/*分销商*/
				'addtime' 		=> $nowtime,/*添加时间*/
				'edittime' 		=> $nowtime,/*修改时间*/
			);
			$result_insert = Db::name('wine_users')->insert($data);
			$userId = Db::name('wine_users')->getLastInsID();
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
		$phone 		= input('phone');
		$password 	= input('password');
		$code	 	= input('code');
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
		$where['phone']	= $phone;
		$user = Db::name('wine_users')->where($where)->find();
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
			$result_update = Db::name('wine_users')->where($where)->update($data);
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
		$user = Db::name('wine_users')->field('id,time_out')->where($user_where)->find();
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
		$result_update = Db::name('wine_users')->where($user_where)->update($data);
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
        if(empty(input('nickname'))){
            return show(config('code.error'),'参数缺失','');
        }
		$nickname = input('nickname');	
        /*查询这个用户名是否存在*/
		$where['nickname'] = $nickname;
		$user = Db::name('wine_users')->where($where)->find();
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
		$where['phone'] = $phone;
		$user = Db::name('wine_users')->where($where)->find();
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

    /**
	 * 短信登录
	 */
    public function logins(){
		/*POST请求方式校验*/
        if(!request()->isPost()){
            return show(config('code.error'),'您没有权限','');
        }
		/*参数校验*/
		$phone 		= input('phone');
		$code 	= input('code');
        if(empty($phone)){
            return show(config('code.error'),'手机不合法','');
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
		/*生成token*/
        $token = IAuth::setAppLoginToken($phone);
        $data = array(
            'token' => $token,
            'time_out' => strtotime("+".config('app.login_time_out_day')." days"),
        );
        /*查询这个手机号是否存在*/
		$where['phone']		= $phone;
		$user = Db::name('wine_users')->where($where)->find();
        if($user) {
			$result_update = Db::name('wine_users')->where($where)->update($data);
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


}