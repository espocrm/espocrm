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

	private $params;

	private $foreignParams;

	protected $allowParams = array();


	public function __construct(\Espo\Core\Utils\Metadata $metadata)
	{
		$this->metadata = $metadata;
	}

	protected function getMetadata()
	{
		return $this->metadata;
	}

	protected function getParams()
	{
		return $this->params;
	}

	protected function getForeignParams()
	{
		return $this->foreignParams;
	}

	protected function setParams(array $params)
	{
		$this->params = $params;
	}

	protected function setForeignParams(array $foreignParams)
	{
		$this->foreignParams = $foreignParams;
	}


	public function process($params, $foreignParams)
	{
		$this->setParams($params);
		$this->setForeignParams($foreignParams);

		$loads = $this->load($params, $foreignParams);

		$loads = $this->mergeAllowedParams($loads);

		return $loads;
	}

	private function mergeAllowedParams($loads)
	{
		$params = $this->getParams();

		if (!empty($this->allowParams)) {
			$linkParams = &$loads[$params['entityName']] ['relations'] [$params['link']['name']];

			foreach ($this->allowParams as $name) {

				$additionalParrams = $this->getAllowedAdditionalParams($name);

				if (isset($additionalParrams) && !isset($linkParams[$name])) {
					$linkParams[$name] = $additionalParrams;
				}
			}
		}

		return $loads;
	}

	private function getAllowedAdditionalParams($allowedItemName)
	{
		$params = $this->getParams();
		$foreignParams = $this->getForeignParams();

		$linkParams = isset($params['link']['params'][$allowedItemName]) ? $params['link']['params'][$allowedItemName] : null;
		$foreignLinkParams = isset($foreignParams['link']['params'][$allowedItemName]) ? $foreignParams['link']['params'][$allowedItemName] : null;

		$additionalParrams = null;

		if (isset($linkParams) && isset($foreignLinkParams)) {
			$additionalParrams = Util::merge($linkParams, $foreignLinkParams);
		} else if (isset($linkParams)) {
			$additionalParrams = $linkParams;
		} else if (isset($foreignLinkParams)) {
			$additionalParrams = $foreignLinkParams;
		}

		return $additionalParrams;
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
