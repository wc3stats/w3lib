<?php

namespace w3lib\Library;

use Exception;

class Stream
{
    protected $_handle;
    protected $_endian;

    private $_mark;

    const ENDIAN_BE = 0x00;
    const ENDIAN_LE = 0x01;

    const NUL  = 0x00;
    const PEEK = 0x01;

    public function __construct ($handle, $endian = self::ENDIAN_LE)
    {
        $this->_handle = $handle;
        $this->_endian = $endian;
    }

    public function read ($bytes, $flags = 0x00)
    {
        if ($flags & self::PEEK) {
            $this->_mark ();
        }

        if (($block = fread ($this->_handle, $bytes)) === FALSE) {
            throw new Exception ("Failed to read [$bytes] bytes from stream");
        }

        if ($flags & self::PEEK) {
            $this->_return ();
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

    public function eof ()
    {
        return feof ($this->_handle);
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

    public function bool ()
    {
        return (bool) ord ($this->char ());
    }

    public function byte ($flags = 0x00)
    {
        return ord ($this->read (1, $flags));
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

    protected function _mark ()
    {
        $this->_mark = ftell ($this->_handle);
    }

    protected function _return ()
    {
        if (!is_int ($this->_mark) || $this->_mark < 0) {
            throw new Exception ('Attempting to return to illegal mark.');
        }

        rewind ($this->_handle);
        fseek ($this->_handle, $this->_mark);
    }

    public function __toString ()
    {
        $buffer = '';
        
        $this->_mark ();

        while (!feof ($this->_handle)) {
            $buffer .= fread ($this->_handle, 0x2000);
        }

        $this->_return ();

        return $buffer;
    }
}

?>