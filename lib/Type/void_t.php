<?php

namespace w3lib\Library\Type;

use w3lib\Library\Type;
use w3lib\Library\Stream;

class void_t extends Type
{
	public function read (Stream $stream)
	{
		return $stream->read ($this->_size);
	}
}

?>