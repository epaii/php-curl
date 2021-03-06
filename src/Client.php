<?php
/**
 * Created by PhpStorm.
 * User: mrren
 * Date: 2018/8/20
 * Time: 下午2:53
 */

namespace epii\curl;


use Curl\Curl;
use epii\curl\run\IGetData;
use epii\curl\run\IListener;
use epii\curl\run\IRun;
use epii\curl\run\IRunDone;
use htmldom\simple_html_dom;

class Client
{


    private $can_next = true;

    private $debug = false;
    private $debug_dir = "./log";
    private $last_response;

    private $runing_data = array();

    private $_setRuningDataLocalCache_tag = false;
    private $_setRuningDataLocalCache_file = false;
    /**
     * @var IListener
     */
    private $curlGlobal = null;

    public function getListener(): IListener
    {
        return $this->curlGlobal;
    }

    /**
     * @var Client
     */
    private static $client;

    public function __construct()
    {
        self::$client = $this;
    }

    public static function getWorkingClient(): Client
    {
        return self::$client;
    }


    public function saveRuingData($file)
    {
        file_put_contents($file, json_encode($this->runing_data));
        return $this;
    }

    public function setRuingCacheDataFile($file)
    {
        $this->_setRuningDataLocalCache_file = $file;

        $dir = pathinfo($this->_setRuningDataLocalCache_file, PATHINFO_DIRNAME);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        if (is_file($this->_setRuningDataLocalCache_file)) {
            $r_data = json_decode(file_get_contents($this->_setRuningDataLocalCache_file), true);
            if ($r_data) {
                $this->setRuningData($r_data);
            }
        }


        return $this;
    }

    public function setRuningData($key, $value = null)
    {
        if (is_array($key)) {

            foreach ($key as $k => $v) {
                $this->runing_data[$k] = $v;

            }
        } else {


            $this->runing_data[$key . ""] = $value;
        }

        return $this;
    }

    public function editRuningData(string $name, callable $fun)
    {
        $this->setRuningData($name, $fun($this->getRuningData($name)));
    }


    public function setRuningDataLocalCache(string $key, $value)
    {
        $this->setRuningData($key, $value);
        if ($this->_setRuningDataLocalCache_tag === false) {
            $this->_setRuningDataLocalCache_tag = [];
        }
        $this->_setRuningDataLocalCache_tag[$key] = $value;
        return $this;
    }

    public function __destruct()
    {
        // TODO: Implement __destruct() method.
        if ($this->_setRuningDataLocalCache_tag !== false) {
            if ($this->_setRuningDataLocalCache_file) {
                file_put_contents($this->_setRuningDataLocalCache_file, json_encode($this->_setRuningDataLocalCache_tag, JSON_UNESCAPED_UNICODE));
            }
        }

    }

    public function getRuningData($key)
    {
        return isset($this->runing_data[$key]) ? $this->runing_data[$key] : "";
    }


    public function setCurlListener(IListener $set)
    {
        $this->curlGlobal = $set;
        return $this;
    }

    public function setDebug($debug = true, $debug_dir = null)
    {
        $this->debug = $debug;
        if ($debug_dir) {
            if (!is_dir($debug_dir)) {
                mkdir($debug_dir, 0777, true);
            }
            $this->debug_dir = $debug_dir;
        }
        return $this;
    }

    public function getLastResponse()
    {
        return $this->last_response;
    }

    public function doRunNext($run, Array $args = [], IGetData $callback = null)
    {


        return $this;
    }


    public function doGet($url, IGetData $callback = null)
    {
        return $this->doRun(new class($url) implements IRun
        {
            private $url;

            public function __construct($url)
            {
                $this->url = $url;
            }

            public function run(\Curl\Curl $curl, Array $args, Client $client)
            {
                // TODO: Implement run() method.
                $curl->get($this->url);
            }

            public function check(Array $args, Client $client)
            {
                // TODO: Implement check() method.
            }
        }, [], $callback);

    }

    public function doPost($url, $args = [], IGetData $callback = null)
    {
        return $this->doRun(new class($url, $args) implements IRun
        {
            private $url;
            private $data;

            public function __construct($url, $data)
            {
                $this->url = $url;
                $this->data = $data;
            }

            public function run(\Curl\Curl $curl, Array $args, Client $client)
            {
                // TODO: Implement run() method.
                $curl->post($this->url, $this->data);
            }

            public function check(Array $args, Client $client)
            {
                // TODO: Implement check() method.
            }
        }, [], $callback);
    }

