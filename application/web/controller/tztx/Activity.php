<?php
namespace app\web\controller\tztx;
use app\web\controller\Common;
use think\Request;
use think\Db;
/*活动管理*/
class Activity extends Common
{
    /*构造方法*/
    public function _initialize()
    {
        /*重载父类构造方法*/
        parent::_initialize();
    }
    
    /*活动管理 start*/
    /*活动列表*/
    public function message() {
        /*查询活动*/
        /*获取参数*/
        $limit = input('limit');
        $search = input('search');
        $this->assign('limit', $limit);
        $this->assign('search', $search);
        $pagelimit = !empty($limit) ? $limit : config('paginate.list_rows'); /*每页条数*/
        if (!empty($search)) {
            $where_list['concat(name)'] = array('like', '%' . $search . '%'); /*搜索*/
        }
        /*分页参数*/
        $paginate = array('query' => array( /*url额外参数*/
            'limit' => $limit, /*每页条数*/
            'search' => $search, /*搜索*/
        ));
        /*条件*/
        $where_list['type'] = array('neq', 9);
        $list = Db::name('wine_activity')->where($where_list)->order('id desc')->paginate($pagelimit,false,$paginate);
        $lists = array();
        foreach ($list as $k => $v) {
            $lists[$k] = $v;
            /*状态*/
            if ($v['status'] == 1) {
                $lists[$k]['status'] = '启用';
            } else {
                $lists[$k]['status'] = '禁用';
            }
        }
        $page = $list->render(); /*获取分页显示*/
        $this->assign('list', $lists);
        $this->assign('page', $page);
        /*分配变量*/
        $this->assign('title', '活动列表'); /*页面标题*/
        $this->assign('keywords', '活动列表'); /*页面关键词*/
        $this->assign('description', '活动列表'); /*页面描述*/
        /*调用模板*/
        return $this->fetch(TEMP_FETCH);
    }
    /*编辑活动*/
    public function messageedit() {
        /*获取参数*/
        $id = input('id'); /*Id*/
        if ($id) {
            /*编辑*/
            /*查询参数*/
            $where['id'] = $id;
            $content = Db::name('wine_activity')->where($where)->find();
            foreach ($content as $k => $v) {
                /*解析图标、图片*/
                if ($k == 'ico' && !empty($content['ico'])) {
                    $ico = explode(',', $v);
                    $content['ico'] = array();
                    foreach ($ico as $k2 => $v2) {                      
                        $basename = explode('/',$v2);
                        $basename = array_pop($basename);
                        $content['ico'][$k2] = array('filename' => $v2, 'basename' => $basename);
                    }
                }
                if ($k == 'picture' && !empty($content['picture'])) {
                    $picture = explode(',', $v);
                    $content['picture'] = array();
                    foreach ($picture as $k2 => $v2) {                      
                        $basename = explode('/',$v2);
                        $basename = array_pop($basename);
                        $content['picture'][$k2] = array('filename' => $v2, 'basename' => $basename);
                    }
                }
                if ($k == 'open_start' && !empty($content['open_end'])) {
                    $content['date'] = date('Y-m-d', $content['open_start']).' ~ '.date('Y-m-d', $content['open_end']);
                }
            }
            $this->assign('content', $content); /*内容*/
            /*组装post提交变量*/
            $submit = array('name' => '确认编辑', 'url' => '?id=' . $id);
            $this->assign('submit', $submit);
            /*分配变量*/
            $this->assign('title', '编辑活动'); /*页面标题*/
            $this->assign('keywords', '编辑活动'); /*页面关键词*/
            $this->assign('description', '编辑活动'); /*页面描述*/
        } else {
            /*添加*/
            /*组装post提交变量*/
            $submit = array('name' => '确认添加','url' => '?a=add');
            $this->assign('submit', $submit);
            /*分配变量*/
            $this->assign('title', '添加活动'); /*页面标题*/
            $this->assign('keywords', '添加活动'); /*页面关键词*/
            $this->assign('description', '添加活动'); /*页面描述*/
        }
        /*提交*/
        if ($_POST) {
            /*获取参数*/
            $nowtime = time();
            $open_date = input('open_date');
            /*验证参数*/
            if (empty(input('name')) || empty(input('open_date'))) {
                echo '<script>$(document).ready(function(){alertBox("名称不可为空..","'.$_SERVER['HTTP_REFERER'].'");})</script>';
                die();
            } else {
                $open_date = explode('~', $open_date);
                // var_dump($open_date);
                $open_start = strtotime($open_date[0]);
                $open_end = strtotime($open_date[1]);
                /*组装参数*/
                $data = array(
                    'user_id' => $this->user_id,
                    'type' => 1,
                    'name' => input('name'), /*名称*/
                    'ico' => input('ico'), /*图标*/
                    'picture' => input('picture'), /*缩略图*/
                    'description' => input('description'), /*简介*/
                    'content' => input('content'), /*内容*/
                    'order' => input('order'), /*排序  默认为100*/
                    'status' => input('status'), /*状态  默认为1:启用|0:禁用*/
                    'edittime' => $nowtime, /*修改时间*/
                    'open_start' => $open_start, /*修改时间*/
                    'open_end' => $open_end, /*修改时间*/
                );          
                if ($id) {
                    /*编辑*/
                    $result = Db::name('wine_activity')->where('id',$id)->update($data);
                    $alert_success = '恭喜，编辑成功！';
                    $alert_error = '抱歉，编辑失败！';
                    $log_in='编辑消息ID为:'.$id.' 的活动';
                } else {
                    /*添加*/
                    $data['addtime'] = $nowtime; /*添加时间*/
                    /*插入数据*/
                    $result = Db::name('wine_activity')->insertGetId($data);
                    $log_in='添加消息ID为:'.$result.' 的活动';
                    $alert_success = '恭喜，添加成功！';
                    $alert_error = '抱歉，添加失败！';
                }
                /*判断结果集*/
                if ($result) {
                    $this->write_log($log_in);
                    echo '<script>$(document).ready(function(){alertBox("' . $alert_success . '","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
                } else {
                    echo '<script>$(document).ready(function(){alertBox("' . $alert_error . '","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
                }
            }
        } else {
            /*调用模板*/
            return $this->fetch(TEMP_FETCH);
        }
    }
    /*删除活动*/
    public function messagedel() {
        /*接收参数*/
        $id = input('id'); /*分类ID*/
        $result = Db::name('wine_activity')->where('id='.$id)->delete();
        if ($result) {
            $log_in='删除消息ID为:'.$id.' 的活动';
            $this->write_log($log_in);
            echo '<script>$(document).ready(function(){alertBox("恭喜，删除成功！","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
        } else {
            echo '<script>$(document).ready(function(){alertBox("抱歉，删除失败！","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
        }
    }
    /*活动管理 end*/

