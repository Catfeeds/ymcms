<?php
namespace app\web\controller\tztx;
use app\web\controller\Common;
use think\Request;
use think\Db;
use think\Session;
/*订单管理*/
class Order extends Common
{
    /**
     *请填写开发者私钥去头去尾去回车，一行字符串
     */
    const RSA_PRIVATE_KEY = 'MIIEpQIBAAKCAQEArqdUuvC7aAFkeFCJGLx1JoAIev50JIf3vJrXVHGfBr0SZ3stWUl6jvPtBAZRtpWWMNYVwj5AoJDJvyoVvIfCMlS9ZTjJXD2sMdecxUD9h5rcnJhgiMivkA6pm+BPr58ncBkuFwgMpgm2caYQFddEiaiwcy+WbxoXl+200YtQZuNTUGkD+50SUOvnbkBxhS+DddtxLamQxVpHQ0V45DsBse7rDZywUA8vj1EBKuHCeqDBSb16vm1sv7FPfT09T6HOwmLGRS0TrqMeJmT7xIfAWT1TClSmaTCv9lvtGhpN9YmQE9KiHj/hcRctQQFjLigz5sPDNGdNKnK+7IcsQ062VwIDAQABAoIBAC6P7cbo5w2TUXXCAsrVc2YQPDKOI+iZVzKxFTcuE3d4cK+l5zEmpcX2wfmQtbg3qRLcAHEIp7Im56JPVfwtNVi1vsh9mzE8P+wJz4HHEdBVOPuGpDXTSvrc7drgsl3f0GPSUrdRLg4WCM3DuAYanesVTfVnenOkQSX/+XTj70t+XrjRZe6dowoJfOOL5MGQKtnCjdd6xFAKA73mitSvnbgPlvIt8LJNCCbCP9GDLzq7Vq4RVO/d47nDbpapEUjtbh1covQPMpTZtAbj1vAeRPSysKUzVu+bTMymhkRqBDuPc0IwSFhjkcYk8zAUty+ao8cXomJCtwBIVxNlo5904gECgYEA03/hD2JKpm1D+gi5aPY8VpiArW3z4EaQZv2f+uPG464wPTijrxcLphCMKpwE5kJg8otrP4iuVaf6+0gPqMUHUa52n29wAIr8xlfiaoeV2noNEqSYFcCThW5w5d1N0shJy9DzsYHkWyuHStWeZ7x/0IbrW2yY5Av8upMjWbfTG3sCgYEA02bM5ZhU+dfANPEe0ukiWULm1/9ZYoFkT77ugdKWEx5GbgpaBIBQgxwnrBIZzT8gm3/YSFISQtG/HW/oim59jNxwXeqST5A/EA/NJgtnRi4UgqBJR/EFToRaV0DHqnNKVVnLF95K9yAwVXO13V+UaXG6umPdC01baxfMCrviu9UCgYEAnfriaLRZ4HCzmvuTSwTK00A8tc7woLD0wglmy2gCsyT0oXZCRdHoAJZRrK43tqsUcXeUl7OHzTGZdsM/9yedLPUtZDBAMBehcqJI3JwEYlpSk39gnrbnOn7hU8H3lJ/JB7Y/oXLN2Q/tkgd4uDIEIwX0najDl2wgzliDyktWJCsCgYEAltRm5n0sS+ISgfNzQZoS5srj90J53N1i277nXvsIFnXoXETIeyOtzg29hHiZriYXNrsdbmQYIVKTYAZjTLmOnHz/MxLU9y18wRH1FerW8WyZN6XzAwBFAANQjaZrjwKZC5J4Y/w3UmDF+4IGRP8X3a/GQYxUvuafjiY5b4MkP00CgYEAjCMmdNNAvzNM7PNUrkDXkUPlohbBKC7JD/pr/INeDyGm+V7kpH87M/VNvNGrR1eTXowkaD7aFWLieeyv5v9yuB+QX0czUj2qQ1PZy0hRepS+gWfQOTqgoURMS/XdkBx4Pg3S1vLRXZTnrddu8N0w7xrnyojkyEA2RmvhxinJxt0=';
    /**
     *请填写支付宝公钥，一行字符串
     */
    const ALIPAY_RSA_PUBLIC_KEY = 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA8E3PeMb72XTwqZrxQbzdHQco8HmUmW+pUgYCFejOUTtp8iApB7RGkeyVbOt1Waoqmy6Ji4QoOurJbZucMzh5CimW4TjyxKUYq7ZpSKMC8tyv3YxKkNn/62z3mS8VzdINxDyE+p6vLOFzINAf1r0/E7PGCiIuuc4eQAx9Ocq4IAHWK2IXu8CrfogMiaVes6UmlCkgoupO3w+RRggjH7ut/6n9CN82dNAa+C/nZ+vBJGoQGbtVA+5ZvBeFzW/nDcNgerAL5TUxf0Ba1dU6huH8oYickCz1Q+H7RYb1cGB8LVod2Ld1arxgp6feN/dEoABehV5Ik2DKV/YzLSLnETLk5QIDAQAB';
	/*构造方法*/
    public function _initialize()
    {
        /*重载父类构造方法*/
        parent::_initialize();
        /*自动收货*/
        $this->take();
        /*自动取消订单*/
        $this->takes();
    }

