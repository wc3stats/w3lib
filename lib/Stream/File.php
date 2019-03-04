<?php

namespace w3lib\Library\Stream;

use Exception;
use w3lib\Library\Stream;

class File extends Stream
{
    protected $_filepath;

    public function __construct (string $filepath)
    {
        $this->_filepath = $filepath;

        $stream = fopen ($filepath, 'rb');

        if (!$stream) {
            throw new Exception (
                sprintf (
                    'Failed to open archive: [%s] with error: [%s]',
                    $path,
                    error_get_last () ['message'] ?? 'Unknown'
                )
            );
        } 

        parent::__construct ($stream);
    }
}

?>