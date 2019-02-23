<?php

namespace w3lib\Library;

use Exception;

abstract class Type
{
	protected $_size;

	public function __construct ($size = NULL)
	{
		$this->_size = $size;
	}

	public function read (Stream $stream)
	{
        if (!is_int ($this->_size)) {
            throw new Exception (
                sprintf (
                    "Cannot read non-integer size: [%s]",
                    $this->_size
                )
            );
        }

        return $stream->read ($this->_size);
	}

    public function getSize ()
    {
        return $this->_size;
    }

    public function setSize ($size)
    {
        if (!is_int ($size) || $size < 0) {
            return false;
        }

        $this->_size = $size;
        return true;
    }

	/** **/

	public static function __callStatic ($name, array $arguments)
    {
        if (!preg_match ('/([u]?)([a-z]+)(\d*)((?:le|be)?)/i', $name, $matches)) {
            throw new Exception ("Referenced unknown type (1): $name");
        }

        $sign   = $matches [1];
        $class  = $matches [2];
        $size   = $matches [3];
        $endian = $matches [4];

        $ns = self::class . '\\' . $class . '_t';

        if (!class_exists ($ns)) {
            throw new Exception ("Type driver not found: $ns");
        }

        if (is_subclass_of ($ns, Primitive::class)) {
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
        }
            
        return new $ns (... $arguments);
    }
}

?>