    /*订单列表*/
    public function order()
    {
		/*获取参数*/
		$limit = input('limit');
		$search = input('search');
		$status = input('status');
		$time = input('time');
		$dispatching = input('dispatching');
		$this->assign('limit', $limit);
		$this->assign('search', $search);
		$this->assign('status', $status);
		$this->assign('time', $time);
		$this->assign('dispatching', $dispatching);
		$pagelimit = !empty($limit) ? $limit : config('paginate.list_rows'); /*每页条数*/
		if (!empty($search)) {
			$where['concat(u.nickname,g.ordersn)'] = array('like', '%' . $search . '%'); /*搜索*/
		}
		/*分页参数*/
		$paginate = array('query' => array( /*url额外参数*/
			'limit' => $limit,/*每页条数*/
			'search' => $search,/*搜索*/
			'status' => $status,/*状态*/
			'time' => $time,/*时间*/
			'dispatching' => $dispatching/*配送类型*/
		));
        /*获取session*/
        $admin_login = Session::get('admin_login');
        if ($admin_login['admin'] != 1) {
            $where['g.shopid'] = array('eq', $admin_login['user_id']);
        }
		/*状态*/
		$where['g.status'] = array('neq', 9);
    	if (!empty($status)) {
    		$where['g.status'] = array('eq', $status);
    	}
    	/*配送*/
    	if (!empty($dispatching)) {
    		$where['g.dispatching'] = array('eq', $dispatching);
    	}
    	/*时间查询*/
    	if (empty($time)) {
    		$start = strtotime('2010-10-1');
    		$end = strtotime('2030-10-1');
    	} else {
    		$time_arr = explode('~', $time);
    		$start = $time_arr[0];
    		$end = $time_arr[1];
    	}
        /*判断查询还是导出*/
        if (input('excel') == 1) {
                $field = 'g.id,g.ordersn,g.shopid,g.addtime,u.nickname,g.type,g.status,g.price,g.money,g.dispatching,g.contacts,g.tel,g.address,g.message,g.singleinfo,g.singletype,g.express_time,g.express,g.express_number,g.shop_content,g.area';
                $list = Db::name('wine_shop_order_goods')
                ->alias('g')
                ->join('wine_users u', 'g.uid = u.id')
                ->field($field)
                ->where($where)
                ->whereTime('g.addtime', 'between', [$start, $end])
                ->order('addtime desc')
                ->select();
            } else {
                /*字段*/
        $field = 'g.id,g.status_6msg,g.status,g.ordersn,g.money,g.addtime,g.area,g.address,u.nickname,u.picture,g.change_type,g.change_money,g.dispatching,g.expect,g.express,g.express_number,g.price';
            	/*查询*/
            	$list = Db::name('wine_shop_order_goods')
            		->alias('g')
            		->join('wine_users u', 'g.uid = u.id')
        	    	->field($field)
                    ->where($where)
                    ->whereTime('g.addtime', 'between', [$start, $end])
                    ->order('addtime desc')
                    ->paginate($pagelimit, false, $paginate);
            }
    	$lists = array();
    	foreach ($list as $k => $v) {
    		$lists[$k] = $v;
            /*配送类型*/
            $lists[$k]['dispatching'] = $this->dispatching($v['dispatching']);
            /*收货地址*/
    		$lists[$k]['address'] = $v['area'].$v['address'];
    		$lists[$k]['addtime'] = date('Y-m-d H:i:s', $v['addtime']);
            /*导出*/
            if (input('excel') == 1) {
                /*发货时间*/
                $lists[$k]['express_time'] = $v['express_time']?date('Y-m-d H:i:s', $v['express_time']):null;
                /*订单价格*/
                $lists[$k]['price'] = moeny_change($lists[$k]['price']);
                /*订单状态*/
                $lists[$k]['status'] = order_status($v['status']);
                /*店铺信息*/
                $shop_info = Db::name('user')->where('id', $v['shopid'])->field('nickname')->find();
                $lists[$k]['shopid'] = $shop_info['nickname'];
                /*发票*/
                if ($v['singleinfo'] == 1) {
                    if ($v['singletype']==1) {
                        $lists[$k]['singletype'] = '个人类型';
                    } else {
                        $lists[$k]['singletype'] = '单位类型';
                    }
                }
                /*付款方式*/
                $lists[$k]['type'] = ($v['type'] == 1)?'微信付款':'支付宝付款';
                /*查询*/
                $where_goods['ordersn'] = array('eq', $v['ordersn']);
                /*商品信息*/
                $goods = Db::name('wine_shop_goods_order')
                ->alias('o')
                ->join('wine_shop_goods g', 'o.goodsid = g.id')
                ->where($where_goods)
                ->field('g.name')
                ->select();
                /*商品名称 - 占位置*/
                $lists[$k]['money'] = '';
                foreach ($goods as $kk => $vv) {
                    $lists[$k]['money'] .= ($kk+1).'.'.$vv['name'].'  ';
                }
                /*删除多余的数据*/
                unset($lists[$k]['singleinfo']);
                unset($lists[$k]['area']);
            } else {
                /*配送类型*/
                $lists[$k]['dispatchings'] = $this->dispatching($v['dispatching']);
                $lists[$k]['expect'] = $v['expect']?$v['expect']:null;
                /*订单状态*/
                $lists[$k]['statusname'] = order_status($v['status']);
                /*商品价格*/
                $lists[$k]['money'] = moeny_change($lists[$k]['money']);/*下单价格*/
                $lists[$k]['price'] = moeny_change($lists[$k]['price']);/*结算价格*/
                /*配送时间*/
                if ($v['dispatching'] < 3) {
                    $lists[$k]['expect'] = date('Y-m-d H:i:s', $v['expect']);
                } else {
                    $lists[$k]['expect'] = "";
                }
            }
    	}
        /*导出*/
        if (input('excel') == 1) {
            $excelHead = array('ID','订单编号','店铺名称','下单时间','会员名称','支付方式','订单状态','结算金额','商品信息','配送类型','收货人','收货人电话','收货地址','买家备注','发票类型','发货时间','快递公司','快递单号','商家备注');
            array_unshift($lists,$excelHead);
            /*写数据文件*/
            $this->create_xls($lists,'订单信息'.date('Y-m-d',time()));
        }
		$page = $list->render(); /*获取分页显示*/
		$this->assign('list', $lists);
		$this->assign('page', $page);
		/*分配变量*/
		$this->assign('title', '产品列表'); /*页面标题*/
		$this->assign('keywords', '产品列表'); /*页面关键词*/
		$this->assign('description', '产品列表'); /*页面描述*/
		/*调用模板*/
		return $this->fetch(TEMP_FETCH);
    }

