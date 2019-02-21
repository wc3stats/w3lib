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
    			$type->unpack ($stream);
    		}

    		if ($type instanceof Type) {
    			$this->$key = $type->read ($stream);
                $this->resolve ();
    		}
    	}
    }

    public function resolve ()
    {
        $instance = get_object_vars ($this);

        foreach ($instance as $key => $type) {
            if (! ($type instanceof Type)) {
                continue;
            }

            $size = $type->getSize ();

            if (!is_string ($size)) {
                continue;
            }

            $type->setSize ($this->$size);
        }
    }
}

?>