<?php
/***** 公共方法文件 ****/

/*将客户端信息写进SESSION*/
function session_client_put(){
	$client = new \client\Info();/*实例化*/	
	$client->session_put();		
}

/**
 * 通用化API接口数据输出
 * @param int $status 业务状态码
 * @param string $message 信息提示
 * @param [] $data 数据
 * @param int $httpCode http状态码
 * @return array
 */
function show($status,$message,$data=array(),$httpCode=200){
	$data = array(
		'status'	=> $status,
		'message'	=> $message,
		'data'		=> $data
	);
	return json($data,$httpCode);
}

/*百度坐标-查询城市名称*/
function getcity($latlng='28.213478,112.979353')
{
	$key = 'ujuIh9RdpSsnLG9WewW7B6OacL5wnCeV';
	$url = "http://api.map.baidu.com/geocoder?location={$latlng}&output=json&key={$key}";
	return curl_get($url);
}

/*百度坐标-计算两点之点的距离*/
function getDistance($lat1,$lng1,$lat2,$lng2,$decimal = 2){
	$radLat1 = $lat1 * M_PI / 180;
	$radLat2 = $lat2 * M_PI / 180;
	$a = $lat1 * M_PI / 180 - $lat2 * M_PI / 180;
	$b = $lng1 * M_PI / 180 - $lng2 * M_PI / 180;
	$s = 2 * asin(sqrt(pow(sin($a / 2), 2) + cos($radLat1) * cos($radLat2) * pow(sin($b / 2), 2)));
	$s = $s * 6371.004;
	$s = round($s * 1000);
	$s /= 1000;
	return round($s,$decimal);
}

/**
* @name 排序 按照数组的某个字段值排序
* @param $array 排序数组 $field 排序字段 $direction 排序顺序
*/
function sort_array($array,$field,$direction) {
	if($direction == 'desc') {
		$direction = 'SORT_DESC';
	}
	if($direction == 'asc') {
		$direction = 'SORT_ASC';
	}
	$sort = array(
		'direction' => $direction, //排序顺序标志 SORT_DESC 降序；SORT_ASC 升序
		'field' => $field, //排序字段
	);
	$arrSort = array();
	foreach($array as $key => $value) {
		foreach($value as $k => $v) {
			$arrSort[$k][$key] = $v;
		}
	}
	if($sort['direction']){
		array_multisort($arrSort[$sort['field']], constant($sort['direction']), $array);
	}
	return $array;
}

/*过滤空白*/
function myTrim($str) {
	$search = array(" ", "　", "\n", "\r", "\t");
	$replace = array("", "", "", "", "");
	return str_replace($search, $replace, $str);
}

/*解析工作周期*/
function weekday($status = 0){
	switch($status){
		case 1:
			$str = '周一'; 
			break;
		case 2:
			$str = '周二'; 
			//$str = '已付款'; 
			break;
		case 3:
			$str = '周三';
			break; 
		case 4:
			$str = '周四';
			break;
		case 5:
			$str = '周五';
			break; 
		case 6:
			$str = '周六'; 
			break;
		case 0:
			$str = '周日'; 
			break;
		default:
			$str = '未知';														
	}
	return $str;
}

/*生成随机字符串 start*/
function noncestr($length){			
	$chars = "abcdefghijklmnopqrstuvwxyz0123456789";
	$nonceStr ="";/*随机字符串*/
	for ( $i = 0; $i < $length; $i++ )  {
		$nonceStr .= substr($chars, mt_rand(0, strlen($chars)-1), 1);
	}
	return $nonceStr;		
}
/*生成字符串 end*/

/*生成随机数字 start*/
function noncenumber($length){			
	$chars = "0123456789";
	$nonceStr ="";/*随机字符串*/
	for ( $i = 0; $i < $length; $i++ )  {
		$nonceStr .= substr($chars, mt_rand(0, strlen($chars)-1), 1);
	}
	return $nonceStr;		
}
/*生成随机数字 end*/

