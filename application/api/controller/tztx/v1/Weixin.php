<?php
namespace app\api\controller\tztx\v1;
use app\api\controller\Common;
use think\Controller;
use app\common\lib\exception\ApiException;
use app\common\lib\IAuth;
use think\Db;

class Weixin extends Controller {

	/**
	 * 微信支付 - 支付回调
	 * @return [type] [description]
	 */
    public function wx_notify(){
        //写日志
        //$file = './public/datalog/'.date("YmdHis").mt_rand(1000000,9999999).'.txt';
        //$result	= file_put_contents($file,'检测进入通知');
        //1、获取回调地址推送过来的post数据(xml格式)
        $getXML = file_get_contents("php://input");
        //2、处理消息类型，并设置回复类型和内容
        $content = xmlToarray($getXML);/*xml转换成数组*/
        //3、业务处理
        if(!empty($getXML) && $content['return_code'] == 'SUCCESS'){
            /*解析数据*/
            $data = array();
            foreach($content as $k=>$v){
                if($k != 'sign'){
                    $data[$k] = $v;
                }
            }
            /*签名安全验证*/
            ksort($data);
            $canshu = "";
            foreach($data as $key=>$vo){
                $canshu .= $key."=".$vo."&";
            }
            $canshu = trim($canshu,"&");
            $canshu = $canshu."&key=".'taozuitianxia1234567890qwertyuio';
            $sign = strtoupper(md5($canshu));
            if($sign != $content['sign']){
                //签名校验失败
                $xml = '<xml><return_code><![CDATA[FAIL]]></return_code><return_msg><![CDATA[签名失败]]></return_msg></xml>';
                echo $xml;
            }else{
                //查询订单
                $out_trade_no = (string)$content['out_trade_no'];
                $total_fee = $content['total_fee'];
                $order_where['ordersn'] = $out_trade_no;
                /*固定字段*/
                $field = 'id,uid,status,ordersn';
                $order = Db::name('wine_shop_order_goods')->field($field)->where($order_where)->find();
                if($order){
                    if($content['result_code'] == 'SUCCESS'){
                        /*支付成功*/
                        /*更改订单的状态和支付方式*/
                        $orderdata=array(
                            'status' => 2,
                            'type' => 1,
                        );
                        if($order['status']==1){
                            $statusSaveResult = Db::name('wine_shop_order_goods')->where('id', $order['id'])->update($orderdata);
                            if($statusSaveResult){
                            	/*商品库存修改*/
                            	$this->goods_info($order['ordersn']);
                            	 //向微信返回成功状态
		                        $xml = '<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>';
		                        echo $xml;
                            }else{
                                /*错误日志*/
                                $user = Db::name('wine_users')->field('id,phone,nickname')->where('id', $order['uid'])->find();

                                $logdata = array(
                                    /*日志内容*/
                                    'name' => '支付金额异常，订单号:'.$order['ordersn']
                                );
                                $log = new \loginfo\Log();/*实例化日志类*/
                                $result_log = $log->writeLog($user['id'],$user['phone'],$user['nickname'],'data',$logdata);
                            }
                        }else{
                        	//订单参数校验错误
		                    $xml = '<xml><return_code><![CDATA[FAIL]]></return_code><return_msg><![CDATA[订单参数校验错误]]></return_msg></xml>';
		                    echo $xml;
                        }
                    }else{
                        //支付失败
                        $xml = '<xml><return_code><![CDATA[FAIL]]></return_code><return_msg><![CDATA[支付失败]]></return_msg></xml>';
                        echo $xml;
                    }
                }else{
                    //订单参数校验错误
                    $xml = '<xml><return_code><![CDATA[FAIL]]></return_code><return_msg><![CDATA[订单参数校验错误]]></return_msg></xml>';
                    echo $xml;
                }
            }
        }else{
            //通知到达失败
            $xml = '<xml><return_code><![CDATA[FAIL]]></return_code><return_msg><![CDATA[通知到达失败]]></return_msg></xml>';
            echo $xml;
        }
    }

