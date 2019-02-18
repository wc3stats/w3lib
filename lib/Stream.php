<?php

namespace w3lib\Library;

use Exception;

class Stream
{
    protected $_handle;

    public function __construct ($handle)
    {
        $this->_handle = $handle;
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
}

?>