/*生成订单号 start*/
function ordernostr(){			
	return date('YmdHis',time()).noncenumber(10);		
}
/*生成订单号 end*/

/** 
 * 生成二维码 
 * @param  string  $url  url连接 
 * @param  integer $size 尺寸 纯数字 
 */  
function qrcode($url,$size=20){
	header('Content-Type: image/png');
    /*引入extend/qrcode/qrcode.php*/
	import('qrcode.phpqrcode', EXTEND_PATH);
	/*
	第一个参数$text；就是上面代码里的URL网址参数；
	第二个参数$outfile默认为否；不生成文件；只将二维码图片返回；否则需要给出存放生成二维码图片的路径；
	第三个参数$level默认为L；这个参数可传递的值分别是L(QR_ECLEVEL_L，7%)、M(QR_ECLEVEL_M，15%)、Q(QR_ECLEVEL_Q，25%)、H(QR_ECLEVEL_H，30%)；这个参数控制二维码容错率；不同的参数表示二维码可被覆盖的区域百分比。利用二维维码的容错率；我们可以将头像放置在生成的二维码图片任何区域；
	第四个参数$size；控制生成图片的大小；默认为4；
	第五个参数$margin；控制生成二维码的空白区域大小；
	第六个参数$saveandprint；保存二维码图片并显示出来；$outfile必须传递图片路径；
	第七个参数$back_color；背景颜色；
	第八个参数$fore_color；绘制二维码的颜色；
	note：第七、第八个参数需要传16进制是色值；并且要把“#”替换为“0x”
	举个栗子：
	白色：#FFFFFF => 0xFFFFFF
	黑色：#000000 => 0x000000
	*/
	ob_clean();
	//打开缓冲区
    ob_start();
    QRcode::png($url,false,QR_ECLEVEL_L,$size,1,false,0xFFFFFF,0x000000);
    //这里就是把生成的图片流从缓冲区保存到内存对象上，使用base64_encode变成编码字符串，通过json返回给页面。
    $imageString = base64_encode(ob_get_contents());
    //关闭缓冲区
    ob_end_clean();
    //把生成的base64字符串返回给前端
    return 'data:image/png;base64,'.$imageString;
}


/**
 * 以GET方式提交
 */
function curl_get($url){
	//1、初始化curl
	$ch = curl_init();	
	//2、设置curl的参数
	curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,0); // 跳过证书检查  
	curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,0);  // 从证书中检查SSL加密算法是否存在  		
	curl_setopt($ch,CURLOPT_URL,$url);
	curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
	//3、采集
	$output = curl_exec($ch);
	//4、关闭
	curl_close($ch);
	//5、返回结果集
	$Array = json_decode($output,true);	
	return $Array;
}

/**
 * GET 请求
 * @param string $url
 */
function http_get($url){
	$ch = curl_init();
	if(stripos($url,"https://")!==FALSE){
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
		curl_setopt($ch, CURLOPT_SSLVERSION, 1); //CURL_SSLVERSION_TLSv1
	}
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1 );
	$sContent = curl_exec($ch);
	$aStatus = curl_getinfo($ch);
	curl_close($ch);
	if(intval($aStatus["http_code"])==200){
		return $sContent;
	}else{
		return 0;
	}
}

/**
 * 以POST方式提交
 */
function curl_post($url,$data){
	$ch = curl_init();/*接口请求*/
	curl_setopt($ch, CURLOPT_TIMEOUT,30);/*超时时间*/
	curl_setopt($ch, CURLOPT_URL, $url);/*请求url*/
	curl_setopt($ch, CURLOPT_POST, 1);/*定义提交类型 1：POST ；0：GET*/
	curl_setopt($ch, CURLOPT_HEADER, 0);/*定义是否显示状态头 1：显示 ； 0：不显示*/
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);/*定义是否直接输出返回流*/
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);/*严格校验1*/
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);/*严格校验2*/
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);/*定义提交的数据，这里是XML文件*/
	$output = curl_exec($ch);/*运行curl*/
	if ($output === FALSE){
	    echo 'cURL Error:'.curl_error($ch);
	}
	if($output){
		curl_close($ch);
		unset($ch);
		return $output;		
	}
	$output = json_decode($output,true);
	curl_close($ch);
	unset($ch);
	return $output;
}

