<?php

namespace w3lib\w3g;

use Exception;
use w3lib\Library\Logger;
use w3lib\Library\Model;
use w3lib\Library\Stream;
use w3lib\Library\Stream\Buffer;
use w3lib\Library\Type;
use w3lib\w3g\Model\Action;
use w3lib\w3g\Model\ActionBlock;
use w3lib\w3g\Model\Header;
use w3lib\w3g\Model\Block;
use w3lib\w3g\Model\Player;
use w3lib\w3g\Model\Game;
use w3lib\w3g\Model\Segment;
use w3lib\w3g\Model\ChatLog;
use w3lib\w3g\Model\W3MMD;

class Parser
{
    protected $context;
    protected $replay;
    protected $settings;

    public function __construct (Replay $replay, Settings $settings = NULL)
    {
        if (!$settings) {
            $settings = new Settings ();
        }

        $context = new Context ();

        $context->settings = $settings;
        $context->replay   = $replay;
        $context->time     = 0x00;

        $this->context  = $context;
        $this->replay   = $replay;
        $this->settings = $settings;
    } 

    public function parse ()
    {
        $context = &$this->context;
        $replay  = &$this->replay;

        /** **/

        $header  = &$replay->header;
        $game    = &$replay->game;

        $buffer = new Buffer ();

        /** **/

        $header = Header::unpack ($replay, $context);

        for ($i = 0; $i < $header->numBlocks; $i++) {
            $block = Block::unpack ($replay, $context);

            /** **/

            $buffer->append ($block->body);

            /** **/

            if ($i === 0) {
                $game = Game::unpack ($buffer, $context);
            }

            $this->desegment ($buffer);
        }

        $this->package ();
    }

    private function desegment (Stream $block)
    {
        foreach (Segment::unpackAll ($block, $this->context) as $segment) {
            switch ($segment->id) {
                case Segment::CHAT_MESSAGE:
                    $this->importChat ($segment);
                break;

                case Segment::LEAVE_GAME: 
                    $this->importLeaver ($segment);
                break;

                case Segment::TIMESLOT_1:
                case Segment::TIMESLOT_2:
                    $this->importTimeslot ($segment);
                break;
            }
        }
    }

    /** **/

    private function importChat (Segment $segment)
    {
        $this->replay->chatlog [] = $segment->message;
    }

    private function importLeaver (Segment $segment)
    {
        $player = $this->replay->getPlayerById ($segment->playerId ?? -1);
        
        if (!$player) {
            return;
        }

        $player->leftAt = $this->context->getTime ();

        // Last leave event is the replay saver.
        $this->replay->game->saver = $player->id;
    }

    private function importTimeslot (Segment $segment)
    {
        foreach ($segment->blocks as $actionBlock) {
            foreach ($actionBlock->actions as $action) {
                switch ($action->id) {
                    default:
                        $this->importAction ($actionBlock, $action);
                    break;

                    case Action::W3MMD:
                        $this->importW3MMD ($action);
                    break;
                }
            }
        }
    }

    private function importAction (ActionBlock $actionBlock, Action $action)
    {
        $player = $this->replay->getPlayerById ($actionBlock->playerId ?? -1);

        if (!$player) {
            return;
        }

        $adx = floor (
            $this->context->time / 
            $this->settings->apx
        );

        if (!isset ($player->activity [$adx])) {
            $player->activity [$adx] = 0;
        }

        $player->activity [$adx]++;

        if ($this->settings->keepActions) {
            $player->actions [] = $action;
        }
    }

    private function importW3MMD (Action $action)
    {
        if (!isset ($action->w3mmd)) {
            return;
        }

        $w3mmd  = $action->w3mmd;
        $player = NULL;

        switch ($w3mmd->type) {
            case W3MMD::W3MMD_EVENT:
                $this->replay->game->events [] = $w3mmd;
            break;            

            case W3MMD::W3MMD_DEF_VARP:
                // var_dump ($w3mmd);
                // die ();
                foreach ($this->replay->getPlayers () as $player) {
                    if (!is_array ($player->variables)) {
                        $player->variables = [];
                    }

                    $player->variables [$w3mmd->varname] = NULL;
                }
            break;
        }

        $player = $this->replay->getPlayer (
            // $w3mmd->playerName ?? 
            $w3mmd->playerId   ?? 
            NULL
        );
    
        if (!$player) {
            return;
        }

        switch ($w3mmd->type) {
            case W3MMD::W3MMD_VARP:
                $player->variables [$w3mmd->varname] = $w3mmd->value;
            break;

            case W3MMD::W3MMD_FLAGP:
                $player->flags [] = $w3mmd->flag;
            break;
        }

        $this->replay->game->hasW3mmd = true;
    }

    /** **/

    private function package ()
    {
        $replayLength = $this->replay->getLength ();

        foreach ($this->replay->getPlayers () as $player) {
            // If there are players still in the game, must set leave time.
            if ($player->leftAt === NULL) {
                $player->leftAt = $replayLength;
            }

            $player->leftAt = min ($player->leftAt, $replayLength);
            $player->stayPercent = round ($player->leftAt / $replayLength * 100, 2);

            // Fill in player activity time holes.
            $ladx = floor ($player->leftAt / $this->settings->apx);

            for ($i = 0; $i < $ladx; $i++) {
                if (!isset ($player->activity [$i])) {
                    $player->activity [$i] = 0;
                }
            }

            ksort ($player->activity);

            $cc = count ($player->activity);

            if ($cc === 0) {
                $player->apm = 0;
            } else {
                $player->apm = ceil (
                    array_sum ($player->activity) / count ($player->activity)
                );
            }
        }

        usort ($this->replay->game->players, function ($p1, $p2) {
            return $p1->team <=> $p2->team;
        });
    }
}

?>