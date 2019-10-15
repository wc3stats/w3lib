<?php

namespace w3lib\w3g;

class Context
{
    public static $settings;
    public static $replay;
    public static $time;

    public static function majorVersion ()
    {
        return self::$replay->header->majorVersion;
    }

    public static function getTime ()
    {
        return floor (self::$time);
    }
}

?>