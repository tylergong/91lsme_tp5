<?php

namespace app\admin\validate;

use think\Validate;

class Group extends Validate {
    protected $rule = [
        'gname' => 'require',
    ];
    protected $message = [
        'gname.require' => '请输入分组名称',
    ];
}