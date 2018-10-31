<?php
namespace app\api\controller\medical\v1;
use app\api\controller\Common;
use think\Controller;
use app\common\lib\exception\ApiException;
use app\common\lib\Aes;
use app\common\lib\IAuth;
use app\common\lib\Alisms;
use think\Db;
/*医生端-登录*/
class Doctauth extends Auth{

    /**
     * 初始化
     */
    public function _initialize(){
		/*继承父类构造方法*/
        parent::_initialize();
		/*验证用户类型是否为医生*/
		$user = Db::name('user')->field('group_ids')->where('id='.$this->user['id'])->find();
        if(!empty($user)) {
			if($user['group_ids'] != 4){
				throw new ApiException('身份类型不匹配');/*医生*/
			}
		}else{
			return show(config('code.error'),'账号信息缺失',array());
		}			
    }

    /**
     * 个人中心-获取用户信息
     */
    public function read(){
		/*非常隐私、需要加密处理
        $obj = new Aes();
        return show(config('code.success'),'ok',$obj->encrypt($this->user));
    	*/
		/*诊所&学历*/
		$user = Db::name('user')->field('clinic_id,doctor_type,education')->where('id='.$this->user['id'])->find();
		$this->user['doctor_type'] = $user['doctor_type'];
		$this->user['education'] = $user['education'];
		$clinic = Db::name('clinic')->field('name')->where('id='.$user['clinic_id'])->find();
		$this->user['clinic_name'] = $clinic['name'];
		return show(config('code.success'),'ok',$this->user);
	}

    /**
     * 个人中心-患者预约
     */
    public function subscribe(){
		/*医生ID*/
		$id = $this->user['id'];
		/*获取参数*/
		$sline = !empty(input('sline'))?input('sline'):0;
		$limit = !empty(input('limit'))?input('limit'):config('default_limit');
		/*查询聊天名师*/
		$where_list['site_id'] = $this->site_id;/*站点ID*/
		$where_list['user_to_id'] = $id;/*医生ID*/
		$where_list['status'] = array('notin',6);/*医生ID*/
		/*排序*/	
		$order = 'time desc,id desc';
		/*条数*/
		$limit = $sline.','.$limit;
		/*字段*/
		$field = 'id,user_id,user_to_id,time,addtime,status,name,sex,age,content,memo';
		/*查询聊天名师*/
		$t_start = input('t_start');/*开始时间*/
		$t_start_data = $t_start;
		$t_end = input('t_end');/*结束时间*/
		$t_end_data = $t_end;		
		$where_list2 = array();
		if(!empty($t_start_data) && empty($t_end_data)){
			$where_list['addtime'] = array('>=',$t_start_data);
		}else if(!empty($t_end_data) && empty($t_start_data)){
			$where_list['addtime'] = array('<=',$t_end_data);
		}else if(!empty($t_end_data) && !empty($t_end_data)){
			$where_list['addtime'] = array('>=',$t_start_data);
			$where_list2['addtime'] = array('<=',$t_end_data);
		}			
		$total = Db::name('subscribe')->where($where_list)->where($where_list2)->count();
		$list = Db::name('subscribe')->field($field)->where($where_list)->where($where_list2)->order($order)->limit($limit)->select();
		$lists = array();
		foreach($list as $k => $v){
			$lists[$k] = $v;
			/*格式化时间*/
			$lists[$k]['time'] = date('Y-m-d',$v['time']);
			$lists[$k]['addtime'] = date('Y-m-d',$v['addtime']);
		}
		/*处理结果集*/
		$result_data = array(
			'total' => $total,
			'list'	=> $lists
		);
		return show(config('code.success'),'ok',$result_data);
	}

    /**
     * 个人中心-预约删除
     */
    public function subscribedel(){
		/*用户ID*/
		$id = input('subscribe_id');
		if(empty($id)){
			return show(config('code.error'),'参数缺失',array());
		}
		$data['status'] = 6;
		$data['edittime'] = time();
		$result = Db::name('subscribe')->where('id='.$id)->update($data);
		if($result){
			return show(config('code.success'),'ok',$data);
		}else{
			return show(config('code.error'),'error',array());
		}	
	}

    /**
     * 个人中心-预约备忘录修改
     */
    public function subscribememo(){
		/*用户ID*/
		$id = input('subscribe_id');
		$memo = input('memo');
		if(empty($id) || empty($memo)){
			return show(config('code.error'),'参数缺失',array());
		}
		$data['memo'] = $memo;
		$data['edittime'] = time();
		$result = Db::name('subscribe')->where('id='.$id)->update($data);
		if($result){
			return show(config('code.success'),'ok',$data);
		}else{
			return show(config('code.error'),'error',array());
		}	
	}

    /**
     * 个人中心-诊所药品库
     */
    public function procat(){
		/*用户ID*/
		$id = $this->user['id'];
		if(empty($id)){
			return show(config('code.error'),'参数缺失',array());
		}		
		$user = Db::name('user')->field('clinic_id')->where('id='.$id)->find();
		/*获取参数*/
		$sline = !empty(input('sline'))?input('sline'):0;
		$limit = !empty(input('limit'))?input('limit'):config('default_limit');
		/*条数*/
		$limit = $sline.','.$limit;		
		/*查询*/
		$where_list['site_id'] = $this->site_id;
		$where_list['clinic_id'] = $user['clinic_id'];		
		$where_list['channel_id'] = 22;
		$where_list['status']  = 1;/*状态*/
		$search = input('search');/*搜索*/
		if(!empty($search)) {
			$where_list['concat(name,description)'] = array('like','%'.$search.'%');
		}			
		/*排序*/	
		$order = '`order` desc,id desc';
		/*字段*/
		$field = 'id,name,description,picture';
		/*查询聊天名师*/
		$total = Db::name('category')->where($where_list)->count();
		$list = Db::name('category')->field($field)->where($where_list)->order($order)->limit($limit)->select();
		$lists = array();
		foreach($list as $k => $v){
			$lists[$k] = $v;
		}
		/*处理结果集*/
		$result_data = array(
			'total' => $total,
			'list'	=> $lists
		);
		return show(config('code.success'),'ok',$result_data);
	}

    /**
     * 个人中心-诊所药品库-产品
     */
    public function procon(){
		/*用户ID*/
		$id = $this->user['id'];
		$category_id = input('category_id');
		if(empty($id) || empty($category_id)){
			return show(config('code.error'),'参数缺失',array());
		}		
		$user = Db::name('user')->field('clinic_id')->where('id='.$id)->find();
		/*获取参数*/
		$sline = !empty(input('sline'))?input('sline'):0;
		$limit = !empty(input('limit'))?input('limit'):config('default_limit');
		/*条数*/
		$limit = $sline.','.$limit;		
		/*查询*/
		$where_list['site_id'] = $this->site_id;
		$where_list['category_id'] = $category_id;		
		$where_list['channel_id'] = 22;
		$where_list['status']  = 1;/*状态*/
		$search = input('search');/*搜索*/
		if(!empty($search)) {
			$where_list['concat(name,description,content)'] = array('like','%'.$search.'%');
		}			
		/*排序*/	
		$order = '`order` desc,id desc';
		/*字段*/
		$field = 'id,name,description,picture,price';
		/*查询聊天名师*/
		$total = Db::name('content')->where($where_list)->count();
		$list = Db::name('content')->field($field)->where($where_list)->order($order)->limit($limit)->select();
		$lists = array();
		foreach($list as $k => $v){
			$lists[$k] = $v;
		}
		/*处理结果集*/
		$result_data = array(
			'total' => $total,
			'list'	=> $lists
		);
		return show(config('code.success'),'ok',$result_data);
	}

    /**
     * 个人中心-诊所药品库-发送给用户
     */
    public function orderout(){
		/*用户ID*/
		$id = $this->user['id'];
		$user_id = input('user_id');
		$user = Db::name('user')->field('clinic_id')->where('id='.$id)->find();
		$goods = input('goods');
		$goods = trim($goods);
		if(empty($id) || empty($user_id) || empty($goods)){
			return show(config('code.error'),'参数缺失',array());
		}
		/*解析订单商品*/
		$goods_arr = json_decode($goods,true);
		if(empty($goods_arr)){
			return show(config('code.error'),'商品参数非法',array());
		}
		/*组装商品信息*/
		$order_title = '';
		$order_price = 0;
		$order_content = array();
		$order_content['type'] = 1;/*线上购物*/
		foreach($goods_arr as $k=>$v){
			$good_id = $v['content_id'];
			$good_num = $v['number'];
			if(empty($good_id) || empty($good_num)){
				return show(config('code.error'),'商品参数结构非法',array());
			}
			/*查询商品*/
			$content = Db::name('content')->field('name,price')->where('id='.$good_id)->find();
			if(!empty($content)){
				$order_title .= ','.$content['name'];
				$order_price = $order_price + ($content['price']*$good_num);
				$order_content['value'][] = array(
					'content_id' => $good_id,
					'number' => $good_num,
					'price' => $content['price']
				);
			}
		}
		$order_content_str = json_encode($order_content);/*订单商品*/
		$order_title = trim($order_title,',');
		/*查询诊所*/
		$clinic = Db::name('clinic')->field('freight,packing')->where('id='.$user['clinic_id'])->find();
		/*配送费*/
		$freight = $clinic['freight'];
		/*打包费*/
		$packing = $clinic['packing'];
		/*订单有效时间*/
		$site = Db::name('site')->field('order_validity')->where('id='.$this->site_id)->find();
		$validity = $site['order_validity'];
		/*生成订单*/
		$now_time = time();
		$data = array(
			'user_id'		=> $user_id,/*用户ID*/
			'site_id'		=> $this->site_id,/*站点ID*/
			'clinic_id'		=> $user['clinic_id'],/*诊所ID*/
			'payment_id'	=> 4,/*支付方式(1:微信，2:支付宝，3:后台付款，4:货到付款)*/
			'title'			=> $order_title,/*标题*/
			'type'			=> 1,/*类型 1:送药订单（用户）|2:进药订单（批发医药公司）*/
			'content'		=> $order_content_str,/*订单详情：内容JONS集([{"content_id":1188,"number":2},{"content_id":1189,"number":1}])*/
			'no'			=> ordernostr(),/*编号*/
			'total_money'	=> $order_price + $freight + $packing,/*总价（价格+配送费+打包费）*/
			'price'			=> $order_price,/*价格*/
			'freight'		=> $freight,/*配送费*/
			'packing'		=> $packing,/*打包费*/
			'validity'		=> $now_time + $validity,/*订单有效期*/
			//'send'			=> '',/*运单JONS集（物流公司ID,其它物流公司时的名称,运单号,发货时间）*/
			//'receiver'		=> '',/*收货人*/
			//'tel'			=> '',/*电话*/
			//'identity'		=> '',/*身份证号码*/
			//'state'			=> '',/*州*/
			//'country'		=> '',/*国*/
			//'province'		=> '',/*省*/
			//'city'			=> '',/*市*/
			//'area'			=> '',/*区*/
			//'address'		=> '',/*地址*/
			//'message'		=> '',/*留言*/
			//'order'			=> 100,/*排序  默认为100*/
			'status'		=> 2,/*JSON集，状态码code（1:未付款|2:已付款|3:配货中|4:已发货|5:已收货|6:退款申请中|7:退款入账中|8:退款成功|9:退款失败|10:已完成|11:已过期|12:已关闭|13:已删除|14:支付金额异常），变更时间(time)，备注(msg)*/
			//'status_leng'	=> '',/*订单处理进度（状态、变更时间、备注）*/
			'addtime'		=> $now_time,/*添加时间*/
			'edittime'		=> $now_time,/*修改时间*/
		);
		$result = Db::name('order')->insert($data);
		$data['id'] = Db::name('order')->getLastInsID();
		if($result){
			return show(config('code.success'),'ok',$data);
		}else{
			return show(config('code.error'),'error',array());
		}
	}


    /**
     * 个人中心-药品下单
     */
    public function proincat(){
		/*用户ID*/
		$id = $this->user['id'];
		if(empty($id)){
			return show(config('code.error'),'参数缺失',array());
		}		
		$user = Db::name('user')->field('clinic_id')->where('id='.$id)->find();
		/*获取参数*/
		$sline = !empty(input('sline'))?input('sline'):0;
		$limit = !empty(input('limit'))?input('limit'):config('default_limit');
		/*条数*/
		$limit = $sline.','.$limit;		
		/*查询*/
		$where_list['site_id'] = $this->site_id;
		$where_list['clinic_id'] = -2;		
		$where_list['channel_id'] = 22;
		$where_list['status']  = 1;/*状态*/
		$search = input('search');/*搜索*/
		if(!empty($search)) {
			$where_list['concat(name,description)'] = array('like','%'.$search.'%');
		}			
		/*排序*/	
		$order = '`order` desc,id desc';
		/*字段*/
		$field = 'id,name,description,picture';
		/*查询聊天名师*/
		$total = Db::name('category')->where($where_list)->count();
		$list = Db::name('category')->field($field)->where($where_list)->order($order)->limit($limit)->select();
		$lists = array();
		foreach($list as $k => $v){
			$lists[$k] = $v;
		}
		/*处理结果集*/
		$result_data = array(
			'total' => $total,
			'list'	=> $lists
		);
		return show(config('code.success'),'ok',$result_data);
	}

    /**
     * 个人中心-药品下单-产品
     */
    public function proincon(){
		/*用户ID*/
		$id = $this->user['id'];
		$category_id = input('category_id');
		if(empty($id) || empty($category_id)){
			return show(config('code.error'),'参数缺失',array());
		}		
		$user = Db::name('user')->field('clinic_id')->where('id='.$id)->find();
		/*获取参数*/
		$sline = !empty(input('sline'))?input('sline'):0;
		$limit = !empty(input('limit'))?input('limit'):config('default_limit');
		/*条数*/
		$limit = $sline.','.$limit;		
		/*查询*/
		$where_list['site_id'] = $this->site_id;
		$where_list['category_id'] = $category_id;		
		$where_list['channel_id'] = 22;
		$where_list['status']  = 1;/*状态*/
		$search = input('search');/*搜索*/
		if(!empty($search)) {
			$where_list['concat(name,description,content)'] = array('like','%'.$search.'%');
		}			
		/*排序*/	
		$order = '`order` desc,id desc';
		/*字段*/
		$field = 'id,name,description,picture,price';
		/*查询聊天名师*/
		$total = Db::name('content')->where($where_list)->count();
		$list = Db::name('content')->field($field)->where($where_list)->order($order)->limit($limit)->select();
		$lists = array();
		foreach($list as $k => $v){
			$lists[$k] = $v;
		}
		/*处理结果集*/
		$result_data = array(
			'total' => $total,
			'list'	=> $lists
		);
		return show(config('code.success'),'ok',$result_data);
	}

    /**
     * 个人中心-药品下单-产品查询(支持批量)
     */
	public function profind(){
		$id = $this->user['id'];/*用户ID*/	
		$ids = input('content_id');/*ID支持批量*/
		if(empty($ids) || empty($id)){
			return show(config('code.error'),'参数缺失',array());
		}
		$ids = trim($ids,',');
		$now_time = time();
		$ids_arr = explode(',',$ids);
		if(!empty($ids_arr)){
			$where['id'] = array('in',$ids);
			$content = Db::name('content')->field('name,price,picture')->where($where)->select();
			if($content){
				return show(config('code.success'),'ok',$content);
			}else{
				return show(config('code.error'),'error',array());
			}		
		}else{
			return show(config('code.error'),'参数格式错误',array());
		}	
	}

