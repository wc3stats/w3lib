<?php

namespace w3lib\w3g\Model;

use stdClass;
use Exception;
use w3lib\Library\Logger;
use w3lib\Library\Model;
use w3lib\Library\Stream;
use w3lib\w3g\Lang;
use w3lib\w3g\Context;

class ClanPlayer extends Model
{
    const HEADER = 0x0A;

    public $name = NULL;
    public $clan = NULL;

    public function read (Stream &$stream)
    {
        $header    = $stream->uint8 ();
        $subheader = $stream->uint32 ();

        if ($header !== self::HEADER) {
            throw new Exception (
                sprintf (
                    'Encountered unknown clan player header: [%2X]',
                    $header
                )
            );
        }

        $n = $stream->int8 ();

        $this->name = $stream->read ($n);

        $stream->read (1);

        $n = $stream->int8 ();

        $this->clan = $stream->read ($n);

        $stream->read (1);

        $n = $stream->int8 ();

        $stream->read ($n);
        $stream->read (4);
    }
}

?>