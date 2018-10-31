<?php
namespace app\web\controller\tztx;
use app\web\controller\Common;
use think\Request;
use think\Db;
/*资讯管理*/
class Info extends Common
{
    /*构造方法*/
    public function _initialize()
    {
        /*重载父类构造方法*/
        parent::_initialize();
    }
    
    /*系统消息管理 start*/
    /*系统消息列表*/
    public function message() {
        /*查询系统消息*/
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
        $list = Db::name('wine_message')->where($where_list)->order('id desc')->paginate($pagelimit,false,$paginate);
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
        $this->assign('title', '系统消息列表'); /*页面标题*/
        $this->assign('keywords', '系统消息列表'); /*页面关键词*/
        $this->assign('description', '系统消息列表'); /*页面描述*/
        /*调用模板*/
        return $this->fetch(TEMP_FETCH);
    }
    /*编辑系统消息*/
    public function messageedit() {
        /*获取参数*/
        $id = input('id'); /*Id*/
        if ($id) {
            /*编辑*/
            /*查询参数*/
            $where['id'] = $id;
            $content = Db::name('wine_message')->where($where)->find();
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
            }
            $this->assign('content', $content); /*内容*/
            /*组装post提交变量*/
            $submit = array('name' => '确认编辑', 'url' => '?id=' . $id);
            $this->assign('submit', $submit);
            /*分配变量*/
            $this->assign('title', '编辑系统消息'); /*页面标题*/
            $this->assign('keywords', '编辑系统消息'); /*页面关键词*/
            $this->assign('description', '编辑系统消息'); /*页面描述*/
        } else {
            /*添加*/
            /*组装post提交变量*/
            $submit = array('name' => '确认添加','url' => '?a=add');
            $this->assign('submit', $submit);
            /*分配变量*/
            $this->assign('title', '添加系统消息'); /*页面标题*/
            $this->assign('keywords', '添加系统消息'); /*页面关键词*/
            $this->assign('description', '添加系统消息'); /*页面描述*/
        }
        /*提交*/
        if ($_POST) {
            /*获取参数*/
            $nowtime = time();
            /*验证参数*/
            if (empty(input('name'))) {
                echo '<script>$(document).ready(function(){alertBox("名称不可为空..","'.$_SERVER['HTTP_REFERER'].'");})</script>';
            } else {
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
                );          
                if ($id) {
                    /*编辑*/
                    $result = Db::name('wine_message')->where('id',$id)->update($data);
                    $alert_success = '恭喜，编辑成功！';
                    $alert_error = '抱歉，编辑失败！';
                    $log_in='编辑消息ID为:'.$id.' 的系统消息';
                } else {
                    /*添加*/
                    $data['addtime'] = $nowtime; /*添加时间*/
                    /*插入数据*/
                    $result = Db::name('wine_message')->insertGetId($data);
                    $log_in='添加消息ID为:'.$result.' 的系统消息';
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
    /*删除系统消息*/
    public function messagedel() {
        /*接收参数*/
        $id = input('id'); /*分类ID*/
        $result = Db::name('wine_message')->where('id='.$id)->delete();
        if ($result) {
            $log_in='删除消息ID为:'.$id.' 的系统消息';
            $this->write_log($log_in);
            echo '<script>$(document).ready(function(){alertBox("恭喜，删除成功！","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
        } else {
            echo '<script>$(document).ready(function(){alertBox("抱歉，删除失败！","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
        }
    }
    /*系统消息管理 end*/

    /*关于我们*/
    public function messageuser() {
        /*获取参数*/
        $id = input('id'); /*Id*/
        if ($id) {
            /*编辑*/
            /*查询参数*/
            $where['id'] = $id;
            $content = Db::name('wine_message')->where($where)->find();
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
            }
            $this->assign('content', $content); /*内容*/
            /*组装post提交变量*/
            $submit = array('name' => '确认编辑', 'url' => '?id=' . $id);
            $this->assign('submit', $submit);
            /*分配变量*/
            $this->assign('title', '编辑关于我们'); /*页面标题*/
            $this->assign('keywords', '编辑关于我们'); /*页面关键词*/
            $this->assign('description', '编辑关于我们'); /*页面描述*/
        }
        /*提交*/
        if ($_POST) {
            /*获取参数*/
            $nowtime = time();
            /*验证参数*/
            if (empty(input('name'))) {
                echo '<script>$(document).ready(function(){alertBox("名称不可为空..","'.$_SERVER['HTTP_REFERER'].'");})</script>';
                die();
            } else {
                /*组装参数*/
                $data = array(
                    'type' => '9',
                    'name' => input('name'), /*名称*/
                    'ico' => input('ico'), /*图标*/
                    'picture' => input('picture'), /*缩略图*/
                    'description' => input('description'), /*简介*/
                    'content' => input('content'), /*内容*/
                    'order' => input('order'), /*排序  默认为100*/
                    'status' => input('status'), /*状态  默认为1:启用|0:禁用*/
                    'edittime' => $nowtime, /*修改时间*/
                );          
                if ($id) {
                    /*编辑*/
                    $result = Db::name('wine_message')->where('id',$id)->update($data);
                    $alert_success = '恭喜，编辑成功！';
                    $alert_error = '抱歉，编辑失败！';
                }
                /*判断结果集*/
                if ($result) {
                    $this->write_log("编辑了关于我们");
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

    /*系统消息管理 start*/
    /*系统消息列表*/
    public function complain() {
        /*查询系统消息*/
        /*获取参数*/
        $limit = input('limit');
        $search = input('search');
        $this->assign('limit', $limit);
        $this->assign('search', $search);
        $pagelimit = !empty($limit) ? $limit : config('paginate.list_rows'); /*每页条数*/
        if (!empty($search)) {
            $where_list['concat(u.nickname,s.content)'] = array('like', '%' . $search . '%'); /*搜索*/
        }
        /*分页参数*/
        $paginate = array('query' => array( /*url额外参数*/
            'limit' => $limit, /*每页条数*/
            'search' => $search, /*搜索*/
        ));
        /*条件*/
        $where_list['s.status'] = array('neq', 9);
        $list = Db::name('wine_information_suggest')
                    ->alias('s')
                    ->join('wine_users u', 's.uid = u.id')
                    ->field('s.id,u.nickname,s.content,s.addtime')
                    ->where($where_list)
                    ->order('s.id desc')
                    ->paginate($pagelimit,false,$paginate);
        $lists = array();
        foreach ($list as $k => $v) {
            $lists[$k] = $v;
            $lists[$k]['addtime'] = date('Y-m-d H:i:s', $v['addtime']);
        }
        $page = $list->render(); /*获取分页显示*/
        $this->assign('list', $lists);
        $this->assign('page', $page);
        /*分配变量*/
        $this->assign('title', '投诉建议列表'); /*页面标题*/
        $this->assign('keywords', '投诉建议列表'); /*页面关键词*/
        $this->assign('description', '投诉建议列表'); /*页面描述*/
        /*调用模板*/
        return $this->fetch(TEMP_FETCH);
    }
    /*查看系统消息*/
    public function complainedit() {
        /*获取参数*/
        $id = input('id'); /*Id*/
        if (empty($id)) {
                echo '<script>$(document).ready(function(){alertBox("ID不可为空..","'.$_SERVER['HTTP_REFERER'].'");})</script>';
                die();
            }
        /*查询参数*/
       /*条件*/
        $where_list['s.status'] = array('neq', 9);
        $where_list['s.id'] = array('eq', $id);
        $list = Db::name('wine_information_suggest')
                    ->alias('s')
                    ->join('wine_users u', 's.uid = u.id')
                    ->field('s.id,u.nickname,s.content,s.addtime')
                    ->where($where_list)
                    ->find();
        $lists = $list;
        $lists['addtime'] = date('Y-m-d H:i:s', $list['addtime']);
        $this->assign('list', $lists); /*内容*/
        /*分配变量*/
        $this->assign('title', '投诉建议列表'); /*页面标题*/
        $this->assign('keywords', '投诉建议列表'); /*页面关键词*/
        $this->assign('description', '投诉建议列表'); /*页面描述*/
        return $this->fetch(TEMP_FETCH);
    }












}