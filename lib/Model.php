<?php

namespace w3lib\Library;

abstract class Model
{ 
    public static function unpack (Stream $stream)
    {
        $model = get_called_class ();
        $model = new $model ();

        $model->read ($stream);

        return $model;
    }

    public abstract function read (Stream $stream);
}

?>