function wy_pay($url,$xml)
{
	$ch = curl_init();/*接口请求*/
	curl_setopt($ch, CURLOPT_URL, $url);/*请求url*/
	curl_setopt($ch, CURLOPT_HEADER, 0);/*定义是否显示状态头 1：显示 ； 0：不显示*/
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);/*定义是否直接输出返回流*/
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1); //证书检查
	curl_setopt($ch, CURLOPT_SSLCERTTYPE, 'pem');
	curl_setopt($ch, CURLOPT_SSLCERT, dirname(__FILE__).'/wx/apiclient_cert.pem');
	curl_setopt($ch, CURLOPT_SSLCERTTYPE, 'pem');
	curl_setopt($ch, CURLOPT_SSLKEY, dirname(__FILE__).'/wx/apiclient_key.pem');
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
	$output = curl_exec($ch);
	if ($output === FALSE){
	    echo 'cURL Error:'.curl_error($ch);
	}
	if($output){
		curl_close($ch);
		unset($ch);
		return $output;		
	}
	$output = json_decode($output,true);
	curl_close($ch);
	unset($ch);
	return $output;
}

/**
 * POST 请求
 * @param string $url
 * @param array $param
 * @param boolean $post_file 是否文件上传
 * @return string content
 */
function http_post($url,$param,$post_file=false){
	$oCurl = curl_init();
	if(stripos($url,"https://")!==FALSE){
		curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($oCurl, CURLOPT_SSLVERSION, 1); //CURL_SSLVERSION_TLSv1
	}
	if (is_string($param) || $post_file) {
		$strPOST = $param;
	} else {
		$aPOST = array();
		foreach($param as $key=>$val){
			$aPOST[] = $key."=".urlencode($val);
		}
		$strPOST =  join("&", $aPOST);
	}
	curl_setopt($oCurl, CURLOPT_URL, $url);
	curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1 );
	curl_setopt($oCurl, CURLOPT_POST,true);
	curl_setopt($oCurl, CURLOPT_POSTFIELDS,$strPOST);
	$sContent = curl_exec($oCurl);
	$aStatus = curl_getinfo($oCurl);
	curl_close($oCurl);
	if(intval($aStatus["http_code"])==200){
		return $sContent;
	}else{
		return false;
	}
}

/*资源请求*/
function https_request($url,$data=null){
		$curl = curl_init();
		$header = "Accept-Charset:utf-8";
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
		//curl_setopt($curl, CURLOPT_SSLVERSION, 3);
		curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
		if (!empty($data)){
			curl_setopt($curl, CURLOPT_POST, 1);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
		}
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		$output = curl_exec($curl);
		F('1', $output);
		
		$errorno= curl_errno($curl);
		if ($errorno) {
			return array('curl'=>false,'errorno'=>$errorno);
		}else{
			$res = json_decode($output,1);

			if ($res['errcode']){
				return array('errcode'=>$res['errcode'],'errmsg'=>$res['errmsg']);
			}else{
				return $res;
			}
		}
		curl_close($curl);
}

/*生成微信签名*/
function makeSign($arrayVal,$key)
{
	/*签名步骤一：按字典序排序参数*/
	ksort($arrayVal);
	/*格式化参数格式化成url参数*/
	$buff = "";
	foreach ($arrayVal as $k => $v)
	{
		if($k != "sign" && $v != "" && !is_array($v)){
			$buff .= $k . "=" . $v . "&";
		}
	}
	$string = trim($buff, "&");
	/*签名步骤二：在string后加入KEY*/
	$string = $string . "&key=$key";
	/*签名步骤三：MD5加密*/
	$string = md5($string);
	/*签名步骤四：所有字符转为大写*/
	$result = strtoupper($string);
	return $result;
}

