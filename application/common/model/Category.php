<?php

namespace app\common\model;

use houdunwang\arr\Arr;
use think\Model;

class Category extends Model {
    //
    protected $pk = 'id';
    protected $table = 'ls_channel';

    // 获取分类数据【树状结构】
    public function getAll() {
        // 使用方法参考 hdphp 手册【hdphp.com】-->搜索 数组增强-->搜索 tree
        return Arr::tree(db('ls_channel')->select(), 'cname', 'id', 'pid');
    }

    // 添加
    public function store($data) {
        //1、执行验证（模型验证 并添加数据）
        $res = $this->validate(true)->save($data);
        if (false === $res) {
            // 验证失败
            return ['valid' => 0, 'msg' => $this->getError()];
        } else {
            return ['valid' => 1, 'msg' => '添加成功'];
        }
    }

    // 处理所属分类
    public function getCateData($id) {
        //1、首先找到 $id 的子集
        $cate_ids = $this->getSon(db('ls_channel')->select(), $id);
        //2、将子集追加进去
        $cate_ids[] = $id;
        //3、找到除了他们之外的数据,变为数状结构
        return Arr::tree(db('ls_channel')->whereNotIn('id', $cate_ids)->select(), 'cname', 'id', 'pid');
    }

    // 找某个 ID 的子集
    public function getSon($data, $id) {
        static $temp = [];
        foreach ($data as $k => $v) {
            if ($id == $v['pid']) {
                $temp[] = $v['id'];
                $this->getSon($data, $v['id']);
            }
        }
        return $temp;
    }

    // 编辑
    public function edit($data) {
        $res = $this->validate(true)->save($data, [$this->pk => $data['id']]);
        if ($res) {
            return ['valid' => 1, 'msg' => '编辑成功'];
        } else {
            return ['valid' => 0, 'msg' => $this->getError()];
        }
    }

    // 删除
    public function del($id) {
        // 获取当前数据的pid
        $cate_pid = $this->where('id', $id)->value('pid');
        // 将当前要删除的 $id 的子集数据的 pid 修改为 $cate_pid
        $this->where('pid', $id)->update(['pid' => $cate_pid]);
        // 执行当前数据删除
        if(Category::destroy($id)){
            return ['valid' => 1, 'msg' => '删除成功'];
        }else{
            return ['valid' => 0, 'msg' => '删除失败'];
        }
    }
}
