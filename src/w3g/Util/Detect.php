<?php

namespace w3lib\w3g\Util;

use w3lib\w3g\Replay;
use w3lib\w3g\Model\W3MMD;
use w3lib\Library\Logger;

class Detect
{
	public static function winner (
		Replay $replay,
		array $leaveSegments
	) {
        if ($replay->isLadder ()) {
            self::detectLadderWinner (
                $replay,
                $leaveSegments
            );
        }

        if ($replay->hasW3MMD ()) {
            self::detectW3MMDWinner (
                $replay
            );

            return;
        }

        // If one team has been identified as the winner, everyone else lost.
        $winningTeams = [];
        $losingTeams  = [];

        foreach ($replay->getPlayers () as $player) {
            if ($player->isWinner) {
                $winningTeams [] = $player->team;
            }

            if ($player->isWinner === false) {
                $losingTeams [] = $player->team;
            }
        }

        if (!empty ($winningTeams)) {
            // At least 1 team has been flagged as winner.
            foreach ($replay->getPlayers () as $player) {
                if ($player->isWinner === null) {
                    $player->isWinner = in_array ($player->team, $winningTeams);
                }
            }
        } else if (!empty ($losingTeams)) {
            // In PvE games, it is possible that everyone lost.
            foreach ($replay->getPlayers () as $player) {
                $player->isWinner = false;
            }
        }

        // Make sure everyone on the same team has the same isWinner value.
        foreach ($replay->getPlayers () as $player) {
            $allies = $replay
                ->getPlayersByTeam ($player->team);

            $highScore = NULL;

            foreach ($allies as $ally) {
                if ($ally->isWinner !== NULL) {
                    $highScore = max ($ally->isWinner, $highScore);
                }
            }

            $player->isWinner = $highScore;
        }
    }

    protected static function detectLadderWinner (
        Replay $replay,
        array $leaveSegments
    ) {
        $saver = $replay->getSaver ();
        $teams = [];

        foreach ($leaveSegments as $segment) {
            $player = $replay->getPlayerById ($segment->playerId);

            if (!$player) {
                Logger::warn (
                    sprintf (
                        'Found leave segment for unknown pid: [%s].',
                        $segment->playerId
                    )
                );
                continue;
            }

            if (!in_array ($player->team, $teams)) {
                $teams [] = $player->team;
            }

            switch ($segment->reason) {
                /*
                    0x01    |  0x01  | player left (disconnected or saver is observer)    [O]
                  (remote)  |  0x07  | player left
                            |  0x08  | player lost (was completly erased)
                            |  0x09  | player won
                            |  0x0A  | draw (long lasting tournament game)
                            |  0x0B  | player left (was observer)                         [?]
                */
                case 0x01:
                    $player->isWinner = [
                        0x01 => FALSE,
                        0x07 => FALSE,
                        0x08 => FALSE,
                        0x09 => TRUE,
                        0x0A => FALSE,
                        0x0B => NULL
                    ] [$segment->result] ?? NULL;
                break;

                /*
                    0x0E    |  0x01  | player left                                        [?]
                  (remote)  |  0x07  | player left                                        [?]
                            |  0x0B  | player left (was observer)                         [?]
                */
                case 0x0E:
                    $player->isWinner = [
                        0x01 => FALSE,
                        0x07 => FALSE,
                        0x0B => NULL
                    ] [$segment->result] ?? NULL;
                break;

                case 0x0C:
                    if ($saver->id === $player->id) {
                        /*
                         -----------+--------+-------------------------------------------------------
                          last 0x0C |  0x01  | saver disconnected
                         (rep.saver)|  0x07  | with INC => saver won
                                    |        | w/o  INC => saver lost
                                    |  0x08  | saver lost (completly erased)
                                    |  0x09  | saver won
                                    |  0x0B  | with INC => saver won most times, but not always   [?]
                                    |        | w/o  INC => saver left (was obs or obs on defeat)  [?]
                        */
                        $player->isWinner = [
                            0x01 => NULL,
                            0x07 => $segment->flagged,
                            0x08 => FALSE,
                            0x09 => TRUE,
                            0x0B => FALSE
                        ] [$segment->result] ?? NULL;
                    } else {
                        /*
                            0x0C    |  0x01  | saver disc. / observer left                        [O]
                          (not last)|  0x07  | saver lost, no info about the player               [?]
                                    |  0x08  | saver lost (erased), no info about the player
                                    |  0x09  | saver won, no info about the player
                                    |  0x0A  | draw (long lasting tournament game)
                                    |  0x0B  | saver lost (obs or obs on defeat)                  [?]
                        */
                        switch ($segment->result) {
                            default:
                                $player->isWinner = [
                                    0x01 => FALSE,
                                    0x0A => NULL,
                                    0x0B => $segment->flagged
                                ] [$segment->result] ?? NULL;
                            break;
                            case 0x07:
                            case 0x08:
                                $saver->isWinner = FALSE;
                            break;
                            case 0x09:
                                $saver->isWinner = TRUE;
                            break;
                        }
                    }
                break;
            }
        }

        if (
            $saver->isWinner !== NULL &&
            count ($teams) <= 2
        ) {
            // Set everyone on the savers' team to the same value.
            // Set everyone on the savers' opponent team to the opposite value.
            foreach ($replay->getPlayers () as $player) {
                if ($player->team === $saver->team) {
                    $player->isWinner = $saver->isWinner;
                } else {
                    $player->isWinner = !$saver->isWinner;
                }
            }
        }
    }

    protected static function detectW3MMDWinner (Replay $replay)
    {
        foreach ($replay->getPlayers () as $player) {
            if ($player->hasFlag (W3MMD::FLAG_WINNER)) {
                $player->isWinner = true;
            } else if ($player->hasFlag (W3MMD::FLAG_LOSER)) {
                $player->isWinner = false;
            }
        }
    }
}

?>
