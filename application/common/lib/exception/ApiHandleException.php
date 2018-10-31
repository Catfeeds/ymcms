<?php
namespace app\common\lib\exception;
use think\exception\Handle;
class ApiHandleException extends Handle{

	/**
	 * http 状态码
	 * @var int
	 */
	public $httpCode = 200;
	
	/*APP API异常处理*/
	public function render(\Exception $e){
		return show($e->getCode(),$e->getMessage(),array(),$this->httpCode);
	}
	
}