<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: luofei614 <weibo.com/luofei614>　
// +----------------------------------------------------------------------
namespace app\common\service;

use think\Db;

/**
 * 权限认证类
 * 功能特性：
 * 1，是对规则进行认证，不是对节点进行认证。用户可以把节点当作规则名称实现对节点进行认证。
 *      $auth=new Auth();  $auth->check('规则名称','用户id')
 * 2，可以同时对多条规则进行认证，并设置多条规则的关系（or或者and）
 *      $auth=new Auth();  $auth->check('规则1,规则2','用户id','and')
 *      第三个参数为and时表示，用户需要同时具有规则1和规则2的权限。当第三个参数为or时，表示用户值需要具备其中一个条件即可。默认为or
 * 3，一个用户可以属于多个用户组(ls_admin_group 定义了用户所属用户组)。我们需要设置每个用户组拥有哪些规则(ls_group 定义了用户组权限)
 *
 * 4，支持规则表达式（暂时不启用）。
 *      在 ls_rules 表中定义一条规则时，如果type为1，condition字段就可以定义规则表达式。
 *          如定义{score}>5 and {score}<100 表示用户的分数在5-100之间时这条规则才会通过。
 */

//数据库
/*
-- ----------------------------
-- ls_rules，权限表，
-- id:主键，rname：规则中文名称，rules：规则唯一标识, status 状态：为1正常，为0禁用，condition：规则表达式，为空表示存在就验证，不为空表示按照条件验证
-- ----------------------------
 DROP TABLE IF EXISTS `ls_rules`;
CREATE TABLE `ls_rules` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `rname` varchar(50) NOT NULL DEFAULT '' COMMENT '权限中文名称',
  `rules` varchar(50) NOT NULL DEFAULT '' COMMENT '权限标识',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '是否禁用（1正常 0已禁用）',
  `pid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '父ID',
  `type` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '类型（1表示附加规则为表达式）',
  `condition` varchar(100) NOT NULL DEFAULT '' COMMENT '附加规则（空则不验证，不空则验证）',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='权限表';

-- ----------------------------
-- ls_group 用户分组表，
-- id：主键， gname:用户组中文名称， rules：用户组拥有的规则id， 多个规则","隔开，status 状态：为1正常，为0禁用
-- ----------------------------
DROP TABLE IF EXISTS `ls_group`;
CREATE TABLE `ls_group` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `gname` varchar(100) NOT NULL DEFAULT '' COMMENT '分组名称',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '是否禁用（1正常 0已禁用）',
  `rules` varchar(80) NOT NULL DEFAULT '' COMMENT '规则',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='用户分组表';


-- ----------------------------
-- ls_admin 管理员用户表（这里只有部分字段，其他看需求）
-- id:用户id，uname：用户名称
-- ----------------------------
CREATE TABLE `ls_admin` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `uname` varchar(20) NOT NULL COMMENT '用户名',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '是否禁用（1正常 0已禁用）',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uname` (`uname`) COMMENT '(null)'
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='管理员用户表';

-- ----------------------------
-- ls_admin_group 管理员与用户分组中间表
-- admin_id:用户id，group_id：用户组id
-- ----------------------------
DROP TABLE IF EXISTS `ls_admin_group`;
CREATE TABLE `ls_admin_group` (
  `admin_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '管理员 ID',
  `group_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户组 ID',
  UNIQUE KEY `admin_id_group_id` (`admin_id`,`group_id`) USING BTREE,
  KEY `admin_id` (`admin_id`),
  KEY `group_id` (`group_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='管理员与用户分组中间表';
 */

