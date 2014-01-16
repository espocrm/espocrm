<?php

namespace Espo\Core\Utils\Database\Orm;

use Espo\Core\Utils\Util;

class Base
{
	private $metadata;

	public function __construct(\Espo\Core\Utils\Metadata $metadata)
	{
    	$this->metadata = $metadata;
	}

	protected function getMetadata()
	{
		return $this->metadata;
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
