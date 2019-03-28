<?php

namespace w3lib\w3g\Model;

use w3lib\Library\Model;
use w3lib\Library\Stream;
use w3lib\w3g\Parser;

class ChatMessage extends Model
{
    const CHAT_FLAG_DELAYED_SCREEN = 0x10;
    const CHAT_FLAG_NORMAL         = 0x20;

    const CHAT_ALL      = 0x00;
    const CHAT_ALLIES   = 0x01;
    const CHAT_OBSERVER = 0x02;
    const CHAT_PRIVATE  = 0x03; // + N (N = slotNumber)

    public $playerId;
    public $length;
    public $flags;
    public $mode;
    public $message;
    public $time;

    public function read (Stream $stream)
    {
        $this->playerId = $stream->int8 ();
        $this->length   = $stream->uint16 ();
        $this->flags    = $stream->int8 ();
        $this->mode     = $stream->uint32 ();
        $this->message  = $stream->string ();
        $this->time     = Parser::getTime ();
    }
}

?>