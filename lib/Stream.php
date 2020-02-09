<?php

namespace w3lib\Library;

use Exception;
use w3lib\Library\Exception\StreamEmptyException;

use function w3lib\Library\xxd;

class Stream
{
    protected $handle;
    protected $endian;

    const ENDIAN_BE = 0x00;
    const ENDIAN_LE = 0x01;

    const NUL   = 0x00;
    const PEEK  = 0x01;
    const QUIET = 0x02;

    const ESCAPE = "\\";

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
        if (    is_string ($bytes)
            && !is_numeric ($bytes)) {
            $bytes = strlen ($bytes);
        }

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

            throw new StreamEmptyException (
                sprintf (
                    "Expecting stream size [%d] but found size [%d]",
                    $bytes,
                    $actual
                )
            );
        }

        if (! ($flags & self::QUIET) && Logger::isDebug ()) {
            // xxd ($block);
        }

        return $block;
    }

    public function readTo ($s)
    {
        while (
            !feof ($this->handle) &&
            !$this->startsWith ($s)
        ) {
            $this->read (1);
        }
    }

    public function readAll ()
    {
        $buffer = '';

        while (true) {
            try {
                $buffer .= $this->read (1);
            } catch (Exception $e) {
                break;
            }
        }

        return $buffer;
    }

    public function append ($data, $format = NULL)
    {
        if ($format) {
            $data = $this->pack ($format, $data);
        }

        $offset = $this->offset ();

        fseek ($this->handle, 0, SEEK_END);
        fwrite ($this->handle, $data);

        $this->seek ($offset);
    }

    public function prepend ($data, $format = NULL)
    {
        if ($format) {
            $data = $this->pack ($format, $data);
        }

        $offset = $this->offset ();

        $remaining = stream_get_contents ($this->handle);

        $this->seek ($offset);
        fwrite ($this->handle, $data . $remaining);
        $this->seek ($offset);
    }

    public function startsWith ($s)
    {
        if (strlen ($s) > strlen ('' . $this)) {
            return FALSE;
        }

        return strcmp (
            $this->read (
                strlen ($s),
                self::PEEK
            ),

            $s
        ) === 0;
    }

    public function eof ()
    {
        return feof ($this->handle);
    }

    public function fh ()
    {
        return $this->handle;
    }

    public function string ($term = self::NUL)
    {
        $s = '';
        $p = NULL;

        while (true) {
            try {
                $c = $this->read (1, self::QUIET);

                if (   ord ($c) === $term
                    && $p !== self::ESCAPE) {
                    break;
                }

                $s .= $c;
                $p  = $c;
            } catch (Exception $e) {
                if ($s === '') {
                    return FALSE;
                }

                break;
            }
        }

        return $s;
    }

    public function token ()
    {
        return $this->string (ord (' '));
    }

    public function char ($n = 1, $f = 0x00)
    {
        return $this->read ($n, $f);
    }

    public function bool ($f = 0x00)
    {
        return (bool) ord ($this->char (1, $f));
    }

    public function int8 ($f = 0x00)
    {
        return ord ($this->read (1, $f));
    }

    public function float ($f = 0x00)
    {
        return $this->unpack ('g', 'G', 4, $f);
    }

    public function double ($f = 0x00)
    {
        return $this->unpack ('e', 'E', 8, $f);
    }

    public function uint8 ($f = 0x00)
    {
        return $this->unpack ('c', 'c', 1, $f);
    }

    public function uint16 ($f = 0x00)
    {
        return $this->unpack ('v', 'n', 2, $f);
    }

    public function uint32 ($f = 0x00)
    {
        return $this->unpack ('V', 'N', 4, $f);
    }

    public function uint64 ($f = 0x00)
    {
        return $this->unpack ('P', 'J', 8, $f);
    }

    public function offset ()
    {
        return ftell ($this->handle);
    }

    public function seek ($offset)
    {
        rewind ($this->handle);

        if ($offset < 0) {
            $stat = fstat ($this->handle);
            $offset = $stat ['size'] - abs ($offset);
        }

        fseek ($this->handle, $offset);
    }

    protected function pack ($format, $data = NULL)
    {
        return $data ? pack ($format, $data) : pack ($format);
    }

    protected function unpack ($le, $be, $size, $f = 0x00)
    {
        $data = unpack ($this->endian ? $le : $be, $this->read ($size, $f));
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