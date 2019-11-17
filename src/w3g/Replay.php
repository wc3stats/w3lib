<?php

namespace w3lib\w3g;

use w3lib\Archive;

class Replay extends Archive
{
    public $header  = NULL;
    public $game    = NULL;
    public $chatlog = [];

    public function __construct (string $filepath, Settings $settings = NULL)
    {
        parent::__construct ($filepath);

        $parser = new Parser ($this, $settings);
        $parser->parse ();
    }

    /** **/

    public function getPlayers ()
    {
        return $this->game->getPlayers ();
    }

    public function getPlayersByTeam ($team)
    {
        return array_filter (
            $this
                ->game
                ->getPlayers (),

            function ($player) use ($team) {
                return $player->team === $team;
            }
        );
    }

    public function getPlayer ($search)
    {
        if (is_numeric ($search)) {
            return $this->getPlayerById ($search);
        }

        return $this->getPlayerByName ($search);
    }

    public function getPlayerById ($playerId)
    {
        return $this->game->getPlayerBy ('id', $playerId);
    }

    public function getPlayerByOrder ($order)
    {
        return $this->game->getPlayerBy ('order', $order);
    }

    public function getPlayerByName ($playerName)
    {
        return $this->game->getPlayerBy ('name', $playerName);
    }

    public function getPlayerByColour ($colour)
    {
        return $this->game->getPlayerBy ('colour', $colour);
    }

    public function getSaver ()
    {
        return $this->getPlayerById (
            $this->game->saver
        );
    }

    public function getEvents ()
    {
        return $this->game->events;
    }

    public function isLadder ()
    {
        return ($this->game->type === Lang::TYPE_LADDER_FFA ||
               $this->game->type === Lang::TYPE_LADDER_TEAM) &&
               $this->game->name === Lang::LADDER_NAME &&
               $this->game->host === Lang::LADDER_HOST;
    }

    public function isLocal ()
    {
        foreach (Lang::LOCAL_GAMES as $gameName) {
            if (stripos ($this->game->name, $gameName) !== FALSE) {
                return TRUE;
            }
        }

        return FALSE;
    }

    public function isFFA ()
    {
        $teams = [];

        foreach ($this->getPlayers () as $player) {
            if (in_array ($player->team, $teams)) {
                return FALSE;
            }

            $teams [] = $player->team;
        }

        return TRUE;
    }

    public function isPrivate ()
    {
        return $this->game->private === Lang::TYPE_PRIVATE;
    }

    public function hasW3MMD ()
    {
        return $this->game->hasW3MMD;
    }

    /** **/

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
        $file = str_replace ([ '\'', '.w3g', '.w3x', '.w3m'  ], '', $file);
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
        $map = ucwords ($map);

        return $map;
    }

    /**
     * $replay->merge ($replay);
     *
     * Merges the chatlog of the passed replay with that of the existing replay
     * and chooses the longer replay as the "master" replay file.
     */
    public function merge (Replay $replay)
    {
        $chatlog = array_merge (
            $replay->chatlog,
            $this->chatlog
        );

        // Remove duplicate messages.
        $chatlog = array_unique ($chatlog);

        // Sort chatlog by time.
        uasort ($chatlog, function ($cX, $cY) {
            return $cX->time <=> $cY->time;
        });

        // Choose the longer of the two replays.
        if ($replay->getLength () >= $this->getLength ()) {
            $this->header = $replay->header;
            $this->game   = $replay->game;
        }

        $this->chatlog = array_values ($chatlog);
    }
}

?>