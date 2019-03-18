<?php

require dirname (__DIR__) . '/vendor/autoload.php';

use Monolog\Logger as Monolog;
use w3lib\Library\Logger;
use w3lib\w3g\Replay;

Logger::setup (Monolog::INFO);

$replay = new Replay (__DIR__ . '/AzerothWars-2.w3g');

foreach ($replay->players as $player) {
    Logger::info (
        'Player: %-16s | APM: %4d | Left: %6d',
        $player->name,
        $player->apm (),
        $player->leftAt
    );
}

?>