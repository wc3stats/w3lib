<?php

require dirname (__DIR__) . '/vendor/autoload.php';

use Monolog\Logger as Monolog;
use w3lib\Library\Logger;
use w3lib\w3g\Replay;
use w3lib\w3g\Parser;

Logger::setup (Monolog::INFO);

$replay = new Replay (
    __DIR__ . '/BrokenAlliances-1a.w3g'
);

echo PHP_EOL;

Logger::info ("File: [%s]",        $replay->getFile ());
Logger::info ("Game Name: [%s]",   $replay->game->name);
Logger::info ("Num Players: [%d]", count ($replay->players));
Logger::info ("Hash: [%s]",        $replay->getHash ());
Logger::info ("Map File: [%s]",    $replay->game->map);
Logger::info ("Map Type: [%s]",    $replay->getMap ());
Logger::info ("Saver Id: [%s]",    $replay->game->saver);

echo PHP_EOL;

foreach ($replay->players as $player) {
    Logger::info (
        'Player: %-16s | APM: %4d | Left: %6d',
        $player->name,
        $player->apm (),
        $player->leftAt
    );
}

?>