    /**
     * 个人中心-药品下单-确认订单
     */
    public function orderin(){
		/*用户ID*/
		$id = $this->user['id'];
		if(empty($id) || empty(input('goods')) || empty(input('receiver')) || empty(input('tel')) || empty(input('address'))){
			return show(config('code.error'),'参数缺失',array());
		}
		$user_id = input('user_id');
		$user = Db::name('user')->field('clinic_id')->where('id='.$id)->find();
		$goods = input('goods');
		$goods = trim($goods);
		$receiver = input('receiver');
		$tel = input('tel');
		$address = input('address');
		$message = input('message');		
		/*解析订单商品*/
		$goods_arr = json_decode($goods,true);
		if(empty($goods_arr)){
			return show(config('code.error'),'商品参数非法',array());
		}
		/*组装商品信息*/
		$order_title = '';
		$order_price = 0;
		$order_content = array();
		$order_content['type'] = 1;/*线上购物*/
		foreach($goods_arr as $k=>$v){
			$good_id = $v['content_id'];
			$good_num = $v['number'];
			if(empty($good_id) || empty($good_num)){
				return show(config('code.error'),'商品参数结构非法',array());
			}
			/*查询商品*/
			$content = Db::name('content')->field('name,price')->where('id='.$good_id)->find();
			if(!empty($content)){
				$order_title .= ','.$content['name'];
				$order_price = $order_price + ($content['price']*$good_num);
				$order_content['value'][] = array(
					'content_id' => $good_id,
					'number' => $good_num,
					'price' => $content['price']
				);
			}
		}
		$order_content_str = json_encode($order_content);/*订单商品*/
		$order_title = trim($order_title,',');
		/*查询诊所*/
		$clinic = Db::name('clinic')->field('freight,packing')->where('id='.$user['clinic_id'])->find();
		/*配送费*/
		$freight = $clinic['freight'];
		/*打包费*/
		$packing = $clinic['packing'];
		/*订单有效时间*/
		$site = Db::name('site')->field('order_validity')->where('id='.$this->site_id)->find();
		$validity = $site['order_validity'];
		/*生成订单*/
		$now_time = time();
		$data = array(
			'user_id'		=> $id,/*用户ID*/
			'site_id'		=> $this->site_id,/*站点ID*/
			'clinic_id'		=> $user['clinic_id'],/*诊所ID*/
			'payment_id'	=> 4,/*支付方式(1:微信，2:支付宝，3:后台付款，4:货到付款)*/
			'title'			=> $order_title,/*标题*/
			'type'			=> 2,/*类型 1:送药订单（用户）|2:进药订单（批发医药公司）*/
			'content'		=> $order_content_str,/*订单详情：内容JONS集([{"content_id":1188,"number":2},{"content_id":1189,"number":1}])*/
			'no'			=> ordernostr(),/*编号*/
			'total_money'	=> $order_price + $freight + $packing,/*总价（价格+配送费+打包费）*/
			'price'			=> $order_price,/*价格*/
			'freight'		=> $freight,/*配送费*/
			'packing'		=> $packing,/*打包费*/
			'validity'		=> $now_time + $validity,/*订单有效期*/
			//'send'			=> '',/*运单JONS集（物流公司ID,其它物流公司时的名称,运单号,发货时间）*/
			'receiver'		=> $receiver,/*收货人*/
			'tel'			=> $tel,/*电话*/
			//'identity'		=> '',/*身份证号码*/
			//'state'			=> '',/*州*/
			//'country'		=> '',/*国*/
			//'province'		=> '',/*省*/
			//'city'			=> '',/*市*/
			//'area'			=> '',/*区*/
			'address'		=> $address,/*地址*/
			//'message'		=> '',/*留言*/
			//'order'			=> 100,/*排序  默认为100*/
			'status'		=> 2,/*JSON集，状态码code（1:未付款|2:已付款|3:配货中|4:已发货|5:已收货|6:退款申请中|7:退款入账中|8:退款成功|9:退款失败|10:已完成|11:已过期|12:已关闭|13:已删除|14:支付金额异常），变更时间(time)，备注(msg)*/
			//'status_leng'	=> '',/*订单处理进度（状态、变更时间、备注）*/
			'addtime'		=> $now_time,/*添加时间*/
			'edittime'		=> $now_time,/*修改时间*/
		);
		if(!empty(input($message))){
			$data['message'] = $message;
		}
		$result = Db::name('order')->insert($data);
		$data['id'] = Db::name('order')->getLastInsID();
		if($result){
			return show(config('code.success'),'ok',$data);
		}else{
			return show(config('code.error'),'error',array());
		}
	}

   /**
     * 个人中心-药品下单-订单列表
     */
    public function orderinlis(){
		/*获取参数*/
		$tab = !empty(input('tab'))?input('tab'):0;/*选项卡*/
		$sline = !empty(input('sline'))?input('sline'):0;
		$limit = !empty(input('limit'))?input('limit'):config('default_limit');
		/*条数*/
		$limit = $sline.','.$limit;		
		/*查询*/
		$where_list['type']	   = 2;//类型 1:送药订单（用户）|2:进药订单（批发医药公司）
		$where_list['user_id'] = $this->user['id'];/*用户ID*/	
		$where_list['site_id'] = $this->site_id;
		$where_list['status']  = array('in','2,3,4,5,10,12');/*状态*/
		$search = input('search');/*搜索*/
		if(!empty($search)) {
			$where_list['concat(name,description)'] = array('like','%'.$search.'%');
		}		
		$sline = !empty(input('sline'))?input('sline'):0;
		$limit = !empty(input('limit'))?input('limit'):config('default_limit');		
		/*查询内容*/
		$where_list['site_id'] = $this->site_id;
		$search = input('search');/*搜索*/
		if(!empty($search)) {
			$where_list['concat(name,description)'] = array('like','%'.$search.'%');
		}		
		/*排序*/	
		$order = 'id desc';
		/*选项卡*/
		if($tab == 1){
			$where_list['status'] = 3;
		}else if($tab == 2){
			$where_list['status'] = 4;
		}else if($tab == 3){
			$where_list['status'] = 10;
		}
		/*字段*/
		$field = 'id,no,title,total_money,status,addtime,content';
		/*查询*/
		$t_start = input('t_start');/*开始时间*/
		$t_start_data = $t_start;
		$t_end = input('t_end');/*结束时间*/
		$t_end_data = $t_end;		
		$where_list2 = array();
		if(!empty($t_start_data) && empty($t_end_data)){
			$where_list['addtime'] = array('>=',$t_start_data);
		}else if(!empty($t_end_data) && empty($t_start_data)){
			$where_list['addtime'] = array('<=',$t_end_data);
		}else if(!empty($t_end_data) && !empty($t_end_data)){
			$where_list['addtime'] = array('>=',$t_start_data);
			$where_list2['addtime'] = array('<=',$t_end_data);
		}
		$total = Db::name('order')->where($where_list)->where($where_list2)->count();
		$list = Db::name('order')->field($field)->where($where_list)->where($where_list2)->order($order)->limit($limit)->select();
		$lists = array();
		foreach($list as $k => $v){
			$lists[$k] = $v;
			/*格式化时间*/
			$lists[$k]['addtime'] = date('Y-m-d',$v['addtime']);
			/*状态*/
			if (!empty($v['status'])) {
				$lists[$k]['status'] = orderStatusToStr($v['status']);
			}		
			/*查询订单商品*/
			$goods_arr = json_decode($v['content'],true);
			if(!empty($goods_arr) && $goods_arr['type'] == 1){
				foreach($goods_arr['value'] as $k2=>$v2){
					$good_array = Db::name('content')->field('name,picture')->where('id='.$v2['content_id'])->find();
					if(!empty($good_array)){
						foreach($good_array as $k3=>$v3){
							$goods_arr['value'][$k2][$k3] = $v3;
						}
					}
				}
			}
			$lists[$k]['goods'] = $goods_arr['value'];
			unset($lists[$k]['content']);
		}
		/*处理结果集*/
		$result_data = array(
			'title' => '我的订单',
			'table' => array('所有订单','待发货','已发货','已完成'),
			'total' => $total,
			'list'	=> $lists
		);
		return show(config('code.success'),'ok',$result_data);
	}

   /**
     * 个人中心-药品下单-订单详情
     */
    public function orderincon(){
		$id = input('order_id');
		if(empty($id)){
			return show(config('code.error'),'参数缺失',array());
		}		
		$where['id'] = $id;
		$field = 'title,content,no,total_money,price,freight,receiver,tel,address,message,status,addtime,status_leng';
		$contents = Db::name('order')->field($field)->where($where)->find();
		foreach ($contents as $k => $v) {
			$content[$k] = $v;
			/*格式化状态*/
			if($k == 'status' && !empty($contents['status'])){
				$content['status'] = orderStatusToStr($content['status']);
			}
			/*订单处理进度*/
			if($k == 'status_leng' && !empty($contents['status_leng'])){
				$content['status_leng'] = json_decode($content['status_leng'],true);
			}
			/*格式化时间*/
			if($k == 'addtime' && !empty($contents['addtime'])){
				$content['addtime'] = date('Y-m-d',$contents['addtime']);
			}			
		}
		/*查询订单商品*/
		$goods_arr = json_decode($content['content'],true);
		if(!empty($goods_arr) && $goods_arr['type'] == 1){
			foreach($goods_arr['value'] as $k=>$v){
				$good_array = Db::name('content')->field('name,picture')->where('id='.$v['content_id'])->find();
				if(!empty($good_array)){
					foreach($good_array as $k2=>$v2){
						$goods_arr['value'][$k][$k2] = $v2;
					}
				}
			}
		}
		$content['goods'] = $goods_arr['value'];
		unset($content['content']);
		return show(config('code.success'),'ok',$content);
	}

   /**
     * 个人中心-药品下单-确认收货
     */
    public function status5(){
		/*接收参数*/
		$id = input('order_id');
		if(empty($id)){
			return show(config('code.error'),'参数缺失',array());
		}		
		$now_time = time();
		$data['status'] = 5;/*确认订单*/
		$data['edittime'] = $now_time;/*时间*/
		$result = Db::name('order')->where('id='.$id)->update($data);
		if($result){
			/*订单处理进度*/
			$data_leng_new = array(
				array(
					'status' 	=> 5,
					'msg'		=> '医生确认收货',
					'time'		=> $now_time
				)
			);
			$order = Db::name('order')->field('status_leng')->where('id='.$id)->find();
			if(!empty($order['status_leng'])){
				$data_leng_old = json_decode($order['status_leng'],true);
				array_push($data_leng_old,$data_leng_new[0]);
				$data_leng_new = $data_leng_old;
			}			
			$data_status['status_leng'] = json_encode($data_leng_new);
			$result_status = Db::name('order')->where('id='.$id)->update($data_status);
			/*订单完成*/
			$now_time2 = time();
			$data2['status'] = 10;/*订单完成*/
			$data2['edittime'] = $now_time2;/*时间*/
			$result2 = Db::name('order')->where('id='.$id)->update($data2);
			/*订单处理进度*/
			$data_leng_new2 = array(
				array(
					'status' 	=> 10,
					'msg'		=> '订单完成',
					'time'		=> $now_time2
				)
			);
			$order2 = Db::name('order')->field('status_leng')->where('id='.$id)->find();
			if(!empty($order2['status_leng'])){
				$data_leng_old2 = json_decode($order2['status_leng'],true);
				array_push($data_leng_old2,$data_leng_new2[0]);
				$data_leng_new2 = $data_leng_old2;
			}			
			$data_status2['status_leng'] = json_encode($data_leng_new2);
			$result_status2 = Db::name('order')->where('id='.$id)->update($data_status2);						
			/*返回结果集*/
			return show(config('code.success'),'ok',$data);
		} else {
			return show(config('code.error'),'error',array());
		}
	}

    /**
     * 个人中心-我的好友-患者|专家
     */
    public function friends(){
		/*用户ID*/
		$id = $this->user['id'];
		if(empty($id) || empty(input('tab'))){
			return show(config('code.error'),'参数缺失',array());
		}
		$tab = input('tab');
		/*获取参数*/
		$sline = !empty(input('sline'))?input('sline'):0;
		$limit = !empty(input('limit'))?input('limit'):config('default_limit')+88;
		/*条数*/
		$limit = $sline.','.$limit;
		/*查询内容*/
		$where_maillist['user_id'] = $id;
		$where_maillist['status'] = 1;
		$maillist = Db::name('maillist')->field('user_to_id')->where($where_maillist)->select();	
		$user_to_id_str = '';
		foreach($maillist as $k=>$v){
			/*查询被添加人的身份*/
			$user = Db::name('user')->field('group_ids')->where('id='.$v['user_to_id'])->find();
			if($tab == 1 && !empty($user) && $user['group_ids']==2){
				$user_to_id_str .= ','.$v['user_to_id'];/*用户*/
			}else if($tab == 2 && !empty($user) && $user['group_ids']==5){
				$user_to_id_str .= ','.$v['user_to_id'];/*专家*/
			}
		}
		$user_to_id_str = trim($user_to_id_str,',');
		$where_maillist2['user_to_id'] = $id;
		$where_maillist2['status'] = 1;
		$maillist2 = Db::name('maillist')->field('user_id')->where($where_maillist2)->select();	
		foreach($maillist2 as $k=>$v){
			/*查询被添加人的身份*/
			$user = Db::name('user')->field('group_ids')->where('id='.$v['user_id'])->find();
			if($tab == 1 && !empty($user) && $user['group_ids']==2){
				$user_to_id_str .= ','.$v['user_id'];/*用户*/
			}else if($tab == 2 && !empty($user) && $user['group_ids']==5){
				$user_to_id_str .= ','.$v['user_id'];/*专家*/
			}
		}		
		$user_to_id_str = trim($user_to_id_str,',');
		$where_list['id'] = array('in',$user_to_id_str);
		$where_list['status']  = 2;/*状态*/		
		/*排序*/	
		$order = '`realname` asc';
		/*字段*/
		$field = 'id,realname,picture,job';
		/*查询聊天名师*/
		$total = Db::name('user')->where($where_list)->count();
		$list = Db::name('user')->field($field)->where($where_list)->order($order)->limit($limit)->select();
		$lists = array();
		foreach($list as $k => $v){
			$lists[$k] = $v;
			/*解析图片*/
			$user_picture_arr = explode(',',$v['picture']);
			$lists[$k]['picture'] = $user_picture_arr[0];
			if(!empty($v['realname'])){
				$lists[$k]['AZ'] = getfirstchar($v['realname']);
			}else{
				$lists[$k]['AZ'] = 'WM';
			}
		}
		/*按字母分组*/
		$list_arr = array();
		$az_arr = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','WM');
		foreach($az_arr as $k2=>$v2){
			foreach($lists as $k=>$v){
				if($v['AZ'] == $v2){
					$list_arr[$v2][$k] = $v;
				}
			}
		}
		/*处理结果集*/
		$result_data = array(
			'total' => $total,
			'list'	=> $lists
		);
		return show(config('code.success'),'ok',$list_arr);	
	}

    /**
     * 个人中心-添加好友(患者|专家)
     */
    public function friendsadd(){
		/*用户ID*/
		$id = $this->user['id'];
		if(empty($id) || empty(input('user_to_id'))){
			return show(config('code.error'),'参数缺失',array());
		}
		$nowtime = time();
		$data = array(
			'user_id' 		=> $this->user['id'],
			'user_to_id' 	=> input('user_to_id'),
			'addtime' 		=> $nowtime,
			'edittime' 		=> $nowtime,	
		);
		/*查询*/
		$where['user_id'] = $this->user['id'];
		$where['user_to_id'] = input('user_to_id');
		$where2['user_id'] = input('user_to_id');
		$where2['user_to_id'] = $this->user['id'];
		$GLOBALS['user_id'] = $id;
		$maillist = Db::name('maillist')->where(function($query) {
			$query->where('user_id',$GLOBALS['user_id'])->whereor('user_id',input('user_to_id'));
		})->where(function ($query) {
			$query->where('user_to_id',$GLOBALS['user_id'])->whereor('user_to_id',input('user_to_id'));
		})->find();
		if(empty($maillist)){
			/*插入数据*/
			$result = Db::name('maillist')->insert($data);
			$data['id'] = Db::name('maillist')->getLastInsID();
		}else{
			if($maillist['status'] == 1){
				return show(config('code.success'),'ok,已经是好友',$data);
			}else{
				$data['status'] = 1;
				$result = Db::name('maillist')->where('id='.$maillist['id'])->update($data);
			}
		}
		if($result){
			return show(config('code.success'),'ok',$data);
		}else{
			return show(config('code.error'),'error',array());
		}
	}

    /**
     * 个人中心-收货地址
     */
    public function address(){
		/*用户ID*/
		$id = $this->user['id'];
		/*获取参数*/	
		$sline = !empty(input('sline'))?input('sline'):0;
		$limit = !empty(input('limit'))?input('limit'):config('default_limit');
		/*查询聊天名师*/
		$where_list['user_id'] = $id;/*用户ID*/
		/*排序*/	
		$order = 'addtime desc,id desc';
		/*条数*/
		$limit = $sline.','.$limit;
		/*字段*/
		$field = 'id,user_id,receiver,tel,province,city,area,content,default,addtime';
		/*查询聊天名师*/
		$total = Db::name('address')->where($where_list)->count();
		$list = Db::name('address')->field($field)->where($where_list)->order($order)->limit($limit)->select();
		$lists = array();
		foreach($list as $k => $v){
			$lists[$k] = $v;
			/*格式化时间*/	
			$lists[$k]['addtime'] = date('Y-m-d',$v['addtime']);
		}
		/*处理结果集*/
		$result_data = array(
			'total' => $total,
			'list'	=> $lists
		);
		return show(config('code.success'),'ok',$result_data);
	}

