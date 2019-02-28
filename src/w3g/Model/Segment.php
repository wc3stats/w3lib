<?php

namespace w3lib\w3g\Model;

use Exception;
use w3lib\Library\Model;
use w3lib\Library\Stream;
use w3lib\Library\Stream\Buffer;

class Segment extends Model
{
    const REASON_CONN_CLOSED_REMOTE = 0x01;
    const REASON_CONN_CLOSED_LOCAL  = 0x0C;
    const REASON_UNKNOWN            = 0x0E;

    const MODE_COUNTDOWN_RUNNING = 0x00;
    const MODE_COUNTDOWN_FORCED  = 0x01;

    /*
    const CHAT_FLAG_DELAYED_SCREEN = 0x10;
    const CHAT_FLAG_NORMAL         = 0x20;

    const CHAT_ALL      = 0x00;
    const CHAT_ALLIES   = 0x01;
    const CHAT_OBSERVER = 0x02;
    const CHAT_PRIVATE  = 0x03; // + N (N = slotNumber)
    */

    const TYPE_TIMESLOT   = 0x01;
    const TYPE_CHAT       = 0x02;
    const TYPE_GAME_END   = 0x04;
    const TYPE_LEAVE_GAME = 0x08;

    private $_codes = [
        'startA'    => 0x1A,
        'startB'    => 0x1B,
        'startC'    => 0x1C,
        'timeslot1' => 0x1E,
        'timeslot2' => 0x1F,
        'chat'      => 0x20,
        'unknown1'  => 0x22,
        'unknown2'  => 0x23,
        'gameEnd'   => 0x2F,
        'leaveGame' => 0x17
    ];

    public function read (Stream $stream)
    {
        $id = $stream->byte (Stream::PEEK);

        if (!in_array ($id, $this->_codes)) {
            throw new Exception (
                sprintf (
                    'Encountered unknown segment id: [%2X]',
                    $id
                )
            );
        }

        $this->id = $stream->byte ();

        switch ($this->id) {
            case $this->_codes ['startA']:
            case $this->_codes ['startB']:
            case $this->_codes ['startC']:
                $stream->uint32 ();
            break;

            case $this->_codes ['timeslot1']:
            case $this->_codes ['timeslot2']:
                $this->type          = self::TYPE_TIMESLOT;
                $this->length        = $stream->uint16 () - 2;
                $this->timeIncrement = $stream->uint16 ();

                if ($this->length > 2) {
                    $block = new Buffer ($stream->read ($this->length));

                    foreach (Action::unpack ($block) as $action) {
                        var_dump ($action->id);
                    }
                }
            break;

            case $this->_codes ['chat']:
                xxd ($stream);
                die ('CHAT');
                $this->type     = self::TYPE_CHAT;
                $this->playerId = $stream->char ();
                $this->length   = $stream->uint16 ();
                $this->flags    = $stream->char ();
                $this->mode     = $stream->uint32 ();
                $this->message  = $stream->string ();
            break;

            case $this->_codes ['unknown1']:
                $this->size = $stream->byte ();
                $this->body = $stream->read ($this->size);
            break;

            case $this->_codes ['unknown2']:
                $stream->uint32 ();
                $stream->byte ();
                $stream->uint32 ();
                $stream->byte ();
            break;

            case $this->_codes ['gameEnd']:
                $this->type      = self::TYPE_GAME_END;
                $this->mode      = $stream->uint32 ();
                $this->countdown = $stream->uint32 ();
            break;

            case $this->_codes ['leaveGame']:
                $this->type     = self::TYPE_LEAVE_GAME;
                $this->reason   = $stream->uint32 ();
                $this->playerId = $stream->char ();
                $this->result   = $stream->uint32 ();

                $stream->uint32 ();
            break;
        }
    }
}

?>