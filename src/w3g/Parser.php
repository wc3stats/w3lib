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
    
    protected $replay;
    protected $settings;

    public function __construct (Replay $replay, Settings $settings = NULL)
    {
        $this->replay   = $replay;
        $this->settings = $settings;

        if (!$this->settings) {
            $this->settings = new Settings ();
        }
    } 

    public function parse ()
    {
        Logger::debug ('Parsing replay header.');

        $this->replay->header  = Header::unpack ($this->replay);
        $this->replay->chatlog = [];

        Logger::debug ('Parsing replay blocks.');

        $buffer = new Buffer ();

        for ($i = 1; $i <= $this->replay->header->numBlocks; $i++) {
            Logger::info (
                "Parsing block %d / %d (%.2f%%)",
                $i,
                $this->replay->header->numBlocks,
                $i / $this->replay->header->numBlocks * 100
            );

            $block = Block::unpack ($this->replay);
            $buffer->append ($block->body);

            if ($i === 1) {
                /* 4 unknown bytes. */
                $buffer->read (4);

                $host = Player::unpack ($buffer);

                $this->replay->game = Game::unpack ($buffer);

                /* Host player is not included in the regular player list. */
                $this->replay->game->players [$host->id] = $host;

                /* Bring the players up a level for easier access. */
                $this->replay->players = $this->replay->game->players;
            }


            /* TODO: (Anders) This could use a lot of cleanup... */
            /* Unpack segments and populate the replay container with appropriate values. */
            foreach (Segment::unpackAll ($buffer) as $k => $segment) {
                switch ($segment->id) {
                    case Segment::TIMESLOT_1:
                    case Segment::TIMESLOT_2:
                        $this->importTimeslot ($segment);
                    break;

                    case Segment::CHAT_MESSAGE:
                        $this->replay->chatlog [] = $segment->message;
                    break;

                    case Segment::LEAVE_GAME:
                        $player = $this->getPlayerFromSegment ($segment);

                        if (!$player) {
                            continue 2;
                        }

                        $player->leftAt = self::getTime ();

                        $this->replay->game->saver = $player->id;
                    break;
                }
            }
        }

        /* Set the leftAt time for each player to the game duration if there was
           no LEAVE_GAME segment. This is necessary for when the saver is not 
           the last to leave the game (there will be no LEAVE_GAME segment for 
           the remaining players). */
        foreach ($this->replay->players as $player) {
            if ($player->leftAt === NULL) {
                $player->leftAt = $this->replay->header->length;
            }

            /* Fill in any player activity time holes. We have to do this here
               because we don't know when the player left until all of the
               actions have been read. */
            $lastActivityIndex = floor ($player->leftAt / $this->settings->apx);

            for ($i = 0; $i < $lastActivityIndex; $i++) {
                if (!isset ($player->activity [$i])) {
                    $player->activity [$i] = 0;
                }
            }

            ksort ($player->activity);
        }
    }

    public static function getTime ()
    {
        return floor (self::$time);
    }

    private function importTimeslot (Segment $segment)
    {
        $player = $this->getPlayerFromSegment ($segment);

        if (!$player) {
            return;
        }

        foreach ($segment->actions as $action) {
            switch ($action->id) {
                default:
                    $activityIndex = floor (self::$time / $this->settings->apx);

                    if (!isset ($player->activity [$activityIndex])) {
                        $player->activity [$activityIndex] = 0;
                    }

                    $player->activity [$activityIndex]++;

                    if ($this->settings->keepActions) {
                        $player->actions [] = $action;
                    }
                break;

                case Action::W3MMD:
                    if (!isset ($action->playerId)) {
                        continue 2;
                    }

                    /* The W3MMD playerIds do not seem to match up with the playerId
                       found in the segment. That is, they seem to be placed in
                       whichever segment is being written at the time of the event. */
                    $slotPlayer = $this->replay->getPlayerBySlot ($action->playerId);

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

    private function getPlayerFromSegment (Segment $segment)
    {
        if (!isset ($segment->playerId)) {
            return NULL;
        }
        
        $player = $this->replay->getPlayerById ($segment->playerId);

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