    /**
     * 订单商品信息修改（库存，销量）
     */
    public function goods_info($ordersn)
    {
    	/*固定字段*/
    	$field = 'g.id,o.number,g.total,g.sales,g.seckill';
    	/*条件*/
    	$where['ordersn'] = array('eq', $ordersn);
    	/*查询*/
    	$list = Db::name('wine_shop_goods_order')
    				->alias('o')
    				->join('wine_shop_goods g', 'o.goodsid = g.id', 'left')
    				->field($field)
    				->where($where)
    				->select();
    	foreach ($list as $k => $v) {
    		/*库存*/
    		$total = ($v['total']-$v['number']>0) ? ($v['total']-$v['number']):0;
    		/*销量*/
    		$sales = $v['sales']+$v['number'];
	    	$lists = array(
	    		'total' => $total,
	    		'sales' => $sales,
	    		);
            /*秒杀商品*/
            if ($v['seckill'] != 0) {
                $result = Db::name('wine_seckill')->field('seckill_num,total')->where('id', $v['seckill'])->find();
                /*库存*/
                $total = ($result['total']-$v['number']>0) ? ($result['total']-$v['number']):0;
                /*销量*/
                $seckill_num = $result['seckill_num']+$v['number'];
                $re = array(
                    'total' => $total,
                    'seckill_num' => $seckill_num,
                    );
                Db::name('wine_seckill')->where('id', $v['seckill'])->update($re);
            } else {
    	    	Db::name('wine_shop_goods')->where('id', $v['id'])->update($lists);
            }
    	}

    }

   /**
     * 支付宝支付成功异步通知
     * @return [type] [description]
     */
    public function alipay_notify()
    {
        /**
         * alipay_notify.php.
         * User: lvfk
         * Date: 2017/10/26 0026
         * Time: 13:48
         * Desc: 支付宝支付成功异步通知
         */
        include_once ('./vendor\alipay-sdk-PHP-3.3.0\AopSdk.php');
        //验证签名
        $aop = new \AopClient();
        /*支付宝公钥*/
        $aop->alipayrsaPublicKey = 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA8E3PeMb72XTwqZrxQbzdHQco8HmUmW+pUgYCFejOUTtp8iApB7RGkeyVbOt1Waoqmy6Ji4QoOurJbZucMzh5CimW4TjyxKUYq7ZpSKMC8tyv3YxKkNn/62z3mS8VzdINxDyE+p6vLOFzINAf1r0/E7PGCiIuuc4eQAx9Ocq4IAHWK2IXu8CrfogMiaVes6UmlCkgoupO3w+RRggjH7ut/6n9CN82dNAa+C/nZ+vBJGoQGbtVA+5ZvBeFzW/nDcNgerAL5TUxf0Ba1dU6huH8oYickCz1Q+H7RYb1cGB8LVod2Ld1arxgp6feN/dEoABehV5Ik2DKV/YzLSLnETLk5QIDAQAB';
        $flag = $aop->rsaCheckV1($_POST, NULL, "RSA2");
        //验签
        if($flag){
            //处理业务，并从$_POST中提取需要的参数内容
            if($_POST['trade_status'] == 'TRADE_SUCCESS' || $_POST['trade_status'] == 'TRADE_FINISHED'){//处理交易完成或者支付成功的通知
                //获取订单号
                $out_trade_no = $_POST['out_trade_no'];
                //交易号
                $trade_no = $_POST['trade_no'];
                //订单支付时间
                $gmt_payment = $_POST['gmt_payment'];
                //转换为时间戳
                $gtime = strtotime($gmt_payment);
         
                //此处编写回调处理逻辑
                //查询订单
                $order_where['ordersn'] = $out_trade_no;
                /*固定字段*/
                $field = 'id,uid,status,ordersn';
                $order = Db::name('wine_shop_order_goods')->field($field)->where($order_where)->find();
                if($order){
                    /*支付成功*/
                    /*更改订单的状态和支付方式*/
                    $orderdata=array(
                        'status' => 2,
                        'type' => 2,
                    );
                    if($order['status']==1){
                        $statusSaveResult = Db::name('wine_shop_order_goods')->where('id', $order['id'])->update($orderdata);
                        if($statusSaveResult){
                            /*商品库存修改*/
                            $this->goods_info($order['ordersn']);
                             //向微信返回成功状态
                            die('success');
                        }else{
                            /*错误日志*/
                            $user = Db::name('wine_users')->field('id,phone,nickname')->where('id', $order['uid'])->find();

                            $logdata = array(
                                /*日志内容*/
                                'name' => '支付宝支付金额异常，订单号:'.$order['ordersn']
                            );
                            $log = new \loginfo\Log();/*实例化日志类*/
                            $result_log = $log->writeLog($user['id'],$user['phone'],$user['nickname'],'data',$logdata);
                        }
                    }
                }
                //处理成功一定要返回 success 这7个字符组成的字符串，
                //die('success');//响应success表示业务处理成功，告知支付宝无需在异步通知
            }
        }
    }
}
