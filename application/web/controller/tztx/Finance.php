<?php
namespace app\web\controller\tztx;
use app\web\controller\Common;
use think\Request;
use think\Db;
use app\common\lib\Upload;
/*财务管理*/
class Finance extends Common
{
    /*构造方法*/
    public function _initialize() {
        /*重载父类构造方法*/
        parent::_initialize();
    }
    /**
     * 会员佣金统计
     * @return [type] [description]
     */
    public function userlist(){
        $limit = input('limit');
        $search = input('search');
        $scope=input('scope');
        if(!empty($scope)){
            $this->assign('scope',$scope);
            $scope=explode(" - ",$scope);
            $starttime=strtotime($scope[0]);//开始
            $endtime=strtotime($scope[1]);//结束
        }
        $where_list=array();
        $where_list2=array();
        if(!empty($scope)) {
            $where_list['addtime'] = array('>=', $starttime); /*开始时间*/
            $where_list2['addtime'] = array( '<', $endtime);/*结束时间*/
        }

        if (!empty($search)) {
            //$where_list['concat(userName,trueName,userPhone,suserEmail)'] = array('like', '%' . $search . '%'); /*搜索*/
            $where_list['concat(nickname,phone)'] = array('like', '%' . $search . '%'); /*搜索*/
        }
        $this->assign('limit', $limit);
        $this->assign('search', $search);
        $pagelimit = !empty($limit) ? $limit : config('paginate.list_rows'); /*每页条数*/
        $paginate = array('query' => array( /*url额外参数*/
            'limit' => $limit,/*每页条数*/
            'search' => $search,/*搜索*/
            'scope' => $scope,/*时间*/
        ));
        /*判断查询还是导出*/
        if (input('excel') == 1) {
            /*导出*/
            $list = Db::name('wine_users')
                ->field('id,nickname,addtime,phone,time_out,commissiontotal,mabycommissiontotal')
                ->where($where_list)
                ->where($where_list2)
                ->select();
        } else {
            $list = Db::name('wine_users')
                ->field('id,nickname,addtime,picture,phone,time_out,commissiontotal,mabycommissiontotal')
                ->where($where_list)
                ->where($where_list2)
                ->paginate($pagelimit, false, $paginate);
        }
        $lists=array();
        foreach ($list as $k=>$v){
            /*更改时间格式*/
            $v['lastTime']=$v['time_out']?date("Y.m.d H:i:s",$v['time_out']-(60*60*24*7)):null;
            $v['addtime']=date("Y-m-d H:i:s",$v['addtime']);
            /*条件*/
            $where['uid'] = array('eq', $v['id']);
            $where['status'] = array('eq', 4);
            $order=Db::name('wine_shop_order_goods')->field('COUNT(id) as number,SUM(price) as Money')->where($where)->order('uid')->find();
            $v['number']=$order['number'];/*用户消费的订单数目*/
            $v['Money']=$order['Money'];/*用户消费金额*/
            $lists[$k]=$v;
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
                unset($lists[$k]['time_out']);
            }
        }
        /*导出*/
        if (input('excel') == 1) {
            $excelHead = array('ID','会员名称','注册时间','手机号','累计赚取佣金','可提现佣金','最后登陆时间','成交订单数量','消费的金额','一级分销商','二级分销商');
            array_unshift($lists,$excelHead);
            /*写数据文件*/
            $this->create_xls($lists,'会员佣金统计'.date('Y-m-d',time()));
        }
        $page = $list->render(); /*获取分页显示*/
        $this->assign('list', $lists);
        $this->assign('page', $page);
        /*分配变量*/
        $this->assign('title', '会员佣金统计'); /*页面标题*/
        $this->assign('keywords', '会员佣金统计'); /*页面关键词*/
        $this->assign('description', '会员佣金统计'); /*页面描述*/
        /*调用模板*/
        return $this->fetch(TEMP_FETCH);
    }


    /**
     * 商户销售统计
     * @return [type] [description]
     */
    public function storelist(){
        /*获取参数*/
        $limit = input('limit');
        $search = input('search');
        $scope=input('scope');
        if(!empty($scope)){
            $this->assign('scope',$scope);
            $scope=explode(" - ",$scope);
            $starttime=strtotime($scope[0]);//开始
            $endtime=strtotime($scope[1]);//结束
        }
        $where_list=array();
        $where_list2=array();
        if(!empty($scope)) {
            $where_list['addtime'] = array('>=', $starttime); /*开始时间*/
            $where_list2['addtime'] = array( '<', $endtime);/*结束时间*/
        }
        if (!empty($search)) {
            $where_list['concat(nickname)'] = array('like', '%' . $search . '%'); /*搜索*/
        }

        $this->assign('limit', $limit);
        $this->assign('search', $search);
        $pagelimit = !empty($limit) ? $limit : config('paginate.list_rows'); /*每页条数*/
        $paginate = array('query' => array( /*url额外参数*/
            'limit' => $limit,/*每页条数*/
            'search' => $search,/*搜索*/
            'scope' => $scope,/*时间*/
        ));
        /*固定字段*/
        $field = 'id,nickname,status,province,city,area,address,addtime';
        /*条件*/
        $where_list['status'] = array('neq', 9);
        $where_list['site_ids'] = array('eq', 5);
        $where_list['admin'] = array('eq', 0);
        /*判断查询还是导出*/
        if (input('excel') == 1) {
            /*导出*/
            $list = Db::name('user')->where($where_list)->where($where_list2)->field($field)->select();
        } else {
            /*查询*/
            $field = 'id,nickname,picture,status,province,city,area,address,addtime';
            $list = Db::name('user')->where($where_list)->where($where_list2)->field($field)->paginate($pagelimit, false, $paginate);
        }
        $lists=array();
        foreach ($list as $k=>$v){
            /*统计所有订单金额和数量*/
            $where['shopid'] = array('eq', $v['id']);
            $where['status'] = array('eq', 4);
            $order=Db::name('wine_shop_order_goods')->field('COUNT(id) as number,SUM(price) as Money')->where($where)->order('shopid')->find();
            $v['number_all']=$order['number'];/*商户消费的订单数目*/
            $v['Money_all']=$order['Money'];/*商户消费金额*/
            $month = date("m", strtotime("last month"));
            $days = date("t", strtotime("last month"));
            $lastmonth_year = date("Y");
            if ($month == 1) {$year = date("Y");$lastmonth_year = $year - 1;}
            //上个月开始的时间戳
            $begin = mktime(0, 0, 0, $month, 1, $lastmonth_year);
            //上个月结束的时间戳
            $end = mktime(23, 59, 59, $month, $days, $lastmonth_year);
            //上个月订单
            $order=Db::name('wine_shop_order_goods')->field('COUNT(id) as number,SUM(price) as Money')->where('shopid',$v['id'])->where(array(
                'status'=>array('=',4)
            ))
                ->where(
                    array(
                        'addtime'=>array('>=',$begin),
                        'addtime'=>array('<',$end)
                    )
                )
                ->order('shopid')->find();
            $v['number']=$order['number'];/*商户消费的订单数目*/
            $v['Money']=$order['Money'];/*商户消费金额*/
            /*状态*/
            if ($v['status'] == 1) {
                $v['status'] = '启用';
            } else {
                $v['status'] = '禁用';

            }
            /*地址*/
            $city= Db::name('areacode')->field('name')->where('id',$v['province'])->find();
            $v['province']=$city['name'];/*门店地址：省*/
            $city= Db::name('areacode')->field('name')->where('id',$v['city'])->find();
            $v['city']=$city['name'];/*门店地址：市*/
            $area= Db::name('areacode')->field('name')->where('id',$v['area'])->find();
            $v['area']=$area['name'];/*门店地址：区*/
            if (input('excel') ==1) {
                $v['addtime'] = $v['addtime']?date('Y-m-d H:i:s',$v['addtime']):null;
            }
            /*赋值*/
            $lists[$k]=$v;
        }
        /*导出*/
        if (input('excel') == 1) {
            $excelHead = array('ID','商户名称','状态','省份','市','区','详细地址','开店时间','总订单数量','总订单金额','上月订单数量','上月订单金额');
            array_unshift($lists,$excelHead);
            /*写数据文件*/
            $this->create_xls($lists,'商户销售统计'.date('Y-m-d',time()));
        }
        $page = $list->render(); /*获取分页显示*/
        $this->assign('list', $lists);
        $this->assign('page', $page);
        /*分配变量*/
        $this->assign('title', '商户销售统计'); /*页面标题*/
        $this->assign('keywords', '商户销售统计'); /*页面关键词*/
        $this->assign('description', '商户销售统计'); /*页面描述*/
        return $this->fetch(TEMP_FETCH);
    }

    /**
     * 按月份统计金额和订单数量
     * @return [type] [description]
     */
    public function monthlist(){
        /*获取时间参数*/
        $current_year = date('Y');
        $year = ((empty(input('year')) ? $current_year : input('year')));/*年*/
        $years = array();
        $i = $current_year - 10;

        while ($i <= $current_year) {
            $years[] = array('data' => $i, 'selected' => $i == $year);
            ++$i;
        }

        $month = input('month');
        $i = 1;

        while ($i <= 12) {
            $months[] = array('data' => $i, 'selected' => $i == $month);
            ++$i;
        }
        $day = intval(input('day'));
        // $type = intval(input('type'));
        $list = array();
        $totalprice = 0;
        $totalcount = 0;
        $maxprice = 0;
        $maxcount = 0;
        $maxcount_date = '';
        $maxdate = '';
        $countfield = ((empty($type) ? '' : ''));
        $typename = ((empty($type) ? '交易额' : '交易量'));

        if (!(empty($year)) && !(empty($month)) && !(empty($day))) {
            $dataname = '时间';
            /*按小时查*/
            $hour = 0;

            while ($hour < 24) {
                $nexthour = $hour + 1;
                /* and  and */
                $count=Db::name('wine_shop_order_goods')
                    ->field('ifnull(sum(price),0) as price,ifnull(count(*),0) as cnt')
                    ->where('status=4')
                    ->where('addtime >='.strtotime($year . '-' . $month . '-' . $day . ' ' . $hour . ':00:00'))
                    ->where('addtime <='.strtotime($year . '-' . $month . '-' . $day . ' ' . $hour . ':59:59'))
                    ->find();
                $dr = array('data' => $hour . '点 - ' . $nexthour . '点', 'count' => $count['cnt'],'price'=>$count['price']);
                $totalcount += $dr['count'];
                $totalprice += $dr['price'];

                if ($maxcount < $dr['count']) {
                    $maxcount = $dr['count'];
                    $maxcount_date = $year . '年' . $month . '月' . $day . '日 ' . $hour . '点 - ' . $nexthour . '点';
                }
                if ($maxprice < $dr['price']) {
                    $maxprice = $dr['price'];
                    $maxprice_date = $year . '年' . $month . '月' . $day . '日 ' . $hour . '点 - ' . $nexthour . '点';
                }
                $list[] = $dr;
                ++$hour;
            }
        }elseif (!(empty($year)) && !(empty($month))) {
            $dataname = '天数';
            /*按天数查*/
            $lastday = date('t', strtotime($year . '-' . $month . ' -1'));
            $d = 1;

            while ($d <= $lastday) {
                $count=Db::name('wine_shop_order_goods')
                    ->field('ifnull(sum(price),0) as price,ifnull(count(*),0) as cnt')
                    ->where('status=4')
                    ->where('addtime >='.strtotime($year . '-' . $month . '-' . $d . ' 00:00:00'))
                    ->where('addtime <='.strtotime($year . '-' . $month . '-' . $d . ' 23:59:59'))
                    ->find();
                $dr = array('data' => $d, 'count' => $count['cnt'],'price'=>$count['price']);
                $totalcount += $dr['count'];
                $totalprice += $dr['price'];

                if ($maxcount < $dr['count']) {
                    $maxcount = $dr['count'];
                    $maxcount_date = $year . '年' . $month . '月' . $d . '日';
                }
                if ($maxprice < $dr['price']) {
                    $maxprice = $dr['price'];
                    $maxprice_date =  $year . '年' . $month . '月' . $d . '日';
                }
                $list[] = $dr;
                ++$d;
            }
        }else if (!(empty($year))) {
            $dataname = '月份';
            /*按月份查*/
            foreach ($months as $m ) {

                $lastday = date('t', strtotime($year . '-' . $m['data'] . ' -1'));//get_last_day($year, $m);
                $count=Db::name('wine_shop_order_goods')
                    ->field('ifnull(sum(price),0) as price,ifnull(count(*),0) as cnt')
                    ->where('status=4')
                    ->where('addtime >='.strtotime($year . '-' . $m['data'] . '-01 00:00:00'))
                    ->where('addtime <='.strtotime($year . '-' . $m['data'] . '-' . $lastday . ' 23:59:59'))
                    ->find();

                $dr = array('data' => $m['data'], 'count' => $count['cnt'],'price'=>$count['price']);
                $totalcount += $dr['count'];
                $totalprice += $dr['price'];
                if ($maxcount < $dr['count']) {
                    $maxcount = $dr['count'];
                    $maxcount_date = $year . '年' . $m['data'] . '月';
                }
                if ($maxprice < $dr['price']) {
                    $maxprice = $dr['price'];
                    $maxprice_date = $year . '年' . $m['data'] . '月';
                }


                $list[] = $dr;
            }
        }
        foreach ($list as $key => &$row ) {
            $list[$key]['percent'] = number_format(($row['count'] / ((empty($totalcount) ? 1 : $totalcount))) * 100, 2);
            $list[$key]['perprice'] = number_format(($row['price'] / ((empty($totalprice) ? 1 : $totalprice))) * 100, 2);
        }
        $this->assign('dataname', $dataname);
        $this->assign('years',$years);
        $this->assign('year',$year);
        $this->assign('day',$day);
        $this->assign('months',$months);
        $this->assign('list', $list);
        $this->assign('title','订单统计');/*页面标题*/
        $this->assign('keywords','订单统计');/*页面关键词*/
        $this->assign('description','订单统计');/*页面描述*/
        if (input('export') == 1) {

            $list[] = array('data' => $typename . '总数', 'count' => $totalcount,'percent'=>'','price'=>'','perprice'=>'');
            $list[] = array('data' => '最高' . $typename, 'count' => $maxcount,'percent'=>'','price'=>'','perprice'=>'');
            $list[] = array('data' => '发生在', 'count' => $maxcount_date,'percent'=>'','price'=>'','perprice'=>'');
            $filename='交易报告-' . ((!(empty($year)) && !(empty($month)) ? $year . '年' . $month . '月' : $year . '年'));
            $header=array($dataname,'订单数量','所占比例(%)','订单金额','所占比例(%)');
            $index=array('data','count','percent','price','perprice');
            $this->createtable($list,$filename,$header,$index);
        }
        return $this->fetch(TEMP_FETCH);
    }
    /**
     * 获取每月日期
     * @return [type] [description]
     */
    public function days()
    {
        $year = intval(input('year'));
        $month = intval(input('month'));
        exit( date('t', strtotime($year . '-' . $month . ' -1')));
    }

    /**
     * 商户统计详情
     * @return [type] [description]
     */
    public function storelistdetail()
    {
        $id = input('id');
        if (empty($id)) {
            echo '<script>$(document).ready(function(){alertBox("商户id不能为空..","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
            die();
        }
        $lists = array();
        /*条件*/
        $where['status'] = array('eq', 4);
        $where['shopid'] = array('eq', $id);
        /*查询订单号*/
        $list = Db::name('wine_shop_order_goods')->field('ordersn')->where($where)->select();
        if (empty($list)) {
            echo '<script>$(document).ready(function(){alertBox("没有详情数据..","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
            die();
        }
        $ordersn_str = '';
        foreach ($list as $v) {
            $ordersn_str .= ','.$v['ordersn'];
        }
        $ordersn_str = trim($ordersn_str, ',');/*去除符号*/
        /*条件*/
        $where_sn['ordersn'] = array('in', $ordersn_str);
        /*固定字段*/
        $field = 'g.name,sum(o.number) as number,g.picture';
        $list = Db::name('wine_shop_goods_order')
                    ->alias('o')
                    ->join('wine_shop_goods g', 'o.goodsid = g.id')
                    ->field($field)
                    ->where($where_sn)
                    ->group('o.goodsid')
                    ->select();
        $this->assign('list', $list);
        $this->assign('title','商品数据统计');/*页面标题*/
        $this->assign('keywords','商品数据统计');/*页面关键词*/
        $this->assign('description','商品数据统计');/*页面描述*/
        return $this->fetch(TEMP_FETCH);
    }

    /**
     * 提现列表
     */
    public function orderlist()
    {
        
        /*获取参数*/
        $limit = input('limit');
        $search = input('search');
        $status = input('status');
        $time = input('time');
        $dispatching = input('dispatching');
        $this->assign('limit', $limit);
        $this->assign('search', $search);
        $this->assign('status', $status);
        $this->assign('time', $time);
        $this->assign('dispatching', $dispatching);
        $pagelimit = !empty($limit) ? $limit : config('paginate.list_rows'); /*每页条数*/
        if (!empty($search)) {
            $where['concat(u.nickname,u.phone)'] = array('like', '%' . $search . '%'); /*搜索*/
        }
        /*分页参数*/
        $paginate = array('query' => array( /*url额外参数*/
            'limit' => $limit,/*每页条数*/
            'search' => $search,/*搜索*/
            'status' => $status,/*状态*/
            'time' => $time,/*时间*/
            'dispatching' => $dispatching,/*提现类型*/
        ));
        /*状态*/
        $where['g.status'] = array('neq', 9);
        if (!empty($status)) {
            $where['g.status'] = array('eq', $status);
        }
        /*提现类型*/
        if (!empty($dispatching)) {
            $where['g.type'] = array('eq', $dispatching);
        }
        /*字段*/
        $field = 'g.id,u.nickname,u.phone,g.addtime,u.picture,g.type,g.idcard,g.yh_name,g.name,g.money,g.status';
        /*时间查询*/
        if (empty($time)) {
            $start = strtotime('2010-10-1');
            $end = strtotime('2030-10-1');
        } else {
            $time_arr = explode('~', $time);
            $start = $time_arr[0];
            $end = $time_arr[1];
        }
        /*判断查询还是导出*/
        if (input('excel') == 1) {
            /*导出*/
            $list = Db::name('wine_commision_record')
                ->alias('g')
                ->join('wine_users u', 'g.uid = u.id')
                ->field($field)->where($where)->whereTime('g.addtime', 'between', [$start, $end])->select();
        } else {
            /*查询*/
            $list = Db::name('wine_commision_record')
                ->alias('g')
                ->join('wine_users u', 'g.uid = u.id')
                ->field($field)
                ->where($where)
                ->whereTime('g.addtime', 'between', [$start, $end])
                ->order('id desc')
                ->paginate($pagelimit, false, $paginate);
        }
        $lists = array();
        foreach ($list as $k => $v) {
            $v['statusname'] = commision_status($v['status']);
            $v['typename'] = commision_type($v['type']);
            /*导出*/
            if (input('excel') == 1) {
                unset($v['status']);
                unset($v['type']);
                unset($v['picture']);
                $v['addtime'] = $v['addtime'] ? date('Y-m-d H:i:s',$v['addtime']):null;
            }
            $lists[$k] = $v;
        }
        /*导出*/
        if (input('excel') == 1) {
            $excelHead = array('提现ID','会员名称','会员手机号','申请时间','卡号','银行名称','申请人名称','申请金额','申请状态','打款方式');
            array_unshift($lists,$excelHead);
            /*写数据文件*/
            $this->create_xls($lists,'提现管理'.date('Y-m-d',time()));
        }
        $page = $list->render(); /*获取分页显示*/
        $this->assign('list', $lists);
        $this->assign('page', $page);
        /*分配变量*/
        $this->assign('title', '提现列表'); /*页面标题*/
        $this->assign('keywords', '提现列表'); /*页面关键词*/
        $this->assign('description', '提现列表'); /*页面描述*/
        /*调用模板*/
        return $this->fetch(TEMP_FETCH);
    }

    /**
     * 提现审核
     * @return [type] [description]
     */
    public function orderedit()
    {
        $status = input('status');
        $content = input('content');
        $id = input('id');
        if (empty($id) || empty($status)) {
            echo '<script>$(document).ready(function(){alertBox("参数缺失","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
            die();
        }
        $update = array(
            'status' => $status,
            'content' => $content,
            'edittime' => time()
            );
        $result = Db::name('wine_commision_record')->where('id', $id)->update($update);
        if ($result) {
            $lon_in = '同意了ID为'.$id.' 的提现申请';
            if ($status == 2) {                
                $money = Db::name('wine_commision_record')->where('id', $id)->find();
                Db::name('wine_users')->where('id', $money['uid'])->setInc('commissiontotal',$money['money']);
                $lon_in = '拒绝了ID为'.$id.' 的提现申请';
            }
            $this->write_log($lon_in);
            echo '<script>$(document).ready(function(){alertBox("修改成功","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
        } else {
            echo '<script>$(document).ready(function(){alertBox("修改失败","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
        }
    }

    /**
     * 提现详情
     * @return [type] [description]
     */
    public function orderdetail()
    {
        $id = input('id');
        if (empty($id)) {
            echo '<script>$(document).ready(function(){alertBox("参数缺失","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
            die();
        }
        /*条件*/
        $where['g.id'] = $id;
        /*字段*/
        $field = 'g.id,g.status,g.money,g.addtime,g.edittime,u.nickname,u.phone,g.type,g.idcard,g.yh_name,g.name,g.content';
        /*查询*/
        $list = Db::name('wine_commision_record')
            ->alias('g')
            ->join('wine_users u', 'g.uid = u.id')
            ->field($field)->where($where)->find();
        $list['statusname'] = commision_status($list['status']);
        $list['typename'] = commision_type($list['type']);
        $this->assign('list', $list);
        /*分配变量*/
        $this->assign('title', '提现详情'); /*页面标题*/
        $this->assign('keywords', '提现详情'); /*页面关键词*/
        $this->assign('description', '提现详情'); /*页面描述*/
        /*调用模板*/
        return $this->fetch(TEMP_FETCH);
    }


}