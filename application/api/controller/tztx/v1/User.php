<?php
namespace app\api\controller\tztx\v1;
use app\api\controller\Common;
use think\Controller;
use app\common\lib\exception\ApiException;
use app\common\lib\IAuth;
use think\Db;
/*用户端*/
class User extends Common{

	public function test()
	{
		var_dump(getDistance(input('a'), input('b'), input('c'), input('d')));
	}
    /**
     * 首页弃用
     */
    public function index_s(){
		/*获取参数*/
		$coords = !empty(input('coords'))?input('coords'):'28.101888,112.988175';
		$coords_arr = explode(',',$coords);
		$log = $coords_arr[1];/*经度*/
		$lat = $coords_arr[0];/*纬度*/
		/*根据经纬度获取城市名称*/
		$address = getcity($coords);
		if ($address['status'] != strtoupper('OK')) {
			return show(config('code.error'),'百度地图API请求失败',$address);
		}
		/*删除市*/
		$city = str_replace('市', '', $address['result']['addressComponent']['city']);
		/*条件*/
		$city_where['name'] = $city;
		/*城市ID查询*/
		$cityid = Db::table('ym_areacode')->field('id')->where($city_where)->find();
		if (empty($cityid)) {
			return show(config('code.error'),'当前经纬度暂未查询到城市',array());
		}
		/*条件*/
		$list_where['status'] = array('eq', '1');
		$list_where['city'] = array('eq', $cityid['id']);
		$list_where['group_ids'] = array('eq', '15');
		$field = 'id,name,picture,address,coords';
		$search = input('search');/*搜索*/
		if(!empty($search)) {
			$list_where['name'] = array('like','%'.$search.'%');
		}
		/*商户查询*/
		$total = Db::table('ym_user')->where($list_where)->count();
		$list = Db::table('ym_user')->field($field)->where($list_where)->select();
		$lists = array();
		foreach($list as $k => $v){
			$lists[$k] = $v;
			/*计算用户与商户的距离*/
			$shop_coords = explode(',',$v['coords']);
			if(empty($shop_coords[0])|| empty($shop_coords[1])){
				$lists[$k]['distance'] = "999999991";//'未知诊所坐标';
			}else if(empty($log)|| empty($lat)){
				$lists[$k]['distance'] = "999999992";//'未知用户坐标';
			}else{
				$lists[$k]['distance'] = getDistance($log,$lat,$shop_coords[1],$shop_coords[0]);
			}
		}
		if (empty($list)) {
			return show(config('code.error'),'ok，暂无数据',array());
		} else {
			$lists = sort_array($lists,'distance','asc');
			$result_data = array(
				'total' => $total,
				'list'	=> $lists
			);
			return show(config('code.success'),'ok',$result_data);
		}
	}
	/**
	 * 	首页
	 * @return [type] [description]
	 */
	public function index(){
	    /*POST请求方式校验*/
	    if(!request()->isPost()){
	        return show(config('code.error'),'您没有权限','');
	    }
	    /*获取用户的坐标*/
	    $lng=input('lng');
	    $lat=input('lat');
	    if(empty($lng) || empty($lat)){
	        return show(config('code.error'),'坐标不完整','');
	    }
	    /*搜索*/
	    $search = input('search');
		if(!empty($search)) {
			$where_list['nickname'] = array('like','%'.$search.'%');
		}
	    /*数量*/
	    $limit=input('limit');
	    if(empty($limit)){
	        $limit=10;
	    }
	    /*页数*/
	    $sline=input('sline');
	    if(empty($sline)){
	        $sline=1;
	    }
	    $paging=$sline*$limit-$limit;
	    $field="id,nickname,lng,lat,address,picture,province,city,area,ROUND( 6378.138 * 2 * ASIN( SQRT( POW( SIN( ( ".$lat." * PI() / 180 - lat * PI() / 180 ) / 2 ),  2  ) + COS(".$lat." * PI() / 180) * COS(lat * PI() / 180) * POW(  SIN(  ( ".$lng." * PI() / 180 - lng * PI() / 180 ) / 2 ),  2 ) ) ) * 1000 ) AS distance_um";
	    $where_list['status'] = array('eq', 1);
	    $where_list['admin'] = array('eq', 0);
	    $where_list['id'] = array('neq', 58);/*总店*/
	    $where_list['site_ids'] = array('eq', $this->site_id);
	    $list = Db::name('user')->field($field)->where($where_list)->limit($paging,$limit)->order('distance_um asc')->select();
	    /*计算需要的参数*/
	    $give = Db::name('site')->where('id', $this->site_id)->field('give,range,business_start,business_end')->find();
	    // var_dump($give);die();
	    $lists=array();
	    foreach ($list as $k=>$v){
	    	$lists[$k] = $v;
	        /*门店名称，门店图片，门店地址，门店经度，门店纬度，门店距离*/
	        $lists[$k]['distance']=$v['distance_um']/1000;/*门店距离 单位：千米*/
	        $lists[$k]['range']=($v['distance_um']<$give['range'])?1:2;/*是否配送 1.配送 2.不能立即送和预约送*/
	        $lists[$k]['arrival_time']=$v['distance_um']/1000*$give['give'];/*到货时间*/
	        $city= Db::name('areacode')->field('name')->where('id',$v['province'])->find();
	        $lists[$k]['province']=$city['name'];/*门店地址：省*/
	        $city= Db::name('areacode')->field('name')->where('id',$v['city'])->find();
	        $lists[$k]['city']=$city['name'];/*门店地址：市*/
	        $area= Db::name('areacode')->field('name')->where('id',$v['area'])->find();
	        $lists[$k]['area']=$area['name'];/*门店地址：区*/
	    }
	    /*营业时间*/
	    $shop_info['business_start'] = date('G:i', $give['business_start']);
	    $shop_info['business_end'] = date('G:i', $give['business_end']);
	    $result_data = array(
	    	'shop_info' => $shop_info,
	    	'list' => $lists
	    	);
	    if(!empty($list)){
	        return show(config('code.success'),'ok',$result_data);
	    }else{
	        return show(config('code.success'),'ok',array());
	    }
	}

