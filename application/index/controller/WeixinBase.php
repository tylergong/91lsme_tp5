<?php

namespace app\index\controller;

use think\Controller;
use app\system\controller\HttpHelp;
use think\Log;

class WeixinBase extends Controller {
    /**
     * 处理消息
     */
    public function responseMsg() {
        $postStr = $GLOBALS["HTTP_RAW_POST_DATA"];
//		$postStr = "<xml><ToUserName><![CDATA[gh_a2d9e56c5339]]></ToUserName>
//					<FromUserName><![CDATA[o6vu8jrVVL33D8nuvOCq0_HDp-Ho]]></FromUserName>
//					<CreateTime>1419403311</CreateTime>
//					<MsgType><![CDATA[location]]></MsgType>
//					<Location_X>39.905762</Location_X>
//					<Location_Y>116.514519</Location_Y>
//					<Scale>16</Scale>
//					<Label><![CDATA[朝阳区高碑店乡半壁店村西店1008-A号(通惠河南路四惠建材城向东800米)]]></Label>
//					<MsgId>6096290800782780333</MsgId>
//					</xml>";
        if (!empty($postStr)) {
            $pushData = '';
            $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
            $postObj = is_object($postObj) ? get_object_vars($postObj) : $postObj;
            $pushData['fromUserName'] = $postObj['FromUserName']; // 接收方微信号
            $pushData['toUserName'] = $postObj['ToUserName'];  // 发送方微信号，若为普通用户，则是一个OpenID
            $msgType = strtolower(trim($postObj['MsgType']));  // 消息类型
            switch ($msgType) {
                // 文本消息
                case 'text':
                    $content = strtolower(trim($postObj['Content'])); // 文本消息内容
                    $result = $this->search($content, $postObj['FromUserName']); // 调用查询接口
                    $resultStr = $this->handleResult($result, $pushData); // 处理返回消息
                    break;
                // 语音消息
                case 'voice':
                    $recognition = trim($postObj['Recognition']); // 文本消息内容
                    $msgid = trim($postObj['MsgId']); // 消息id
                    if (empty($recognition)) {
                        $pushData['contentStr'] = DEFAULT_STRING1 . DEFAULT_STRING;
                        $resultStr = $this->replyMessage("text", $pushData);
                    } else {
                        $result = $this->search($recognition, $postObj['FromUserName']); // 调用查询接口
                        $resultStr = $this->handleResult($result, $pushData); // 处理返回消息
                    }
                    break;
                // 地理位置
                case 'location':
                    // 存储用户当前地址信息
                    $data['wxid'] = $postObj['FromUserName'];
                    $data['Location_X'] = trim($postObj['Location_X']); // 地理位置维度
                    $data['Location_Y'] = trim($postObj['Location_Y']); // 地理位置经度
                    $data['Label'] = trim($postObj['Label']); // 地理位置信息
                    $data['uptime'] = time(); // 当前时间
                    //$rtn = $this->IDBModel->M_add('ls_wxlocation', $data, true, 'wxid');
                    $tmp = db('ls_wxlocation')->where('wxid', $data['wxid'])->find();
                    if ($tmp) {
                        $rtn = db('ls_wxlocation')->where('wxid', $data['wxid'])->update($data);
                    } else {
                        $rtn = db('ls_wxlocation')->insert($data);
                    }
                    if ($rtn > 0) {
                        $pushData['contentStr'] = DEFAULT_STRING5;
                    } else {
                        $pushData['contentStr'] = DEFAULT_STRING6;
                    }
                    $resultStr = $this->replyMessage("text", $pushData);
                    break;
                case "image":
                    $imgurl = $postObj['PicUrl'];
                    $pushData['contentStr'] = $this->getFaceInfo($imgurl);
                    $resultStr = $this->replyMessage("text", $pushData);
                    break;
                // 事件
                case 'event':
                    $event = strtolower(trim($postObj['Event'])); // 事件类型
                    $eventKey = strtolower(trim($postObj['EventKey'])); // 事件KEY值
                    switch ($event) {
                        // 点击事件
                        case 'click':
                            if ($eventKey == 'subway') {
                                $pushData['contentStr'] = '地铁';
                            } else if ($eventKey == 'about') {
                                $pushData['contentStr'] = '关于';
                            } else {
                                $pushData['contentStr'] = '其他';
                            }
                            break;
                        // 关注事件
                        case 'subscribe':
                            // 正常关注（之前未关注）
                            $pushData['contentStr'] = DEFAULT_STRING4 . DEFAULT_STRING;
                            break;
                    }
                    $resultStr = $this->replyMessage("text", $pushData);
                    break;
                // 其他
                default :
                    $pushData['contentStr'] = DEFAULT_STRING1 . DEFAULT_STRING;
                    $resultStr = $this->replyMessage("text", $pushData);
                    break;
            }
        } // 未收到消息
        else {
            $pushData['contentStr'] = DEFAULT_STRING1 . DEFAULT_STRING;
            $resultStr = $this->replyMessage("text", $pushData);
        }
        // 输出结果给微信
        echo $resultStr;
        exit;
    }

