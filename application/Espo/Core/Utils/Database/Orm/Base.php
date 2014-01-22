<?php

namespace Espo\Core\Utils\Database\Orm;

use Espo\Core\Utils\Util;

class Base
{
	private $metadata;

	protected $allowParams = array();

	public function __construct(\Espo\Core\Utils\Metadata $metadata)
	{
    	$this->metadata = $metadata;
	}

	protected function getMetadata()
	{
		return $this->metadata;
	}


	public function process($params, $foreignParams)
	{				
		$load = $this->load($params, $foreignParams);		

		$load = $this->mergeAllowedParams($load, $params);

		return $load;		
	}


	private function mergeAllowedParams($load, $params)
	{		
		if (!empty($this->allowParams)) {
			$linkParams = &$load[$params['entityName']] ['relations'] [$params['link']['name']];
		
			foreach ($this->allowParams as $name) {
				if (isset($params['link']['params'][$name]) && !isset($linkParams[$name])) {
					$linkParams[$name] = $params['link']['params'][$name];	
				}
			} 
		}		

		return $load;
	}




    protected function getForeignField($name, $entityName)
	{
		$foreignField = $this->getMetadata()->get('entityDefs.'.$entityName.'.fields.'.$name);

		if ($foreignField['type'] != 'varchar') {
        	$fieldDefs = $this->getMetadata()->get('fields.'.$foreignField['type']);
            $naming = isset($fieldDefs['naming']) ? $fieldDefs['naming'] : 'postfix';

			if (isset($fieldDefs['actualFields']) && is_array($fieldDefs['actualFields'])) {
            	$foreignFieldArray = array();
				foreach($fieldDefs['actualFields'] as $fieldName) {
					if ($fieldName != 'salutation') {
                    	$foreignFieldArray[] = Util::getNaming($name, $fieldName, $naming);
					}
				}
				return explode('|', implode('| |', $foreignFieldArray)); //add an empty string between items
			}
		}

		return $name;
	}



}
