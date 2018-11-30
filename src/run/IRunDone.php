<?php
namespace epii\curl\run;
use epii\curl\Client;


/**
 * Created by PhpStorm.
 * User: mrren
 * Date: 2018/8/20
 * Time: 下午2:54
 */
interface IRunDone
{
    public function done(\Curl\Curl $curl,Array $args,Client $client);

}