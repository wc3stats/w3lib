<?php

namespace w3lib\w3g\Model;

use w3lib\Library\Model;
use w3lib\Library\Stream;

class Slot extends Model
{
    /* $slot->status */
    const EMPTY  = 0x00;
    const CLOSED = 0x01;
    const USED   = 0x02;

    /* $slot->isComputer */
    const HUMAN    = 0x00;
    const COMPUTER = 0x01;

    /* $slot->race */
    const RACE_HUMAN    = 0x01;
    const RACE_ORC      = 0x02;
    const RACE_NIGHTELF = 0x04;
    const RACE_UNDEAD   = 0x08;
    const RACE_RANDOM   = 0x20;

    /* slot->aiStrength */
    const AI_EASY   = 0x01;
    const AI_NORMAL = 0x02;
    const AI_INSANE = 0x04;

    public $playerId;
    public $status;
    public $isComputer;
    public $team;
    public $colour;
    public $race;
    public $aiStrength;
    public $handicap;

    public function read (Stream $stream)
    {
        $this->playerId = $stream->int8 ();

        /* Map download percent (0x64 in custom, 0xFF in ladder) */
        $stream->int8 ();

        $this->status     = $stream->int8 ();
        $this->isComputer = $stream->bool ();
        $this->team       = $stream->int8 ();
        $this->colour     = $stream->int8 ();
        $this->race       = $stream->int8 ();
        $this->aiStrength = $stream->int8 ();
        $this->handicap   = $stream->int8 ();
    }
}

?>