    /**
     * 提交搜索请求，并返回结果集
     *
     * @param type $keyword
     * @param type $wxid
     *
     * @return boolean
     */
    private function search($keyword, $wxid) {
        // 若无固定关键词查询
        if (stripos($keyword, '@') === false) {
            $res['type'] = 'text';
            // 帮助
            if ($keyword == 'help' || $keyword == '帮助') {
                $res['contentStr'] = DEFAULT_STRING1 . DEFAULT_STRING;
            } else if ($keyword == '地铁') {
                $res['type'] = 'news';
                $res['tracks'][0]['title'] = "全国地铁换乘线路查询（北、上、广、深、津）";
                $res['tracks'][0]['description'] = '';
                $res['tracks'][0]['picUrl'] = 'http://www.91lsme.com/static/index/images/dt2.png';
                $res['tracks'][0]['url'] = 'http://www.91lsme.com/subway/1.html';
            } else {
                $res['contentStr'] = DEFAULT_STRING3 . DEFAULT_STRING;
            }
        } // 固定关键词查询
        else {
            $key = explode('@', strtolower($keyword));
            switch ($key[0]) {
                case '天气':
                    $data = $this->getWeatherInfo($key[1]);
                    $res['tracks'] = $data;
                    $res['type'] = 'news';
                    break;
                case "附近":
                    $data = $this->getNearPlace($wxid, $key[1]);
                    if (isset($data['error']) && $data['error'] != 0) {
                        // 数据错误
                        $res['type'] = 'text';
                        $res['contentStr'] = $data['msg'];
                    } else {
                        // 数据正常
                        $res['tracks'] = $data;
                        $res['type'] = 'news';
                    }
                    break;
                case "快递":
                    $data = $this->getKuaiDi($key[1]);
                    $res['type'] = 'text';
                    $res['contentStr'] = $data;
                    break;
                default:
                    $res['type'] = 'text';
                    $res['contentStr'] = $key[0] . "? \n" . DEFAULT_STRING2 . DEFAULT_STRING;
                    break;
            }
        }
        return $res;
    }

    /**
     * 获取天气数据
     *
     * @param $cityName
     *
     * @return mixed
     */
    private function getWeatherInfo($cityName) {
        $url = "http://api.map.baidu.com/telematics/v3/weather?location=" . $cityName . "&output=json&ak=" . BAIDU_AK;
        $json = HttpHelp::CurlRequest($url);
        $data = json_decode($json);
        foreach ($data->results[0]->weather_data as $k => $v) {
            if ($k == 0) {
                $rtn[$k]['title'] = '【' . $data->results[0]->currentCity . '】天气实况 ' . $v->weather . ' ' . $v->wind . ' ' . $v->temperature . ' PM2.5指数：' . $data->results[0]->pm25;
                $rtn[$k]['description'] = '';
                $rtn[$k]['picUrl'] = 'http://www.91lsme.com/static/index/images/tq.png';
                $rtn[$k]['url'] = '';
            } else {
                $rtn[$k]['title'] = $v->date . ' ' . $v->weather . ' ' . $v->wind . ' ' . $v->temperature;
                $rtn[$k]['description'] = '';
                $rtn[$k]['picUrl'] = $v->dayPictureUrl;
                $rtn[$k]['url'] = '';
            }
        }
        return $rtn;
    }

