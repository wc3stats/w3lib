<?php

namespace w3lib\w3g;

use w3lib\Archive;

class Replay extends Archive
{
    public $header;
    public $game;
    public $players;
    public $chatlog;

    public function __construct (string $filepath)
    {
        parent::__construct ($filepath);

        $parser = new Parser ($this->_fh);
        $parser->parse ();
    }
}

?>