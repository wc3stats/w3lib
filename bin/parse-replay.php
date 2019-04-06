<?php

require dirname (__DIR__) . '/vendor/autoload.php';

use Monolog\Logger as Monolog;
use w3lib\Library\Logger;
use w3lib\w3g\Replay;
use w3lib\w3g\Lang;
use w3lib\w3g\Settings;

// define ('REPLAY_FILE', __DIR__ . '/AzerothWars-4.w3g');
// define ('REPLAY_FILE', __DIR__ . '/Hellhalt.w3g');
define ('REPLAY_FILE', __DIR__ . '/BrokenAlliances-w3mmd-1.w3g');

/** **/

error_reporting (E_ALL);
ini_set ('display_errors', 1);

Logger::setup (Monolog::INFO);

$settings = new Settings ();

// $settings->keepActions = true;

$replay = new Replay (REPLAY_FILE, $settings);

echo PHP_EOL;

Logger::info ('File: [%s]',        $replay->getFile ());
Logger::info ('Game Name: [%s]',   $replay->game->name);
Logger::info ('Num Players: [%d]', count ($replay->getPlayers ()));
Logger::info ('Hash: [%s]',        $replay->getHash ());
Logger::info ('Map File: [%s]',    $replay->game->map);
Logger::info ('Map Type: [%s]',    $replay->getMap ());
Logger::info ('Saver Id: [%s]',    $replay->game->saver);
Logger::info ('Host Id: [%s]',     $replay->game->host);
Logger::info ('W3MMD: [%s]',       $replay->game->w3mmd ? 'Yes' : 'No');

echo PHP_EOL;

foreach ($replay->getTeams () as $teamId => $team) {
    Logger::info (
        'Team: %d | Score: %4d | Placement: %2d | isWinner: %-3s | Size: %d',
        $teamId,
        $team->score,
        $team->placement,
        $team->isWinner ? 'Yes' : 'No',
        $team->getSize ()
    );

    foreach ($team->getPlayers () as $player) {
        Logger::info (
            'Id: %2d | Slot: %2d | Colour: %2d %-10s | Player: %-16s | Team: %2d | Score: %4d | Placement: %2d | APM: %4d | Left: %6d | Vars: %2d',
            $player->id,
            $player->slot,
            $player->colour,
            Lang::colour ($player->colour),
            $player->name,
            $player->team,
            $player->score,
            $player->placement,
            $player->apm,
            $player->leftAt,
            count ($player->variables ?? [])
        );
    }

    echo PHP_EOL;
}

echo PHP_EOL;

Logger::info ('Memory usage: [%d]', memory_get_usage ());

?>
