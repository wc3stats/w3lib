<?php

namespace w3lib\w3g\Model;

use stdClass;
use Exception;
use w3lib\Library\Logger;
use w3lib\Library\Model;
use w3lib\Library\Stream;
use w3lib\w3g\Lang;
use w3lib\w3g\Context;
use w3lib\w3g\Parser;
use function w3lib\Library\xxd;

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
        

        /**
         * Added in Patch 1.33
         */
        
        if (Context::majorVersion () >= Parser::WC3_VERSION_33) {
            $stream->uint32 ();
            $stream->uint32 ();

            $stream->int8 ();

            /**
             * Added in Patch 2.0
             */

            if (Context::majorVersion () >= Parser::WC3_VERSION_34) {
                $stream->uint16 ();
            }
        }
    }
}

?>