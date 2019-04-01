<?php

namespace w3lib\w3g\Model;

use w3lib\Library\Model;
use w3lib\Library\Stream;
use w3lib\Library\Stream\Buffer;
use w3lib\w3g\Lang;

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
    public $players       = [];
    public $randomSeed    = NULL;
    public $selectMode    = NULL;
    public $startSpots    = NULL;

    // Deferred.

    public $saver = NULL;

    public function read (Stream $stream, $context = NULL)
    {
        // 4 unknown bytes. 
        $stream->read (4);

        /**
         * 4.1 [PlayerRecord]
         */
        $host = Player::unpack ($stream, $context);
        $host->isHost = true;
        
        $this->players [$host->id] = $host;

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
            $player = Player::unpack ($stream, $context);
            $this->players [$player->id] = $player;

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

            $player = $this->players [$slot->playerId];

            $player->slot     = $i;
            $player->team     = $slot->team;
            $player->colour   = $slot->colour;
            $player->race     = $this->race ?? $slot->race;
            $player->handicap = $slot->handicap;
        }

        /**
         * 4.12 [RandomSeed]
         */
        $this->randomSeed = $stream->uint32 ();
        
        $this->selectMode = $stream->int8 ();
        $this->startSpots = $stream->int8 ();

        $this->saver = NULL;
    }
}

?>