    /*系统消息列表*/
    public function message() {
        /*查询系统消息*/
        /*获取参数*/
        $search = input('search');
        if (!empty($search)) {
            $where_list['concat(name)'] = array('like', '%' . $search . '%'); /*搜索*/
        }
		$sline = !empty(input('sline'))?input('sline'):0;
		$limit = !empty(input('limit'))?input('limit'):config('default_limit');/*每页条数*/
		/*条数*/
		$limit = $sline.','.$limit;
        /*条件*/
        $where_list['status'] = array('eq', 1);
        $where_list['type'] = array('eq', 1);
        /*查询*/
        $field = 'id,name,ico,picture,description,content,edittime';
        /*查询*/
		$total = Db::name('wine_message')->where($where_list)->count();
        $list = Db::name('wine_message')->field($field)->where($where_list)->order('id desc')->limit($limit)->select();
        $lists = array();
        foreach ($list as $k => $v) {
        	$lists[$k] = $v;
        	$lists[$k]['edittime'] = date('Y-m-d H:i:s',$v['edittime']);
        }
        if($list){
				/*处理结果集*/
			$result_data = array(
				'total' => $total,
				'list'	=> $lists
			);
			return show(config('code.success'),'ok',$result_data);
		}else{
			return show(config('code.error'),'error',array());
		}
    }

    /**
     * 系统消息详情
     */
    public function messagedetail(){
		/*ID*/
		$msgid = input('msgid');
		if(empty($msgid)){
			return show(config('code.error'),'参数缺失',array());
		}
		/*查询*/
        $field = 'id,name,ico,picture,description,content,edittime';
		$result = Db::name('wine_message')->field($field)->where('id', $msgid)->find();
		if($result){
			return show(config('code.success'),'ok',$result);
		}else{
			return show(config('code.error'),'error',array());
		}	
	}

