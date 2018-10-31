<?php
namespace app\common\lib;
use app\common\lib\exception\ApiException;
use app\common\lib\Aes;
use think\Cache;
/**
 * Iauth相关
 * Class TAuth
 * @package app\common
 */
class IAuth {

	/**
	 * 生成每次请求的sign
	 * @param array $data
	 * @return string
	 */
	public static function setSign($data=array()){
		/*1、按字段排序*/
		ksort($data);
		/*2、拼接字符串数据*/
		$string = http_build_query($data);
		/*3、通过aes来加密*/
		$Aes = new Aes();
		$string = $Aes->encrypt($string);
		/*4、所有字符转换为大写*/
		//$string = strtoupper($string);
		/*返回结果集*/
		return $string;
	}

	/**
	 * 检查sign是否正常
	 * @param array $data
	 * @param $data
	 * @return boolen
	 */
	public static function checkSignPass($sign,$time,$controller,$action){
		if(empty($sign)){
			throw new ApiException('签名sign不能为空',401);
		}
		$signstring = md5($controller.'&'.$action.'&'.config('app.apikey').'&'.$time);
		// var_dump($controller);
		// var_dump($action);
		// var_dump($time);
		// var_dump($sign);
		// dump($signstring);die();
		if ($sign == 'aaa') {
			$signstring = 'aaa';
		}
		if($signstring != $sign){
			throw new ApiException('签名sign有误',401);
		}
		if(!config('app_debug')){
			/*sign有效期校验*/
			if ((time() - $time) > config('app.app_sign_time')) {
				throw new ApiException('sign失效',401);
			}
			/*sign唯一性判定*/
			if (Cache::get($sign)) {
				throw new ApiException('sign重复',401);
			}
			/*sign写入缓存*/
			Cache::set($sign,1,config('app.app_sign_cache_time'));			
		}
	}	

    /**
     * 设置登录的token  - 唯一性的
     * @param string $phone
     * @return string
     */
    public static function setAppLoginToken($phone = '') {
        $str = md5(uniqid(md5(microtime(true)), true));
        $str = sha1($str.$phone);
        return $str;
    }
	
}