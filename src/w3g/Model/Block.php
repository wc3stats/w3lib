<?php

namespace w3lib\w3g\Model;

use Exception;
use w3lib\Library\Logger;
use w3lib\Library\Model;
use w3lib\Library\Stream;
use w3lib\Library\Stream\Buffer;

class Block extends Model
{
    public static $blockIndex = 1;

    public $compressedSize    = NULL;
    public $uncompressedSize  = NULL;
    public $checksum          = NULL;
    public $body              = NULL;

    public function read (Stream $stream, $context = NULL)
    {
        Logger::info (
            "Unpacking block %d / %d (%.2f%%)",
            self::$blockIndex,
            $context->replay->header->numBlocks,
            self::$blockIndex++ / $context->replay->header->numBlocks * 100
        );

        $this->compressedSize   = $stream->uint16 ();
        $this->uncompressedSize = $stream->uint16 ();
        $this->checksum         = $stream->uint32 ();

        $body = $stream->read ($this->compressedSize);
        $body = substr ($body, 2, -4);

        /* Last bit in the first byte needs to be set. */
        $body [0] = chr (ord ($body [0]) | 1);
        
        /* Decompress body. */
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
}

?>