    public function coupon()
    {
        /*查询活动*/
        /*获取参数*/
        $limit = input('limit');
        $search = input('search');
        $this->assign('limit', $limit);
        $this->assign('search', $search);
        $pagelimit = !empty($limit) ? $limit : config('paginate.list_rows'); /*每页条数*/
        if (!empty($search)) {
            $where_list['concat(title,id)'] = array('like', '%' . $search . '%'); /*搜索*/
        }
        /*分页参数*/
        $paginate = array('query' => array( /*url额外参数*/
            'limit' => $limit, /*每页条数*/
            'search' => $search, /*搜索*/
        ));
        /*条件*/
        $where_list['status'] = array('neq', 9);
        $list = Db::name('wine_activity_coupon')->where($where_list)->order('id desc')->paginate($pagelimit,false,$paginate);
        $lists = array();
        foreach ($list as $k => $v) {
            $lists[$k] = $v;
            /*状态*/
            if ($v['status'] == 1) {
                $lists[$k]['status'] = '启用';
            } else {
                $lists[$k]['status'] = '禁用';
            }
        }
        $page = $list->render(); /*获取分页显示*/
        $this->assign('list', $lists);
        $this->assign('page', $page);
        /*分配变量*/
        $this->assign('title', '优惠卷列表'); /*页面标题*/
        $this->assign('keywords', '优惠卷列表'); /*页面关键词*/
        $this->assign('description', '优惠卷列表'); /*页面描述*/
        /*调用模板*/
        return $this->fetch(TEMP_FETCH);
    }

