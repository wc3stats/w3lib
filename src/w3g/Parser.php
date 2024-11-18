<?php

namespace w3lib\w3g;

use stdClass;
use Exception;
use function w3lib\Library\xxd;
use w3lib\Library\Logger;
use w3lib\Library\Model;
use w3lib\Library\Stream;
use w3lib\Library\Stream\Buffer;
use w3lib\Library\Type;
use w3lib\Library\Exception\RecoverableException;
use w3lib\w3g\Model\Action;
use w3lib\w3g\Model\ActionBlock;
use w3lib\w3g\Model\Header;
use w3lib\w3g\Model\Block;
use w3lib\w3g\Model\Player;
use w3lib\w3g\Model\Game;
use w3lib\w3g\Model\Segment;
use w3lib\w3g\Model\ChatLog;
use w3lib\w3g\Model\W3MMD;
use w3lib\w3g\Util\Detect;

class Parser
{
    const VERSION = 2.2;

    const WC3_VERSION_31 = 10031;
    const WC3_VERSION_32 = 10032;
    const WC3_VERSION_33 = 10033;
    const WC3_VERSION_34 = 10100;

    protected $replay;
    protected $settings;

    public function __construct (Replay $replay, Settings $settings = NULL)
    {
        if (!$settings) {
            $settings = new Settings ();
        }

        Context::$settings = $settings;
        Context::$replay   = $replay;
        Context::$time     = 0x00;
        Context::$leavers  = [];

        $this->replay   = $replay;
        $this->settings = $settings;

        $this->cache = new stdClass ();
    }

    public function parse ()
    {
        $replay  = &$this->replay;

        /** **/

        $header  = &$replay->header;
        $game    = &$replay->game;

        $buffer = new Buffer ();

        /** **/

        $header = Header::unpack ($replay);

        for ($i = 0; $i < $header->numBlocks; $i++) {
            $block = Block::unpack ($replay);

            /** **/

            $buffer->append ($block->body);

            /** **/

            if ($i === 0) {
                $game = Game::unpack ($buffer);
            }

            $this->desegment ($buffer);
         }

        $this->package ();
    }

    private function desegment (Stream $block)
    {
        foreach (Segment::unpackAll ($block) as $segment) {
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
        $cc = count (
            $this
                ->replay
                ->chatlog
        ) - 1;

        if ($cc >= 0) {
            $lastMessage = $this
                ->replay
                ->chatlog [$cc];

            if (
                $segment->message->time - $lastMessage->time <= 1 &&
                strcasecmp ($segment->message->message, $lastMessage->message) === 0
            ) {
                // Duplicate entry in replay.
                return;
            }
        }

        $this->replay->chatlog [] = $segment->message;
    }

    private function importLeaver (Segment $segment)
    {
        $player = $this
            ->replay
            ->getPlayerById (
                $segment->playerId ?? -1
            );

        if (!$player) {
            return;
        }

        $player->leftAt = Context::getTime ();

        // Last leave record is the saver.
        $this
            ->replay
            ->game
            ->saver = $player->id;

        if (!$player->isObserver) {
            switch ($segment->reason) {
                case 0x01:
                    $player->isObserver = [
                        // 0x01 => true,
                        0x0B => true
                    ] [$segment->result] ?? false;
                break;

                case 0x0E:
                    $player->isObserver = [
                        0x0B => true
                    ] [$segment->result] ?? false;
                break;

                case 0x0C:
                    $player->isObserver = [
                        // 0x01 => true
                    ] [$segment->result] ?? false;
                break;
            }
        }

        // Save leaver segments for win detection.
        Context::$leavers [] = $segment;
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
            Context::getTime () /
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
            case W3MMD::EVENT:
                $this->replay->game->events [] = $w3mmd;
            break;

            case W3MMD::DEF_VARP:
                foreach ($this->replay->getPlayers () as $player) {
                    if (!is_array ($player->variables)) {
                        $player->variables = [];
                    }

                    $player->variables [$w3mmd->varname] = NULL;
                }
            break;
        }

        $this
            ->replay
            ->game
            ->hasW3MMD = true;

        $player = $this->replay->getPlayer (
            // $w3mmd->playerName ??
            $w3mmd->playerId ??
            NULL
        );

        if (!$player) {
            return;
        }

        switch ($w3mmd->type) {
            case W3MMD::VARP:
                if (!isset ($player->variables [$w3mmd->varname])) {
                    $player->variables [$w3mmd->varname] = 0;
                }

                switch ($w3mmd->operator) {
                    case W3MMD::OP_ADD:
                        $player->variables [$w3mmd->varname] += $w3mmd->value;
                    break;

                    case W3MMD::OP_SUB:
                        $player->variables [$w3mmd->varname] -= $w3mmd->value;
                    break;

                    case W3MMD::OP_SET:
                        $player->variables [$w3mmd->varname] = $w3mmd->value;
                    break;
                }
            break;

            case W3MMD::FLAGP:
                $player->flags [] = $w3mmd->flag;
                $player->flags = array_unique ($player->flags);
            break;
        }
    }

    /** **/

    private function package ()
    {
        $replayLength = $this->replay->getLength ();

        foreach (
            $this
                ->replay
                ->getPlayers () as $player
        ) {
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

            unset ($player->partial);
        }

        usort (
            $this
                ->replay
                ->game
                ->players,

            function ($p1, $p2) {
                return $p1->team <=> $p2->team;
            }
        );

        Detect::winner (
            $this->replay,
            Context::$leavers
        );
    }
}

?>
