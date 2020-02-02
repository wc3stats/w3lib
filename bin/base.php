<?php

require dirname (__DIR__) . '/vendor/autoload.php';

use Monolog\Logger as Monolog;
use w3lib\Library\Logger;

error_reporting (E_ALL);
ini_set ('display_errors', 1);

$opts = getopt ('d:', [ 'debug' ]);

if (
    isset ($opts ['d']) ||
    isset ($opts ['debug'])
) {
    Logger::setup (Monolog::DEBUG);
} else {
    Logger::setup (Monolog::INFO);
}

?>