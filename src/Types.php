<?php

namespace w3lib;

use Exception;

abstract class Types
{
    const LITTLE_ENDIAN = 0x00;
    const BIG_ENDIAN    = 0x01;

    private static $_endian = Types::LITTLE_ENDIAN;

    public static function endian ($endian)
    {
        if (   $endian !== Types::LITTLE_ENDIAN
            && $endian !== Types::BIG_ENDIAN) {
            throw new Exception ("Unknown endianness: $endian");
        }

        self::$_endian = $endian;
    }

    public static function string ()
    {
        return [ 'a', NULL ];
    }

    public static function char ($length = 1)
    {
        return [ 'b', $length ];
    }

    public static function uint16 ()
    {
        return [ [ 'v', 'n' ] [self::$_endian], 2 ];
    }

    public static function uint32 ()
    {
        return [ [ 'V', 'N' ] [self::$_endian ], 4 ];
    }
}

?>