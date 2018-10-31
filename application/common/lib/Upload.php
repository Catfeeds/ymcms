<?php
namespace app\common\lib;
use Qiniu\Auth as Auth;/*引入鉴权类*/
use Qiniu\Storage\UploadManager;/*引入上传类*/
use Qiniu\Storage\BucketManager;
/**
 * 七牛图片上传基础类库
 * Class Upload
 * @package app\common\lib
 */
class Upload {

    /**
     * 文件上传
     */
    private static function upfile($tmp_name,$name) {
		$video_tmp_name = $tmp_name;
        if(empty($video_tmp_name)) {
            exception('您提交的图片数据不合法',404);
        }
        //要上传的文件的
		$file = $name;
        $pathinfo = pathinfo($file);
        $ext = $pathinfo['extension'];
        $config = config('qiniu');
		/*引入类库*/
		include_once './vendor/qiniu/php-sdk/autoload.php';      
		//构建一个鉴权对象
        $auth  = new Auth($config['ak'],$config['sk']);
        //生成上传的token
        $token = $auth->uploadToken($config['bucket']);
        //上传到七牛后保存的文件名
        $key  = date('Y')."/".date('m')."/".substr(md5($file),0,5).date('YmdHis').rand(0,9999).'.'.$ext;
        // $key  = date('Y')."/".date('m')."/".$name;/*中文文件名*/
        //初始UploadManager类
        $uploadMgr = new UploadManager();
        list($ret,$err) = $uploadMgr->putFile($token,$key,$video_tmp_name);
        if($err !== null) {
            return null;
        } else {
			$file_name = $config['image_url'].'/'.$key;
            return $file_name;
        }
    }
	
    /**
     * 单/批量文件上传
     */
    public static function fileput($files){
		$filename_str = '';
		if(is_array($files['tmp_name'])){
			foreach($files['tmp_name'] as $k=>$v){
				$tmp_name = $files['tmp_name'][$k];
				$name	  = $files['name'][$k];
				$filename = self::upfile($tmp_name,$name);
				if(!empty($filename)){
					$filename_str .= ','.$filename;	
				}
			}
			$filename_str = trim($filename_str,',');
		}else{
			$tmp_name = $files['tmp_name'];
			$name	  = $files['name'];
			$filename_str = self::upfile($tmp_name,$name);
		}
		return $filename_str;
    }
		
}