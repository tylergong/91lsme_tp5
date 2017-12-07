<?php

namespace app\common\model;

use think\Model;

class Link extends Model {
    //
    protected $pk = 'id';
    protected $table = 'ls_flinks';

    // 获取所有
    public function getAll() {
        return $this->order('orderby desc')->paginate(6);
    }

    // 添加
    public function store($data) {
        // 1、验证并保存
        $res = $this->validate(true)->save($data, $data['id']);
        if ($res) {
            return ['valid' => 1, 'msg' => '保存成功'];
        } else {
            return ['valid' => 0, 'msg' => $this->getError()];
        }
    }

    // 删除
    public function del($id) {
        if (Link::destroy($id)) {
            return ['valid' => 1, 'msg' => '删除成功'];
        } else {
            return ['valid' => 0, 'msg' => '删除失败'];
        }
    }

    // 修改排序
    public function changeSort($data) {
        $res = $this->validate([
            'orderby' => 'require|between:1,9999',
        ], [
            'orderby.require' => '请输入排序值',
            'orderby.between' => '排序范围在1~9999之间',
        ])->save($data, [$this->pk => $data['id']]);
        if ($res) {
            return ['valid' => 1, 'msg' => '修改成功'];
        } else {
            return ['valid' => 0, 'msg' => $this->getError()];
        }
    }
}