    public function couponedit()
    {
        /*获取商品类别*/
        $where_group['status'] = array('eq', 1);
        $where_group['pid'] = array('eq', 0);
        $field = 'id,name';
        $group = Db::name('wine_shop_cate')->field($field)->where($where_group)->select();
        $this->assign('group', $group);
        /*获取参数*/
        $id = input('id'); /*Id*/
        if ($id) {
            /*编辑*/
            /*查询参数*/
            $where['id'] = $id;
            $content = Db::name('wine_activity_coupon')->where($where)->find();
            foreach ($content as $k => $v) {
                /*有效时间范围*/
                if ($k == 'daystart' && !empty($content['daystart'])) {
                    $content['daystart'] = date('Y/m/d',$v);
                }                
            }
            $this->assign('content', $content); /*内容*/
            /*组装post提交变量*/
            $submit = array('name' => '确认编辑', 'url' => '?id=' . $id);
            $this->assign('submit', $submit);
            /*分配变量*/
            $this->assign('title', '编辑活动'); /*页面标题*/
            $this->assign('keywords', '编辑活动'); /*页面关键词*/
            $this->assign('description', '编辑活动'); /*页面描述*/
        } else {
            /*添加*/
            /*组装post提交变量*/
            $submit = array('name' => '确认添加','url' => '?a=add');
            $this->assign('submit', $submit);
            /*分配变量*/
            $this->assign('title', '添加活动'); /*页面标题*/
            $this->assign('keywords', '添加活动'); /*页面关键词*/
            $this->assign('description', '添加活动'); /*页面描述*/
        }
        /*提交*/
        if ($_POST) {
            /*获取参数*/
            $nowtime = time();
            $title = input('title');/*名称*/
            $type = input('type');/*类型 1.满减卷 2.通用卷  3.首单卷*/
            $minimum = input('minimum');/*最低消费金额*/
            $favourable = input('favourable');/*优惠金额*/
            $favourtype = input('favourtype');/*优惠券有效期 1.领取后N天内有效 2.时间段*/
            $day = input('day');/*有效天数*/
            $daytime = input('daytime');/*有效时间范围*/
            $centrality = input('centrality');/*加入领卷中心 1.加入 | 2.不加入*/
            $order = input('order');/*排序*/
            $status = input('status');/*状态*/
            $group_id = input('group_id');/*指定商品类别ID*/
            if ($type != 1) {
                $minimum = 0;
            }
            if ($favourtype == 2) {
                $daystart = strtotime($daytime);
                $dayend = strtotime($daytime);
            } else {
                $daystart = $nowtime;
                $dayend = $nowtime;
            }
            /*验证参数*/
            if (empty($title)||empty($type)||!isset($minimum)||!isset($favourable)||!isset($favourtype)||!isset($day)||!isset($daytime)||empty($order)||!isset($status)) {
                echo '<script>$(document).ready(function(){alertBox("参数缺失","'.$_SERVER['HTTP_REFERER'].'");})</script>';
            } else {
                /*组装参数*/
                $data = array(
                    'title' => $title,
                    'type' => $type,
                    'minimum' => $minimum,
                    'favourable' => $favourable,
                    'favourtype' => $favourtype,
                    'day' => $day,
                    'daystart' => $daystart,
                    'dayend' => $dayend,
                    'centrality' => $centrality,
                    'order' => $order,
                    'group_id' => $group_id,
                    'status' => $status,
                    'edittime' => $nowtime
                );
                if ($id) {
                    /*编辑*/
                    $result = Db::name('wine_activity_coupon')->where('id',$id)->update($data);
                    $alert_success = '恭喜，编辑成功！';
                    $alert_error = '抱歉，编辑失败！';
                    $log_in='编辑ID为:'.$id.' 的优惠卷';
                } else {
                    /*添加*/
                    $data['addtime'] = $nowtime; /*添加时间*/
                    /*插入数据*/
                    $result = Db::name('wine_activity_coupon')->insertGetId($data);
                    $log_in='添加ID为:'.$result.' 的优惠卷';
                    $alert_success = '恭喜，添加成功！';
                    $alert_error = '抱歉，添加失败！';
                }
                /*判断结果集*/
                if ($result) {
                    $this->write_log($log_in);
                    echo '<script>$(document).ready(function(){alertBox("' . $alert_success . '","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
                } else {
                    echo '<script>$(document).ready(function(){alertBox("' . $alert_error . '","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
                }
            }
        } else {
            /*调用模板*/
            return $this->fetch(TEMP_FETCH);
        }
    }
    /*删除优惠卷*/
    public function coupondel() {
        /*接收参数*/
        $id = input('id'); /*分类ID*/
        $update['status'] = 9;
        $result = Db::name('wine_activity_coupon')->where('id='.$id)->update($update);
        if ($result) {
            $log_in='删除ID为:'.$id.' 的优惠卷';
            $this->write_log($log_in);
            echo '<script>$(document).ready(function(){alertBox("恭喜，删除成功！","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
        } else {
            echo '<script>$(document).ready(function(){alertBox("抱歉，删除失败！","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
        }
    }

