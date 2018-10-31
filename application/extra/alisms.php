<?php
/**
 * 阿里短信相关的配置
 */
return [
    'appKey' 				=> 'LTAIogrpClThomSJ',
    'secretKey' 			=> 'Q9A4wyH8eHq92ndlbGfEvlxxA5yRGx',
    'signName' 				=> '陶醉天下实业有限公司',
    'templateCode'  		=> 'SMS_136840037',/*注册模板*/
	'tempCode_pass'  		=> 'SMS_136840036',/*忘记密码模板*/
	'tempCode_phonebind' 	=> 'SMS_136840039',/*登录确认验证码*/
    'identify_time' 		=> 600,
];