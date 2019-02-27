<?php

namespace w3lib\w3g\Model;

use Exception;
use w3lib\Library\Model;
use w3lib\Library\Stream;

class Action extends Model
{
    public function read (Stream $stream)
    {
        $this->id = $stream->char ();

        switch ($this->id) {

        }
    }
}

?>