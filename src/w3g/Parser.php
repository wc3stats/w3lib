<?php

namespace w3lib\w3g;

use Exception;
use w3lib\Library\Logger;
use w3lib\Library\Model;
use w3lib\Library\Stream;
use w3lib\Library\Stream\Buffer;
use w3lib\Library\Type;
use w3lib\w3g\Model\Action;
use w3lib\w3g\Model\Header;
use w3lib\w3g\Model\Block;
use w3lib\w3g\Model\Player;
use w3lib\w3g\Model\Game;
use w3lib\w3g\Model\Segment;
use w3lib\w3g\Model\ChatLog;

class Parser
{
    public static $time = 0x00;
    
    private $_replay;

    public function __construct (Replay $replay)
    {
        $this->_replay = $replay;
    } 

    public function parse ()
    {
        Logger::debug ('Parsing replay header.');

        $this->_replay->header  = Header::unpack ($this->_replay);
        $this->_replay->chatlog = [];

        Logger::debug ('Parsing replay blocks.');

        $buffer = new Buffer ();

        for ($i = 1; $i <= $this->_replay->header->numBlocks; $i++) {
            Logger::info (
                "Parsing block %d / %d (%.2f%%)",
                $i,
                $this->_replay->header->numBlocks,
                $i / $this->_replay->header->numBlocks * 100
            );

            $block = Block::unpack ($this->_replay);
            $buffer->append ($block->body);

            if ($i === 1) {
                /* 4 unknown bytes. */
                $buffer->read (4);

                $this->_replay->host = Player::unpack ($buffer);
                $this->_replay->game = Game::unpack ($buffer);
            }

            /* Host player is not included in the regular player list. */
            $this->_replay->game->players [$this->_replay->host->id] = $this->_replay->host;

            /* Bring the players up a level for easier access. */
            $this->_replay->players = $this->_replay->game->players;

            /* TODO: (Anders) This could use a lot of cleanup... */
            /* Unpack segments and populate the replay container with appropriate values. */
            foreach (Segment::unpackAll ($buffer) as $k => $segment) {
                switch ($segment->id) {
                    case Segment::TIMESLOT_1:
                    case Segment::TIMESLOT_2:
                        $this->_importTimeslot ($segment);
                    break;

                    case Segment::CHAT_MESSAGE:
                        $this->_replay->chatlog [] = $segment->message;
                    break;

                    case Segment::LEAVE_GAME:
                        $player = $this->_getPlayerFromSegment ($segment);

                        if (!$player) {
                            continue;
                        }

                        $player->leftAt = Parser::$time;
                    break;
                }
            }
        }

        /* Set the leftAt time for each player to the game duration if there was
           no LEAVE_GAME segment. This is necessary for when the saver is not 
           the last to leave the game (there will be no LEAVE_GAME segment for 
           the remaining players). */
        foreach ($this->_replay->game->players as $player) {
            if ($player->leftAt === NULL) {
                $player->leftAt = $this->_replay->header->length;
            }
        }
    }

    private function _importTimeslot (Segment $segment)
    {
        $player = $this->_getPlayerFromSegment ($segment);

        if (!$player) {
            return;
        }

        foreach ($segment->actions as $action) {
            switch ($action->id) {
                default:
                    $player->actions [] = $action;
                break;

                case Action::W3MMD:
                    if (!isset ($action->playerId)) {
                        continue;
                    }

                    /* The W3MMD playerIds do not seem to match up with the playerId
                       found in the segment. That is, they seem to be placed in
                       whichever segment is being written at the time of the event. */
                    $slotPlayer = $this->_replay->getPlayerBySlot ($action->playerId);

                    switch ($action->type) {
                        case Action::W3MMD_VARP:
                            $slotPlayer->variables [$action->varname] = $action->value;
                        break;
                     
                        case Action::W3MMD_FLAGP: 
                            $slotPlayer->flags = $action->flag;
                        break;
                    }
                break;
            }
        }
    }

    private function _getPlayerFromSegment (Segment $segment)
    {
        if (!isset ($segment->playerId)) {
            return NULL;
        }
        
        $player = $this->_replay->getPlayerById ($segment->playerId);

        if (!$player) {
            Logger::warn (
                'Player referenced in segment but not found: [%d]',
                $segment->playerId
            );

            return NULL;
        }

        return $player;
    }
}

?>