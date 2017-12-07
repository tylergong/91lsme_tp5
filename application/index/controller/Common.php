<?php

namespace app\index\controller;

use think\Controller;
use think\Request;

class Common extends Controller {
    //
    public function __construct(Request $request = null) {
        parent::__construct($request);
        // 1、获取配置项数据
        $webSet = $this->loadWebSet();
        $this->assign('_webSet', $webSet);
        // 2、获取顶级栏目数据
        $cateData = $this->loadCateData();
        $this->assign('_cateData', $cateData);
        // 3、获取全部栏目数据
        $allCateData = $this->loadAllCateData();
        $this->assign('_allCateData', $allCateData);
        // 4、获取标签数据
        $tagData = $this->loadTagData();
        $this->assign('_tagData', $tagData);
        // 5、获取最新文章数据
        $articleData = $this->loadArticleData();
        $this->assign('_articleData', $articleData);
        // 6、获取友情链接数据
        $linkData = $this->loadLinkData();
        $this->assign('_linkData', $linkData);
    }

    // 获取配置项数据
    public function loadWebSet() {
        return db('ls_webset')->column('webset_value', 'webset_name');
    }

    // 获取顶级栏目数据
    public function loadCateData() {
        return db('ls_channel')->where('pid', 0)->order('csort desc')->limit(4)->select();
    }

    // 获取全部栏目数据
    public function loadAllCateData() {
        return db('ls_channel')->order('csort desc')->select();
    }

    // 获取标签数据
    public function loadTagData() {
        return db('ls_tag')->select();
    }

    // 获取最新文章数据
    public function loadArticleData() {
        return db('ls_article')->where('is_del', 0)->where('is_show', 1)
            ->field('id,title,create_time')
            ->order('create_time desc')->limit(3)->select();
    }

    // 获取友情链接数据
    public function loadLinkData() {
        return db('ls_flinks')->order('orderby desc')->limit(6)->select();
    }
}
