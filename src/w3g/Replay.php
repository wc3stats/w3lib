<?php

namespace w3lib\w3g;

use w3lib\Archive;

class Replay extends Archive
{
    public $header;
    public $game;
    public $players;
    public $chatlog;

    public function __construct (string $filepath)
    {
        parent::__construct ($filepath);

        $parser = new Parser ($this);
        $parser->parse ();
    }

    public function getPlayerById ($playerId)
    {
        foreach ($this->players as $player) {
            if ($player->id === $playerId) {
                return $player;
            }
        }

        return NULL;
    }

    public function getPlayerBySlot ($slot)
    {
        if (! ($slot = $this->game->slots [$slot])) {
            return NULL;
        }

        return $this->getPlayerById ($slot->playerId);
    }

    public function getLength ()
    {
        return $this->header->length;
    }
}

?>