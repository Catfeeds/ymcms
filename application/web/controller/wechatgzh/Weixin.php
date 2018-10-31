<?php
namespace app\web\controller\wechatgzh;
use app\web\controller\Common;
use think\Controller;
use think\Request;
use think\Url;
use think\Db;
class Weixin extends Common 
{
	protected $wx_config;/*微信第三方参数*/
	
	/*构造方法*/
	public function _initialize()
	{
		/*开启资源*/		
		$request = Request::instance();		
		/*微信第三方参数*/
		$this->wx_config = array(
			'appid' 			=> 'wx4b48f142fd1e527e',/*第三方平台ID*/
			'appsecret' 		=> 'cb232eab852801d87400a66bdd283b16',/*第三方平台secert*/
			'encodingAesKey' 	=> 'infokeyazcms2017040220407102smczayedofni888',/*公众号消息加解密Key*/
			'token' 			=> 'infotokenazcms2017040220407102smczanekotofni',/*公众号消息校验Token*/
		);	
		/*获取第三方平台component_access_token*/
		if($request->action() != 'api'){/*屏蔽授权权事件通知*/
			$component_access_token_fun = $this->component_access_token();
			$component_access_token = $component_access_token_fun['content'];
			$this->wx_config['component_access_token'] = $component_access_token;
		}			
	}

	/*授权事件接收URLhttp://a.az-cms.com/weixin/api*/
	public function api(){
		/*获取回调地址推送过来的数据*/
		$encryptMsg   = file_get_contents("php://input");
		/*调用解密接口*/
		$resultDecrypt = $this->msgDecrypt($_GET,$encryptMsg);
		$ComponentVerifyTicket = '';
		if($resultDecrypt['code'] == 100){		
			/*将获取后的XML转换成数组*/
			$msgArr = xmlToarray($resultDecrypt['content']);
			if($msgArr['InfoType'] == 'component_verify_ticket'){
				/*推送component_verify_ticket协议获取*/
				$ComponentVerifyTicket = $msgArr['ComponentVerifyTicket'];
				/*将ComponentVerifyTicket存入数据*/
				if(!empty($ComponentVerifyTicket)){
					$where['id'] = 21;/*微信开发系统*/
					$tree = Db::name('tree')->where($where)->find();
					$configArr = json_decode($tree['config'],true);
					foreach($configArr as $k=>$v){
						if($k == 'ComponentVerifyTicket'){
							$configArr['ComponentVerifyTicket'] = $ComponentVerifyTicket;
						}
						if($k == 'ComponentVerifyTicket_time'){
							$configArr['ComponentVerifyTicket_time'] = time();
						}							
					}
					$data['config'] = json_encode($configArr);
					$result = Db::name('tree')->where($where)->update($data);					
											
				}
			}else if($msgArr['InfoType'] == 'unauthorized'){			
				/*取消授权通知,更新授权状态*/
				$data = array(
					'auth_status'				=> 3,/*授权状态 3:取消授权*/
					'edittime'					=> time()
				);	
				$where['authorizer_appid']	= $msgArr['AuthorizerAppid'];
				$result_update = Db::name('site_weixin')->where($where)->update($data);
			}else if($msgArr['InfoType'] == 'authorized'){			
				/*授权成功通知,更新授权状态和授权码、授权码过期时间*/
				$data = array(
					'auth_status'				=> 1,/*授权状态 2:授权成功*/
					'edittime'					=> time()
				);	
				$where['authorizer_appid']	= $msgArr['AuthorizerAppid'];
				$result_update = Db::name('site_weixin')->where($where)->update($data);
			}else if($msgArr['InfoType'] == 'updateauthorized'){				
				/*授权更新通知,更新授权状态和授权码、授权码过期时间*/
				$data = array(
					'auth_status'				=> 2,/*授权状态 2:授权成功*/
					'edittime'					=> time()
				);	
				$where['authorizer_appid']	= $msgArr['AuthorizerAppid'];
				$result_update = Db::name('site_weixin')->where($where)->update($data);
			}
			echo "success";			
		}		
		//写日志
		//$file = './public/datalog/'.date("YmdHis").mt_rand(1000000,9999999).'.txt';
		//$result	= file_put_contents($file,'msg:'.$resultDecrypt['content'].'，@@@component_verify_ticket：'.$ComponentVerifyTicket);	
	}

