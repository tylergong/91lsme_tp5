<?php

namespace app\common\service;

class HttpHelp {
    //
    /**
     * 获取客户端ip
     *
     * HTTP_X_FORWARDED_FOR 获取到的数据可能会存在 ","号 的情况 需要截取内容
     *
     * @return string
     */
    public static function GetIPaddress() {

        if (isset($_SERVER)) {
            if (isset($_SERVER["HTTP_X_FORWARDED_FOR"])) {
                $ipArray = explode(',', $_SERVER["HTTP_X_FORWARDED_FOR"]);
                $realip = $ipArray[0];
            } elseif (isset($_SERVER["HTTP_CLIENT_IP"])) {
                $realip = $_SERVER["HTTP_CLIENT_IP"];
            } elseif (isset($_SERVER["REMOTE_ADDR"])) {
                $realip = $_SERVER["REMOTE_ADDR"];
            } else {
                $realip = $_SERVER["SSH_CLIENT"];
            }
        } else {
            if (getenv("HTTP_X_FORWARDED_FOR")) {
                $ipArray = explode(',', getenv("HTTP_X_FORWARDED_FOR"));
                $realip = $ipArray[0];
            } elseif (getenv("HTTP_CLIENT_IP")) {
                $realip = getenv("HTTP_CLIENT_IP");
            } else {
                $realip = getenv("REMOTE_ADDR");
            }
        }
        return addslashes($realip);
    }

    /**
     * CURL方式POST数据
     */
    public static function CurlRequest($url, $postfield = null, $proxy = "") {
        $t1 = microtime(TRUE);
        $proxy = trim($proxy);
        $user_agent = "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1)";
        $ch = curl_init(); // 初始化CURL句柄
        if (!empty($proxy)) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy); //设置代理服务器
        }
        curl_setopt($ch, CURLOPT_URL, $url); //设置请求的URL
        //curl_setopt($ch, CURLOPT_FAILONERROR, 1);
        // 启用时显示HTTP状态码，默认行为是忽略编号小于等于400的HTTP信息
        //curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        //启用时会将服务器服务器返回的“Location:”放在header中递归的返回给服务器
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        // 设为TRUE把curl_exec()结果转化为字串，而不是直接输出
        if ($postfield != null && $postfield != "") {
            curl_setopt($ch, CURLOPT_POST, 1); //启用POST提交
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postfield); //设置POST提交的字符串
        }
        if (preg_match('/^https:/', $url)) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        }
        //curl_setopt($ch, CURLOPT_PORT, 80); //设置端口
        curl_setopt($ch, CURLOPT_TIMEOUT, 25); // 超时时间
        //curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);//HTTP请求User-Agent:头
        //curl_setopt($ch,CURLOPT_HEADER,1);//设为TRUE在输出中包含头信息
        //$fp = fopen("example_homepage.txt", "w");//输出文件
        //curl_setopt($ch, CURLOPT_FILE, $fp);//设置输出文件的位置，值是一个资源类型，默认为STDOUT (浏览器)。
        $client_ip = self::GetIPaddress();
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Accept-Language: utf-8',
            'Connection: Keep-Alive',
            'Cache-Control: no-cache',
            'X-Real-IP: ' . $client_ip
        )); //设置HTTP头信息

        $document = curl_exec($ch); //执行预定义的CURL
        $info = curl_getinfo($ch); //得到返回信息的特性
        //print_r($info);
        //curl_close($ch);
        $t2 = microtime(TRUE) - $t1;
        $strpost = '';
        if ($postfield != null && $postfield != "") {
            $strpost = $postfield;
        }
        $str = 'ip[' . $client_ip . '] time[' . $t2 . '] request[' . $url . '] post[' . $strpost . ']';

        //FileHelp::WriteLog(1, 'd', $str, 'time', 'curl/time/');
        return $document;
    }
}
