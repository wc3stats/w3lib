<?php

namespace w3lib\w3g\Model;

use Exception;
use w3lib\Library\Model;
use w3lib\Library\Logger;
use w3lib\Library\Encoding;
use w3lib\Library\Stream;
use w3lib\Library\Stream\Buffer;
use w3lib\w3g\Context;
use w3lib\w3g\Lang;

use function w3lib\Library\xxd;

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
    public $path          = NULL;
    public $map           = NULL;
    public $host          = NULL;
    public $sha1          = NULL;
    public $numSlots      = NULL;
    public $type          = NULL;
    public $isLocal       = NULL;
    public $private       = NULL;
    public $recordId      = NULL;
    public $recordLength  = NULL;
    public $slotRecords   = NULL;
    public $players       = [];
    public $randomSeed    = NULL;
    public $selectMode    = NULL;
    public $startSpots    = NULL;
    public $events        = [];
    public $variables     = [];

    // Deferred.

    public $saver = NULL;
    public $hasW3MMD = false;

    public function read (Stream &$stream)
    {
        // 4 unknown bytes.
        $stream->read (4);

        /**
         * 4.1 [PlayerRecord]
         */
        $host = Player::unpack ($stream);
        $host->isHost = true;


        $this->addPlayer ($host);

        /**
         * 4.2 [GameName]
         */
        $this->name = $stream->string ();

        // null byte.
        $stream->string ();

        /**
         * 4.3 [Encoded String]
         */
        $encoded = $stream->string ();
        $decoded = Encoding::decodeString ($encoded);

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

        // $this->checksum = $decoded->uint32 ();

        $this->checksum = $decoded->hex (4);

        /**
         * 4.5 [Map & Creator Name]
         */
        $this->path = $decoded->string ();

        // Fix for windows download paths.

        $this->map = str_replace ('\\', '/', $this->path);
        $this->map = basename ($this->map);

        $this->host = $decoded->string ();

        $decoded->read (1);

        $this->sha1 = $decoded->hex (20);

        // \w3lib\Library\xxd ($decoded);
        // die ();

        /**
         * 4.6 [PlayerCount]
         */
        $this->numSlots = $stream->uint32 ();

        /**
         * 4.7 [GameType]
         */
        $this->type     = $stream->int8 ();
        $this->private  = $stream->int8 ();

        /**
         * 4.8 [Language ID]
         */
        $stream->read (6);

        /**
         * 4.9 [PlayerList]
         */
        while ($stream->int8 (Stream::PEEK) === Lang::PLAYER) {
            $this->addPlayer (
                Player::unpack ($stream)
            );

            // 4 unknown padding bytes.
            $stream->read (4);
        }

        /**
         * Reforged adds another player listing.
         */
        if (Context::isReforged ()) {

            // ???
            $localHeader = chr (0x39) . chr (0x04) . chr (0x02);
            $localHeaderLen = strlen ($localHeader);

            if ($stream->read ($localHeaderLen, Stream::PEEK) === $localHeader) {
                $this->isLocal = TRUE;

                while ($byte = $stream->uint8 (Stream::PEEK)) {
                    switch ($byte) {
                        default:
                        break 2;

                        case 0x10:
                            $stream->read (2);
                        break;

                        case 0x39:
                            $stream->read (8);
                        break;

                        case 0x12:
                            $stream->read (1);

                            $n = $stream->uint8 ();
                            $stream->read ($n);

                            $stream->uint16 ();

                            if ($stream->uint8 (Stream::PEEK) === 0x70) {
                                $stream->uint32 ();
                            }

                        break;
                    }
                }

            } else {
                while ($stream->int8 (Stream::PEEK) === 0x38 ||
                       $stream->int8 (Stream::PEEK) === 0x39) {
                    
                    $type    = $stream->int8 (); // 0x38 or 0x39
                    $subtype = $stream->int8 ();

                    $length = $stream->uint32 ();
                        
                    $stream->read ($length);
                }

                // while ($stream->int8 (Stream::PEEK) === ClanPlayer::HEADER) {
                //     $clanPlayer = ClanPlayer::unpack ($stream);

                //     var_dump ($clanPlayer);


                //     foreach ($this->players as $player) {
                //         if (
                //             stripos ($clanPlayer->name, $player->name) === 0 &&
                //             strlen ($clanPlayer->name) > strlen ($player->name)
                //         ) {
                //             Logger::debug (
                //                 'Updating player name from [%s] to [%s]',
                //                 $player->name,
                //                 $clanPlayer->name
                //             );

                //             $player->name = $clanPlayer->name;
                //         }
                //     }
                // }
            }
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
        $order = 0;

        for ($i = 0; $i < $this->slotRecords; $i++) {
            // $stream->string ();
            // $stream->read (20);
            
            $slot = Slot::unpack ($stream);


            if (! ($player = $this->getPlayerBy ('id', $slot->playerId))) {
                continue;
            }

            $player->slot       = $i;
            $player->team       = $slot->team;
            $player->colour     = $slot->colour;
            $player->race       = $player->race ?? $slot->race;
            $player->handicap   = $slot->handicap;
            $player->isObserver = $slot->isObserver;

            if (!$player->isObserver) {
                $player->order = $order++;
            }
        }

        /**
         * 4.12 [RandomSeed]
         */
        $this->randomSeed = $stream->uint32 ();

        $this->selectMode = $stream->int8 ();
        $this->startSpots = $stream->int8 ();

        /** **/

        foreach (Lang::LOCAL_GAMES as $gameName) {
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