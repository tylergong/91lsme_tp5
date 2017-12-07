<?php

namespace app\index\controller;

use think\Log;

class Weixin extends WeixinBase {
    /**
     * 接收微信消息
     */
    public function index() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            //FileHelp::WriteLog(1, 'y', serialize($GLOBALS["HTTP_RAW_POST_DATA"]), 'responseMsg', 'weixin/');
            Log::record(serialize($GLOBALS["HTTP_RAW_POST_DATA"]));
            $this->responseMsg(); // 接受消息
        } else {
            $this->valid(); // 服务器校验
        }
    }

    /**
     * 自定义微信公众号导航
     */
    public function cm() {
        $a = $this->createMenu();
        print_r($a);
    }

    /**
     * 获取微信公众号导航
     */
    public function gm() {
        $a = $this->getMenu();
        print_r($a);
    }
}
