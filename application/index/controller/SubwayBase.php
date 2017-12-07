<?php

namespace app\index\controller;

use think\Controller;

class SubwayBase extends Controller {
    /**
     * 所有城市
     *
     * @return array
     */
    public function C_getCity() {
//        $arr['table'] = 'sw_city';
//        return $res_city = $this->IDBModel->M_getLimit($arr);
        return db('sw_city')->select();
    }

    /**
     * 根据城市获取地铁线路
     *
     * @param int $cid 城市
     *
     * @return array
     */
    private function C_getLineByCity($cid) {
//        $arr['where'] = array("cid" => $cid);
//        $arr['by'] = array('orderby' => 'asc');
//        $arr['table'] = 'sw_line';
//        $res = $this->IDBModel->M_getLimit($arr);
//        return $res;
        return db('sw_line')->where('cid', $cid)->order('orderby')->select();
    }

    /**
     * 根据城市获取地铁线路（格式化）
     *
     * @param int $cid 城市
     *
     * @return array
     */
    public function C_getLineByCityFormat($cid) {
        $result = $this->C_getLineByCity($cid);
        $res_subway = null;
        if (is_array($result)) {
            foreach ($result as $k => $v) {
                $res_subway[$cid . '_' . $v['line']] = $v['name'];
            }
        }
        return $res_subway;
    }

    /**
     * 根据城市线路获取地铁站点
     *
     * @param int $cid  城市
     * @param int $line 线路
     *
     * @return array
     */
    private function C_getSiteByLine($cid, $line) {
//        $arr['where'] = array("cid" => $cid,
//            "line" => $line);
//        $arr['by'] = array('orderby' => 'asc');
//        $arr['table'] = 'sw_subway';
//        $res = $this->IDBModel->M_getLimit($arr);
//        return $res;
        return db('sw_subway')->where('cid', $cid)->where('line', $line)->order('orderby')->select();;
    }

    /**
     * 根据城市线路获取地铁站点（格式化）
     *
     * @param int $cid  城市
     * @param int $line 线路
     *
     * @return array
     */
    public function C_getSiteByLineFormat($cid, $line) {
        $result = $this->C_getSiteByLine($cid, $line);
        $res_subway = null;
        if (is_array($result)) {
            foreach ($result as $k => $v) {
                $res_subway[$cid . '_' . $line . '_' . $v['id']] = $v['name'];
            }
        }
        return $res_subway;
    }

    /**
     * 获取地铁换乘方案
     *
     * @param type $start 起点线路start_line_start_site
     * @param type $end   终点线路end_line_end_site
     *
     * @return mixed
     */
    public function C_getSubwayTransferMode($start, $end) {
        if (empty($start) || empty($end)) {
            return array('error' => '-1',
                'message' => '参数错误');
        }
        if ($start == $end) {
            return array('error' => '-1',
                'message' => '当前起始点一致，无需换乘');
        }
        $start_array = explode('_', $start);
        $end_array = explode('_', $end);

        $city = $start_array[0];

        $start_line = $start_array[1];
        $start_site = $start_array[2];
        $end_line = $end_array[1];
        $end_site = $end_array[2];

        $res_line = $this->C_getLineByCity($city);
        foreach ($res_line as $key => $value) {
            $line[$value['line']] = $value['name'];
        }

        $data = $this->C_taransfer($city, $start_line, $start_site, $end_line, $end_site);
        //print_r($data);
        $this->C_getTransfer($data, $str);
        $str = substr($str, 0, -1);
        $str_array = explode(',', $str);
        //print_r($str_array);
        $str_line_2 = '';
        foreach ($str_array as $key => &$value) {
            $str_array_1 = explode(' ', $value);
            //print_r($str_array_1);
            $str_line = rtrim(ltrim($str_array_1[0], '['), ']');
            //print_r($str_line);
            $str_array_2 = explode('-', $str_line);
            //print_r($str_array_2);
            $last_count = $last_count_tmp = count($str_array_2) - 1;
            $tmp_line = '';
            foreach ($str_array_2 as $k => $v) {
                $a = substr($v, 0, strpos($v, '('));
                $b = substr($v, strpos($v, '('));
                if ($tmp_line == $b && $k < $last_count_tmp) {
                    $last_count--;
                    continue;
                }
                $tmp_line = $b;
                if ($k == $last_count_tmp) {
                    $str_line_2 .= $line[$a] . ' >> ';
                } else {
                    $str_line_2 .= $line[$a] . '>' . $b . ' >> ';
                }
            }
            //print_r($str_line_2);
            $str_line_2 = '[ ' . substr($str_line_2, 0, -4) . ' ]';
            $str_array_1_1 = explode('_', $str_array_1[1]); // 公里数_经过站数
            //print_r($str_array_1_1);
            $money = $this->C_getMoney($city, $str_array_1_1[0], $str_array_1_1[1]);
            $value = $str_line_2 . ' 共' . $str_array_1_1[0] . 'Km，经过' . $str_array_1_1[1] . '站，换乘' . $last_count . '次，需RMB ' . $money;
            $str_line_2 = '';

            $tmp_count[$key] = $last_count;  // 换乘次数
            $tmp_dis[$key] = $str_array_1_1[0]; // 所需金钱
            $tmp[$key] = array('key' => $key,
                'transfer' => $last_count,
                'money' => $str_array_1_1[0],
                'count' => $str_array_1_1[1]);
        }
        //print_r($str_array);
        $str_array = array_unique($str_array);
        //print_r($str_array);
        // 最短距离（最小花费）
        $pos_min = array_search(min($tmp_dis), $tmp_dis);

        // 最少换乘 + （最小花费） 完事后取数组第一个
        array_multisort($tmp_count, SORT_ASC, $tmp_dis, SORT_ASC, $tmp);

        $result['min'] = $str_array[$pos_min];
        $result['min2'] = $str_array[$tmp[0]['key']];

        rsort($str_array);
        $result['allline'] = $str_array;
        $result['count'] = count($str_array);
        $result['error'] = 0;

        return $result;
    }