    /**
     * 获取附近目标
     *
     * @param type $wxid
     * @param type $keyword
     *
     * @return mixed
     */
    private function getNearPlace($wxid, $keyword) {
        $res = $this->getUserLocation($wxid);
        if ($res['error'] == 0) {
            $url = "http://api.map.baidu.com/place/search?&query=" . $keyword . "&location=" . $res['Location_X'] . "," . $res['Location_Y'] . "&radius=10000&output=xml&key=" . BAIDU_AK;
            $xml = HttpHelp::CurlRequest($url);
            $data = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
            $data = is_object($data) ? get_object_vars($data) : $data;

            $results = $data['results'];
            for ($i = 0; $i < count($results); $i++) {
                $distance = $this->getDistance($res['Location_X'], $res['Location_Y'], $results->result[$i]->location->lat, $results->result[$i]->location->lng);
                $shopSortArrays[$distance] = array(
                    "Title" => "【" . $results->result[$i]->name . "】<" . $distance . "M>" . $results->result[$i]->address . (isset($results->result[$i]->telephone) ? " " . $results->result[$i]->telephone : ""),
                    "Description" => "",
                    "PicUrl" => "",
                    "Url" => "");
            }
            $shopArray = array();

            if (is_array($shopSortArrays)) {
                ksort($shopSortArrays); //排序
                foreach ($shopSortArrays as $key => $value) {
                    $shopArray[] = array(
                        "title" => $value["Title"],
                        "description" => $value["Description"],
                        "picUrl" => $value["PicUrl"],
                        "url" => $value["Url"],
                    );
                    if (count($shopArray) >= 6) {
                        break;
                    }
                }
            }
            // 首行特殊处理
            $once['title'] = '您附近【' . $keyword . '】信息如下';
            $once['description'] = '';
            $once['picUrl'] = 'http://www.91lsme.com/static/index/images/fj.png';
            $once['url'] = '';
            array_unshift($shopArray, $once); // 将信息压入 数组第一个位置
            return $shopArray;
        } else {
            return $res;
        }
    }

    /**
     *  根据坐标获取距离
     *
     * @param type $lat_a
     * @param type $lng_a
     * @param type $lat_b
     * @param type $lng_b
     *
     * @return type
     */
    private function getDistance($lat_a, $lng_a, $lat_b, $lng_b) {
        $lat_b = floatval($lat_b);
        $lng_b = floatval($lng_b);
        //R是地球半径（米）
        $R = 6366000;
        $pk = doubleval(180 / 3.14169);

        $a1 = doubleval($lat_a / $pk);
        $a2 = doubleval($lng_a / $pk);
        $b1 = doubleval($lat_b / $pk);
        $b2 = doubleval($lng_b / $pk);
        $t1 = doubleval(cos($a1) * cos($a2) * cos($b1) * cos($b2));

        $t2 = doubleval(cos($a1) * sin($a2) * cos($b1) * sin($b2));
        $t3 = doubleval(sin($a1) * sin($b1));
        $tt = doubleval(acos($t1 + $t2 + $t3));

        return round($R * $tt);
    }

    /**
     * 获取当前用户位置信息
     *
     * @param type $wxid
     *
     * @return string
     */
    private function getUserLocation($wxid) {
//        $arr['where'] = array('wxid' => $wxid);
//        $arr['table'] = 'ls_wxlocation';
//        $rtn = $this->IDBModel->M_getRow($arr);
        $rtn = db('ls_wxlocation')->find($wxid);
        if ($rtn) {
            if (time() > ($rtn['uptime'] + 3600)) {
                $res['error'] = -2;
                $res['msg'] = DEFAULT_STRING7;
            } else {
                $res['error'] = 0;
                $res['Location_X'] = $rtn['Location_X'];
                $res['Location_Y'] = $rtn['Location_Y'];
            }
        } else {
            $res['error'] = -1;
            $res['msg'] = DEFAULT_STRING8;
        }
        return $res;
    }

