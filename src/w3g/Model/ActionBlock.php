<?php

namespace w3lib\w3g\Model;

use w3lib\Library\Logger;
use w3lib\Library\Model;
use w3lib\Library\Stream;
use w3lib\Library\Stream\Buffer;

use function w3lib\Library\xxd;

class ActionBlock extends Model
{
    public function read (Stream &$stream)
    {
        $this->playerId = $stream->uint8 ();
        $this->length   = $stream->uint16 ();
        $this->actions  = [];

        // Logger::debug (
        //     sprintf (
        //         'Processing action block for player [%d] of length [%d]',
        //         $this->playerId,
        //         $this->length
        //     )
        // );


        $block = new Buffer ($stream->read ($this->length));

        // if (Logger::isDebug ()) {
            // xxd ($block);
        // }

        foreach (Action::unpackAll ($block) as $action) {
            // Actions to ignore.
            if (in_array ($action->id, [
                Action::UNKNOWN_1,
                Action::UNKNOWN_2,
                Action::UNKNOWN_3,
                Action::UNKNOWN_4,
                Action::UNKNOWN_5,
                Action::SCENARIO_TRIGGER,
                Action::PRE_SUBSELECT
            ])) {
                continue;
            }

            $this->actions [] = $action;
        }

        // Logger::debug (
        //     'Found [%d] action%s in blocksize [%d] for player [%s].',
        //     count ($this->actions),
        //     count ($this->actions) === 1 ? '' : 's',
        //     $this->length,
        //     $this->playerId
        // );
    }
}

?>