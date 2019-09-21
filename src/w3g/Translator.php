<?php

namespace w3lib\w3g;

use w3lib\Library\Stream;

abstract class Translator
{
    public abstract function understands (Stream $stream);
    public abstract function translate (Stream $stream);
}

?>