	/*获取第三方平台component_access_token*/
	public function component_access_token(){
		$where['id'] = 21;/*微信开发系统*/
		$tree = Db::name('tree')->where($where)->find();
		$configArr = json_decode($tree['config'],true);	
		if($configArr['component_access_token_time'] <= time()){
			$component_verify_ticket = $configArr['ComponentVerifyTicket'];
			$component_access_token_time = time();/*access请求生成时间*/
			$json = '{
						"component_appid":"'.$this->wx_config['appid'].'",
						"component_appsecret": "'.$this->wx_config['appsecret'].'", 
						"component_verify_ticket": "'.$component_verify_ticket.'" 
					}';
			$resultCurl = curl_post('https://api.weixin.qq.com/cgi-bin/component/api_component_token',$json);
			if($resultCurl){
				$component_access_token_arr = json_decode($resultCurl,true);
				$component_access_token = $component_access_token_arr['component_access_token'];
				$component_access_token_time += 7000;/*access有效时间*/
				foreach($configArr as $k=>$v){
					if($k == 'component_access_token'){
						$configArr['component_access_token'] = $component_access_token;
					}
					if($k == 'component_access_token_time'){
						$configArr['component_access_token_time'] = $component_access_token_time;
					}				
				}		
				$data['config'] = json_encode($configArr);
				$resultSave = Db::name('tree')->where($where)->update($data);
				if($resultSave){
					$result = array(
						'code' 		=> 100,
						'msg'		=> 'component_access_token更新成功',
						'content'	=> $component_access_token
					);
					return $result;
				}else{
					$result = array(
				'code' 		=> 400,
						'msg'		=> 'component_access_token更新失败',
						'content'	=> $component_access_token
					);
					return $result;
				}
			}else{
				$result = array(
					'code' 		=> 300,
					'msg'		=> 'component_access_token请求接口失败',
					'content'	=> $configArr['component_access_token']
				);
				return $result;
			}
		}else{
			$result = array(
				'code' 		=> 200,
				'msg'		=> 'component_access_token有效期内',
				'content'	=> $configArr['component_access_token']
			);
			return $result;
		}
	}

	/*全网发布api回复*/
	protected function query_auto_code_api($msgArr){
		/*返回空串*/
		//echo '';
		/*获取信息中的授权码query_auth_code*/
		$auth_code = trim(str_replace("QUERY_AUTH_CODE:","",$msgArr['Content']));
		/*使用授权码换取公众号的授权信息API*/
		$json = '{
					"component_appid":"'.$this->wx_config['appid'].'",
					"authorization_code":"'.$auth_code.'"
				}';
		$resultCurl = curl_post('https://api.weixin.qq.com/cgi-bin/component/api_query_auth?component_access_token='.$this->wx_config['component_access_token'],$json);
		$resultCurlArr = json_decode($resultCurl,true);
		$authorization_info = $resultCurlArr["authorization_info"];	
		/*调用发送客服消息api回复文本消息给粉丝*/
		$content = $auth_code.'_from_api';
		$json_custom = '{
							"touser":"'.$msgArr['FromUserName'].'",
							"msgtype":"text",
							"text":
							{
								 "content":"'.$content.'"
							}				
						}';					
		$url_custom = 'https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token='.$authorization_info['authorizer_access_token'];
		$resultCurl_custom = curl_post($url_custom,$json_custom);			
	}

	/*消息解密*/
	private function msgDecrypt($getArr,$encryptMsg){
		/*获取回调地址推送过来的数据*/
        $timeStamp    = isset($getArr['timestamp'])?$getArr['timestamp']:'';
        $nonce        = isset($getArr['nonce'])?$getArr['nonce']:'';
        $encrypt_type = isset($getArr['encrypt_type'])?$getArr['encrypt_type']:'';
        $msg_sign     = isset($getArr['msg_signature'])?$getArr['msg_signature']:'';
		/*调用解密接口*/
		import('weixin.cryptmsg.wxBizMsgCrypt', EXTEND_PATH);/*引入接口类库文件*/
		$encodingAesKey = $this->wx_config['encodingAesKey'];
		$token = $this->wx_config['token'];
		$appId = $this->wx_config['appid'];		
		$pc = new \WXBizMsgCrypt($token,$encodingAesKey,$appId);
		$xml_tree = new \DOMDocument();
		if(empty($encryptMsg)){
			$encryptMsg = '<xml><AppId><![CDATA[wx19a3b1fe015bc27b]]></AppId><Encrypt><![CDATA[rfBNqj2QknagO/TW70zeeGN2vnvgG4tVY69Io6qgNAPO8tgcF+5b0b1cJfWgDszFjETi+rk2SVNgdhR3ot31tQ2H4kz955r3t/kUfzUfCJYMgsNUxOfQtHFtIogrVIrNhcPJ7GwOxYwaNwqg3VyqBnugFRK+1Uv0IpGitvZlP3wDSGqRyuedukunkum0HkJPZbU3/uSPKJ6MvrlYFqyw6StbkjKjdR9urFe5I2EYOYxujGhkr6FhI+iYStLDXwisqclj156TZYif4wyg95K1PxRFKBuCsXvWIJIaX0cAp4zKPbsH9hZh0ACrDntuwc01/Hf++kPg0/+YeWPx4feTvThUxP9LuxUHwTdMT+vh4ljEqG0LME7rsqI/rk5q8k5UFjuDF/FD+li2V6ddMjP+ge8fFD4ajQ3w2z4Vk9ULMWFwJOCG1Om4jkq7m3rQYbuGqVnuXqKzo6VR6eVmz75iNg==]]></Encrypt></xml>';
		}
		$xml_tree->loadXML($encryptMsg);
		$array_e = $xml_tree->getElementsByTagName('Encrypt');
		$encrypt = $array_e->item(0)->nodeValue;
		$format = "<xml><ToUserName><![CDATA[toUser]]></ToUserName><Encrypt><![CDATA[%s]]></Encrypt></xml>";
		$from_xml = sprintf($format, $encrypt);	
		$msg = '';
		$errCode = $pc->decryptMsg($msg_sign,$timeStamp,$nonce,$from_xml,$msg);
		$ComponentVerifyTicket = '';
		if ($errCode == 0) {
			$result['code'] = 100;
			$result['msg'] 	= '消息解密成功！';
			$result['content'] = $msg;		
		}else{
			$result['code'] = 200;
			$result['msg'] 	= '消息解密失败！';
		}
		return $result;
	}
	
	/*消息加密*/
	private function msgEncrypt($text){
		/*获取回调地址推送过来的数据*/
        $timeStamp    = time();
        $nonce        = noncestr(32);
		/*调用解密接口*/
		import('weixin.cryptmsg.wxBizMsgCrypt', EXTEND_PATH);/*引入接口类库文件*/
		$encodingAesKey = $this->wx_config['encodingAesKey'];
		$token = $this->wx_config['token'];
		$appId = $this->wx_config['appid'];		
		$pc = new \WXBizMsgCrypt($token,$encodingAesKey,$appId);
		$encryptMsg = '';
		$errCode = $pc->encryptMsg($text,$timeStamp,$nonce,$encryptMsg);
		if ($errCode == 0) {
			$result['code'] = 100;
			$result['msg'] 	= '消息加密成功！';
			$result['content'] = $encryptMsg;		
		}else{
			$result['code'] = 200;
			$result['msg'] 	= '消息解密失败！';
		}
		return $result;
	}	
	
	/*
	*回复消息
	*1:接受到的信息数组
	*2:回复信息内容
	*3:回复信息类型
	*/
	private function replyMsg($msgArr,$content,$type){
		$xml = '<xml>
					<ToUserName><![CDATA['.$msgArr['FromUserName'].']]></ToUserName>
					<FromUserName><![CDATA['.$msgArr['ToUserName'].']]></FromUserName>
					<CreateTime>'.time().'</CreateTime>
					<MsgType><![CDATA['.$type.']]></MsgType>';
		if($type == 'text'){
			/*回复文本消息*/	
			$xml .= '<Content><![CDATA['.$content.']]></Content>';
		}else if($type == 'image'){
			/*回复图片消息*/	
			$xml .= '<Image>';
			$xml .= '<MediaId><![CDATA['.$content.']]></MediaId>';
			$xml .= '</Image>';	
		}else if($type == 'voice'){
			/*回复语音消息*/	
			$xml .= '<Voice>';
			$xml .= '<MediaId><![CDATA['.$content.']]></MediaId>';
			$xml .= '</Voice>';	
		}else if($type == 'video'){
			/*回复视频消息*/	
			$xml .= '<Video>';
			$xml .= '<MediaId><![CDATA['.$content['media_id'].']]></MediaId>';
			$xml .= '<Title><![CDATA['.$content['title'].']]></Title>';
			$xml .= '<Description><![CDATA['.$content['description'].']]></Description>';
			$xml .= '</Video>';	
		}else if($type == 'music'){
			/*回复音乐消息*/
			$xml .= '<Music>';
			$xml .= '<Title><![CDATA['.$content['title'].']]></Title>';
			$xml .= '<Description><![CDATA['.$content['description'].']]></Description>';
			$xml .= '<MusicUrl><![CDATA['.$content['music_url'].']]></MusicUrl>';
			$xml .= '<HQMusicUrl><![CDATA['.$content['hq_music_url'].']]></HQMusicUrl>';
			$xml .= '<MediaId><![CDATA['.$content['media_id'].']]></MediaId>';
			$xml .= '</Music>';	
		}else if($type == 'news'){
			/*回复图文消息*/	
			$xml .= '<ArticleCount>'.count($content).'</ArticleCount>';
			$xml .= '<Articles>';
			foreach($content as $k=>$v){
				$xml .= '<item>';
				$xml .= '<Title><![CDATA['.$v['title'].']]></Title>';
				$xml .= '<Description><![CDATA['.$v['description'].']]></Description>';
				$xml .= '<PicUrl><![CDATA['.$v['picurl'].']]></PicUrl>';
				$xml .= '<Url><![CDATA['.$v['url'].']]></Url>';
				$xml .= '</item>';
			}
			$xml .= '</Articles>';	
		}
		$xml .= '</xml>';
		$result = $this->msgEncrypt($xml);
		echo $result['content'];		
	}

	/*微信消息回调*/
	public function callback(){
		$appid = input('id');/*公众号ID*/
		/*获取回调地址推送过来的数据*/
		$encryptMsg   = file_get_contents("php://input");
		/*调用解密接口*/
		$resultDecrypt = $this->msgDecrypt($_GET,$encryptMsg);
		if($resultDecrypt['code'] == 100){
			//写日志
			//$file = './public/datalog/data/'.date("YmdHis").mt_rand(1000000,9999999).'.txt';
			//$result	= file_put_contents($file,'appid：'.$appid.'，MSG:'.$resultDecrypt['content']);			
			/*将获取后的XML转换成数组*/
			$msgArr = xmlToarray($resultDecrypt['content']);
			/*消息或事件类型*/
			if($msgArr['MsgType'] == 'event'){
				/*全网发布事件消息回复*/
				if($msgArr['ToUserName'] == 'gh_3c884a361561' || $msgArr['ToUserName'] == 'gh_8dad206e9538'){
					$this->replyMsg($msgArr,$msgArr['Event'].'from_callback','text');
				}
				/*事件接收*/
				if($msgArr['Event'] == 'subscribe'){
					/*关注事件*/
					if(!empty($msgArr['Ticket'])){
						/*扫描带参数二维码事件,用户未关注时，进行关注后的事件推送*/
						$this->replyMsg($msgArr,'感谢您的关注！','text');
					}else{
						/*非扫码关注*/
						$this->replyMsg($msgArr,'感谢您的关注！','text');
					}
				}else if($msgArr['Event'] == 'unsubscribe'){
					/*取消关注事件*/
					$this->replyMsg($msgArr,'取消关注事件','text');
				}else if($msgArr['Event'] == 'SCAN'){
					/*扫描带参数二维码事件,用户已关注时的事件推送*/
					$this->replyMsg($msgArr,'扫描带参数二维码事件,用户已关注时的事件推送','text');
				}else if($msgArr['Event'] == 'LOCATION'){
					/*上报地理位置事件*/
				}else if($msgArr['Event'] == 'CLICK'){
					/*点击菜单拉取消息时的事件推送*/
				}else if($msgArr['Event'] == 'VIEW'){
					/*点击菜单跳转链接时的事件推送*/
				}
			}else if($msgArr['MsgType'] == 'text'){
				/*文本消息*/
				if($msgArr['ToUserName'] == 'gh_3c884a361561' || $msgArr['ToUserName'] == 'gh_8dad206e9538'){
					/*全网发布api回复*/
					$msgContent = $msgArr['Content'];
					if(strstr($msgContent,'QUERY_AUTH_CODE')){
						$this->query_auto_code_api($msgArr);
					}
					/*全网发布文本回复*/
					if($msgArr['Content'] == 'TESTCOMPONENT_MSG_TYPE_TEXT'){
						$this->replyMsg($msgArr,'TESTCOMPONENT_MSG_TYPE_TEXT_callback','text');
					}
				}
				if($msgArr['Content'] == '123'){
					/*回复文本消息
					$this->replyMsg($msgArr,'你好！我就是我，是颜色不一样的烟火！','text');
					*/
					/*回复图片消息
					$this->replyMsg($msgArr,'72BbOKzEAb6b1lPPjYGfTY5idQukI9gYXBNoy8iKI4U','image');	
					*/
					/*回复语音消息
					$this->replyMsg($msgArr,'72BbOKzEAb6b1lPPjYGfTZtMPJOSGf0cPPxbSY25ug0','voice');	
					*/
					/*回复视频消息*/
					$content = array(
						'title' 		=> '我就是我',
						'description' 	=> '我就是我，是颜色不一样的烟火！',				
						'media_id' 		=> '72BbOKzEAb6b1lPPjYGfTdNZwIlImqWrTKECtD6zj44'
					);
					$this->replyMsg($msgArr,$content,'video');
								
					/*回复音乐消息
					$content = array(
						'title' 		=> '我就是我',
						'description' 	=> '我就是我，是颜色不一样的烟火！',
						'music_url'		=> 'http://www.9ku.com/play/464446.htm',
						'hq_music_url'	=> 'http://www.9ku.com/play/464446.htm',				
						'media_id' 		=> '72BbOKzEAb6b1lPPjYGfTY5idQukI9gYXBNoy8iKI4U'
					);
					$this->replyMsg($msgArr,$content,'music');
					*/
					/*回复图文消息
					$content = array(
						array(
							'title' 		=> '第一：111111111111111',
							'description' 	=> '111简介',
							'picurl'		=> 'http://www.az-cms.com/public/upload/space/site_1/20170705/32647155_code3.jpg',
							'url'			=> 'http://www.az-cms.com'
						),
						array(
							'title' 		=> '第二：2222222222222211',
							'description' 	=> '2简介',
							'picurl'		=> 'http://www.az-cms.com/public/upload/space/site_1/20170705/32647155_code3.jpg',
							'url'			=> 'http://www.az-cms.com'						
						)
					);
					$this->replyMsg($msgArr,$content,'news');
					*/
				}				
			}else if($msgArr['MsgType'] == 'image'){
				/*图片消息*/
				$this->replyMsg($msgArr,'谢谢你给我图片','text');
			}else if($msgArr['MsgType'] == 'voice'){
				/*语音消息*/
				$this->replyMsg($msgArr,'谢谢你给我语音','text');
			}else if($msgArr['MsgType'] == 'video'){
				/*视频消息*/
				$this->replyMsg($msgArr,'谢谢你给我视频','text');
			}else if($msgArr['MsgType'] == 'shortvideo'){
				/*小视频消息*/
				$this->replyMsg($msgArr,'谢谢你给我小视频','text');
			}else if($msgArr['MsgType'] == 'location'){
				/*地理位置消息*/
				$this->replyMsg($msgArr,'谢谢你给我地理位置','text');
			}else if($msgArr['MsgType'] == 'link'){
				/*链接消息*/
				$this->replyMsg($msgArr,'谢谢你给我链接','text');
			}else{
				echo 'success';
			}	
		}
	}

	
}