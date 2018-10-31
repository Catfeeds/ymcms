<?php
namespace app\web\controller\tztx;
use app\web\controller\Common;
use think\Request;
use think\Db;
/*会员管理*/
class Admin extends Common {

	/*构造方法*/
	public function _initialize()
	{
		/*重载父类构造方法*/
		parent::_initialize();
	}
	/*会员列表*/
	public function user() {
		/*获取参数*/
		$limit = input('limit');
		$search = input('search');
		$this->assign('limit',$limit);
		$this->assign('search',$search);
		$pagelimit = !empty($limit)?$limit:config('paginate.list_rows');/*每页条数*/
		if(!empty($search)){
			$where_user_list['concat(phone,nickname)'] = array('like','%'.$search.'%');/*搜索*/
		}
		/*条件*/
		$where_user_list['status'] = array('neq',9);
		/*分页参数*/
		$paginate = array(
			'query' => array(/*url额外参数*/
				'limit' => $limit,/*每页条数*/
				'search' => $search,/*搜索*/
			)
		);
		$field = "id,nickname,phone,ranks,status";
		/*判断查询还是导出*/
		if (input('excel') == 1) {
				$list = Db::name('wine_users')->where($where_user_list)->field($field)->select();
			} else{
				/*查询*/
				$field .= ',picture,agentlevel';
				$list = Db::name('wine_users')->where($where_user_list)->field($field)->order('id desc')->paginate($pagelimit,false,$paginate);
			}
		$lists = array();
		foreach($list as $k=>$v){
			$lists[$k] = $v;
			/*状态*/
			if($v['status'] == 1){
				$lists[$k]['status'] = '启用';
			}else{
				$lists[$k]['status'] = '禁用';
			}
			/*会员等级*/
			$ranks = Db::name('wine_users_ranks')->field('username')->where('id', $v['ranks'])->find();
			$lists[$k]['ranks'] = $ranks['username'];
			/*分销商数量*/
            $one_arr = Db::name('wine_users')->field('id')->where('agentid', $v['id'])->select();
            if ($one_arr) {
                $id_str = '';
                foreach ($one_arr as $vv) {
                    $id_str = $id_str.','.$vv['id'];
                }
                $id_str = trim($id_str,',');
                /*二级分销商*/
                $where_in['agentid'] = array('in', $id_str);
                $lists[$k]['one'] = Db::name('wine_users')->where('agentid', $v['id'])->count();
                $lists[$k]['tow'] = Db::name('wine_users')->where($where_in)->count();
                /*一级分销商*/
            } else {
                $lists[$k]['one'] = 0;
                $lists[$k]['tow'] = 0;
            }
		}
		/*导出*/
		if (input('excel') == 1) {
			$excelHead = array('ID','姓名','手机号','会员等级','状态','一级分销会员数','二级分销会员数');
			array_unshift($lists,$excelHead);
			/*写数据文件*/
			$this->create_xls($lists,'会员信息'.date('Y-m-d',time()));
		}
		$page = $list->render();/*获取分页显示*/
		$this->assign('list',$lists);
		$this->assign('page',$page);
		/*分配变量*/
		$this->assign('title','用户列表');/*页面标题*/
		$this->assign('keywords','用户列表');/*页面关键词*/
		$this->assign('description','用户列表');/*页面描述*/
		/*调用模板*/
		return $this->fetch(TEMP_FETCH);
    
	}