    /**
     * 个人中心-添加收货地址
     */
    public function addressadd(){
		/*ID*/
		if(empty(input('receiver')) || empty(input('tel')) || empty(input('province')) || empty(input('city')) || empty(input('area')) || empty(input('content'))){
			return show(config('code.error'),'参数缺失',array());
		}
		$nowtime = time();
		$data = array(
			'user_id' 		=> $this->user['id'],
			'receiver' 		=> input('receiver'),/*收货人*/
			'tel' 			=> input('tel'),/*电话*/
			'province' 		=> input('province'),/*省*/
			'city' 			=> input('city'),/*市*/
			'area' 			=> input('area'),/*区*/
			'content' 		=> input('content'),/*详情地址*/
			'default' 		=> !empty(input('default'))?input('default'):0,/*默认 默认为0:否|1:是*/
			'addtime' 		=> $nowtime,
			'edittime' 		=> $nowtime,	
		);
		/*如果设置为默认地址，修改其它地址*/
		if($data['default'] == 1){
			$default_list_where['user_id'] = $this->user['id'];
			$default_list_where['default'] = 1;
			$default_list = Db::name('address')->field('id')->where($default_list_where)->select();
			foreach($default_list as $k=>$v){
				$default_data['default'] = 0;
				$default_result = Db::name('address')->where('id='.$v['id'])->update($default_data);
			}
		}
		/*插入数据*/
		$result = Db::name('address')->insert($data);
		$data['id'] = Db::name('address')->getLastInsID();
		if($result){
			return show(config('code.success'),'ok',$data);
		}else{
			return show(config('code.error'),'error',array());
		}	
	}
	
    /**
     * 个人中心-修改收货地址
     */
    public function addressedit(){
		/*ID*/
		$id = input('address_id');
		if(empty($id) || empty(input('receiver')) || empty(input('tel')) || empty(input('province')) || empty(input('city')) || empty(input('area')) || empty(input('content'))){
			return show(config('code.error'),'参数缺失',array());
		}
		$nowtime = time();
		$data = array(
			'receiver' 		=> input('receiver'),/*收货人*/
			'tel' 			=> input('tel'),/*电话*/
			'province' 		=> input('province'),/*省*/
			'city' 			=> input('city'),/*市*/
			'area' 			=> input('area'),/*区*/
			'content' 		=> input('content'),/*详情地址*/
			'default' 		=> !empty(input('default'))?input('default'):0,/*默认 默认为0:否|1:是*/
			'edittime' 		=> $nowtime,		
		);
		/*如果设置为默认地址，修改其它地址*/
		if($data['default'] == 1){
			$default_list_where['user_id'] = $this->user['id'];
			$default_list_where['default'] = 1;
			$default_list = Db::name('address')->field('id')->where($default_list_where)->select();
			foreach($default_list as $k=>$v){
				$default_data['default'] = 0;
				$default_result = Db::name('address')->where('id='.$v['id'])->update($default_data);
			}
		}		
		$result = Db::name('address')->where('id='.$id)->update($data);
		if($result){
			return show(config('code.success'),'ok',$data);
		}else{
			return show(config('code.error'),'error',array());
		}	
	}
	
    /**
     * 个人中心-删除收货地址
     */
    public function addressdel(){
		/*ID*/
		$id = input('address_id');
		if(empty($id)){
			return show(config('code.error'),'参数缺失',array());
		}
		$result = Db::name('address')->where('id='.$id)->delete();
		if($result){
			return show(config('code.success'),'ok',array());
		}else{
			return show(config('code.error'),'error',array());
		}	
	}

    /**
     * 个人中心-评价
     */
    public function user_comment(){
		/*用户ID*/
		$id = $this->user['id'];
		/*获取参数*/	
		$sline = !empty(input('sline'))?input('sline'):0;
		$limit = !empty(input('limit'))?input('limit'):config('default_limit');
		/*查询聊天名师*/
		$where_list['user_id'] = $id;/*用户ID*/
		/*排序*/	
		$order = 'addtime desc,id desc';
		/*条数*/
		$limit = $sline.','.$limit;
		/*字段*/
		$field = 'id,user_id,user_to_id,total_star,total_level,total_smile,content,addtime';
		/*查询聊天名师*/
		$total = Db::name('user_comment')->where($where_list)->count();
		$list = Db::name('user_comment')->field($field)->where($where_list)->order($order)->limit($limit)->select();
		$lists = array();
		foreach($list as $k => $v){
			$lists[$k] = $v;
			/*格式化时间*/	
			$lists[$k]['addtime'] = date('Y-m-d',$v['addtime']);
			/*查询专家身份信息*/
			$user = Db::name('user')->field('job,nickname,picture')->where('id='.$v['user_to_id'])->find();
			$user_picture_arr = explode(',',$user['picture']);
			$lists[$k]['nickname'] = $user['nickname'];
			$lists[$k]['picture'] = $user_picture_arr[0];
			$lists[$k]['job'] = $user['job'];
		}
		/*处理结果集*/
		$result_data = array(
			'total' => $total,
			'list'	=> $lists
		);
		return show(config('code.success'),'ok',$result_data);
	}
	
    /**
     * 个人中心-删除评价(支持批量)
     */
    public function user_commentdel(){
		/*ID支持批量*/
		$ids = input('user_comment_id');
		if(empty($ids)){
			return show(config('code.error'),'参数缺失',array());
		}
		$ids = trim($ids,',');
		$where['id'] = array('in',$ids);
		$result = Db::name('user_comment')->where($where)->delete();
		if($result){
			return show(config('code.success'),'ok',array());
		}else{
			return show(config('code.error'),'error',array());
		}	
	}

    /**
     * 个人中心-系统信息(客服热线)
     */
    public function site(){
		/*ID*/
		$id = $this->site_id;
		if(empty($id)){
			return show(config('code.error'),'参数缺失',array());
		}
		$field = 'name,ico,tel';
		$result = Db::name('site')->field($field)->where('id='.$id)->find();
		if($result){
			return show(config('code.success'),'ok',$result);
		}else{
			return show(config('code.error'),'error',array());
		}	
	}

    /**
     * 个人中心-修改个人信息
     */
    public function useredit(){
		/*用户ID*/
		$id = $this->user['id'];
		if(empty($id)){
			return show(config('code.error'),'参数缺失',array());
		}
		$nowtime = time();
		if(!empty(input('nickname'))){
			$data['nickname'] = input('nickname');
		}
		if(!empty(input('realname'))){
			$data['realname'] = input('realname');
		}
		if(!empty(input('picture'))){
			$data['picture'] = input('picture');
		}
		if(!empty(input('sex'))){
			$data['sex'] = input('sex');
		}
		if(!empty(input('year'))){
			$data['year'] = input('year');
		}					
		if(!empty(input('month'))){
			$data['month'] = input('month');
		}	
		if(!empty(input('day'))){
			$data['day'] = input('day');
		}
		/*诊所&学历*/
		if(!empty(input('education'))){
			$data['education'] = input('education');
		}
		$data['edittime'] = $nowtime;
		$result = Db::name('user')->where('id='.$id)->update($data);
		if($result){
			return show(config('code.success'),'ok',$data);
		}else{
			return show(config('code.error'),'error',array());
		}	
	}

    /**
     * 个人中心-修改密码
     */
    public function passedit(){
		/*用户ID*/
		$id = $this->user['id'];
		if(empty($id) || empty(input('old_pass')) || empty(input('new_pass'))){
			return show(config('code.error'),'参数缺失',array());
		}
		$user = Db::name('user')->field('password,addtime')->where('id='.$id)->find();
		$nowtime = time();
		if(md5(md5($user['addtime'].input('old_pass'))) == $user['password']){
			$data['password'] = md5(md5($user['addtime'].input('new_pass')));
			$data['edittime'] = $nowtime;
			$result = Db::name('user')->where('id='.$id)->update($data);
			if($result){
				return show(config('code.success'),'ok',$data);
			}else{
				return show(config('code.error'),'error',array());
			}	
		}else{
			return show(2,'密码错误',array());
		}
	}	

	/**
	 * 个人中心-手机绑定
	 */
    public function phonebind(){
		/*参数校验*/
		$type 		= input('type');
		$phone 		= input('phone');
		$code	 	= input('code');
        if(empty($type)){
            return show(config('code.error'),'身份类型不合法','',404);
        }		
        if(empty($phone)){
            return show(config('code.error'),'手机不合法','',404);
        }
        if(empty($code)){
            return show(config('code.error'),'验证码不合法','',404);
        }else{
            /*validate严格校验*/
            $sms_code = Alisms::getInstance()->checkSmsIdentify($phone);
            if($code != $sms_code){
                return show(config('code.error'),'手机短信验证码错误','',404);
            }
		}
		/*校验类型*/
		if($type == 1){
			$group_id = 2;/*用户*/
		}else if($type == 2){
			$group_id = 4;/*医生*/
		}else if($type == 3){
			$group_id = 5;/*专家*/
		}		
        /*查询这个手机号是否存在*/
		$where['site_ids']	= 1;
		$where['group_ids']	= $group_id;
		$where['phone']		= $phone;
		$user = Db::name('user')->where($where)->find();
        if(empty($user)){
			return show(config('code.error'),'用户不存在',array());			
        }else{
			$nowtime = time();
			$data = array(
				'phone' 		=> $phone,
				'edittime' 		=> $nowtime,/*修改时间*/
			);
			$result_update = Db::name('user')->where($where)->update($data);
			if($result_update){
				return show(config('code.success'),'ok',$data);
			}else {
				return show(config('code.error'),'error',array());
			}
        }
    }

    /**
     * 个人中心-我的出诊时间
     */
    public function worktime(){
		/*用户ID*/
		$id = $this->user['id'];
		if(empty($id)){
			return show(config('code.error'),'参数缺失',array());
		}
		$user = Db::name('user')->field('work_week,work_day_start,work_day_end')->where('id='.$id)->find();
		if(!empty($user['work_week'])){
			$str = trim($user['work_week'],',');
			$str_arr = explode(',',$str);
			/*按大小排序*/
			foreach($str_arr as $k=>$v){
				$str_arrs[$v] = $v;
			}
			ksort($str_arrs);
			/*相邻分组*/
			$i = -1;
			$s = 0;
			foreach($str_arrs as $k=>$v){
				if(abs($v - $i) != 1){
					$s++;
				}
				$leng[$s][$k] = $v;
				$i = $v;
			}
			/*解析为中文*/
			$total_str = '';
			foreach($leng as $k=>$v){
				$total = count($v);
				if($total > 1){
					$first = array_shift($v);/*取出第一个*/
					$last = array_pop($v);/*取出最后一个*/
					$total_str .= ','.weekday($first).'-'.weekday($last);
				}else{
					foreach($v as $k2=>$v2){
						$total_str .= ','.weekday($v2);
					}
				}
			}
			$total_str = trim($total_str,',');
			$user['work_week_zh'] = $total_str;
		}else{
			$user['work_week_zh'] = '未知';
		}
		if($user){
			return show(config('code.success'),'ok',$user);
		}else{
			return show(config('code.error'),'error',array());
		}	
	}

    /**
     * 个人中心-我的出诊修改
     */
    public function worktimeedit(){
		/*用户ID*/
		$id = $this->user['id'];
		if(empty($id)){
			return show(config('code.error'),'参数缺失',array());
		}
		$nowtime = time();
		if(!empty(input('work_week'))){
			$data['work_week'] = input('work_week');
		}
		if(!empty(input('work_day_start'))){
			$data['work_day_start'] = input('work_day_start');
		}
		if(!empty(input('work_day_end'))){
			$data['work_day_end'] = input('work_day_end');
		}
		$data['edittime'] = $nowtime;
		$result = Db::name('user')->where('id='.$id)->update($data);
		if($result){
			return show(config('code.success'),'ok',$data);
		}else{
			return show(config('code.error'),'error',array());
		}	
	}

    /**
     * 个人中心-诊所信息
     */
    public function clinicinfo(){
		/*用户ID*/
		$id = $this->user['id'];
		if(empty($id)){
			return show(config('code.error'),'参数缺失',array());
		}
		$user = Db::name('user')->field('clinic_id')->where('id='.$id)->find();
		if(empty($user['clinic_id'])){
			return show(config('code.error'),'诊所信息缺失',array());
		}		
		$clinic = Db::name('clinic')->field('tel,workday,description')->where('id='.$user['clinic_id'])->find();
		if($clinic){
			return show(config('code.success'),'ok',$clinic);
		}else{
			return show(config('code.error'),'error',array());
		}	
	}
	
    /**
     * 个人中心-诊所信息-修改
     */
    public function clinicedit(){
		/*用户ID*/
		$id = $this->user['id'];
		if(empty($id)){
			return show(config('code.error'),'参数缺失',array());
		}
		$user = Db::name('user')->field('clinic_id')->where('id='.$id)->find();
		if(empty($user['clinic_id'])){
			return show(config('code.error'),'诊所信息缺失',array());
		}
		$nowtime = time();
		if(!empty(input('tel'))){
			$data['tel'] = input('tel');
		}
		if(!empty(input('workday'))){
			$data['workday'] = input('workday');
		}
		if(!empty(input('description'))){
			$data['description'] = input('description');
		}
		$data['edittime'] = $nowtime;
		$result = Db::name('clinic')->where('id='.$user['clinic_id'])->update($data);
		if($result){
			return show(config('code.success'),'ok',$data);
		}else{
			return show(config('code.error'),'error',array());
		}	
	}

    /**
     * 个人中心-医生管理
     */
    public function doctor(){
		/*用户ID*/
		$id = $this->user['id'];
		if(empty($id)){
			return show(config('code.error'),'参数缺失',array());
		}
		$user = Db::name('user')->field('clinic_id')->where('id='.$id)->find();
		if(empty($user['clinic_id'])){
			return show(config('code.error'),'诊所信息缺失',array());
		}		
		/*获取参数*/	
		$sline = !empty(input('sline'))?input('sline'):0;
		$limit = !empty(input('limit'))?input('limit'):config('default_limit');
		/*查询聊天名师*/
		$where_list['clinic_id'] = $user['clinic_id'];/*诊所ID*/
		$where_list['group_ids'] = 4;/*医生*/
		/*排序*/	
		$order = 'addtime desc,id desc';
		/*条数*/
		$limit = $sline.','.$limit;
		/*字段*/
		$field = 'id,realname,addtime';
		/*查询聊天名师*/
		$total = Db::name('user')->where($where_list)->count();
		$list = Db::name('user')->field($field)->where($where_list)->order($order)->limit($limit)->select();
		$lists = array();
		foreach($list as $k => $v){
			$lists[$k] = $v;
			/*格式化时间*/	
			$lists[$k]['addtime'] = date('Y-m-d',$v['addtime']);
		}
		/*处理结果集*/
		$result_data = array(
			'total' => $total,
			'list'	=> $lists
		);
		return show(config('code.success'),'ok',$result_data);
	}		

    /**
     * 个人中心-医生管理-添加
     */
    public function doctoradd(){
		$user = Db::name('user')->field('clinic_id')->where('id='.$this->user['id'])->find();
		if(empty($user['clinic_id'])){
			return show(config('code.error'),'诊所信息缺失',array());
		}
		/*参数校验*/
		if(empty(input('name')) || empty(input('phone')) || empty(input('realname'))){
			return show(config('code.error'),'参数缺失',array());
		}
		$nowtime = time();
		$data = array(
			'site_ids'		=> $this->site_id,/*站点ID*/
			'clinic_id' 	=> $user['clinic_id'],
			'name' 			=> input('name'),/*帐号名*/
			'phone' 		=> input('phone'),/*帐号手机*/
			'realname' 		=> input('realname'),/*真实姓名*/
			'addtime' 		=> $nowtime,
			'edittime' 		=> $nowtime
		);
		/*用户名验证*/
		$namevalidate_where['site_ids']	= $this->site_id;
		$namevalidate_where['name'] = $data['name'];
		$namevalidate = Db::name('user')->where($namevalidate_where)->find();
        if($namevalidate){
			return show(config('code.error'),'该用户已存在',array());			
        }
		/*手机号验证*/
		$phonevalidate_where['site_ids']	= $this->site_id;
		$phonevalidate_where['phone'] = $data['phone'];
		$phonevalidate = Db::name('user')->where($phonevalidate_where)->find();
        if($phonevalidate){
			return show(config('code.error'),'该手机号已存在',array());			
        }		
		$nowtime = time();
		if(!empty(input('password'))){
			$data['password'] = md5(md5($nowtime.input('new_pass')));/*密码*/
		}
		if(!empty(input('picture'))){
			$data['picture'] = input('picture');/*头像*/
		}
		if(!empty(input('identity'))){
			$data['identity'] = input('identity');/*身份证号码*/
		}
		if(!empty(input('identity_pic_a'))){
			$data['identity_pic_a'] = input('identity_pic_a');/*身份证正面*/
		}
		if(!empty(input('identity_pic_b'))){
			$data['identity_pic_b'] = input('identity_pic_b');/*身份证背面*/
		}	
		if(!empty(input('practitioners'))){
			$data['practitioners'] = input('practitioners');/*从业资格证*/
		}
		if(!empty(input('physician'))){
			$data['physician'] = input('physician');/*职业医师证书*/
		}
		if(!empty(input('pharmacist'))){
			$data['pharmacist'] = input('pharmacist');/*职业药师证书*/
		}											
		/*插入数据*/
		$result = Db::name('user')->insert($data);
		$data['id'] = Db::name('user')->getLastInsID();
		if($result){
			return show(config('code.success'),'ok',$data);
		}else{
			return show(config('code.error'),'error',array());
		}	
	}

