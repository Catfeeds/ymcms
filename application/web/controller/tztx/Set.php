<?php
namespace app\web\controller\tztx;
use app\web\controller\Common;
use think\Request;
use think\Db;
/*设置中心*/
class Set extends Common
{
    public $pageHead;/*定义页面头部*/
    public $pageFoot;/*定义页面底部*/
    public $temp_id;/*模板ID*/

    /*构造方法*/
    public function _initialize()
    {
        /*重载父类构造方法*/
        parent::_initialize();
        
    }
    /*轮播图列表*/
    public function slideshowlist(){
        /*查询分类*/
        /*获取参数*/
        $limit = input('limit');
        $search = input('search');
        $where_list=array();
        if (!empty($search)) {
            $where_list['concat(advname)'] = array('like', '%' . $search . '%'); /*搜索*/
        }
        $this->assign('limit', $limit);
        $this->assign('search', $search);
        $pagelimit = !empty($limit) ? $limit : config('paginate.list_rows'); /*每页条数*/
        $paginate = array('query' => array( /*url额外参数*/
            'limit' => $limit,/*每页条数*/
            'search' => $search,/*搜索*/
        ));
        $list = Db::name('wine_adv')->where($where_list)->paginate($pagelimit, false, $paginate);
        $lists=array();
        foreach ($list as $k=>$v){
            if($v['enabled']==1){
                $v['enabled']='显示';
            }else{
                $v['enabled']='不显示';
            }
            $lists[$k] = $v;
        }
        $page = $list->render(); /*获取分页显示*/
        $this->assign('list', $lists);
        $this->assign('page', $page);
        /*分配变量*/
        $this->assign('title', '轮播图列表'); /*页面标题*/
        $this->assign('keywords', '轮播图列表'); /*页面关键词*/
        $this->assign('description', '轮播图列表'); /*页面描述*/
        /*调用模板*/
        return $this->fetch(TEMP_FETCH);
    }
    /*编辑轮播图*/
    public function slideshowpost(){
        $id = input('id');
        if(empty($id)){
            $submit = array('name' => '确认添加', 'url' => '?a=add');
            $this->assign('submit', $submit);
            $this->assign('title', '添加轮播图'); /*页面标题*/
            $this->assign('keywords', '添加轮播图'); /*页面关键词*/
            $this->assign('description', '添加轮播图'); /*页面描述*/
        }else{
            $submit = array('name' => '确认编辑', 'url' => '?a=add');
            $this->assign('submit', $submit);
            $this->assign('title', '编辑轮播图'); /*页面标题*/
            $this->assign('keywords', '编辑轮播图'); /*页面关键词*/
            $this->assign('description', '编辑轮播图'); /*页面描述*/
            $list=Db::name('wine_adv')->where('id',$id)->find();
            $ico = explode(',',$list['thumb']);
            /*
            $essay=Db::name('pair_essay')->where('id',$list['link'])->field('id,title')->find();
            $this->assign('link', $essay);
            */
            $list['thumb'] = array();
            foreach ($ico as $k2 => $v2) {
                $basename = explode('/',$v2);
                $basename = array_pop($basename);
                $list['thumb'][$k2] = array('filename' => $v2, 'basename' => $basename);
            }
            $this->assign('content', $list);
        }
        if($_POST){
            /*组合数据*/
            $title=input('advname');
            $ico=input('ico');
            if(empty($title)){
                echo '<script>$(document).ready(function(){alertBox("名称不可为空..","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
            }else {
                $files_video = request()->file('video');/*获取表单上传文件*/
                if(!empty($files_video)){
                    /*七牛上传*/
                    $video = Upload::fileput($_FILES['video']);
                    //$video = $this->filetp($files_video);;
                }else{
                    $video = '';
                }
                $data = array(
                    'type' => 2,/*类型 1.活动 2.商品详情 3.超链接*/
                    'link'  => input('link'),/*地址*/
                    'edittime' 	=> time(),/*时间*/
                    'advname' 	=> input('advname'), /*资讯标题*/
                    'thumb' 	=> input('thumb'), /*资讯图片*/
                    'enabled'  => input('enabled'), /*是否显示*/
                    'displayorder'  => input('displayorder'), /*排序*/
                );
                if ($id) {
                    /*编辑*/
                    // if(!empty($video)){$data['video']= $video;/*资讯视频*/}
                    $log_in='编辑轮播图ID为'.$id.' 的图片';
                    $result = Db::name('wine_adv')->where('id=' . $id)->update($data);
                    $alert_success = '恭喜，编辑成功！';
                    $alert_error = '抱歉，编辑失败！';
                } else {
                    /*插入数据*/
                    $data['addtime'] = time();
                    $result = Db::name('wine_adv')->insertGetId($data);
                    $log_in='添加轮播图ID为'.$result.' 的图片';
                    $alert_success = '恭喜，添加成功！';
                    $alert_error = '抱歉，添加失败！';
                }
                /*判断结果集*/
                if ($result) {
                    $this->write_log($log_in);
                    echo '<script>$(document).ready(function(){alertBox("' . $alert_success . '","' . url('tztx.set/slideshowlist') . '");})</script>';
                } else {
                    echo '<script>$(document).ready(function(){alertBox("' . $alert_error . '","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
                }
            }
        }
        /*组装post提交变量*/
        return $this->fetch(TEMP_FETCH);
    }
    /*删除轮播图*/
    public function slideshowdelete(){
        /*接收参数*/
        $id = input('id'); /*分类ID*/
        /*不存在子类*/
        $result = Db::name('wine_adv')->where('id='.$id)->delete();
        if ($result) {
            $this->write_log('删除轮播图ID为:'.$id.' 的图片');
            echo '<script>$(document).ready(function(){alertBox("恭喜，删除成功！","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
        } else {
            echo '<script>$(document).ready(function(){alertBox("抱歉，删除失败！","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
        }
    }
    /*轮播图选择资讯*
    public function slideshowessay(){
        $search = input('search');
        $where_list=array();
        if (!empty($search)) {
            $where_list['concat(title,describe2,content)'] = array('like', '%' . $search . '%'); /*搜索*
        }
        $list = Db::name('pair_essay')
            ->where($where_list)
            ->field('id,title')
            ->limit(10)
            ->select();
        return json_encode($list);
    }
    */
    /*员工操作日志列表*/
    public function loginlist(){
        /*员工姓名，登录ip，进行的操作，操作时间*/
        /*查询分类*/
        /*获取参数*/
        $limit = input('limit');
        $search = input('search');
        $where_list=array();
        if (!empty($search)) {
            $where_list['concat(l.motion,u.name)'] = array('like', '%' . $search . '%'); /*搜索*/
        }
        $this->assign('limit', $limit);
        $this->assign('search', $search);
        $pagelimit = !empty($limit) ? $limit : config('paginate.list_rows'); /*每页条数*/
        $paginate = array('query' => array( /*url额外参数*/
            'limit' => $limit,/*每页条数*/
            'search' => $search,/*搜索*/
        ));
        $field = 'u.name,l.motion,l.logon_ip,l.logon_time';
        $list = Db::name('wine_log')
            ->alias('l')
            ->join('user u ','u.id= l.staff_name')
            ->field($field)
            ->where($where_list)
            ->order('l.logon_time', 'desc')
            ->paginate($pagelimit, false, $paginate);
        $lists=array();
        foreach ($list as $k=>$v){
            $v['logon_time']=date("Y.m.d H:i:s",$v['logon_time']);
            $lists[$k]=$v;
        }
        $page = $list->render(); /*获取分页显示*/
        $this->assign('list', $lists);
        $this->assign('page', $page);
        /*分配变量*/
        $this->assign('title', '员工日志列表'); /*页面标题*/
        $this->assign('keywords', '员工日志列表'); /*页面关键词*/
        $this->assign('description', '员工日志列表'); /*页面描述*/
        /*调用模板*/
        return $this->fetch(TEMP_FETCH);
    }

