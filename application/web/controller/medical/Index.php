<?php
namespace app\web\controller\medical;
use think\Controller;
use think\Request;
use think\Db;
use app\common\lib\Upload;
/*欢迎页面*/
class Index extends Controller {

	/*首页*/
	public function index(){
		/*查询用户数量*/
		$user_number_where['group_ids'] = 2;
		$user_number = Db::table('ym_user')->where($user_number_where)->count();
		$this->assign('user_number',$user_number);
		/*查询医生数量*/
		$doctor_number_where['group_ids'] = 4;
		$doctor_number = Db::table('ym_user')->where($doctor_number_where)->count();
		$this->assign('doctor_number',$doctor_number);
		/*查询专家数量*/
		$expert_number_where['group_ids'] = 5;
		$expert_number = Db::table('ym_user')->where($expert_number_where)->count();
		$this->assign('expert_number',$expert_number);
		/*查询文章数量*/
		$news_number_where['channel_id'] = 21;
		$news_number = Db::table('ym_content')->where($news_number_where)->count();
		$this->assign('news_number',$news_number);
		/*查询视频数量*/
		$video_number_where['channel_id'] = 25;
		$video_number = Db::table('ym_content')->where($video_number_where)->count();
		$this->assign('video_number',$video_number);
		/*一周新增动态*/
		$week_number = array();
		for($i=1;$i<6;$i++){
			$beginday	= mktime(0,0,0,date('m'),date('d')-$i,date('Y'));/*开始时间*/
			$endday		= mktime(0,0,0,date('m'),date('d'),date('Y'))-$i;/*结束时间*/
			$week_where1['addtime'] = array('>',$beginday);
			$week_where2['addtime'] = array('<',$endday);
			/*查询文章数量*/
			$news_where['channel_id'] = 21;
			$news_week = Db::table('ym_content')->where($news_where)->where($week_where1)->where($week_where2)->count();
			$week_number[$i]['time'] 	= date('Y-m-d',$beginday+3600);
			$week_number[$i]['news_number']  = $news_week;
			/*查询视频数量*/
			$video_where['channel_id'] = 25;
			$video_week = Db::table('ym_content')->where($video_where)->where($week_where1)->where($week_where2)->count();
			$week_number[$i]['video_number']  = $video_week;
			/*送药订单*/
			$outorder_where['type'] = 1;
			$outorder_week = Db::table('ym_order')->where($outorder_where)->where($week_where1)->where($week_where2)->count();
			$week_number[$i]['outorder_number']  = $outorder_week;	
			/*进药订单*/
			$inorder_where['type'] = 2;
			$inorder_week = Db::table('ym_order')->where($inorder_where)->where($week_where1)->where($week_where2)->count();
			$week_number[$i]['inorder_number']  = $inorder_week;			
		}
		$this->assign('week_number',$week_number);
		/*分配变量*/
		$this->assign('title','管理首页');/*页面标题*/
		$this->assign('keywords','管理首页');/*页面关键词*/
		$this->assign('description','管理首页');/*页面描述*/
	}
}