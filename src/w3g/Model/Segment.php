<?php

namespace w3lib\w3g\Model;

use Exception;
use w3lib\Library\Model;
use w3lib\Library\Stream;

class Segment extends Model
{
    private $_codes [
        'startA'    => 0x1A,
        'startB'    => 0x1B,
        'startC'    => 0x1C,
        'timeslot1' => 0x1E,
        'timeslot2' => 0x1F,
        'chat'      => 0x20,
        'unknown1'  => 0x22,
        'unknown2'  => 0x23,
        'gameEnd'   => 0x2F,
        'leaveGame' => 0x17
    ];

    public $id;

    public function __construct ($id)
    {
        if (!in_array ($id, $this->_codes)) {
            throw new Exception (
                sprintf (
                    'Encountered unknown segment id: [%2X]',
                    $id
                )
            );
        }

        $this->id = $id;
    }

    public function read (Stream $stream)
    {
        
    }
}

?>