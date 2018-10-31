<?php
namespace app\web\controller\tztx;
use app\web\controller\Common;
use think\Request;
use think\Db;
/*分销管理*/
class Commission extends Common {
	/*构造方法*/
	public function _initialize()
	{
		/*重载父类构造方法*/
		parent::_initialize();
	}

	/*分销设置*/
	public function site()
	{
        /*编辑*/
        /*查询参数*/
        $where['id'] = 1;
        $content = Db::name('wine_commission')->where($where)->find();
        $this->assign('content', $content); /*内容*/
        /*组装post提交变量*/
        $submit = array('name' => '确认编辑', 'url' => '?id=1');
        $this->assign('submit', $submit);
        /*分配变量*/
        $this->assign('title', '编辑分销'); /*页面标题*/
        $this->assign('keywords', '编辑分销'); /*页面关键词*/
        $this->assign('description', '编辑分销'); /*页面描述*/
        /*提交*/
        if ($_POST) {
            /*获取参数*/
            $nowtime = time();
            /*组装参数*/
            $data = array(
                'commission1' => input('commission1'),/*级别1分佣 比例 */
                'commission2' => input('commission2'), /*级别2分佣 比例 */
                'ordermoney' => input('ordermoney'), /*升级条件 订单总金额 元 */
                'status' => input('status'), /*状态 1.开启 | 0.关闭*/
                'min_money' => input('min_money'), /*最少提现金额*/
                'content' => input('content'), /*内容*/
                'addtime' => $nowtime
            );          
            /*编辑*/
            $result = Db::name('wine_commission')->where('id',1)->update($data);
            $alert_success = '恭喜，编辑成功！';
            $alert_error = '抱歉，编辑失败！';
            /*判断结果集*/
            if ($result) {
                $this->write_log("编辑分销设置");
                echo '<script>$(document).ready(function(){alertBox("' . $alert_success . '","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
            } else {
                echo '<script>$(document).ready(function(){alertBox("' . $alert_error . '","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
            }
            
        } else {
            /*调用模板*/
            return $this->fetch(TEMP_FETCH);
        }
	}

	public function list()
	{
		/*获取参数*/
		$limit = input('limit');
		$search = input('search');
		$dispatching = input('dispatching');
		$this->assign('limit', $limit);
		$this->assign('search', $search);
		$this->assign('dispatching', $dispatching);
		$pagelimit = !empty($limit) ? $limit : config('paginate.list_rows'); /*每页条数*/
		if (!empty($search)) {
			$where['concat(nickname)'] = array('like', '%' . $search . '%'); /*搜索*/
		}
		/*分页参数*/
		$paginate = array('query' => array( /*url额外参数*/
			'limit' => $limit,/*每页条数*/
			'search' => $search/*搜索*/
		));
    	/*字段*/
    	$field = 'id,picture,phone,nickname,mabycommissiontotal,commissiontotal,agentid';
    	/*条件*/
    	$where['agentlevel'] = array('eq', 1);
		/*判断查询还是导出*/
	    if (input('excel') == 1) {
	    	/*导出*/
	    	$list = Db::name('wine_users')->field($field)->where($where)->order('id desc')->select();
	    } else {
	    	/*查询*/
	    	$list = Db::name('wine_users')->field($field)->where($where)->order('id desc')->paginate($pagelimit, false, $paginate);
	    }
    	$lists = array();
    	foreach ($list as $k => $v) {
    		$lists[$k] = $v;
    		/*上级分销ID*/
    		$agentid_arr = Db::name('wine_users')->field('id,nickname,phone')->where('id', $v['agentid'])->find();
    		if ($agentid_arr) {
    			$lists[$k]['name'] = $agentid_arr['nickname'];
                $lists[$k]['agentid'] = $agentid_arr['id'];
    			$lists[$k]['phone_s'] = $agentid_arr['phone'];
    		} else {
    			$lists[$k]['name'] = '平台';
                $lists[$k]['agentid'] = null;
    			$lists[$k]['phone_s'] = null;
    		}
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
    		/*导出*/
    		if (input('excel') == 1) {
    			/*删除多余的参数*/
    			unset($lists[$k]['agentid']);
    			unset($lists[$k]['picture']);
    		}
    	}
    	/*导出*/
        if (input('excel') == 1) {
            $excelHead = array('ID','会员名称','累计赚取佣金','可提现佣金','上级分销商名称','一级分销商','二级分销商');
            array_unshift($lists,$excelHead);
            /*写数据文件*/
            $this->create_xls($lists,'分销商信息'.date('Y-m-d',time()));
        }
		$page = $list->render(); /*获取分页显示*/
		$this->assign('list', $lists);
		$this->assign('page', $page);
		/*分配变量*/
		$this->assign('title', '分销列表'); /*页面标题*/
		$this->assign('keywords', '分销列表'); /*页面关键词*/
		$this->assign('description', '分销列表'); /*页面描述*/
		/*调用模板*/
		return $this->fetch(TEMP_FETCH);
	}

	public function listedit()
	{
		$agentid = input('agentid')?input('agentid'):null;/*上级ID*/
		$id = input('id');/*会员ID*/
		var_dump($agentid);
		var_dump($id);
		if (empty($id)) {
			echo '<script>$(document).ready(function(){alertBox("参数缺失","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
			die();
		}
		$where['id'] = $id;
		$update['agentid'] = $agentid;
		$update['edittime'] = time();
		$result = Db::name('wine_users')->where($where)->update($update);
		if ($result) {
			$log_in = '将ID为 '.$id.' 的上级分销商修改为 '.$agentid;
			$this->write_log($log_in);
			echo '<script>$(document).ready(function(){alertBox("修改成功","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
		} else {
			echo '<script>$(document).ready(function(){alertBox("修改失败","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
		}

	}










}