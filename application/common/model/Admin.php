<?php

namespace app\common\model;

use think\Loader;
use think\Model;
use think\Validate;

class Admin extends Model {
    protected $pk = 'id';
    protected $table = 'ls_admin';

    // 登录
    public function login($data) {
        //1、执行验证（模型验证）
        $validate = Loader::validate('Admin');
        if (!$validate->check($data)) {
            return ['valid' => 0, 'msg' => $validate->getError()];
        }

        //2、比对用户名和密码是否正确
        $userInfo = $this->where('uname', $data['uname'])->where('upwd', md5($data['upwd']))->find();
        if (!$userInfo) {
            return ['valid' => 0, 'msg' => '用户名或密码错误'];
            //dump($validate->getError());
        }

        //3、将用户信息存入 session
        session('admin.admin_id', $userInfo['id']);
        session('admin.admin_uname', $userInfo['uname']);
        return ['valid' => 1, 'msg' => '登录成功'];
    }

    // 修改密码
    public function pass($data) {
        //1、执行验证
        $validate = new Validate([
            'upwd' => 'require',
            'new_pwd' => 'require',
            'confirm_pwd' => 'require|confirm:new_pwd',
        ], [
            'upwd.require' => '请输入原始密码',
            'new_pwd.require' => '请输入新密码',
            'confirm_pwd.require' => '请输入确认密码',
            'confirm_pwd.confirm' => '二次输入密码不一致',
        ]);
        if (!$validate->check($data)) {
            return ['valid' => 0, 'msg' => $validate->getError()];
            //dump($validate->getError());
        }

        //2、比对原始密码是否一致
        $userInfo = $this->where('id', session('admin.admin_id'))->where('upwd', md5($data['upwd']))->find();
        if (!$userInfo) {
            return ['valid' => 0, 'msg' => '原始密码不正确'];
        }

        //3、修改密码
        $res = $this->save([
            'upwd' => md5($data['confirm_pwd']),
        ], [$this->pk => session('admin.admin_id')]);
        if ($res) {
            return ['valid' => 1, 'msg' => '密码修改成功'];
        } else {
            return ['valid' => 0, 'msg' => '密码修改失败'];
        }
    }
}
