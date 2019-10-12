<?php

require dirname (__DIR__) . '/vendor/autoload.php';

use Monolog\Logger as Monolog;
use w3lib\Library\Logger;
use w3lib\w3g\Replay;
use w3lib\w3g\Lang;
use w3lib\w3g\Settings;

// define ('REPLAY_FILE', __DIR__ . '/krur-krur21.w3g');
// define ('REPLAY_FILE', __DIR__ . '/Wc3addict-dihl6.w3g');
// define ('REPLAY_FILE', __DIR__ . '/Dota-2.w3g');
// define ('REPLAY_FILE', __DIR__ . '/broken.w3g');
// define ('REPLAY_FILE', __DIR__ . '/risk-2.w3g');
define ('REPLAY_FILE', __DIR__ . '/ltd-2.w3g');
// define ('REPLAY_FILE', __DIR__ . '/events.w3g');
// define ('REPLAY_FILE', __DIR__ . '/BrokenAlliances-w3mmd-4.w3g');

/** **/

error_reporting (E_ALL);
ini_set ('display_errors', 1);

Logger::setup (Monolog::INFO);
// Logger::setup (Monolog::DEBUG);

$settings = new Settings ();

// $settings->keepActions = true;

$replay = new Replay (REPLAY_FILE, $settings);

echo PHP_EOL;

Logger::info ('File: [%s]',              $replay->getFile ());
Logger::info ('Compressed Size: [%s]',   $replay->header->compressedSize);
Logger::info ('Uncompressed Size: [%s]', $replay->header->uncompressedSize);

Logger::info ('Game Name: [%s]',    $replay->game->name);
Logger::info ('Is Local: [%s]',     $replay->game->isLocal ? 'Yes' : 'No');
Logger::info ('Num Players: [%d]',  count ($replay->getPlayers ()));
Logger::info ('Hash: [%s]',         $replay->getHash ());
Logger::info ('Map File: [%s]',     $replay->game->map);
Logger::info ('Map Type: [%s]',     $replay->getMap ());
Logger::info ('Map Chec ksum: [%s]', $replay->game->checksum);
Logger::info ('Saver Id: [%s]',     $replay->game->saver);
Logger::info ('Host Id: [%s]',      $replay->game->host);
Logger::info ('W3MMD: [%s]',        $replay->game->hasW3mmd ? 'Yes' : 'No');
Logger::info ('W3MMD Events: [%s]', count ($replay->game->events));

echo PHP_EOL;

foreach ($replay->getPlayers () as $player) {
    Logger::info (
        'Id: %2d | Slot: %2d | Colour: %2d %-10s | Player: %-16s | Team: %2d | APM: %4d | Left: %6d | | Stayed: %3.2f | Vars: %2d',
        $player->id,
        $player->slot,
        $player->colour,
        Lang::colour ($player->colour),
        $player->name,
        $player->team,
        $player->apm,
        $player->leftAt,
        $player->stayPercent,
        count ($player->variables ?? [])
    );

    var_dump($player->flags);
    var_dump($player->variables);
}

// var_dump($replay->game->events);
// echo PHP_EOL;

Logger::info ('Memory usage: [%d]', memory_get_usage ());

?>
