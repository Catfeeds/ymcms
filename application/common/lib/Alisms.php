<?php
namespace app\common\lib;
use think\Cache;
use think\Log;
/**
 * 阿里发送短信基础类库
 * Class Alidayu
 * @package app\common\lib
 */
class Alisms {

    const LOG_TPL = "alisms:";
    /**
     * 静态变量保存全局的实例
     * @var null
     */
    private static $_instance = null;

    /**
     * 私有的构造方法
     */
    private function __construct() {

    }

    /**
     * 静态方法 单例模式统一入口
     */
    public static function getInstance() {
        if(is_null(self::$_instance)) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /**
     * 设置短信验证
     * @param int $phone
     * @return bool
     */
    public function setSmsIdentify($phone=0,$type=1) {
        /*生成验证码随机数*/
        $code = mt_rand(100000,999999);
		/*发送短信*/
        try{
			/*引入阿里短信API*/
			include_once './vendor/alisms/aliyun-php-sdk-core/Config.php';
			include_once './vendor/alisms/Dysmsapi/Request/V20170525/SendSmsRequest.php';
			$accessKeyId = config('alisms.appKey');//阿里云短信keyId
			$accessKeySecret = config('alisms.secretKey');//阿里云短信keysecret
			//短信API产品名
			$product = "Dysmsapi";
			//短信API产品域名
			$domain = "dysmsapi.aliyuncs.com";
			//暂时不支持多Region
			$region = "cn-hangzhou";
			//初始化访问的acsCleint
			$profile = \DefaultProfile::getProfile($region, $accessKeyId, $accessKeySecret);
			\DefaultProfile::addEndpoint("cn-hangzhou", "cn-hangzhou", $product, $domain);
			$acsClient= new \DefaultAcsClient($profile);
			$request = new \Dysmsapi\Request\V20170525\SendSmsRequest;
			$request->setPhoneNumbers($phone);//必填-短信接收号码
			$request->setSignName(config('alisms.signName'));//必填-短信签名
			//必填-短信模板Code
			if($type == 1){
				/*注册*/
				$request->setTemplateCode(config('alisms.templateCode'));
			}else if($type == 2){
				/*忘记密码*/
				$request->setTemplateCode(config('alisms.tempCode_pass'));
			}else if($type == 3){
				/*手机绑定*/
				$request->setTemplateCode(config('alisms.tempCode_phonebind'));
			}
			//选填-假如模板中存在变量需要替换则为必填(JSON格式)
			$request->setTemplateParam("{\"code\":\"".$code."\"}");//短信签名内容
			//选填-发送短信流水号
			//$request->setOutId("1234");
			//发起访问请求
			$resp = $acsClient->getAcsResponse($request);
        }catch(\Exception $e) {
            // 记录日志
            Log::write(self::LOG_TPL."alisms_send_sms_error_1====".$e->getMessage());
            return false;
        }
		/*处理结果集*/
        if($resp && $resp->Code == 'OK') {
            /*设置验证码失效时间*/
            Cache::set($phone,$code,config('alisms.identify_time'));
            return true;
        }else{
            Log::write(self::LOG_TPL."alisms_send_sms_error_2====".json_encode($resp));
			return false;
        }
    }

    /**
     * 根据手机号码查询验证码是否正常
     * @param int $phone
     */
    public function checkSmsIdentify($phone = 0) {
        if(!$phone) {
            return false;
        }
        return Cache::get($phone);
    }

}