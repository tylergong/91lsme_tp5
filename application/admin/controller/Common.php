<?php

namespace app\admin\controller;

use app\common\service\Auth;
use think\Controller;
use think\Request;

class Common extends Controller {
    //
    public function __construct(Request $request = null) {
        parent::__construct($request);
        // 验证登录
        if (!session('admin.admin_id')) {
            $this->redirect('admin/login/login');
        }
        $admin_id = session('admin.admin_id');
        $rule = request()->module() . '/' . request()->controller() . '/' . request()->action();
        // halt($rule);
        // 这里可再添加一些无需验证权限的判断
        // 访问权限验证
        $auth = new Auth();
        if (!$auth->check($rule, $admin_id)) {
            $this->error('你没有权限访问');
        }
    }
}
