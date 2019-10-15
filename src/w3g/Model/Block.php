<?php

namespace w3lib\w3g\Model;

use Exception;
use w3lib\Library\Logger;
use w3lib\Library\Model;
use w3lib\Library\Stream;
use w3lib\Library\Stream\Buffer;
use w3lib\w3g\Context;
use w3lib\w3g\Parser;
use function \w3lib\Library\xxd;

class Block extends Model
{
    public static $blockIndex = 1;

    public $compressedSize    = NULL;
    public $uncompressedSize  = NULL;
    public $checksum          = NULL;
    public $compressed        = NULL;
    public $body              = NULL;

    public function read (Stream &$stream)
    {
        Logger::info (
            "Unpacking block %d / %d (%.2f%%)",
            self::$blockIndex,
            Context::$replay->header->numBlocks,
            self::$blockIndex++ / Context::$replay->header->numBlocks * 100
        );


        if (Context::majorVersion () >= Parser::WC3_VERSION_32) {
            $this->compressedSize   = $stream->uint32 ();
            $this->uncompressedSize = $stream->uint32 ();
        } else {
            $this->compressedSize   = $stream->uint16 ();
            $this->uncompressedSize = $stream->uint16 ();
        }

        $this->checksum   = $stream->uint32 ();
        $this->compressed = $stream->read ($this->compressedSize);

        if (uint32 ($this->checksum) !== uint32 ($this->crc ())) {
            xxd (uint32 ($this->checksum));
            xxd (uint32 ($this->crc ()));

            throw new Exception (
                'Block checksum mismatch.'
            );
        }

        $body = substr ($this->compressed, 2, -4);

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

    public function crc ()
    {
        $size = Context::majorVersion () >= Parser::WC3_VERSION_32 ? 'V' : 'v';

        $crc1 = crc32 (
            pack ($size, $this->compressedSize) .
            pack ($size, $this->uncompressedSize) .
            pack ('V', 0)
        );

        $crc1 = $crc1 ^ ($crc1 >> 16);

        $crc2 = crc32 ($this->compressed);
        $crc2 = $crc2 ^ ($crc2 >> 16);

        $crc = ($crc1 & 0xFFFF) | ($crc2 << 16);

        return $crc;
    }
}

?>