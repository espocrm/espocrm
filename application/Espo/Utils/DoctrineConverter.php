<?php

namespace Espo\Utils;
use Espo\Utils as Utils;

class DoctrineConverter extends BaseUtils
{
	/**
	* Metadata conversion from Espo format into Doctrine
    *
	* @param string $name
	* @param array $data
	* @param bool $withName - different return array. If $withName=false, then return is "array()"; $withName=true, then array('name'=>$entityName, 'meta'=>$doctrineMeta);
	*
	* @return array
	*/
	//NEED TO CHANGE
	function convert($name, $data, $withName=false)
	{
		//HERE SHOULD BE CONVERSION FUNCTIONALITY
        $metadata= $this->getObject('Metadata');

		$entityFullName= $metadata->getEntityPath($name, '\\');

		$doctrineMeta= array(
            $entityFullName => $data
		);

		if ($withName) {
			return array('name'=>$entityFullName, 'meta'=>$doctrineMeta);
		}
        return $doctrineMeta;
	}

}


?>