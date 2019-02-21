<?php

namespace w3lib\w3g\Model;

use Exception;
use w3lib\Library\Model;
use w3lib\Library\Stream;
use w3lib\Library\Type;

class Block extends Model
{
    public function __construct ()
    {
        $this->compressedSize   = Type::uint16 ();
        $this->uncompressedSize = Type::uint16 ();
        $this->checksum         = Type::uint32 ();
        $this->body             = Type::buffer ('compressedSize');
    }

    public function unpack (Stream $stream)
    {
        parent::unpack ($stream);

        // Decompress body.
        $body = $this->body;
        $body = substr ($body, 2, -4);

        // Last bit in the first byte needs to be set.
        $body [0] = chr (ord ($body [0]) | 1);
        $body     = gzinflate ($body);

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

        $this->body = $body;
    }
}

?>