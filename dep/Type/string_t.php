<?php

namespace w3lib\Library\Type;

use w3lib\Library\Type;
use w3lib\Library\Stream;

class string_t extends Type
{
	private const NUL = 0x00;

	public function read (Stream $stream)
	{
		if ($this->_size) {
			return $stream->read ($this->_size);
		}

		$block = '';

		while (($c = $stream->read (1)) !== FALSE) {
			if (ord ($c) == self::NUL) {
				break;
			}

			$block .= $c;
		}

		return $block;
	}
}

?>