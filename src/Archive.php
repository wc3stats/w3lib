<?php

namespace w3lib;

use Exception;

class Archive
{
    protected $_fp;
    protected $_fh;

    public function __construct (string $filepath)
    {
        $this->_fp = $filepath;
        $this->_fh = fopen ($filepath, 'rb');

        if (!$this->_fh) {
            throw new Exception (
                sprintf (
                    'Failed to open archive: [%s] with error: [%s]',
                    $path,
                    error_get_last () ['message'] ?? 'Unknown'
                )
            );
        }

        flock ($this->_fh, LOCK_EX);
    }

    public function close ()
    {
        flock  ($this->_fh, LOCK_UN);
        fclose ($this->_fh);
    }
}

?>