	/**
	 * 轮播图
	 */
	public function advlist(){
	    $field = 'id,advname,thumb,link';
	    $result = Db::name('wine_adv')->field($field)->where('enabled',1)->limit(10)->order('displayorder')->select();
	    $lists = array();
	    foreach ($result as $k => $v) {
	    	$data = $v;
	    	$data['id'] = $v['link'];
	    	$lists[$k] = $data;
	    }
	    if($result){
	        return show(config('code.success'),'ok',$lists);
	    }else{
	        return show(config('code.error'),'error',array());
	    }
	}

	/**
	 * 精选商品
	 */
	public function choice()
	{
		$field = 'id,name,picture,price_original,price';
		$where['choice'] = 1;
		$where['status'] = 1;
		$limit = !empty(input('limit'))?input('limit'):6;/*每页条数*/
	    $list = Db::name('wine_shop_goods')->field($field)->where($where)->limit($limit)->order('rand()')->select();
	    $lists = array();
	    foreach ($list as $k => $v) {
	    	$lists[$k] = $v;
	    	// $lists[$k]['picture'] = 'http://'.$_SERVER['HTTP_HOST'].$v['picture'];
	    }
	    if($list){
	        return show(config('code.success'),'ok',$lists);
	    }else{
	        return show(config('code.error'),'error',array());
	    }
	}

	/**
	 * 获取顶级分类
	 */
	public function classify()
	{
		$field = 'id,path,name,ico';
		$where['pid'] = 0;
		$where['status'] = 1;
		$order = '`order` asc';
	    $list = Db::name('wine_shop_cate')->field($field)->where($where)->order($order)->select();
	    $lists = array();
	    foreach ($list as $k => $v) {
	    	$lists[$k] = $v;
	    	// $lists[$k]['ico'] = 'http://'.$_SERVER['HTTP_HOST'].$v['ico'];
	    }
	    if($list){
	        return show(config('code.success'),'ok',$lists);
	    }else{
	        return show(config('code.error'),'error',array());
	    }
	}

	/**
	 * 活动列表
	 */
	public function activity()
	{
		$field = 'id,name,ico';
		$where['status'] = 1;
		$order = '`order` asc';
	    $list = Db::name('wine_activity')->field($field)->where($where)->order($order)->select();
	    $lists = array();
	    foreach ($list as $k => $v) {
	    	$lists[$k] = $v;
	    	// $lists[$k]['ico'] = 'http://'.$_SERVER['HTTP_HOST'].$v['ico'];
	    }
	    if($list){
	        return show(config('code.success'),'ok',$lists);
	    }else{
	        return show(config('code.error'),'error',array());
	    }
	}

	/**
	 * 活动详情
	 */
	public function activity_detail()
	{
		$act_id = input('act_id');
		if(empty($act_id)){
	        return show(config('code.error'),'参数缺失',array());
	    }
	    /*固定字段*/
		$field = 'id,name,description,content,open_start,open_end';
		$where['status'] = 1;
		$where['id'] = $act_id;
		/*查询*/
	    $list = Db::name('wine_activity')->field($field)->where($where)->find();
	    /*时间*/
	    $list['open_start'] = date('Y-m-d',$list['open_start']);
	    $list['open_end'] = date('Y-m-d',$list['open_end']);
	    if($list){
	        return show(config('code.success'),'ok',$list);
	    }else{
	        return show(config('code.error'),'error',array());
	    }
	}

