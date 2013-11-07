<?php

namespace Espo\Controllers;

use Espo\Utils as Utils;

class Metadata extends Utils\Controllers\Controller
{

    public function read($params, $data)
	{
		$metadata= new Utils\Metadata();  

		$data= $metadata->getMetadata(true);

       	return array($data, 'Cannot reach metadata data');
	}


	public function update($params, $data)
	{  
		$metadata = new Utils\Metadata();
		$result = $metadata->setMetadata($data, $params['type'], $params['scope']);

		if ($result===false) {
        	return array($result, 'Cannot save metadata data');
        }

        $data= $metadata->getMetadata(true, true);
        return array($data, 'Cannot get metadata data');
	}   


}


?>