    /**
     * 个人中心-医生管理-详情
     */
    public function doctorcon(){
		/*参数校验*/
		if(empty(input('doctor_id'))){
			return show(config('code.error'),'参数缺失',array());
		}
		$field = 'name,phone,realname,picture,identity,identity_pic_a,identity_pic_b,practitioners,physician,pharmacist';
		$user = Db::name('user')->field($field)->where('id='.input('doctor_id'))->find();
		if($user){
			return show(config('code.success'),'ok',$user);
		}else{
			return show(config('code.error'),'error',array());
		}	
	}

    /**
     * 个人中心-医生管理-重置
     */
    public function doctoredit(){
		$user = Db::name('user')->field('clinic_id')->where('id='.$this->user['id'])->find();
		if(empty($user['clinic_id'])){
			return show(config('code.error'),'诊所信息缺失',array());
		}
		/*参数校验*/
		if(empty(input('doctor_id')) || empty(input('name')) || empty(input('phone')) || empty(input('realname'))){
			return show(config('code.error'),'参数缺失',array());
		}
		$nowtime = time();
		$data = array(
			'site_ids'		=> $this->site_id,/*站点ID*/
			'clinic_id' 	=> $user['clinic_id'],
			'name' 			=> input('name'),/*帐号名*/
			'phone' 		=> input('phone'),/*帐号手机*/
			'realname' 		=> input('realname'),/*真实姓名*/
			'addtime' 		=> $nowtime,
			'edittime' 		=> $nowtime
		);
		/*用户名验证*/
		$namevalidate_where['site_ids']	= $this->site_id;
		$namevalidate_where['name'] = $data['name'];
		$namevalidate = Db::name('user')->where($namevalidate_where)->find();
        if($namevalidate){
			return show(config('code.error'),'该用户已存在',array());			
        }
		/*手机号验证*/
		$phonevalidate_where['site_ids']	= $this->site_id;
		$phonevalidate_where['phone'] = $data['phone'];
		$phonevalidate = Db::name('user')->where($phonevalidate_where)->find();
        if($phonevalidate){
			return show(config('code.error'),'该手机号已存在',array());			
        }		
		$nowtime = time();
		if(!empty(input('password'))){
			$data['password'] = md5(md5($nowtime.input('new_pass')));/*密码*/
		}
		if(!empty(input('picture'))){
			$data['picture'] = input('picture');/*头像*/
		}
		if(!empty(input('identity'))){
			$data['identity'] = input('identity');/*身份证号码*/
		}
		if(!empty(input('identity_pic_a'))){
			$data['identity_pic_a'] = input('identity_pic_a');/*身份证正面*/
		}
		if(!empty(input('identity_pic_b'))){
			$data['identity_pic_b'] = input('identity_pic_b');/*身份证背面*/
		}	
		if(!empty(input('practitioners'))){
			$data['practitioners'] = input('practitioners');/*从业资格证*/
		}
		if(!empty(input('physician'))){
			$data['physician'] = input('physician');/*职业医师证书*/
		}
		if(!empty(input('pharmacist'))){
			$data['pharmacist'] = input('pharmacist');/*职业药师证书*/
		}											
		/*插入数据*/
		$where['id'] = input('doctor_id');
		$result = Db::name('user')->where($where)->update($data);
		if($result){
			return show(config('code.success'),'ok',$data);
		}else{
			return show(config('code.error'),'error',array());
		}		
	}

    /**
     * 首页
     */
    public function index(){
		/*用户ID*/
		$id = $this->user['id'];
		if(empty($id)){
			return show(config('code.error'),'参数缺失',array());
		}
		$user = Db::name('user')->field('clinic_id')->where('id='.$id)->find();
		if(empty($user['clinic_id'])){
			return show(config('code.error'),'诊所信息缺失',array());
		}
		/*诊所信息*/
		$clinic = Db::name('clinic')->field('name')->where('id='.$user['clinic_id'])->find();
		/*获取参数*/	
		$sline = !empty(input('sline'))?input('sline'):0;
		$limit = !empty(input('limit'))?input('limit'):config('default_limit');
		/*查询聊天名师*/
		$where_list['clinic_id'] = $user['clinic_id'];/*诊所ID*/
		$where_list['group_ids'] = 4;/*医生*/
		/*排序*/	
		$order = 'addtime desc,id desc';
		/*条数*/
		$limit = $sline.','.$limit;
		/*字段*/
		$field = 'id,realname,picture,job,description,doctor_type,addtime';
		/*查询聊天名师*/
		$total = Db::name('user')->where($where_list)->count();
		$list = Db::name('user')->field($field)->where($where_list)->order($order)->limit($limit)->select();
		$lists = array();
		foreach($list as $k => $v){
			$lists[$k] = $v;
			/*格式化时间*/	
			$lists[$k]['addtime'] = date('Y-m-d',$v['addtime']);
		}
		/*处理结果集*/
		$result_data = array(
			'total' => $total,
			'clinic' => $clinic['name'],
			'list'	=> $lists
		);
		return show(config('code.success'),'ok',$result_data);
	}	

    /**
     * 在线|离线状态查询
     */
    public function is_online(){
		/*用户ID*/
		$id = $this->user['id'];
		if(empty($id)){
			return show(config('code.error'),'参数缺失',array());
		}
		$user = Db::name('user')->field('is_online')->where('id='.$id)->find();
		if(empty($user)){
			return show(config('code.error'),'用户信息缺失',array());
		}
		$is_online = !empty($user['is_online'])?$user['is_online']:0;
		return show(config('code.success'),'ok',$is_online);
	}

    /**
     * 在线|离线状态切换
     */
    public function is_onlineedit(){
		/*用户ID*/
		$id = $this->user['id'];
		if(empty($id)){
			return show(config('code.error'),'参数缺失',array());
		}
		$user = Db::name('user')->field('is_online')->where('id='.$id)->find();
		if(empty($user)){
			return show(config('code.error'),'用户信息缺失',array());
		}
		$is_online = !empty($user['is_online'])?$user['is_online']:0;
		if($is_online == 1){
			$data['is_online'] = 0;
		}else{
			$data['is_online'] = 1;
		}
		$nowtime = time();
		$data['edittime'] = $nowtime;
		$where['id'] = $id;
		$result = Db::name('user')->where($where)->update($data);
		if($result){
			return show(config('code.success'),'ok',$data);
		}else{
			return show(config('code.error'),'error',array());
		}
	}

    /**
     * 系统消息列表
     */
    public function message(){
		/*用户ID*/
		$id = $this->user['id'];
		if(empty($id)){
			return show(config('code.error'),'参数缺失',array());
		}		
		/*获取参数*/
		$sline = !empty(input('sline'))?input('sline'):0;
		$limit = !empty(input('limit'))?input('limit'):config('default_limit');
		/*条数*/
		$limit = $sline.','.$limit;
		/*查询内容*/
		$where_list['type']  = array('in','2,4');/*1:用户端 | 2:医生端 | 3:专家端 | 4:所有终端*/	
		$where_list['status']  = 1;/*状态*/		
		/*排序*/	
		$order = '`order` desc,edittime desc';
		/*字段*/
		$field = 'id,name,description,picture,content,edittime';
		/*查询聊天名师*/
		$total = Db::name('message')->where($where_list)->count();
		$list = Db::name('message')->field($field)->where($where_list)->order($order)->limit($limit)->select();
		$lists = array();
		foreach($list as $k => $v){
			$lists[$k] = $v;
			/*格式化时间*/
			$lists[$k]['edittime'] = date('Y-m-d',$v['edittime']);	
			/*查看用户操作状态*/
			$where_message_log['user_id'] = $id;
			$where_message_log['message_id'] = $v['id'];
			$message_log = Db::name('message_log')->field('id,status')->where($where_message_log)->find();
			if(empty($message_log)){
				$lists[$k]['log_status'] = 0;/*未读*/
			}else{
				if($message_log['status']==1){
					$lists[$k]['log_status'] = 1;/*已读*/
				}else if($message_log['status']==2){
					$lists[$k]['log_status'] = 2;/*已删除*/
				}
			}
		}
		/*处理结果集*/
		$result_data = array(
			'total' => $total,
			'list'	=> $lists
		);
		return show(config('code.success'),'ok',$result_data);	
	}

    /**
     * 系统消息-未读统计
     */
    public function unmessage(){
		/*用户ID*/
		$id = $this->user['id'];
		if(empty($id)){
			return show(config('code.error'),'参数缺失',array());
		}
		/*查询该用户已读或删除的记录*/
		$where_message_log['user_id'] = $id;
		$where_message_log['status'] = array('>',0); 
		$read_arr = Db::name('message_log')->where($where_message_log)->count();
		/*查询系统通知总数目*/
		$where_list['type']  = array('in','2,4');/*1:用户端 | 2:医生端 | 3:专家端 | 4:所有终端*/	
		$where_list['status'] = 1;
		$total = Db::name('message')->where($where_list)->count();
		$total = $total - $read_arr;
		if($total < 0){
			$total = 0;
		}
		/*处理结果集*/
		$result_data = array(
			'total' => $total
		);
		return show(config('code.success'),'ok',$result_data);	
	}

    /**
     * 系统消息-标记已读(支持批量)
     */
    public function messagered(){
		$id = $this->user['id'];/*用户ID*/	
		$ids = input('message_id');/*ID支持批量*/
		if(empty($ids) || empty($id)){
			return show(config('code.error'),'参数缺失',array());
		}
		$ids = trim($ids,',');
		$now_time = time();
		$ids_arr = explode(',',$ids);
		if(!empty($ids_arr)){
			$result = 0;
			foreach($ids_arr as $k=>$v){
				/*查询用户记录情况*/
				$where['user_id'] = $id;
				$where['message_id'] = $v;
				$message_log = Db::name('message_log')->field('id,status')->where($where)->find();
				$data['status'] 	= 1;
				$data['edittime'] 	= $now_time;
				if(empty($message_log)){
					$data['user_id'] 	= $id;
					$data['message_id'] = $v;
					$data['addtime'] 	= $now_time;
					$result_insert = Db::name('message_log')->insert($data);
					$result = $result+$result_insert;
				}else{
					$result_update = Db::name('message_log')->where($where)->update($data);
					$result = $result+$result_update;
				}	
			}
			if($result){
				return show(config('code.success'),'ok',$result);
			}else{
				return show(config('code.error'),'error',array());
			}			
		}else{
			return show(config('code.error'),'参数格式错误',array());
		}
	}

    /**
     * 系统消息-消息删除(支持批量)
     */
    public function messagedel(){
		$id = $this->user['id'];/*用户ID*/
		$ids = input('message_id');/*ID支持批量*/
		if(empty($ids) || empty($id)){
			return show(config('code.error'),'参数缺失',array());
		}
		$ids = trim($ids,',');
		$now_time = time();
		$ids_arr = explode(',',$ids);
		if(!empty($ids_arr)){
			$result = 0;
			foreach($ids_arr as $k=>$v){
				/*查询用户记录情况*/
				$where['user_id'] = $id;
				$where['message_id'] = $v;
				$message_log = Db::name('message_log')->field('id,status')->where($where)->find();
				$data['status'] 	= 2;
				$data['edittime'] 	= $now_time;
				if(empty($message_log)){
					$data['user_id'] 	= $id;
					$data['message_id'] = $v;
					$data['addtime'] 	= $now_time;
					$result_insert = Db::name('message_log')->insert($data);
					$result = $result+$result_insert;
				}else{
					$result_update = Db::name('message_log')->where($where)->update($data);
					$result = $result+$result_update;
				}	
			}
			if($result){
				return show(config('code.success'),'ok',$result);
			}else{
				return show(config('code.error'),'error',array());
			}			
		}else{
			return show(config('code.error'),'参数格式错误',array());
		}
	}

    /**
     * 名医联线
     */
    public function expert(){
		/*获取参数*/
		$sline = !empty(input('sline'))?input('sline'):0;
		$limit = !empty(input('limit'))?input('limit'):config('default_limit');
		/*在线状态*/
		$is_online = input('is_online');
		if((!empty($is_online) || $is_online == 0) && $is_online != 2){
			$where_list['is_online'] = !empty($is_online)?$is_online:0;
		}
		/*查询聊天名师*/
		$where_list['group_ids'] = 5;/*专家*/	
		/*综合评价*/
		$is_comment = input('is_comment');
		if(!empty($is_comment)){
			$list = Db::name('user')->field('id')->where($where_list)->select();
			$lists = array();
			foreach($list as $k => $v){
				$lists[$k] = $v;
				/*统计评分指数*/
				$where_user_comment['user_to_id'] = $v['id'];/*被评人*/
				$total_star_avg = Db::table('ym_user_comment')->where($where_user_comment)->avg('total_star');/*综合评分平均分*/
				$total_level_avg = Db::table('ym_user_comment')->where($where_user_comment)->avg('total_level');/*医生水准平均分*/
				$total_smile_avg = Db::table('ym_user_comment')->where($where_user_comment)->avg('total_smile');/*医生态度平均分*/
				$lists[$k]['comment_avg'] = number_format(($total_star_avg+$total_level_avg+$total_smile_avg)/3*2,1);
			}			
			if($is_comment == 1){
				/*由高到低*/
				$lists = sort_array($lists,'comment_avg','desc');
			}else if($is_comment == 2){
				/*由低到高*/
				$lists = sort_array($lists,'comment_avg','asc');
			}
			$comment_str = '';
			foreach($lists as $k=>$v){
				$comment_str .= ','.$v['id'];
			}
			$comment_str = trim($comment_str,',');
			$where_list['id'] = array('in',$comment_str);
			/*排序*/	
			$order = '';			
		}else{
			/*排序*/	
			$order = 'addtime desc,id desc';
		}
		/*条数*/
		$limit = $sline.','.$limit;
		/*字段*/
		$field = 'id,realname,picture,job,description,addtime';
		/*查询*/
		$total = Db::name('user')->where($where_list)->count();
		$list = Db::name('user')->field($field)->where($where_list)->order($order)->limit($limit)->select();
		$lists = array();
		foreach($list as $k => $v){
			$lists[$k] = $v;
			/*格式化时间*/
			$lists[$k]['addtime'] = date('Y-m-d',$v['addtime']);
			/*统计评分指数*/
			$where_user_comment['user_to_id'] = $v['id'];/*被评人*/
			$total_star_avg = Db::table('ym_user_comment')->where($where_user_comment)->avg('total_star');/*综合评分平均分*/
			$total_level_avg = Db::table('ym_user_comment')->where($where_user_comment)->avg('total_level');/*医生水准平均分*/
			$total_smile_avg = Db::table('ym_user_comment')->where($where_user_comment)->avg('total_smile');/*医生态度平均分*/
			$lists[$k]['comment_avg'] = number_format(($total_star_avg+$total_level_avg+$total_smile_avg)/3*2,1);
			if(!empty($is_comment)){
				/*排序*/
				if($is_comment == 1){
					/*由高到低*/
					$lists = sort_array($lists,'comment_avg','desc');
				}else if($is_comment == 2){
					/*由低到高*/
					$lists = sort_array($lists,'comment_avg','asc');
				}				
			}
		}		
		/*处理结果集*/
		$result_data = array(
			'total' => $total,
			'list'	=> $lists
		);
		return show(config('code.success'),'ok',$result_data);
	}	