    /*机构管理 start*/
    /*机构列表*/
    public function grouplist() {
        /*获取参数*/
        $limit = input('limit');
        $search = input('search');
        $this->assign('limit', $limit);
        $this->assign('search', $search);
        $pagelimit = !empty($limit) ? $limit : config('paginate.list_rows');/*每页条数*/
        if (!empty($search)) {
            $where_group_list['concat(name,description)'] = array('like', '%' . $search . '%');/*搜索*/
        }
        /*查询机构*/
        $where_group_list['site_id'] = $this->site_id;
        $where_group_list['type'] = 2; /*类型：站点管理员*/
        $where_group_list['admin'] = 1; /*类型：站点管理员*/
        /*分页参数*/
        $paginate = array('query' => array( /*url额外参数*/
        'limit' => $limit, /*每页条数*/
        'search' => $search, /*搜索*/
        ));
        $list = Db::name('group')->where($where_group_list)->order('id desc')->paginate($pagelimit, false, $paginate);
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
        $this->assign('title', '机构列表'); /*页面标题*/
        $this->assign('keywords', '机构列表'); /*页面关键词*/
        $this->assign('description', '机构列表'); /*页面描述*/
        /*调用模板*/
        return $this->fetch(TEMP_FETCH);
    }
    /*编辑节点*/
    public function groupedit() {
        /*查询站点*/
        $site_all = Db::name('site')->field('id,name')->where('status=1')->select(); /*查询所有站点*/
        $this->assign('site_all', $site_all); /*内容*/
        /*获取参数*/
        $group_id = input('id'); /*机构ID*/
        if ($group_id) {
            /*编辑*/
            /*查询参数*/
            $where_group['id'] = $group_id;
            $group = Db::name('group')->where($where_group)->find();
            foreach ($group as $k => $v) {
                /*解析图标、图片*/
                if ($k == 'ico' && !empty($group['ico'])) {
                    $ico = explode(',', $v);
                    $group['ico'] = array();
                    foreach ($ico as $k2 => $v2) {                      $basename = explode('/',$v2);
                        $basename = array_pop($basename);
                        $group['ico'][$k2] = array('filename' => $v2, 'basename' => $basename);
                    }
                }
                if ($k == 'picture' && !empty($group['picture'])) {
                    $picture = explode(',', $v);
                    $group['picture'] = array();
                    foreach ($picture as $k2 => $v2) {                      $basename = explode('/',$v2);
                        $basename = array_pop($basename);
                        $group['picture'][$k2] = array('filename' => $v2, 'basename' => $basename);
                    }
                }
                /*解析权限集*/
                $group['tree'] = trim($group['tree'], '[');
                $group['tree'] = trim($group['tree'], ']');
            }
            $this->assign('content', $group); /*内容*/
            /*组装post提交变量*/
            $submit = array('name' => '确认编辑', 'url' => '?id=' . $group_id);
            $this->assign('submit', $submit);
            /*分配变量*/
            $this->assign('title', '编辑机构'); /*页面标题*/
            $this->assign('keywords', '编辑机构'); /*页面关键词*/
            $this->assign('description', '编辑机构'); /*页面描述*/
        } else {
            /*添加*/
            /*组装post提交变量*/
            $submit = array('name' => '确认添加', 'url' => '?a=add');
            $this->assign('submit', $submit);
            /*分配变量*/
            $this->assign('title', '添加机构'); /*页面标题*/
            $this->assign('keywords', '添加机构'); /*页面关键词*/
            $this->assign('description', '添加机构'); /*页面描述*/
        }
        /*查询当前用户机构可分配的权限*/
        $group_type = !empty($group['type']) ? $group['type'] : 2;
        if ($group_type == 3) {
            /*系统管理员*/
            $where_tree_list['path'] = array('like', '0,%');
        } else {
            /*网站管理员*/
            $site = Db::name('site')->field('server_id')->where('id=' . $this->site_id)->find(); /*查询站点*/
            $where_tree_list['path'] = array('like', '%,' . $site['server_id'] . ',%');
        }
        /*过滤子级非菜单项*/
        $tree_type = Db::name('tree')->field('id')->where('type=1')->select();
        $tree_type1_ids = '';
        foreach ($tree_type as $k => $v) {
            $tree_type1_ids.= $v['id'] . ',';
            if ($group_type == 3) {
                /*系统管理员过滤非菜单项子集*/
                $tree = Db::name('tree')->select();
                foreach ($tree as $k2 => $v2) {
                    if (strstr($v2['path'], ',' . $v['id'] . ',')) {
                        $tree_type1_ids.= $v2['id'] . ',';
                    }
                }
            }
        }
        $tree_type1_ids = trim($tree_type1_ids, ',');
        $where_tree_list['id'] = array('notin', $tree_type1_ids);
        $group_tree = Db::name('tree')->field('id,name,pid,path')->where($where_tree_list)->select();
        /*编辑时查出已有权限*/
        foreach ($group_tree as $k => $v) {
            if ($group_id) {
                if (!empty($group['tree'])) {
                    $group_tree_old = explode(',', $group['tree']);
                    foreach ($group_tree_old as $k2 => $v2) {
                        if ($v2 == $v['id']) {
                            $group_tree[$k]['on'] = 100;
                        }
                    }
                }
            }
        }
        /*重组权限层级*/
        $group_tree_arr = array();
        foreach ($group_tree as $k => $v) {
            if ($group_type == 3) {
                /*系统管理员*/
                /*重组*/
                if ($v['pid'] == 0) {
                    /*顶级*/
                    $group_tree_arr[$v['id']] = $v;
                } else {
                    /*子级*/
                    $group_tree_arr[$v['pid']]['tree_sub'][$v['id']] = $v;
                }
            } else {
                /*网站管理员*/
                if ($v['pid'] == $site['server_id']) {
                    /*顶级*/
                    $group_tree_arr[$v['id']] = $v;
                } else {
                    /*子级*/
                    $group_tree_arr[$v['pid']]['tree_sub'][$v['id']] = $v;
                }
            }
        }
        /*分配权限变量*/
        $this->assign('tree', $group_tree_arr);
        /*提交*/
        if ($_POST) {
            /*获取参数*/
            $nowtime = time();
            /*组装权限集*/
            $treejson = explode(',', input('tree'));
            $treejson_str = '[';
            foreach ($treejson as $k => $v) {
                if (!empty($v)) {
                    $treejson_str.= $v . ',';
                }
            }
            $treejson_str = trim($treejson_str, ',');
            $treejson_str.= ']';
            /*验证参数*/
            if (empty(input('name'))) {
                echo '<script>$(document).ready(function(){alertBox("名称不可为空..","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
            } else {
                /*组装参数*/
                $data = array(
                'site_id' => $this->site_id, 
                'pid' => 0, /*父结点ID*/
                'path' => '0,', /*路径（如：0,1,2,）*/
                'name' => input('name'), /*名称*/
                'ico' => input('ico'), /*图标*/
                'picture' => input('picture'), /*缩略图*/
                'description' => input('description'), /*简介*/
                'content' => input('content'), /*内容*/
                'tree' => $treejson_str, /*权限集*/
                'type' => 2, /*类型 站点管理*/
                'order' => input('order'), /*排序  默认为100*/
                'status' => input('status'), /*状态  默认为1:启用|0:禁用*/
                'admin' => 1, /*状态  默认为1:启用|0:禁用*/
                'edittime' => $nowtime, /*修改时间*/
                );
                if ($group_id) {
                    /*编辑*/
                    /*更新数据*/
                    $result = Db::name('group')->where('id=' . $group_id)->update($data);
                    $log_in = '编辑ID为:'.$group_id.' 的机构';
                    $alert_success = '恭喜，编辑成功！';
                    $alert_error = '抱歉，编辑失败！';
                } else {
                    /*添加*/
                    $data['addtime'] = $nowtime; /*添加时间*/
                    /*插入数据*/
                    $result = Db::name('group')->insertGetId($data);
                    $log_in = '添加ID为:'.$result.' 的机构';
                    $alert_success = '恭喜，添加成功！';
                    $alert_error = '抱歉，添加失败！';
                }
                /*判断结果集*/
                if ($result) {
                    $this->write_log($log_in);
                    echo '<script>$(document).ready(function(){alertBox("' . $alert_success . '","' . url('tztx.set/grouplist') . '");})</script>';
                } else {
                    echo '<script>$(document).ready(function(){alertBox("' . $alert_error . '","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
                }
            }
        } else {
            /*调用模板*/
            return $this->fetch(TEMP_FETCH);
        }
    }
    /*删除节点*/
    public function groupdel() {
        /*接收参数*/
        $group_id = input('id'); /*机构ID*/
        /*判断是否存在管理员*/
        $group_user = Db::name('user')->where('group_ids=' . $group_id)->find();
        if ($group_user) {
            /*存在子类*/
            echo '<script>$(document).ready(function(){alertBox("抱歉，存在管理员，不允许删除！","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
        } else {
            /*不存在子类*/
            $result = Db::name('group')->where('id=' . $group_id)->delete();
            if ($result) {
                $log_in = '删除ID为:'.$result.' 的机构';
                $this->write_log($log_in);
                echo '<script>$(document).ready(function(){alertBox("恭喜，删除成功！","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
            } else {
                echo '<script>$(document).ready(function(){alertBox("抱歉，删除失败！","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
            }
        }
    }
    /*机构管理管理 end*/
    
