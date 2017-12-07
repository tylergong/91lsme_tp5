<?php

namespace app\admin\validate;

use think\Validate;

class Article extends Validate {
    protected $rule = [
        'title' => 'require',
        'author' => 'require',
        'cid' => 'notIn:0',
    ];
    protected $message = [
        'title.require' => '请输入文章标题',
        'author.require' => '请输入文章作者',
        'cid.notIn' => '请选择文章分类',
    ];
}