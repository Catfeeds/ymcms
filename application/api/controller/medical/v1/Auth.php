<?php
namespace app\api\controller\medical\v1;
use app\api\controller\Common;
use app\common\lib\IAuth;
use think\Controller;
use app\common\lib\exception\ApiException;
use app\common\lib\Aes;
use think\Db;
/**
 * 客户端auth登录权限基础类库
 * 1、每个接口(需要登录  个人中心 点赞 评论）都需要去集
 * 2、判定 access_user_token 是否合法
 * 3、用户信息 - 》 user
 * Class Auth
 * @package app\api\controller\v1
 */
class Auth extends Common{

    /**
     * 登录用户的基本信息
     * @var array
     */
    public $user = array();
	
    /**
     * 初始化
     */
    public function _initialize(){
		/*继承父类构造方法*/
        parent::_initialize();
		/*登录校验*/
		$this->isLogin();
    }

    /**
     * 判定是否登录
     * @return  boolen
     */
    public function isLogin() {
		/*获取token*/
		$access_user_token = input('access_user_token');
        if(empty($access_user_token)) {
            throw new ApiException('access_user_token缺失',401);
        }
		/*解密token*/
        $obj = new Aes();
        $accessUserToken = $obj->decrypt($access_user_token);
        if(empty($accessUserToken)) {
            throw new ApiException('access_user_token有误',401);
        }
		/*校验token*/
        if(!preg_match('/||/',$accessUserToken)) {
            throw new ApiException('access_user_token非法',401);
        }
		/*查询用户*/
        list($token,$id) = explode("||",$accessUserToken);
		$user_where['token'] = $token;
		$user_where['status'] = array('>',0);
		$user_field = array('id','phone','nickname','realname','picture','sex','year','month','day','addtime','time_out');
		$user = Db::name('user')->field($user_field)->where($user_where)->find();
		if(!$user) {
			throw new ApiException('您没有登录',401);
        }
        /*判定时间是否过期*/
        if(time() > $user['time_out']) {
            throw new ApiException('登录失效',401);
        }
		/*用户信息*/
        $this->user = $user;
    }
}