    /**
     * 订单详情
     */
    public function orderdetail()
    {
    	$id = input('id');
    	if (empty($id)) {
    		echo '<script>$(document).ready(function(){alertBox("订单id不能为空..","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
    		die();
    	}
    	/*状态*/
		$where['g.status'] = array('neq', 9);
		$where['g.id'] = array('eq', $id);
    	$field = 'g.id,g.status,g.ordersn,g.money,g.addtime,g.area,g.address,u.nickname,g.type,g.change_type,g.change_money,g.express,g.express_number,g.contacts,g.tel,g.status_leng,g.singleinfo,g.singletype,g.single,g.express_time,g.message,g.shop_content';
    	/*查询*/
    	$list = Db::name('wine_shop_order_goods')
    		->alias('g')
    		->join('wine_users u', 'g.uid = u.id')
	    	->field($field)
	    	->where($where)
	    	->find();
    	$lists = array();
    	$goods = array();
    	if ($list) {
    		$lists = $list;
    		/*订单信息*/
    		$lists['status'] = order_status($list['status']);
    		$lists['type'] = ($list['type'] == 1)?'微信付款':'支付宝付款';
    		$lists['address'] = $list['area'].$list['address'];
    		$lists['addtime'] = date('Y-m-d H:i:s', $list['addtime']);
    		$lists['express_time'] = $list['express_time']?date('Y-m-d H:i:s', $list['express_time']):null;
    		$lists['moneys'] = $lists['money'];
    		if (!empty($list['change_type']) && !empty($list['change_money'])) {
    			if ($list['change_type'] == 1) {
    				$lists['change_type'] = '加价+';
    				$lists['moneys'] = moeny_change($lists['money']+$list['change_money']);
    			} else {
    				$lists['change_type'] = '优惠-';
    				$lists['moneys'] = moeny_change($lists['money']-$list['change_money']);
    			}
    		}
    		/*发票*/
    		if ($list['singleinfo'] == 1) {
    			if ($list['singletype']==1) {
    				$lists['singletype'] = '个人类型';
    			} else {
    				$lists['singletype'] = '单位类型';
    				$lists['single'] = json_decode($list['single'],true);
    			}
    		}
    		/*商品名称-数量-价格*/
    		$field = 'g.name,o.number,g.price,g.picture';
    		/*查询*/
    		$where_goods['ordersn'] = array('eq', $list['ordersn']);
    		/*商品信息*/
    		$goods = Db::name('wine_shop_goods_order')
    		->alias('o')
    		->join('wine_shop_goods g', 'o.goodsid = g.id')
    		->where($where_goods)
    		->field($field)
    		->select();
    	}
    	/*分配变量*/
    	$this->assign('list', $lists); /*订单数据*/
    	$this->assign('goods', $goods); /*商品数据*/
		$this->assign('title', '订单详情'); /*页面标题*/
		$this->assign('keywords', '订单详情'); /*页面关键词*/
		$this->assign('description', '订单详情'); /*页面描述*/
		/*调用模板*/
		return $this->fetch(TEMP_FETCH);
    }
    
