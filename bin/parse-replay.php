<?php

require dirname (__DIR__) . '/vendor/autoload.php';

use Monolog\Logger as Monolog;
use w3lib\Library\Logger;
use w3lib\w3g\Replay;

Logger::setup (Monolog::INFO);

$replay = new Replay (__DIR__ . '/BrokenAlliances-1a.w3g');

var_dump ($replay);
die ();

?>