    /*员工管理 start*/
    /*员工列表*/
    public function adminlist() {
        /*获取参数*/
        $limit = input('limit');
        $search = input('search');
        $this->assign('limit', $limit);
        $this->assign('search', $search);
        $pagelimit = !empty($limit) ? $limit : config('paginate.list_rows'); /*每页条数*/
        if (!empty($search)) {
            $where_user_list['concat(nickname)'] = array('like', '%' . $search . '%'); /*搜索*/
        }
        /*查询员工*/
        $where_group['site_id'] = $this->site_id;
        $where_group['type'] = 2; /*类型：站点员工*/
        $where_group['admin'] = 1; /*类型：1.平台管理员 0.商户*/
        $group = Db::name('group')->where($where_group)->select();
        $group_ids = '';
        foreach ($group as $k => $v) {
            $group_ids.= $v['id'] . ',';
        }
        $group_ids = trim($group_ids, ',');
        /*查询员工*/
        $where_user_list['site_ids'] = array('eq',$this->site_id); /*管理组：站点员工*/
        $where_user_list['group_ids'] = array('in',$group_ids);
        /*分页参数*/
        $paginate = array('query' => array( /*url额外参数*/
            'limit' => $limit, /*每页条数*/
            'search' => $search, /*搜索*/
        ));
        $list = Db::name('user')->where($where_user_list)->where(function ($query) {
            $query->where('site_ids', $this->site_id)->whereor('site_ids', 'like', $this->site_id . ',%');
        })->whereOr(function ($query) {
            $query->where('site_ids', 'like', '%,' . $this->site_id . ',%')->whereOr('site_ids', 'like', '%,' . $this->site_id);
        })->paginate($pagelimit, false, $paginate);
        $lists = array();
        foreach ($list as $k => $v) {
            $lists[$k] = $v;
            /*状态*/
            if ($v['status'] == 1) {
                $lists[$k]['status'] = '启用';
            } else {
                $lists[$k]['status'] = '禁用';
            }
            /*所属管理组*/
            $group_name = Db::name('group')->where('id',$v['group_ids'])->field('name')->find();
            $lists[$k]['group_name'] = $group_name['name'];
        }
        $page = $list->render(); /*获取分页显示*/
        $this->assign('list', $lists);
        $this->assign('page', $page);
        /*分配变量*/
        $this->assign('title', '员工列表'); /*页面标题*/
        $this->assign('keywords', '员工列表'); /*页面关键词*/
        $this->assign('description', '员工列表'); /*页面描述*/
        /*调用模板*/
        return $this->fetch(TEMP_FETCH);
    }
    /*编辑节点*/
    public function adminedit() {
        /*查询系统员工组*/
        $group_where['site_id'] = $this->site_id;
        $group_where['type'] = 2;
        $group_where['status'] = 1;
        $group = Db::name('group')->field('id,name')->where($group_where)->select();
        $this->assign('group', $group);
        /*查询行业*/
        $industry = Db::name('industry')->field('id,name')->where('status=1')->select();
        $this->assign('industry', $industry);
        /*查询州*
        $state = Db::name('areacode')->field('id,name')->where('pid=0 AND status=1')->select();
        $this->assign('state', $state);
        */
        /*查询省**/    
        $province = Db::name('areacode')->field('id,name')->where('pid=7 AND status=1')->select();
        $this->assign('province', $province);
        /*获取参数*/
        $user_id = input('id'); /*员工ID*/
        if ($user_id) {
            /*编辑*/
            /*查询参数*/
            $where_user['id'] = $user_id;
            $user = Db::name('user')->where($where_user)->find();
            foreach ($user as $k => $v) {
                /*解析图标、图片*/
                if($k == 'ico' && !empty($user['ico'])){
                    $ico = explode(',',$v);
                    $user['ico'] = array();
                    foreach($ico as $k2=>$v2){                      
                        $basename = explode('/',$v2);
                        $basename = array_pop($basename);
                        $user['ico'][$k2] = array(
                            'filename' => $v2,
                            'basename' => $basename
                        );
                    }
                }
                if ($k == 'picture' && !empty($user['picture'])) {
                    $picture = explode(',', $v);
                    $user['picture'] = array();
                    foreach ($picture as $k2 => $v2) {                      
                        $basename = explode('/',$v2);
                        $basename = array_pop($basename);
                        $user['picture'][$k2] = array('filename' => $v2, 'basename' => $basename);
                    }
                }
                /*解析身份证号*/
                if ($k == 'identity' && !empty($user['identity'])) {
                    $identity = json_decode($user['identity']);
                    $user['identity'] = $identity->name;
                }
                /*解析手机号*/
                if ($k == 'phone' && !empty($user['phone'])) {
                    $phone = json_decode($user['phone']);
                    $user['phone'] = $phone->name;
                }
                /*解析邮箱地址*/
                if ($k == 'mail' && !empty($user['mail'])) {
                    $mail = json_decode($user['mail']);
                    $user['mail'] = $mail->name;
                }
                /*解析qq号码*/
                if ($k == 'qq' && !empty($user['qq'])) {
                    $qq = json_decode($user['qq']);
                    $user['qq'] = $qq->name;
                }
                /*解析微信号*/
                if ($k == 'wechat' && !empty($user['wechat'])) {
                    $wechat = json_decode($user['wechat']);
                    $user['wechat'] = $wechat->name;
                }
                /*查询国
                if ($k == 'state' && !empty($user['state'])) {
                    $country = Db::name('areacode')->field('id,name')->where('pid=' . $user['state'] . ' AND status=1')->select();
                    $this->assign('country', $country);
                }
                /*查询省*
                if ($k == 'country' && !empty($user['country'])) {
                    $province = Db::name('areacode')->field('id,name')->where('pid=' . $user['country'] . ' AND status=1')->select();
                    $this->assign('province', $province);
                }
                */
                /*查询市*/
                if ($k == 'province' && !empty($user['province'])) {
                    $city = Db::name('areacode')->field('id,name')->where('pid=' . $user['province'] . ' AND status=1')->select();
                    $this->assign('city', $city);
                }
                /*查询区*/
                if ($k == 'city' && !empty($user['city'])) {
                    $area = Db::name('areacode')->field('id,name')->where('pid=' . $user['city'] . ' AND status=1')->select();
                    $this->assign('area', $area);
                }
                if ($k == 'lat') {
                    if(!empty($user['lat'])&&!empty($user['lng'])){
                        /*解析坐标*/
                        $locaiton_coords = array();
                        $locaiton_coords['lat'] = $user['lat'];
                        $locaiton_coords['lng'] = $user['lng'];
                    }else{
                        /*IP所在位置*/
                        $locaiton_coords = array();
                        if(empty($_SESSION['az']['client']['city']['lat']) || empty($_SESSION['az']['client']['city']['log'])){
                            $locaiton_coords['lat'] = 28.09953;
                            $locaiton_coords['lng'] = 112.980773;
                        }else{
                            $locaiton_coords['lat'] = $_SESSION['az']['client']['city']['lat'];
                            $locaiton_coords['lng'] = $_SESSION['az']['client']['city']['log'];                         
                        }           
                    }
                    $this->assign('locaiton_coords', $locaiton_coords);
                }
            }
            $this->assign('content', $user); /*内容*/
            /*组装post提交变量*/
            $submit = array('name' => '确认编辑', 'url' => '?id=' . $user_id);
            $this->assign('submit', $submit);
            /*分配变量*/
            $this->assign('title', '编辑员工'); /*页面标题*/
            $this->assign('keywords', '编辑员工'); /*页面关键词*/
            $this->assign('description', '编辑员工'); /*页面描述*/
        } else {
            /*添加*/
            /*组装post提交变量*/
            $submit = array('name' => '确认添加', 'url' => '?a=add');
            $this->assign('submit', $submit);
            /*分配变量*/
            $this->assign('title', '添加员工'); /*页面标题*/
            $this->assign('keywords', '添加员工'); /*页面关键词*/
            $this->assign('description', '添加员工'); /*页面描述*/
        }
        /*提交*/
        if ($_POST) {
            /*获取参数*/
            $nowtime = time();
            $password = input('password');
            $password = input('password');
            /*验证参数*/
            $coords = explode(',',input('coords'));
            $where_name = input('name');
            if (empty($where_name)) {
                echo '<script>$(document).ready(function(){alertBox("名称不可为空..","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
                die();
            }
            if (input('password') != input('password2')) {
                echo '<script>$(document).ready(function(){alertBox("两次密码不一致..","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
                die();
            } else {
                /*组装参数*/
                $data = array(
                    'name' => input('name'), /*名称*/
                    'nickname' => input('nickname'), /*昵称*/
                    'picture' => input('picture'), /*头像*/
                    'ico' => input('ico'), /*头像*/
                    'sex' => input('sex'), /*性别 默认女:0|男为1 */
                    'industry' => input('industry'), /*行业*/
                    'year' => input('year'), /*出生年*/
                    'month' => input('month'), /*出生月*/
                    'day' => input('day'), /*出生日*/
                    'state' => input('state'), /*州*/
                    'country' => input('country'), /*国*/
                    'province' => input('province'), /*省*/
                    'city' => input('city'), /*市*/
                    'area' => input('area'), /*区*/
                    'address' => input('address'), /*地址*/
                    'order' => input('order'), /*排序  默认为100*/
                    'status' => input('status'), /*状态  默认为1:启用|0:禁用*/
                    'admin' => 1, /*状态  默认为0:商户|1:平台管理员*/
                    'edittime' => $nowtime, /*修改时间*/
                    'lat'   => $coords[0],/*坐标维度*/
                    'lng'   => $coords[1],/*坐标经度*/
                );
                if ($user_id) {
                    $data['group_ids'] = input('group_id'); /*所属管理组*/
                    /*编辑*/
                    /*判断名称唯一*/
                    $where_u['name'] = array('eq', $where_name);
                    $where_u['id'] = array('neq', $user_id);
                    $valiinfo = Db::name('user')->where($where_u)->count();
                    if ($valiinfo) {
                        echo '<script>$(document).ready(function(){alertBox("名称已存在..","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
                        die();
                    }
                    /*更新密码*/
                    if (!empty($password)) {
                        $reg_time = $user['addtime'];
                        $password = md5(md5($reg_time . $password));
                        $data['password'] = $password;
                    }
                    /*更新数据*/
                    $result = Db::name('user')->where('id=' . $user_id)->update($data);
                    $log_in='编辑用户ID为'.$user_id.' 的个人信息';
                    $alert_success = '恭喜，编辑成功！';
                    $alert_error = '抱歉，编辑失败！';
                } else {
                    $data['group_ids'] = input('group_id'); /*所属管理组*/
                    $data['site_ids'] = $this->site_id; /*用户管理站点*/
                    /*用户名唯一*/
                    $valiinfo = $this->valiinfo('ym_user', 'name', $where_name);
                    if ($valiinfo) {
                        echo '<script>$(document).ready(function(){alertBox("名称已存在..","' . $_SERVER['HTTP_REFERER'] . '");})</script>';
                        die();
                    }
                    /*更新密码*/
                    if (!empty($password)) {
                        $reg_time = $nowtime;
                        $password = md5(md5($reg_time . $password));
                        $data['password'] = $password;
                    }
                    /*重组身份证json*/
                    $identity_arr = array('name' => input('identity'), 'status' => 0, 'edittime' => $nowtime,);
                    $identity_json = json_encode($identity_arr);
                    /*重组手机json*/
                    $phone_arr = array('name' => input('phone'), 'status' => 0, 'edittime' => $nowtime,);
                    $phone_json = json_encode($phone_arr);
                    /*重组邮箱json*/
                    $mail_arr = array('name' => input('mail'), 'status' => 0, 'edittime' => $nowtime,);
                    $mail_json = json_encode($mail_arr);
                    /*重组QQjson*/
                    $qq_arr = array('name' => input('qq'), 'status' => 0, 'edittime' => $nowtime,);
                    $qq_json = json_encode($qq_arr);
                    /*重组微信号json*/
                    $wechat_arr = array('name' => input('wechat'), 'status' => 0, 'edittime' => $nowtime,);
                    $wechat_json = json_encode($wechat_arr);
                    /*添加*/
                    $data['money'] = 0; /*余额*/
                    $data['integral'] = 0; /*积分*/
                    $data['growth'] = 0; /*成长值*/
                    $data['identity'] = $identity_json; /*身份证号码JONS集（号码,认证状态1已认证0未认证,变更时间）*/
                    $data['phone'] = $phone_json; /*手机JONS集（号码,认证状态1已认证0未认证,变更时间）*/
                    $data['mail'] = $mail_json; /*邮箱JONS集（号码,认证状态1已认证0未认证,变更时间）*/
                    $data['qq'] = $qq_json; /*QQ-JONS集（号码,关联状态1已关联0未关联,变更时间）*/
                    $data['wechat'] = $wechat_json; /*微信-JONS集（号码,关联状态1已关联0未关联,变更时间）*/
                    $data['addtime'] = $nowtime; /*添加时间*/
                    //dump($data);die();
                    /*插入数据*/
                    $result = Db::name('user')->insertGetId($data);
                    $log_in='添加用户ID为'.$result.' 的个人信息';
                    $alert_success = '恭喜，添加成功！';
                    $alert_error = '抱歉，添加失败！';
                }
                /*判断结果集*/
                if ($result) {
                    $this->write_log($log_in);
                    echo '<script>$(document).ready(function(){alertBox("'.$alert_success.'","'.url('tztx.set/adminlist').'");})</script>';
                } else {
                    echo '<script>$(document).ready(function(){alertBox("'.$alert_error.'","'.$_SERVER['HTTP_REFERER'].'");})</script>';
                }
            }
        } else {
            /*调用模板*/
            return $this->fetch(TEMP_FETCH);
        }
    }
    /*删除员工*/
    public function admindel() {
        /*接收参数*/
        $user_id = input('id'); /*管理组ID*/
        $result = Db::name('user')->where('id=' . $user_id)->delete();
        if ($result) {
            $this->write_log($user_id.'删除成功');
            echo '<script>$(document).ready(function(){alertBox("恭喜，删除成功！","'.$_SERVER['HTTP_REFERER'].'");})</script>';
        } else {
            echo '<script>$(document).ready(function(){alertBox("抱歉，删除失败！","'.$_SERVER['HTTP_REFERER'].'");})</script>';
        }
    }
    /*员工管理 end*/



}