<?php
namespace app\api\controller\tztx\v1;
use app\api\controller\Common;
use think\Controller;
use app\common\lib\exception\ApiException;
use app\common\lib\Aes;
use app\common\lib\IAuth;
use app\common\lib\Alisms;
use think\Db;
/*用户端-登录*/
class Userauth extends Auth{


	/**
     * 应用ID
     */
    const APPID = '2018070360542305';
    /**
     *请填写开发者私钥去头去尾去回车，一行字符串
     */
    const RSA_PRIVATE_KEY = 'MIIEpQIBAAKCAQEArqdUuvC7aAFkeFCJGLx1JoAIev50JIf3vJrXVHGfBr0SZ3stWUl6jvPtBAZRtpWWMNYVwj5AoJDJvyoVvIfCMlS9ZTjJXD2sMdecxUD9h5rcnJhgiMivkA6pm+BPr58ncBkuFwgMpgm2caYQFddEiaiwcy+WbxoXl+200YtQZuNTUGkD+50SUOvnbkBxhS+DddtxLamQxVpHQ0V45DsBse7rDZywUA8vj1EBKuHCeqDBSb16vm1sv7FPfT09T6HOwmLGRS0TrqMeJmT7xIfAWT1TClSmaTCv9lvtGhpN9YmQE9KiHj/hcRctQQFjLigz5sPDNGdNKnK+7IcsQ062VwIDAQABAoIBAC6P7cbo5w2TUXXCAsrVc2YQPDKOI+iZVzKxFTcuE3d4cK+l5zEmpcX2wfmQtbg3qRLcAHEIp7Im56JPVfwtNVi1vsh9mzE8P+wJz4HHEdBVOPuGpDXTSvrc7drgsl3f0GPSUrdRLg4WCM3DuAYanesVTfVnenOkQSX/+XTj70t+XrjRZe6dowoJfOOL5MGQKtnCjdd6xFAKA73mitSvnbgPlvIt8LJNCCbCP9GDLzq7Vq4RVO/d47nDbpapEUjtbh1covQPMpTZtAbj1vAeRPSysKUzVu+bTMymhkRqBDuPc0IwSFhjkcYk8zAUty+ao8cXomJCtwBIVxNlo5904gECgYEA03/hD2JKpm1D+gi5aPY8VpiArW3z4EaQZv2f+uPG464wPTijrxcLphCMKpwE5kJg8otrP4iuVaf6+0gPqMUHUa52n29wAIr8xlfiaoeV2noNEqSYFcCThW5w5d1N0shJy9DzsYHkWyuHStWeZ7x/0IbrW2yY5Av8upMjWbfTG3sCgYEA02bM5ZhU+dfANPEe0ukiWULm1/9ZYoFkT77ugdKWEx5GbgpaBIBQgxwnrBIZzT8gm3/YSFISQtG/HW/oim59jNxwXeqST5A/EA/NJgtnRi4UgqBJR/EFToRaV0DHqnNKVVnLF95K9yAwVXO13V+UaXG6umPdC01baxfMCrviu9UCgYEAnfriaLRZ4HCzmvuTSwTK00A8tc7woLD0wglmy2gCsyT0oXZCRdHoAJZRrK43tqsUcXeUl7OHzTGZdsM/9yedLPUtZDBAMBehcqJI3JwEYlpSk39gnrbnOn7hU8H3lJ/JB7Y/oXLN2Q/tkgd4uDIEIwX0najDl2wgzliDyktWJCsCgYEAltRm5n0sS+ISgfNzQZoS5srj90J53N1i277nXvsIFnXoXETIeyOtzg29hHiZriYXNrsdbmQYIVKTYAZjTLmOnHz/MxLU9y18wRH1FerW8WyZN6XzAwBFAANQjaZrjwKZC5J4Y/w3UmDF+4IGRP8X3a/GQYxUvuafjiY5b4MkP00CgYEAjCMmdNNAvzNM7PNUrkDXkUPlohbBKC7JD/pr/INeDyGm+V7kpH87M/VNvNGrR1eTXowkaD7aFWLieeyv5v9yuB+QX0czUj2qQ1PZy0hRepS+gWfQOTqgoURMS/XdkBx4Pg3S1vLRXZTnrddu8N0w7xrnyojkyEA2RmvhxinJxt0=';
    /**
     *请填写支付宝公钥，一行字符串
     */
    const ALIPAY_RSA_PUBLIC_KEY = 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA8E3PeMb72XTwqZrxQbzdHQco8HmUmW+pUgYCFejOUTtp8iApB7RGkeyVbOt1Waoqmy6Ji4QoOurJbZucMzh5CimW4TjyxKUYq7ZpSKMC8tyv3YxKkNn/62z3mS8VzdINxDyE+p6vLOFzINAf1r0/E7PGCiIuuc4eQAx9Ocq4IAHWK2IXu8CrfogMiaVes6UmlCkgoupO3w+RRggjH7ut/6n9CN82dNAa+C/nZ+vBJGoQGbtVA+5ZvBeFzW/nDcNgerAL5TUxf0Ba1dU6huH8oYickCz1Q+H7RYb1cGB8LVod2Ld1arxgp6feN/dEoABehV5Ik2DKV/YzLSLnETLk5QIDAQAB';
    /**
     * 支付宝服务器主动通知商户服务器里指定的页面
     * @var string
     */
    private $callback = "http://tz.hn-ym.cn/index.php/api/tztx/v1/weixin/alipay_notify.html";


    /**
     * 初始化
     */
    public function _initialize(){
		/*继承父类构造方法*/
        parent::_initialize();
        /*自动收货*/
		$this->take();
		/*自动取消订单*/
		$this->takes();
		/*验证用户类型是否为用户*
		$user = Db::name('user')->field('group_ids')->where('id='.$this->user['id'])->find();
        if(!empty($user)) {
			if($user['group_ids'] != 2){
				throw new ApiException('身份类型不匹配');/*用户*
			}
		}else{
			return show(config('code.error'),'账号信息缺失',array());
		}
		*/
	}

    /**
     * 个人中心-获取用户信息
     */
    public function read(){
		/*非常隐私、需要加密处理
        $obj = new Aes();
        return show(config('code.success'),'ok',$obj->encrypt($this->user));
    	*/
    	$field = 'id,nickname,picture,commissiontotal,mabycommissiontotal,phone,year,month,day,address,agentlevel,sex,ranks';
    	$list = Db::name('wine_users')->field($field)->where('id', $this->user['id'])->find();
    	switch ($list['sex']) {
    		case 1:
    			$list['sex_name'] = '男';
    			break;
    		case 2:
    			$list['sex_name'] = '女';
    			break;
    		default:
    			$list['sex_name'] = '保密';
    			break;
    	}
    	/*等级查询*/
    	$ranks = Db::name('wine_users_ranks')->field('username')->where('id', $list['ranks'])->find();
    	$list['member'] = $ranks['username'];
		return show(config('code.success'),'ok',$list);
	}

	/**
	 * 个人中心-修改用户信息
	 */
	public function useredit()
	{
		/*用户id*/
		$id = $this->user['id'];
		$nickname = input('nickname');
		if (empty($nickname)) {
			return show(config('code.error'),'用户名缺失',array());
		}
		$data = array(
			'nickname' => $nickname,
			'picture' => input('picture'),
			'phone' => input('phone'),
			'sex' => input('sex'),
			'address' => input('address')
			);
		$ymd = input('ymd');
		if (!empty($ymd)) {
			$ymd_arr = explode('-', $ymd);
			$data['year'] = $ymd_arr[0];
			$data['month'] = $ymd_arr[1];
			$data['day'] = $ymd_arr[2];
		}
		$result = Db::name('wine_users')->where('id', $id)->update($data);
		return show(config('code.success'),'ok',$result);
	}

	/**
     * 个人中心-添加收货地址
     */
    public function addressadd(){
		/*ID*/
		if(empty(input('receiver')) || empty(input('tel')) || empty(input('province')) || empty(input('city')) || empty(input('area')) || empty(input('street')) || empty(input('lng')) || empty(input('lat')) || empty(input('content'))){
			return show(config('code.error'),'参数缺失',array());
		}
		$nowtime = time();
		$data = array(
			'user_id' 		=> $this->user['id'],
			'receiver' 		=> input('receiver'),/*收货人*/
			'tel' 			=> input('tel'),/*电话*/
			'province' 		=> input('province'),/*省*/
			'city' 			=> input('city'),/*市*/
			'area' 			=> input('area'),/*区*/
			'street' 		=> input('street'),/*街道*/
			'content' 		=> input('content'),/*详情地址*/
			'lng' 			=> input('lng'),/*精度*/
			'lat' 			=> input('lat'),/*纬度*/
			'default' 		=> !empty(input('default'))?input('default'):0,/*默认 默认为0:否|1:是*/
			'addtime' 		=> $nowtime,
			'edittime' 		=> $nowtime,	
		);
		/*如果设置为默认地址，修改其它地址*/
		if($data['default'] == 1){
			$default_list_where['user_id'] = $this->user['id'];
			$default_list_where['default'] = 1;
			$default_list = Db::name('wine_users_address')->field('id')->where($default_list_where)->select();
			foreach($default_list as $k=>$v){
				$default_data['default'] = 0;
				$default_result = Db::name('wine_users_address')->where('id='.$v['id'])->update($default_data);
			}
		}
		/*插入数据*/
		$result = Db::name('wine_users_address')->insert($data);
		$data['id'] = Db::name('wine_users_address')->getLastInsID();
		if($result){
			return show(config('code.success'),'ok',$data);
		}else{
			return show(config('code.error'),'error',array());
		}	
	}
	
    /**
     * 个人中心-修改收货地址
     */
    public function addressedit(){
		/*ID*/
		$id = input('address_id');
		if(empty($id) || empty(input('receiver')) || empty(input('tel')) || empty(input('province')) || empty(input('city')) || empty(input('area')) || empty(input('street'))|| empty(input('content'))){
			return show(config('code.error'),'参数缺失',array());
		}
		$nowtime = time();
		$data = array(
			'receiver' 		=> input('receiver'),/*收货人*/
			'tel' 			=> input('tel'),/*电话*/
			'province' 		=> input('province'),/*省*/
			'city' 			=> input('city'),/*市*/
			'area' 			=> input('area'),/*区*/
			'street' 		=> input('street'),/*街道*/
			'content' 		=> input('content'),/*详情地址*/
			'lng' 			=> input('lng'),/*经度*/
			'lat' 			=> input('lat'),/*维度*/
			'default' 		=> !empty(input('default'))?input('default'):0,/*默认 默认为0:否|1:是*/
			'edittime' 		=> $nowtime,		
		);
		/*如果设置为默认地址，修改其它地址*/
		if($data['default'] == 1){
			$default_list_where['user_id'] = $this->user['id'];
			$default_list_where['default'] = 1;
			$default_list = Db::name('wine_users_address')->field('id')->where($default_list_where)->select();
			foreach($default_list as $k=>$v){
				$default_data['default'] = 0;
				$default_result = Db::name('wine_users_address')->where('id='.$v['id'])->update($default_data);
			}
		}		
		$result = Db::name('wine_users_address')->where('id='.$id)->update($data);
		if($result){
			return show(config('code.success'),'ok',$data);
		}else{
			return show(config('code.error'),'error',array());
		}	
	}
	