	/**
	 * 优惠卷
	 */
	public function coupon()
	{
		/*获取参数*/	
		$sline = !empty(input('sline'))?input('sline'):0;
		$limit = !empty(input('limit'))?input('limit'):config('default_limit');
		/*条数*/
		$limit = $sline.','.$limit;
		/*字段*/
		$field = 'id,title,type,minimum,favourable,favourtype,day,daystart,dayend';
		/*条件*/
		$where['status'] = 1;
		$where['centrality'] = 1;
		/*排序*/
		$order = '`order` asc';
		/*查询*/
	    $list = Db::name('wine_activity_coupon')->field($field)->where($where)->order($order)->limit($limit)->select();
	    $lists = array();
	    foreach ($list as $k => $v) {
	    	$data['id'] = $v['id'];
	    	$data['title'] = $v['title'];
	    	switch ($v['type']) {
	    		case 1:
	    			$data['type'] = 1;
	    			$data['type_name'] = '满减卷';
	    			$data['minimum'] = '满'.$v['minimum'].'可用';
	    			$data['favourable'] = $v['favourable'];
	    			break;
	    		case 2:
	    			$data['type'] = 2;
	    			$data['type_name'] = '通用卷';
	    			$data['minimum'] = '无门槛卷';
	    			$data['favourable'] = $v['favourable'];
	    			break;
	    		case 3:
	    			$data['type'] = 3;
	    			$data['type_name'] = '首单优惠';
	    			$data['minimum'] = '新用户可用';
	    			$data['favourable'] = $v['favourable'];
	    			break;
	    	}
	    	if ($v['favourtype'] == 1) {
	    		$data['favourtime'] = '领取后'.$v['day'].'天内有效';
	    	} else {
	    		$data['favourtime'] = '有效期至'.date('Y-m-d', $v['daystart']);
	    	}
	    	$lists[$k] = $data;
	    }
	    if($list){
	        return show(config('code.success'),'ok',$lists);
	    }else{
	        return show(config('code.error'),'error',array());
	    }
	}

	/**
	 * 秒杀
	 */
	public function seckill()
	{
		/*开放时间查询*/
        $time = Db::name('wine_seckill_time')->field('open')->select();
        $total = Db::name('wine_seckill_time')->count();
        $lists = array();
	    foreach ($time as $k => $v) {
			/*条件*/
	        $where_list['s.open_time'] = array('eq', $v['open']);//当天时间段
	        $open_date = strtotime(date('Y-m-d',time()));
	        $where_list['s.open_date'] = array('eq', $open_date);//开放日期
	        $where_list['s.status'] = array('eq', 1);//状态
			/*字段*/
			$field = 's.id,g.name,s.open_time,g.picture,s.price,s.price_seckill,s.total';
			/*排序*/
			$order = 's.open_time asc';
			/*查询*/
			$list = Db::name('wine_seckill')
	            ->alias('s')
	            ->join('wine_shop_goods g','s.gid = g.id')
	            ->where($where_list)
	            ->field($field)
	            ->order($order)
	            ->select();
	        /*秒杀库存*/
	        $res = array();
	        foreach ($list as $kk => $vv) {
	        	$vv['price'] = sprintf("%.2f",$vv['price']);
	        	$res[$kk] = $vv;
	        }
	    	$lists[$k] = $v;
	    	$lists[$k]['list'] = $res;
	    }
	    if($time){
	    	$result = array(
	    		'total' => $total,
	    		'list' => $lists
	    	);
	        return show(config('code.success'),'ok',$result);
	    }else{
	        return show(config('code.error'),'error','暂无秒杀');
	    }
	}

	/**
	 * 分类1
	 */
	public function get_classify()
	{
		/*所属分类*/
		$where_category['status'] = 1;
		$where_category['pid'] = 0;
		/*排序*/
		$order = 'id asc';
		/*字段*/
		$field = 'id,name,ico,picture';
		$list = Db::name('wine_shop_cate')->field($field)->where($where_category)->order($order)->select();
		$lists = array();
		$fields = 'id,name,ico';
		foreach ($list as $k => $v) {
			$lists[$k] = $v;
			$where['pid'] = $v['id'];
			$re = Db::name('wine_shop_cate')->field($fields)->where($where)->select();
			$lists[$k]['list'] = $re;
		}
		return show(config('code.success'),'ok',$lists);
	}

