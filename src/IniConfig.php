<?php
/**
 * Created by PhpStorm.
 * User: mrren
 * Date: 2018/8/21
 * Time: 下午4:46
 */

namespace epii\curl;

class IniConfig
{
    private static $initdata = null;

    public static function init($file)
    {
        self::$initdata = parse_ini_file($file, true);
    }

    public static function get(...$args)
    {
        $out = self::$initdata;
        foreach ($args as $value) {
            $out = isset($out[$value]) ? $out[$value] : null;
        }
        return $out;
    }
}