<?php

namespace w3lib\w3g\Model;

use w3lib\Library\Model;
use w3lib\Library\Stream;
use w3lib\Library\Exception\FatalException;

class Header extends Model
{
    public $intro            = NULL;
    public $headerSize       = NULL;
    public $compressedSize   = NULL;
    public $headerVersion    = NULL;
    public $uncompressedSize = NULL;
    public $numBlocks        = NULL;
    public $identification   = NULL;
    public $majorVersion     = NULL;
    public $buildVersion     = NULL;
    public $flags            = NULL;
    public $length           = NULL;
    public $checksum         = NULL;

    public function read (Stream &$stream)
    {
        /**
         * 2.0 [Header]
         */
        $this->intro            = $stream->string ();
        $this->headerSize       = $stream->uint32 ();

        $this->compressedSize   = $stream->uint32 ();
        $this->headerVersion    = $stream->uint32 ();
        $this->uncompressedSize = $stream->uint32 ();
        $this->numBlocks        = $stream->uint32 ();

        /**
         * 2.2 [SubHeader]
         */
        $this->identification   = $stream->char (4);
        $this->majorVersion     = $stream->uint32 ();
        $this->buildVersion     = $stream->uint16 ();

        $this->flags            = $stream->uint16 ();
        $this->length           = ceil ($stream->uint32 () / 1000);
        $this->checksum         = $stream->hex (4);

        if ($this->numBlocks <= 0) {
            throw new FatalException ('Malformed or empty replay.');
        }
    }
}

?>