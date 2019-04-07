<?php

namespace w3lib\w3g\Util;

use w3lib\w3g\Model\Player;

class Team
{
    public $id        = NULL;
    public $players   = [];
    public $score     = NULL;
    public $placement = NULL;
    public $isWinner  = false;

    public function __construct ($id)
    {
        $this->id = $id;
    }

    public function add (Player $player)
    {
        $this->players [] = $player;

        if ($player->isWinner) {
            $this->isWinner = true;
        }
        
        $this->refresh ();
    }

    public function get ($playerId) 
    {
        foreach ($this->players as $player) {
            if ($player->id === $playerId) {
                return $player;
            }
        }

        return NULL;
    }

    public function getPlayers ()
    {
        return $this->players;
    }

    public function getSize ()
    {
        return count ($this->players);
    }

    public function setPlacement ($placement)
    {
        $this->placement = $placement;
        $this->isWinner  = $placement === 1;

        foreach ($this->players as $player) {
            $player->placement = $placement;
            $player->isWinner  = $this->isWinner;
        }
    }

    private function refresh ()
    {
        $score = 0;
        $count = 0;

        foreach ($this->players as $player) {
            if ($player->score) {
                $score += $player->score;
                $count++;
            }
        }

        if ($count > 0) {
            $score = ceil ($score / $count);
        }

        $this->score = $score;
    }
}

?>