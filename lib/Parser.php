<?php

namespace w3lib\Library;

use w3lib\Library\Types\char;
use w3lib\Library\Types\float;
use w3lib\Library\Types\int;

class Parser
{
    private $_primitives = [
        'uchar',
        'char',
        'uint8',
        'uint16',
        'uint32',
        'uint64',
        'int8',
        'int16',
        'int32',
        'int64',
        'float',
        'double'
    ];

    private $_structs = [
        'string',
        'array',
        'bit',
        'buffer'
    ];

    private $_types = [];

    public function __construct ()
    {

    }

    public function __call ($name, array $arguments)
    {
        var_dump ($name);
        var_dump ($arguments);
    }
}

?>