    /**
     * 在线接诊(咨询-聊天)
     */
    public function chat(){
		/*用户ID*/
		$id = $this->user['id'];
		if(empty($id) || empty(input('tab'))){
			return show(config('code.error'),'参数缺失',array());
		}
		$tab = input('tab');
		/*获取参数*/	
		$sline = !empty(input('sline'))?input('sline'):0;
		$limit = !empty(input('limit'))?input('limit'):config('default_limit');
		/*查询*/
		$where_user_list['user_from_id'] = $id;/*信息发件人*/
		if($tab == 1){
			$where_user_list['user_to_type'] = 1;/*用户*/
		}else if($tab == 2){
			$where_user_list['user_to_type'] = 3;/*专家*/
		}
		/*条件*/
		$where_user_list2 = array();
		if(!empty($t_start_data) && empty($t_end_data)){
			$where_user_list['edittime'] = array('>=',$t_start_data);
		}else if(!empty($t_end_data) && empty($t_start_data)){
			$where_user_list['edittime'] = array('<=',$t_end_data);
		}else if(!empty($t_end_data) && !empty($t_end_data)){
			$where_user_list['edittime'] = array('>=',$t_start_data);
			$where_user_list2['edittime'] = array('<=',$t_end_data);
		}			
		/*排序*/	
		$order = 'edittime desc,id desc';
		/*条数*/
		$limit = $sline.','.$limit;
		/*查询*/
		$total = Db::name('chat')->distinct(true)->field('user_to_id')->where($where_user_list)->where($where_user_list2)->select();
		$total = count($total);
		$list = Db::name('chat')->distinct(true)->field('user_to_id')->where($where_user_list)->where($where_user_list2)->order($order)->limit($limit)->select();
		$lists = array();
		foreach($list as $k => $v){
			$lists[$k] = $v;
			/*查询用户身份信息*/
			$expert = Db::name('user')->field('realname,picture')->where('id='.$v['user_to_id'])->find();
			$expert_picture_arr = explode(',',$expert['picture']);
			$lists[$k]['realname'] = $expert['realname'];
			$lists[$k]['picture'] = $expert_picture_arr[0];
			/*获取最近一条聊天信息*/
			$GLOBALS['expert_id'] = $v['user_to_id'];
			$GLOBALS['user_id'] = $id;
			$chat_expert_list = Db::name('chat')->field('id,user_from_id,user_from_type,user_to_id,user_to_type,type,content,addtime')->where('type=1')->where(function($query){
					$query->where('user_from_id',$GLOBALS['expert_id'])->whereor('user_to_id',$GLOBALS['expert_id']);
				})->where(function($query){
					$query->where('user_from_id',$GLOBALS['user_id'])->whereor('user_to_id',$GLOBALS['user_id']);/*用户*/
				})->order('edittime desc,id desc')->find();
			$lists[$k]['chat'] = $chat_expert_list;
			$lists[$k]['chat']['addtime'] = date('y-m-d',$chat_expert_list['addtime']);	
		}
		/*处理结果集*/
		$result_data = array(
			'table' => array('患者咨询','咨询专家'),
			'total' => $total,
			'list'	=> $lists
		);
		return show(config('code.success'),'ok',$result_data);
	}

    /**
     * 咨询-添加好友
     */
    public function maillistadd(){
		/*用户ID*/
		$id = $this->user['id'];
		if(empty($id) || empty(input('user_to_id'))){
			return show(config('code.error'),'参数缺失',array());
		}
		$nowtime = time();
		$data = array(
			'user_id' 		=> $this->user['id'],
			'user_to_id' 	=> input('user_to_id'),
			'addtime' 		=> $nowtime,
			'edittime' 		=> $nowtime,	
		);
		/*查询*/	
		$where['user_id'] = $this->user['id'];
		$where['user_to_id'] = input('user_to_id');
		$where2['user_id'] = input('user_to_id');
		$where2['user_to_id'] = $this->user['id'];
		$GLOBALS['user_id'] = $id;
		$maillist = Db::name('maillist')->where(function($query) {
			$query->where('user_id',$GLOBALS['user_id'])->whereor('user_id',input('user_to_id'));
		})->where(function ($query) {
			$query->where('user_to_id',$GLOBALS['user_id'])->whereor('user_to_id',input('user_to_id'));
		})->find();			
		//$maillist = Db::name('maillist')->field('id,status')->where($where)->whereor($where2)->find();
		if(empty($maillist)){
			/*插入数据*/
			$result = Db::name('maillist')->insert($data);
			$data['id'] = Db::name('maillist')->getLastInsID();
		}else{
			if($maillist['status'] == 1){
				return show(config('code.success'),'ok,已经是好友',$data);
			}else{
				$data['status'] = 1;
				$result = Db::name('maillist')->where('id='.$maillist['id'])->update($data);
			}
		}
		if($result){
			return show(config('code.success'),'ok',$data);
		}else{
			return show(config('code.error'),'error',array());
		}
	}

    /**
     * 咨询-聊天-删除
     */
    public function chatdel(){
		/*用户ID*/
		$id = $this->user['id'];
		if(empty($id) || empty(input('user_to_id'))){
			return show(config('code.error'),'参数缺失',array());
		}
		$user_to_id = input('user_to_id');
		/*接收参数*/
		$GLOBALS['user_id'] = $id;/*发信人ID*/
		$GLOBALS['user_to_id'] = $user_to_id;/*收信人ID*/
		$result = Db::name('chat')->where(function($query){
					$query->where('user_from_id',$GLOBALS['user_id'])->whereor('user_to_id',$GLOBALS['user_id']);/*发信人id*/
				})->where(function($query){
					$query->where('user_from_id',$GLOBALS['user_to_id'])->whereor('user_to_id',$GLOBALS['user_to_id']);/*收信人id*/
				})->delete();
		if($result){
			return show(config('code.success'),'ok',$result);
		}else{
			return show(config('code.error'),'error',array());
		}
	}

    /**
     * 咨询-聊天-添加
     */
    public function chatadd(){
		/*用户ID*/
		$id = $this->user['id'];
		if(empty($id) || empty(input('user_to_type')) || empty(input('user_to_id')) || empty(input('type')) || empty(input('content'))){
			return show(config('code.error'),'参数缺失',array());
		}
		$nowtime = time();
		$data = array(
			'user_from_id' 	=> $this->user['id'],
			'user_from_type'=> 2,
			'user_to_id' 	=> input('user_to_id'),
			'type' 			=> input('type'),
			'content' 		=> input('content'),
			'addtime' 		=> $nowtime,
			'edittime' 		=> $nowtime,	
		);
		if(input('user_to_type') == 1){
			/*患者*/
			$data['user_to_type'] = 1;
		}else if(input('user_to_type') == 3){
			/*专家*/
			$data['user_to_type'] = 3;
		}else{
			return show(config('code.error'),'收信人类型非法',array());
		}
		/*插入数据*/
		$result = Db::name('chat')->insert($data);
		$data['id'] = Db::name('chat')->getLastInsID();
		if($result){
			return show(config('code.success'),'ok',$data);
		}else{
			return show(config('code.error'),'error',array());
		}
	}

   /**
     * 健康计划
     */
    public function health(){
		/*用户ID*/
		$id = $this->user['id'];
		/*获取参数*/	
		$sline = !empty(input('sline'))?input('sline'):0;
		$limit = !empty(input('limit'))?input('limit'):config('default_limit');
		/*查询聊天名师*/
		$where_list['user_id'] = $id;/*医生ID*/
		/*排序*/	
		$order = 'addtime desc,id desc';
		/*条数*/
		$limit = $sline.','.$limit;
		/*字段*/
		$field = 'id,user_id,user_to_id,title,name,addtime';
		/*查询聊天名师*/
		$total = Db::name('health_plan')->where($where_list)->count();
		$list = Db::name('health_plan')->field($field)->where($where_list)->order($order)->limit($limit)->select();
		$lists = array();
		foreach($list as $k => $v){
			$lists[$k] = $v;
			/*查询用户*/
			$user = Db::name('user')->field('nickname,picture')->where('id='.$v['user_to_id'])->find();
			$lists[$k]['nickname'] = $user['nickname'];			
			/*格式化时间*/	
			$lists[$k]['addtime'] = date('Y-m-d',$v['addtime']);
		}
		/*处理结果集*/
		$result_data = array(
			'total' => $total,
			'list'	=> $lists
		);
		return show(config('code.success'),'ok',$result_data);
	}

    /**
     * 健康计划-详情
     */
    public function healthcon(){
		/*ID*/
		$id = input('health_id');
		if(empty($id)){
			return show(config('code.error'),'参数缺失',array());
		}
		$field = 'id,user_id,user_to_id,title,name,sex,age,height,blood,content,addtime';
		$data = Db::name('health_plan')->field($field)->where('id='.$id)->find();
		if($data){
			return show(config('code.success'),'ok',$data);
		}else{
			return show(config('code.error'),'error',array());
		}	
	}
	
    /**
     * 健康计划-进展
     */
    public function healthevo(){
		/*ID*/
		$id = input('health_id');
		if(empty($id)){
			return show(config('code.error'),'参数缺失',array());
		}
		$field = 'days,content,addtime';
		$data = Db::name('health_plan_evolve')->field($field)->where('health_plan_id='.$id)->select();
		if($data){
			return show(config('code.success'),'ok',$data);
		}else{
			return show(config('code.error'),'error',array());
		}	
	}	

    /**
     * 健康计划-添加
     */
    public function healthadd(){
		$user = Db::name('user')->field('clinic_id')->where('id='.$this->user['id'])->find();
		if(empty($user['clinic_id'])){
			return show(config('code.error'),'诊所信息缺失',array());
		}
		/*参数校验*/
		if(empty(input('user_to_id')) || empty(input('title')) || empty(input('name'))){
			return show(config('code.error'),'参数缺失',array());
		}
		$nowtime = time();
		$data = array(
			'site_id'	=> $this->site_id,/*站点ID*/
			'clinic_id' => $user['clinic_id'],
			'user_id'	=> $this->user['id'],
			'user_to_id'=> input('user_to_id'),/*患者ID*/
			'title' 	=> input('title'),/*健康计划名*/
			'name' 		=> input('name'),/*姓名*/
			'addtime' 	=> $nowtime,
			'edittime' 	=> $nowtime
		);	
		if(!empty(input('sex'))){
			$data['sex'] = input('sex');/*性别 默认女:0|男为1 */
		}
		if(!empty(input('age'))){
			$data['age'] = input('age');/*年龄*/
		}
		if(!empty(input('height'))){
			$data['height'] = input('height');/*身高*/
		}
		if(!empty(input('blood'))){
			$data['blood'] = input('blood');/*血型*/
		}	
		if(!empty(input('content'))){
			$data['content'] = input('content');/*治疗方案*/
		}										
		/*插入数据*/
		$result = Db::name('health_plan')->insert($data);
		$data['id'] = Db::name('health_plan')->getLastInsID();
		if($result){
			return show(config('code.success'),'ok',$data);
		}else{
			return show(config('code.error'),'error',array());
		}	
	}

    /**
     * 健康计划-修改
     */
    public function healthedit(){
		/*ID*/
		$id = input('health_id');
		$nowtime = time();
		if(!empty(input('title'))){
			$data['title'] = input('title');/*健康计划名*/
		}
		if(!empty(input('name'))){
			$data['name'] = input('name');/*姓名*/
		}		
		if(!empty(input('sex'))){
			$data['sex'] = input('sex');/*性别 默认女:0|男为1 */
		}
		if(!empty(input('age'))){
			$data['age'] = input('age');/*年龄*/
		}
		if(!empty(input('height'))){
			$data['height'] = input('height');/*身高*/
		}
		if(!empty(input('blood'))){
			$data['blood'] = input('blood');/*血型*/
		}	
		if(!empty(input('content'))){
			$data['content'] = input('content');/*治疗方案*/
		}
		$data['edittime'] = $nowtime;								
		/*插入数据*/
		$result = Db::name('health_plan')->where('id='.$id)->update($data);
		if($result){
			return show(config('code.success'),'ok',$data);
		}else{
			return show(config('code.error'),'error',array());
		}
	}

    /**
     * 健康计划-进展-添加
     */
    public function healthevoadd(){
		$user = Db::name('user')->field('clinic_id')->where('id='.$this->user['id'])->find();
		if(empty($user['clinic_id'])){
			return show(config('code.error'),'诊所信息缺失',array());
		}
		/*参数校验*/
		if(empty(input('health_id')) || empty(input('days')) || empty(input('content'))){
			return show(config('code.error'),'参数缺失',array());
		}
		$nowtime = time();
		$data = array(
			'health_plan_id'=> input('health_id'),/*健康计划ID*/
			'days' 			=> input('days'),/*距上次天数*/
			'content' 		=> input('content'),/*健康状况*/
			'addtime' 		=> $nowtime,
			'edittime' 		=> $nowtime
		);										
		/*插入数据*/
		$result = Db::name('health_plan_evolve')->insert($data);
		$data['id'] = Db::name('health_plan_evolve')->getLastInsID();
		if($result){
			return show(config('code.success'),'ok',$data);
		}else{
			return show(config('code.error'),'error',array());
		}	
	}

    /**
     * 健康计划-进展-修改
     */
    public function healthevoedit(){
		/*ID*/
		$id = input('healthevo_id');
		$nowtime = time();
		if(!empty(input('days'))){
			$data['days'] = input('days');/*距上次天数*/
		}
		if(!empty(input('content'))){
			$data['content'] = input('content');/*健康状况*/
		}
		$data['edittime'] = $nowtime;								
		/*插入数据*/
		$result = Db::name('health_plan_evolve')->where('id='.$id)->update($data);
		if($result){
			return show(config('code.success'),'ok',$data);
		}else{
			return show(config('code.error'),'error',array());
		}
	}

   /**
     * 健康计划-按患者查询
     */
    public function healthlis(){
		if(empty(input('user_to_id'))){
			return show(config('code.error'),'参数缺失',array());
		}
		/*用户ID*/
		$id = $this->user['id'];
		/*获取参数*/	
		$sline = !empty(input('sline'))?input('sline'):0;
		$limit = !empty(input('limit'))?input('limit'):config('default_limit');
		/*查询聊天名师*/
		$where_list['user_id'] = $id;/*医生ID*/
		$where_list['user_to_id'] = input('user_to_id');/*患者ID*/
		/*排序*/
		$order = 'addtime desc,id desc';
		/*条数*/
		$limit = $sline.','.$limit;
		/*字段*/
		$field = 'id,user_id,user_to_id,title,name,addtime';
		/*查询聊天名师*/
		$total = Db::name('health_plan')->where($where_list)->count();
		$list = Db::name('health_plan')->field($field)->where($where_list)->order($order)->limit($limit)->select();
		$lists = array();
		foreach($list as $k => $v){
			$lists[$k] = $v;
			/*查询用户*/
			$user = Db::name('user')->field('nickname,picture')->where('id='.$v['user_to_id'])->find();
			$lists[$k]['nickname'] = $user['nickname'];			
			/*格式化时间*/	
			$lists[$k]['addtime'] = date('Y-m-d',$v['addtime']);
		}
		/*处理结果集*/
		$result_data = array(
			'total' => $total,
			'list'	=> $lists
		);
		return show(config('code.success'),'ok',$result_data);
	}