	/**
	 * 分类1-商品列表
	 */
	public function get_classifylist()
	{
		//分类ID
		$classid = input('classid');
		if(empty($classid)){
			return show(config('code.error'),'参数缺失',array());
		}
		/*分类条件*/
		$where_cate['pid'] = array('eq', $classid);
		$cate = Db::name('wine_shop_cate')->where($where_cate)->select();
		if ($cate) {
			$cate_str = $classid;
			foreach ($cate as $v_cate) {
				$cate_str.= ','.$v_cate['id'];
			}
			$where['cate_id'] = array('in', $cate_str);
		} else {
			$where['cate_id'] = array('eq', $classid);
		}
		/*价格筛选*/
		$money_start = input('money_start');
		$money_end = input('money_end');
		if (!empty($money_start)) {
			$where['price'] = array('egt', $money_start);
		}
		if (!empty($money_end)) {
			$where_goods['price'] = array('lt', $money_end);
		}
		/*搜索*/
		$search = input('search');
		if (!empty($search)) {
			$where['name'] = array('like', '%'.$search.'%');
		}
		/*筛选条件*/
		$screen_xx = input('screen_xx');
		if (!empty($screen_xx)) {
			$where['screen_xx'] = array('eq', $screen_xx);
		}
		$screen_pp = input('screen_pp');
		if (!empty($screen_pp)) {
			$where['screen_pp'] = array('eq', $screen_pp);
		}
		$screen_lb = input('screen_lb');
		if (!empty($screen_lb)) {
			$where['screen_lb'] = array('eq', $screen_lb);
		}
		$screen_cd = input('screen_cd');
		if (!empty($screen_cd)) {
			$where['screen_cd'] = array('eq', $screen_cd);
		}
		$screen_zl = input('screen_zl');
		if (!empty($screen_zl)) {
			$where['screen_zl'] = array('eq', $screen_zl);
		}
		/*获取参数*/
		$sline = !empty(input('sline'))?input('sline'):0;
		$limit = !empty(input('limit'))?input('limit'):config('default_limit');
		/*条数*/
		$limit = $sline.','.$limit;
		/*字段*/
		$field = 'id,name,picture,price_original,price';
		/*条件*/
		$where['status'] = array('eq', 1);
		$where_goods['status'] = array('eq', 1);
		/*排序 综合 销量 价格*/
		$order = '`order` desc';
		$sales = input('sales');
		if (!empty($sales)) {
			if ($sales == 1) {
				$order = '`sales` desc';
			} else {
				$order = '`sales` asc';
			}
		}
		$price = input('price');
		if (!empty($price)) {
			if ($price == 1) {
				$order = '`price` desc';
			} else {
				$order = '`price` asc';
			}
		}
		/*查询*/
	    $list = Db::name('wine_shop_goods')->field($field)->where($where)->where($where_goods)->order($order)->limit($limit)->select();
	    $lists = array();
	    foreach ($list as $k => $v) {
	    	$lists[$k] = $v;
	    }
	    if($list){
	        return show(config('code.success'),'ok',$lists);
	    }else{
	        return show(config('code.error'),'error',array());
	    }
	}

