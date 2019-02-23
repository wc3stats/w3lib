<?php

namespace w3lib\Library\Type;

use w3lib\Library\Primitive;

class char_t extends Primitive
{
	protected $_codes = [
		Primitive::T_U => [
			Primitive::T_LE => [
				Primitive::T_S8  => 'C',
				Primitive::T_S16 => NULL,
				Primitive::T_S32 => NULL,
				Primitive::T_S64 => NULL
			],

			Primitive::T_BE => [
				Primitive::T_S8  => 'C',
				Primitive::T_S16 => NULL,
				Primitive::T_S32 => NULL,
				Primitive::T_S64 => NULL
			]
		],

		Primitive::T_S => [
			Primitive::T_LE => [
				Primitive::T_S8 => 'c',
				Primitive::T_S16 => NULL,
				Primitive::T_S32 => NULL,
				Primitive::T_S64 => NULL
			],

			Primitive::T_BE => [
				Primitive::T_S8 => 'c',
				Primitive::T_S16 => NULL,
				Primitive::T_S32 => NULL,
				Primitive::T_S64 => NULL
			]
		]
	];
}

?>