<?php

namespace app\admin\controller;

use app\common\model\Admin;

class Index extends Common {
    //后台首页
    public function index() {
        return $this->fetch();
    }

    // 修改密码
    public function pass() {
        if (request()->isPost()) {
            $res = (new Admin())->pass(input('post.'));
            if ($res['valid']) {
                // 修改密码成功
                // 清除 session 登录信息重新登
                session(null);
                $this->success($res['msg'], 'admin/index/index');
                exit;
            } else {
                // 失败
                $this->error($res['msg']);
                exit;
            }
        }
        return $this->fetch();
    }
}