/**
 * 数组 转 对象
 * @param array $arr 数组
 * @return object
 */
function array_to_object($arr) {
    if (gettype($arr) != 'array') {
        return;
    }
    foreach ($arr as $k => $v) {
        if (gettype($v) == 'array' || getType($v) == 'object') {
            $arr[$k] = (object)array_to_object($v);
        }
    }
    return (object)$arr;
}
 
/**
 * 对象 转 数组*
 * @param object $obj 对象
 * @return array
 */
function object_to_array($obj) {
    $obj = (array)$obj;
    foreach ($obj as $k => $v) {
        if (gettype($v) == 'resource') {
            return;
        }
        if (gettype($v) == 'object' || gettype($v) == 'array') {
            $obj[$k] = (array)object_to_array($v);
        }
    }
    return $obj;
}

/*数组转换XML*/
function arrayToxml($array){
	/*组装xml信息*/
	$xml = '<xml>';
	foreach ($array as $k => $v) {
		if (is_numeric($v)) {
			$xml .= '<' . $k . '>' . $v . '</' . $k . '>';
		} else {
			$xml .= '<' . $k . '><![CDATA[' . $v . ']]></' . $k . '>';
		}
		
	}
	$xml .= '</xml>';
	return $xml;
}


/*XML转换数组*/
function xmlToarray($xml){ 
	$xmlstring = simplexml_load_string($xml,'SimpleXMLElement',LIBXML_NOCDATA);
	$val = (array)$xmlstring;/*类型强制转换*/ 
	/*$val = json_decode(json_encode($xmlstring),true);*//*json做中转*/
	return $val;
}

/*移动端判断*/
function isMobile(){ 
    // 如果有HTTP_X_WAP_PROFILE则一定是移动设备
    if (isset ($_SERVER['HTTP_X_WAP_PROFILE'])){
        return true;
    } 
    // 如果via信息含有wap则一定是移动设备,部分服务商会屏蔽该信息
    if (isset ($_SERVER['HTTP_VIA'])){ 
        // 找不到为flase,否则为true
        return stristr($_SERVER['HTTP_VIA'], "wap") ? true : false;
    } 
    // 脑残法，判断手机发送的客户端标志,兼容性有待提高
    if (isset ($_SERVER['HTTP_USER_AGENT'])){
        $clientkeywords = array ('nokia',
            'sony',
            'ericsson',
            'mot',
            'samsung',
            'htc',
            'sgh',
            'lg',
            'sharp',
            'sie-',
            'philips',
            'panasonic',
            'alcatel',
            'lenovo',
            'iphone',
            'ipod',
            'blackberry',
            'meizu',
            'android',
            'netfront',
            'symbian',
            'ucweb',
            'windowsce',
            'palm',
            'operamini',
            'operamobi',
            'openwave',
            'nexusone',
            'cldc',
            'midp',
            'wap',
            'mobile'
            ); 
        // 从HTTP_USER_AGENT中查找手机浏览器的关键字
        if (preg_match("/(" . implode('|', $clientkeywords) . ")/i", strtolower($_SERVER['HTTP_USER_AGENT']))){
            return true;
        } 
    } 
    // 协议法，因为有可能不准确，放到最后判断
    if (isset ($_SERVER['HTTP_ACCEPT'])){ 
        // 如果只支持wml并且不支持html那一定是移动设备
        // 如果支持wml和html但是wml在html之前则是移动设备
        if ((strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') !== false) && (strpos($_SERVER['HTTP_ACCEPT'], 'text/html') === false || (strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') < strpos($_SERVER['HTTP_ACCEPT'], 'text/html'))))
        {
            return true;
        } 
    } 
    return false;
} 

