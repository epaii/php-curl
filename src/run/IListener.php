<?php
/**
 * Created by PhpStorm.
 * User: mrren
 * Date: 2018/8/20
 * Time: 下午6:02
 */

namespace epii\curl\run;


use Curl\Curl;

interface IListener
{
    public function getCurl();
    public function whenDone(Curl $curl);
    public function beforExec(Curl $curl);
}