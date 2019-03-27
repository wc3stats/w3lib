<?php

namespace w3lib\Library;

use Exception;
use ReflectionClass;
use JsonSerializable;

abstract class Model implements JsonSerializable
{ 
    private $_ref;

    public function __construct ()
    {
        $this->_ref = new ReflectionClass (get_class ($this));
    } 

    public function __sleep ()
    {
        return array_keys ((array) $this);
    }

    public abstract function read (Stream $stream);

    public static function unpack (Stream $stream)
    {
        $model = get_called_class ();
        $model = new $model ();

        $offset = $stream->offset ();

        try {
            $model->read ($stream);
        } catch (Exception $e) {
            $stream->seek ($offset);
            throw $e;
        }

        return $model;
    }

    public static function unpackAll (Stream $stream)
    {
        for ($i = 1; /* */ ; $i++) {
            try {
                Logger::debug (
                    'Unpacking [%d:%s]',
                    $i,
                    get_called_class ()
                );

                yield static::unpack ($stream);
            } catch (Exception $e) {
                Logger::debug ($e->getMessage ());
                return;
            }
        }
    }

    protected function keyName ($value)
    {
        $keys = [ ];

        foreach ($this->_ref->getConstants () as $k => $v) {
            if ($v === $value) {
                $keys [] = $k;
            }
        }

        return implode (':', $keys) ?? '?';
    }

    /** **/

    public function jsonSerialize ()
    {
        return array_intersect_key (
            get_object_vars ($this),
            
            array_combine (
                $this->__sleep (),
                $this->__sleep ()
            )
        );
    }
}

?>