    /**
     * 根据快递号获取快递信息
     *
     * @param $num
     *
     * @return string
     */
    private function getKuaiDi($num) {
        $url = "http://www.kuaidi100.com/autonumber/auto?num=" . $num;
        $json = HttpHelp::CurlRequest($url);
        $data = json_decode($json);
        if ($data && !empty($data[0]->comCode)) {
            $url2 = "http://www.kuaidi100.com/query?type=" . $data[0]->comCode . "&postid=" . $num;
            $json2 = HttpHelp::CurlRequest($url2);
            $data2 = json_decode($json2);
            $str = $this->getKuaiDiComName($data2->com) . " [" . $data2->nu . "]\n";
            foreach ($data2->data as $k => $v) {
                $str .= $v->time . " " . $v->context . "\n";
            }
        } else {
            $str = '对不起，暂时没有查到该快递信息！';
        }
        return $str;
    }

    /**
     * 根据转换快递公司名称
     *
     * @param $com
     *
     * @return mixed
     */
    private function getKuaiDiComName($com) {
        $comArr = array(
            'aae' => 'AAE',
            'anxindakuaixi' => '安信达',
            'huitongkuaidi' => '百世汇通',
            'baifudongfang' => '百福东方',
            'bht' => 'BHT',
            'youzhengguonei' => '包裹/平邮/挂号信',
            'bangsongwuliu' => '邦送物流',
            'cces' => '希伊艾斯',
            'coe' => '中国东方',
            'chuanxiwuliu' => '传喜物流',
            'datianwuliu' => '大田物流',
            'debangwuliu' => '德邦物流',
            'dhl' => 'DHL',
            'dsukuaidi' => 'D速快递',
            'disifang' => '递四方',
            'ems' => 'EMS',
            'emsguoji' => 'EMS',
            'fedex' => 'FedEx',
            'fedexcn' => 'FedEx',
            'feikangda' => '飞康达物流',
            'feikuaida' => '飞快达',
            'rufengda' => '凡客如风达',
            'fengxingtianxia' => '风行天下',
            'feibaokuaidi' => '飞豹快递',
            'ganzhongnengda' => '港中能达',
            'guotongkuaidi' => '国通快递',
            'guangdongyouzhengwuliu' => '广东邮政',
            'youzhengguonei' => '挂号信',
            'youzhengguonei' => '国内邮件',
            'youzhengguoji' => '国际邮件',
            'gls' => 'GLS',
            'gongsuda' => '共速达',
            'huitongkuaidi' => '汇通快运',
            'huiqiangkuaidi' => '汇强快递',
            'tiandihuayu' => '华宇物流',
            'hengluwuliu' => '恒路物流',
            'huaxialongwuliu' => '华夏龙',
            'tiantian' => '海航天天',
            'haiwaihuanqiu' => '海外环球',
            'hebeijianhua' => '河北建华',
            'haimengsudi' => '海盟速递',
            'huaqikuaiyun' => '华企快运',
            'haihongwangsong' => '山东海红',
            'jiajiwuliu' => '佳吉物流',
            'jiayiwuliu' => '佳怡物流',
            'jiayunmeiwuliu' => '加运美',
            "jinguangsudikuaijian" => "京广速递",
            "jixianda" => "急先达",
            "jinyuekuaidi" => "晋越快递",
            "jietekuaidi" => "捷特快递",
            "jindawuliu" => "金大物流",
            "jialidatong" => "嘉里大通",
            "kuaijiesudi" => "快捷速递",
            "kangliwuliu" => "康力物流",
            "kuayue" => "跨越物流",
            "lianhaowuliu" => "联昊通",
            "longbanwuliu" => "龙邦物流",
            "lanbiaokuaidi" => "蓝镖快递",
            "lejiedi" => "乐捷递",
            "lianbangkuaidi" => "联邦快递",
            "lianbangkuaidien" => "联邦快递",
            "lijisong" => "立即送",
            "longlangkuaidi" => "隆浪快递",
            "menduimen" => "门对门",
            "meiguokuaidi" => "美国快递",
            "mingliangwuliu" => "明亮物流",
            "ocs" => "OCS",
            "ontrac" => "onTrac",
            "quanchenkuaidi" => "全晨快递",
            "quanjitong" => "全际通",
            "quanritongkuaidi" => "全日通",
            "quanyikuaidi" => "全一快递",
            "quanfengkuaidi" => "全峰快递",
            "sevendays" => "七天连锁",
            "rufengda" => "如风达快递",
            "shentong" => "申通",
            "shunfeng" => "顺丰速递",
            "shunfengen" => "顺丰速递",
            "santaisudi" => "三态速递",
            "shenghuiwuliu" => "盛辉物流",
            "suer" => "速尔物流",
            "shengfengwuliu" => "盛丰物流",
            "shangda" => "上大物流",
            "santaisudi" => "三态速递",
            "haihongwangsong" => "山东海红",
            "saiaodi" => "赛澳递",
            "haihongwangsong" => "山东海红",
            "sxhongmajia" => "山西红马甲",
            "shenganwuliu" => "圣安物流",
            "suijiawuliu" => "穗佳物流",
            "tiandihuayu" => "天地华宇",
            "tiantian" => "天天快递",
            "tnt" => "TNT",
            "tnten" => "TNT",
            "tonghetianxia" => "通和天下",
            "ups" => "UPS",
            "upsen" => "UPS",
            "youshuwuliu" => "优速物流",
            "usps" => "USPS",
            "wanjiawuliu" => "万家物流",
            "wanxiangwuliu" => "万象物流",
            "weitepai" => "微特派",
            "xinbangwuliu" => "新邦物流",
            "xinfengwuliu" => "信丰物流",
            "xinbangwuliu" => "新邦物流",
            "neweggozzo" => "新蛋奥硕物流",
            "hkpost" => "香港邮政",
            "yuantong" => "圆通速递",
            "yunda" => "韵达快运",
            "yuntongkuaidi" => "运通快递",
            "youzhengguonei" => "邮政小包",
            "youzhengguoji" => "邮政小包",
            "yuanchengwuliu" => "远成物流",
            "yafengsudi" => "亚风速递",
            "yibangwuliu" => "一邦速递",
            "youshuwuliu" => "优速物流",
            "yuanweifeng" => "源伟丰快递",
            "yuanzhijiecheng" => "元智捷诚",
            "yuefengwuliu" => "越丰物流",
            "yuananda" => "源安达",
            "yuanfeihangwuliu" => "原飞航",
            "zhongxinda" => "忠信达快递",
            "zhimakaimen" => "芝麻开门",
            "yinjiesudi" => "银捷速递",
            "yitongfeihong" => "一统飞鸿",
            "zhongtong" => "中通速递",
            "zhaijisong" => "宅急送",
            "zhongyouwuliu" => "中邮物流",
            "zhongxinda" => "忠信达",
            "zhongsukuaidi" => "中速快件",
            "zhimakaimen" => "芝麻开门",
            "zhengzhoujianhua" => "郑州建华",
            "zhongtianwanyun" => "中天万运",
        );
        return empty($comArr[$com]) ? $com : $comArr[$com];
    }

