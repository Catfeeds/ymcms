<?php
namespace app\web\controller\wechatgzh;
use app\web\controller\Common;
use think\Controller;
use think\Request;
use think\Url;
use think\Db;
class Wx extends Common {

	private $authorizer_access_token;/*公众号全局acess_token*/
	private $weixin;/*公众账户信息*/
	protected $wx_config;/*微信第三方参数*/

	/*构造方法*/
	public function _initialize()
	{
		/*重载父类构造方法*/
		parent::_initialize();
		/*微信第三方参数*/
		$this->wx_config = array(
			'appid' 			=> 'wx4b48f142fd1e527e',/*第三方平台ID*/
			'appsecret' 		=> 'cb232eab852801d87400a66bdd283b16',/*第三方平台secert*/
			'encodingAesKey' 	=> 'infokeyazcms2017040220407102smczayedofni888',/*公众号消息加解密Key*/
			'token' 			=> 'infotokenazcms2017040220407102smczanekotofni',/*公众号消息校验Token*/
		);
		$component_access_token_fun = action('wechatgzh.weixin/component_access_token');
		$component_access_token = $component_access_token_fun['content'];
		$this->wx_config['component_access_token'] = $component_access_token;	
		/*检测公众号全局acess_token*/
		$authorizer_access_token_arr = $this->authorizer_access_token_set();
		$this->authorizer_access_token = $authorizer_access_token_arr['content'];
		/*location_report(地理位置上报选项|0:无上报|1:进入会话时上报|2:每5s上报)*/
		$location_report_result = $this->getandset_authorizer_option('location_report',2);
		/*voice_recognize（语音识别开关选项|0:关闭语音识别|1:开启语音识别）*/
		$voice_recognize_result = $this->getandset_authorizer_option('voice_recognize',1);
		/*customer_service（多客服开关选项|0:关闭多客服|1:开启多客服）*/
		$customer_service_result = $this->getandset_authorizer_option('customer_service',1);
	}
	
	/*检测公众号全局acess_token*/
	private function authorizer_access_token_set(){
		$id = $this->site_id;
		if(empty($this->site_id)){
			echo '<script>$(document).ready(function(){alertBox("帐号参数缺失...",'.$this->referer_url.');})</script>';
			die();
		}else{
			$where['site_id'] = $this->site_id;
			$weixin = Db::name('site_weixin')->where($where)->find();
			$this->weixin = $weixin;
			if(!$weixin){
				echo '<script>$(document).ready(function(){alertBox("帐号信息缺失...",'.$this->referer_url.');})</script>';
				die();
			}else{
				$result = action('Wxcenter/authorizer_access_token',array('authorizer_appid'=>$weixin['authorizer_appid']));
				return $result;
			}
		}	
	}

	/*自动获取并设置-授权方的选项设置信息*/
	private function getandset_authorizer_option($option_name,$option_value){
		/*获取选项设置信息*/
		$json = '{
					"component_appid":"'.$this->wx_config['appid'].'",
					"authorizer_appid":"'.$this->weixin['authorizer_appid'].'", 
					"option_name":"'.$option_name.'" 
				}';
		$resultCurl = curl_post('https://api.weixin.qq.com/cgi-bin/component/api_get_authorizer_option?component_access_token='.$this->wx_config['component_access_token'],$json);
		if($resultCurl){
			$authorizer_option_arr = json_decode($resultCurl,true);
			if($authorizer_option_arr['option_value'] != $option_value){
				/*设置选项设置信息*/
				$json_set = '{
							"component_appid":"'.$this->wx_config['appid'].'",
							"authorizer_appid":"'.$this->weixin['authorizer_appid'].'", 
							"option_name":"'.$option_name.'",
							"option_value":"'.$option_value.'"
						}';
				$resultCurl_set = curl_post('https://api.weixin.qq.com/cgi-bin/component/api_set_authorizer_option?component_access_token='.$this->wx_config['component_access_token'],$json_set);
				if($resultCurl_set){
					$authorizer_option_set_arr = json_decode($resultCurl_set,true);
					if($authorizer_option_set_arr['errcode'] == 0){
						$result['code'] = 100;
						$result['msg'] 	= '修改成功！';					
					}else{
						$result['code'] = 400;
						$result['msg'] 	= '修改失败！';						
					}				
				}
			}else{
				$result['code'] = 300;
				$result['msg'] 	= '成功，与目标参数一致！';
			}
		}else{
			$result['code'] = 200;
			$result['msg'] 	= '抱歉，参数校验一致！';
		}
		return $result;
	}
	
	/*用户公众帐户管理中心*/
	public function imtext(){
		dump($this->authorizer_access_token);
		/*获取素材列表
		$json = '{
					"type":"video",
					"offset":0, 
					"count":20
				}';
		$resultCurl = curl_post('https://api.weixin.qq.com/cgi-bin/material/batchget_material?access_token='.$this->authorizer_access_token,$json);
		if($resultCurl){
			$authorizer_option_arr = json_decode($resultCurl,true);
			dump($authorizer_option_arr);
		}
		*/
		/*分配变量*/
		$this->assign('title', '文字回复'); /*页面标题*/
		$this->assign('keywords', '文字回复'); /*页面关键词*/
		$this->assign('description', '文字回复'); /*页面描述*/
		/*调用模板*/
		//return $this->fetch(TEMP_FETCH);
	}
		
}