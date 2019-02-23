<?php

namespace w3lib\Library;

use Exception;

class Stream
{
    protected $_handle;
    protected $_endian;

    const ENDIAN_BE = 0x00;
    const ENDIAN_LE = 0x01;

    const NUL = 0x00;

    public function __construct ($handle, $endian = self::ENDIAN_LE)
    {
        $this->_handle = $handle;
        $this->_endian = $endian;
    }

    public function read ($bytes)
    {
        if (($block = fread ($this->_handle, $bytes)) === FALSE) {
            throw new Exception ("Failed to read [$bytes] bytes from stream");
        }

        if (($actual = mb_strlen ($block, '8bit')) != $bytes) {
            throw new Exception (
                sprintf (
                    'Expecting segment size [%d] but found size [%d]',
                    $bytes,
                    $actual
                )
            );
        }

        return $block;
    }

    public function string ()
    {
        $s = '';

        while (($c = $this->read (1)) !== FALSE) {
            if (ord ($c) == self::NUL) {
                break;
            }

            $s .= $c;
        }

        return $s;
    }

    public function char ($n = 1)
    {
        return $this->read ($n);
    }

    public function float ()
    {
        return $this->_unpack ('g', 'G', 4);
    }

    public function double ()
    {
        return $this->_unpack ('e', 'E', 8);
    }

    public function uint8 ()
    {
        return $this->_unpack ('c', 'c', 1);
    }

    public function uint16 ()
    {
        return $this->_unpack ('v', 'n', 2);
    }

    public function uint32 ()
    {
        return $this->_unpack ('V', 'N', 4);
    }

    public function uint64 ()
    {
        return $this->_unpack ('P', 'J', 8);
    }

    protected function _unpack ($le, $be, $size)
    {
        $data = unpack ($this->_endian ? $le : $be, $this->read ($size));
        return current ($data);
    }
}

?>