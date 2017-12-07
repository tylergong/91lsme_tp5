<?php

namespace app\common\model;

use think\Model;

class Webset extends Model {
    //
    protected $pk = 'id';
    protected $table = 'ls_webset';

    // 编辑
    public function edit($data) {
        $res = $this->validate([
            'webset_value' => ['require', 'regex' => '/\S+/'],
        ], [
            'webset_value.require' => '请输入站点配置值',
            'webset_value.regex' => '请输入站点配置值',
        ])->save($data, [$this->pk => $data['id']]);
        if ($res) {
            return ['valid' => 1, 'msg' => '修改成功'];
        } else {
            return ['valid' => 0, 'msg' => '修改失败'];
        }
    }
}
