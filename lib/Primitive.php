<?php

namespace w3lib\Library;

abstract class Primitive extends Type
{
    const T_U    = 0x01;
    const T_S    = 0x02;

    const T_S8   = 0x08;
    const T_S16  = 0x10;
    const T_S32  = 0x20;
    const T_S64  = 0x40;

    const T_BE   = 0x100;
    const T_LE   = 0x200;

    const M_SIGN = 0x3;
    const M_SIZE = 0x78;
    const M_ORD  = 0x300;

    protected $_flags;
    protected $_codes;

    public function __construct ($flags = 0x00)
    {
        if (!$flags) {
            $flags = self::T_S & self::T_LE;
        }

        $this->_size = ($flags & self::M_SIZE) / 8;

        if ($flags & Primitive::T_S8) {
            $this->_size = 1;
        }

        $this->_flags = $flags;
    }

    public function getCode ()
    {
        return $this->_codes 
            [$this->_flags & self::M_SIGN]
            [$this->_flags & self::M_ORD]
            [$this->_flags & self::M_SIZE] ?? NULL;
    }

    public function read (Stream $stream)
    {
        $block = $stream->read ($this->_size);
        $block = unpack ($this->getCode (), $block);

        return current ($block);
    }
}

?>