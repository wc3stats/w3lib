<?php

namespace w3lib\w3g\Model;

use Exception;
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
    public $events        = [];

    // Deferred.

    public $saver = NULL;
    public $hasW3mmd = false;

    const LOCAL_GAMES = [
        "Local Game",
        "Lokales Spiel",
        "Partida Local",
        "Partie Locale",
        "Partita Locale",
        "Gra Lokalna"
    ];

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
        $encoded = $stream->string ();
        $decoded = $this->decode ($encoded);

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

        /** **/

        $this->isLocal = FALSE;

        foreach (self::LOCAL_GAMES as $gameName) {
            if (stripos ($this->name, $gameName) !== FALSE) {
                $this->isLocal = TRUE;
                break;
            }
        }
    }

    /** **/

    public function addPlayer (Player $player)
    {
        $this->players [] = $player;
    }

    public function getPlayers ()
    {
        return $this->players;
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

    /** **/

    protected function decode ($encoded)
    {
        $decoded = new Buffer ();

        for ($i = 0, $cc = strlen ($encoded); $i < $cc; $i++) {
            if ($i % 8 === 0) {
                $mask = ord ($encoded [$i]);
            } else {
                $decoded->append (chr (ord ($encoded [$i]) - !($mask & (1 << $i % 8))));
            }
        }

        return $decoded;
    }

    protected function encode (Stream $stream)
    {
        $encoded = '';

        /**
         * Every even byte value incremented by 1 so all encoded bytes are odd.
         * A control-byte stores the transformations for the next 7 bytes.
         */
        $data  = $stream->readAll ();
        $mask  = 1;
        $bytes = [];

        $dataLength = strlen ($data);

        for ($i = 0; $i < $dataLength; ++$i) {
            $x = ord ($data [$i]);

            if ($x % 2 === 0) {
                $bytes [] = $x + 1;
            } else {
                $bytes [] = $x;
                $mask |= 1 << (($i % 7) + 1);
            }

            if ($i % 7 === 6 || $i === $dataLength - 1) {
                array_splice ($bytes, count ($bytes) - 1 - ($i % 7), 0, $mask);
                $mask = 1;
            }
        }

        foreach ($bytes as $byte) {
            $encoded .= chr ($byte);
        }

        return $encoded;
    }
}

?>