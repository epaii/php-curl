<?php

namespace epii\curl;

use Curl\Curl;
use epii\curl\run\IListener;

/**
 * Created by PhpStorm.
 * User: mrren
 * Date: 2018/8/24
 * Time: 上午11:31
 */
class ClientListener implements IListener
{
    private $cookie_file = null;


    public function __construct($cookir_file)
    {
        $this->cookie_file = $cookir_file;
        if (!is_dir($cookie_dir = pathinfo($this->cookie_file, PATHINFO_DIRNAME))) {
            mkdir($cookie_dir, 0777, true);
        }

    }

    public function getCurl()
    {
        // TODO: Implement getCurl() method.
        try {
            $curl = new EpiiCurl();
        } catch (\ErrorException $e) {

        }
        $curl->IListener = $this;
        $curl->setHeader("Accept-Encoding", "gzip, deflate");
        $curl->setHeader("Accept-Language", "zh-CN,zh;q=0.9");
        $curl->setHeader("User-Agent", "Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/68.0.3440.106 Safari/537.36");
        $curl->setHeader("Connection", "keep-alive");

        $curl->setHeader("Accept", "text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8");

        return $curl;
    }


    public function whenDone(\Curl\Curl $curl)
    {
        // TODO: Implement whenDone() method.

        $n_cookie = $curl->getResponseCookies();
        $cookies = $curl->cookies;
        foreach ($n_cookie as $k => $v) {
            $cookies[$k] = $v;
        }
        if ($n_cookie)
            $this->setCookie($curl, $cookies);

    }

    private function setCookie(\Curl\Curl $curl, $cookies)
    {
        file_put_contents($this->getCookieFile($curl->url), json_encode($cookies));
    }

    public function beforExec(Curl $curl)
    {

        if (file_exists($file = $this->getCookieFile($curl->url))) {
            $curl->setCookies(json_decode(file_get_contents($file), true));
        }
    }

    private function getCookieFile($url)
    {
        $info = parse_url($url);
        $file = $this->cookie_file;
        if ($info) {
            if (isset($info["host"])) {
                $file .= "." . $info["host"];
            }
            if (isset($info["port"])) {
                $file .= "." . $info["port"];
            }
            $file .= ".txt";
        }
        return $file;
    }
}