Class Auth {

    //默认配置
    protected $_config = array(
        'AUTH_ON' => true,                          // 认证开关
        'AUTH_TYPE' => 1,                           // 认证方式，1为实时认证；2为登录认证。
        'AUTH_GROUP' => 'ls_group',                 // 用户组数据表名
        'AUTH_GROUP_ACCESS' => 'ls_admin_group',    // 用户-用户组关系表
        'AUTH_RULE' => 'ls_rules',                  // 权限规则表
        'AUTH_USER' => 'ls_admin'                   // 用户信息表
    );

    public function __construct() {
        if (config('AUTH_CONFIG')) {
            //可设置配置项 AUTH_CONFIG, 此配置项为数组。
            $this->_config = array_merge($this->_config, config('auth')); // 请新扩展auth配置文件
        }
    }

    /**
     * 检查权限
     *
     * @param $name      string|array  需要验证的规则列表,支持逗号分隔的权限规则或索引数组
     * @param $uid       int           认证用户的id
     * @param $type      int
     * @param $mode      string        执行check的模式
     * @param $relation  string        如果为 'or' 表示满足任一条规则即通过验证;如果为 'and'则表示需满足所有规则才能通过验证
     *
     * @return boolean  通过验证返回true;失败返回false
     */
    public function check($name, $uid, $type = 0, $mode = 'url', $relation = 'or') {
        if (!$this->_config['AUTH_ON'])
            return true;
        //获取用户需要验证的所有有效规则列表
        $authList = $this->getAuthList($uid, $type);
        //halt($authList);
        if (is_string($name)) {
            $name = strtolower($name);
            if (strpos($name, ',') !== false) {
                $name = explode(',', $name);
            } else {
                $name = array($name);
            }
        }
        $list = array(); //保存验证通过的规则名
        if ($mode == 'url') {
            $REQUEST = unserialize(strtolower(serialize($_REQUEST)));
        }
        foreach ($authList as $auth) {
            $query = preg_replace('/^.+\?/U', '', $auth);
            if ($mode == 'url' && $query != $auth) {
                parse_str($query, $param); //解析规则中的param
                $intersect = array_intersect_assoc($REQUEST, $param);
                $auth = preg_replace('/\?.*$/U', '', $auth);
                //如果节点相符且url参数满足
                if (in_array($auth, $name) && $intersect == $param) {
                    $list[] = $auth;
                }
            } else if (in_array($auth, $name)) {
                $list[] = $auth;
            }
        }
        if ($relation == 'or' and !empty($list)) {
            return true;
        }
        $diff = array_diff($name, $list);
        if ($relation == 'and' and empty($diff)) {
            return true;
        }
        return false;
    }

    /**
     * 根据用户id获取用户组,返回值为数组
     *
     * @param  $uid int 用户id
     *
     * @return array    用户所属的用户组
     *  array(
     *     array('admin_id'=>'用户id','group_id'=>'用户组id','gname'=>'用户组名称','rules'=>'用户组拥有的规则id,多个,号隔开'),
     *    ...)
     */
    public function getGroups($uid) {
        static $groups = array();
        if (isset($groups[$uid]))
            return $groups[$uid];
        $user_groups = Db::name($this->_config['AUTH_GROUP_ACCESS'])->alias('a')
            ->join($this->_config['AUTH_GROUP'] . ' g', 'a.group_id=g.id')
            ->where("a.admin_id='$uid' and g.status='1'")
            ->select();
        $groups[$uid] = $user_groups ?: array();
        return $groups[$uid];
    }

    /**
     * 获得权限列表
     *
     * @param $uid  用户id
     * @param $type
     *
     * @return array|mixed
     */
    protected function getAuthList($uid, $type) {
        static $_authList = array(); //保存用户验证通过的权限列表
        $t = implode(',', (array)$type);
        if (isset($_authList[$uid . $t])) {
            return $_authList[$uid . $t];
        }
        if ($this->_config['AUTH_TYPE'] == 2 && isset($_SESSION['_AUTH_LIST_' . $uid . $t])) {
            return $_SESSION['_AUTH_LIST_' . $uid . $t];
        }

        //读取用户所属用户组
        $groups = $this->getGroups($uid);
        $ids = array();//保存用户所属用户组设置的所有权限规则id
        foreach ($groups as $g) {
            $ids = array_merge($ids, explode(',', trim($g['rules'], ',')));
        }
        $ids = array_unique($ids);
        if (empty($ids)) {
            $_authList[$uid . $t] = array();
            return array();
        }

        $map = array(
            'id' => array('in', $ids),
            'type' => $type,
            'status' => 1,
        );
        //读取用户组所有权限规则
        $rules = Db::name($this->_config['AUTH_RULE'])
            ->where($map)
            ->select();

        //循环规则，判断结果。
        $authList = array();
        foreach ($rules as $rule) {
//            if (!empty($rule['condition'])) { //根据condition进行验证
//                $user = $this->getUserInfo($uid);//获取用户信息,一维数组
//
//                $command = preg_replace('/\{(\w*?)\}/', '$user[\'\\1\']', $rule['condition']);
//                //dump($command);//debug
//                @(eval('$condition=(' . $command . ');'));
//                if ($condition) {
//                    $authList[] = strtolower($rule['name']);
//                }
//            } else {
                //只要存在就记录
                $authList[] = strtolower($rule['rules']);
//            }
        }

        $_authList[$uid . $t] = $authList;
        if ($this->_config['AUTH_TYPE'] == 2) {
            //规则列表结果保存到session
            $_SESSION['_AUTH_LIST_' . $uid . $t] = $authList;
        }
        return array_unique(array_filter($authList));
    }

    /**
     * 获得用户资料,根据自己的情况读取数据库
     */
    protected function getUserInfo($uid) {
        static $userInfo = array();
        if (!isset($userInfo[$uid])) {
            $userInfo[$uid] = Db::name($this->_config['AUTH_USER'])
                ->where(array('id' => $uid))
                ->find();
        }
        return $userInfo[$uid];
    }

}
