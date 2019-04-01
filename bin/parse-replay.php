<?php

require dirname (__DIR__) . '/vendor/autoload.php';

use Monolog\Logger as Monolog;
use w3lib\Library\Logger;
use w3lib\w3g\Replay;
use w3lib\w3g\Settings;

define ('REPLAY_FILE', __DIR__ . '/BrokenAlliances-w3mmd.w3g');

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
Logger::info ('Num Players: [%d]', count ($replay->game->players));
Logger::info ('Hash: [%s]',        $replay->getHash ());
Logger::info ('Map File: [%s]',    $replay->game->map);
Logger::info ('Map Type: [%s]',    $replay->getMap ());
Logger::info ('Saver Id: [%s]',    $replay->game->saver);
Logger::info ('Host Id: [%s]',     $replay->game->host);

echo PHP_EOL;

foreach ($replay->game->players as $player) {
    Logger::info (
        'Id: %2d | Slot: %2d | Player: %-16s | APM: %4d | Left: %6d | Vars: %2d',
        $player->id,
        $player->slot,
        $player->name,
        $player->apm (),
        $player->leftAt,
        count ($player->variables)
    );
}

echo PHP_EOL;

Logger::info ('Memory usage: [%d]', memory_get_usage ());

?>