    /**
     * 患者档案
     */
    public function medical_record(){
		/*用户ID*/
		$id = $this->user['id'];
		if(empty($id)){
			return show(config('code.error'),'参数缺失',array());
		}
		/*获取参数*/	
		$sline = !empty(input('sline'))?input('sline'):0;
		$limit = !empty(input('limit'))?input('limit'):config('default_limit');
		/*查询*/
		$where_user_list['user_from_id'] = $id;/*信息发件人*/
		$where_user_list['user_to_type'] = 1;/*用户*/
		/*条件*/
		$where_user_list2 = array();
		if(!empty($t_start_data) && empty($t_end_data)){
			$where_user_list['edittime'] = array('>=',$t_start_data);
		}else if(!empty($t_end_data) && empty($t_start_data)){
			$where_user_list['edittime'] = array('<=',$t_end_data);
		}else if(!empty($t_end_data) && !empty($t_end_data)){
			$where_user_list['edittime'] = array('>=',$t_start_data);
			$where_user_list2['edittime'] = array('<=',$t_end_data);
		}			
		/*排序*/	
		$order = 'edittime desc,id desc';
		/*条数*/
		$limit = $sline.','.$limit;
		/*查询*/
		$total = Db::name('chat')->distinct(true)->field('user_to_id')->where($where_user_list)->where($where_user_list2)->select();
		$total = count($total);
		$list = Db::name('chat')->distinct(true)->field('user_to_id')->where($where_user_list)->where($where_user_list2)->order($order)->limit($limit)->select();
		$lists = array();
		foreach($list as $k => $v){
			$lists[$k] = $v;
			/*查询用户身份信息*/
			$expert = Db::name('user')->field('realname,picture')->where('id='.$v['user_to_id'])->find();
			$expert_picture_arr = explode(',',$expert['picture']);
			$lists[$k]['realname'] = $expert['realname'];
			$lists[$k]['picture'] = $expert_picture_arr[0];
			/*获取最近一条聊天信息
			$GLOBALS['expert_id'] = $v['user_to_id'];
			$GLOBALS['user_id'] = $id;
			$chat_expert_list = Db::name('chat')->field('id,user_from_id,user_from_type,user_to_id,user_to_type,type,content,addtime')->where('type=1')->where(function($query){
					$query->where('user_from_id',$GLOBALS['expert_id'])->whereor('user_to_id',$GLOBALS['expert_id']);
				})->where(function($query){
					$query->where('user_from_id',$GLOBALS['user_id'])->whereor('user_to_id',$GLOBALS['user_id']);
				})->order('edittime desc,id desc')->find();
			$lists[$k]['chat'] = $chat_expert_list;
			$lists[$k]['chat']['addtime'] = date('y-m-d',$chat_expert_list['addtime']);	
			*/
		}
		/*处理结果集*/
		$result_data = array(
			'total' => $total,
			'list'	=> $lists
		);
		return show(config('code.success'),'ok',$result_data);
	}

    /**
     * 就诊记录-列表
     */
    public function medical(){
		if(empty(input('user_id'))){
			return show(config('code.error'),'参数缺失',array());
		}
		/*用户ID*/
		$id = $this->user['id'];
		/*获取参数*/	
		$sline = !empty(input('sline'))?input('sline'):0;
		$limit = !empty(input('limit'))?input('limit'):config('default_limit');
		/*查询聊天名师*/
		$where_list['user_id'] = input('user_id');/*用户ID*/
		$where_list['doctor_ids'] = $id;
		/*排序*/	
		$order = 'addtime desc,id desc';
		/*条数*/
		$limit = $sline.','.$limit;
		/*字段*/
		$field = 'id,user_id,clinic_name,doctor_name,name,age,sex,jz_time,ky_time,content,bl_album,bg_album,cf_album,status,addtime';
		/*查询聊天名师*/
		$total = Db::name('medical_record')->where($where_list)->count();
		$list = Db::name('medical_record')->field($field)->where($where_list)->order($order)->limit($limit)->select();
		$lists = array();
		foreach($list as $k => $v){
			$lists[$k] = $v;
			/*格式化时间*/	
			$lists[$k]['jz_time'] = date('Y-m-d',$v['jz_time']);
			$lists[$k]['ky_time'] = date('Y-m-d',$v['ky_time']);
			$lists[$k]['addtime'] = date('Y-m-d',$v['addtime']);
			/*查询医生查看权限状态*/
			$where_use['medical_id'] = $v['id'];
			$where_use['user_id'] = $id;
			$use_result = Db::name('medical_use')->field('status')->where($where_use)->find();
			if($use_result){
				$use_status = $use_result['status'];
			}else{
				$use_status = 0;
			}			
			$lists[$k]['use_status'] = $use_status;			
		}
		/*处理结果集*/
		$result_data = array(
			'total' => $total,
			'list'	=> $lists
		);
		return show(config('code.success'),'ok',$result_data);
	}

    /**
     * 医生对病历的查看权限查询
     */
    public function medical_find(){
		if(empty(input('medical_id'))){
			return show(config('code.error'),'参数缺失',array());
		}
		/*用户ID*/
		$id = $this->user['id'];
		/*查询医生查看权限状态*/
		$where_use['medical_id'] = input('medical_id');
		$where_use['user_id'] = $id;
		$use_result = Db::name('medical_use')->field('status')->where($where_use)->find();
		if($use_result){
			$use_status = $use_result['status'];
		}else{
			$use_status = 0;
		}		
		return show(config('code.success'),'ok',$use_status);
	}

    /**
     * 就诊记录-添加
     */
    public function medicaladd(){
		if(empty(input('user_id'))){
			return show(config('code.error'),'参数缺失',array());
		}		
		/*ID*/
		if(empty(input('clinic_name')) || empty(input('doctor_name')) || empty(input('name'))){
			return show(config('code.error'),'参数缺失',array());
		}
		$nowtime = time();
		$data = array(
			'user_id'		=> input('user_id'),
			'doctor_ids' 	=> $this->user['id'],
			'clinic_name' 	=> input('clinic_name'),/*就诊机构*/
			'doctor_name' 	=> input('doctor_name'),/*问诊医生*/
			'name' 			=> input('name'),/*姓名*/
			'addtime' 		=> $nowtime,
			'edittime' 		=> $nowtime,			
		);
		if(!empty(input('age'))){
			$data['age'] = input('age');/*年龄*/
		}
		if(!empty(input('sex'))){
			$data['sex'] = input('sex');/*性别 默认女:0|男为1*/
		}
		if(!empty(input('jz_time'))){
			$data['jz_time'] = input('jz_time');/*就诊时间,10位Unix时间戳*/
		}
		if(!empty(input('ky_time'))){
			$data['ky_time'] = input('ky_time');/*开药时间,10位Unix时间戳*/
		}
		if(!empty(input('content'))){
			$data['content'] = input('content');/*就诊结果*/
		}
		if(!empty(input('bl_album'))){
			$data['bl_album'] = input('bl_album');/*病历相册,多图用逗号连接起来*/
		}
		if(!empty(input('bg_album'))){
			$data['bg_album'] = input('bg_album');/*报告相册,多图用逗号连接起来*/
		}
		if(!empty(input('cf_album'))){
			$data['cf_album'] = input('cf_album');/*处方相册,多图用逗号连接起来*/
		}				
		$result = Db::name('medical_record')->insert($data);
		$data['id'] = Db::name('medical_record')->getLastInsID();
		if($result){
			return show(config('code.success'),'ok',$data);
		}else{
			return show(config('code.error'),'error',array());
		}	
	}
	
    /**
     * 就诊记录-修改
     */
    public function medicaledit(){
		/*ID*/
		$id = input('medical_id');
		if(empty($id) || empty(input('clinic_name')) || empty(input('doctor_name')) || empty(input('name'))){
			return show(config('code.error'),'参数缺失',array());
		}
		$nowtime = time();
		$data = array(
			'clinic_name' 	=> input('clinic_name'),/*就诊机构*/
			'doctor_name' 	=> input('doctor_name'),/*问诊医生*/
			'name' 			=> input('name'),/*姓名*/
			'edittime' 		=> $nowtime,			
		);
		if(!empty(input('age'))){
			$data['age'] = input('age');/*年龄*/
		}
		if(!empty(input('sex'))){
			$data['sex'] = input('sex');/*性别 默认女:0|男为1*/
		}
		if(!empty(input('jz_time'))){
			$data['jz_time'] = input('jz_time');/*就诊时间,10位Unix时间戳*/
		}
		if(!empty(input('ky_time'))){
			$data['ky_time'] = input('ky_time');/*开药时间,10位Unix时间戳*/
		}
		if(!empty(input('content'))){
			$data['content'] = input('content');/*就诊结果*/
		}
		if(!empty(input('bl_album'))){
			$data['bl_album'] = input('bl_album');/*病历相册,多图用逗号连接起来*/
		}
		if(!empty(input('bg_album'))){
			$data['bg_album'] = input('bg_album');/*报告相册,多图用逗号连接起来*/
		}
		if(!empty(input('cf_album'))){
			$data['cf_album'] = input('cf_album');/*处方相册,多图用逗号连接起来*/
		}			
		$result = Db::name('medical_record')->where('id='.$id)->update($data);
		if($result){
			return show(config('code.success'),'ok',$data);
		}else{
			return show(config('code.error'),'error',array());
		}	
	}
	
    /**
     * 就诊记录-删除
     */
    public function medicaldel(){
		/*ID*/
		$id = input('medical_id');
		if(empty($id)){
			return show(config('code.error'),'参数缺失',array());
		}
		$result = Db::name('medical_record')->where('id='.$id)->delete();
		if($result){
			return show(config('code.success'),'ok',array());
		}else{
			return show(config('code.error'),'error',array());
		}	
	}

   /**
     * 医学堂
     */
    public function cms(){
		/*获取参数*/
		$tab = !empty(input('tab'))?input('tab'):1;/*选项卡*/
		$sline = !empty(input('sline'))?input('sline'):0;
		$limit = !empty(input('limit'))?input('limit'):config('default_limit');
		/*条数*/
		$limit = $sline.','.$limit;		
		/*查询*/
		$where_list['site_id'] = $this->site_id;
		$where_list['status']  = 1;/*状态*/
		$search = input('search');/*搜索*/
		if(!empty($search)) {
			$where_list['concat(name,description)'] = array('like','%'.$search.'%');
		}
		/*排序*/	
		$order = '`order` desc,edittime desc';
		/*选项卡*/
		if($tab == 2){
			$where_list['channel_id'] = array('in','25');/*视频*/
		}else if($tab == 1){
			$where_list['channel_id'] = array('in','21');/*文章*/
		}else{
			$where_list['channel_id'] = array('in','21,25');/*文章|视频*/
		}
		/*字段*/
		$category_id = 113;/*医学堂*/
		$field = 'id,name,description,picture,video,visitor,edittime';
		if ($category_id > 0) {
			/*组合当前分类及子分类ID集*/
			$category_where['site_id'] = $this->site_id;
			$category_where['status'] = 1;
			$category_where['path'] = array('like', '%,' . $category_id . ',%');
			$categorys = Db::name('category')->where($category_where)->select();
			$category_str = $category_id;
			if ($categorys) {
				foreach ($categorys as $k => $v) {
					$category_str.= ',' . $v['id'];
				}
			}
		}else{
			$category_str = '';
			foreach($category as $k => $v) {
				$category_str.= ','.$v['id'];
			}
			$category_str = trim($category_str,',');
		}
		$where_list['category_id'] = array('in',$category_str); /*搜索*/		
		/*查询聊天名师*/
		$total = Db::name('content')->where($where_list)->count();
		$list = Db::name('content')->field($field)->where($where_list)->order($order)->limit($limit)->select();
		$lists = array();
		foreach($list as $k => $v){
			$lists[$k] = $v;
			/*格式化时间*/
			$lists[$k]['edittime'] = date('Y-m-d',$v['edittime']);
			/*查询点赞数量*/
			$where_like['content_id'] = $v['id']; 
			$where_like['status'] = 1;/*已赞*/
			$lists[$k]['total_like'] = Db::name('like')->where($where_like)->count();
			/*查询点赞数量*/
			$where_comment['content_id'] = $v['id']; 
			$where_comment['status'] = 1;/*启用*/
			$lists[$k]['total_comment'] = Db::name('comment')->where($where_comment)->count();
			/*查询用户点赞状态*/
			$like_where['user_id'] = $this->user['id'];
			$like_where['content_id'] = $v['id'];
			$like_result = Db::name('like')->field('status')->where($like_where)->find();
			if($like_result){
				$like_status = $like_result['status'];
			}else{
				$like_status = 0;
			}
			$lists[$k]['like_status'] = $like_status;			
		}
		/*处理结果集*/
		$result_data = array(
			'title' => '医学堂',
			'table' => array('文章','视频'),
			'total' => $total,
			'list'	=> $lists
		);
		return show(config('code.success'),'ok',$result_data);
	}

    /**
     * 医学堂-内容点赞状态查询
     */
    public function cms_likefind(){
		/*ID*/
		$id = input('content_id');
		if(empty($id) || empty($this->user['id'])){
			return show(config('code.error'),'参数缺失',array());
		}
		/*查询初始状态*/
		$where['user_id'] = $this->user['id'];
		$where['content_id'] = $id;
		$result = Db::name('like')->field('id,status')->where($where)->find();
		/*返回结果集*/
		if($result){
			return show(config('code.success'),'ok',$result);
		}else{
			return show(config('code.error'),'error',array());
		}		
	}

    /**
     * 医学堂-内容点赞|取消点赞
     */
    public function cms_like(){
		/*ID*/
		$id = input('content_id');
		if(empty($id) || empty($this->user['id'])){
			return show(config('code.error'),'参数缺失',array());
		}
		/*组装参数*/
		$nowtime = time();
		$data = array(
			'user_id' 		=> $this->user['id'],/*用户ID*/
			'content_id' 	=> $id,/*内容*/
			'edittime' 		=> $nowtime,			
		);
		/*查询初始状态*/
		$where['user_id'] = $this->user['id'];
		$where['content_id'] = $id;
		$like = Db::name('like')->field('id,status')->where($where)->find();
		if(empty($like)){
			/*不存在或为取消状态*/
			$data['addtime'] = $nowtime;
			$data['status'] = 1;
			$result = Db::name('like')->insert($data);
			$data['id'] = Db::name('like')->getLastInsID();
		}else{
			if($like['status'] == 1){
				/*已经是点赞状态*/
				$data['status'] = 0;
				$result = Db::name('like')->where('id='.$like['id'])->update($data);
			}else{
				/*取消点赞状态*/
				$data['status'] = 1;
				$result = Db::name('like')->where('id='.$like['id'])->update($data);
			}
			$data['like_id'] = $like['id'];
		}
		/*返回结果集*/
		if($result){
			return show(config('code.success'),'ok',$data);
		}else{
			return show(config('code.error'),'error',array());
		}		
	}

    /**
     * 医学堂-内容评论添加
     */
    public function cms_commentadd(){
		/*ID*/
		if(empty(input('content_id')) || empty(input('content'))){
			return show(config('code.error'),'参数缺失',array());
		}
		$nowtime = time();
		$data = array(
			'user_id' 		=> $this->user['id'],
			'content_id' 	=> input('content_id'),
			'content' 		=> input('content'),/*内容*/
			'addtime' 		=> $nowtime,
			'edittime' 		=> $nowtime,	
		);
		if(!empty(input('name'))){
			$data['name'] = input('name');
		}
		if(!empty(input('picture'))){
			$data['picture'] = input('picture');
		}
		if(!empty(input('description'))){
			$data['description'] = input('description');
		}		
		/*插入数据*/
		$result = Db::name('comment')->insert($data);
		$data['id'] = Db::name('comment')->getLastInsID();
		if($result){
			return show(config('code.success'),'ok',$data);
		}else{
			return show(config('code.error'),'error',array());
		}	
	}
	
