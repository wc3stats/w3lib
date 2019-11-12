<?php

namespace w3lib\w3g\Model;

use w3lib\Library\Model;
use w3lib\Library\Stream;
use w3lib\w3g\Lang;

class Slot extends Model
{
    public $playerId   = NULL;
    public $status     = NULL;
    public $isComputer = NULL;
    public $team       = NULL;
    public $colour     = NULL;
    public $race       = NULL;
    public $aiStrength = NULL;
    public $handicap   = NULL;
    public $isObserver = NULL;

    public function read (Stream &$stream)
    {
        $this->playerId = $stream->int8 ();

        // Map download percent (0x64 in custom, 0xFF in ladder)
        $stream->int8 ();

        $this->status     = $stream->int8 ();
        $this->isComputer = $stream->bool ();
        $this->team       = $stream->int8 ();
        $this->colour     = $stream->int8 ();
        $this->race       = $stream->int8 ();
        $this->aiStrength = $stream->int8 ();
        $this->handicap   = $stream->int8 ();
        $this->isObserver = $this->team === Lang::SLOT_OBSERVER;
    }
}

?>