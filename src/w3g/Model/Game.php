<?php

namespace w3lib\w3g\Model;

use w3lib\Library\Model;
use w3lib\Library\Stream;
use w3lib\Library\Stream\Buffer;
use w3lib\w3g\Lang;
use w3lib\w3g\Util\Team;

class Game extends Model
{
    public $name          = NULL;
    public $speed         = NULL;
    public $visibility    = NULL;
    public $observers     = NULL;
    public $teamsTogether = NULL;
    public $lockedTeams   = NULL;
    public $fullShare     = NULL;
    public $randomHero    = NULL;
    public $randomRaces   = NULL;
    public $checksum      = NULL;
    public $map           = NULL;
    public $host          = NULL;
    public $numSlots      = NULL;
    public $type          = NULL;
    public $private       = NULL;
    public $recordId      = NULL;
    public $recordLength  = NULL;
    public $slotRecords   = NULL;
    public $teams         = [];
    public $randomSeed    = NULL;
    public $selectMode    = NULL;
    public $startSpots    = NULL;

    // Deferred.

    public $saver = NULL;
    public $w3mmd = false;

    public function read (Stream $stream, $context = NULL)
    {
        // 4 unknown bytes. 
        $stream->read (4);

        /**
         * 4.1 [PlayerRecord]
         */
        $host = Player::unpack ($stream, $context);
        $host->isHost = true;

        $this->addPlayer ($host);

        /**
         * 4.2 [GameName]
         */
        $this->name = $stream->string ();

        // 1 null byte.
        $stream->read (1);

        /**
         * 4.3 [Encoded String]
         */
        $decoded = new Buffer ();
        $encoded = $stream->string ();

        for ($i = 0, $cc = strlen ($encoded); $i < $cc; $i++) {
        	if ($i % 8 === 0) {
        		$mask = ord ($encoded [$i]);
        	} else {
        		$decoded->append (chr (ord ($encoded [$i]) - !($mask & (1 << $i % 8))));
        	}
        }

        /**
         * 4.4 [GameSettings]
         */
        $codes = [
            $decoded->int8 (),
            $decoded->int8 (),
            $decoded->int8 (),
            $decoded->int8 ()
        ];

        $this->speed = $codes [0];

        if ($codes [1] & 0x01) {
            $this->visibility = Lang::VISIBILITY_HIDE_TERRAIN;
        } else if ($codes [1] & 0x02) {
            $this->visibility = Lang::VISIBILITY_MAP_EXPLORED;
        } else if ($codes [1] & 0x04) {
            $this->visibility = Lang::VISIBILITY_ALWAYS_VISIBLE;
        } else if ($codes [1] & 0x08) {
            $this->visibility = Lang::VISIBILITY_DEFAULT;
        }

        $this->observers = $codes [1] & 0x10 + 2 * $codes [1] & 0x20;

        if ($codes [3] & 0x40) {
            $this->observers = Lang::OBSERVER_REFEREE;
        }

        $this->teamsTogether = (bool) ($codes [1] & 0x40);
        $this->lockedTeams   = (bool) ($codes [2]);
        $this->fullShare     = (bool) ($codes [3] & 0x01);
        $this->randomHero    = (bool) ($codes [3] & 0x02);
        $this->randomRaces   = (bool) ($codes [3] & 0x04);

        // 5 unknown bytes.
        $decoded->read (5);

        $this->checksum = $decoded->uint32 ();

        /**
         * 4.5 [Map & Creator Name]
         */
        $this->map = $decoded->string ();

        // Fix for windows download paths.
        $this->map = str_replace ('\\', '/', $this->map);
        $this->map = basename ($this->map);

        $this->host = $decoded->string ();
        
        /**
         * 4.6 [PlayerCount]
         */
        $this->numSlots = $stream->uint32 ();
        
        /**
         * 4.7 [GameType]
         */
        $this->type     = $stream->int8 ();
        $this->private  = $stream->bool ();

        /**
         * 4.8 [Language ID]
         */
        $stream->read (6);

        /**
         * 4.9 [PlayerList]
         */
        while ($stream->int8 (Stream::PEEK) === Lang::PLAYER) {
            $this->addPlayer (
                Player::unpack ($stream, $context)
            );

            // 4 unknown padding bytes.
            $stream->read (4);
        }

        /**
         * 4.10 [GameStartRecord]
         */
        $this->recordId     = $stream->int8 ();
        $this->recordLength = $stream->uint16 ();
        $this->slotRecords  = $stream->int8 ();

        /**
         * 4.11 [SlotRecord]
         */
        for ($i = 0; $i < $this->slotRecords; $i++) {
            $slot = Slot::unpack ($stream, $context);

            if (! ($player = $this->getPlayerBy ('id', $slot->playerId))) {
                continue;
            }

            $player->slot     = $i;
            $player->team     = $slot->team;
            $player->colour   = $slot->colour;
            $player->race     = $player->race ?? $slot->race;
            $player->handicap = $slot->handicap;
        }

        /**
         * 4.12 [RandomSeed]
         */
        $this->randomSeed = $stream->uint32 ();
        
        $this->selectMode = $stream->int8 ();
        $this->startSpots = $stream->int8 ();
    }

    /** **/

    public function addPlayer (Player $player)
    {
        if (!isset ($this->teams [$player->team])) {
            $this->teams [$player->team] = new Team ($player->team);
        }

        $this->teams [$player->team]->add ($player);
    }

    public function getPlayers ()
    {
        $players = [];

        foreach ($this->teams as $team) {
            foreach ($team->getPlayers () as $player) {
                $players [] = $player;
            }
        }

        return $players;
    }

    public function getPlayerBy ($key, $value)
    {
        return current ($this->getPlayersBy ($key, $value)) ?: NULL;
    }

    public function getPlayersBy ($key, $value)
    {
        $players = [];

        foreach ($this->getPlayers () as $player) {
            if (strcasecmp ($player->$key, $value) === 0) {
                $players [] = $player;
            }
        }

        return $players;
    }

    public function rebuild ()
    {
        $teams = [];

        foreach ($this->getPlayers () as $player) {
            if (!isset ($teams [$player->team])) {
                $teams [$player->team] = new Team ($player->team);
            }

            $teams [$player->team]->add ($player);
        }

        $this->teams = $teams;

        $this->sort ();
    }

    public function sort ()
    {
        if (!$this->isSortable ()) {
            return;
        }

        uasort ($this->teams, function ($teamX, $teamY) {
            if ($teamX->isWinner) {
                return -1;
            }

            if ($teamY->isWinner) {
                return 1;
            }

            return $teamY->score <=> $teamX->score;
        });

        $placement = 1;

        foreach ($this->teams as $team) {
            $team->setPlacement ($placement++);
        }
    }

    private function isSortable ()
    {
        foreach ($this->getPlayers () as $player) {
            if (is_numeric ($player->score)) {
                return true;
            }
        }

        return false;
    }

}

?>