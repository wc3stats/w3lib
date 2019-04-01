<?php

namespace w3lib\w3g\Model;

use stdClass;
use w3lib\Library\Logger;
use w3lib\Library\Model;
use w3lib\Library\Stream;
use w3lib\w3g\Lang;

class Player extends Model
{
    public $type      = NULL;
    public $id        = NULL;
    public $name      = NULL;
    public $platform  = NULL;
    public $runtime   = NULL;
    public $race      = NULL;

    /* Deferred */

    public $isHost    = NULL;
    public $slot      = NULL;
    public $colour    = NULL;
    public $handicap  = NULL;
    public $leftAt    = NULL;
    public $isWinner  = NULL;
    public $team      = NULL;
    public $score     = NULL;
    public $actions   = [];
    public $activity  = [];
    public $variables = NULL;

    public function read (Stream $stream, $context = NULL)
    {
        $this->type     = $stream->uint8 ();
        $this->id       = $stream->uint8 ();
        $this->name     = $stream->string ();
        $this->platform = $stream->uint8 ();

        switch ($this->platform) {
            case Lang::CUSTOM:
                // Null byte
                $stream->read (1); 
            break;

            case Lang::LADDER:
                $this->runtime = $stream->uint32 ();
                $this->race    = $stream->uint32 ();
            break;

            case Lang::NETEASE:
                $stream->read (2);
            break;
        }

        $this->isHost    = false;
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