	/**
	 * 分类筛选条件
	 * 白酒,红酒,啤酒,茶叶,洋酒
	 * 香型,品牌,类别,产地,种类
	 */
	public function get_screen()
	{
		$screenid = input('screenid');//分类筛选条件ID
		if(empty($screenid)){
			return show(config('code.error'),'参数缺失',array());
		}
		/*商品类别*/
		switch ($screenid) {
			case '1':
				$where['type'] = array('in', '1,2');
				break;
			case '3':
				$where['type'] = array('in', '3,4');
				break;
			case '7':
				$where['type'] = array('in', '4,5');
				break;
			case '8':
				$where['type'] = array('in', '4,5');
				break;
			case '9':
				$where['type'] = array('in', '4,5');
				break;
			default:
				return  show(config('code.success'),'success',array());
				break;
		}
		$list = Db::name('wine_screen')->field('id,name,type')->where($where)->order('type asc')->select();
		$lists = array();
		foreach ($list as $k => $v) {
			/*筛选类型*/
			switch ($v['type']) {
				case '1':
					$lists['screen_xx'][] = $v;
					break;
				case '2':
					$lists['screen_pp'][] = $v;
					break;
				case '3':
					$lists['screen_lb'][] = $v;
					break;
				case '4':
					$lists['screen_cd'][] = $v;
					break;
				case '5':
					$lists['screen_zl'][] = $v;
					break;
			}
		}
		switch ($screenid) {
			case '1':
				$data[] = array(
					'name' => '香型',
					'screen' => $lists['screen_xx'],
					'class' => 'screen_xx'
					);
				$data[] = array(
					'name' => '品牌',
					'screen' => $lists['screen_pp'],
					'class' => 'screen_pp'
					);
				break;
			case '3':
				$data[] = array(
					'name' => '类别',
					'screen' => $lists['screen_lb'],
					'class' => 'screen_lb'
					);
				$data[] = array(
					'name' => '产地',
					'screen' => $lists['screen_cd'],
					'class' => 'screen_cd'
					);
				break;
			case '7':
				$data[] = array(
					'name' => '产地',
					'screen' => $lists['screen_cd'],
					'class' => 'screen_cd'
					);
				$data[] = array(
					'name' => '种类',
					'screen' => $lists['screen_zl'],
					'class' => 'screen_zl'
					);
				break;
			case '8':
				$data[] = array(
					'name' => '产地',
					'screen' => $lists['screen_cd'],
					'class' => 'screen_cd'
					);
				$data[] = array(
					'name' => '种类',
					'screen' => $lists['screen_zl'],
					'class' => 'screen_zl'
					);
				break;
			case '9':
				$data[] = array(
					'name' => '产地',
					'screen' => $lists['screen_cd'],
					'class' => 'screen_cd'
					);
				$data[] = array(
					'name' => '种类',
					'screen' => $lists['screen_zl'],
					'class' => 'screen_zl'
					);
				break;
		}
		if($list){
	        return show(config('code.success'),'ok',$data);
	    }else{
	        return show(config('code.error'),'error',array());
	    }
	}

	/**
	 * 搜索
	 * @return [type] [description]
	 */
	public function search()
	{
		/*搜索*/
		$search = input('search');
		if (!empty($search)) {
			$where['name'] = array('like', '%'.$search.'%');
		}
		/*获取参数*/
		$sline = !empty(input('sline'))?input('sline'):0;
		$limit = !empty(input('limit'))?input('limit'):config('default_limit');
		/*条数*/
		$limit = $sline.','.$limit;
		/*字段*/
		$field = 'id,name,picture,price_original,price';
		/*条件*/
		$where['status'] = array('eq', 1);
		$where_goods['status'] = array('eq', 1);
		/*排序 综合 销量 价格*/
		$order = '`order` desc';
		$sales = input('sales');
		if (!empty($sales)) {
			if ($sales == 1) {
				$order = '`sales` desc';
			} else {
				$order = '`sales` asc';
			}
		}
		$price = input('price');
		if (!empty($price)) {
			if ($price== 1) {
				$order = '`price` desc';
			} else {
				$order = '`price` asc';
			}
		}
		/*价格筛选*/
		$money_start = input('money_start');
		$money_end = input('money_end');
		if (!empty($money_start)) {
			$where['price'] = array('egt', $money_start);
		}
		if (!empty($money_end)) {
			$where_goods['price'] = array('lt', $money_end);
		}
		/*查询*/
		$list = Db::name('wine_shop_goods')->field($field)->where($where)->where($where_goods)->order($order)->limit($limit)->select();
	    $lists = array();
	    foreach ($list as $k => $v) {
	    	$lists[$k] = $v;
	    }
	    if($list){
	        return show(config('code.success'),'ok',$lists);
	    }else{
	        return show(config('code.error'),'error',array());
	    }
	}