    /**
     * 个人中心-删除收货地址
     */
    public function addressdel(){
		/*ID*/
		$id = input('address_id');
		if(empty($id)){
			return show(config('code.error'),'参数缺失',array());
		}
		$result = Db::name('wine_users_address')->where('id='.$id)->delete();
		if($result){
			return show(config('code.success'),'ok',array());
		}else{
			return show(config('code.error'),'error',array());
		}	
	}

	/**
     * 收货地址详情
     */
    public function address_detail(){
		/*用户ID*/
		$id = $this->user['id'];
		/*地址ID*/
		$address_id = input('id');
		if(empty($address_id)){
			return show(config('code.error'),'参数缺失',array());
		}
		/*查询聊天名师*/
		$where_list['user_id'] = $id;
		$where_list['id'] = $address_id;
		/*字段*/
		$field = 'id,user_id,receiver,tel,province,city,area,street,content,lng,lat,default,addtime';
		/*查询*/
		$list = Db::name('wine_users_address')->field($field)->where($where_list)->find();
		/*处理结果集*/
		return show(config('code.success'),'ok',$list);
	}

	/**
     * 默认收货地址
     */
    public function address_id(){
		/*地址ID*/
		$address_id = input('id');
		/*查询*/
		$default_list_where['user_id'] = $this->user['id'];
		$default_list_where['default'] = 1;
		$default_list = Db::name('wine_users_address')->field('id')->where($default_list_where)->select();
		foreach($default_list as $k=>$v){
			$default_data['default'] = 0;
			$default_result = Db::name('wine_users_address')->where('id',$v['id'])->update($default_data);
		}
		/*结果*/
		$data['default'] = 1;
		$result = Db::name('wine_users_address')->where('id',$address_id)->update($data);
		/*处理结果集*/
		return show(config('code.success'),'ok',$result);
	}

	 /**
     * 个人中心-收货地址
     */
    public function address(){
		/*用户ID*/
		$id = $this->user['id'];
		/*选择ID*/
		$aid = input('aid')?input('aid'):1;
		/*获取参数*/	
		$sline = !empty(input('sline'))?input('sline'):0;
		$limit = !empty(input('limit'))?input('limit'):config('default_limit');
		/*查询聊天名师*/
		$where_list['user_id'] = $id;/*用户ID*/
		/*排序*/	
		$order = '`default` desc,id desc';
		/*条数*/
		$limit = $sline.','.$limit;
		/*字段*/
		$field = 'id,user_id,receiver,tel,province,city,area,street,content,lng,lat,default,addtime';
		/*查询聊天名师*/
		$total = Db::name('wine_users_address')->where($where_list)->count();
		$list = Db::name('wine_users_address')->field($field)->where($where_list)->order($order)->limit($limit)->select();
		$lists = array();
		foreach($list as $k => $v){
			$lists[$k] = $v;
			/*格式化时间*/	
			$lists[$k]['addtime'] = date('Y-m-d',$v['addtime']);
			$lists[$k]['select'] = ($v['id']==$aid)?1:0;
		}
		/*处理结果集*/
		$result_data = array(
			'total' => $total,
			'list'	=> $lists
		);
		return show(config('code.success'),'ok',$result_data);
	}

	/**
     * 商品收藏 - 1.添加收藏 2.查询收藏 3.取消收藏
     */
    public function house(){
    	/*商品ID*/
    	$gid = input('gid');
    	$type = input('type');
    	if(empty($gid)||empty($type)){
			return show(config('code.error'),'参数缺失',array());
		}
		/*用户ID*/
		$uid = $this->user['id'];
		/*数据*/
		$data = array(
			'gid' => $gid,
			'uid' => $uid,
			'addtime' => time(),
			'edittime' => time()
			);
		/*条件*/
		$where['uid'] = $uid;
		$where['gid'] = $gid;
		/*查询*/
		switch ($type) {
			case '1':
				$re = Db::name('wine_shop_house')->where($where)->count();
				if ($re) {
					return show(config('code.error'),'已收藏',array());
				} else {
					$list = Db::name('wine_shop_house')->insert($data);
				}
				break;
			case '2':
				$list = Db::name('wine_shop_house')->where($where)->count();
				/*添加浏览记录*/
				$this->like_add($gid);
				break;
			case '3':
				$list = Db::name('wine_shop_house')->where($where)->delete();
				break;
		}
		if ($list) {
			return show(config('code.success'),'ok',$list);
		} else {
			return show(config('code.error'),'error',array());
		}
	}

	/**
     * 收藏商品列表
     */
	public function houselist(){
		
		$where['h.uid'] = $this->user['id'];
		/*获取参数*/
		$sline = !empty(input('sline'))?input('sline'):0;
		$limit = !empty(input('limit'))?input('limit'):config('default_limit');
		/*条数*/
		$limit = $sline.','.$limit;
		$field = 'g.id,name,picture,description,price,price_original,content,g.status';
		$order = 'h.addtime desc';
		$list = Db::name('wine_shop_house')
	    ->alias('h')
	    ->join('wine_shop_goods g', 'h.gid = g.id')
	    ->field($field)
	    ->where($where)
	    ->order($order)
	    ->limit($limit)
	    ->select();
	     if($list){
	        return show(config('code.success'),'ok',$list);
	    }else{
	        return show(config('code.error'),'error',array());
	    }
	}

	/**
	 * 优惠卷领取 -- 登陆
	 */
	public function get_coupon()
	{
		$coupon_id = input('coupon_id');
		if(empty($coupon_id)){
			return show(config('code.error'),'参数缺失',array());
		}
		/*用户ID*/
		$uid = $this->user['id'];
		/*查询结束时间*/
		$field = 'favourtype,day,dayend,type';
		$re_coupon = Db::name('wine_activity_coupon')->field($field)->where('id', $coupon_id)->find();
		if ($re_coupon['favourtype'] == 1) {
			$endtime = strtotime('+'.$re_coupon['day'].' day');
		} else {
			$endtime = $re_coupon['dayend'];
		}
		/*首单卷*/
		if ($re_coupon['type'] == 3) {
			$re_number = Db::name('wine_shop_order_goods')->where('uid',$uid)->count();
			if ($re_number) {
				return show(config('code.error'),'限新用户领取',array());
			}
		}
		$newtime = time();
		/*插入数据*/
		$data = array(
			'uid' => $uid,
			'couponid' => $coupon_id,
			'used' => 0,
			'gettime' => $newtime,
			'addtime' => $newtime,
			'edittime' => $newtime,
			'endtime' => $endtime
			);
		$where['couponid'] = $coupon_id;
		$where['uid'] = $uid;
		$result = Db::name('wine_activity_coupon_data')->field('id')->where($where)->find();
		if ($result) {
			return show(config('code.error'),'已领取',array());
		} else {
			$list = Db::name('wine_activity_coupon_data')->insert($data);
			if ($list) {
				return show(config('code.success'),'ok',$list);
			} else {
				return show(config('code.error'),'error',array());
			}
		}
	}

	/**
	 * 优惠卷 -- 登陆
	 */
	public function get_couponlist()
	{
		/*用户ID*/
		$uid = $this->user['id'];
		/*获取参数*/
		$sline = !empty(input('sline'))?input('sline'):0;
		$limit = !empty(input('limit'))?input('limit'):config('default_limit');
		/*条数*/
		$limit = $sline.','.$limit;
		/*字段*/
		$field = 'id,title,type,minimum,favourable,favourtype,day,daystart,dayend';
		/*条件*/
		$where['status'] = 1;
		$where['centrality'] = 1;
		/*排序*/
		$order = '`order` asc';
		/*查询*/
	    $list = Db::name('wine_activity_coupon')->field($field)->where($where)->order($order)->limit($limit)->select();
	    $lists = array();
	    foreach ($list as $k => $v) {
	    	/*是否领取*/
	    	$where_data['uid'] = $uid;
	    	$where_data['couponid'] = $v['id'];
			$i = Db::name('wine_activity_coupon_data')->where($where_data)->count();
			$data['lq'] = $i?1:0;
	    	$data['id'] = $v['id'];
	    	$data['title'] = $v['title'];
	    	switch ($v['type']) {
	    		case 1:
	    			$data['type'] = 1;
	    			$data['type_name'] = '满减卷';
	    			$data['minimum'] = '满'.$v['minimum'].'可用';
	    			$data['favourable'] = $v['favourable'];
	    			break;
	    		case 2:
	    			$data['type'] = 2;
	    			$data['type_name'] = '通用卷';
	    			$data['minimum'] = '无门槛卷';
	    			$data['favourable'] = $v['favourable'];
	    			break;
	    		case 3:
	    			$data['type'] = 3;
	    			$data['type_name'] = '首单优惠';
	    			$data['minimum'] = '新用户可用';
	    			$data['favourable'] = $v['favourable'];
	    			break;
	    	}
	    	if ($v['favourtype'] == 1) {
	    		$data['favourtime'] = '领取后'.$v['day'].'天内有效';
	    	} else {
	    		$data['favourtime'] ='有效期至'.date('Y-m-d', $v['daystart']);
	    	}
	    	$lists[$k] = $data;
	    }
	    if($list){
	        return show(config('code.success'),'ok',$lists);
	    }else{
	        return show(config('code.error'),'error',array());
	    }
	}

