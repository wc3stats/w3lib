<?php

require 'base.php';

use w3lib\Library\Stream\Buffer;
use w3lib\Gamelist;

$record = base64_decode ($argv [1]);
$record = new Buffer ($record);
$record = Gamelist::unpack ($record);

var_dump ($record);
die ();

?>