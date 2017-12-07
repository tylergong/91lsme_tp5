<?php

namespace app\common\model;

use think\Model;

class Tag extends Model {
    //
    protected $pk = 'id';
    protected $table = 'ls_tag';

    // 添加、修改
    public function store($data) {
        //1、验证 并 添加到数据库
        $res = $this->validate(true)->save($data, $data['id']);
        if (false === $res) {
            return ['valid' => 0, 'msg' => $this->getError()];
        } else {
            return ['valid' => 1, 'msg' => '添加成功'];
        }
    }

    // 删除
    public function del($id) {
        if (Tag::destroy($id)) {
            return ['valid' => 1, 'msg' => '删除成功'];
        } else {
            return ['valid' => 0, 'msg' => '删除失败'];
        }
    }
}
