<?php

namespace w3lib\Library;

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
        printf (
            '%6X : %-s [%-s]' . PHP_EOL,
            $offset,
            str_pad (implode (' ', str_split ($line, 2)), $width * 3 - 1),
            str_pad ($chars [$i], $width)
        );

        $offset += $width;
    }
}

function camelCase ($s)
{
    $s = preg_replace ('/[^a-z0-9]+/i', ' ', $s);
    $s = trim ($s);
    $s = ucwords ($s);
    $s = str_replace (" ", "", $s);
    $s = lcfirst ($s);

    return $s;
}

function inArrayInsensitive ($needle, $haystack)
{
    foreach ($haystack as $hay) {
        if (strcasecmp ($needle, $hay) === 0) {
            return TRUE;
        }
    }

    return FALSE;
}

?>