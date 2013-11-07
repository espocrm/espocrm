<?php

namespace Espo\Utils\Controllers;

use Espo\Utils as Utils;

class Metadata extends Controller
{

    public function read($params, $data)
	{
		$metadata= new Utils\Metadata();
        $devMode= !$metadata->getObject('Configurator')->get('useCache');

		$data= $metadata->getMetadata(true, $devMode);

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