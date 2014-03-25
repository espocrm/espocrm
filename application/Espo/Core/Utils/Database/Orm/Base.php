<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014  Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
 *
 * EspoCRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * EspoCRM is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with EspoCRM. If not, see http://www.gnu.org/licenses/.
 ************************************************************************/ 

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
