<?php

namespace w3lib\Library\Stream;

use Exception;
use w3lib\Library\Stream;

class File extends Stream
{
    protected $filepath;
    protected $filesize;

    public function __construct (string $filepath)
    {
        $this->filepath = $filepath;
        $this->filesize = filesize ($filepath);

        $stream = fopen ($filepath, 'rb+');

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

    public function getFile ()
    {
        return realpath ($this->filepath);
    }

    public function getSize ()
    {
        return $this->filesize;
    }
}

?>