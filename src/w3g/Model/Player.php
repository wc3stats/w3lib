<?php

namespace w3lib\w3g\Model;

use stdClass;
use w3lib\Library\Logger;
use w3lib\Library\Model;
use w3lib\Library\Stream;
use w3lib\w3g\Lang;
use w3lib\w3g\Context;

class Player extends Model
{
    public $type      = NULL;
    public $id        = NULL;
    public $name      = NULL;
    public $partial   = NULL;
    public $race      = NULL;

    // Deferred.

    public $isHost      = FALSE;
    public $isWinner    = NULL;
    public $isObserver  = FALSE;
    public $slot        = NULL;
    public $order       = NULL;
    public $colour      = NULL;
    public $handicap    = NULL;
    public $leftAt      = NULL;
    public $stayPercent = NULL;
    public $team        = NULL;
    public $actions     = NULL;
    public $apm         = 0;
    public $activity    = [];
    public $flags       = [];
    public $variables   = NULL;

    public function read (Stream &$stream)
    {
        $this->type    = $stream->uint8 ();
        $this->id      = $stream->uint8 ();
        $this->name    = $stream->string ();
        $this->partial = $this->name;

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

        if (Context::$settings->keepActions) {
            $this->actions = [];
        }
    }

    public function getVar ($varname, $default = NULL)
    {
        if (!isset ($this->variables [$varname])) {
            return $default;
        }

        return $this->variables [$varname];
    }

    public function hasVar ($varname)
    {
        return !empty ($this->variables [$varname]);
    }

    public function hasFlag ($flag)
    {
        return in_array ($flag, $this->flags);
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