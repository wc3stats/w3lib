<?php

namespace w3lib\w3g;

use w3lib\Archive;

class Replay extends Archive
{
    public $header;
    public $game;
    public $players;
    public $chatlog;

    public function __construct (string $filepath, Settings $settings = NULL)
    {
        parent::__construct ($filepath);
        
        $parser = new Parser ($this, $settings);
        $parser->parse ();
    }

    public function getPlayer ($key, $value)
    {
        foreach ($this->players as $player) {
            if ($player->$key === $value) {
                return $player;
            }
        }

        return NULL;
    }

    public function getPlayerById ($playerId)
    {
        return $this->getPlayer ('id', $playerId);
    }

    public function getPlayerByName ($playerName) 
    {
        return $this->getPlayer ('name', $playerName);
    }

    public function getPlayerBySlot ($slot)
    {
        if (! ($slot = $this->game->slots [$slot])) {
            return NULL;
        }

        return $this->getPlayerById ($slot->playerId);
    }

    public function getSlot ($playerId)
    {
        foreach ($this->game->slots as $slot) {
            if ($slot->playerId === $playerId) {
                return $slot;
            }
        }

        return NULL;
    }

    public function getLength ()
    {
        return $this->header->length;
    }
    
    /**
     * $replay->getHash ()
     *
     * This hash can be used to detect "duplicate" replays. That is, replays
     * that may have different file signatures because savers left at different
     * times.
     *
     * if ($replayA->getHash () === $replayB->getHash ()) {
     *     // They are of the same game. 
     * 
     *     if ($replayA->getLength () > $replayB->getLength ()) {
     *         // Use data from replayA but merge the chatlog from replayB.
     *     } else {
     *         // Use data from replayB but merge the chatlog from replayA.
     *     }
     * }
     */
    public function getHash ()
    {
        return md5 (
            $this->game->name . 
            $this->game->randomSeed
        );
    }

    /**
     * $replay->getMap ()
     *
     * Attempts to extract the map basename (without the version number).
     *
     * For example: 
     *     War in the Plaguelands [24] B6b
     *  => War in the Plaguelands
     *
     *     TSoL_Cots_1.2fix
     *  => TSoL Cots
     *
     *     KalimdorTA_0.23
     *  => Kalimdor TA
     *
     *     Azeroth Wars LR 2.08a
     *  => Azeroth Wars LR
     */
    public function getMap ()
    {
        $file = $this->game->map;

        $file = str_replace ([ '_', '?', '!', '-' ], ' ', $file);
        $file = str_replace ([ '\'' ], '', $file);
        $file = trim ($file);

        /* Remove anything between brackets or parentheses if there are numbers. */
        $map = preg_replace ('/[\[\(\{].*\d+.*[\]\)\}]/', '', $file);

        /* Remove anything after a tilde if at the end of the string. */
        # $map = preg_replace ('/~\d*$/', '', $map);

        /* Add spaces before capital letters on PascalCased map names. */
        $map = preg_replace ('/(?<=[a-z0-9])[A-Z](?!\s|$)/', ' $0', $map);

        /* Add a space before the first number and remove any decorations. */
        $map = preg_replace ('/\d/', ' $0', $map, 1);

        /* Remove duplicate spaces. */
        $map = preg_replace ('/\s\s+/', ' ', $map);

        $toks = explode (' ', $map);

        for ($i = 1, $cc = count ($toks); $i < $cc; $i++) {
            if (empty ($toks [$i])) {
                continue;
            }

            if (strlen ($toks [$i]) === 1 || ctype_digit ($toks [$i] [0])) {
                break;
            }
        }

        $toks = array_slice ($toks, 0, $i);

        $map = implode (' ', $toks);
        $map = trim ($map);
        $map = ucfirst ($map);

        return $map;
    }
}

?>