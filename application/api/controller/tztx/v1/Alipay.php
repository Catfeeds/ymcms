<?php
namespace app\api\controller\tztx\v1;
use app\api\controller\Common;
use think\Controller;
use app\common\lib\exception\ApiException;
use app\common\lib\IAuth;
use think\Db;
require_once ('./vendor\alipay-sdk-PHP-3.3.0\AopSdk.php');

class Alipay extends Controller{
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
    private $callback = "http://www.test.com/notify/alipay_notify.html";
 
    /**
     *生成APP支付订单信息
     * @param string $ordersn   商品订单ID
     * @param string $subject   支付商品的标题
     * @param string $body      支付商品描述
     * @param float $pre_price  商品总支付金额
     * @param int $expire       支付交易时间
     * @return bool|string  返回支付宝签名后订单信息，否则返回false
     */
    public function unifiedorder($ordersn, $subject,$body,$pre_price,$expire){
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
            return htmlspecialchars($response);//就是orderString 可以直接给客户端请求，无需再做处理。
 
    }
}