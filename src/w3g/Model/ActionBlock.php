<?php

namespace w3lib\w3g\Model;

use w3lib\Library\Logger;
use w3lib\Library\Model;
use w3lib\Library\Stream;
use w3lib\Library\Stream\Buffer;

class ActionBlock extends Model
{
    public function read (Stream $stream, $context = NULL)
    {
        $this->playerId = $stream->uint8 ();
        $this->length   = $stream->uint16 ();
        $this->actions  = [];

        Logger::debug (
            sprintf (
                'Processing action block for player [%d] of length [%d]',
                $this->playerId,
                $this->length
            )
        );

        $block = new Buffer ($stream->read ($this->length));

        foreach (Action::unpackAll ($block, $context) as $action) {

            // Actions to ignore.
            if (in_array ($action->id, [
                Action::UNKNOWN_1,
                Action::UNKNOWN_2,
                Action::UNKNOWN_3,
                Action::SCENARIO_TRIGGER,
                Action::PRE_SUBSELECT
            ])) {
                continue;
            }

            $this->actions [] = $action;
        }
    }
}

?>