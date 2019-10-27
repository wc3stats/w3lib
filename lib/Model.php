<?php

namespace w3lib\Library;

use Exception;
use ReflectionClass;
use JsonSerializable;
use w3lib\Library\Exception\RecoverableException;
use w3lib\Library\Exception\StreamEmptyException;

abstract class Model implements JsonSerializable
{
    private $ref;

    public function __construct ()
    {
        $this->ref = new ReflectionClass (get_class ($this));
    }

    public function __sleep ()
    {
        return array_keys ((array) $this);
    }

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

    public abstract function read (Stream &$stream);

    public static function unpack (Stream &$stream)
    {
        $model = get_called_class ();
        $model = new $model ();

        // Logger::debug (
        //     'Unpacking [%s]',
        //     get_class ($model)
        // );

        $offset = $stream->offset ();

        try {
            $model->read ($stream);
        } catch (RecoverableException $e) {
            Logger::debug ('Recoverable Exception: ' . $e->getMessage ());
            return NULL;
        } catch (Exception $e) {
            $stream->seek ($offset);
            throw $e;
        }

        return $model;
    }

    public static function unpackAll (Stream &$stream)
    {
        for ($i = 1; /* */ ; $i++) {
            try {
                $model = static::unpack ($stream);

                if ($model) {
                    yield $model;
                }
            } catch (StreamEmptyException $e) {
                // Logger::debug ('Stream Empty Exception: ' . $e->getMessage ());
                return;
            } catch (Exception $e) {
                // if (Logger::isDebug ()) {
                    xxd ($stream);
                // }

                Logger::error ('Non-Recoverable Exception: ' . $e->getMessage ());
                return;
            }
        }
    }

    public function keyName ($value)
    {
        $keys = [ ];

        foreach ($this->ref->getConstants () as $k => $v) {
            if ($v === $value) {
                $keys [] = $k;
            }
        }

        return implode (':', $keys) ?? '?';
    }
}

?>
