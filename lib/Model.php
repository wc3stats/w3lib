<?php

namespace w3lib\Library;

abstract class Model
{ 
    public static function unpack (Stream $stream)
    {
        $model = get_called_class ();
        $model = new $model ();

        $model->read ($stream);

        return $model;
    }

    public abstract function read (Stream $stream);
}

	// public function __construct (array $types = [])
	// {
	// 	foreach ($types as $key => $type) {
	// 		$this->$key = $type;
	// 	}
	// }

	// public function unpack (Stream $stream)
 //    {
 //    	$instance = get_object_vars ($this);

 //    	foreach ($instance as $key => $type) {
 //    		if ($type instanceof Model) {
 //    			$type->unpack ($stream);
 //    		}

 //    		if ($type instanceof Type) {
 //    			$this->$key = $type->read ($stream);
 //                $this->resolve ();
 //    		}
 //    	}
 //    }

 //    public function resolve ()
 //    {
 //        $instance = get_object_vars ($this);

 //        foreach ($instance as $key => $type) {
 //            if (! ($type instanceof Type)) {
 //                continue;
 //            }

 //            $size = $type->getSize ();

 //            if (!is_string ($size)) {
 //                continue;
 //            }

 //            $type->setSize ($this->$size);
 //        }
 //    }

?>