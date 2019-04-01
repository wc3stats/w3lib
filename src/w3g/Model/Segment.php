<?php

namespace w3lib\w3g\Model;

use stdClass;
use Exception;
use w3lib\Library\Logger;
use w3lib\Library\Model;
use w3lib\Library\Stream;
use w3lib\Library\Stream\Buffer;

class Segment extends Model
{
    const START_BLOCK_A = 0x1A;
    const START_BLOCK_B = 0x1B;
    const START_BLOCK_C = 0x1C;
    const TIMESLOT_1    = 0x1E;
    const TIMESLOT_2    = 0x1F;
    const CHAT_MESSAGE  = 0x20;
    const UNKNOWN_1     = 0x22;
    const UNKNOWN_2     = 0x23;
    const GAME_OVER     = 0x2F;
    const LEAVE_GAME    = 0x17;

    public function read (Stream $stream, $context = NULL)
    {
        $this->id  = $stream->int8 ();
        $this->key = $this->keyName ($this->id);

        Logger::debug (
            sprintf (
                'Found segment: [0x%2X:%s].',
                $this->id, 
                $this->key
            )
        );

        switch ($this->id) {
            default:
                throw new Exception (
                    sprintf (
                        'Encountered unknown segment id: [%2X]',
                        $this->id
                    )
                );
            break;

            case self::START_BLOCK_A:
            case self::START_BLOCK_B:
            case self::START_BLOCK_C:
                $stream->uint32 ();
            break;

            case self::TIMESLOT_1:
            case self::TIMESLOT_2:
                $this->length        = $stream->uint16 () - 2;
                $this->timeIncrement = $stream->uint16 ();
                $this->actions       = [];

                $context->time += $this->timeIncrement / 1000;

                if ($this->length > 0) {
                    $block = new Buffer ($stream->read ($this->length));

                    $this->playerId = $block->uint8 ();
                    $this->length   = $block->uint16 ();

                    Logger::debug (
                        sprintf (
                            'Processing actions for player [%d] of length [%d]',
                            $this->playerId,
                            $this->length
                        )
                    );

                    $actions = new Buffer ($block->read ($this->length));
                    
                    // xxd ($actions);

                    foreach (Action::unpackAll ($actions, $context) as $action) {

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
            break;

            case self::CHAT_MESSAGE:
                $this->message = ChatMessage::unpack ($stream, $context);
            break;

            case self::UNKNOWN_1:
                $this->size = $stream->int8 ();
                $this->body = $stream->read ($this->size);
            break;

            case self::UNKNOWN_2:
                $stream->uint32 ();
                $stream->int8 ();
                $stream->uint32 ();
                $stream->int8 ();
            break;

            case self::GAME_OVER:
                $this->mode      = $stream->uint32 ();
                $this->countdown = $stream->uint32 ();
            break;

            case self::LEAVE_GAME:
                $this->reason   = $stream->uint32 ();
                $this->playerId = $stream->int8 ();
                $this->result   = $stream->uint32 ();

                $stream->uint32 ();
            break;
        }
    }
}

?>