	/**
	 * 商品详情
	 * @return [type] [description]
	 */
	public function goods_detail()
	{
		/*获取参数*/
		$goods_id = input('goods_id');
		$user_gid = input('user_gid');
		if (empty($goods_id)||empty($user_gid)) {
			return show(config('code.error'),'参数缺失',array());
		}
		/*固定字段*/
		$field = 'id,name,ico,price_original,price,total,sales,content,status';
		$fields = 'id,nickname,picture,content,m_content,addtime';
		/*查询*/
		$list = Db::name('wine_shop_goods')->field($field)->where('id', $goods_id)->find();
		$user_info = Db::name('user')->field('id,nickname,picture,phone')->where('id', $user_gid)->find();
		/*商户信息*/
		$phone = json_decode($user_info['phone'],true);
		unset($user_info['phone']);
		$user_info['phone'] = $phone['name'];
		$lists['user'] = $user_info;
		/*条件*/
		$where['goodsid'] = array('eq', $goods_id);
		foreach ($list as $k => $v) {
			$lists[$k] = $v;
			$info = Db::name('wine_shop_comment')->field($fields)->where($where)->where('status',1)->select();
			$lists['list'] = $info;
			foreach ($info as $kk => $vv) {
				$lists['list'][$kk]['addtime'] = date('Y-m-d', $vv['addtime']);
			}
		}
		if($list){
	        return show(config('code.success'),'ok',$lists);
	    }else{
	        return show(config('code.error'),'error',array());
	    }
	}

	/**
	 * 联系我们
	 */
	public function touch()
	{
		$touch = Db::name('site')->where('id', $this->site_id)->field('tel')->find();
		if($touch){
	        return show(config('code.success'),'ok',$touch['tel']);
	    }else{
	        return show(config('code.error'),'error',array());
	    }
	}

	/**
	 * 联系我们
	 */
	public function baidu()
	{
		$touch = Db::name('site')->where('id', $this->site_id)->field('baidu')->find();
		if($touch){
	        return show(config('code.success'),'ok',$touch['baidu']);
	    }else{
	        return show(config('code.error'),'error',array());
	    }
	}

	/**
	 * 秒杀商品详情
	 * @return [type] [description]
	 */
	public function seckill_detail()
	{
		/*获取参数*/
		$seckill = input('seckill');
		$shop_id = input('shop_id');
		if (empty($seckill)||empty($shop_id)) {
			return show(config('code.error'),'参数缺失',array());
		}
		/*条件*/
		$where_seckill['s.id'] = array('eq', $seckill);
		/*固定字段*/
		$field_seckill = 's.id,g.name,g.ico,s.price_seckill,s.price,s.total,s.seckill_num,g.content,s.open_time,g.id as goodsid,s.max_number,g.status';
		/*查询*/
		$list = Db::name('wine_seckill')
						->alias('s')
						->join('wine_shop_goods g', 's.gid = g.id')
						->field($field_seckill)
						->where($where_seckill)
						->find();
		/*规范小数点*/
		$list['price_seckill'] = sprintf("%.2f",$list['price_seckill']);
		$list['price'] = sprintf("%.2f",$list['price']);
		$lists = $list;
		/*商户信息*/
		$user_info = Db::name('user')->field('id,nickname,picture,phone')->where('id', $shop_id)->find();
		$phone = json_decode($user_info['phone'],true);
		unset($user_info['phone']);
		$user_info['phone'] = $phone['name'];
		$lists['user'] = $user_info;
		/*固定字段*/
		$fields = 'id,nickname,picture,content,m_content,addtime';
		/*条件*/
		$where['goodsid'] = array('eq',$list['goodsid']);
		$info = Db::name('wine_shop_comment')->field($fields)->where($where)->where('status',1)->select();
		$lists['list'] = $info;
		foreach ($info as $kk => $vv) {
			$lists['list'][$kk]['addtime'] = date('Y-m-d', $vv['addtime']);
		}
		if($list){
	        return show(config('code.success'),'ok',$lists);
	    }else{
	        return show(config('code.error'),'error',array());
	    }
	}
	


















}