   /**
     * 社区-圈子
     */
    public function bbs_info(){
		if(empty(input('tab'))){
			return show(config('code.error'),'参数缺失',array());
		}		
		/*获取参数*/
		$sline = !empty(input('sline'))?input('sline'):0;
		$limit = !empty(input('limit'))?input('limit'):config('default_limit');
		/*条数*/
		$limit = $sline.','.$limit;		
		/*查询*/
		$where_list['site_id'] = $this->site_id;
		if(input('tab') == 1){
			$where_list['type']  = 1;/*类型  默认为1:医患圈|2:专家圈*/
		}else if(input('tab') == 2){
			$where_list['type']  = 2;/*类型  默认为1:医患圈|2:专家圈*/
		}else if(input('tab') == 3){
			$where_list['type']  = array('in','1,2');/*类型  默认为1:医患圈|2:专家圈*/
		}else{
			return show(config('code.error'),'选项卡参数非法',array());
		}
		$where_list['status']  = 1;/*状态*/
		$search = input('search');/*搜索*/
		if(!empty($search)) {
			$where_list['concat(name,description)'] = array('like','%'.$search.'%');
		}			
		/*排序*/	
		$order = '`order` desc,edittime desc';
		/*字段*/
		$field = 'id,user_id,content,album,edittime';
		/*查询聊天名师*/
		$total = Db::name('bbs_info')->where($where_list)->count();
		$list = Db::name('bbs_info')->field($field)->where($where_list)->order($order)->limit($limit)->select();
		$lists = array();
		foreach($list as $k => $v){
			$lists[$k] = $v;
			/*格式化时间*/
			$lists[$k]['edittime'] = date('Y-m-d',$v['edittime']);
			/*查询点赞数量*/
			$where_like['bbs_info_id'] = $v['id']; 
			$where_like['status'] = 1;/*已赞*/
			$lists[$k]['total_like'] = Db::name('bbs_like')->where($where_like)->count();
			/*查询评论数量*/
			$where_comment['bbs_info_id'] = $v['id']; 
			$where_comment['status'] = 1;/*启用*/
			$lists[$k]['total_comment'] = Db::name('bbs_comment')->where($where_comment)->count();
			/*查询用户信息*/
			$user = Db::name('user')->field('realname,picture')->where('id='.$v['user_id'])->find();
			$user_picture_arr = explode(',',$user['picture']);
			$lists[$k]['user_info']['realname'] = $user['realname'];
			$lists[$k]['user_info']['picture'] = $user_picture_arr[0];				
			/*查询用户点赞状态*/
			$like_where['user_id'] = $this->user['id'];
			$like_where['bbs_info_id'] = $v['id'];
			$like_result = Db::name('bbs_like')->field('status')->where($like_where)->find();
			if($like_result){
				$like_status = $like_result['status'];
			}else{
				$like_status = 0;
			}			
			$lists[$k]['like_status'] = $like_status;
			/*查询所有点赞用户*/
			$where_bbs_info['bbs_info_id'] = $v['id'];
			$where_bbs_info['status'] = 1;
			$bbs_like = Db::name('bbs_like')->field('user_id')->where($where_bbs_info)->select();
			if(!empty($bbs_like)){
				foreach($bbs_like as $k2=>$v2){
					/*查询用户身份信息*/
					$user = Db::name('user')->field('realname')->where('id='.$v2['user_id'])->find();
					$bbs_like[$k2]['realname'] = $user['realname'];			
				}
			}
			$lists[$k]['bbs_like'] = !empty($bbs_like)?$bbs_like:array();	
			$lists[$k]['bbs_comment'] = array();	
			/*查询信息评论列表*/
			$where_bbscomment_list['bbs_info_id'] = $v['id'];/*查询内容*/
			$where_bbscomment_list['status']  = 1;/*状态*/
			$list_bbscomment = Db::name('bbs_comment')->field('id,user_id,content,addtime')->where($where_bbscomment_list)->order('`order` desc,edittime desc')->limit(20)->select();
			if(!empty($list_bbscomment)){
				foreach($list_bbscomment as $k2 => $v2){
					/*查询用户身份信息*/
					$user = Db::name('user')->field('realname')->where('id='.$v2['user_id'])->find();
					$list_bbscomment[$k2]['realname'] = $user['realname'];						
					/*格式化时间*/
					$list_bbscomment[$k2]['addtime'] = date('Y-m-d',$v2['addtime']);		
				}
			}
			$lists[$k]['bbs_comment'] = !empty($list_bbscomment)?$list_bbscomment:array();						
		}
		/*处理结果集*/
		$result_data = array(
			'total' => $total,
			'list'	=> $lists
		);
		return show(config('code.success'),'ok',$result_data);
	}

   /**
     * 社区-圈子-我发布的
     */
    public function bbs_infome(){
		if(empty(input('tab'))){
			return show(config('code.error'),'参数缺失',array());
		}		
		/*用户ID*/
		$id = $this->user['id'];
		if(empty($id)){
			return show(config('code.error'),'参数缺失',array());
		}		
		/*获取参数*/
		$sline = !empty(input('sline'))?input('sline'):0;
		$limit = !empty(input('limit'))?input('limit'):config('default_limit');
		/*条数*/
		$limit = $sline.','.$limit;		
		/*查询*/
		$where_list['site_id'] = $this->site_id;
		$where_list['user_id'] = $id;
		if(input('tab') == 1){
			$where_list['type']  = 1;/*类型  默认为1:医患圈|2:专家圈*/
		}else if(input('tab') == 2){
			$where_list['type']  = 2;/*类型  默认为1:医患圈|2:专家圈*/
		}else if(input('tab') == 3){
			$where_list['type']  = array('in','1,2');/*类型  默认为1:医患圈|2:专家圈*/
		}else{
			return show(config('code.error'),'选项卡参数非法',array());
		}
		$where_list['status']  = 1;/*状态*/
		$search = input('search');/*搜索*/
		if(!empty($search)) {
			$where_list['concat(name,description)'] = array('like','%'.$search.'%');
		}			
		/*排序*/	
		$order = '`order` desc,edittime desc';
		/*字段*/
		$field = 'id,user_id,content,album,edittime';
		/*查询聊天名师*/
		$total = Db::name('bbs_info')->where($where_list)->count();
		$list = Db::name('bbs_info')->field($field)->where($where_list)->order($order)->limit($limit)->select();
		$lists = array();
		foreach($list as $k => $v){
			$lists[$k] = $v;
			/*格式化时间*/
			$lists[$k]['edittime'] = date('Y-m-d',$v['edittime']);
			/*查询点赞数量*/
			$where_like['bbs_info_id'] = $v['id']; 
			$where_like['status'] = 1;/*已赞*/
			$lists[$k]['total_like'] = Db::name('bbs_like')->where($where_like)->count();
			/*查询评论数量*/
			$where_comment['bbs_info_id'] = $v['id']; 
			$where_comment['status'] = 1;/*启用*/
			$lists[$k]['total_comment'] = Db::name('bbs_comment')->where($where_comment)->count();
			/*查询用户信息*/
			$user = Db::name('user')->field('realname,picture')->where('id='.$v['user_id'])->find();
			$user_picture_arr = explode(',',$user['picture']);
			$lists[$k]['user_info']['realname'] = $user['realname'];
			$lists[$k]['user_info']['picture'] = $user_picture_arr[0];			
			/*查询用户点赞状态*/
			$like_where['user_id'] = $this->user['id'];
			$like_where['bbs_info_id'] = $v['id'];
			$like_result = Db::name('bbs_like')->field('status')->where($like_where)->find();
			if($like_result){
				$like_status = $like_result['status'];
			}else{
				$like_status = 0;
			}			
			$lists[$k]['like_status'] = $like_status;
			/*查询所有点赞用户*/
			$where_bbs_info['bbs_info_id'] = $v['id'];
			$where_bbs_info['status'] = 1;
			$bbs_like = Db::name('bbs_like')->field('user_id')->where($where_bbs_info)->select();
			if(!empty($bbs_like)){
				foreach($bbs_like as $k2=>$v2){
					/*查询用户身份信息*/
					$user = Db::name('user')->field('realname')->where('id='.$v2['user_id'])->find();
					$bbs_like[$k2]['realname'] = $user['realname'];			
				}
			}
			$lists[$k]['bbs_like'] = !empty($bbs_like)?$bbs_like:array();	
			$lists[$k]['bbs_comment'] = array();	
			/*查询信息评论列表*/
			$where_bbscomment_list['bbs_info_id'] = $v['id'];/*查询内容*/
			$where_bbscomment_list['status']  = 1;/*状态*/
			$list_bbscomment = Db::name('bbs_comment')->field('id,user_id,content,addtime')->where($where_bbscomment_list)->order('`order` desc,edittime desc')->limit(20)->select();
			if(!empty($list_bbscomment)){
				foreach($list_bbscomment as $k2 => $v2){
					/*查询用户身份信息*/
					$user = Db::name('user')->field('realname')->where('id='.$v2['user_id'])->find();
					$list_bbscomment[$k2]['realname'] = $user['realname'];						
					/*格式化时间*/
					$list_bbscomment[$k2]['addtime'] = date('Y-m-d',$v2['addtime']);		
				}
			}
			$lists[$k]['bbs_comment'] = !empty($list_bbscomment)?$list_bbscomment:array();				
		}
		/*处理结果集*/
		$result_data = array(
			'total' => $total,
			'list'	=> $lists
		);
		return show(config('code.success'),'ok',$result_data);
	}

    /**
     * 社区-圈子-发圈
     */
    public function bbs_infoadd(){
		/*用户ID*/
		$id = $this->user['id'];
		if(empty($id) || empty(input('type')) || (empty(input('album')) && empty(input('content')))){
			return show(config('code.error'),'参数缺失',array());
		}
		$nowtime = time();
		$data = array(
			'site_id'		=> $this->site_id,
			'user_id' 		=> $this->user['id'],
			'type'			=> input('type'),/*类型  默认为1:医患圈|2:专家圈*/
			'addtime' 		=> $nowtime,
			'edittime' 		=> $nowtime,	
		);
		if(!empty(input('album'))){
			$data['album'] = input('album');
		}
		if(!empty(input('content'))){
			$data['content'] = input('content');
		}		
		/*插入数据*/
		$result = Db::name('bbs_info')->insert($data);
		$data['id'] = Db::name('bbs_info')->getLastInsID();
		if($result){
			return show(config('code.success'),'ok',$data);
		}else{
			return show(config('code.error'),'error',array());
		}
	}

   /**
     * 社区-登录用户点赞状态查询
     */
    public function bbs_likefind(){
		/*ID*/
		$id = input('bbs_info_id');
		if(empty($id) || empty($this->user['id'])){
			return show(config('code.error'),'参数缺失',array());
		}
		/*查询初始状态*/
		$where['user_id'] = $this->user['id'];
		$where['bbs_info_id'] = $id;
		$result = Db::name('bbs_like')->field('id,status')->where($where)->find();
		/*返回结果集*/
		if($result){
			return show(config('code.success'),'ok',$result);
		}else{
			return show(config('code.error'),'error',array());
		}		
	}

    /**
     * 社区-信息点赞|取消点赞
     */
    public function bbs_like(){
		/*ID*/
		$id = input('bbs_info_id');
		if(empty($id) || empty($this->user['id'])){
			return show(config('code.error'),'参数缺失',array());
		}
		/*组装参数*/
		$nowtime = time();
		$data = array(
			'user_id' 		=> $this->user['id'],/*用户ID*/
			'bbs_info_id' 	=> $id,/*内容*/
			'edittime' 		=> $nowtime,			
		);
		/*查询初始状态*/
		$where['user_id'] = $this->user['id'];
		$where['bbs_info_id'] = $id;
		$bbs_like = Db::name('bbs_like')->field('id,status')->where($where)->find();
		if(empty($bbs_like)){
			/*不存在或为取消状态*/
			$data['addtime'] = $nowtime;
			$data['status'] = 1;
			$result = Db::name('bbs_like')->insert($data);
			$data['bbs_like_id'] = Db::name('bbs_like')->getLastInsID();
		}else{
			if($bbs_like['status'] == 1){
				/*已经是点赞状态*/
				$data['status'] = 0;
				$result = Db::name('bbs_like')->where('id='.$bbs_like['id'])->update($data);
			}else{
				/*取消点赞状态*/
				$data['status'] = 1;
				$result = Db::name('bbs_like')->where('id='.$bbs_like['id'])->update($data);
			}
			$data['bbs_like_id'] = $bbs_like['id'];
		}
		/*返回结果集*/
		if($result){
			return show(config('code.success'),'ok',$data);
		}else{
			return show(config('code.error'),'error',array());
		}		
	}

    /**
     * 社区-信息评论添加
     */
    public function bbs_commentadd(){
		/*ID*/
		if(empty(input('bbs_info_id')) || empty(input('content'))){
			return show(config('code.error'),'参数缺失',array());
		}
		$nowtime = time();
		$data = array(
			'user_id' 		=> $this->user['id'],
			'bbs_info_id' 	=> input('bbs_info_id'),
			'content' 		=> input('content'),/*内容*/
			'addtime' 		=> $nowtime,
			'edittime' 		=> $nowtime,	
		);
		if(!empty(input('name'))){
			$data['name'] = input('name');
		}
		if(!empty(input('picture'))){
			$data['picture'] = input('picture');
		}
		if(!empty(input('description'))){
			$data['description'] = input('description');
		}		
		/*插入数据*/
		$result = Db::name('bbs_comment')->insert($data);
		$data['id'] = Db::name('bbs_comment')->getLastInsID();
		if($result){
			return show(config('code.success'),'ok',$data);
		}else{
			return show(config('code.error'),'error',array());
		}	
	}

    /**
     * 管理-药品库存-分类添加
     */
    public function categoryadd(){
		$user = Db::name('user')->field('clinic_id')->where('id='.$this->user['id'])->find();
		/*所属诊所*/
		$clinic_id = $user['clinic_id'];		
		if(empty($clinic_id) || (empty(input('pids')) && input('pids')!=0) || empty(input('name'))){
			return show(config('code.error'),'参数缺失',array());
		}
		$pids = !empty(input('pids'))?input('pids'):0;
		/*获取参数*/
		$nowtime = time();
		/*组装参数*/
		$data = array(
			'site_id' 		=> $this->site_id,/*站点ID*/
			'channel_id' 	=> 22,/*频道ID*/
			'clinic_id' 	=> $clinic_id,/*诊所ID*/
			'pid' 			=> $pids, /*父结点ID*/
			'name' 			=> input('name'),/*名称*/
			'edittime' 		=> $nowtime,/*修改时间*/
		);
		if(!empty(input('ico'))){
			$data['ico'] = input('ico');/*图标*/
		}
		if(!empty(input('picture'))){
			$data['picture'] = input('picture');/*缩略图*/
		}
		if(!empty(input('description'))){
			$data['description'] = input('description');/*简介*/
		}
		if(!empty(input('content'))){
			$data['content'] = input('content');/*内容*/
		}			
		if(input('pids') == 0) {
			$data['path'] = '0,';
		}else{
			$path = Db::name('category')->field('path')->where('id='.$pids)->find();
			if(empty($path)){
				return show(config('code.error'),'该父类不存在',array());
			}
			$data['path'] = $path['path'].$pids.',';
		}				
		/*添加*/
		$data['addtime'] = $nowtime; /*添加时间*/
		/*插入数据*/
		$result = Db::name('category')->insert($data);
		$data['id'] = Db::name('category')->getLastInsID();
		/*判断结果集*/
		if($result){
			return show(config('code.success'),'ok',$data);
		}else{
			return show(config('code.error'),'error',array());
		}		
	}

    /**
     * 管理-药品库存-分类修改
     */
    public function categoryedit(){
		if(empty(input('category_id'))){
			return show(config('code.error'),'参数缺失',array());
		}
		$category_id = input('category_id');
		/*获取参数*/
		$nowtime = time();
		/*组装参数*/
		$data = array(
			'edittime' => $nowtime,/*修改时间*/
		);
		if(!empty(input('name'))){
			$data['name'] = input('name');/*名称*/
		}
		if(!empty(input('ico'))){
			$data['ico'] = input('ico');/*图标*/
		}
		if(!empty(input('picture'))){
			$data['picture'] = input('picture');/*缩略图*/
		}
		if(!empty(input('description'))){
			$data['description'] = input('description');/*简介*/
		}
		if(!empty(input('content'))){
			$data['content'] = input('content');/*内容*/
		}			
		/*更新数据*/
		$result = Db::name('category')->where('id='.$category_id)->update($data);
		/*判断结果集*/
		if($result){
			return show(config('code.success'),'ok',$data);
		}else{
			return show(config('code.error'),'error',array());
		}		
	}

    /**
     * 管理-药品库存-分类删除
     */
    public function categorydel(){
		if(empty(input('category_id'))){
			return show(config('code.error'),'参数缺失',array());
		}
		$category_id = input('category_id');		
		/*判断是否存在子分类*/
		$where['path'] = array('like','%,'.$category_id.',%');
		$where['id'] = array('notin',$category_id);
		$content = Db::name('category')->where($where)->find();
		if ($content) {
			/*存在子类*/
			return show(config('code.error'),'抱歉，存在子分类，不允许删除！',array());
		} else {
			/*不存在子类*/
			$result = Db::name('category')->where('id='.$category_id)->delete();
			if($result){
				return show(config('code.success'),'ok',$result);
			}else{
				return show(config('code.error'),'error',array());
			}
		}
	}

    /**
     * 管理-药品库存-分类查询
     */
    public function categorylis(){
		$user = Db::name('user')->field('clinic_id')->where('id='.$this->user['id'])->find();
		/*所属诊所*/
		$clinic_id = $user['clinic_id'];		
		if(empty($clinic_id)){
			return show(config('code.error'),'参数缺失',array());
		}		
		/*查询分类信息*/
		$category_where['site_id'] = $this->site_id;
		$category_where['clinic_id'] = $clinic_id;
		$category_where['status'] = 1;
		$field = 'id,pid,path,name';
		$category = Db::name('category')->field($field)->where($category_where)->order('concat(`path`,`id`)')->select();
		if(!empty($category)){
			foreach ($category as $k => $v) {
				/*名称*/
				$path_arr = explode(',', trim($v['path'], ','));
				$path_arr_count = count($path_arr);
				$category[$k]['path_count'] = $path_arr_count; //层级数
				$path_str = '';
				if ($path_arr_count > 1) {
					for ($i = 1;$i < $path_arr_count;$i++) {
						$path_str.= '&nbsp;';
					}
					$path_str.= $path_str . '└';
				}
				$category[$k]['name'] = $path_str . $v['name'];
			}
			return show(config('code.success'),'ok',$category);		
		}else{
			return show(config('code.error'),'error',array());
		}
	}

