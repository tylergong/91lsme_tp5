<?php

namespace app\index\controller;

use app\common\model\Category;

class Lists extends Common {
    //
    public function index() {
        $headConf = ['title' => '列表页'];
        $this->assign('_headConf', $headConf);

        $cate_id = input('param.cate_id');
        if ($cate_id) {
            // 获取当前分类下所有子集分类
            $cids = (new Category())->getSon(db('ls_channel')->select(), $cate_id);
            $cids[] = $cate_id;// 把自己追加进去
            $headData = [
                'title' => '分类',
                'name' => db('ls_channel')->where('id', $cate_id)->value('cname'),
                'total' => db('ls_article')->whereIn('cid', $cids)->count(),
            ];

            // 获取当前栏目下文章列表
            $articleData = db('ls_article')->alias('a')
                ->join('ls_channel c', 'a.cid=c.id')
                ->whereIn('a.cid', $cids)
                ->where('a.is_del', 0)
                ->order('create_time desc')
                ->field('a.id,a.title,a.create_time,a.author,a.content,a.thumb,a.click_num,a.cid,c.cname')
                ->select();
        }

        $tag_id = input('param.tag_id');
        if ($tag_id) {
            $headData = [
                'title' => '标签',
                'name' => db('ls_tag')->where('id', $tag_id)->value('tag_name'),
                'total' => db('ls_article_tag')->alias('at')
                    ->join('ls_article a', 'at.article_id=a.id')
                    ->where('tag_id', $tag_id)->where('a.is_del', 0)->count(),
            ];

            // 获取包含当前标签的文章列表
            $articleData = db('ls_article_tag')->alias('at')
                ->join('ls_article a', 'at.article_id=a.id')
                ->join('ls_channel c', 'a.cid=c.id')
                ->where('at.tag_id', $tag_id)
                ->where('a.is_del', 0)
                ->order('create_time desc')
                ->field('a.id,a.title,a.create_time,a.author,a.content,a.thumb,a.click_num,a.cid,c.cname')
                ->select();
        }

        foreach ($articleData as $k => $v) {
            // 替换掉各种标签 空格 换行符等
            $tmp = preg_replace(array('/<img(.*?)>/', '/<(.*?)>/', '/<\/(.*?)>/', '/<br \/>/', '/&nbsp;/', '/&lt;(.*?)&gt;/'), '', $v['content']);
            // 然后在从内容中获取100字摘要
            $articleData[$k]['digest'] = mb_substr($tmp, 0, 100, 'utf-8');
            // 查询文章标签
            $articleData[$k]['tags'] = db('ls_article_tag')->alias('at')
                ->join('ls_tag t', 'at.tag_id=t.id')
                ->where('at.article_id', $v['id'])
                ->field('at.tag_id,t.tag_name')
                ->select();
        }

        $this->assign('headData', $headData);
        $this->assign('articleData', $articleData);
        return $this->fetch();
    }
}
