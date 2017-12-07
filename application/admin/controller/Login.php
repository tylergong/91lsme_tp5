<?php

namespace app\admin\controller;

use app\common\model\Admin;
use think\Controller;

class Login extends Controller {
    //登录
    public function login() {
        if (request()->isPost()) {
            $res = (new Admin())->login(input('post.'));
            if ($res['valid']) {
                // 登录成功
                $this->success($res['msg'], 'admin/index/index');
                exit;
            } else {
                // 登录失败
                $this->error($res['msg']);
                exit;
            }
        }
        return $this->fetch();
    }

    // 退出
    public function logout() {
        session(null);
        $this->success('登出成功', 'admin/index/index');
    }
}
