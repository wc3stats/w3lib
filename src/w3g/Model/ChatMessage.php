<?php

namespace w3lib\w3g\Model;

use w3lib\Library\Model;
use w3lib\Library\Stream;

class ChatMessage extends Model
{
    public $playerId = NULL;
    public $length   = NULL;
    public $flags    = NULL;
    public $mode     = NULL;
    public $message  = NULL;
    public $time     = NULL;

    public function read (Stream $stream, $context = NULL)
    {
        $this->playerId = $stream->int8 ();
        $this->length   = $stream->uint16 ();
        $this->flags    = $stream->int8 ();
        $this->mode     = $stream->uint32 ();
        $this->message  = $stream->string ();
        $this->time     = $context->time;
    }

    public function __toString ()
    {
        $minutes = str_pad (floor ($this->time / 60), 2, '0', STR_PAD_LEFT);
        $seconds = str_pad ($this->time % 60, 2, '0', STR_PAD_LEFT);

        return sprintf (
            "[%s:%s] [mode: %d] [pid: %s] - %s",
            $minutes,
            $seconds,
            $this->mode,
            $this->playerId,
            $this->message
        );
    }
}

?>