/*判断是否游览器访问*/
function is_weixin(){ 
	if(strpos($_SERVER['HTTP_USER_AGENT'],'MicroMessenger') !== false){
        return true;
    }else{ 
 return false;
	}
}

/*检查是否该站会员，参数1:站点ID 参数2:用户会员组信息 返回bool*/
function checkSiteUser($site_id,$user_group_ids){
	/*匹配站点会员*/
	$user_level_status = 0;/*判定是否属于本站会员*/
	/*查询站点*/
	$site_group_where['site_id'] = $site_id;
	$site_group_where['type'] = array('IN','1,2'); 				
	if($site_id == 1){
		/*系统站点，面向系统网站会员、管理员以及其它站点管理员*/
		$site_group_whereOr['type'] = 2;
		$site_group_arr = db('group')->field('id')->where($site_group_where)->whereOr($site_group_whereOr)->select();
	}else{
		/*普通站点，面向本站会员、管理员*/
		$site_group_arr = db('group')->field('id')->where($site_group_where)->select();
	}
	$user_group_arr = explode(',',$user_group_ids);
	foreach($site_group_arr as $k=>$v){
		foreach($user_group_arr as $k2=>$v2){
			if($v['id'] == $v2){
				$user_level_status = 1;
			}
		}
	}
	if($user_level_status == 1){
		return true;
	}else{
		return false;
	}
}

/*获取中文首字母*/
function getfirstchar($s0){ 
	$firstchar_ord=ord(strtoupper($s0{0})); 
	if (($firstchar_ord>=65 and $firstchar_ord<=91)or($firstchar_ord>=48 and $firstchar_ord<=57)) return $s0{0}; 
	$s=iconv("UTF-8","gb2312", $s0); 
	$asc=ord($s{0})*256+ord($s{1})-65536; 
	if($asc>=-20319 and $asc<=-20284)return "A"; 
	if($asc>=-20283 and $asc<=-19776)return "B"; 
	if($asc>=-19775 and $asc<=-19219)return "C"; 
	if($asc>=-19218 and $asc<=-18711)return "D"; 
	if($asc>=-18710 and $asc<=-18527)return "E"; 
	if($asc>=-18526 and $asc<=-18240)return "F"; 
	if($asc>=-18239 and $asc<=-17923)return "G"; 
	if($asc>=-17922 and $asc<=-17418)return "H"; 
	if($asc>=-17417 and $asc<=-16475)return "J"; 
	if($asc>=-16474 and $asc<=-16213)return "K"; 
	if($asc>=-16212 and $asc<=-15641)return "L"; 
	if($asc>=-15640 and $asc<=-15166)return "M"; 
	if($asc>=-15165 and $asc<=-14923)return "N"; 
	if($asc>=-14922 and $asc<=-14915)return "O"; 
	if($asc>=-14914 and $asc<=-14631)return "P"; 
	if($asc>=-14630 and $asc<=-14150)return "Q"; 
	if($asc>=-14149 and $asc<=-14091)return "R"; 
	if($asc>=-14090 and $asc<=-13319)return "S"; 
	if($asc>=-13318 and $asc<=-12839)return "T"; 
	if($asc>=-12838 and $asc<=-12557)return "W"; 
	if($asc>=-12556 and $asc<=-11848)return "X"; 
	if($asc>=-11847 and $asc<=-11056)return "Y"; 
	if($asc>=-11055 and $asc<=-10247)return "Z"; 
	return null; 
} 

