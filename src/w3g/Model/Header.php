<?php

namespace w3lib\w3g\Model;

use w3lib\Library\Model;
use w3lib\Library\Type;
use w3lib\Library\Stream;

class Header extends Model
{
    const REPLAY_INTRO = "Warcraft III recorded game";

    public function __construct ()
    {
        $this->intro            = Type::string ();
        $this->headerSize       = Type::uint32 ();
        $this->compressedSize   = Type::uint32 ();
        $this->headerVersion    = Type::uint32 ();
        $this->uncompressedSize = Type::uint32 ();
        $this->numBlocks        = Type::uint32 ();
        $this->identification   = Type::string (4);
        $this->majorVersion     = Type::uint32 ();
        $this->buildVersion     = Type::uint16 ();
        $this->flags            = Type::uint16 ();
        $this->length           = Type::uint32 ();
        $this->checksum         = Type::uint32 ();
    }

    public function unpack (Stream $stream)
    {
        parent::unpack ($stream);

        if (strpos ($this->intro, self::REPLAY_INTRO) !== 0) {
            return sprintf (
                'Unrecognized replay file intro: [%s]',
                $this->intro
            );
        }
    }
}

?>