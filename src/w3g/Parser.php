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
            $this->_replay->players = $this->_replay->game->players;

            /* TODO: (Anders) This could use a lot of cleanup... */
            /* Unpack segments and populate the replay container with appropriate values. */
            foreach (Segment::unpackAll ($buffer) as $k => $segment) {
                switch ($segment->id) {
                    case Segment::CHAT_MESSAGE:
                        $this->_replay->chatlog [] = $segment->message;
                    break;

                    case Segment::TIMESLOT:
                        if (!isset ($segment->playerId)) {
                            continue;
                        }
                        
                        $player = $this->_replay->getPlayerById ($segment->playerId);

                        if (!$player) {
                            Logger::warn (
                                'Player referenced in segment but not found: [%d]',
                                $segment->playerId
                            );

                            continue;
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

                                    $slotPlayer = $this->_replay->getPlayerBySlot ($action->playerId);

                                    switch ($action->type) {
                                        case Action::W3MMD_DEF_VARP:
                                            $slotPlayer->variables [$action->variable] = NULL;
                                        break;

                                        case Action::W3MMD_VARP:
                                            $slotPlayer->variables [$action->variable] = $action->value;
                                        break;
                                     
                                        case Action::W3MMD_FLAGP: 
                                            $slotPlayer->flags = $action->flag;
                                        break;
                                    }
                                break;
                            }
                        }
                    break;
                }
            }
        }
    }
}

?>