    /**
     * 管理-药品库存-药品添加
     */
    public function productadd(){	
		if(empty(input('category_id')) || empty(input('name')) || empty(input('total'))){
			return show(config('code.error'),'参数缺失',array());
		}
		/*获取参数*/
		$nowtime = time();
		/*组装参数*/
		$data = array(
			'site_id' 		=> $this->site_id,/*站点ID*/
			'category_id' 	=> input('category_id'),/*分类ID*/
			//'sendtemp_id' => input('sendtemp'), /*运费模板ID*/
			//'type'		=> json_encode($_POST['type']),/*类型json集((0,颜色[红、黄]),(1,材质[棉、麻]))*/
			//'attr' 		=> json_encode($_POST['attr']),/*属性json集((0,人群[皆宜]),(1,爱好[微应用]))*/
			//'val' 		=> json_encode($_POST['val']),/*参数json集((0,人群[皆宜]),(1,爱好[微应用]))*/
			'name' 			=> input('name'),/*名称*/
			'total' 		=> input('total'),/*库存*/
			//'ico' 		=> input('ico'),/*图标*/
			'price' 		=> !empty(input('price'))?input('price'):0,/*售价*/
			'price_original'=> !empty(input('price'))?input('price'):0,/*原价*/
			'price_enter' 	=> !empty(input('price'))?input('price'):0,/*进价*/
			//'video'		=> input('video'),/*视频*/
			//'resource'	=> input('resource'),/*资源*/
			//'url' 		=> input('url'),/*链接*/
			//'author' 		=> input('author'),/*作者*/
			//'source' 		=> input('source'),/*来源*/
			//'sales' 		=> input('sales'),/*销量*/
			//'visitor' 	=> input('visitor'),/*访问量*/
			//'order' 		=> input('order'),/*排序 默认为100*/
			//'status' 		=> input('status'),/*状态 默认为1:启用|0:禁用*/
			'edittime' 		=> $nowtime,/*修改时间*/
		);
		if(!empty(input('picture'))){
			$data['picture'] = input('picture');/*缩略图*/
		}
		if(!empty(input('description'))){
			$data['description'] = input('description');/*简介*/
		}
		if(!empty(input('content'))){
			$data['content'] = input('content');/*内容*/
		}			
		/*添加*/
		$data['addtime'] = $nowtime; /*添加时间*/
		/*插入数据*/
		$result = Db::name('content')->insert($data);
		$data['id'] = Db::name('content')->getLastInsID();
		/*判断结果集*/
		if($result){
			return show(config('code.success'),'ok',$data);
		}else{
			return show(config('code.error'),'error',array());
		}		
	}

    /**
     * 管理-药品库存-药品修改
     */
    public function productedit(){	
		if(empty(input('producte_id'))){
			return show(config('code.error'),'参数缺失',array());
		}
		$producte_id = input('producte_id');
		/*获取参数*/
		$nowtime = time();
		/*组装参数*/
		$data = array(
			'edittime' 	=> $nowtime,/*修改时间*/
		);
		if(!empty(input('name'))){
			$data['name'] = input('name');/*名称*/
		}
		if(!empty(input('total'))){
			$data['total'] = input('total');/*库存*/
		}
		if(!empty(input('price'))){
			$data['price'] = input('price');/*售价*/
		}
		if(!empty(input('price_original'))){
			$data['price_original'] = input('price_original');/*原价*/
		}
		if(!empty(input('price_enter'))){
			$data['price_enter'] = input('price_enter');/*进价*/
		}
		if(!empty(input('picture'))){
			$data['picture'] = input('picture');/*缩略图*/
		}
		if(!empty(input('description'))){
			$data['description'] = input('description');/*简介*/
		}
		if(!empty(input('content'))){
			$data['content'] = input('content');/*内容*/
		}			
		/*插入数据*/
		$result = Db::name('content')->where('id='.$producte_id)->update($data);
		/*判断结果集*/
		if($result){
			return show(config('code.success'),'ok',$data);
		}else{
			return show(config('code.error'),'error',array());
		}		
	}

    /**
     * 管理-药品库存-药品删除
     */
    public function productdel(){
		if(empty(input('producte_id'))){
			return show(config('code.error'),'参数缺失',array());
		}
		$producte_id = input('producte_id');		
		$result = Db::name('content')->where('id='.$producte_id)->delete();
		if($result){
			return show(config('code.success'),'ok',$result);
		}else{
			return show(config('code.error'),'error',array());
		}
	}

    /**
     * 管理-药品库存-药品列表
     */
    public function productlis(){
		$user = Db::name('user')->field('clinic_id')->where('id='.$this->user['id'])->find();
		/*所属诊所*/
		$clinic_id = $user['clinic_id'];		
		if(empty($clinic_id)){
			return show(config('code.error'),'参数缺失',array());
		}		
		/*获取参数*/
		$sline = !empty(input('sline'))?input('sline'):0;
		$limit = !empty(input('limit'))?input('limit'):config('default_limit');
		/*条数*/
		$limit = $sline.','.$limit;		
		/*查询*/
		$where_list['site_id'] = $this->site_id;
		$where_list['status']  = 1;/*状态*/
		$search = input('search');/*搜索*/
		if(!empty($search)) {
			$where_list['concat(name,description)'] = array('like','%'.$search.'%');
		}
		/*查询分类信息*/
		$category_where['site_id'] = $this->site_id;
		$category_where['clinic_id'] = $clinic_id;
		$category_where['status'] = 1;
		$category = Db::name('category')->field('id')->where($category_where)->order('concat(`path`,`id`)')->select();
		$category_str = '';
		foreach($category as $k => $v) {
			$category_str.= ','.$v['id'];
		}
		$category_str = trim($category_str,',');
		$where_list['category_id'] = array('in',$category_str); /*搜索*/		
		/*排序*/	
		$order = '`order` desc,edittime desc';
		$where_list['channel_id'] = array('in','22');/*药品*/
		/*字段*/
		$field = 'id,category_id,name,description,picture,total,edittime';
		/*查询聊天名师*/
		$total = Db::name('content')->where($where_list)->count();
		$list = Db::name('content')->field($field)->where($where_list)->order($order)->limit($limit)->select();
		$lists = array();
		foreach($list as $k => $v){
			$lists[$k] = $v;
			/*格式化时间*/
			$lists[$k]['edittime'] = date('Y-m-d',$v['edittime']);
			/*查询分类信息*/
			$category2 = Db::name('category')->field('id,pid,path,name')->where('id='.$v['category_id'])->order('concat(`path`,`id`)')->select();
			$lists[$k]['category'] = $category2;
		}
		/*处理结果集*/
		$result_data = array(
			'total' => $total,
			'list'	=> $lists
		);
		return show(config('code.success'),'ok',$result_data);
	}

    /**
     * 管理-药品库存-药品详情
     */
    public function productcon(){
		if(empty(input('producte_id'))){
			return show(config('code.error'),'参数缺失',array());
		}
		$producte_id = input('producte_id');
		$field = 'name,total,price,price_original,price_enter,picture,description,content';	
		$result = Db::name('content')->field($field)->where('id='.$producte_id)->find();
		if($result){
			return show(config('code.success'),'ok',$result);
		}else{
			return show(config('code.error'),'error',array());
		}
	}

    /**
     * 管理-备忘录-列表
     */
    public function memolis(){
		$user = Db::name('user')->field('clinic_id')->where('id='.$this->user['id'])->find();
		/*所属诊所*/
		$clinic_id = $user['clinic_id'];		
		if(empty($clinic_id)){
			return show(config('code.error'),'参数缺失',array());
		}		
		/*获取参数*/
		$sline = !empty(input('sline'))?input('sline'):0;
		$limit = !empty(input('limit'))?input('limit'):config('default_limit');
		/*条数*/
		$limit = $sline.','.$limit;		
		/*查询*/
		$where_list['site_id'] = $this->site_id;
		$where_list['clinic_id'] = $clinic_id;		
		$where_list['status']  = 1;/*状态*/
		$search = input('search');/*搜索*/
		if(!empty($search)) {
			$where_list['concat(id,content)'] = array('like','%'.$search.'%');
		}
		$order = array('alarm'=>'asc','order'=>'desc','id'=>'desc');
		/*字段*/
		$field = 'id,alarm,content,edittime';
		/*查询聊天名师*/
		$total = Db::name('clinic_memo')->where($where_list)->count();
		$list = Db::name('clinic_memo')->field($field)->where($where_list)->order($order)->limit($limit)->select();
		$lists = array();
		foreach($list as $k => $v){
			$lists[$k] = $v;
			/*格式化时间*/
			$lists[$k]['edittime'] = date('Y-m-d',$v['edittime']);
		}
		/*处理结果集*/
		$result_data = array(
			'total' => $total,
			'list'	=> $lists
		);
		return show(config('code.success'),'ok',$result_data);
	}

    /**
     * 管理-备忘录-添加
     */
    public function memoadd(){
		$user = Db::name('user')->field('clinic_id')->where('id='.$this->user['id'])->find();
		/*所属诊所*/
		$clinic_id = $user['clinic_id'];		
		if(empty($clinic_id) || empty(input('alarm')) || empty(input('content'))){
			return show(config('code.error'),'参数缺失',array());
		}
		/*获取参数*/
		$nowtime = time();
		/*组装参数*/
		$data = array(
			'site_id' 		=> $this->site_id,/*站点ID*/
			'clinic_id'		=> $clinic_id,
			'user_id'		=> $this->user['id'],
			'alarm' 		=> input('alarm'),/*闹铃提醒时间*/
			'content' 		=> input('content'),/*内容*/
			'edittime' 		=> $nowtime,/*修改时间*/
		);	
		/*添加*/
		$data['addtime'] = $nowtime; /*添加时间*/
		/*插入数据*/
		$result = Db::name('clinic_memo')->insert($data);
		$data['id'] = Db::name('clinic_memo')->getLastInsID();
		/*判断结果集*/
		if($result){
			return show(config('code.success'),'ok',$data);
		}else{
			return show(config('code.error'),'error',array());
		}
	}

    /**
     * 管理-备忘录-修改
     */
    public function memoedit(){
		if(empty(input('memo_id'))){
			return show(config('code.error'),'参数缺失',array());
		}
		$memo_id = input('memo_id');
		/*获取参数*/
		$nowtime = time();
		/*组装参数*/
		$data = array(
			'edittime' 	=> $nowtime,/*修改时间*/
		);
		if(!empty(input('alarm'))){
			$data['alarm'] = input('alarm');
		}
		if(!empty(input('content'))){
			$data['content'] = input('content');
		}			
		/*插入数据*/
		$result = Db::name('clinic_memo')->where('id='.$memo_id)->update($data);
		/*判断结果集*/
		if($result){
			return show(config('code.success'),'ok',$data);
		}else{
			return show(config('code.error'),'error',array());
		}
	}	

    /**
     * 管理-备忘录-删除
     */
    public function memodel(){
		if(empty(input('memo_id'))){
			return show(config('code.error'),'参数缺失',array());
		}
		$memo_id = input('memo_id');		
		$result = Db::name('clinic_memo')->where('id='.$memo_id)->delete();
		if($result){
			return show(config('code.success'),'ok',$result);
		}else{
			return show(config('code.error'),'error',array());
		}
	}

    /**
     * 用户家庭医生申请状态
     */
    public function family_doctor_status(){
		/*ID*/
		$id = input('user_id');
		if(empty($id) || empty($this->user['id'])){
			return show(config('code.error'),'参数缺失',array());
		}
		/*查询初始状态*/
		$where['user_id'] = $id;
		$where['user_to_id'] = $this->user['id'];
		$family_doctor = Db::name('family_doctor')->field('status')->where($where)->find();
		if(empty($family_doctor)){
			/*不存在*/
			$data['family_doctor_status'] = 0;
		}else{
			$data['family_doctor_status'] = $family_doctor['status'];
		}
		/*返回结果集*/
		if($data){
			return show(config('code.success'),'ok',$data);
		}else{
			return show(config('code.error'),'error',array());
		}		
	}

    /**
     * 家庭医生申请列表
     */
    public function family_doctor(){
		/*医生ID*/
		$id = $this->user['id'];
		/*获取参数*/
		$sline = !empty(input('sline'))?input('sline'):0;
		$limit = !empty(input('limit'))?input('limit'):config('default_limit');
		/*查询聊天名师*/
		$where_list['user_to_id'] = $id;/*医生ID*/
		/*排序*/	
		$order = 'edittime desc,id desc';
		/*条数*/
		$limit = $sline.','.$limit;
		/*字段*/
		$field = 'id,user_id,status,edittime';
		/*查询*/
		$t_start = input('t_start');/*开始时间*/
		$t_start_data = $t_start;
		$t_end = input('t_end');/*结束时间*/
		$t_end_data = $t_end;		
		$where_list2 = array();
		if(!empty($t_start_data) && empty($t_end_data)){
			$where_list['edittime'] = array('>=',$t_start_data);
		}else if(!empty($t_end_data) && empty($t_start_data)){
			$where_list['edittime'] = array('<=',$t_end_data);
		}else if(!empty($t_end_data) && !empty($t_end_data)){
			$where_list['edittime'] = array('>=',$t_start_data);
			$where_list2['edittime'] = array('<=',$t_end_data);
		}			
		$total = Db::name('family_doctor')->where($where_list)->where($where_list2)->count();
		$list = Db::name('family_doctor')->field($field)->where($where_list)->where($where_list2)->order($order)->limit($limit)->select();
		$lists = array();
		foreach($list as $k => $v){
			$lists[$k] = $v;
			/*格式化时间*/
			$lists[$k]['edittime'] = date('Y-m-d',$v['edittime']);
			/*查询医生身份信息*/
			$user = Db::name('user')->field('realname,picture')->where('id='.$v['user_id'])->find();
			$user_picture_arr = explode(',',$user['picture']);
			$lists[$k]['realname'] = $user['realname'];
			$lists[$k]['picture'] = $user_picture_arr[0];
		}
		/*处理结果集*/
		$result_data = array(
			'total' => $total,
			'list'	=> $lists
		);
		return show(config('code.success'),'ok',$result_data);
	}

    /**
     * 家庭医生申请处理
     */
    public function family_doctoredit(){
		/*家庭医生ID*/
		if(empty(input('family_doctor_id')) || empty(input('type'))){
			return show(config('code.error'),'参数缺失',array());
		}
		$id = input('family_doctor_id');
		$type = input('type');
		if($type == 2){
			/*同意*/
			$data['status'] = 2;
		}else if($type == 3){
			/*不同意*/
			$data['status'] = 3;
		}
		$data['edittime'] = time();
		$result = Db::name('family_doctor')->where('id='.$id)->update($data);
		if($result){
			return show(config('code.success'),'ok',$result);
		}else{
			return show(config('code.error'),'error',array());
		}	
	}
	
    /**
     * 患者预约申请处理
     */
    public function subscribeedit(){
		/*家庭医生ID*/
		if(empty(input('subscribe_id')) || empty(input('type'))){
			return show(config('code.error'),'参数缺失',array());
		}
		$id = input('subscribe_id');
		$type = input('type');
		if($type == 2){
			/*同意*/
			$data['status'] = 2;
		}else if($type == 3){
			/*不同意*/
			$data['status'] = 3;
		}
		$data['edittime'] = time();
		$result = Db::name('subscribe')->where('id='.$id)->update($data);
		if($result){
			return show(config('code.success'),'ok',$result);
		}else{
			return show(config('code.error'),'error',array());
		}	
	}	

    /**
     * 就诊记录-私密病历申请查看
     */
    public function medical_useadd(){
		/*用户ID*/
		$id = $this->user['id'];
		/*ID*/
		if(empty(input('medical_id'))){
			return show(config('code.error'),'参数缺失',array());
		}
		$nowtime = time();
		$data = array(
			'medical_id' 	=> input('medical_id'),/*病历ID*/
			'user_id' 		=> $id,/*预约人ID */
			'addtime' 		=> $nowtime,
			'edittime' 		=> $nowtime,			
		);
		$result = Db::name('medical_use')->insert($data);
		$data['id'] = Db::name('medical_use')->getLastInsID();
		if($result){
			return show(config('code.success'),'ok',$data);
		}else{
			return show(config('code.error'),'error',array());
		}	
	}

	
	

}