	/**
	 * 个人中心 优惠卷状态
	 */
	public function get_couponstatus()
	{
		$type_id = input('type_id');
		if (empty($type_id)) {
			return show(config('code.error'),'参数缺失',array());
		}
		/*用户ID*/
		$uid = $this->user['id'];
		/*获取参数*/
		$sline = !empty(input('sline'))?input('sline'):0;
		$limit = !empty(input('limit'))?input('limit'):config('default_limit');
		/*条数*/
		$limit = $sline.','.$limit;
		/*字段*/
		$field = 'c.id,c.title,c.type,c.minimum,c.favourable,c.favourtype,c.day,c.daystart,d.endtime,d.used';
		/*条件*/
		$where['d.uid'] = array('eq', $uid);
		switch ($type_id) {
			case '2':
				$where['d.used'] = array('eq', 0);
				$where['d.endtime'] = array('gt', time());
				break;
			case '3':
				$where['d.used'] = array('eq', 1);
				break;
			case '4':
				$where['d.endtime'] = array('lt', time());
				break;
		}
		/*排序*/
		$order = '`order` asc';
		/*查询*/
	    $list = Db::name('wine_activity_coupon_data')
	    ->alias('d')
	    ->join('wine_activity_coupon c', 'd.couponid = c.id')
	    ->field($field)
	    ->where($where)
	    ->order($order)
	    ->limit($limit)
	    ->select();
	    $lists = array();
	    foreach ($list as $k => $v) {
	    	$data['id'] = $v['id'];
	    	$data['title'] = $v['title'];
	    	switch ($v['type']) {
	    		case 1:
	    			$data['type'] = 1;
	    			$data['type_name'] = '满减卷';
	    			$data['minimum'] = '满'.$v['minimum'].'可用';
	    			$data['favourable'] = $v['favourable'];
	    			break;
	    		case 2:
	    			$data['type'] = 2;
	    			$data['type_name'] = '通用卷';
	    			$data['minimum'] = '无门槛卷';
	    			$data['favourable'] = $v['favourable'];
	    			break;
	    		case 3:
	    			$data['type'] = 3;
	    			$data['type_name'] = '首单优惠';
	    			$data['minimum'] = '新用户可用';
	    			$data['favourable'] = $v['favourable'];
	    			break;
	    	}
	    	$data['favourtime'] ='有效期至'.date('Y-m-d', $v['endtime']);
	    	/*状态 2.未使用 3.已使用 4.已过期*/
	    	if ($v['used'] == 0) {
	    		$data['status'] = 2;
	    	}
	    	if ($v['endtime'] < time()) {
	    		$data['status'] = 4;
	    	}
	    	if ($v['used'] == 1) {
	    		$data['status'] = 3;
	    	}
	    	$lists[$k] = $data;
	    }
	    if($list){
	        return show(config('code.success'),'ok',$lists);
	    }else{
	        return show(config('code.error'),'error',array());
	    }
	}

	/**
	 * 购物车 - 添加商品
	 */
	public function member_cartadd()
	{
		/*用户ID*/
		$uid = $this->user['id'];
		$newtime = time();
		/*商品ID*/
		$goodsid = input('goodsid');
		$type = input('type');//类型 1.加 2.减 3.固定数量
		$number = input('number');
		if (empty($goodsid) || empty($number) || empty($type)) {
			return show(config('code.error'),'参数缺失',array());
		}
		/*固定字段*/
		$field = 'total';
		$where_cart['uid'] = $uid;
		$where_cart['goodsid'] = $goodsid;
		$re = Db::name('wine_shop_member_cart')->field($field)->where($where_cart)->find();
		/*数据*/
		$data = array(
			'uid' => $uid,
			'goodsid' => $goodsid,
			'total' => $number,
			'addtime' => $newtime
			);
		/*添加方式  1.加 2.减 3.固定数量*/
		if ($re) {
			switch ($type) {
				case '1':
					$total = $number+$re['total'];
					break;
				case '2':
					$total = (($re['total']-$number)>0)?($re['total']-$number):1;
					break;
				case '3':
					$total = $number;
					break;
			}
			$update['total'] = $total;
			$update['addtime'] = $newtime;
			$list = Db::name('wine_shop_member_cart')->where($where_cart)->update($update);
		} else {
			$list = Db::name('wine_shop_member_cart')->insert($data);
		}
		/*购物商品数量*/
		$goods_number = $this->goods_number();
		/*购物车总金额*/
		$goods_money = $this->goods_money();
		$result = array(
			'number' => $goods_number,
			'money' => $goods_money
			);
		if($list){
	        return show(config('code.success'),'ok',$result);
	    }else{
	        return show(config('code.error'),'error',array());
	    }
	}

	/**
	 * 购物车 选择商品结算金额
	 */
	public function get_money()
	{
		/*用户ID*/
		$uid = $this->user['id'];
		/*商品ID*/
		$goodsid = input('goodsid');
		if (empty($goodsid)) {
			return show(config('code.error'),'参数缺失',array());
		}
		$goodsid = trim($goodsid,',');
		/*固定字段*/
		$field = 'c.total,g.price';
		/*条件*/
		$where['c.uid'] = array('eq', $uid);
		$where['c.status'] = array('eq', 1);
		$where['c.goodsid'] = array('in', $goodsid);
		/*查询*/
		$list = Db::name('wine_shop_member_cart')
		->alias('c')
		->join('wine_shop_goods g', 'c.goodsid = g.id')
		->field($field)
		->where($where)
		->select();
		$lists = 0;
		foreach ($list as  $v) {
			$lists += ($v['total']*$v['price']);
		}
		if($list){
	        return show(config('code.success'),'ok',$lists);
	    }else{
	        return show(config('code.error'),'error',array());
	    }
	}

	/**
	 * 购物车 - 数量与金额
	 */
	public function get_goods()
	{
		/*购物商品数量*/
		$goods_number = $this->goods_number();
		/*购物车总金额*/
		$goods_money = $this->goods_money();
		$result = array(
			'number' => $goods_number,
			'money' => $goods_money
			);
		if($result){
	        return show(config('code.success'),'ok',$result);
	    }else{
	        return show(config('code.error'),'error',array());
	    }
	}

	/**
	 * 购物车 - 商品列表
	 */
	public function get_goodslist()
	{
		/*商铺ID*/
		$shopid = input('shops_id');
		if (empty($shopid)) {
			return show(config('code.error'),'参数缺失',array());
		}
		$shops = Db::name('user')->where('id', $shopid)->field('nickname')->find();
		/*用户ID*/
		$uid = $this->user['id'];
		/*条件*/
		$where['c.uid'] = array('eq', $uid);
		$where['c.status'] = array('eq', 1);
		/*字段*/
		$field = 'c.id,c.total,g.picture,g.name,g.price,g.id as goodsid,g.status';
		/*查询*/
		$list = Db::name('wine_shop_member_cart')
		->alias('c')
		->join('wine_shop_goods g', 'c.goodsid = g.id')
		->field($field)
		->where($where)
		->select();
		/*结果*/
		$result_data = array(
			'shops_name' => $shops['nickname'],
			'list' => $list
			);
		if($list){
	        return show(config('code.success'),'ok',$result_data);
	    }else{
	        return show(config('code.error'),'error',array());
	    }
	}

	/**
	 * 购物车 - 删除商品
	 */
	public function get_goodsdel()
	{
		/*用户ID*/
		$uid = $this->user['id'];
		/*商品ID*/
		$shops_id = input('shops_id');
		if (empty($shops_id)) {
			return show(config('code.error'),'参数缺失',array());
		}
		$shops_id = trim($shops_id,',');
		/*条件*/
		$where['uid'] = array('eq', $uid);
		$where['goodsid'] = array('in', $shops_id);
		/*查询*/
		$list = Db::name('wine_shop_member_cart')->where($where)->delete();
		if($list){
	        return show(config('code.success'),'ok',$list);
	    }else{
	        return show(config('code.error'),'error',array());
	    }
	}

	/**
	 * 提交订单
	 */
	public function ordersubmit()
	{
		$uid = $this->user['id'];/*用户ID*/
		$type = input('type');/*订单类型 1.直接购买 2.购物车购买*/
		$shopid = input('shopid');/*商铺id*/
		$goodsid = input('goodsid');/*商品ID*/
		$contacts = input('contacts');/*联系人*/
		$tel = input('tel');/*tel*/
		$area = input('area');/*所在地区*/
		$address = input('address');/*详细地址*/
		$lng = input('lng');/*经度*/
		$lat = input('lat');/*纬度*/
		$dispatching = input('dispatching');/*配送方式 1.立即送 2.预约送 3.快递送 4.自提*/
		$expect = input('expect');/*预计送达时间*/
		$couponid = input('couponid');/*优惠卷ID*/
		$singleid = input('singleid');/*发票ID*/
		$message = input('message');/*买家留言*/
		$singleinfo = input('singleinfo');/*是否开票 1.是 2.否*/
		$singletype = input('singletype');/*发票类型 1.个人 2.单位*/
		$number = input('number')?input('number'):1;
		if (empty($type) || empty($shopid) || empty($goodsid)) {
			return show(config('code.error'),'参数缺失',array());
		}
		/*商铺信息*/
		$shop_info = Db::name('user')->field('id,nickname')->where('id', $shopid)->find();
		/*计算配送范围*/
		$lists_user=array();
		if (!empty($lng) && !empty($lat)) {
				$field="id,nickname,ROUND( 6378.138 * 2 * ASIN( SQRT( POW( SIN( ( ".$lat." * PI() / 180 - lat * PI() / 180 ) / 2 ),  2  ) + COS(".$lat." * PI() / 180) * COS(lat * PI() / 180) * POW(  SIN(  ( ".$lng." * PI() / 180 - lng * PI() / 180 ) / 2 ),  2 ) ) ) * 1000 ) AS distance_um";
		    $where_list['status'] = array('eq', 1);
		    $where_list['admin'] = array('eq', 0);
		    $where_list['site_ids'] = array('eq', $this->site_id);
		    $list_user = Db::name('user')->field($field)->where($where_list)->order('distance_um asc')->find();
		    /*计算需要的参数*/
		    $give = Db::name('site')->where('id', $this->site_id)->field('give,range')->find();
		        /*门店名称，门店图片，门店距离*/
	        $shopid = $list_user['id'];
	    	$lists_user['id'] = $list_user['id'];
	    	$lists_user['nickname'] = $list_user['nickname'];
	        $lists_user['distance']=$list_user['distance_um']/1000;/*门店距离 单位：千米*/
	        $lists_user['range']=($list_user['distance_um']<$give['range'])?1:2;/*是否配送 1.配送 2.不能立即送和预约送*/
	        $lists_user['arrival_time']=((int)($list_user['distance_um']/1000*$give['give']*60))+time();/*到货时间*/
		}
		/*用户配送信息*/
		$user = array(
			'contacts' => $contacts,
			'tel' => $tel,
			'area' => $area,
			'address' => $address,
			'lng' => $lng,
			'lat' => $lat,
			'dispatching' => $dispatching,
			'expect' => $expect,
			'expect_info' => $lists_user,
			'shop_info' => $shop_info,
			'message' => $message,
			'singleinfo' => $singleinfo,
			'singletype' => $singletype
			);
		/*总金额*/
		$money = 0;
		if ($type == 1) {
			/*直接结算*/
			$where['id'] = $goodsid;
			$field = 'id,name,price,picture';
			$list = Db::name('wine_shop_goods')->where($where)->field($field)->find();
			$list['total'] = $number;
			$money = $list['price']*$number;
		} else {
			/*购物车*/
			$goodsid = trim($goodsid,',');
			$field = 'g.id,g.name,g.price,c.total,g.picture';
			$where['goodsid'] = array('in', $goodsid);/*商品ID*/
			$where['uid'] = array('eq', $uid);
			$list = Db::name('wine_shop_member_cart')
			->alias('c')
			->join('wine_shop_goods g', 'c.goodsid = g.id')
			->field($field)
			->where($where)
			->select();
			foreach ($list as $v) {
				$money += $v['price']*$v['total'];
			}
		}
		/*原价*/
		$money_max = $money;
		/*优惠卷start*/
		$coupon = count($this->do_coupon($money));/*是否可用*/
		$result_conpon = array();
		if (!empty($couponid)) {
			$result_conpon = Db::name('wine_activity_coupon')->field('title,favourable')->where('id', $couponid)->find();
			$money = $money - $result_conpon['favourable'];
		}
		/*发票start*/
		$result_single = array();
		/*固定字段*/
		$field = 'id,title,ratepaying,phone,address,bank,account';
		/*条件*/
		$where_single['uid'] = array('eq', $uid);
		$single = Db::name('wine_users_single')->where($where_single)->count();/*是否可用*/
		if (!empty($singleid)) {
			$where_single['id'] = array('eq', $singleid);
			$result_single = Db::name('wine_users_single')->field($field)->where($where_single)->find();
		} else {
			$result_single = Db::name('wine_users_single')->field($field)->where($where_single)->find();
		}
		/*会员等级等级优惠*/
		$field = 'username,discount,content';
		$where_ranks['id'] = array('eq', $this->user['ranks']);
		$result_ranks = Db::name('wine_users_ranks')->field($field)->where($where_ranks)->find();
		$money = $money * $result_ranks['discount'];
		$money_max = $money_max * $result_ranks['discount'];
		/*页面数据*/
		$result_data = array(
			'money_max' => ($money_max<0.01)?0.01:$money_max,
			'money' => ($money<0.01)?0.01:$money,
			'type' => $type,
			"list" => $list,
			'user' => $user,
			'coupon' => $coupon,
			'conpon_data' => $result_conpon,
			'single' => $single,
			'single_data' => $result_single,
			'ranks' => $result_ranks
			);
		if($list){
	        return show(config('code.success'),'success',$result_data);
	    }else{
	        return show(config('code.error'),'error',array());
	    }
	}