    /**
     * 获取人脸识别信息
     *
     * @param $imgurl
     *
     * @return string
     */
    private function getFaceInfo($imgurl) {
        $url = FACE_APIURL . 'v2/detection/detect?api_key=' . FACE_APIKEY . '&api_secret=' . FACE_APISECRET . '&url=' . $imgurl . '&attribute=glass,pose,gender,age,race,smiling';
        $json = HttpHelp::CurlRequest($url);
        $data = json_decode($json);
        if (count($data->face) == 1) {
            $sex = ($data->face[0]->attribute->gender->value == 'Male') ? '男' : '女';
            $sex_range = $data->face[0]->attribute->gender->confidence;
            $age = $data->face[0]->attribute->age->value;
            $age_range = $data->face[0]->attribute->age->range;
            $race = ($data->face[0]->attribute->race->value == 'Asian') ? '黄种人' : (($data->face[0]->attribute->race->value == 'White') ? '白种人' : '黑种人');
            $smail = $data->face[0]->attribute->smiling->value;
            $str = "据本大神~察颜~观色~...嘿~有了：\n"
                . "性别：" . $sex . "，可信度为" . $sex_range . "%\n"
                . "年龄：" . $age . "岁，误差" . $age_range . "岁左右\n"
                . "种族：应该是" . $race . "\n"
                . "微笑度：" . $smail . "%";
        } else if (count($data->face) == 2) {
            $face_id1 = $data->face[0]->face_id;
            $face_id2 = $data->face[1]->face_id;
            $url2 = FACE_APIURL . 'v2/recognition/compare?api_key=' . FACE_APIKEY . '&api_secret=' . FACE_APISECRET . '&face_id1=' . $face_id1 . '&face_id2=' . $face_id2;
            $json2 = HttpHelp::CurlRequest($url2);
            $data2 = json_decode($json2);

            $similarity = $data2->similarity;
            $eye = $data2->component_similarity->eye;
            $eyebrow = $data2->component_similarity->eyebrow;
            $mouth = $data2->component_similarity->mouth;
            $nose = $data2->component_similarity->nose;
            $str = "据本大神~再三观察~...原来有2人啊，来比较一下哈......有了：\n"
                . "整体相似度为：" . $similarity . "%\n"
                . "我在仔细看看的吧~~~~~~~：\n"
                . "眼 相似度：" . $eye . "%\n"
                . "眉 相似度：" . $eyebrow . "%\n"
                . "嘴 相似度：" . $mouth . "%\n"
                . "鼻 相似度：" . $nose . "%\n";
        } else {
            $str = "none";
        }
        return $str;
    }

