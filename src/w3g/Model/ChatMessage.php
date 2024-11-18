<?php

namespace w3lib\w3g\Model;

use w3lib\Library\Model;
use w3lib\Library\Stream;
use w3lib\Library\Stream\Buffer;
use w3lib\w3g\Context;

class ChatMessage extends Model
{
    public $playerId = NULL;
    public $length   = NULL;
    public $flags    = NULL;
    public $mode     = NULL;
    public $message  = NULL;
    public $time     = NULL;

    public function read (Stream &$stream)
    {
        $this->playerId = $stream->int8 ();
        $this->length   = $stream->uint16 ();

        $block = new Buffer ($stream->read ($this->length));

        $this->flags    = $block->int8 ();
        $this->mode     = $block->uint32 ();
        $this->message  = utf8_encode($block->string ());

        $this->time     = Context::getTime ();
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