    public function doPostJson($url,   $args)
    {
        return $this->doWidthCurl(function (EpiiCurl $curl) use ($url, $args) {
            $curl->setHeader("Content-Type", "application/json;charset=UTF-8");
            $curl->setHeader("Accept", "application/json, text/plain, */*");
            $curl->post($url, $args);
            $this->last_response = $curl->response;
            return  $this->last_response;

        });
    }
    /**
     * @return Curl
     */
    public function getNewCurl()
    {
        return $this->curlGlobal->getCurl();
    }


    public function doWidthCurl(callable $function)
    {
        $curl = $this->curlGlobal->getCurl();
        $out = $function($curl);
        $this->log($curl);
        $this->curlGlobal->whenDone($curl);
        return $out;
    }

    public function doRun($run, Array $args = [], IGetData $callback = null)
    {


        if (!$this->can_next) return false;
        if (is_string($run)) {
            if (class_exists($run)) {
                $run = new $run();
                if (!($run instanceof IRun)) {
                    return false;
                }
            }
        }

        $out = $run->check($args, $this);
        if ($out === false) {

            if ($callback) {
                $callback->callback("", null, $this);
            }
            return $this;
        } else {

            $curl = $this->curlGlobal->getCurl();


            $out = $run->run($curl, $args, $this);
            if ($out === false) {
                $this->can_next = false;
            }
            $this->last_response = $curl->response;
            $this->log($curl);
            $this->curlGlobal->whenDone($curl);


            $info = $curl->getInfo();
            if (isset($info["redirect_url"]) && $info["redirect_url"]) {

                $this->doGet($info["redirect_url"]);
                return $this;
            }

            if ($run instanceof IRunDone) {
                $run->done($curl, $args, $this);
            }
            if ($callback) {
                $callback->callback($curl->response, $curl, $this);
            }
        }


        return $this;
    }

    public function submitFormInHtmlGetPostData(String $htmlString, $tags = ["input"], $tag = "form")
    {
        $html = new simple_html_dom();
        $html->load($htmlString);
        //var_export($html->find("form")->plaintext);

        foreach ($html->find($tag) as $element) {
            $action = $element->action;

            $me = strtolower($element->method);

            if (!$me) $me = "post";
            $data = [];

            foreach ($tags as $tag) {

                foreach ($element->find($tag) as $item) {

                    if ($tag == "select") {
                        if ($dom_tag = $item->find('option[selected]', 0)) {
                            $data[$item->name] = $dom_tag->value;
                        } else if ($dom_tag = $item->firstChild()) {
                            //   var_dump($item->name);
                            $data[$item->name] = $dom_tag->value;
                        } else {
                            $data[$item->name] = $item->value;
                        }
                    } else if ($tag == "textarea") {

                        $data[$item->name] = $item->innertext;
                    } else
                        $data[$item->name] = $item->value;

                }
            }

            return [$action, $data];

        }


        return $this;
    }

    public function submitFormInHtml(String $htmlString, IGetData $callback = null, $ac_pre = "")
    {
        $html = new simple_html_dom();
        $html->load($htmlString);
        //var_export($html->find("form")->plaintext);

        foreach ($html->find('form') as $element) {
            $action = $ac_pre . $element->action;

            $me = strtolower($element->method);

            if (!$me) $me = "post";
            $data = [];
            foreach ($html->find('input') as $item) {

                $data[$item->name] = $item->value;

            }
            $this->logmsg("submitFormInHtml:" . $action);
            $this->logmsg("submitFormInHtml_data " . $me);
            $this->logmsg("submitFormInHtml_method " . var_export($data, true));
            if ($me == "post") {
                $this->doPost($action, $data, $callback);
            } else {
                $this->doGet($action, $callback);
            }

        }


        return $this;
    }

    private function log(Curl $curl)
    {
        if ($this->debug) {
            $logmsg = "\n------------------------------------\n";
            $logmsg .= $curl->url . "\n";
            $logmsg .= var_export($curl->getInfo(), true) . "\n";
            $logmsg .= var_export($curl->getResponseCookies(), true) . "\n";
            file_put_contents($this->debug_dir . "/" . date("YmdH") . ".txt.txt", $logmsg . "\n", FILE_APPEND);
            $logmsg .= $curl->response . "\n";
            file_put_contents($this->debug_dir . "/" . date("YmdH") . ".txt", $logmsg, FILE_APPEND);
        }
    }

    public function logmsg($msg)
    {
        if ($this->debug) {
            $logmsg = "\n----------------msg--------------------\n";

            $logmsg .= $msg . "\n";
            file_put_contents($this->debug_dir . "/" . date("YmdH") . ".txt", $logmsg, FILE_APPEND);
        }
    }

}