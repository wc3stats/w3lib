<?php

namespace w3lib\w3g\Model;

use Exception;
use w3lib\Library\Model;
use w3lib\Library\Stream;

class Segment extends Model
{
    private $_codes = [
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

    public function read (Stream $stream)
    {
        $id = $stream->byte (Stream::PEEK);

        if (!in_array ($id, $this->_codes)) {
            throw new Exception (
                sprintf (
                    'Encountered unknown segment id: [%2X]',
                    $id
                )
            );
        }

        $this->id = $stream->byte ();

        switch ($this->id) {
            case $this->_codes ['startA']:
            case $this->_codes ['startB']:
            case $this->_codes ['startC']:
                
            break;

            case $this->_codes ['timeslot1']:
            case $this->_codes ['timeslot2']:

            break;

            case $this->_codes ['chat']:

            break;

            case $this->_codes ['unknown1']:
            case $this->_codes ['unknown2']:

            break;

            case $this->_codes ['gameEnd']:

            break;

            case $this->_codes ['leaveGame']:
                
            break;
        }
    }
}

?>