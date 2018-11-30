<?php
namespace epii\curl\run;
use epii\curl\Client;

/**
 * Created by PhpStorm.
 * User: mrren
 * Date: 2018/8/20
 * Time: 下午3:02
 */
interface IGetData
{
    public function callback(String $response, \Curl\Curl $curl, Client $client);
}