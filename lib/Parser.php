<?php

namespace w3lib\Library;

use Exception;

class Parser
{
    private static $_primitives = [
        'char',
        'int',
        'float',
        'double'
    ];

    private static $_structs = [
        'string',
        'array',
        'bit',
        'buffer'
    ];

    private $_types = [];

    public function __construct (array $types = [])
    {
        $this->_types = $types;
    }

    public function unpack (Stream $stream)
    {
        $data = [];

        foreach ($this->_types as $key => $type) {
            if ($type instanceof Parser) {
                throw new Exception ('Not implemented.');
            } else if ($type instanceof Type) {
                $data [$key] = $type->read ($stream);
            }
        }

        return $data;
    }

    public static function __callStatic ($name, array $arguments)
    {
        if (!preg_match ('/([u]?)([a-z]+)(\d*)((?:le|be)?)/i', $name, $matches)) {
            throw new Exception ("Referenced unknown type (1): $name");
        }

        $sign   = $matches [1];
        $class  = $matches [2];
        $size   = $matches [3];
        $endian = $matches [4];

        $ns = Type::class . '\\' . $class . '_t';

        if (!class_exists ($ns)) {
            throw new Exception ("Type driver not found: $ns");
        }

        if (in_array ($class, self::$_primitives)) {
            $flags = 0x00;

            if ($sign === 'u') {
                $flags |= Primitive::T_U;
            } else {
                $flags |= Primitive::T_S;
            }

            switch ($size) {
                default:
                case 8:  $flags |= Primitive::T_S8;  break;
                case 16: $flags |= Primitive::T_S16; break;
                case 32: $flags |= Primitive::T_S32; break;
                case 64: $flags |= Primitive::T_S64; break;
            }

            if ($endian === 'be') {
                $flags |= Primitive::T_BE;
            } else {
                $flags |= Primitive::T_LE;
            }

            return new $ns ($flags, ... $arguments);
        } else if (in_array ($name, self::$_structs)) {
            return new $ns (... $arguments);
        }

        throw new Exception ("Referenced unknown type (2): $name");
    }
}

?>