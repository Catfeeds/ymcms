<?php
namespace app\web\controller\tztx;
use think\Controller;
use think\Request;
use think\Db;
use app\common\lib\Upload;
/*欢迎页面*/
class Index extends Controller {

	/*首页*/
	public function index(){

		//var_dump($this->m_index);
		/*读取日志*/
		//$log = new \loginfo\Log();/*实例化日志类*/
		//$loglist = $log->readLog('login');/*读取日志*/
		//dump($loglist);
		/*查询用户数量*/
		$user_number_where['status'] = array('neq', 9);
		$user_number = Db::table('ym_wine_users')->where($user_number_where)->count();
		$this->assign('user_number',$user_number);
		/*查询商户数量*/
		$doctor_number_where['status'] = array('neq', 9);
		$doctor_number_where['admin'] = array('eq', 0);
		$doctor_number_where['site_ids'] = array('eq', 5);
		$doctor_number = Db::table('ym_user')->where($doctor_number_where)->count();
		$this->assign('doctor_number',$doctor_number);
		/*查询订单数量*/
		$expert_number_where['g.status'] = array('neq', 9);
		$expert_number = Db::table('ym_wine_shop_order_goods')->alias('g')->join('wine_users u', 'g.uid = u.id')->where($expert_number_where)->count();
		$this->assign('expert_number',$expert_number);
		/*查询商品数量*/
		$news_number_where['status'] = array('neq', 9);
		$news_number = Db::table('ym_wine_shop_goods')->where($news_number_where)->count();
		$this->assign('news_number',$news_number);
		/*查询管理数量*/
		$goods_number_where['status'] = array('neq', 9);
		$goods_number_where['site_ids'] = array('eq', 5);
		$goods_number_where['admin'] = array('eq', 1);
		$goods_number = Db::table('ym_user')->where($goods_number_where)->count();
		$this->assign('goods_number',$goods_number);
		/*一周新增动态*/
		$week_number = array();
		for($i=1;$i<6;$i++){
			$beginday	= mktime(0,0,0,date('m'),date('d')-$i,date('Y'));/*开始时间*/
			$endday		= mktime(0,0,0,date('m'),date('d')-$i+1,date('Y'));/*结束时间*/
			$week_where1['addtime'] = array('>',$beginday);
			$week_where2['addtime'] = array('<',$endday);
			/*查询订单数量*/
			// $news_where['channel_id'] = 21;
			$week_where3['g.addtime'] = array('>',$beginday);
			$week_where4['g.addtime'] = array('<',$endday);
			$news_week = Db::table('ym_wine_shop_order_goods')->alias('g')->join('wine_users u', 'g.uid = u.id')->where($expert_number_where)->where($week_where3)->where($week_where4)->count();
			$week_number[$i]['time'] 	= date('Y-m-d',$beginday+3600);
			$week_number[$i]['news_number']  = $news_week;
			/*查询商品数量*/
			// $video_where['channel_id'] = 25;
			$video_week = Db::table('ym_wine_shop_goods')->where($news_number_where)->where($week_where1)->where($week_where2)->count();
			$week_number[$i]['video_number']  = $video_week;
			/*送药订单*/
			// $outorder_where['type'] = 1;
			$outorder_week = Db::table('ym_wine_users')->where($user_number_where)->where($week_where1)->where($week_where2)->count();
			$week_number[$i]['outorder_number']  = $outorder_week;	
			/*进药订单*/
			// $inorder_where['type'] = 2;
			$inorder_week = Db::table('ym_user')->where($doctor_number_where)->where($week_where1)->where($week_where2)->count();
			$week_number[$i]['inorder_number']  = $inorder_week;			
		}
		$this->assign('week_number',$week_number);
		/*分配变量*/
		$this->assign('title','管理首页');/*页面标题*/
		$this->assign('keywords','管理首页');/*页面关键词*/
		$this->assign('description','管理首页');/*页面描述*/
		/*调用模板*/
		return $this->fetch(TEMP_FETCH);
	}
}