    /**
     * 根据公里数换算成需要支付的金额
     * 北京 :: 32公里以上：每20公里1块，12到32公里：每10公里1块，6到12公里：4块，6公里内：3块
     * 天津 :: 乘坐5站以内（含5站）票价2元；乘坐5站以上10站以下（含10站）票价3元；乘坐10站以上16站以下（含16站）票价4元；乘坐16站以上票价5元
     * 上海 :: 0~6公里3元，6公里之后每10公里增加1元;
     * 广州 :: 起步4公里以内2元；4～12公里范围内每递增4公里加1元；12～24公里范围内每递增6公里加1元；24公里以后，每递增8公里加1元。APM独立计费，每程2元。
     * 深圳 :: 首4公里2元;4公里至12公里部分，每1元可乘坐4公里;12公里至24公里部分，每1元可乘坐6公里;超过24公里，每1元可乘坐8公里。
     *
     * @param int $city  城市
     * @param float $dis 公里数
     * @param int $trans 途径站点数
     *
     * @return int
     */
    private function C_getMoney($city = 1, $dis = 0, $trans = 0) {
        if ($city == 1) {
            if ($dis > 32) {
                $money = ceil(($dis - 32) / 20) + 2 + 4;
            } else if ($dis > 12 && $dis <= 32) {
                $money = ceil(($dis - 12) / 10) + 4;
            } else if ($dis > 6 && $dis <= 12) {
                $money = 4;
            } else {
                $money = 3;
            }
        } else if ($city == 2) {
            if ($trans > 16) {
                $money = 5;
            } else if ($trans > 10 && $trans <= 16) {
                $money = 4;
            } else if ($trans > 5 && $trans <= 10) {
                $money = 3;
            } else {
                $money = 2;
            }
        } else if ($city == 3) {
            if ($dis <= 6) {
                $money = 3;
            } else {
                $money = ceil(($dis - 6) / 10) + 3;
            }
        } else if ($city == 4 || $city == 5) {
            if ($dis > 24) {
                $money = ceil(($dis - 24) / 8) + 6;
            } else if ($dis > 12 && $dis <= 24) {
                $money = ceil(($dis - 12) / 6) + 4;
            } else if ($dis > 4 && $dis <= 12) {
                $money = ceil(($dis - 4) / 4) + 2;
            } else {
                $money = 2;
            }
        } else {
            $money = 0;
        }

        return $money;
    }