	/**
	 * 优惠卷 - 结算
	 */
	public function order_coupon()
	{
		$money = input('money');
		if (empty($money)) {
			return show(config('code.error'),'参数缺失',array());
		}
		$list = $this->do_coupon($money);
		if($list){
	        return show(config('code.success'),'success',$list);
	    }else{
	        return show(config('code.error'),'error',array());
	    }
	}
	/**
     * 添加发票
     */
    public function singleadd(){
		/*ID*/
		if(empty(input('title')) || empty(input('ratepaying'))){
			return show(config('code.error'),'参数缺失',array());
		}
		$nowtime = time();
		$data = array(
			'uid' 			=> $this->user['id'],
			'title' 		=> input('title'),/*发票抬头*/
			'ratepaying' 	=> input('ratepaying'),/*纳税人识别号*/
			'phone' 		=> input('phone'),/*注册电话*/
			'address' 		=> input('address'),/*注册地址*/
			'bank' 			=> input('bank'),/*开户银行*/
			'account' 		=> input('account'),/*c*/
			'addtime' 		=> $nowtime,
			'edittime' 		=> $nowtime,	
		);
		/*插入数据*/
		$result = Db::name('wine_users_single')->insertGetid($data);
		if($result){
			return show(config('code.success'),'ok',$data);
		}else{
			return show(config('code.error'),'error',array());
		}	
	}

	/**
     * 修改发票
     */
    public function singleedit(){
		if(empty(input('sid')) ||empty(input('title')) || empty(input('ratepaying')) || empty(input('phone')) || empty(input('address')) || empty(input('bank')) || empty(input('account'))){
			return show(config('code.error'),'参数缺失',array());
		}
		$nowtime = time();
		$data = array(
			'title' 		=> input('title'),/*发票抬头*/
			'ratepaying' 	=> input('ratepaying'),/*纳税人识别号*/
			'phone' 		=> input('phone'),/*注册电话*/
			'address' 		=> input('address'),/*注册地址*/
			'bank' 			=> input('bank'),/*开户银行*/
			'account' 		=> input('account'),/*开户银行账号*/
			'edittime' 		=> $nowtime,	
		);
		/*修改数据*/
		$where['uid'] = $this->user['id'];
		$where['id'] = input('sid');
		$result = Db::name('wine_users_single')->where($where)->update($data);
		if($result){
			return show(config('code.success'),'ok',$data);
		}else{
			return show(config('code.error'),'error',array());
		}	
	}

	/**
     * 删除发票
     */
    public function singledel(){
		/*ID*/
		if(empty(input('sid'))){
			return show(config('code.error'),'参数缺失',array());
		}
		/*删除数据*/
		$where['uid'] = $this->user['id'];
		$where['id'] = input('sid');
		$result = Db::name('wine_users_single')->where($where)->delete();
		if($result){
			return show(config('code.success'),'ok',$result);
		}else{
			return show(config('code.error'),'error',array());
		}	
	}

	 /**
     * 发票
     */
    public function single(){
		/*用户ID*/
		$uid = $this->user['id'];
		/*获取参数*/	
		$sline = !empty(input('sline'))?input('sline'):0;
		$limit = !empty(input('limit'))?input('limit'):config('default_limit');
		$where_list['uid'] = $uid;/*用户ID*/
		/*排序*/	
		$order = 'addtime desc';
		/*条数*/
		$limit = $sline.','.$limit;
		/*字段*/
		$field = 'id,uid,title,ratepaying,phone,address,bank,account,addtime';
		/*查询聊天名师*/
		$total = Db::name('wine_users_single')->where($where_list)->count();
		$list = Db::name('wine_users_single')->field($field)->where($where_list)->order($order)->limit($limit)->select();
		$lists = array();
		foreach($list as $k => $v){
			$lists[$k] = $v;
			/*格式化时间*/	
			$lists[$k]['addtime'] = date('Y-m-d',$v['addtime']);
		}
		/*处理结果集*/
		$result_data = array(
			'total' => $total,
			'list'	=> $lists
		);
		return show(config('code.success'),'ok',$result_data);
	}

	/**
	 * 结算订单
	 */
	public function submit()
	{
		/*POST请求方式校验*/
        if(!request()->isPost()){
            return show(config('code.error'),'您没有权限','');
        }
        $type = input('type');/*订单类型 1.直接购买 2.购物车购买*/
		$uid = $this->user['id'];/*用户ID*/
		$shopid = input('shopid');/*商铺id*/
		$goodsid = input('goodsid');/*商品id*/
		$contacts = input('contacts');/*收货人*/
		$tel = input('tel');/*tel*/
		$area = input('area');/*所在地区*/
		$address = input('address');/*详细地址*/
		$dispatching = input('dispatching');/*配送方式 1.立即送 2.预约送 3.快递送 4.自提*/
		$expect = input('expect');/*预计送达时间*/
		$message = input('message');/*买家留言*/
		$singleinfo = input('singleinfo');/*是否开票 1.是 2.否*/
		$singletype = input('singletype');/*发票类型 1.个人 2.单位*/
		$couponid = input('couponid');/*优惠卷ID*/
		$title = input('title');/*发票名称*/
		$ratepaying = input('ratepaying');/*发票税号*/
		$phone = input('phone');/*发票电话*/
		$single_address = input('single_address');/*公司地址*/
		$bank = input('bank');/*发票银行*/
		$number = input('number');/*直接购买时的数量*/
		$account = input('account');/*发票银行卡号*/
		if (empty($type) || empty($shopid) || empty($goodsid) || empty($contacts) || empty($tel) || empty($dispatching) || empty($singleinfo) ) {
			return show(config('code.error'),'参数缺失','');
		}
		/*订单号*/
        $ordersn = ordernostr();
		/*发票信息*/
		$single = array(
			'title' => $title,
			'ratepaying' => $ratepaying,
			'phone' => $phone,
			'single_address' => $single_address,
			'bank' => $bank,
			'account' => $account
			);
		/*订单数据*/
		$data = array(
			'uid' => $uid,
			'shopid' => $shopid,
			'contacts' => $contacts,
			'tel' => $tel,
			'area' => $area,
			'address' => $address,
			'dispatching' => $dispatching,
			'expect' => $expect,
			'message' => $message,
			'singleinfo' => $singleinfo,
			'singletype' => $singletype,
			'single' => json_encode($single),
			'status' => 1,
			'ordersn' => $ordersn,
			'addtime' => time(),
			'overtime' => strtotime('+15 minute')
			);
		/*总金额*/
		$money = 0;
		if ($type == 1) {
			/*直接结算*/
			$where['id'] = $goodsid;
			/*商品信息-库存等*/
			$field = 'id,name,price,picture,total';
			$list = Db::name('wine_shop_goods')->where($where)->field($field)->find();
			$money = $list['price']*$number;
			/*未付款数量*/
			$field = 'sum(o.number) as total';
			$where_num['g.status'] = array('eq', 1);
			$where_num['g.seckill'] = array('eq', 0);
			$where_num['o.goodsid'] = array('eq', $goodsid);
			$unpaid = Db::name('wine_shop_order_goods')
							->alias('g')
							->join('wine_shop_goods_order o', 'g.ordersn = o.ordersn')
							->field($field)
							->where($where_num)
							->find();
			/*库存-（购买数量+未付款数量）*/
			if ($list['total'] < ($number+$unpaid['total'])) {
				return show(config('code.error'),'库存不足',array());
			}
			$data_order = array(
				'uid' => $uid,
				'goodsid' => $goodsid,
				'number' => $number,
				'ordersn' => $ordersn,
				'addtime' => time()
				);
			$lists = Db::name('wine_shop_goods_order')->insertGetid($data_order);
		} else {
			/*购物车*/
			$goodsid = trim($goodsid,',');
			$field = 'g.id,g.name,g.price,c.total,g.picture,g.total as g_total';
			$where['goodsid'] = array('in', $goodsid);/*商品ID*/
			$where['uid'] = array('eq', $uid);
			$list = Db::name('wine_shop_member_cart')
			->alias('c')
			->join('wine_shop_goods g', 'c.goodsid = g.id')
			->field($field)
			->where($where)
			->select();
			// 启动事务
			$field = 'sum(o.number) as total';
			Db::startTrans();
			foreach ($list as $v) {
				/*订单金额*/
				$money += $v['price']*$v['total'];
				/*未付款数量*/
				$where_num['g.status'] = array('eq', 1);
				$where_num['g.seckill'] = array('neq', 0);
				$where_num['o.goodsid'] = array('eq', $v['id']);
				$unpaid = Db::name('wine_shop_order_goods')
								->alias('g')
								->join('wine_shop_goods_order o', 'g.ordersn = o.ordersn')
								->field($field)
								->where($where_num)
								->find();
				/*库存-（购买数量+未付款数量）*/
				if ($v['g_total'] < ($v['total']+$unpaid['total'])) {
					// 回滚事务
				    Db::rollback();
					return show(config('code.error'),$v['name'].'库存不足','');
				}
				/*商品信息*/
				$data_order = array();
				$data_order = array(
					'uid' => $uid,
					'goodsid' => $v['id'],
					'number' => $v['total'],
					'ordersn' => $ordersn,
					'addtime' => time()
				);
				/*添加商品信息到订单商品表*/
				$lists = Db::name('wine_shop_goods_order')->insertGetid($data_order);
			}
			// 提交事务
		    Db::commit(); 
		}
		/*优惠卷start*/
		if (!empty($couponid)) {
			$result_conpon = Db::name('wine_activity_coupon')->field('favourable')->where('id', $couponid)->find();
			$money = $money - $result_conpon['favourable'];
			/*条件*/
			$where_coupon_u['uid'] = $uid;/*用户ID*/
			$where_coupon_u['couponid'] = $couponid;/*优惠卷*/
			/*修改数据*/
			$update = array(
				'used' => 1,
				'ordersn' => $ordersn,
				'usetime' => time()
				);
			Db::name('wine_activity_coupon_data')->where($where_coupon_u)->update($update);
		}
		/*会员等级等级优惠start*/
		$field = 'discount';
		$where_ranks['id'] = array('eq', $this->user['ranks']);
		$result_ranks = Db::name('wine_users_ranks')->field($field)->where($where_ranks)->find();
		$money = $money * $result_ranks['discount'];
		/*防止金额为负数*/
		$money = ($money>0)?$money:0.01;
		/*总金额 - 未改价*/
		$data['money'] = $money;
		/*总金额 - 改价*/
		$data['price'] = $money;
		$lists = Db::name('wine_shop_order_goods')->insertGetId($data);
		if ($lists) {
			if ($type == 2) {
				/*购物车商品删除*/
				$where_c['goodsid'] = array('in', $goodsid);/*商品ID*/
				$where_c['uid'] = array('eq', $uid);
				$where_c['status'] = array('eq', 1);
				Db::name('wine_shop_member_cart')->where($where_c)->delete();
			}
			$result_data = array(
				'orderid' => $lists,
				'ordersn' => $ordersn
				);
			return show(config('code.success'),'ok',$result_data);
		} else {
			return show(config('code.error'),'error', array());
		}
	}