/*天气查询*/
function weather_api($city){
	$apiUrl = 'http://www.sojson.com/open/api/weather/json.shtml?city='.$city;
	$result = file_get_contents($apiUrl);
	$contentArr = json_decode($result,true);
	$limit = 0;
	if($contentArr['status'] == 200){	
		$contentArr['data']['edittime'] = date("Ymd");/*追加时间标记*/			
		$fileContent = json_encode($contentArr['data']);
		$filePath = './public/datalog/weather/data_'.$city.'.txt';
		$result_file = file_put_contents($filePath,$fileContent);
		$weather = file_get_contents($filePath);
		$weatherArr = json_decode($weather,true);	
		return $weatherArr;
	}else{
		$limit += 1;
		if($limit < 3){
			weather_api($city);
		}else{
			return false;
		}
	}
}
function weather($city){
	$filePath = './public/datalog/weather/data_'.$city.'.txt';
	if(is_file($filePath)){
		$weather = file_get_contents($filePath);
		$weatherArr = json_decode($weather,true);
		if($weatherArr['edittime'] != date("Ymd")){
			$weatherArr = weather_api($city);					
		}			
	}else{
		$weatherArr = weather_api($city);				
	}
	return $weatherArr;
}

/*订单状态*/
function orderStatusToStr($status){
	switch($status){
		case 1:
			$str = '未付款'; 
			break;
		case 2:
			$str = '待确认'; 
			//$str = '已付款'; 
			break;
		case 3:
			$str = '待发货';
			break; 
		case 4:
			$str = '已发货';
			break;
		case 5:
			$str = '已收货';
			break; 
		case 6:
			$str = '退款申请中'; 
			break;
		case 7:
			$str = '退款入账中'; 
			break;
		case 8:
			$str = '退款成功'; 
			break;
			break;
		case 9:
			$str = '退款失败'; 
			break;
		case 10:
			$str = '已完成'; 
			break;
		case 11:
			$str = '已过期'; 
			break;
		case 12:
			$str = '已关闭';
			break;
		case 13:
			$str = '已删除'; 
			break;
		case 14:
			$str = '支付金额异常';	
			break;
		default:
			$str = '未知';														
	}
	return $str;
}

/*手机号码正则匹配*/
function phone_check($pnone)
{
	if(preg_match("/^1[34578]{1}\d{9}$/",$pnone)){  
		return true;
	}else{  
	   return false;
	}
}

/**
 * 订单状态
 * 状态 1.未付款 2.待发货 3.待收货 4.已完成 5.待处理 6.已取消
 */
function order_status($status)
{
	switch ($status) {
		case 1:
			$content = '未付款';
			break;
		case 2:
			$content = '待发货';
			break;
		case 3:
			$content = '待收货';
			break;
		case 4:
			$content = '已完成';
			break;
		case 5:
			$content = '退款中';
			break;
		case 6:
			$content = '已取消';
			break;
		case 7:
			$content = '退款成功';
			break;
		default:
			$content = '未知';
			break;
	}
	return $content;
}

/**
 * 订单状态
 * 状态 1.等待付款 2.等待发货 3.买家已发货 4.交易成功 5.待处理 6.交易取消
 */
function orderstatus($status)
{
	switch ($status) {
		case 1:
			$content = '等待付款';
			break;
		case 2:
			$content = '等待发货';
			break;
		case 3:
			$content = '买家已发货';
			break;
		case 4:
			$content = '交易成功';
			break;
		case 5:
			$content = '退款中';
			break;
		case 6:
			$content = '交易取消';
			break;
		case 7:
			$content = '退款成功';
			break;
		default:
			$content = '未知';
			break;
	}
	return $content;
}

/**
 * 提现状态
 */
function commision_status($status)
{
	switch ($status) {
		case 1:
			$content = '申请中';
			break;
		case 2:
			$content = '已拒绝';
			break;
		case 3:
			$content = '已同意';
			break;
		default:
			$content = '未知';
			break;
	}
	return $content;
}

/**
 * 提现状态
 */
function commision_type($type)
{
	switch ($type) {
		case 1:
			$content = '微信';
			break;
		case 2:
			$content = '支付宝';
			break;
		case 3:
			$content = '银行卡';
			break;
		default:
			$content = '未知';
			break;
	}
	return $content;
}

/**
 * 金额取2位小数
 */
function moeny_change($money)
{
	/*利用sprintf格式化字符串*/
    $money = sprintf("%.2f",$money);
    return $money;
}