    /*秒杀列表*/
    /*产品列表*/
    public function secKill(){    
        /*查询内容*/
        /*获取参数*/
        $limit = input('limit');
        $search = input('search');
        $this->assign('limit', $limit);
        $this->assign('search', $search);
        $pagelimit = !empty($limit) ? $limit : config('paginate.list_rows'); /*每页条数*/
        /*搜索*/
        if (!empty($search)) {
            $where_goods['name'] = array('like', '%' . $search . '%'); /*搜索*/
            $where_goods['status'] = array('eq', 1);
            $goods = Db::name('wine_shop_goods')->field('id')->where($where_goods)->select();
            $goods_str = '';
            if ($goods) {
                foreach ($goods as $k => $v) {
                    $goods_str.= ','.$v['id'];
                }
            }
            $where_list['s.gid'] = array('in', $goods_str); /*搜索*/
        }
        /*条件*/
        $where_list['s.status'] = array('eq', 1);
        /*分页参数*/
        $paginate = array('query' => array( /*url额外参数*/
            'limit' => $limit, /*每页条数*/
            'search' => $search, /*搜索*/
        ));
        /*条件*/
        // $order = array('order' => 'desc', 'id' => 'desc');
        $field = 'g.id,count(s.gid),g.name';
        $list = Db::name('wine_seckill')
            ->alias('s')
            ->join('wine_shop_goods g','s.gid = g.id')
            ->where($where_list)
            ->field($field)
            ->group('s.gid')
            ->paginate($pagelimit,false,$paginate);
        // var_dump($list);
        $page = $list->render(); /*获取分页显示*/
        $this->assign('list', $list);
        $this->assign('page', $page);
        /*分配变量*/
        $this->assign('title', '秒杀列表'); /*页面标题*/
        $this->assign('keywords', '秒杀列表'); /*页面关键词*/
        $this->assign('description', '秒杀列表'); /*页面描述*/
        /*调用模板*/
        return $this->fetch(TEMP_FETCH);
    }
    /*编辑产品*/
    public function secKilledit(){        
        /*所属分类*/
        $where_category['status'] = 1;
        $list = Db::name('wine_shop_cate')->field('id,name,path,pid')->where($where_category)->order('concat(`path`,`id`)')->select();
        $lists = array();
        foreach ($list as $k => $v) {
            $lists[$k] = $v;
            /*名称*/
            $path_arr = explode(',', trim($v['path'], ','));
            $path_arr_count = count($path_arr) - 1;
            $lists[$k]['path_count'] = $path_arr_count; //层级数
            $path_str = '';
            if ($path_arr_count > 0) {
                for ($i = 1;$i < $path_arr_count;$i++) {
                    $path_str.= '&nbsp;';
                }
                $path_str.= $path_str . '└ ';
            }
            $lists[$k]['name'] = $path_str . $v['name'];        
        }
        $this->assign('category', $lists);
        /*添加*/
        /*组装post提交变量*/
        $submit = array('name' => '确认添加', 'url' => '?a=add');
        $this->assign('submit', $submit);
        /*分配变量*/
        $this->assign('title', '添加秒杀商品'); /*页面标题*/
        $this->assign('keywords', '添加秒杀商品'); /*页面关键词*/
        $this->assign('description', '添加秒杀商品'); /*页面描述*/
        /*提交*/
        if ($_POST) {
            /*获取参数*/
            // echo "<pre>";
            // var_dump($_POST);
            $gid = input('seckill');/*商品ID*/
            $price = input('price');/*出售价*/
            $price_seckill = input('price_seckill');/*秒杀价*/
            // $price_seckill_num = input('price_seckill_num');/*秒杀数量*/
            $total = input('total');/*秒杀库存*/
            $spec = input('spec');/*规格状态 1.启用 | 0.禁用*/
            $val = input('val/a');/*规格表数据*/
            $open_time = input('open_time/a');/*秒杀开放时间*/
            $max_number = input('max_number')?input('max_number'):0;/*限购数量*/
            $single = input('single')?input('single'):0;/*限购单数*/
            $open_date = input('open_date');/*开放日期*/
            if (empty($gid)) {
                echo '<script>$(document).ready(function(){alertBox("商品不能为空","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
                die();
            } else if (empty($price_seckill) ||empty($price) || empty($open_time) || empty($open_date)){
                echo '<script>$(document).ready(function(){alertBox("参数缺失","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
                die();
            }
            $open_date = explode('~', $open_date);
            // var_dump($open_date);
            $open_start = strtotime($open_date[0]);
            $open_end = strtotime($open_date[1]);
            $i = 0;
            while ($open_start <= $open_end) {
                foreach ($open_time as $k => $v) {
                    $data_seckill = array();
                    $data_seckill = array(
                            'gid' => $gid,//商品id
                            'price' => $price,//出售价
                            'price_seckill' => $price_seckill,//秒杀价格
                            'open_time' => $v,//开放时间段 0-23 分别对应24小时
                            'max_number' => $max_number,//限购数量
                            'single' => $single,//限购单数
                            'open_date' => $open_start,//开放日期
                            // 'seckill_num' => $price_seckill_num,//秒杀数量
                            // 'seckill_xl' => 0,//秒杀销量
                            'spec' => $spec,//规格状态 1.启用 | 0.禁用
                            'total' => $total,//秒杀库存
                            'addtime' => time(),
                            'edittime' => time()
                        );
                    $sid = Db::name('wine_seckill')->insertGetId($data_seckill);
                    // if ($spec == 1) {
                    //     foreach ($val as $v) {
                    //         $val_data = $v;
                    //         $val_data['gid'] = $gid;
                    //         $val_data['seckill_id'] = $sid;
                    //         Db::name('wine_seckill_spec')->insert($val_data);
                    //     }
                    // }
                }
                if ($i++ == 30) {
                    echo '<script>$(document).ready(function(){alertBox("最多添加时长为1个月,多余的自动过滤","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
                    die();
                }
                $log_info = '添加商品ID为:' .$gid.' 的秒杀商品';
                $this->write_log($log_info);
                $open_start = $open_start + (60*60*24);
            }
            /*判断结果集*/
            $alert_success = '添加秒杀成功';
            $alert_error = '添加秒杀失败';
            if ($sid) {
                echo '<script>$(document).ready(function(){alertBox("'.$alert_success.'","'.url('tztx.activity/secKill').'");})</script>';
            } else {
                echo '<script>$(document).ready(function(){alertBox("'.$alert_error.'","'.$_SERVER['HTTP_REFERER'].'");})</script>';
            }
        } else {
            /*调用模板*/
            return $this->fetch(TEMP_FETCH);
        }
    }
    /*删除商品秒杀*/
    public function secKilldel() {
        /*接收参数*/
        $id = input('id'); /*商品ID*/
        /*秒杀商品删除*/
        $update['status'] = 9;
        $result = Db::name('wine_seckill')->where('gid',$id)->update($update);
        if ($result) {
            echo '<script>$(document).ready(function(){alertBox("恭喜，删除成功！","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
        } else {
            echo '<script>$(document).ready(function(){alertBox("抱歉，删除失败！","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
        }
    }

    /*秒杀- 开放时间 */
    public function secKilltime()
    {
        if ($_POST) {
            $this->write_log('修改了秒杀开放时间');
            $open_time = input('open_time/a');
            Db::name('wine_seckill_time')->delete(true);
            if (is_array($open_time)) {
                foreach ($open_time as $value) {
                    $date['addtime'] = time();
                    $date['open'] = $value;
                    Db::name('wine_seckill_time')->insert($date);
                }
                echo '<script>$(document).ready(function(){alertBox("恭喜，修改成功! ","' . url('tztx.activity/secKilltime') . '");})</script>';
            }
        }
        $list = Db::name('wine_seckill_time')->field('open')->select();
        $this->assign('list', $list);
        /*分配变量*/
        $this->assign('title', '开放时间列表'); /*页面标题*/
        $this->assign('keywords', '开放时间列表'); /*页面关键词*/
        $this->assign('description', '开放时间列表'); /*页面描述*/
        /*调用模板*/
        return $this->fetch(TEMP_FETCH);
    }
    /*产品管理 end*/













    
}