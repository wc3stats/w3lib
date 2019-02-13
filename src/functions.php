<?php

function debug ($message)
{
    printf (
        "[%s] %s" . PHP_EOL,
        date ('Y-m-d H:i:s'),
        $message
    );
}

?>