    /**
     * 改价
     * @return [type] [description]
     */
    public function orderedit()
    {
    	$id = input('id');
    	$type = input('type');
    	$money = input('money');
    	if (empty($id) || empty($type) || !isset($money)) {
    		echo '<script>$(document).ready(function(){alertBox("参数缺失.","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
    		die();
    	}
    	$result = Db::name('wine_shop_order_goods')->where('id', $id)->field('money,ordersn')->find();
        if ($type == 1) {
            $result['money'] = $result['money']+$money;
        } else {
            $result['money'] = $result['money']-$money;
        }
        $result['money'] = sprintf("%.2f",$result['money']);
    	if ($result['money'] < 0.01) {
    		echo '<script>$(document).ready(function(){alertBox("总金额不能低于0.01","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
    		die();
    	}
        $ordersn = ordernostr();
    	$data = array(
    		'change_type' => $type,
    		'change_money' => $money,
            'price' => $result['money'],
            'ordersn' => $ordersn,
    		);
    	$result_data = Db::name('wine_shop_order_goods')->where('id', $id)->update($data);
        if ($result_data) {
            $order_data['ordersn'] = $ordersn;
            Db::name('wine_shop_goods_order')->where('ordersn', $result['ordersn'])->update($order_data);
    		$log_in='修改订单ID为'.$id.' 的价格';
    		$this->write_log($log_in);
	    	echo '<script>$(document).ready(function(){alertBox("修改成功..","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
	    	die();
    	}
    }

