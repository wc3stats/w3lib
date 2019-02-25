<?php

namespace w3lib\Library\Stream;

use w3lib\Library\Stream;

class Buffer extends Stream
{
    public function __construct (string $s = '')
    {
        $stream = fopen ('php://memory', 'r+');
    
        fwrite ($stream, $s);
        rewind ($stream);

        parent::__construct ($stream);
    }

    public function append ($s)
    {
        $this->_mark ();

        fseek ($this->_handle, 0, SEEK_END);
        fwrite ($this->_handle, $s);
        
        $this->_return ();
    }
}

?>