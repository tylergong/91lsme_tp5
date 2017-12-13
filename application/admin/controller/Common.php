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
        //halt($rule);
        $auth = new Auth();
        if (!$auth->check($rule, $admin_id)) {
            $this->error('你没有权限访问');
        }
    }
}
