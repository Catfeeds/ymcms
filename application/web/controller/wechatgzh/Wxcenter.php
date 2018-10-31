<?php
namespace app\web\controller\wechatgzh;
use app\web\controller\wechatgzh\Weixin;
use think\Controller;
use think\Request;
use think\Url;
use think\Db;
class Wxcenter extends Weixin
{

	protected $admin_login;/*登录信息*/
	public $referer_url;/*返回上一步链接*/
	
	/*构造方法*/
	public function _initialize()
	{
		/*重载父类构造方法*/
		parent::_initialize();
		/*开启资源*/		
		$request = Request::instance();
		/*登录验证*/
		$admin_login = session('admin_login');
		$this->admin_login = $admin_login;
		$is_login = isset($admin_login['is_login']) ? $admin_login['is_login'] : 0;
		if (!$admin_login || $admin_login['is_login'] != 100) {
			$this->redirect('base/login');
			die();
		}
		/**调用公共脚本类库 过滤ajax传参数混淆**/
		$public_static = '/public/static';
		if (!Request::instance()->isAjax()) {
			echo '<script src="' . $public_static . '/html/js/jquery-1.9.1.min.js"></script>';
			echo '<script src="' . $public_static . '/html/js/public.js"></script>';
		}	
		/*返回上一步链接*/
		$this->referer_url = isset($_SERVER['HTTP_REFERER'])?$_SERVER['HTTP_REFERER']:'location';
		if($this->referer_url !=  'location'){
			$this->referer_url = '"'.$this->referer_url.'"';
		}
	}
	
