<?php
namespace app\api\controller;
use think\Controller;
use app\common\lib\exception\ApiException;
use app\common\lib\Aes;
use app\common\lib\IAuth;
use app\common\lib\Time;
use think\Cache;
use think\Request;
use think\Db;

class Common extends Controller {
	
	/*公共变量*/
    /**
     * headers信息
     * @var string
     */
    public $site_id;
	public $headers;	
	public $system_image;
	
	/*构造方法*/
	public function _initialize(){
		$this->site_id = 5;
		/*Aes加密*/
		//$this->testaes();
		/*签名校验*/
		$this->checkRquestAuth();
		/*站点ID*/
		$this->site_id = 5;
		/*系统图片路径*/
		$this->system_image = 'http://www.'.config('system_domain').config('view_replace_str.__SYSTEM__').'/image';
	}
	
	/**
	 * 校验APP请求的数据是否合法
	 */
	public function checkRquestAuth(){
		/*获取header头数据*/
		$headers = request()->header();
		/*基础参数校验*/
        if(empty($headers['version'])) {
            throw new ApiException('版本号不合法',400);
        }
        if(!in_array($headers['apptype'],config('app.apptypes'))) {
            throw new ApiException('apptype不合法',400);
        }
        if(empty($headers['did'])) {
            throw new ApiException('did设备号不合法',400);
        }
        if(empty($headers['os'])) {
            throw new ApiException('设备的操作系统不合法',400);
        }
        if(empty($headers['model'])) {
            throw new ApiException('APP的机型不合法',400);
        }
		/*把headers信息发布至公共变量*/
		$this->headers = $headers;							
        /*校验sign*/
		$sign = input('sign');
		if(empty($sign)) {
            throw new ApiException('sign不存在',400);
        }else{
			$time = input('time');
			if(empty($time)) {
				throw new ApiException('时间戳不存在',400);
			}else{
				$request = Request::instance();	
				$controller = strtolower($request->controller());
				$action = $request->action();
				IAuth::checkSignPass($sign,$time,$controller,$action);
			}
			
			/*
			if(!IAuth::checkSignPass($sign)) {
				throw new ApiException('授权码sign失败',401);
			}else{
				/*sign写入缓存*
				Cache::set($sign,1,config('app.app_sign_cache_time'));
			}
			*/
		}
	}
	
	public function testaes(){
		$Aes = new Aes();
		/*Aes加密
		$str = '1651984sdfdsf';
		echo $Aes->encrypt($str);
		exit;
		*/
		
		/*Aes解密
		$str = 'lSJtnhVl0narUiK9dw3j0A==';	
		echo $Aes->decrypt($str);
		exit;
		*/
		
		/*sign加密*/
		$data = array(
			'did' => 456,
			'version' => 1,
			'time' => Time::get13TimeStamp()
		);
		dump($data);
		echo IAuth::setSign($data);exit;
		
		
		/*sign解密*/
		$str = 'MCr0e9htAzIOhPlEjdmryMYyaOtaT6VHcn2svD8JpFvD6vAma';
		echo $Aes->decrypt($str);exit;
		
		
	}

	/*二维数组排序*/
    function my_sort($arrays,$sort_key,$sort_order=SORT_ASC,$sort_type=SORT_NUMERIC ){  
        if(is_array($arrays)){  
            foreach ($arrays as $array){  
                if(!empty($array[$sort_key])){  
                    $key_arrays[$array[$sort_key]] = $array[$sort_key];
                }
            }
            asort($key_arrays);
            $list = array();
            foreach ($key_arrays as  $v) {
            	foreach ($arrays as  $vv) {
            		if ($v == $vv[$sort_key]) {
            			$list[] = $vv;
            		}
            	}
            }
        }else{  
            return false;  
        } 
        return $list;  
    }


