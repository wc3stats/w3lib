<?php

namespace w3lib\w3g\Model;

use Exception;
use w3lib\Library\Model;
use w3lib\Library\Stream;
use w3lib\Library\Stream\Buffer;

class Block extends Model
{
    public $compressedSize;
    public $uncompressedSize;
    public $checksum;
    public $body;

    public function read (Stream $stream)
    {
        $this->compressedSize   = $stream->uint16 ();
        $this->uncompressedSize = $stream->uint16 ();
        $this->checksum         = $stream->uint32 ();
        
        // 2 unknown bytes.
        $stream->char (2);

        $body = $stream->read ($this->compressedSize);

        // Last bit in the first byte needs to be set.
        $body [0] = chr (ord ($body [0]) | 1);
        
        // Decompress body.
        $body = gzinflate ($body);

        if (!$body) {
            throw new Exception ('Failed to gzinflate block.');
        }

        $actual = strlen ($body);

        if ($actual !== $this->uncompressedSize) {
            throw new Exception (
                sprintf (
                    'Found block length discrepency, expecting [%d] found [%d]',
                    $this->uncompressedSize,
                    $actual
                )
            );
        }

        $this->body = new Buffer ($body);
    }

    public function segments ()
    {
        // switch ($id = $this->body->byte ()) {
        //     case Segment::ID_START_A:
        //     case Segment::ID_START_B:
        //     case Segment::ID_START_C:
        //         new Segment ($id);
        //     break;
        // }
    }
}

?>