	/*获取预授权码pre_auth_code,用户授权页面*/
	public function impower(){
		/*获取预授权码pre_auth_code*/
		$json = '{
					"component_appid":"'.$this->wx_config['appid'].'" 
				}';
		$resultCurl = curl_post('https://api.weixin.qq.com/cgi-bin/component/api_create_preauthcode?component_access_token='.$this->wx_config['component_access_token'],$json);
		if(!empty($resultCurl)){
			$resultCurlArr = json_decode($resultCurl,true);
			$pre_auth_code = $resultCurlArr['pre_auth_code'];
			echo '<script>location="https://mp.weixin.qq.com/cgi-bin/componentloginpage?component_appid='.$this->wx_config['appid'].'&pre_auth_code='.$pre_auth_code.'&redirect_uri='.url('wxcenter/accept').'";</script>';
		}else{
			echo '授权码pre_auth_code接口请求失败。';
		}
	}

	/*创建一个微信站点*/
	private function createsite_weixin($data){
		$user_id = $this->admin_login['user_id'];
		/*站点数据*/
		$now_time = time();
		$data_site['server_id'] 	= 21;/*应用ID*/
		$data_site['language_id'] 	= 1;/*语种ID*/
		$data_site['payment_ids'] 	= '1,2';/*支付IDS*/
		$data_site['name'] 			= '未命名';
		$data_site['addtime'] 		= $now_time;
		$data_site['edittime'] 		= $now_time;		
		/*启动事务*/
		//Db::startTrans();
		//try{
			/*创建网站*/
			Db::name('site')->insert($data_site);
			$lastSiteId = Db::name('site')->getLastInsID();
			/*更新站点编号*/
			$save_site['no'] = 'a'.mt_rand(1000,9999).$lastSiteId;
			Db::name('site')->where('id',$lastSiteId)->update($save_site);
			/*新建站点‘管理员’会员组以及‘VIP’会员组*/
			$system_group_where['site_id'] = 1;
			$system_group_where['type'] = array('IN','1,2');
			$system_group = Db::name('group')->where($system_group_where)->select();
			foreach($system_group as $k=>$v){
				$site_group[$k] = $v;
				foreach($v as $k2=>$v2){
					$site_group[$k]['id'] = NULL;
					$site_group[$k]['site_id'] = $lastSiteId;
					$site_group[$k]['addtime'] = $now_time;
					$site_group[$k]['edittime']= $now_time;						
				}
				Db::name('group')->insert($site_group[$k]);
			}
			/*更新用户-会员组集以及站点集字符串*/
			$system_group = Db::name('user')->field('group_ids,site_ids')->where('id='.$user_id)->find();
			$group_ids_arr = explode(',',$system_group['group_ids']);
			$site_ids_arr = explode(',',$system_group['site_ids']);
			/*查询管理员组*/
			$site_group_type2_where['site_id'] = $lastSiteId;
			$site_group_type2_where['type'] = 2; /*网站管理员*/
			$site_group_type2 = Db::name('group')->field('id')->where($site_group_type2_where)->find();
			/*已知身份是老站长还是新站长(奥站会员)*/		
			if(in_array('2',$group_ids_arr)){					
				/*将奥站会员转换为新网站管理员*/
				foreach($group_ids_arr as $k=>$v){
					if($v == '2'){
						$group_ids_arr[$k] = $site_group_type2['id'];
						$site_ids_arr[$k] = $lastSiteId;
					}
				}
				$group_ids_str = implode(',',$group_ids_arr);
				$site_ids_str = implode(',',$site_ids_arr);										
			}else{
				/*新增网站管理组及站点*/
				$group_ids_str = $system_group['group_ids'].','.$site_group_type2['id'];
				$site_ids_str = $system_group['site_ids'].','.$lastSiteId;	
			}
			$save_user['group_ids'] = $group_ids_str;
			$save_user['site_ids'] = $site_ids_str;
			Db::name('user')->where('id='.$user_id)->update($save_user);
			/*新增微信站点帐号副表*/
			$data['site_id'] = $lastSiteId;
			$data['addtime'] = $now_time;
			$result = Db::name('site_weixin')->insert($data);									
			/*提交事务*/
			//Db::commit();
			return $lastSiteId;/*免费，自动生成网站成功!ID为*/
		//} catch (\Exception $e) {
			/*回滚事务*/
			//Db::rollback();
			//return 0;/*抱歉，自动生成网站失败!*/
		///}	
	}
	
	/*授权同步回调页面*/
	public function accept(){
		/*使用授权码换取公众号或小程序的接口调用凭据和授权信息*/
		$auth_code_value = isset($_GET['auth_code'])?$_GET['auth_code']:'';
		$auth_code_time	 = time() + 500;
		$json = '{
					"component_appid":"'.$this->wx_config['appid'].'",
					"authorization_code":"'.$auth_code_value.'"
				}';
		$newtime = time();
		$resultCurl = curl_post('https://api.weixin.qq.com/cgi-bin/component/api_query_auth?component_access_token='.$this->wx_config['component_access_token'],$json);
		$resultCurlArr = json_decode($resultCurl,true);
		$data = array(
			//'auth_status'					=> 1,/*已授权,回调异步通知处理*/
			'auth_code'						=> $auth_code_value,/*授权码*/
			'auth_code_time'				=> $auth_code_time,/*授权码有效期*/
			'authorizer_appid'				=> $resultCurlArr["authorization_info"]['authorizer_appid'],
			'authorizer_access_token'		=> $resultCurlArr["authorization_info"]['authorizer_access_token'],
			'authorizer_access_token_time'	=> $newtime+7000,
			'authorizer_refresh_token'		=> $resultCurlArr["authorization_info"]['authorizer_refresh_token'],
			'authorizer_info'				=> $resultCurl,
			'edittime'						=> $newtime
		);
		$where['authorizer_appid'] = $resultCurlArr["authorization_info"]['authorizer_appid'];/*微信开发系统*/
		$resultAppid = Db::name('site_weixin')->where($where)->find();		
		if(!$resultAppid){
			$result = $this->createsite_weixin($data);		
			if($result){
				/*更新授权公众号/小程序的帐户信息*/
				$reusltWxUserUpdate = $this->weixinUserUpdate($resultCurlArr["authorization_info"]['authorizer_appid']);
				if($reusltWxUserUpdate['code']){
					echo '<script>$(document).ready(function(){alertBox("授权信息更新成功...","'.url('wxcenter/lists').'");})</script>';
				}else{
					echo '<script>$(document).ready(function(){alertBox("授权信息更新失败...","'.url('wxcenter/lists').'");})</script>';
				}
			}else{
				echo '<script>$(document).ready(function(){alertBox("授权添加失败...","'.url('wxcenter/impower').'");})</script>';
			}
		}else{
			$result = Db::name('site_weixin')->where($where)->update($data);
			if($result){
				/*更新授权公众号/小程序的帐户信息*/
				$reusltWxUserUpdate = $this->weixinUserUpdate($resultCurlArr["authorization_info"]['authorizer_appid']);
				if($reusltWxUserUpdate['code']){
					echo '<script>$(document).ready(function(){alertBox("授权信息更新成功...","'.url('wxcenter/lists').'");})</script>';
				}else{
					echo '<script>$(document).ready(function(){alertBox("授权信息更新失败...","'.url('wxcenter/lists').'");})</script>';
				}
			}else{
				echo '<script>$(document).ready(function(){alertBox("授权更新失败...","'.url('wxcenter/impower').'");})</script>';
			}
		}
	}
		
	/*获取授权方的帐号基本信息并更新*/
	private function weixinUserUpdate($authorizer_appid){
		/*获取授权方的帐号基本信息*/
		$json = '{
					"component_appid":"'.$this->wx_config['appid'].'",
					"authorizer_appid":"'.$authorizer_appid.'"
				}';
		$resultCurl = curl_post('https://api.weixin.qq.com/cgi-bin/component/api_get_authorizer_info?component_access_token='.$this->wx_config['component_access_token'],$json);
		$resultCurlArr = json_decode($resultCurl,true);
		$where['authorizer_appid']	= $authorizer_appid;
		$site_weixin = Db::name('site_weixin')->where($where)->find();			
		/*写入公众号、小程序公共信息*/
		$data = array(
			'name'						=> $resultCurlArr['authorizer_info']['nick_name'],
			'ico'						=> $resultCurlArr['authorizer_info']['head_img'],
			'picture'					=> $resultCurlArr['authorizer_info']['qrcode_url'],
			'description'				=> $resultCurlArr['authorizer_info']['principal_name'],
			'content'					=> $resultCurlArr['authorizer_info']['signature'],
			'more'						=> $resultCurl,
			'edittime'					=> time()
		);	
		$where_update['id']	= $site_weixin['site_id'];
		$result_update = Db::name('site')->where($where_update)->update($data);	
		if($result_update){
			$result['code'] = 100;
			$result['msg']	= '授权资料更新成功！';
		}else{
			$result['code'] = 200;
			$result['msg']	= '授权资料更新失败！';		
		}
		return $result;
	}
		
	/*获取（刷新）授权公众号或小程序的接口调用凭据（令牌）*/
	public function authorizer_access_token($authorizer_appid){
		$where['authorizer_appid'] = $authorizer_appid;/*微信开发系统*/
		$weixin = Db::name('site_weixin')->where($where)->find();
		if($weixin['authorizer_access_token_time'] <= time()){
			$authorizer_access_token_time = time();/*access请求生成时间*/
			$json = '{
						"component_appid":"'.$this->wx_config['appid'].'",
						"authorizer_appid": "'.$authorizer_appid.'", 
						"authorizer_refresh_token": "'.$weixin['authorizer_refresh_token'].'" 
					}';
			$resultCurl = curl_post('https://api.weixin.qq.com/cgi-bin/component/api_authorizer_token?component_access_token='.$this->wx_config['component_access_token'],$json);
			if($resultCurl){
				$authorizer_access_token_arr = json_decode($resultCurl,true);
				$data['authorizer_access_token'] = $authorizer_access_token_arr['authorizer_access_token'];
				$data['authorizer_access_token_time'] = $authorizer_access_token_time + 7000;/*access有效时间*/		
				$data['authorizer_refresh_token'] = $authorizer_access_token_arr['authorizer_refresh_token'];
				$resultSave = Db::name('site_weixin')->where($where)->update($data);
				if($resultSave){
					$result = array(
						'code' 		=> 100,
						'msg'		=> 'authorizer_access_token更新成功',
						'content'	=> $data['authorizer_access_token']
					);
					return $result;
				}else{
					$result = array(
						'code' 		=> 400,
						'msg'		=> 'authorizer_access_token更新失败',
						'content'	=> $data['authorizer_access_token']
					);
					return $result;
				}
			}else{
				$result = array(
					'code' 		=> 300,
					'msg'		=> 'authorizer_access_token请求接口失败',
					'content'	=> $weixin['authorizer_access_token']
				);
				return $result;
			}
		}else{
			$result = array(
				'code' 		=> 200,
				'msg'		=> 'authorizer_access_token有效期内',
				'content'	=> $weixin['authorizer_access_token']
			);
			return $result;
		}
	}	
	
	/*用户公众帐户列表*/
	public function lists(){
		$where['site_id'] = $this->admin_login['site_id'];
		$weixinArr = Db::name('site_weixin')->where($where)->select();
		echo '<h1>我的公众号/小程序：</h1><hr />';
		foreach($weixinArr as $k=>$v){
			echo '<a href="'.url('wxuser/index').'?id='.$v['id'].'">'.$v['name'].'</a>';
		}
	}
		
}