    /**
	 * 结算佣金
	 * @return [type]          [description]
	 */
	public function ordersn_distributions($ordersn)
	{
		$field = 'commission1,commission2,status';
		/*分销信息*/
		$info = Db::name('wine_commission')->field($field)->find();
		if ($info['status'] == 0) {
			return 0;
		}
		/*固定字段*/
		$field = 'o.id,g.price,o.uid,g.fx,g.commission1,g.commission2';
		/*条件*/
		$where['o.ordersn'] = array('eq', $ordersn);
		$list = Db::name('wine_shop_goods_order')
					->alias('o')
					->join('wine_shop_goods g', 'o.goodsid = g.id')
					->field($field)
					->where($where)
					->select();
		if (!$list) {
			return 0;/*为空则结束*/
		}
		$money = 0;
		$moneys = 0;
		/*分销层级*/
		$cii = Db::name('wine_users')->where('id', $list[0]['uid'])->field('agentid')->find();
		if (empty($cii['agentid'])) {
			return 0;/*没有分销-直接结束*/
		}
		/*二级分销*/
		$re = 0;
		$cii_one = Db::name('wine_users')->where('id', $cii['agentid'])->field('agentid')->find();
		if (empty($cii['agentid'])) {
			$re = 1;/*没有分销-直接结束*/
		}
		foreach ($list as $v) {
			/*条件*/
			$where_order['id'] = array('eq', $v['id']);
			$where_order['ordersn'] = array('eq', $ordersn);
			/*修改数据*/
			if ($v['fx'] == 1) {
				/*独立分销*/
				$update['commission1'] = $v['commission1']*$v['price']/100;/*一级佣金*/
			} else {
				/*通用分销*/
				$update['commission1'] = $info['commission1']*$v['price']/100;/*一级佣金*/
			}
			if (!empty($re)) {
				if ($v['fx'] == 1) {
					/*独立分销*/
					$update['commission2'] = $v['commission2']*$v['price']/100;/*二级佣金*/
				} else {
					/*通用分销*/
					$update['commission2'] = $info['commission2']*$v['price']/100;/*二级佣金*/
				}
				$moneys += $update['commission2'];
			}
			$update['status_fx'] = 2;/*享有分销*/
			/*执行修改*/
			$result = Db::name('wine_shop_goods_order')->where($where_order)->update($update);
			/*累计金额*/
			$money += $update['commission1'];
		}
		/*一级分销用户信息-增加佣金*/
		Db::name('wine_users')->where('id', $cii['agentid'])->setInc('mabycommissiontotal',$money);/*佣金总额*/
		Db::name('wine_users')->where('id', $cii['agentid'])->setInc('commissiontotal',$money);/*可提现佣金额度*/
		/*二级分销用户信息-增加佣金*/
		if ($re) {
			Db::name('wine_users')->where('id', $cii_one['agentid'])->setInc('mabycommissiontotal',$moneys);/*佣金总额*/
			Db::name('wine_users')->where('id', $cii_one['agentid'])->setInc('commissiontotal',$moneys);/*可提现佣金额度*/
		}
		return 1;
	}

	/**
	 * 会员等级 - 分销商
	 */
	public function user_grades($uid=null)
	{
		/*修改数据*/
		$update['edittime'] = time();
		/*会员ID*/
		$uid = $uid?$uid:$this->user['id'];
		/*固定字段*/
		$field = 'agentlevel,ranks';
		/*条件*/
		$where['id'] = array('eq', $uid);
		/*查询用户信息*/
		$list = Db::name('wine_users')->field($field)->where($where)->find();
		/*条件*/
		$where_one['uid'] = $uid;
		$where_one['status'] = 4;
		$field = 'sum(price) as price';
		/*消费总价*/
		$price = Db::name('wine_shop_order_goods')->field($field)->where($where_one)->find();
		$price['price'] = $price['price'] ? $price['price'] : 0;
		/*条件*/
		$where_r['status'] = array('eq', 1);
		$field = 'id,quota';
		/*会员等级*/
		$number = 0;
		$lists = Db::name('wine_users_ranks')->field($field)->where($where_r)->select();
		foreach ($lists as $v) {
			if ($price['price'] > $v['quota']) {
				$number = $v['id'];
			}
		}
		if ($list['ranks'] < $number) {
			/*等级*/
			$update['ranks'] = $number;
		}
		/*分销商*/
		if ($list['agentlevel'] == 2) {
			$commission = Db::name('wine_commission')->field('ordermoney')->where('id', 1)->find();
			if ($price['price'] > $commission['ordermoney']) {
				$update['agentlevel'] = 1;
			}
		}
		Db::name('wine_users')->where('id', $uid)->update($update);
	}











}
