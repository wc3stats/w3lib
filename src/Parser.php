<?php

namespace w3lib;

use Exception;

class Parser
{
    const NUL = 0x00;

    private $_archive;

    public function __construct (Archive $archive)
    {
        $this->_archive = $archive;
    }

    protected function unpack ($size, array $format, $block = NULL)
    {
        $data  = [];

        if (!$block) {
            $block = $this->_archive->read ($size);
        }

        if (($actual = mb_strlen ($block, '8bit')) !== $size) {
            throw new Exception (
                sprintf (
                    "Expecting block size [%d] but found size [%d]",
                    $size,
                    $actual
                )
            );
        }

        foreach ($format as $k => $x) {
            $code = $x [0];
            $size = $x [1];
            
            debug (
                sprintf (
                    "Reading key [%s] of type [%s] and length [%d]",
                    $k,
                    $code,
                    $size
                )
            );
            
            $this->xxd ($block);

            switch ($code) {
                case 'a':
                    if (($size = strpos ($block, Parser::NUL)) === FALSE) {
                        throw new Exception ('Null-terminated string not found in block.');
                    }

                    $code .= $size++;
                break;

                case 'b':
                    $code = "a$size";
                break;
            }

            $segment = unpack  ($code, $block);
            $segment = implode ('', $segment);

            $data [$k] = $segment;

            $block = substr ($block, $size);

            debug (
                sprintf (
                    "Got segment: [%s]",
                    $segment
                )
            );

            print (PHP_EOL);
        }

        return $data;
    }

    protected function xxd ($block, $width = 16)
    {
        $from = '';
        $to   = '';

        $offset = 0;

        if (!$from) {
            for ($i = 0; $i <= 0xFF; $i++) {
                $from .= chr ($i);
                $to   .= ($i >= 0x20 && $i <= 0x7E) ? chr ($i) : '.';
            }
        }

        $hex   = str_split (bin2hex ($block), $width * 2);
        $chars = str_split (strtr ($block, $from, $to), $width);

        foreach ($hex as $i => $line) {
            debug (
                sprintf (
                    '%6X : %-s [%-s]',
                    $offset,
                    str_pad (implode (' ', str_split ($line, 2)), $width * 3 - 1),
                    str_pad ($chars [$i], $width)
                )
            );

            $offset += $width;
        }
    }
}

?>