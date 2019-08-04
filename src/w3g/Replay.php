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

    public function getPlayerByName ($playerName)
    {
        return $this->game->getPlayerBy ('name', $playerName);
    }

    public function getSaver ()
    {
        return $this->getPlayerById (
            $this->game->saver
        );
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

    public function toDisplay ()
    {
        $this->game->speed         = Lang::speed ($this->game->speed);
        $this->game->visibility    = Lang::visibility ($this->game->visibility);
        $this->game->observers     = Lang::observer ($this->game->observers);
        $this->game->teamsTogether = Lang::boolean ($this->game->teamsTogether);
        $this->game->lockedTeams   = Lang::boolean ($this->game->lockedTeams);
        $this->game->fullShare     = Lang::boolean ($this->game->fullShare);
        $this->game->randomHero    = Lang::boolean ($this->game->randomHero);
        $this->game->randomRaces   = Lang::boolean ($this->game->randomRaces);
        $this->game->type          = Lang::gameType ($this->game->type);
        $this->game->private       = Lang::boolean ($this->game->private);

        foreach ($this->game->players as $player) {
            $player->colour = Lang::colour ($player->colour);
            $player->race   = Lang::race ($player->race);
            $player->isHost = Lang::boolean ($player->isHost);
        }

        foreach ($this->chatlog as $chat) {
            $chat->mode = Lang::chat ($chat->mode);
        }
    }
}

?>