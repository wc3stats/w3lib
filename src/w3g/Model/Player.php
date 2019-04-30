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
    public $race      = NULL;

    // Deferred.

    public $isHost    = false;
    public $slot      = NULL;
    public $colour    = NULL;
    public $handicap  = NULL;
    public $leftAt    = NULL;
    public $isWinner  = NULL;
    public $team      = NULL;
    public $actions   = NULL;
    public $activity  = [];
    public $apm       = 0;
    public $variables = NULL;

    public function read (Stream $stream, $context = NULL)
    {
        $this->type = $stream->uint8 ();
        $this->id   = $stream->uint8 ();
        $this->name = $stream->string ();
        
        $platform = $stream->uint8 ();

        switch ($platform) {
            case Lang::CUSTOM:
                // Null byte
                $stream->read (1); 
            break;

            case Lang::LADDER:
                // Warcraft III runtime of exe in milliseconds.
                $stream->uint32 ();
                $stream->uint32 ();
            break;

            case Lang::NETEASE:
                $stream->read (2);
            break;
        }

        if ($context->settings->keepActions) {
            $this->actions = [];
        }
    }

    public function __sleep ()
    {
        $keys = array_keys ((array) $this);

        // Omit actions, there are too many to reasonably serialize.
        $keys = array_diff ($keys, [ 'actions' ]);

        return $keys;
    }
}

?>