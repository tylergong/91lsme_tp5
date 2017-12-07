<?php

namespace app\admin\validate;

use think\Validate;

class Category extends Validate {
    protected $rule = [
        'cname' => 'require',
        'csort' => 'require|number|between:1,9999',
    ];
    protected $message = [
        'cname.require' => '请输入栏目名称',
        'csort.require' => '请输入栏目排序',
        'csort.number' => '栏目排序必须为数字',
        'csort.between' => '栏目排序需在1~9999之间',
    ];
}