<?php

namespace w3lib\Library;

use Exception;
use Monolog\Logger as Monolog;
use Monolog\Handler\StreamHandler;
use Bramus\Monolog\Formatter\ColoredLineFormatter;

class Logger
{
    private static $_instance;

    public static function __callStatic ($name, $arguments = [])
    {
        if (!self::$_instance) {
            $instance = new Monolog (NULL);

            $handler = new StreamHandler ('php://stdout', Monolog::DEBUG);

            $handler->setFormatter (
                new ColoredLineFormatter (NULL, "[%datetime%] %level_name% - %message% [%extra.file%:%extra.line%]\n")
            );

            $instance->pushProcessor (function ($record) {
                $trace = debug_backtrace () [4];

                $record ['extra'] ['file'] = basename ($trace ['file']);
                $record ['extra'] ['line'] = $trace ['line'];
                
                return $record;
            });

            $instance->pushHandler ($handler);

            self::$_instance = $instance;
        }

        if (!method_exists (self::$_instance, $name)) {
            throw new Exception ("Logger method not found: [$name].");
        }

        self::$_instance->$name (
            vsprintf (
                array_shift ($arguments),
                $arguments
            )
        );
    }
}

?>