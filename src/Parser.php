<?php

namespace w3lib;

use Exception;
use w3lib\Library\Stream;

abstract class Parser
{
    protected $_stream;

    public function __construct (Stream $stream)
    {
        // if (is_string ($stream)) {
        //     $stream = fopen ('php://memory', 'r+');
    
        //     fwrite ($stream, $string);
        //     rewind ($stream);
        // }

        // if (!is_resource ($stream)) {
        //     throw new Exception ("Parser expects a stream resource.");
        // }

        // $this->_stream = $stream;
    }

    // public function read ($bytes)
    // {
    //     if (($block = fread ($this->_stream, $bytes)) === FALSE) {
    //         throw new Exception ("Failed to read [$bytes] bytes from stream");
    //     }

    //     if (($actual = mb_strlen ($block, '8bit')) != $bytes) {
    //         throw new Exception (
    //             sprintf (
    //                 'Expecting segment size [%d] but found size [%d]',
    //                 $bytes,
    //                 $actual
    //             )
    //         );
    //     }

    //     return $block;
    // }
}

?>