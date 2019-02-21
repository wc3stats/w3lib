<?php

namespace w3lib\w3g\Model;

use w3lib\Library\Model;
use w3lib\Library\Type;
use w3lib\Library\Stream;

class Player extends Model
{
    const HOST   = 0x00;
    const PLAYER = 0x16;
    const CUSTOM = 0x01;
    const LADDER = 0x08;

    const HUMAN    = 0x01;
    const ORC      = 0x02;
    const NIGHTELF = 0x04;
    const UNDEAD   = 0x08;
    const DAEMON   = 0x10;
    const RANDOM   = 0x20;
    const FIXED    = 0x40;

    public function __construct ()
    {
        $this->type  = Type::char ();
        $this->id    = Type::char ();
        $this->name  = Type::string ();
        $this->addon = Type::char ();
    }

    public function unpack (Stream $stream)
    {
    	parent::unpack ($stream);

    	switch ($this->addon) {
    		case self::CUSTOM:
    			Type::void (1)->read ($stream);
    		break;

    		case self::LADDER:
    			$this->runtime = Type::uint32 ()->read ($stream);
    			$this->race    = Type::uint32 ()->read ($stream);
    		break;
    	}
    }
}

?>