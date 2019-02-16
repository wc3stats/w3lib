<?php

namespace w3lib\Library\Stream;

use w3lib\Library\Stream;

class String extends Stream
{
    public function __construct (string $s)
    {
        $stream = fopen ('php://memory', 'r+');
    
        fwrite ($stream, $s);
        rewind ($stream);

        parent::__construct ($stream);
    }
}

?>