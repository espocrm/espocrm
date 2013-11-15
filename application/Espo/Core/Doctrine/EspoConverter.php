<?php

namespace Espo\Core\Doctrine;

class EspoConverter
{
	private $metadata;

    public function __construct(\Espo\Core\Utils\Metadata $metadata)
	{
		$this->metadata = $metadata;
	}

	public function getMetadata()
	{
		return $this->metadata;
	}

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
		$entityFullName= $this->getMetadata()->getEntityPath($name, '\\');   

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