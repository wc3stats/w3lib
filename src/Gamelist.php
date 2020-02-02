<?php

namespace w3lib;

use w3lib\Library\Model;
use w3lib\Library\Stream;
use w3lib\Library\Encoding;

class Gamelist extends Model
{
    public $name;
    public $server;
    public $map;
    public $slotsTaken;
    public $slotsTotal;
    public $host;
    public $duration;
    public $checksum;

    public function read (Stream &$stream)
    {
        $stream->read (16 * 5);
        $stream->uint32 ();
        $stream->uint32 ();

        $this->slotsTaken = $stream->uint32 ();
        $this->slotsTotal = $stream->uint32 ();

        $this->duration = $stream->uint32 ();

        $this->name = $stream->string ();

        $stream->readTo (
            chr (0x4d) .
            chr (0xdb) .
            chr (0x61) .
            chr (0x71) .
            chr (0x73)
        );

        $stream->seek (
            $stream->offset () - 15
        );

        $encoded = $stream->string ();
        $decoded = Encoding::decodeString ($encoded);

        // Game Settings.
        $decoded->read (9);

        $this->checksum = $decoded->uint32 ();

        /** **/

        $this->map = $decoded->string ();

        // Fix for windows download paths.
        $this->map = str_replace ('\\', '/', $this->map);
        $this->map = basename ($this->map);

        /** **/

        $this->host = $decoded->string ();

        $stream->seek (-32);

        $this->server = $stream->string ();
    }
}

?>