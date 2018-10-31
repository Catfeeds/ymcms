<?php
/**
 * 时间
 * Class Time
 * @package app\api
 */
namespace app\api\controller;

use think\Controller;
use app\common\lib\exception\ApiException;
use app\common\lib\Aes;

class Time extends Controller {
    public function index(){
		/*参数校验*/
		$key = input('key');
        if(empty($key) || $key != config('app.apikey')){
            return show(config('code.error'),'参数不合法','',404);
        }		
        return show(1,'ok',time());
    }
}