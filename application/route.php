<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

return [
    '__pattern__' => [
        'name' => '\w+',
    ],
    '[weixin]' => [
        '' => ['index/weixin/index', [], []],
    ],
    '[subway]' => [
        '[:cid]' => ['index/subway/index', ['method' => 'get'], ['cid' => '\d+']],
    ],
    '[c]' => [
        ':cate_id' => ['index/lists/index', ['method' => 'get'], ['cate_id' => '\d+']],
    ],
    '[t]' => [
        ':tag_id' => ['index/lists/index', ['method' => 'get'], ['tag_id' => '\d+']],
    ],
    '[a]' => [
        ':id' => ['index/content/index', ['method' => 'get'], ['id' => '\d+']],
    ],
];
