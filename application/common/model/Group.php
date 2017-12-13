<?php

namespace app\common\model;

use think\Model;

class Group extends Model {
    //
    protected $pk = 'id';
    protected $table = 'ls_group';

    // 格式化状态 汉字显示
    public function getStatusTxtAttr($value, $data) {
        $status = [0 => '已禁用', 1 => '正常'];
        return $status[$data['status']];
    }

    // 获取所有分组
    public function getAll() {
        return $this->order('status desc, id desc')->paginate(8);
    }

    // 添加、编辑分组
    public function store($data) {
        // 1、验证输入数据，并写入数据库
        $res = $this->validate(true)->save($data, $data['id']);
        if (false === $res) {
            return ['valid' => 0, 'msg' => $this->getError()];
        } else {
            return ['valid' => 1, 'msg' => '添加成功'];
        }
    }

    // 禁用、恢复用户
    public function changeStatus($id, $handle) {
        if ($id == 1) {
            return ['valid' => 0, 'msg' => '超级管理员组不能禁用哦！'];
        } else {
            $res = $this->where('id', $id)->update(['status' => $handle]);
            if ($res) {
                return ['valid' => 1, 'msg' => '修改成功'];
            } else {
                return ['valid' => 0, 'msg' => '删除失败'];
            }
        }
    }

    // 删除用户
    public function del($id) {
        if ($id == 1) {
            return ['valid' => 0, 'msg' => '超级管理员组不能删除哦！'];
        } else {
            if ($this::destroy($id)) {
                return ['valid' => 1, 'msg' => '删除成功'];
            } else {
                return ['valid' => 0, 'msg' => '删除失败'];
            }
        }
    }

    // 给用户组设置权限
    public function setAuth($data) {
        // 1、验证
        if (!isset($data['rules_id'])) {
            return ['valid' => 0, 'msg' => '请选择要分配的权限'];
        }
        // 2、格式化获取到的权限为字符串
        $rules_str = implode(',', $data['rules_id']);
        // 3、更新用户组权限
        if (db('ls_group')->where('id', $data['id'])->update(['rules' => $rules_str])) {
            return ['valid' => 1, 'msg' => '设置分组成功'];
        } else {
            return ['valid' => 0, 'msg' => '设置分组失败'];
        }
    }
}