	/*添加用户*/
    public function useredit()
    {	
		/*获取参数*/
		$user_id = input('id');/*用户ID*/
		if($user_id){
			/*编辑*/
			/*查询参数*/
			$where_user['id'] = $user_id;
			$user = Db::name('wine_users')->where($where_user)->find();
			foreach($user as $k=>$v){
				/*解析头像*/
				if($k == 'picture' && !empty($user['picture'])){
					$picture = explode(',',$v);
					$user['picture'] = array();
					foreach($picture as $k2=>$v2){						
						$basename = explode('/',$v2);
						$basename = array_pop($basename);
						$user['picture'][$k2] = array(
							'filename' => $v2,
							'basename' => $basename
						);
					}
				}
			}
			$this->assign('content',$user);/*内容*/	
			/*组装post提交变量*/
			$submit = array(
				'name' => '确认编辑',
				'url' => '?id='.$user_id
			);
			$this->assign('submit',$submit);
			/*分配变量*/
			$this->assign('title','编辑用户');/*页面标题*/
			$this->assign('keywords','编辑用户');/*页面关键词*/
			$this->assign('description','编辑用户');/*页面描述*/			
		}else{
			/*添加*/
			/*组装post提交变量*/
			$submit = array(
				'name' => '确认添加',
				'url' => '?a=add'
			);
			$this->assign('submit',$submit);	
			/*分配变量*/
			$this->assign('title','添加用户');/*页面标题*/
			$this->assign('keywords','添加用户');/*页面关键词*/
			$this->assign('description','添加用户');/*页面描述*/		
		}
		/*提交*/
		if($_POST){
			/*获取参数*/
			$nowtime = time();
			$password = input('password');
			$phone = input('phone');
			/*验证参数*/
			if(input('password') != input('password2')){
				echo '<script>$(document).ready(function(){alertBox("两次密码不一致..","'.$_SERVER['HTTP_REFERER'].'");})</script>';
				die();
			}else{											
				/*组装参数*/
				$data = array(		
					'nickname'	=> input('nickname'),/*昵称*/
					'picture'	=> input('picture'),/*头像*/
					'sex'		=> input('sex'),/*性别 默认女:0|男为1 */
					'year'		=> input('year'),/*出生年*/
					'month'		=> input('month'),/*出生月*/
					'day'		=> input('day'),/*出生日*/
					'address'	=> input('address'),/*地址*/
					'order'		=> input('order'),/*排序  认为100*/
					'status'	=> input('status'),/*状态  默认为1:启用|0:禁用*/
					'edittime'	=> $nowtime,/*修改时间*/
					'time_out'	=> $nowtime,/*登陆时间*/
				);
				if($user_id){
					/*编辑*/
					/*更新密码*/
					if(!empty($password)){
						$reg_time = $user['addtime'];
						$password = md5(md5($reg_time.$password));
						$data['password'] = $password;
					}
					/*更新数据*/
					$result = Db::name('wine_users')->where('id='.$user_id)->update($data);	
					$alert_success = '恭喜，编辑成功！';
					$alert_error = '抱歉，编辑失败！';					
				}else{
					/*验证参数*/
					if(!phone_check($phone)){
						echo '<script>$(document).ready(function(){alertBox("手机号格式不正确..","'.$_SERVER['HTTP_REFERER'].'");})</script>';
						die();
					}
					/*查询是否存在*/
					$result_number = Db::name('wine_users')->where('phone',$phone)->count();
					if ($result_number) {
						echo '<script>$(document).ready(function(){alertBox("手机号已存在..","'.$_SERVER['HTTP_REFERER'].'");})</script>';
						die();
					}
					/*更新密码*/
					if(!empty($password)){
						$reg_time = $nowtime;
						$password = md5(md5($reg_time.$password));
						$data['password'] = $password;
					}
					$data['addtime']	= $nowtime;/*添加时间*/
					$data['phone']	= $phone;/*手机号*/
					//dump($data);die();
					/*插入数据*/
					$result = Db::name('wine_users')->insert($data);
					$alert_success = '恭喜，添加成功！';
					$alert_error = '抱歉，添加失败！';										
				}
				/*判断结果集*/
				if($result){
					echo '<script>$(document).ready(function(){alertBox("'.$alert_success.'","'.url('tztx.admin/user').'");})</script>';
				}else{
					echo '<script>$(document).ready(function(){alertBox("'.$alert_error.'","'.$_SERVER['HTTP_REFERER'].'");})</script>';
				}
			}
		}else{
			/*调用模板*/
			return $this->fetch(TEMP_FETCH);		
		}
    }
    /*删除用户*/
    public function userdel()
    {	
		/*接收参数*/
		$user_id = input('id');/*用户ID*/
		$result = Db::name('wine_users')->where('id',$user_id)->delete();
		if($result){
			echo '<script>$(document).ready(function(){alertBox("恭喜，删除成功！","'.$_SERVER['HTTP_REFERER'].'");})</script>';
		}else{
			echo '<script>$(document).ready(function(){alertBox("抱歉，删除失败！","'.$_SERVER['HTTP_REFERER'].'");})</script>';
		}
    }	

    /*会员等级*/
    public function userrank() {
		/*获取参数*/
		$limit = input('limit');
		$search = input('search');
		$this->assign('limit',$limit);
		$this->assign('search',$search);
		$pagelimit = !empty($limit)?$limit:config('paginate.list_rows');/*每页条数*/
		if(!empty($search)){
			$where_user_list['concat(username)'] = array('like','%'.$search.'%');/*搜索*/
		}
		/*条件*/
		$where_user_list['status'] = array('neq',9);
		/*分页参数*/
		$paginate = array(
			'query' => array(/*url额外参数*/
				'limit' => $limit,/*每页条数*/
				'search' => $search,/*搜索*/
			)
		);		
		$list = Db::name('wine_users_ranks')->where($where_user_list)->paginate($pagelimit,false,$paginate);
		$lists = array();
		foreach($list as $k=>$v){
			$lists[$k] = $v;
			/*状态*/
			if($v['status'] == 1){
				$lists[$k]['status'] = '启用';
			}else{
				$lists[$k]['status'] = '禁用';
			}
		}
		$page = $list->render();/*获取分页显示*/
		$this->assign('list',$lists);
		$this->assign('page',$page);
		/*分配变量*/
		$this->assign('title','用户列表');/*页面标题*/
		$this->assign('keywords','用户列表');/*页面关键词*/
		$this->assign('description','用户列表');/*页面描述*/
		/*调用模板*/
		return $this->fetch(TEMP_FETCH);
	}