    /**
     * 发货
     */
    public function order_consignment()
    {
    	$id = input('id');//订单ID
    	$this->assign('id',$id);
    	if (empty($id)) {
    		echo '<script>$(document).ready(function(){alertBox("参数缺失.","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
    		die();
    	}
        $send = Db::name('wine_shop_order_goods')->where('id', $id)->find();
		$this->assign('send', $send); /*订单数据*/
    	if ($_POST) {
            /*查询设定收货是时间*/
            $site_arr = Db::name('site')->where('id', $this->site_id)->field('take')->find();
            /*过期时间*/
            $newtime = strtotime('+'.$site_arr['take'].' day');
	    	/*获取参数*/
	    	$favourtype = input('favourtype');/*配送类型*/
	    	$consig = input('consig');/*配送公司*/
	    	$consig_number = input('consig_number');/*快递单号*/
	    	$content = input('content');/*商家备注*/
	    	/*数据汇总 状态 1.未付款 2.待发货 3.待收货 4.已完成 5.待处理 6.已取消*/
	    	$data = array(
	    		'shop_content' => $content,
	    		'status' => 3,
	    		'express_time' => $newtime,
                'express_date' => time(),
                'express_type' => $favourtype
	    		);
	    	if ($favourtype == 1) {
	    		$data['express'] = $consig;
	    		$data['express_number'] = $consig_number;
	    	}
	    	$result = Db::name('wine_shop_order_goods')->where('id', $id)->update($data);
	    	if ($result) {
	    		$log_in='发货ID为'.$id.' 的订单';
	    		$this->write_log($log_in);
	    		echo '<script>$(document).ready(function(){alertBox("修改成功.","' . url('web/tztx.order/order') . '");})</script>';
	    		die();
	    	} else {
	    		echo '<script>$(document).ready(function(){alertBox("修改失败.","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
	    		die();
	    	}
    	}
    	/*分配变量*/
		$this->assign('title', '产品列表'); /*页面标题*/
		$this->assign('keywords', '产品列表'); /*页面关键词*/
		$this->assign('description', '产品列表'); /*页面描述*/
		/*调用模板*/
		return $this->fetch(TEMP_FETCH);
    }

    /**
     * 确认收货
     */
    public function order_take()
    {
    	$id = input('id');
    	if (empty($id)) {
    		echo '<script>$(document).ready(function(){alertBox("参数缺失","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
	    	die();
    	}
    	/*修改数据*/
    	$update = array(
    		'status' => 4,
    		'edittime' => time()
    		);
    	$result = Db::name('wine_shop_order_goods')->where('id', $id)->update($update);
    	if ($result) {
            $ordersn = Db::name('wine_shop_order_goods')->field('uid,ordersn')->where('id', $id)->find();
            /*结算分销等级与会员等级*/
            $this->user_grade($ordersn['uid']);
            /*结算佣金*/
            $this->ordersn_distribution($ordersn['ordersn']);
    		$log_in='订单ID为'.$id.' 的订单，确认收货';
	    	$this->write_log($log_in);
    		echo '<script>$(document).ready(function(){alertBox("修改成功","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
	    	die();
    	} else {
    		echo '<script>$(document).ready(function(){alertBox("修改失败","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
	    	die();
    	}
    }
    
    /**
     * 同意退款
     * @return [type] [description]
     */
    public function order_refund()
    {
        $id = input('id');
        if (empty($id)) {
            echo '<script>$(document).ready(function(){alertBox("参数缺失","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
            die();
        }
        $pay_info = Db::name('wine_shop_order_goods')->field('type')->where('id',$id)->find();
        if ($pay_info['type'] == 1) {
            $number = $this->pay_refund($id)/100;/*微信退款*/
            $company = '微信';
        } else {
            $number = $this->alipay_refund($id);/*支付宝退款*/
            $company = '支付宝';
        }
        /*修改数据*/
        $update = array(
            'status' => 7,
            'edittime' => time()
            );
        $result = Db::name('wine_shop_order_goods')->where('id', $id)->update($update);
        if ($result) {
            $log_in='ID为'.$id.' 的订单，'.$company.'成功退款，金额为￥'.$number;
            $this->write_log($log_in);
            echo '<script>$(document).ready(function(){alertBox("成功退款￥'.$number.'","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
            die();
        } else {
            echo '<script>$(document).ready(function(){alertBox("修改失败","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
            die();
        }
    }

    /**
     * 拒绝退款
     * @return [type] [description]
     */
    public function order_query()
    {
        $id = input('id');
        $shop_content = input('prompt');
        if (empty($id)) {
            echo '<script>$(document).ready(function(){alertBox("参数缺失","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
            die();
        }
        /*修改数据*/
        $update = array(
            'status' => 2,
            'edittime' => time(),
            'shop_content' => $shop_content,
            );
        $result = Db::name('wine_shop_order_goods')->where('id', $id)->update($update);
        $result = 1;
        if ($result) {
            $log_in='拒绝了订单ID为'.$id.' 的退款';
            $this->write_log($log_in);
            echo '<script>$(document).ready(function(){alertBox("修改成功","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
            die();
        } else {
            echo '<script>$(document).ready(function(){alertBox("修改失败","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
            die();
        }
    
    }

