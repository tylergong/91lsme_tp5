<?php

namespace app\admin\validate;

use think\Validate;

class Rules extends Validate {
    protected $rule = [
        'rname' => 'require',
    ];
    protected $message = [
        'rname.require' => '请输入权限名称',
    ];
}