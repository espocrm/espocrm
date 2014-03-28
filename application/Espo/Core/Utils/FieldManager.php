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

namespace Espo\Core\Utils;

class FieldManager
{
	private $metadata;

	protected $metadataType = 'entityDefs';


	public function __construct(Metadata $metadata)
	{
		$this->metadata = $metadata;
	}

	protected function getMetadata()
	{
		return $this->metadata;
	}


	public function read($name, $scope)
	{
		return $this->getMetadata()->get($this->metadataType.'.'.$scope.'.fields.'.$name);
	}

	public function create($name, $fieldDef, $scope)
	{
		return $this->update($name, $fieldDef, $scope);
	}

	public function update($name, $fieldDef, $scope)
	{
		$defs = $this->normalizeDefs($name, $fieldDef);

		return $this->setEntityDefs($defs, $scope);
	}

	public function delete($name, $scope)
	{
		$unsets = 'fields.'.$name;

		return $this->getMetadata()->unsets($unsets, $this->metadataType, $scope);
	}

	/**
	 * Add all needed block for a field defenition
	 *
	 * @param string $fieldName
	 * @param array $fieldDef
	 * @return array
	 */
	protected function normalizeDefs($fieldName, array $fieldDef)
	{
		return array(
			'fields' => array(
				$fieldName => $fieldDef,
			),
		);
	}


	protected function setEntityDefs($defs, $scope)
	{
		$data = Json::encode($defs);
		$result = $this->getMetadata()->set($data, $this->metadataType, $scope);

		return $result;
	}


}