    /**
     * 处理搜索结果
     *
     * @param string $result
     * @param string $pushData
     *
     * @return type
     */
    private function handleResult($result = '', $pushData = '') {
        if ($result) {
            if ($result['type'] == 'news') {
                $pushData['tracks'] = $result['tracks'];
            } else {
                $pushData['contentStr'] = $result['contentStr'];
            }
            $resultStr = $this->replyMessage($result['type'], $pushData);
        } else {
            // 异常
            $pushData['contentStr'] = DEFAULT_STRING3 . DEFAULT_STRING;
            $resultStr = $this->replyMessage("text", $pushData);
        }
        return $resultStr;
    }

    /**
     * 回复用户消息
     *
     * @param string $type
     * @param string $data
     *
     * @return string
     */
    private function replyMessage($type = '', $data = '') {
        $time = time();
        // 公用部分[]
        // ToUserName	是	接收方帐号（收到的OpenID）
        // FromUserName	是	开发者微信号
        // CreateTime	是	消息创建时间 （整型）
        $Tpl = "<xml>
				<ToUserName><![CDATA[" . $data['fromUserName'] . "]]></ToUserName>
				<FromUserName><![CDATA[" . $data['toUserName'] . "]]></FromUserName>
				<CreateTime>$time</CreateTime>";
        switch ($type) {
            // 图文[]
            // MsgType		是	news
            // ArticleCount	是	图文消息个数，限制为10条以内
            // Articles		是	多条图文消息信息，默认第一个item为大图,注意，如果图文数超过10，则将会无响应
            // Title		否	图文消息标题
            // Description	否	图文消息描述
            // PicUrl		否	图片链接，支持JPG、PNG格式，较好的效果为大图360*200，小图200*200
            // Url			否	点击图文消息跳转链接
            case 'news':
                $Tpl .= "<MsgType><![CDATA[news]]></MsgType>
						<ArticleCount>" . count($data['tracks']) . "</ArticleCount>
						<Articles>";
                foreach ($data['tracks'] as $key => $val) {
                    $Tpl .= "<item>
							<Title><![CDATA[" . $val['title'] . "]]></Title>
							<Description><![CDATA[" . $val['description'] . "]]></Description>
							<PicUrl><![CDATA[" . $val['picUrl'] . "]]></PicUrl>
							<Url><![CDATA[" . $val['url'] . "]]></Url>
							</item>";
                }
                $Tpl .= "</Articles>";
                break;
            // 文本[]
            // MsgType	是	text
            // Content	是	回复的消息内容（换行：在content中能够换行，微信客户端就支持换行显示）
            case 'text':
            default:
                $Tpl .= "<MsgType><![CDATA[text]]></MsgType>
						<Content><![CDATA[" . $data['contentStr'] . "]]></Content>
						<FuncFlag>0</FuncFlag>";
                break;
        }
        $Tpl .= "</xml>";
        return $Tpl;
    }