    /**
     * 递归 将计算好的线路按规则取出
     *
     * @param type $array
     * @param type $str
     *
     * @return string
     */
    private function C_getTransfer($array, &$str) {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $str = $this->C_getTransfer($value, $str);
            } else {
                if ($value) {
                    $str .= '[' . $key . '] ' . $value . ',';
                }
            }
        }
        return $str;
    }

    /**
     * 递归 计算起点到终点的所有可能线路
     *
     * @param $cid
     * @param $start_line
     * @param $start_site
     * @param $end_line
     * @param $end_site
     * @param array $remove
     * @param int $tmp_dis
     * @param string $tmp_line
     * @param int $tmp_count
     *
     * @return array|string
     */
    private function C_taransfer($cid, $start_line, $start_site, $end_line, $end_site, &$remove = array(), $tmp_dis = 0, $tmp_line = '', $tmp_count = 0) {
        $dis = '';
        // 查询起点信息
//        $start['select'] = "line,`name`,distance,is_transfer,orderby";
//        $start['where'] = array("id" => $start_site);
//        $start['table'] = 'sw_subway';
//        $res_start = $this->IDBModel->M_getRow($start);
        $res_start = db('sw_subway')->where('id',$start_site)
            ->field('line,`name`,distance,is_transfer,orderby')->find();

        // 查询终点信息
//        $end['select'] = "line,`name`,distance,is_transfer,orderby";
//        $end['where'] = array("id" => $end_site);
//        $end['table'] = 'sw_subway';
//        $res_end = $this->IDBModel->M_getRow($end);
        $res_end = db('sw_subway')->where('id',$end_site)
            ->field('line,`name`,distance,is_transfer,orderby')->find();
        // 若起点线路和终点线路一致 则直接计算距离 退出
        if ($start_line == $end_line) {
            // 环线
            if (($cid == 1 && ($start_line == 2 || $start_line == 10)) || ($cid == 3 && $start_line == 4)) {
                if ($res_start['name'] == $res_end['name']) {
                    $dis[$start_line . '(' . $start_site . ')'] = 0 . '_' . abs($res_end['orderby'] - $res_start['orderby']);
                } else {
                    // 作为起点公里数
//                    $loop1['select'] = "name,distance,orderby";
//                    $loop1['where'] = array("line" => $start_line,
//                        "is_loop" => 1);
//                    $loop1['table'] = 'sw_subway';
//                    $res_loop1 = $this->IDBModel->M_getRow($loop1);
                    $res_loop1 = db('sw_subway')->where('line',$start_line)->where('is_loop',1)
                        ->field('name,distance,orderby')->find();
                    // 作为终点公里数
//                    $loop2['select'] = "name,distance,orderby";
//                    $loop2['where'] = array("line" => $start_line,
//                        "is_loop" => 2);
//                    $loop2['table'] = 'sw_subway';
//                    $res_loop2 = $this->IDBModel->M_getRow($loop2);
                    $res_loop2 = db('sw_subway')->where('line',$start_line)->where('is_loop',2)
                        ->field('name,distance,orderby')->find();

                    // 起点到A、B点距离
                    $a1 = abs($res_loop1['distance'] - $res_start['distance']);
                    $a2 = abs($res_loop2['distance'] - $res_start['distance']);
                    // 终点到A、B点距离
                    $b1 = abs($res_loop1['distance'] - $res_end['distance']);
                    $b2 = abs($res_loop2['distance'] - $res_end['distance']);
                    // 同侧距离
                    $a_t = abs($b1 - $a1);
                    $b_t = abs($b2 - $a2);
                    $tmp_t = $a_t;
                    // 跨越距离
                    $a_k = ($a1 > $a2) ? $a2 : $a1;
                    $b_k = ($b1 > $b2) ? $b2 : $b1;
                    $tmp_k = $a_k + $b_k;
                    // 最终距离 和 站数
                    if ($tmp_t > $tmp_k) {
                        // 跨越计算
                        $tmp_count1 = abs($res_start['orderby'] - $res_loop1['orderby']);
                        $tmp_count2 = abs($res_end['orderby'] - $res_loop2['orderby']);
                        $tmp_count_h = $tmp_count1 + $tmp_count2;
                        $tmp_dis_h = $tmp_k;
                    } else {
                        // 同侧计算
                        $tmp_count_h = abs($res_end['orderby'] - $res_start['orderby']);
                        $tmp_dis_h = $tmp_t;
                    }

                    $dis[$start_line . '(' . $start_site . ')'] = $tmp_dis_h . '_' . $tmp_count_h;
                }
            } else {
                $dis[$start_line . '(' . $start_site . ')'] = abs($res_end['distance'] - $res_start['distance']) . '_' . abs($res_end['orderby'] - $res_start['orderby']);
            }
        } else {
            // 去除重复
            $remove = array_unique($remove);

            // 查询起点站所在线路能换乘的线路及换乘站
//            $arr['select'] = "t.line,t.sid,t.name,s.distance,s.orderby,t.t_line,t.t_sid,t.t_name,t_s.distance AS t_distance,t_s.orderby AS t_orderby";
//            $arr['from'] = array("sw_transfer" => "t");
//            $arr['join'] = array("sw_subway as s" => "s.id = t.sid",
//                "sw_subway as t_s" => "t_s.id = t.t_sid");
//            $arr['where'] = array("t.cid" => $cid,
//                "t.line" => $start_line);
//            $arr['notin'] = array("t.t_line" => $remove,
//                "t.sid" => $start_site);
//            $res_2 = $this->IDBModel->M_getLimit($arr);
            $res_2 = db('sw_transfer')->alias('t')
                ->join('sw_subway s','s.id = t.sid')
                ->join('sw_subway t_s','t_s.id = t.t_sid')
                ->where('t.cid',$cid)->where('t.line',$start_line)
                ->whereNotIn('t.t_line',$remove)->whereNotIn('t.sid',$start_site)
                ->field('t.line,t.sid,t.name,s.distance,s.orderby,t.t_line,t.t_sid,t.t_name,t_s.distance t_distance,t_s.orderby t_orderby')
                ->select();
            // 排序（按起点与换乘点之间的距离排序）
            if ($res_2 && is_array($res_2)) {
                foreach ($res_2 as $k => $v) {
                    $vals[$k] = abs($v['distance'] - $res_start['distance']);
                }
                array_multisort($vals, SORT_ASC, SORT_NUMERIC, $res_2);
            }

            foreach ($res_2 as $key => &$value) {
                // 计算换乘点和起点的距离
                $value['dis_start'] = abs($value['distance'] - $res_start['distance']) + $tmp_dis;
                // 计算换乘点和起点的站数
                $value['count_start'] = abs($value['orderby'] - $res_start['orderby']) + $tmp_count;
                if ($value['t_line'] == $end_line) {
                    // 若终点线路在当前列表中
                    $value['is_t'] = 1;
                    $value['dis_end'] = abs($value['t_distance'] - $res_end['distance']);
                    $value['count_end'] = abs($value['t_orderby'] - $res_end['orderby']);
                    if (!empty($tmp_line)) {
                        $value['line_tran'] = $tmp_line . '-' . $value['line'] . '(' . $value['name'] . ')' . '-' . $value['t_line'] . '(' . $value['t_name'] . ')';
                    } else {
                        $value['line_tran'] = $value['line'] . '(' . $value['name'] . ')' . '-' . $value['t_line'] . '(' . $value['t_name'] . ')';
                    }
                } else {
                    $value['is_t'] = 0;
                    // 查询过的线路不在出现在后续的换乘列表中
                    $remove[] = $start_line;
                    if (!empty($tmp_line)) {
                        $value['line_tran'] = $tmp_line . '-' . $value['line'] . '(' . $value['name'] . ')';
                    } else {
                        $value['line_tran'] = $value['line'] . '(' . $value['name'] . ')';
                    }
                    $dis[] = $this->C_taransfer($cid, $value['t_line'], $value['t_sid'], $end_line, $end_site, $remove, $value['dis_start'], $value['line_tran'], $value['count_start']);
                    //print_r($dis);
                }
            }
            foreach ($res_2 as $key => &$value) {
                if ($value['is_t'] == 1) {
                    $dis[$value['line_tran'] . '#' . abs($value['dis_start'] + $value['dis_end'])] = abs($value['dis_start'] + $value['dis_end']) . '_' . abs($value['count_start'] + $value['count_end']);
                }
            }
        }
        //print_r($dis);
        return $dis;
    }
}
