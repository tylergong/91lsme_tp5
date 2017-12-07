<?php

namespace app\admin\validate;

use think\Validate;

class Link extends Validate {
    protected $rule = [
        'fname' => 'require',
        'flink' => 'require',
        'orderby' => 'require|between:1,9999',
    ];
    protected $message = [
        'fname.require' => '请输入友链名称',
        'flink.require' => '请输入友链地址',
        'orderby.require' => '请输入友链排序',
        'orderby.between' => '排序范围在1~9999之间',
    ];
}