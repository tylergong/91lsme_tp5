<?php

namespace app\index\controller;

class Content extends Common {
    //
    public function index() {
        $article_id = input('param.id');
        if ($article_id) {
            // 获取文章信息
            $articleData = db('ls_article')->alias('a')
                ->join('ls_channel c', 'a.cid=c.id')
                ->where('a.id', $article_id)
                ->where('a.is_del', 0)
                ->field('a.id,a.title,a.create_time,a.author,a.content,a.click_num,a.cid,c.cname')
                ->find();
            if ($articleData) {
                // 新增一次点击次数
                db('ls_article')->where('id', $article_id)->setInc('click_num');
                // 查询文章标签
                $articleData['tags'] = db('ls_article_tag')->alias('at')
                    ->join('ls_tag t', 'at.tag_id=t.id')
                    ->where('at.article_id', $article_id)
                    ->field('at.tag_id,t.tag_name')
                    ->select();
                // 转义字符
                $articleData['content'] = stripslashes($articleData['content']);
                $this->assign('articleData', $articleData);
            } else {
                $this->redirect('/');
            }
        } else {
            $this->redirect('/');
        }


        $this->assign('_head_title', $articleData['title']);

        $webSet = $this->loadWebSet();
        $webSet['description'] = $articleData['title'] . ',' . $webSet['description'];
        $this->assign('_webSet', $webSet);

        return $this->fetch();
    }
}
