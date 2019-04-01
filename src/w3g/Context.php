<?php

namespace w3lib\w3g;

class Context
{
    public $settings;
    public $replay;
    public $time;

    public function getTime ()
    {
        return floor ($this->time);
    }
}

?>