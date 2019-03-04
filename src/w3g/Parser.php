<?php

namespace w3lib\w3g;

use Exception;
use w3lib\Library\Logger;
use w3lib\Library\Model;
use w3lib\Library\Stream;
use w3lib\Library\Stream\Buffer;
use w3lib\Library\Type;
use w3lib\w3g\Model\Header;
use w3lib\w3g\Model\Block;
use w3lib\w3g\Model\Player;
use w3lib\w3g\Model\Game;
use w3lib\w3g\Model\Segment;

class Parser
{
    private $_replay;

    public function __construct (Replay $replay)
    {
        $this->_replay = $replay;
    } 

    public function parse ()
    {
        Logger::debug ('Parsing replay header.');

        $replay->header = Header::unpack ($this->_replay);

        Logger::debug ('Parsing replay blocks.');

        $buffer = new Buffer ();

        for ($i = 1; $i <= $replay->header->numBlocks; $i++) {
            Logger::info (
                "Parsing block %d / %d (%.2f%%)",
                $i,
                $replay->header->numBlocks,
                $i / $replay->header->numBlocks * 100
            );

            $block = Block::unpack ($this->_replay);
            $buffer->append ($block->body);

            if ($i === 1) {
                // 4 unknown bytes.
                $buffer->read (4);

                $replay->host = Player::unpack ($buffer);
                $replay->game = Game::unpack ($buffer);
            }

            foreach (Segment::unpackAll ($buffer) as $k => $segment) {
                if (!empty ($segment->actions)) {
                    var_dump ($segment);
                }
            }
        }
    }

    // const MISC_MAX_DATABLOCK = 1500;
    // const MISC_APM_DELAY     = 200;

    // const ACTION_ID_TYPE_STRING  = 0x01;
    // const ACTION_ID_TYPE_NUMERIC = 0x02;

    // const FILE_INTRO            = "Warcraft III recorded game";

    // const W3MMD_PREFIX          = "MMD.Dat";
    // const W3MMD_INIT            = "Init";
    // const W3MMD_EVENT           = "Event";
    // const W3MMD_DEF_EVENT       = "DefEvent";
    // const W3MMD_DEF_VARP        = "DefVarP";
    // const W3MMD_FLAGP           = "FlagP";
    // const W3MMD_VARP            = "VarP";

    // const W3MMD_FLAG_DRAWER     = 0x01;
    // const W3MMD_FLAG_LOSER      = 0x02;
    // const W3MMD_FLAG_WINNER     = 0x04;
    // const W3MMD_FLAG_LEAVER     = 0x08;
    // const W3MMD_FLAG_PRACTICING = 0x10;

    // const STATE_DESELECT        = 0x01;
    // const STATE_SUBGROUP        = 0x02;
    // const STATE_BASIC_ACTION    = 0x04;

    // const EVENT_CREATE_OBJECT   = 'Create';
    // const EVENT_CANCEL_OBJECT   = 'Cancel';

    // const LEAVE_VOLUNTARILY     = 'Left';
    // const LEAVE_DISCONNECTED    = 'Disconnected';

    // const ACTION_MACRO          = 0x01;
    // const ACTION_MICRO          = 0x02;
    // const ACTION_GENERAL        = 0x04;
}

?>