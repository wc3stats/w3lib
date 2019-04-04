<?php

namespace w3lib\Library;

use Exception;

class Stream
{
    protected $handle;
    protected $endian;

    const ENDIAN_BE = 0x00;
    const ENDIAN_LE = 0x01;

    const NUL   = 0x00;
    const PEEK  = 0x01;
    const QUIET = 0x02;

    public function __construct ($handle, $endian = self::ENDIAN_LE)
    {
        $this->handle = $handle;
        $this->endian = $endian;

        flock ($this->handle, LOCK_EX);
    }

    public function __destruct ()
    {
        if (!is_resource ($this->handle)) {
            return;
        }
        
        flock  ($this->handle, LOCK_UN);
        fclose ($this->handle);
    }

    public function read ($bytes, $flags = 0x00)
    {
        if ($bytes <= 0) {
            return '';
        }

        $offset = $this->offset ();

        if (($block = fread ($this->handle, $bytes)) === FALSE) {
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

        if (! ($flags & self::QUIET) && Logger::isDebug ()) {
            xxd ($block);
        }

        return $block;
    }

    public function append ($data, $format = NULL)
    {
        if ($format) {
            $data = pack ($format, $data);
        }

        $offset = $this->offset ();

        fseek ($this->handle, 0, SEEK_END);
        fwrite ($this->handle, $data);
        
        $this->seek ($offset);
    }

    public function prepend ($data, $format = NULL)
    {
        if ($format) {
            $data = pack ($format, $data);
        }

        $offset = $this->offset ();

        $remaining = stream_get_contents ($this->handle);

        $this->seek ($offset);
        fwrite ($this->handle, $data . $remaining);
        $this->seek ($offset);
    }

    public function eof ()
    {
        return feof ($this->handle);
    }

    public function string ($term = self::NUL)
    {
        $s = '';

        while (($c = $this->read (1, self::QUIET)) !== FALSE) {
            if (ord ($c) == $term) {
                break;
            }

            $s .= $c;
        }

        xxd ($s);

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
        return $this->unpack ('g', 'G', 4);
    }

    public function double ()
    {
        return $this->unpack ('e', 'E', 8);
    }

    public function uint8 ()
    {
        return $this->unpack ('c', 'c', 1);
    }

    public function uint16 ()
    {
        return $this->unpack ('v', 'n', 2);
    }

    public function uint32 ()
    {
        return $this->unpack ('V', 'N', 4);
    }

    public function uint64 ()
    {
        return $this->unpack ('P', 'J', 8);
    }

    public function offset ()
    {
        return ftell ($this->handle);
    }

    public function seek ($offset)
    {
        rewind ($this->handle);
        fseek ($this->handle, $offset);
    }

    protected function unpack ($le, $be, $size)
    {
        $data = unpack ($this->endian ? $le : $be, $this->read ($size));
        return current ($data);
    }

    public function __toString ()
    {
        $buffer = '';
        
        $offset = $this->offset ();

        while (!feof ($this->handle)) {
            $buffer .= fread ($this->handle, 0x2000);
        }

        $this->seek ($offset);

        return $buffer;
    }
}

?>