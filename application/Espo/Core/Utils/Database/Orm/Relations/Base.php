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

namespace Espo\Core\Utils\Database\Orm\Relations;

class Base extends \Espo\Core\Utils\Database\Orm\Base
{
	private $params;

	private $foreignParams;

	protected $foreignLinkName = null;
	protected $foreignEntityName = null;

	protected $allowedParams = array(
		'relationName',
		'conditions',
		'additionalColumns',
		'midKeys',
	);

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

	protected function setForeignLinkName($foreignLinkName)
	{
		$this->foreignLinkName = $foreignLinkName;
	}

	protected function getForeignLinkName()
	{
		return $this->foreignLinkName;
	}

	protected function setForeignEntityName($foreignEntityName)
	{
		$this->foreignEntityName = $foreignEntityName;
	}

	protected function getForeignEntityName()
	{
		return $this->foreignEntityName;
	}

	protected function getForeignLinkParams()
	{
		$foreignLinkName = $this->getForeignLinkName();
		$foreignEntityName = $this->getForeignEntityName();
		$foreignLinkParams = $this->getLinkParams($foreignLinkName, $foreignEntityName);

		return $foreignLinkParams;
	}

	public function process($linkName, $entityName, $foreignLinkName, $foreignEntityName)
	{
		$inputs = array(
			'itemName' => $linkName,
			'entityName' => $entityName,
			'foreignLinkName' => $foreignLinkName,
			'foreignEntityName' => $foreignEntityName,
		);
		$this->setMethods($inputs);

		$convertedDefs = $this->load($linkName, $entityName);
		$convertedDefs = $this->mergeAllowedParams($convertedDefs);

		$inputs = $this->setArrayValue(null, $inputs);
		$this->setMethods($inputs);

		return $convertedDefs;
	}

	private function mergeAllowedParams($loads)
	{
		$linkName = $this->getLinkName();
		$entityName = $this->getEntityName();

		if (!empty($this->allowedParams)) {
			$linkParams = &$loads[$entityName]['relations'][$linkName];

			foreach ($this->allowedParams as $name) {

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
		$linkParams = $this->getLinkParams();
		$foreignLinkParams = $this->getForeignLinkParams();

		$itemLinkParams = isset($linkParams[$allowedItemName]) ? $linkParams[$allowedItemName] : null;
		$itemForeignLinkParams = isset($foreignLinkParams[$allowedItemName]) ? $foreignLinkParams[$allowedItemName] : null;

		$additionalParrams = null;

		if (isset($itemLinkParams) && isset($itemForeignLinkParams)) {
			$additionalParrams = Util::merge($itemLinkParams, $itemForeignLinkParams);
		} else if (isset($itemLinkParams)) {
			$additionalParrams = $itemLinkParams;
		} else if (isset($itemForeignLinkParams)) {
			$additionalParrams = $itemForeignLinkParams;
		}

		return $additionalParrams;
	}

}