<?php

namespace w3lib\Library;

use Exception;

class Stream
{
    protected $_handle;
    protected $_endian;

    const ENDIAN_BE = 0x00;
    const ENDIAN_LE = 0x01;

    const NUL  = 0x00;
    const PEEK = 0x01;

    public function __construct ($handle, $endian = self::ENDIAN_LE)
    {
        $this->_handle = $handle;
        $this->_endian = $endian;

        flock ($this->_handle, LOCK_EX);
    }

    public function __destruct ()
    {
        if (!is_resource ($this->_handle)) {
            return;
        }
        
        flock  ($this->_handle, LOCK_UN);
        fclose ($this->_handle);
    }

    public function read ($bytes, $flags = 0x00)
    {
        if ($bytes <= 0) {
            return '';
        }

        $offset = $this->offset ();

        if (($block = fread ($this->_handle, $bytes)) === FALSE) {
            throw new Exception ("Failed to read [$bytes] bytes from stream");
        }

        if ($flags & self::PEEK) {
            $this->seek ($offset);
        }

        if (($actual = mb_strlen ($block, '8bit')) != $bytes) { 
            $this->seek ($offset);

            throw new Exception (
                sprintf (
                    'Expecting stream size [%d] but found size [%d]',
                    $bytes,
                    $actual
                )
            );
        }

        return $block;
    }

    public function append ($s)
    {
        $offset = $this->offset ();

        fseek ($this->_handle, 0, SEEK_END);
        fwrite ($this->_handle, $s);
        
        $this->seek ($offset);
    }

    public function prepend ($s)
    {
        $offset = $this->offset ();

        fseek ($this->_handle, 0);
        fwrite ($this->_handle, $s);

        $this->seek ($offset);
    }

    public function eof ()
    {
        return feof ($this->_handle);
    }

    public function string ($term = self::NUL)
    {
        $s = '';

        while (($c = $this->read (1)) !== FALSE) {
            if (ord ($c) == $term) {
                break;
            }

            $s .= $c;
        }

        return $s;
    }

    public function token ()
    {
        return $this->string (ord (' '));
    }

    public function char ($n = 1)
    {
        return $this->read ($n);
    }

    public function bool ()
    {
        return (bool) ord ($this->char ());
    }

    public function int8 ($flags = 0x00)
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

    public function offset ()
    {
        return ftell ($this->_handle);
    }

    public function seek ($offset)
    {
        rewind ($this->_handle);
        fseek ($this->_handle, $offset);
    }

    protected function _unpack ($le, $be, $size)
    {
        $data = unpack ($this->_endian ? $le : $be, $this->read ($size));
        return current ($data);
    }

    public function __toString ()
    {
        $buffer = '';
        
        $offset = $this->offset ();

        while (!feof ($this->_handle)) {
            $buffer .= fread ($this->_handle, 0x2000);
        }

        $this->seek ($offset);

        return $buffer;
    }
}

?>