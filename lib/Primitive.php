<?php

namespace w3lib\Library;

abstract class Primitive
{
    const T_U   = 0x01;
    const T_S   = 0x02;
    const T_BE  = 0x04;
    const T_LE  = 0x08;
    const T_S8  = 0x10;
    const T_S16 = 0x20;
    const T_S32 = 0x40;
    const T_S64 = 0x80;

    protected $_flags;

    public function __construct ($flags = 0x00)
    {
        if (!$flags) {
            $flags = self::T_S & self::T_LE;
        }

        $this->_flags = $flags;
    }
}

?>