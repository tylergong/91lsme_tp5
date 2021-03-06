<?php

namespace app\index\controller;


class Index extends Common {
    //
    public function index() {
        $articleData = db('ls_article')->alias('a')
            ->join('ls_channel c', 'a.cid=c.id')
            ->where('a.is_del', 0)
            ->order('create_time desc')
            ->field('a.id,a.title,a.create_time,a.author,a.content,a.thumb,a.click_num,a.cid,c.cname')
            ->limit(20)->select();
        foreach ($articleData as $k => $v) {
            // 替换掉各种标签 空格 换行符等
            $tmp = preg_replace(array('/<img(.*?)>/', '/<(.*?)>/', '/<\/(.*?)>/', '/<br \/>/', '/&nbsp;/', '/&lt;(.*?)&gt;/'), '', $v['content']);
            // 然后在从内容中获取100字摘要
            $articleData[$k]['digest'] = mb_substr($tmp, 0, 200, 'utf-8');
            // 查询文章标签
            $articleData[$k]['tags'] = db('ls_article_tag')->alias('at')
                ->join('ls_tag t', 'at.tag_id=t.id')
                ->where('at.article_id', $v['id'])
                ->field('at.tag_id,t.tag_name')
                ->select();
        }

        $this->assign('_head_title', '首页');
        $this->assign('articleData', $articleData);
        return $this->fetch();
    }

    // 搜索
    public function search() {
        $words = input('get.words/s');
        $searchData['words'] = $words;
        $searchData['list'] = db('ls_article')->alias('a')
            ->join('ls_channel c', 'a.cid=c.id')
            ->where('a.is_del', 0)
            ->where('a.title', 'like', "%{$words}%")
            ->order('create_time desc')
            ->field('a.id,a.title,a.create_time,a.author,a.content,a.thumb,a.click_num,a.cid,c.cname')
            ->order('create_time desc')->select();
        foreach ($searchData['list'] as $k => &$v) {
            // 替换掉各种标签 空格 换行符等
            $tmp = preg_replace(array('/<img(.*?)>/', '/<(.*?)>/', '/<\/(.*?)>/', '/<br \/>/', '/&nbsp;/', '/&lt;(.*?)&gt;/'), '', $v['content']);
            //$tmp = strip_tags($v[['content']]);
            // 然后在从内容中获取100字摘要
            $v['digest'] = mb_substr($tmp, 0, 100, 'utf-8');
        }
        $searchData['total'] = count($searchData['list']);
        $this->assign('_head_title', '搜索结果');
        $this->assign('searchData', $searchData);
        return $this->fetch();
    }
}