	/*添加等级*/
    public function userrankedit()
    {	
		/*获取参数*/
		$user_id = input('id');/*等级ID*/
		if($user_id){
			/*编辑*/
			/*查询参数*/
			$where_user['id'] = $user_id;
			$user = Db::name('wine_users_ranks')->where($where_user)->find();
			$this->assign('content',$user);/*内容*/	
			/*组装post提交变量*/
			$submit = array(
				'name' => '确认编辑',
				'url' => '?id='.$user_id
			);
			$this->assign('submit',$submit);
			/*分配变量*/
			$this->assign('title','编辑等级');/*页面标题*/
			$this->assign('keywords','编辑等级');/*页面关键词*/
			$this->assign('description','编辑等级');/*页面描述*/			
		}else{
			/*添加*/
			/*组装post提交变量*/
			$submit = array(
				'name' => '确认添加',
				'url' => '?a=add'
			);
			$this->assign('submit',$submit);	
			/*分配变量*/
			$this->assign('title','添加等级');/*页面标题*/
			$this->assign('keywords','添加等级');/*页面关键词*/
			$this->assign('description','添加等级');/*页面描述*/		
		}
		/*提交*/
		if($_POST){
			/*获取参数*/
			$nowtime = time();
			$username = input('username');
			$quota = input('quota');
			$discount = input('discount');
			/*验证参数*/
			if(empty($username)||!isset($quota)||!isset($discount)){
				echo '<script>$(document).ready(function(){alertBox("参数缺失..","'.$_SERVER['HTTP_REFERER'].'");})</script>';
				die();
			}else{											
				/*组装参数*/
				$data = array(		
					'username'	=> $username,/*等级名称*/
					'quota'		=> $quota,/*消费额度*/
					'discount'	=> $discount,/*享受折扣*/
					'order'		=> input('order'),/*排序  认为100*/
					'content'	=> input('content'),/*内容*/
					'synopsis'	=> input('synopsis'),/*简介*/
					'edittime'	=> $nowtime,/*修改时间*/
				);
				if($user_id){
					/*更新数据*/
					$result = Db::name('wine_users_ranks')->where('id='.$user_id)->update($data);	
					$alert_success = '恭喜，编辑成功！';
					$alert_error = '抱歉，编辑失败！';
					$log_in='编辑ID为'.$user_id.' 的等级';					
				}else{
					$data['addtime'] = $nowtime;/*添加时间*/
					/*插入数据*/
					$result = Db::name('wine_users_ranks')->insertGetId($data);
					$alert_success = '恭喜，添加成功！';
					$alert_error = '抱歉，添加失败！';
					$log_in='添加ID为'.$result.' 的等级';				
				}
				/*判断结果集*/
				if($result){
					$this->write_log($log_in);
					echo '<script>$(document).ready(function(){alertBox("'.$alert_success.'","'.url('tztx.admin/userrank').'");})</script>';
				}else{
					echo '<script>$(document).ready(function(){alertBox("'.$alert_error.'","'.$_SERVER['HTTP_REFERER'].'");})</script>';
				}
			}
		}else{
			/*调用模板*/
			return $this->fetch(TEMP_FETCH);		
		}
    }
    /*删除等级*/
    public function userrankdel()
    {	
		/*接收参数*/
		$id = input('id');/*用户ID*/
		$result = Db::name('wine_users_ranks')->where('id',$id)->delete();
		if($result){
			$log_in='删除ID为'.$id.' 的等级';
			$this->write_log($log_in);
			echo '<script>$(document).ready(function(){alertBox("恭喜，删除成功！","'.$_SERVER['HTTP_REFERER'].'");})</script>';
		}else{
			echo '<script>$(document).ready(function(){alertBox("抱歉，删除失败！","'.$_SERVER['HTTP_REFERER'].'");})</script>';
		}
    }

    /**
     * 成为分销商
     * @return [type] [description]
     */
    public function useradd()
    {
    	/*分销商上级ID*/
    	$agentid = input('agentid');
    	$id = input('id');
    	if (empty($id)) {
    		echo '<script>$(document).ready(function(){alertBox("参数缺失..","'.$_SERVER['HTTP_REFERER'].'");})</script>';
    		die();
    	}
    	/*查询上级是否存在*/
    	$user_info = Db::name('wine_users')->where('id', $agentid)->count();
        if (empty($user_info) && $agentid != 0) {
        	echo '<script>$(document).ready(function(){alertBox("上级不存在..","'.$_SERVER['HTTP_REFERER'].'");})</script>';
    		die();
        }
        /*修改上级*/
        $update = array(
        	'agentlevel' => 1,
        	'agentid' => $agentid
        	);
        $result = Db::name('wine_users')->where('id', $id)->update($update);
        if($result){
			$log_in='修改ID为'.$id.' 的上级分销商';
			$this->write_log($log_in);
			echo '<script>$(document).ready(function(){alertBox("恭喜，修改成功！","'.$_SERVER['HTTP_REFERER'].'");})</script>';
		}else{
			echo '<script>$(document).ready(function(){alertBox("抱歉，修改失败！","'.$_SERVER['HTTP_REFERER'].'");})</script>';
		}
    }



}