    /**
     * 接入网站初始校验
     */
    public function valid() {
        $echoStr = $_GET["echostr"];
        if ($this->checkSignature()) {
            echo $echoStr;
            exit;
        }
    }

    /**
     * 参数校验
     *
     * @return boolean
     */
    private function checkSignature() {
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];
        $token = WEIXIN_TOKEN;
        $tmpArr = array($token,
            $timestamp,
            $nonce);
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode($tmpArr);
        $tmpStr = sha1($tmpStr);
        if ($tmpStr == $signature) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 获取当前自定义菜单
     *
     * @return boolean
     */
    public function getMenu() {
        $info = false;
        $token = self::getWeiXinToken();
        if (!$token) {
            return $info;
        }
        $send_url = "https://api.weixin.qq.com/cgi-bin/menu/get?access_token=" . $token;
        $result = HttpHelp::CurlRequest($send_url);
        $info = json_decode($result);
        return $info;
    }

    /**
     * 自定义微信公众号导航
     *
     * @return bool|mixed
     */
    public function createMenu() {
        $menu = false;
        $token = self::getWeiXinToken();
        print_r($token);
        if (!$token) {
            return false;
        }
//        $arr['table'] = 'ls_wxmenu';
//        $arr['where'] = array('fid' => 0);
//        $once_menu = $this->IDBModel->M_getLimit($arr);
        $once_menu = db('ls_wxmenu')->where('fid', 0)->select();
        if ($once_menu && is_array($once_menu)) {
            foreach ($once_menu as $key => $val) {
                $sec_arr['table'] = 'ls_wxmenu';
                $sec_arr['where'] = array('fid' => $val['id']);
                $sec_menu = $this->IDBModel->M_getLimit($sec_arr);
                if ($sec_menu && is_array($sec_menu)) {
                    foreach ($sec_menu as $key2 => $val2) {
                        if ($val2['type'] == 'click') {
                            $sub_button[] = array('type' => 'click',
                                'name' => $val2['name'],
                                'key' => $val2['content']);
                        } else if ($val2['type'] == 'view') {
                            $sub_button[] = array('type' => 'view',
                                'name' => $val2['name'],
                                'url' => $val2['content']);
                        }
                    }
                    $menu['button'][] = array('name' => $val['name'],
                        'sub_button' => $sub_button);
                    $sub_button = array();
                } else {
                    if ($val['type'] == 'click') {
                        $menu['button'][] = array('type' => 'click',
                            'name' => $val['name'],
                            'key' => $val['content']);
                    } else if ($val['type'] == 'view') {
                        $menu['button'][] = array('type' => 'view',
                            'name' => $val['name'],
                            'url' => $val['content']);
                    }
                }
            }
        }
        if (!$menu) {
            return false;
        }
        // 组合json格式
        $post_data = json_encode($menu, JSON_UNESCAPED_UNICODE);
        $send_url = "https://api.weixin.qq.com/cgi-bin/menu/create?access_token=" . $token;
        $result = HttpHelp::CurlRequest($send_url, $post_data);
        $info = json_decode($result);
        return $info;
    }

    /**
     * 获取 tonken
     */
    private static function getWeiXinToken() {
        $token = false;
        $appid = WEIXIN_APPID;
        $appsecret = WEIXIN_APPSECRET;
        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$appid&secret=$appsecret";
        $result = HttpHelp::CurlRequest($url);
        $result = json_decode($result);
        if ($result && isset($result->access_token)) {
            $token = $result->access_token;
        }
        return $token;
    }

}
