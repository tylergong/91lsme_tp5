<?php

namespace app\admin\controller;

use think\Controller;

class Link extends Controller {
    protected $db;

    public function _initialize() {
        parent::_initialize(); // TODO: Change the autogenerated stub
        $this->db = new \app\common\model\Link();
    }

    // 首页
    public function index() {
        $links = $this->db->getAll();
        $this->assign('links', $links);
        return $this->fetch();
    }

    // 添加
    public function store() {
        $link_id = input('param.id');
        if (request()->isPost()) {
            $res = $this->db->store(input('post.'));
            if ($res['valid']) {
                $this->success($res['msg'], 'index');
                exit;
            } else {
                $this->error($res['msg']);
                exit;
            }
        }
        if ($link_id) {
            // 有 ID 则为编辑
            $oldData = $this->db->find($link_id);
        } else {
            $oldData = ['fname' => '', 'flink' => '', 'orderby' => ''];
        }
        $this->assign('oldData', $oldData);
        return $this->fetch();
    }

    // 删除
    public function del() {
        $res = $this->db->del(input('get.id'));
        if ($res['valid']) {
            $this->success($res['msg'], 'index');
            exit;
        } else {
            $this->error($res['msg']);
            exit;
        }
    }

    // 排序
    public function changeSort(){
        if (request()->isAjax()) {
            $res = $this->db->changeSort(input('post.'));
            if ($res['valid']) {
                $this->success($res['msg'], 'index');
                exit;
            } else {
                $this->error($res['msg']);
                exit;
            }
        }
    }
}
