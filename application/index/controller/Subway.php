<?php

namespace app\index\controller;

use app\common\service\PlatformHelp;

class Subway extends SubwayBase {
    //
    public function index() {
        $cid = input('param.cid/d');

        $res_city = $this->C_getCity();
        $res_line = $this->C_getLineByCityFormat($cid);
        $platform = PlatformHelp::CheckPlatform();

        $this->assign('cid', $cid);
        $this->assign('res_city', $res_city);
        $this->assign('res_line', $res_line);
        $this->assign('platform', $platform);

        return $this->fetch();
    }

    public function getsitebyline() {
        $cid = input('param.cid/d');
        $line = input('param.line/d');

        $result = $this->C_getSiteByLineFormat($cid, $line);

        echo json_encode($result);
        die;
    }

    public function submit() {
        $start = input('param.s_s/s');
        $end = input('param.e_s/s');

        $result = $this->C_getSubwayTransferMode($start, $end);
        echo json_encode($result);
        die;
    }
}
