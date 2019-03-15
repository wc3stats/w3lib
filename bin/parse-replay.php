<?php

require dirname (__DIR__) . '/vendor/autoload.php';

use Monolog\Logger as Monolog;
use w3lib\Library\Logger;
use w3lib\w3g\Replay;

Logger::setup (Monolog::INFO);

$replay = new Replay (__DIR__ . '/LastReplay.w3g');

var_dump ($replay->getLength ());
var_dump ($replay->chatlog);
var_dump ($replay->getPlayerById (5)->apm ());
var_dump ($replay->getPlayerById (5)->variables);
die ();


var_dump ($replay);
die ();

?>