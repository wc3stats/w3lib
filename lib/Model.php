<?php

namespace w3lib\Library;

use Exception;

class Model
{
	public function __construct (array $types = [])
	{
		foreach ($types as $key => $type) {
			$this->$key = $type;
		}
	}

	public function unpack (Stream $stream)
    {
    	$instance = get_object_vars ($this);

    	foreach ($instance as $key => $type) {
    		if ($type instanceof Model) {
    			throw new Exception ('Not implemented.');
    		}

    		if ($type instanceof Type) {
    			$type->resolve ($this);
    			$this->$key = $type->read ($stream);
    		}
    	}

    	return $this;
    }
}

?>