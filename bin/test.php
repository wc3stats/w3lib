<?php

require 'base.php';

use w3lib\Library\Logger;
use w3lib\w3g\Replay;
use w3lib\w3g\Lang;
use w3lib\w3g\Settings;

$settings = new Settings ();
$r1   = new Replay ('st-5.w3g', $settings);
$r2   = new Replay ('st-6.w3g', $settings);
$r3   = new Replay ('st-7.w3g', $settings);

$r1->merge ($r2);
$r1->merge ($r3);

foreach ($r1->chatlog as $message) {
    echo $message->message . PHP_EOL;
}

?>
