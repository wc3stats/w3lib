<?php

use w3lib\Library\Logger;

function xxd ($block, $width = 16)
{
    $from = '';
    $to   = '';

    $offset = 0;

    if (!$from) {
        for ($i = 0; $i <= 0xFF; $i++) {
            $from .= chr ($i);
            $to   .= ($i >= 0x20 && $i <= 0x7E) ? chr ($i) : '.';
        }
    }

    $hex   = str_split (bin2hex ($block), $width * 2);
    $chars = str_split (strtr ($block, $from, $to), $width);

    foreach ($hex as $i => $line) {
        Logger::info (
            '%6X : %-s [%-s]',
            $offset,
            str_pad (implode (' ', str_split ($line, 2)), $width * 3 - 1),
            str_pad ($chars [$i], $width)
        );

        $offset += $width;
    }
}

?>