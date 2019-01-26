<?php
/**
 * Created by PhpStorm.
 * User: mrren
 * Date: 2019/1/26
 * Time: 4:27 PM
 */

namespace epii\curl;


use Curl\Curl;
use epii\curl\run\IListener;

class EpiiCurl extends Curl
{

    public $IListener = null;


    public function exec($ch = null)
    {
        if ($this->IListener !== null) {
            if ($this->IListener instanceof IListener) {
                $this->IListener->beforExec($this);
            }
        }
        return parent::exec($ch);

    }


}