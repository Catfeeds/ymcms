<?php
namespace app\api\controller\tztx\v1;
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
            throw new ApiException('access_user_token缺失',401,2);
        }
		/*解密token*/
        $obj = new Aes();
        $accessUserToken = $obj->decrypt($access_user_token);
        if(empty($accessUserToken)) {
            throw new ApiException('access_user_token有误',401,2);
        }
		/*校验token*/
        if(!preg_match('/||/',$accessUserToken)) {
            throw new ApiException('access_user_token非法',401,2);
        }
		/*查询用户*/
        list($token,$id) = explode("||",$accessUserToken);
		$user_where['token'] = $token;
		$user_where['status'] = array('gt',0);
		$user_field = array('id','phone','nickname','picture','sex','year','month','day','addtime','time_out','ranks');
        // throw new ApiException($token,401);
		$user = Db::name('wine_users')->field($user_field)->where($user_where)->find();
		if(!$user) {
			throw new ApiException('您没有登录',401,2);
        }
        /*判定时间是否过期*/
        if(time() > $user['time_out']) {
            throw new ApiException('登录失效',401,2);
        }
		/*用户信息*/
        $this->user = $user;
    }
    
    /*获取商品数量*/
    public function goods_number()
    {
        /*用户iD*/
        $uid = $this->user['id'];
        /*购物商品数量*/
        $where_number['uid'] = $uid;
        // $where_number['status'] = 1;
        $goods_number = Db::name('wine_shop_member_cart')->where($where_number)->count();
        return $goods_number;
    }

    /**
     * 购物车所有商品总金额
     * @return [type] [description]
     */
    public function goods_money()
    {
        /*用户ID*/
        $uid = $this->user['id'];
        /*固定字段*/
        $field = 'c.total,g.price';
        /*条件*/
        $where['c.uid'] = array('eq', $uid);
        // $where['c.status'] = array('eq', 1);
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
        return $lists;
    }

    /**
     * 优惠卷 - 是否有可用优惠卷
     */
    public function do_coupon($money)
    {
        $uid = $this->user['id'];/*用户ID*/
        /*字段*/
        $field = 'c.id,c.title,c.type,c.minimum,c.favourable,c.favourtype,c.day,c.daystart,d.endtime';
        /*条件*/
        $where['d.uid'] = array('eq', $uid);
        $where['d.used'] = array('eq', 0);
        $where['d.endtime'] = array('gt', time());
        /*排序*/
        $order = '`order` asc';
        /*查询*/
        $list = Db::name('wine_activity_coupon_data')
        ->alias('d')
        ->join('wine_activity_coupon c', 'd.couponid = c.id')
        ->field($field)
        ->where($where)
        ->order($order)
        ->select();
        $lists = array();
        foreach ($list as $k => $v) {
            $data['id'] = $v['id'];
            $data['title'] = $v['title'];
            $data['favourtime'] ='有效期至'.date('Y-m-d', $v['endtime']);
            switch ($v['type']) {
                case 1:
                    $data['type'] = 1;
                    $data['type_name'] = '满减卷';
                    $data['minimum'] = '满'.$v['minimum'].'可用';
                    $data['favourable'] = $v['favourable'];
                    $lists[$k] = $data;
                    if ($money<$v['minimum']) {
                        unset($lists[$k]);
                    }
                    break;
                case 2:
                    $data['type'] = 2;
                    $data['type_name'] = '通用卷';
                    $data['minimum'] = '无门槛卷';
                    $data['favourable'] = $v['favourable'];
                    $lists[$k] = $data;
                    break;
                case 3:
                    $data['type'] = 3;
                    $data['type_name'] = '首单优惠';
                    $data['minimum'] = '新用户可用';
                    $data['favourable'] = $v['favourable'];
                    $lists[$k] = $data;
                    break;
            }
        }
        return $lists;
    }

    /*秒杀购买验证*/
    public function seckill_validate($seckill, $number)
    {
        $uid = $this->user['id'];/*用户ID*/
        /*条件*/
        $where_seckill['id'] = array('eq', $seckill);
        /*固定字段*/
        $field_seckill = 'open_time,max_number,single,total';
        $list = Db::name('wine_seckill')
                        ->field($field_seckill)
                        ->where($where_seckill)
                        ->find();
        if (($list['open_time']) != date('H',time())) {
            return '未到开放时间';
        }
        /*未付款数量*/
        $field = 'sum(o.number) as total';
        $where_num['g.status'] = array('eq', 1);
        $where_num['g.seckill'] = array('eq', $seckill);
        $unpaid = Db::name('wine_shop_order_goods')
                        ->alias('g')
                        ->join('wine_shop_goods_order o', 'g.ordersn = o.ordersn')
                        ->field($field)
                        ->where($where_num)
                        ->find();
        /*库存-（购买数量+未付款数量）*/
        if ($list['total'] < ($number+$unpaid['total'])) {
            return '库存不足';
        }
        /*限购数量*/
        if ($number > $list['max_number'] && $list['max_number'] != 0) {
            return '购买数量超出';
        }
        /*限购单数*/
        $where['seckill'] = array('eq', $seckill);
        $where['uid'] = array('eq', $uid);
        $lists = Db::name('wine_shop_order_goods')->where($where)->count();
        if ($lists > $list['single'] && $list['single'] != 0) {
            return '购买单数超出';
        }
        return null;
    }

    /**
     * 自动确认收货
     */
    public function take()
    {
        /*查询设定收货是时间*/
        // $result = Db::name('site')->where('id', $this->site_id)->field('take')->find();
        /*过期时间*/
        $newtime = time();
        /*条件*/
        $where['express_time'] = array('lt', $newtime);
        $where['status'] = array('eq', 3);
        /*查询*/
        $list = Db::name('wine_shop_order_goods')->field('ordersn,uid')->where($where)->select();
        foreach ($list as $k => $v) {
            /*结算佣金*/
            $this->ordersn_distributions($v['ordersn']);
            /*会员等级*/
            $this->user_grades($v['uid']);
        }
        /*修改数据*/
        $update = array(
            'status' => 4,
            'edittime' => time()
            );
        Db::name('wine_shop_order_goods')->where($where)->update($update);
    }

    /**
     * 自动取消未付款
     */
    public function takes()
    {
        /*过期时间*/
        $newtime = time()+5;
        /*条件*/
        $where['overtime'] = array('lt', $newtime);
        $where['status'] = array('eq', 1);
        /*修改数据*/
        $update = array(
            'status' => 6,
            'edittime' => time()
            );
        Db::name('wine_shop_order_goods')->where($where)->update($update);
    }


    /**
     * 判定access_user_token是否有效
     * @return  boolen
     */
    public function is_Login() {
        /*用户信息*/
        throw new ApiException('token有效',200,1);
    }












}