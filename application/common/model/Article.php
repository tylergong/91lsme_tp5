<?php

namespace app\common\model;

use think\Model;

class Article extends Model {
    //
    protected $pk = 'id';
    protected $table = 'ls_article';
    protected $auto = ['admin_id'];//自动添加
    protected $insert = ['create_time'];//新增时自动添加字段
    protected $update = ['up_time'];//修改时自动添加字段

    // 获取用当前用户 ID
    protected function setAdminIdAttr($value) {
        return session('admin.admin_id');
    }

    // 格式化新增时间
    protected function setCreateTimeAttr($value) {
        return date('Y-m-d H:i:s', time());
    }

    // 格式化修改时间
    protected function setUpTimeAttr($value) {
        return date('Y-m-d H:i:s', time());
    }

    // 获取列表
    public function getAll($where = array('is_del' => 0), $orderby = 'create_time desc') {
        $allList = db('ls_article')->alias('a')
            ->join('ls_channel c', 'a.cid = c.id')
            ->field('a.id,a.title,a.author,a.create_time,a.is_show,a.click_num,c.cname')
            ->where('a.is_del', $where['is_del']);
        if (!empty($where['title'])) {
            $allList = $allList->where('title', 'like', "%{$where['title']}%");
        }
        if (!empty($where['cid'])) {
            $allList = $allList->where('cid', $where['cid']);
        }
        $allList = $allList->order('a.' . $orderby . ' ,a.id asc');
        return $allList;
    }

    // 获取回收站列表
    public function getRecycleAll() {
        return db('ls_article')->alias('a')
            ->join('ls_channel c', 'a.cid=c.id')
            ->field('a.id,a.title,a.author,a.create_time,a.is_show,a.click_num,c.cname')
            ->where('a.is_del', 1)
            ->order('a.create_time desc')
            ->paginate(8);
    }

    // 编辑保存
    public function edit($data) {
        if (!isset($data['tag'])) {
            // 如果未选择标签提示错误
            return ['valid' => 0, 'msg' => '请选择文章标签'];
        }

        //1、验证、添加
        $res = $this->validate(true)->allowField(true)->save($data, [$this->pk => $data['id']]);
        if ($res) {
            // 删除原始标签
            (new ArticleTag())->where('article_id', $data['id'])->delete();
            // 添加文章标签
            foreach ($data['tag'] as $v) {
                $arcTagData = [
                    'article_id' => $this->id,
                    'tag_id' => $v,
                ];
                (new ArticleTag())->save($arcTagData);
            }
            return ['valid' => 1, 'msg' => '编辑成功'];
        } else {
            return ['valid' => 0, 'msg' => $this->getError()];
        }
    }

    // 新增保存
    public function store($data) {
        if (!isset($data['tag'])) {
            // 如果未选择标签提示错误
            return ['valid' => 0, 'msg' => '请选择文章标签'];
        }

        //1、验证、添加
        $res = $this->validate(true)->allowField(true)->save($data);
        if ($res) {
            // 添加文章标签
            foreach ($data['tag'] as $v) {
                $arcTagData = [
                    'article_id' => $this->id,
                    'tag_id' => $v,
                ];
                (new ArticleTag())->save($arcTagData);
            }
            return ['valid' => 1, 'msg' => '添加成功'];
        } else {
            return ['valid' => 0, 'msg' => $this->getError()];
        }
    }

//    // 修改文章是否显示
//    public function changeShow($data) {
//        $res = $this->validate([
//            'is_show' => 'require|between:0,1',
//        ], [
//            'is_show.require' => '是否显示不能为空',
//            'is_show.between' => '显示输入1，隐藏输入0',
//        ])->save($data, [$this->pk => $data['id']]);
//        if ($res) {
//            return ['valid' => 1, 'msg' => '修改成功'];
//        } else {
//            return ['valid' => 0, 'msg' => $this->getError()];
//        }
//    }

    // 物理删除数据
    public function del($id) {
        if (Article::destroy($id)) {
            // 删除文章标签
            (new ArticleTag())->where('article_id', $id)->delete();
            return ['valid' => 1, 'msg' => '删除成功'];
        } else {
            return ['valid' => 0, 'msg' => '删除失败'];
        }
    }
}
