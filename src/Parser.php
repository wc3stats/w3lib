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
}

?>