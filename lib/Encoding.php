<?php

namespace w3lib\Library;

use w3lib\Library\Stream\Buffer;

abstract class Encoding
{
    /**
     * Defined in docs/w3g_format.txt [4.3] - Encoded String.
     */
    public static function decodeString ($encoded)
    {
        $decoded = new Buffer ();

        for ($i = 0, $cc = strlen ($encoded); $i < $cc; $i++) {
            if ($i % 8 === 0) {
                $mask = ord ($encoded [$i]);
            } else {
                $decoded->append (chr (ord ($encoded [$i]) - !($mask & (1 << $i % 8))));
            }
        }

        return $decoded;
    }
}

?>