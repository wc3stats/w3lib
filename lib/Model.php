<?php

namespace w3lib\Library;

use Exception;

abstract class Model
{ 
    public static function unpack (Stream $stream)
    {
        $model = get_called_class ();
        $model = new $model ();

        $model->read ($stream);

        return $model;
    }

    public static function unpackAll (Stream $stream)
    {
        while (true) {
            try {
                yield static::unpack ($stream);
            } catch (Exception $e) {
                return;
            }
        }
    }

    public abstract function read (Stream $stream);
}

?>