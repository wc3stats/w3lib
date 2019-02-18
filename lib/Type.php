<?php

namespace w3lib\Library;

abstract class Type
{
	protected $_size;

	public function __construct ($size = NULL)
	{
		$this->_size = $size;
	}

	public abstract function read (Stream $stream);
}

?>