    /**
     * 微信支付退款
     * @return [type] [description]
     */
    public function pay_refund($orderid)
    {
        if(empty($orderid)){
            echo '<script>$(document).ready(function(){alertBox("参数缺失","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
            die();
        }
        $order=Db::name('wine_shop_order_goods')
            ->where('id',$orderid)
            ->where(array(
                'status'=>5,
            ))
            ->find();
        if(empty($order)){
            echo '<script>$(document).ready(function(){alertBox("订单号不存在","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
            die();
        }
        /*商品改价*/
        if (!empty($order['change_type']) && !empty($order['change_money'])) {
            if ($order['change_type'] == 1) {
                $order['money'] = $order['money']+$order['change_money'];
            } else {
                $order['money'] = $order['money']-$order['change_money'];
            }
        }
        $data=array(
            'appid'=>'wx592e5b08934adca0',/*应用ID*/
            'mch_id'=>'1509236441',/*商户号*/
            'nonce_str'=>noncestr(32),/*随机字符串*/
            'out_trade_no'=>$order['ordersn'],/*商户系统内部订单号*/
            'out_refund_no'=>$order['ordersn'],/*商户系统内部的退款单号*/
            'total_fee'=>moeny_change($order['money'])*100,/*订单总金额*/
            'refund_fee'=>moeny_change($order['money'])*100/*退款总金额*/
        );
        $data['sign'] = makeSign($data,'taozuitianxia1234567890qwertyuio');/*签名*/
        $xml = arrayToxml($data);
        /*申请退款-接口请求*/
        $resultCurl = wy_pay('https://api.mch.weixin.qq.com/secapi/pay/refund',$xml);
        /*请求结果*/
        if (!$resultCurl) {
           echo '<script>$(document).ready(function(){alertBox("连接微信失败，请重新再试","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
            die();
        }
        $result = xmlToarray($resultCurl);
        /*响应结果*/
        if ($result['return_code'] != "SUCCESS") {
            echo '<script>$(document).ready(function(){alertBox("申请退款失败，请重新再试","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
            die();
        }
        /*业务结果*/
        if ($result['result_code'] != "SUCCESS") {
            echo '<script>$(document).ready(function(){alertBox("'.$result['err_code_des'].'","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
            die();
        }
        /*返回退款金额*/
        return $result['refund_fee'];
    }
    /**
     * 支付宝支付退款
     * @return [type] [description]
     */
    public function alipay_refund($orderid)
    {
        if(empty($orderid)){
            echo '<script>$(document).ready(function(){alertBox("参数缺失","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
            die();
        }
        $order=Db::name('wine_shop_order_goods')
            ->where('id',$orderid)
            ->where(array(
                'status'=>5,
            ))
            ->find();
        if(empty($order)){
            echo '<script>$(document).ready(function(){alertBox("订单号不存在","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
            die();
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
        /*引入*/
        include_once ('./vendor\alipay-sdk-PHP-3.3.0\AopSdk.php');
        $aop = new \AopClient ();
        $aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
        $aop->appId = '2018070360542305';
        $aop->rsaPrivateKey = self::RSA_PRIVATE_KEY;
        $aop->alipayrsaPublicKey= self::ALIPAY_RSA_PUBLIC_KEY;
        $aop->apiVersion = '1.0';
        $aop->signType = 'RSA2';
        $aop->postCharset='UTF-8';
        $aop->format='json';
        $request = new \AlipayTradeRefundRequest();
        $result_info = array(
            'out_trade_no' => $order['ordersn'],
            'refund_amount' => $order['money'],
            );
        $request->setBizContent(json_encode($result_info));
        $result = $aop->execute ( $request); 
        $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
        $resultCode = $result->$responseNode->code;
        if(!empty($resultCode)&&$resultCode == 10000){
            return $order['money'];
        } else {
            echo '<script>$(document).ready(function(){alertBox("申请退款失败，请重新再试","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
            die();
        }
    }








}