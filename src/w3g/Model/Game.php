<?php

namespace w3lib\w3g\Model;

use w3lib\Library\Model;
use w3lib\Library\Stream;
use w3lib\Library\Stream\Buffer;

class Game extends Model
{
    /* $game->speed */
	const SPEED_SLOW   = 0x00;
	const SPEED_NORMAL = 0x01;
	const SPEED_FAST   = 0x02;

    /* $game->visibility */
	const VISIBILITY_HIDE_TERRAIN   = 0x00;
	const VISIBILITY_MAP_EXPLORED   = 0x01;
	const VISIBILITY_ALWAYS_VISIBLE = 0x02;
	const VISIBILITY_DEFAULT		= 0x03;

    /* game->observers */
	const OBSERVER_NONE      = 0x00;
	const OBSERVER_ON_DEFEAT = 0x02;
	const OBSERVER_FULL 	 = 0x03;
	const OBSERVER_REFEREE 	 = 0x04;

    /* game->type */
	const TYPE_LADDER_FFA  = 0x01;
	const TYPE_CUSTOM      = 0x09;
	const TYPE_LOCAL       = 0x0D;
	const TYPE_LADDER_TEAM = 0x20;

    /* $game->selectMode */
	const MODE_TEAM_RACE_SELECTABLE 	= 0x00;
	const MODE_TEAM_NOT_SELECTABLE  	= 0x01;
	const MODE_TEAM_RACE_NOT_SELECTABLE = 0x03;
	const MODE_RACE_FIXED_TO_RANDOM 	= 0x04;
	const MODE_AUTOMATIC_MATCHMAKING 	= 0xCC;

	const CHAT_ALL    	 = 0x00;
	const CHAT_ALLIES 	 = 0x01;
	const CHAT_OBSERVERS = 0x02;
	const CHAT_PAUSED 	 = 0xFE;
	const CHAT_RESUMED 	 = 0xFF;

    public $gameName;
    public $speed;
    public $visibility;
    public $observers;
    public $teamsTogether;
    public $lockedTeams;
    public $fullShare;
    public $randomHero;
    public $randomRaces;
    public $checksum;
    public $map;
    public $host;
    public $numSlots;
    public $type;
    public $private;
    public $recordId;
    public $recordLength;
    public $slotRecords;
    public $slots;
    public $players;
    public $randomSeed;
    public $selectMode;
    public $startSpots;

    public function read (Stream $stream)
    {
        $this->gameName = $stream->string ();

        /* 1 null byte. */
        $stream->read (1);

        $decoded = new Buffer ();
        $encoded = $stream->string ();

        for ($i = 0, $cc = strlen ($encoded); $i < $cc; $i++) {
        	if ($i % 8 === 0) {
        		$mask = ord ($encoded [$i]);
        	} else {
        		$decoded->append (chr (ord ($encoded [$i]) - !($mask & (1 << $i % 8))));
        	}
        }

        $codes = [
            $decoded->int8 (),
            $decoded->int8 (),
            $decoded->int8 (),
            $decoded->int8 ()
        ];

        $this->speed = $codes [0];

        if ($codes [1] & 0x01) {
            $this->visibility = Game::VISIBILITY_HIDE_TERRAIN;
        } else if ($codes [1] & 0x02) {
            $this->visibility = Game::VISIBILITY_MAP_EXPLORED;
        } else if ($codes [1] & 0x04) {
            $this->visibility = Game::VISIBILITY_ALWAYS_VISIBLE;
        } else if ($codes [1] & 0x08) {
            $this->visibility = Game::VISIBILITY_DEFAULT;
        }

        $this->observers = $codes [1] & 0x10 + 2 * $codes [1] & 0x20;

        if ($codes [3] & 0x40) {
            $this->observers = Game::OBSERVER_REFEREE;
        }

        $this->teamsTogether = (bool) ($codes [1] & 0x40);
        $this->lockedTeams   = (bool) ($codes [2]);
        $this->fullShare     = (bool) ($codes [3] & 0x01);
        $this->randomHero    = (bool) ($codes [3] & 0x02);
        $this->randomRaces   = (bool) ($codes [3] & 0x04);


        /* 5 unknown bytes. */
        $decoded->read (5);

        $this->checksum = $decoded->uint32 ();

        $this->map  = $decoded->string ();
        $this->host = $decoded->string ();
        
        // TODO: (Anders) 22 bytes left?
        // xxd ($decoded);
        // die ();

        $this->numSlots = $stream->uint32 ();
        $this->type     = $stream->int8 ();
        $this->private  = $stream->bool ();

        /* 6 unknown bytes. */
        $stream->read (6);

        $this->players = [];

        while ($stream->int8 (Stream::PEEK) === Player::PLAYER) {
            $player = Player::unpack ($stream);
            $this->players [$player->id] = $player;

            /* 4 unknown padding bytes. */
            $stream->read (4);
        }

        /* 2 unknown bytes. */
        // $stream->read (2);

        $this->recordId     = $stream->int8 ();
        $this->recordLength = $stream->uint16 ();
        $this->slotRecords  = $stream->int8 ();

        for ($i = 0; $i < $this->slotRecords; $i++) {
            $slot = Slot::unpack ($stream);
            $this->slots [] = $slot;
        }

        $this->randomSeed = $stream->uint32 ();
        $this->selectMode = $stream->int8 ();
        $this->startSpots = $stream->int8 ();
    }
}

?>