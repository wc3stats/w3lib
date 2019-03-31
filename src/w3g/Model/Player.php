<?php

namespace w3lib\w3g\Model;

use stdClass;
use w3lib\Library\Logger;
use w3lib\Library\Model;
use w3lib\Library\Stream;

class Player extends Model
{
    const HOST    = 0x00;
    const PLAYER  = 0x16;
    const CUSTOM  = 0x01;
    const NETEASE = 0x02;
    const LADDER  = 0x08;

    const HUMAN    = 0x01;
    const ORC      = 0x02;
    const NIGHTELF = 0x04;
    const UNDEAD   = 0x08;
    const DAEMON   = 0x10;
    const RANDOM   = 0x20;
    const FIXED    = 0x40;

    const RED       = 0x00;
    const BLUE      = 0x01;
    const TEAL      = 0x02;
    const PURPLE    = 0x03;
    const YELLOW    = 0x04;
    const ORANGE    = 0x05;
    const GREEN     = 0x06;
    const PINK      = 0x07;
    const GREY      = 0x08;
    const LIGHTBLUE = 0x09;
    const DARKGREEN = 0x0A;
    const BROWN     = 0x0B;
    const MAROON    = 0x0C;
    const NAVY      = 0x0D;
    const TURQUOISE = 0x0F;
    const VIOLET    = 0x10;
    const WHEAT     = 0x11;
    const PEACH     = 0x12;
    const MINT      = 0x13;
    const LAVENDER  = 0x14;
    const COAL      = 0x15;
    const SNOW      = 0x16;
    const EMERALD   = 0x17;
    const PEANUT    = 0x18;

    // private const STATE_SELECT   = 0x01;
    // private const STATE_DESELECT = 0x02;

    // private $state = 0x00;

    public $type;
    public $id;
    public $name;
    public $addon;
    public $runtime;
    public $race;

    /* Deferred */

    public $colour;
    public $handicap;
    public $leftAt;
    public $isWinner;
    public $team;
    public $score;
    public $actions;
    public $activity;
    public $variables;

    public function read (Stream $stream)
    {
        $this->type  = $stream->uint8 ();
        $this->id    = $stream->uint8 ();
        $this->name  = $stream->string ();
        $this->addon = $stream->uint8 ();

        switch ($this->addon) {
            case self::CUSTOM:
                // Null byte
                $stream->read (1); 
            break;

            case self::LADDER:
                $this->runtime = $stream->uint32 ();
                $this->race    = $stream->uint32 ();
            break;

            case self::NETEASE:
                $stream->read (2);
            break;
        }

        $this->colour    = NULL;
        $this->handicap  = NULL;
        $this->leftAt    = NULL;
        $this->isWinner  = NULL;
        $this->team      = NULL;
        $this->score     = NULL;
        $this->actions   = [];
        $this->activity  = [];
        $this->variables = [];
    }

    public function apm ()
    {
        if (empty ($this->activity)) {
            return 0;
        }

        return array_sum ($this->activity) / count ($this->activity);
    }

    public function __sleep ()
    {   
        /* Refresh APM before serializing. */
        $this->apm = $this->apm ();

        $keys = array_keys ((array) $this);

        /* Omit actions, there are too many to reasonably serialize. */
        $keys = array_diff ($keys, [ 'actions']);

        return $keys;
    }

    public function __wakeup ()
    {
        $this->actions = [];
    }
}

?>