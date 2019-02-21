<?php

namespace w3lib\w3g\Model;

use w3lib\Library\Model;
use w3lib\Library\Type;
use w3lib\Library\Stream;

class MetaBlock extends Model
{
    public function __construct ()
    {
        $this->host     = new Player ();
        $this->gameName = Type::string ();
    }

    public function unpack (Stream $stream)
    {
    	Type::void (4)->read ($stream);

    	parent::unpack ($stream);
    }

//      1 |   4 byte | Unknown (0x00000110 - another record id?)
//  2 | variable | PlayerRecord (see 4.1)
//  3 | variable | GameName (null terminated string) (see 4.2)
//  4 |   1 byte | Nullbyte
//  5 | variable | Encoded String (null terminated) (see 4.3)
//    |          |  - GameSettings (see 4.4)
//    |          |  - Map&CreatorName (see 4.5)
//  6 |   4 byte | PlayerCount (see 4.6)
//  7 |   4 byte | GameType (see 4.7)
//  8 |   4 byte | LanguageID (see 4.8)
//  9 | variable | PlayerList (see 4.9)
// 10 | variable | GameStartRecord (see 4.11)
}

?>