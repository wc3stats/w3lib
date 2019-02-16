<?php

namespace w3lib;

use Exception;

abstract class Type
{
    const LITTLE_ENDIAN = 0x00;
    const BIG_ENDIAN    = 0x01;

    private static $_endian = Types::LITTLE_ENDIAN;
    
    public $code;
    public $size;

    public function __construct ($code, $size)
    {
        $this->code = $code;
        $this->size = $size;
    }

    /** **/

    public static function endian ($endian)
    {
        if (   $endian !== Types::LITTLE_ENDIAN
            && $endian !== Types::BIG_ENDIAN) {
            throw new Exception ("Unknown endianness: $endian");
        }

        self::$_endian = $endian;
    }

    public static function string ($length = NULL)
    {
        return new Type ('a', $length);
    }

    public static function char ($length = 1)
    {
        return new Type ('b', $length);
    }

    public static function uint8 ()
    {
        return new Type ('C', 1);
    }

    public static function uint16 ()
    {
        return new Type ([ 'v', 'n' ] [self::$_endian], 2);
    }

    public static function uint32 ()
    {
        return new Type ([ 'V', 'N' ] [self::$_endian ], 4 );
    }
}

?>