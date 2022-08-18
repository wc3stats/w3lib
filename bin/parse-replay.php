<?php

require 'base.php';

use Monolog\Logger as Monolog;
use w3lib\Library\Logger;
use w3lib\w3g\Replay;
use w3lib\w3g\Lang;
use w3lib\w3g\Settings;

define ('REPLAY_FILE', getcwd () . '/' . $argv [1]);

$opts = getopt ('f:', [ 'file:' ]);
$file = getcwd () . '/' . ($opts ['f'] ?? $opts ['file'] ?? null);

if (!is_file ($file)) {
    printf ('File not found: \'%s\'.' . PHP_EOL, $file);
    die ();
}

// Logger::setup (Monolog::DEBUG);

$settings = new Settings ();
$replay   = new Replay ($file, $settings);

/** **/

echo PHP_EOL;

Logger::info ('File: [%s]',              $replay->getFile ());
Logger::info ('File Size: [%s]',         $replay->getSize ());
Logger::info ('Compressed Size: [%s]',   $replay->header->compressedSize);
Logger::info ('Uncompressed Size: [%s]', $replay->header->uncompressedSize);

Logger::info ('Game Name: [%s]',    $replay->game->name);
Logger::info ('Major Version [%d]', $replay->header->majorVersion);
Logger::info ('Build Version [%d]', $replay->header->buildVersion);
Logger::info ('Is Local: [%s]',     $replay->isLocal () ? 'Yes' : 'No');
Logger::info ('Is Ladder: [%s]',    $replay->isLadder () ? 'Yes' : 'No');
Logger::info ('Is FFA: [%s]',       $replay->isFFA () ? 'Yes' : 'No');
Logger::info ('Is Private: [%s]',   $replay->isPrivate () ? 'Yes' : 'No');
Logger::info ('Num Players: [%d]',  count ($replay->getPlayers ()));
Logger::info ('Hash: [%s]',         $replay->getHash ());
Logger::info ('Map File: [%s]',     $replay->game->map);
Logger::info ('Map Type: [%s]',     $replay->getMap ());
Logger::info ('Map Version: [%s]',  $replay->getVersion ());
Logger::info ('Game Type: [%s]',    Lang::gameType ($replay->game->type));
Logger::info ('Map Checksum: [%s]', $replay->game->checksum);
Logger::info ('Saver Id: [%s]',     $replay->game->saver);
Logger::info ('Host Id: [%s]',      $replay->game->host);
Logger::info ('W3MMD: [%s]',        $replay->hasW3mmd () ? 'Yes' : 'No');
Logger::info ('W3MMD Events: [%s]', count ($replay->game->events));

echo PHP_EOL;

foreach ($replay->getPlayers () as $player) {
    Logger::info (
        'Id: %2d | Slot: %2d | Colour: %2d %-10s | Player: %-20s | Team: %2d | APM: %4d | Winner: %s | Left: %6d | | Stayed: %3.2f | Vars: %2d | Obs: %3s',
        $player->id,
        $player->slot,
        $player->colour,
        Lang::colour ($player->colour),
        $player->name,
        $player->team,
        $player->apm,
	in_array ('winner', $player->flags) ? 'Yes' : 'No',
        $player->leftAt,
        $player->stayPercent,
        count ($player->variables ?? []),
        $player->isObserver ? 'Yes' : 'No'
    );

    // var_dump($player->flags);
    // var_dump($player->variables);
}

echo PHP_EOL;

Logger::info ('Memory usage: [%d]', memory_get_usage ());

?>