	/*微信统一下单*/
    public function unifyorder(){
        /*需要参数：订单id*/
        /*收集数据*/
        $orderid=input('order_id');
        if(empty($orderid)){
            return show(config('code.error'),'参数错误','');
        }
        if(!request()->isPost()){
            return show(config('code.error'),'您没有权限','');
        }
        $order=Db::name('wine_shop_order_goods')
            ->where('id',$orderid)
            ->where(array(
                'status'=>1,
            ))
            ->find();
        if(empty($order)){
            return show(config('code.error'),'订单不存在','');
        }
        /*商品改价*/
		if (!empty($order['change_type']) && !empty($order['change_money'])) {
			if ($order['change_type'] == 1) {
				$order['money'] = $order['money']+$order['change_money'];
			} else {
				$order['money'] = $order['money']-$order['change_money'];
			}
			/*2位小数*/
			$order['money'] = sprintf("%.2f",$order['money']);
		}
        /*IP*/
        $unknown = 'unknown';
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && $_SERVER['HTTP_X_FORWARDED_FOR'] && strcasecmp($_SERVER['HTTP_X_FORWARDED_FOR'], $unknown)) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else if (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], $unknown)) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        if (false !== strpos($ip, ',')) {
            $ip = reset(explode(',', $ip));
        }
        $data=array(
            'appid'=>'wx592e5b08934adca0',/*应用ID*/
            'mch_id'=>'1509236441',/*商户号*/
            'device_info'=>$order['shopid'],/*平台商户ID*/
            'nonce_str'=>noncestr(32),/*随机字符串*/
            'body'=>'酒莱-商品购买',/*商品描述*/
            'out_trade_no'=>$order['ordersn'],/*商户订单号*/
            'total_fee'=>moeny_change($order['money'])*100,/*总金额*/
            'spbill_create_ip'=>$ip,/*终端IP*/
            'notify_url'=>'http://tz.hn-ym.cn/index.php/api/tztx/v1/weixin/wx_notify.html',/*通知地址*/
            'trade_type'=>'APP'/*交易类型*/
        );
        $data['sign'] = makeSign($data,'taozuitianxia1234567890qwertyuio');/*签名*/
        $xml = arrayToxml($data);
        /*统一下单-接口请求*/
        $resultCurl = curl_post('https://api.mch.weixin.qq.com/pay/unifiedorder',$xml);
        if(empty($resultCurl)){
            $result['code'] = 300;
            $result['msg']	= '查询接口请求异常';
        }else{
            $resultArr = xmlToarray($resultCurl);/*xml转换成数组*/
            if($resultArr['return_code'] != 'SUCCESS'){
                $result['code'] = 400;
                $result['msg']	= $resultArr['return_msg'];
            }else{
                if($resultArr['result_code'] != 'SUCCESS'){
                    $result['code'] = 500;
                    $result['msg']	= $resultArr['err_code_des'];
                }else{
                    $result = array(
                        'appid' => 'wx592e5b08934adca0',
                        'partnerid' => '1509236441',
                        'prepayid' => $resultArr['prepay_id'],
                        'noncestr' => noncestr(32),
                        'timestamp' => time(),
                        'package' => 'Sign=WXPay'
                    );
                    $result['sign'] = makeSign($result,'taozuitianxia1234567890qwertyuio');/*签名*/
                    $result['code'] = 100;
                }
            }

        }
        return show(config('code.success'),'ok',$result);
    }

    /**
     *支付宝 - 统一下单
     * @param string $ordersn   商品订单ID
     * @param string $subject   支付商品的标题
     * @param string $body      支付商品描述
     * @param float $pre_price  商品总支付金额
     * @param int $expire       支付交易时间
     * @return bool|string  返回支付宝签名后订单信息，否则返回false
     */
    public function unifiedorder(){
    	/*需要参数：订单id*/
        /*收集数据*/
        $orderid=input('order_id');
        if(empty($orderid)){
            return show(config('code.error'),'参数错误','');
        }
        if(!request()->isPost()){
            return show(config('code.error'),'您没有权限','');
        }
        $order=Db::name('wine_shop_order_goods')
            ->where('id',$orderid)
            ->where(array(
                'status'=>1,
            ))
            ->find();
        if(empty($order)){
            return show(config('code.error'),'订单不存在','');
        }
        /*商品改价*/
		if (!empty($order['change_type']) && !empty($order['change_money'])) {
			if ($order['change_type'] == 1) {
				$order['money'] = $order['money']+$order['change_money'];
			} else {
				$order['money'] = $order['money']-$order['change_money'];
			}
		}
		require_once ('./vendor\alipay-sdk-PHP-3.3.0\AopSdk.php');
        $aop = new \AopClient();
        $aop->gatewayUrl = "https://openapi.alipay.com/gateway.do";
        $aop->appId = self::APPID;
        $aop->rsaPrivateKey = self::RSA_PRIVATE_KEY;
        $aop->format = "json";
        $aop->charset = "UTF-8";
        $aop->signType = "RSA2";
        $aop->alipayrsaPublicKey = self::ALIPAY_RSA_PUBLIC_KEY;
        //实例化具体API对应的request类,类名称和接口名称对应,当前调用接口名称：alipay.trade.app.pay
        $request = new \AlipayTradeAppPayRequest();
        $body = '酒莱-商品购买';
        $subject = '酒莱';
        $ordersn = $order['ordersn'];
        $expire = 16;
        $pre_price = sprintf("%.2f",$order['money']);
        //SDK已经封装掉了公共参数，这里只需要传入业务参数
        $bizcontent = "{\"body\":\"{$body}\","      //支付商品描述
            . "\"subject\":\"{$subject}\","        //支付商品的标题
            . "\"out_trade_no\":\"{$ordersn}\","   //商户网站唯一订单号
            . "\"timeout_express\":\"{$expire}m\","       //该笔订单允许的最晚付款时间，逾期将关闭交易
            . "\"total_amount\":\"{$pre_price}\"," //订单总金额，单位为元，精确到小数点后两位，取值范围[0.01,100000000]
            . "\"product_code\":\"QUICK_MSECURITY_PAY\""
            . "}";
        $request->setNotifyUrl($this->callback);
        $request->setBizContent($bizcontent);
        //这里和普通的接口调用不同，使用的是sdkExecute
        $response = $aop->sdkExecute($request);
        //htmlspecialchars是为了输出到页面时防止被浏览器将关键参数html转义，实际打印到日志以及http传输不会有这个问题
        return show(config('code.success'),'ok',array('test'=>htmlspecialchars($response),'index'=>$response));//就是orderString 可以直接给客户端请求，无需再做处理。
    }

    /**
     * 订单状态
     */
    public function order_status()
    {
    	/*状态*/
    	$status = input('status');
    	if (!empty($status)) {
    		$where['g.status'] = array('eq', $status);
    		if ($status == 4) {
    			$where['g.assess'] = array('eq', 2);
    		}
    	} else {
    		$where['g.status'] = array('neq', 9);
    	}
    	$uid = $this->user['id'];
    	/*商户名称-订单-交易状态-订单号*/
    	$field = 'u.nickname,g.id,g.status,g.ordersn,g.money,g.overtime,g.assess,g.change_type,g.change_money,g.express_time_stauts,g.dispatching,g.express_type';
    	/*条件*/
    	$where['g.uid'] = array('eq', $uid);
    	/*查询*/
    	$list = Db::name('wine_shop_order_goods')
	    	->alias('g')
	    	->join('user u', 'g.shopid = u.id')
	    	->field($field)
	    	->where($where)
	    	->order('g.addtime desc')
	    	->select();
    	$lists = array();
    	/*固定字段*/
    	$field = 's.id,s.picture,s.name,s.price,o.number';
    	foreach ($list as $k => $v) {
    		$wheres = array();
    		/*条件*/
	    	$wheres['o.ordersn'] = array('eq', $v['ordersn']);
    		$data = Db::name('wine_shop_goods_order')
	    	->alias('o')
	    	->join('wine_shop_goods s', 'o.goodsid = s.id')
	    	->field($field)
	    	->where($wheres)
	    	->order('o.addtime desc')
	    	->select();
	    	/*解析参数*/
    		$lists[$k] = $v;
    		/*状态 1.等待付款 2.等待发货 3.买家已发货 4.交易成功  6.交易取消*/
    		$lists[$k]['statusname'] = orderstatus($v['status']);
    		if ($v['dispatching'] == 4 && $v['status'] == 2) {
    			$lists[$k]['statusname'] = '等待提货';
    		}
    		if ($v['dispatching'] == 4 && $v['status'] == 3) {
    			$lists[$k]['statusname'] = '等待提货';
    		}
    		if ($v['status'] == 1) {
    			$lists[$k]['overtime'] = $v['overtime']-time();
    		}
    		/*改价*/
    		if (!empty($v['change_type']) && !empty($v['change_money'])) {
    			if ($v['change_type'] == 1) {
    				$lists[$k]['money'] = $v['money']+$v['change_money'];
    			} else {
    				$lists[$k]['money'] = $v['money']-$v['change_money'];
    			}
    		}
    		/*利用sprintf格式化字符串*/
    		$lists[$k]['money'] = sprintf("%.2f",$lists[$k]['money']);
    		$lists[$k]['total'] = count($data);
    		$lists[$k]['list'] = $data;
    	}
    	return show(config('code.success'),'ok',$lists);
    }

    /**
     * 删除订单
     */
    public function order_status9()
    {
    	/*订单号*/
    	$ordersn = input('ordersn');
    	if (empty($ordersn)) {
    		return show(config('code.error'),'订单号缺失',array());
    	}
    	/*修改数据*/
    	$update = array(
    		'status' => 9,
    		'edittime' => time()
    		);
    	$result = Db::name('wine_shop_order_goods')->where('ordersn', $ordersn)->update($update);
    	if ($result) {
    		Db::name('wine_shop_goods_order')->where('ordersn', $ordersn)->delete();
    		return show(config('code.success'),'ok',$result);
    	} else {
    		return show(config('code.error'),'error',array());
    	}
    }

     /**
     * 取消订单
     */
    public function order_status6()
    {
    	/*订单号*/
    	$ordersn = input('ordersn');
    	/*取消原因*/
    	$content = input('content');
    	if (empty($ordersn)) {
    		return show(config('code.error'),'订单号缺失',array());
    	}
    	/*修改数据 6.取消订单*/
    	$update = array(
    		'status' => 6,
    		'edittime' => time(),
    		'status_6msg' => $content
    		);
    	$re = Db::name('wine_shop_order_goods')->field('status')->where('ordersn', $ordersn)->find();
    	/*修改数据 5.申请退款*/
    	if ($re['status'] == 2) {
    		$update['status'] = 5;
    	}
    	$result = Db::name('wine_shop_order_goods')->where('ordersn', $ordersn)->update($update);
    	if ($result) {
	    	$updates = array('status' => 2);
	    	Db::name('wine_shop_goods_order')->where('ordersn', $ordersn)->update($updates);
    		return show(config('code.success'),'ok',$result);
    	} else {
    		return show(config('code.error'),'error',array());
    	}
    }

    /**
     * 订单评论
     */
    public function order_comment()
    {
    	/*订单号*/
    	$ordersn = input('ordersn');
    	$newtime = time();
    	$content = input('content');/*评论*/
    	$service = input('service')?input('service'):5;/*服务评价*/
    	$logistics = input('logistics')?input('logistics'):5;/*物流评价*/
    	$product = input('product')?input('product'):5;/*产品评价*/
    	if (empty($ordersn)) {
    		return show(config('code.error'),'订单号缺失',array());
    	}
    	$data = array(
    		'ordersn' => $ordersn, 
    		'uid' => $this->user['id'],
    		'picture' => $this->user['picture'],
    		'nickname' => $this->user['nickname'],
    		'content' => $content,
    		'addtime' => $newtime,
    		'edittime' => $newtime,
			'service' => $service,
			'logistics' => $logistics,
			'product' => $product
    		);
    	$list = Db::name('wine_shop_goods_order')->field('goodsid')->where('ordersn', $ordersn)->select();
    	foreach ($list as  $v) {
    		$data['goodsid'] = $v['goodsid'];
	    	$result = Db::name('wine_shop_comment')->insertGetId($data);
    	}
    	if ($result) {
    		/*订单评价状态修改*/
    		$update['assess'] = 1;
    		Db::name('wine_shop_order_goods')->where('ordersn', $ordersn)->update($update);
	    	return show(config('code.success'),'ok',$data);
    	} else {
    		return show(config('code.error'),'error',array());
    	}
    }

    /**
     * 延长收货时间
     */
    public function order_extend()
    {
    	/*订单号*/
    	$ordersn = input('ordersn');
    	if (empty($ordersn)) {
    		return show(config('code.error'),'订单号缺失',array());
    	}
    	$result = Db::name('wine_shop_order_goods')->where('ordersn', $ordersn)->setInc('express_time', 60*60*24*3);
    	if ($result) {
    		$update['express_time_stauts'] = 2;
    		Db::name('wine_shop_order_goods')->where('ordersn', $ordersn)->update($update);
	    	return show(config('code.success'),'ok',$result);
    	} else {
    		return show(config('code.error'),'error',array());
    	}
    }

    /**
     * 查看物流
     * 配送方式 1.立即送 2.预约送 3.快递送 4.自提
     */
    public function logistics()
    {
    	/*用户id*/
    	$uid = $this->user['id'];
    	/*订单号*/
    	$ordersn = input('ordersn');
    	if (empty($ordersn)) {
    		return show(config('code.error'),'订单号缺失',array());
    	}
    	/*固定字段*/
    	$field = 'dispatching,shop_content,expect,express,express_number,express_type';
    	/*条件*/
    	$where['uid'] = $uid;
    	$where['ordersn'] = $ordersn;
       	/*查询*/
    	$list = Db::name('wine_shop_order_goods')->field($field)->where($where)->find();
		return show(config('code.success'),'success', $list);
    }

    /**
     * 确认收货
     */
    public function order_status4()
    {
    	/*订单号*/
    	$ordersn = input('ordersn');
    	if (empty($ordersn)) {
    		return show(config('code.error'),'订单号缺失',array());
    	}
    	/*修改数据*/
    	$update = array(
    		'status' => 4
   		);
    	/*修改*/
    	$result = Db::name('wine_shop_order_goods')->where('ordersn', $ordersn)->update($update);
    	if ($result) {
    		/*结算佣金*/
    		$this->ordersn_distributions($ordersn);
    		/*会员等级*/
    		$this->user_grades();
	    	return show(config('code.success'),'ok',$result);
    	} else {
    		return show(config('code.error'),'error',array());
    	}
    }

    /**
     * 订单详情
     */
    public function order_detail()
    {
    	$uid = $this->user['id'];
    	/*订单号*/
    	$ordersn = input('ordersn');
    	if (empty($ordersn)) {
    		return show(config('code.error'),'订单号缺失',array());
    	}
    	/*状态*/
		$where['status'] = array('neq', 9);
		$where['ordersn'] = array('eq', $ordersn);
		$where['uid'] = array('eq', $uid);
    	$field = 'id,status,ordersn,money,addtime,area,address,change_type,change_money,express,express_number,contacts,tel,singleinfo,singletype,single,message,assess,dispatching,shopid,shop_content,status_6msg,expect,seckill';
    	/*查询*/
    	$list = Db::name('wine_shop_order_goods')
	    	->field($field)
	    	->where($where)
	    	->find();
    	$lists = array();
    	$goods = array();
    	if ($list) {
    		$lists = $list;
    		/*订单信息*/
    		$lists['statusname'] = orderstatus($list['status']);
    		$lists['address'] = $list['area'].$list['address'];
    		$lists['addtime'] = date('Y-m-d H:i:s', $list['addtime']);
    		/*价格*/
    		if (!empty($list['change_type']) && !empty($list['change_money'])) {
    			if ($list['change_type'] == 1) {
    				$lists['money'] = $lists['money']+$list['change_money'];
    			} else {
    				$lists['money'] = $lists['money']-$list['change_money'];
    			}
    		}
    		/*利用sprintf格式化字符串*/
    		$lists['money'] = sprintf("%.2f",$lists['money']);
    		/*预计送达时间*/
    		$lists['expect'] = $lists['expect']?date('Y-m-d H:i:s', $list['expect']):null;
    		/*dispatching 配送方式 1.立即送 2.预约送 3.快递送 4.自提*/
    		$fields = 'nickname,province,city,area,address';
    		$shopid = Db::name('user')->where('id', $list['shopid'])->field($fields)->find();
    		/*省*/
    		$province = Db::name('areacode')->field('name')->where('id', $shopid['province'])->find();
    		$shopid['province'] = $province['name'];
    		/*市*/
    		$city = Db::name('areacode')->field('name')->where('id', $shopid['city'])->find();
    		$shopid['city'] = $city['name'];
    		/*区*/
    		$area = Db::name('areacode')->field('name')->where('id', $shopid['area'])->find();
    		$shopid['area'] = $area['name'];
    		$lists['shop_info'] = $shopid;

    		/*发票*/
    		if ($list['singleinfo'] == 1) {
    			if ($list['singletype']==1) {
    			} else {
    				$lists['single'] = json_decode($list['single'],true);
    			}
    		}

    		/*商品名称-数量-价格*/
    		$field = 'g.id,g.name,o.number,g.price,g.picture';
    		/*查询*/
    		$where_goods['ordersn'] = array('eq', $list['ordersn']);
    		/*商品信息*/
    		$goods = Db::name('wine_shop_goods_order')
    		->alias('o')
    		->join('wine_shop_goods g', 'o.goodsid = g.id')
    		->where($where_goods)
    		->field($field)
    		->select();
    		/*总金额*/
			foreach ($goods as  $v_goods) {
				$lists['total_money'] = $v_goods['price']*$v_goods['number'];
			}
			$lists['Discount_amount'] = $lists['total_money'] - $lists['money'];
			if ($lists['Discount_amount'] < 0) {
				$lists['Discount_amount'] = 0;
			}
			/*防止金额为负数*/
			$lists['money'] = ($lists['money']>0)?$lists['money']:0.01;
    	}
    	$result = array(
    		'list' => $lists,
    		'goods' => $goods
    		);
		if ($list) {
	    	return show(config('code.success'),'ok',$result);
    	} else {
    		return show(config('code.error'),'error',array());
    	}
    }

    /**
     * 投诉建议
     */
    public function suggestion(){
    	$content = input('content');
    	if (empty($content)) {
    		return show(config('code.error'),'投诉建议不能为空',array());
    	}
		$nowtime = time();
		$data = array(
			'uid' 		    => $this->user['id'],/*用户id*/
			'content' 		=> $content,/*纳税人识别号*/
			'addtime' 		=> $nowtime,/*投诉时间*/	
		);
		/*插入数据*/
		$result = Db::name('wine_information_suggest')->insert($data);
		
		if($result){
			return show(config('code.success'),'ok',$data);
		}else{
			return show(config('code.error'),'error',array());
		}	
	}

	/**
	 * 秒杀-提交订单
	 */
	public function ordersubmit_seckill()
	{
		$uid = $this->user['id'];/*用户ID*/
		$shopid = input('shopid');/*商铺id*/
		$seckill = input('seckill');/*秒杀ID*/
		$contacts = input('contacts');/*联系人*/
		$tel = input('tel');/*tel*/
		$area = input('area');/*所在地区*/
		$address = input('address');/*详细地址*/
		$lng = input('lng');/*经度*/
		$lat = input('lat');/*纬度*/
		$dispatching = input('dispatching');/*配送方式 1.立即送 2.预约送 3.快递送 4.自提*/
		$expect = input('expect');/*预计送达时间*/
		$couponid = input('couponid');/*优惠卷ID*/
		$singleid = input('singleid');/*发票ID*/
		$message = input('message');/*买家留言*/
		$singleinfo = input('singleinfo');/*是否开票 1.是 2.否*/
		$singletype = input('singletype');/*发票类型 1.个人 2.单位*/
		$number = input('number')?input('number'):1;
		if (empty($shopid) || empty($seckill)) {
			return show(config('code.error'),'参数缺失',array());
		}
		/*秒杀购买验证 - 库存 - 限购*/
		$vali = $this->seckill_validate($seckill, $number);
		if (!empty($vali)) {
			return show(config('code.error'),$vali,array());
		}
		/*商铺信息*/
		$shop_info = Db::name('user')->field('id,nickname')->where('id', $shopid)->find();
		/*计算配送范围*/
		$lists_user=array();
		if (!empty($lng) && !empty($lat)) {
				$field="id,nickname,ROUND( 6378.138 * 2 * ASIN( SQRT( POW( SIN( ( ".$lat." * PI() / 180 - lat * PI() / 180 ) / 2 ),  2  ) + COS(".$lat." * PI() / 180) * COS(lat * PI() / 180) * POW(  SIN(  ( ".$lng." * PI() / 180 - lng * PI() / 180 ) / 2 ),  2 ) ) ) * 1000 ) AS distance_um";
		    $where_list['status'] = array('eq', 1);
		    $where_list['admin'] = array('eq', 0);
		    $where_list['site_ids'] = array('eq', $this->site_id);
		    $list_user = Db::name('user')->field($field)->where($where_list)->order('distance_um asc')->find();
		    /*计算需要的参数*/
		    $give = Db::name('site')->where('id', $this->site_id)->field('give,range')->find();
		        /*门店名称，门店图片，门店距离*/
	        $shopid = $list_user['id'];
	    	$lists_user['id'] = $list_user['id'];
	    	$lists_user['nickname'] = $list_user['nickname'];
	        $lists_user['distance']=$list_user['distance_um']/1000;/*门店距离 单位：千米*/
	        $lists_user['range']=($list_user['distance_um']<$give['range'])?1:2;/*是否配送 1.配送 2.不能立即送和预约送*/
	        $lists_user['arrival_time']=$list_user['distance_um']/1000*$give['give'];/*到货时间*/
		}
		/*用户配送信息*/
		$user = array(
			'contacts' => $contacts,
			'tel' => $tel,
			'area' => $area,
			'address' => $address,
			'lng' => $lng,
			'lat' => $lat,
			'dispatching' => $dispatching,
			'expect' => $expect,
			'expect_info' => $lists_user,
			'shop_info' => $shop_info,
			'message' => $message,
			'singleinfo' => $singleinfo,
			'singletype' => $singletype
			);
		/*条件*/
		$where_seckill['s.id'] = array('eq', $seckill);
		/*固定字段*/
		$field_seckill = 's.id,g.name,g.picture,s.price_seckill,s.price,s.open_time,g.id as goodsid';
		$list = Db::name('wine_seckill')
						->alias('s')
						->join('wine_shop_goods g', 's.gid = g.id')
						->field($field_seckill)
						->where($where_seckill)
						->find();
		$list['total'] = $number;
		/*秒杀金额*/
		$money = $list['price_seckill'];
		/*发票start*/
		$result_single = array();
		/*固定字段*/
		$field = 'id,title,ratepaying,phone,address,bank,account';
		/*条件*/
		$where_single['uid'] = array('eq', $uid);
		$single = Db::name('wine_users_single')->where($where_single)->count();/*是否可用*/
		if (!empty($singleid)) {
			$where_single['id'] = array('eq', $singleid);
			$result_single = Db::name('wine_users_single')->field($field)->where($where_single)->find();
		} else {
			$result_single = Db::name('wine_users_single')->field($field)->where($where_single)->find();
		}
		/*页面数据*/
		$result_data = array(
			'money' => ($money<0.01)?0.01:$money,
			"list" => $list,
			'user' => $user,
			'single' => $single,
			'single_data' => $result_single,
			);
		if($list){
	        return show(config('code.success'),'success',$result_data);
	    }else{
	        return show(config('code.error'),'error',array());
	    }
	}

	/**
	 * 秒杀订单结算
	 */
	public function submit_seckill()
	{
		/*POST请求方式校验*/
        if(!request()->isPost()){
            return show(config('code.error'),'您没有权限','');
        }
		$uid = $this->user['id'];/*用户ID*/
		$shopid = input('shopid');/*商铺id*/
		$seckill = input('seckill');/*秒杀id*/
		$contacts = input('contacts');/*收货人*/
		$tel = input('tel');/*tel*/
		$area = input('area');/*所在地区*/
		$address = input('address');/*详细地址*/
		$dispatching = input('dispatching');/*配送方式 1.立即送 2.预约送 3.快递送 4.自提*/
		$expect = input('expect');/*预计送达时间*/
		$message = input('message');/*买家留言*/
		$singleinfo = input('singleinfo');/*是否开票 1.是 2.否*/
		$singletype = input('singletype');/*发票类型 1.个人 2.单位*/
		$title = input('title');/*发票名称*/
		$ratepaying = input('ratepaying');/*发票税号*/
		$phone = input('phone');/*发票电话*/
		$single_address = input('single_address');/*公司地址*/
		$bank = input('bank');/*发票银行*/
		$number = input('number')?input('number'):1;/*直接购买时的数量*/
		$account = input('account');/*发票银行卡号*/
		if (empty($seckill) || empty($shopid) || empty($contacts) || empty($tel) || empty($dispatching) || empty($singleinfo) ) {
			return show(config('code.error'),'参数缺失','');
		}
		/*秒杀购买验证 - 库存 - 限购*/
		$vali = $this->seckill_validate($seckill, $number);
		if (!empty($vali)) {
			return show(config('code.error'),$vali,array());
		}
		/*订单号*/
        $ordersn = ordernostr();
		/*发票信息*/
		$single = array(
			'title' => $title,
			'ratepaying' => $ratepaying,
			'phone' => $phone,
			'single_address' => $single_address,
			'bank' => $bank,
			'account' => $account
			);
		/*订单数据*/
		$data = array(
			'uid' => $uid,
			'shopid' => $shopid,
			'seckill' => $seckill,
			'contacts' => $contacts,
			'tel' => $tel,
			'area' => $area,
			'address' => $address,
			'dispatching' => $dispatching,
			'expect' => $expect,
			'message' => $message,
			'singleinfo' => $singleinfo,
			'singletype' => $singletype,
			'single' => json_encode($single),
			'status' => 1,
			'ordersn' => $ordersn,
			'addtime' => time(),
			'overtime' => strtotime('+15 minute')
			);
		/*总金额*/
		$money = 0;
		/*条件*/
		$where_seckill['id'] = array('eq', $seckill);
		/*固定字段*/
		$field_seckill = 'price_seckill,gid';
		$list = Db::name('wine_seckill')
						->field($field_seckill)
						->where($where_seckill)
						->find();
		/*秒杀金额*/
		$money = $list['price_seckill'];
		$data_order = array(
			'uid' => $uid,
			'goodsid' => $list['gid'],
			'number' => $number,
			'ordersn' => $ordersn,
			'addtime' => time(),
			'seckill' => $seckill
			);
		$lists = Db::name('wine_shop_goods_order')->insertGetid($data_order);
		/*总金额*/
		if ($lists) {
			$data['money'] = $money;
			$data['price'] = $money;
			$orderid = Db::name('wine_shop_order_goods')->insertGetId($data);
			$data['orderid'] = $orderid;
			return show(config('code.success'),'ok',$data);
		} else {
			return show(config('code.error'),'error', array());
		}
	}

	/**
	 * 分销详情页
	 */
	public function dis_detail()
	{
		$uid = $this->user['id'];
		/*固定字段*/
		$field = 'id,nickname,picture';
		/*条件*/
		$where['id'] = array('eq', $uid);
		/*查询*/
		$user_info = Db::name('wine_users')->field($field)->where('id', $uid)->find();
		/*一级分销商数据计算*/
		/*固定字段*/
		$field = 'id';
		/*查询*/
		$where_fx['agentid'] = array('eq', $uid);
		/*一级分销*/
		$user_max = Db::name('wine_users')->field($field)->where($where_fx)->select();
		/*一级分销总金额*/
		$money = 0;
		$moneys = 0;
		$uid_str = '';
		$user_max_one = array();
		foreach ($user_max as $v) {
			$uid_str .= ','.$v['id'];
		}
		if ($uid_str == "") {
			$money = 0;
		} else {
			/*固定字段*/
			$field = 'sum(price) as money';
			$uid_str = trim($uid_str,',');/*去除多余的，*/
			/*条件*/
			$where_go['uid'] = array('in', $uid_str);
			$where_go['status'] = array('eq', 4);
			/*查询*/
			$result = Db::name('wine_shop_order_goods')->field($field)->where($where_go)->find();
			$money = $result['money'];
			/*二级分销商数据计算 - start*/
			$field = 'id';
			/*查询*/
			$where_fx_one['agentid'] = array('in', $uid_str);
			$user_max_one = Db::name('wine_users')->field($field)->where($where_fx_one)->select();
			$uid_str = '';
			foreach ($user_max_one as $vv) {
				$uid_str .= ','.$vv['id'];
			}
			if (!$uid_str == "") {
				/*固定字段*/
				$field = 'sum(price) as money';
				$uid_str = trim($uid_str,',');/*去除多余的，*/
				/*条件*/
				$where_go['uid'] = array('in', $uid_str);
				$where_go['status'] = array('eq', 4);
				/*查询*/
				$result = Db::name('wine_shop_order_goods')->field($field)->where($where_go)->find();
				/*二级分销总金额*/
				$moneys = $result['money'];
			}
		}
		$data = array(
			'id' => $uid,
			'nickname' => $user_info['nickname'],
			'picture' => $user_info['picture'],
			'max' => count($user_max),
			'money' => $money,
			'max_one' => count($user_max_one),
			'moneys' => $moneys
			);
		return show(config('code.success'),'ok',$data);
	}

	/**
	 * 一级分销列表
	 */
	public function dieone_list()
	{
		$uid = $this->user['id'];
		/*获取参数*/	
		$sline = !empty(input('sline'))?input('sline'):0;
		$limit = !empty(input('limit'))?input('limit'):config('default_limit');
		/*条数*/
		$limit = $sline.','.$limit;
		/*固定字段*/
		$field = 'id,phone,picture';
		/*查询*/
		$where_fx['agentid'] = array('eq', $uid);
		/*一级分销*/
		$list = Db::name('wine_users')->field($field)->where($where_fx)->limit($limit)->select();
		$lists =array();
		/*固定字段*/
		$field = 'sum(price) as money';
		/*条件*/
		$where['status'] = array('eq', 4);
		foreach ($list as $k => $v) {
			$v['phone'] = substr_replace($v['phone'], "****", 3,4);/*电话注释*/
			$where['uid'] = array('eq', $v['id']);
			$re = Db::name('wine_shop_order_goods')->field($field)->where($where)->find();
			$v['money'] = $re['money'];
			$lists[$k] = $v;
		}
		return show(config('code.success'),'ok',$lists);
	}

	/**
	 * 二级分销列表
	 */
	public function dieone_list_one()
	{
		$uid = $this->user['id'];
		/*获取参数*/	
		$sline = !empty(input('sline'))?input('sline'):0;
		$limit = !empty(input('limit'))?input('limit'):config('default_limit');
		/*条数*/
		$limit = $sline.','.$limit;
		/*查询*/
		$where_fx['agentid'] = array('eq', $uid);
		/*一级分销*/
		$str_id = Db::name('wine_users')->field('id')->where($where_fx)->select();
		if (empty($str_id)) {
			return show(config('code.success'),'ok',array());
		}
		$str_str = '';
		foreach ($str_id as $kk => $vv) {
			$str_str .= ','.$vv['id'];
		}
		$str_str = trim($str_str, ',');
		/*固定字段*/
		$field = 'id,phone,picture';
		/*条件*/
		$where_fx_one['agentid'] = array('in', $str_str);
		/*二级分销商*/
		$list = Db::name('wine_users')->field($field)->where($where_fx_one)->limit($limit)->select();
		$lists =array();
		/*固定字段*/
		$field = 'sum(price) as money';
		/*条件*/
		$where['status'] = array('eq', 4);
		foreach ($list as $k => $v) {
			$v['phone'] = substr_replace($v['phone'], "****", 3,4);/*电话注释*/
			$where['uid'] = array('eq', $v['id']);
			$re = Db::name('wine_shop_order_goods')->field($field)->where($where)->find();
			$v['money'] = $re['money'];
			$lists[$k] = $v;
		}
		return show(config('code.success'),'ok',$lists);
	}

	/**
	 * 分销商品详情
	 * @return [type] [description]
	 */
	public function fx_detail()
	{
		$user_id = input('user_id');
		if (empty($user_id)) {
			return show(config('code.error'),'error',array());
		}
		/*条件*/
		// $where['o.status'] = array('eq', 4);
		$where['o.uid'] = array('eq', $user_id);
		/*固定字段*/
		$field = 'g.name,g.price';
		$list = Db::name('wine_shop_goods_order')
						->alias('o')
						->join('wine_shop_goods g', 'o.goodsid = g.id', 'left')
						->where($where)
						->field($field)
						->select();
		return show(config('code.success'),'ok',$list);
	}

	/**
	 * 我的钱包
	 * @return [type] [description]
	 */
	public function commision()
	{
		/*会员ID*/
		$uid = $this->user['id'];
		/*会员佣金*/
		$field = 'commissiontotal';
		$where['id'] = array('eq', $uid);
		$list = Db::name('wine_users')->where($where)->field($field)->find();
		return show(config('code.success'),'ok',$list);
	}

	/**
	 * 佣金提现
	 */
	public function get_commision()
	{
		$type = input('type');//类型 1.微信 2.支付宝 3.银行卡
		$money = input('money');
		$name = input('name');
		$idcard = input('idcard');
		$yh_name = input('yh_name');
		if (empty($type) || empty($money) || empty($name) || empty($idcard)) {
			return show(config('code.error'),'参数缺失',array());
		}
		/*插入数据*/
		$data = array(
			'type' => $type,
			'money' => $money,
			'name' => $name,
			'idcard' => $idcard,
			'uid' => $this->user['id'],
			'addtime' => time(),
			'edittime' => time(),
			'status' => 1
			);
		if ($type == 3) {
			$data['yh_name'] = $yh_name;
		}
		/*固定字段*/
		$field = 'commissiontotal';
		/*条件*/
		$where['id'] = array('eq', $this->user['id']);
		/*查询*/
		$money_Arr = Db::name('wine_users')->where($where)->field($field)->find();
		/*剩余金额*/
		$money_min = $money_Arr['commissiontotal']-$money;
		if ($money_min>0) {
			
		} else {
			return show(config('code.error'),'额度不够',array());
		}
		/*修改用户表总金额*/
		$update['commissiontotal'] = $money_min;
		$result = Db::name('wine_users')->where($where)->update($update);
		if ($result) {
			Db::name('wine_commision_record')->insert($data);
			return show(config('code.success'),'ok',$data);
		} else {
			return show(config('code.error'),'error',array());
		}
	}

	/**
    * 推荐二维码
    */
    public function qr_url()
    {
        $uid = $this->user['id'];
        $url = 'http://tz.hn-ym.cn/app/QRcode.html?code=V'.$uid;
        $data = qrcode($url);
        return show(config('code.success'),'success',$data);
    }

	/**
	 * 提现记录
	 */
	public function commision_list()
	{
		/*获取参数*/	
		$sline = !empty(input('sline'))?input('sline'):0;
		$limit = !empty(input('limit'))?input('limit'):config('default_limit');
		/*条数*/
		$limit = $sline.','.$limit;
		/*查询*/
		$list = Db::name('wine_commision_record')->where('uid', $this->user['id'])->order('type')->limit($limit)->select();
		$lists =array();
		foreach ($list as $k => $v) {
			$v['status'] = commision_status($v['status']);
			$lists[$k] = $v;
		}
		return show(config('code.success'),'ok',$lists);
	}

	/**
     * 取消退款申请
     */
    public function order_status2()
    {
   		$uid = $this->user['id'];
    	/*订单号*/
    	$ordersn = input('ordersn');
    	/*取消原因*/
    	$content = '';
    	if (empty($ordersn)) {
    		return show(config('code.error'),'订单号缺失',array());
    	}
    	/*修改数据 2.取消退款，重新发货*/
    	$update = array(
    		'status' => 2,
    		'edittime' => time(),
    		'status_6msg' => $content
    		);
    	/*修改*/
    	$result = Db::name('wine_shop_order_goods')->where('ordersn', $ordersn)->where('uid', $uid)->update($update);
    	if ($result) {
	    	$updates = array('status' => 1);
	    	Db::name('wine_shop_goods_order')->where('ordersn', $ordersn)->update($updates);
    		return show(config('code.success'),'ok',$result);
    	} else {
    		return show(config('code.error'),'error',array());
    	}
    }

    /**
     * 浏览记录
     * @param string $value [description]
     */
    public function like_add($gid)
    {
    	/*商品名称*/
    	$name = Db::name('wine_shop_goods')->field('name')->where('id', $gid)->find();
    	/*用户ID*/
    	$uid = $this->user['id'];
    	$data = array(
    		'uid' => $uid,
    		'name' => $name['name'],
    		);
    	Db::name('wine_users_like')->insert($data);
    }

    /**
     * 猜你喜欢
     * 
     */
    public function like()
    {
    	$uid = $this->user['id'];
    	/*固定字段*/
    	$field = 'id,name,picture,price_original,price';
    	/*查询浏览记录*/
    	$list = Db::name('wine_users_like')->field('name')->where('uid', $uid)->order('id desc')->limit(0,5)->select();
    	/*查询不到浏览记录*/
    	if (empty($list)) {
    		$rerult_arr = Db::name('wine_shop_goods')->field($field)->where('status', 1)->order('rand()')->limit(6)->select();
    		return show(config('code.success'),'ok',$rerult_arr);
    	}
    	$list_str = '';
    	foreach ($list as $k => $v) {
    		$list_str .= $v['name'].',';
    	}
    	/*分词*/
    	require_once './vendor/phpanalysis/phpanalysis.class.php';
		$pa=new \PhpAnalysis();
		$pa->SetSource($list_str);
		$pa->resultType=2;
		$pa->differMax=true;
		$pa->StartAnalysis();
		/*获取分词结果*/
		$arr=$pa->GetFinallyKeywords(0);
		$arr = trim($arr,',');
		$where['name'] = array('like','%'.$arr.'%');
		$where['status'] = array('eq',1);
		$re = Db::name('wine_shop_goods')->field($field)->where($where)->order('rand()')->limit(6)->select();
		$info = array();
		/*查询所有满足微信条件*/
		foreach ($re as $k => $v) {
			$info[][] = $v;
		}
		/*随机2个商品*/
		// $info_s = array_rand($info,2);
		// $result_data = array();
		// foreach ($info_s as $kk => $vv) {
		// 	$result_data[] = $info[$vv];
		// }
		/*结果*/
		return show(config('code.success'),'ok',$info);
    }

    /*会员等级*/
    public function ranks()
    {
    	/*用户信息*/
    	$field = 'id,nickname,picture,ranks';
    	$list = Db::name('wine_users')->field($field)->where('id', $this->user['id'])->find();
    	/*等级*/
    	$ranks = Db::name('wine_users_ranks')->field('username')->where('id', $list['ranks'])->find();
    	$list['member'] = $ranks['username'];
    	/*会员等级信息*/
    	$result = Db::name('wine_users_ranks')->field('id,synopsis,content')->select();
    	$result_data = array(
    		'user' => $list,
    		'list' => $result
    		);
    	/*结果*/
    	return show(config('code.success'),'ok',$result_data);
    }


}