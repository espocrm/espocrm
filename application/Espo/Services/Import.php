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

namespace Espo\Services;

use \Espo\Core\Exceptions\Forbidden;
use \Espo\Core\Exceptions\NotFound;

use Espo\ORM\Entity;

class Import extends \Espo\Core\Services\Base
{	
	protected $dependencies = array(
		'entityManager',
		'user',
		'metadata',
		'acl',
		'selectManagerFactory',
		'config',
		'serviceFactory',
	);
	
	protected function getSelectManagerFactory()
	{
		return $this->injections['selectManagerFactory'];
	}

	protected function getEntityManager()
	{
		return $this->injections['entityManager'];
	}

	protected function getUser()
	{
		return $this->injections['user'];
	}
	
	protected function getAcl()
	{
		return $this->injections['acl'];
	}
	
	protected function getMetadata()
	{
		return $this->injections['metadata'];
	}
	
	protected function getConfig()
	{
		return $this->injections['config'];
	}	
	
	protected function getServiceFactory()
	{
		return $this->injections['serviceFactory'];
	}	
	
	public function import($scope, array $fields, $contents, array $params = array())
	{
		$delimiter = ',';
		if (!empty($params['fieldDelimiter'])) {
			$delimiter = $params['fieldDelimiter'];
		}
		$enclosure = '"';
		if (!empty($params['textQualifier'])) {
			$enclosure = $params['textQualifier'];
		}
		
		$lines = explode("\n", $contents);
		
		$result	= array(
			'importedIds' => array(),
			'updatedIds' => array(),
			'duplicateIds' => array(),			 
		);	
	
		foreach ($lines as $i => $line) {
			if ($i == 0 && !empty($params['headerRow'])) {
				continue;
			}
			$arr = str_getcsv($line, $delimiter, $enclosure);
			if (count($arr) == 1 && empty($arr[0])) {
				continue;
			}
			$r = $this->importRow($scope, $fields, $arr, $params);
			if (!empty($r['imported'])) {
				$result['importedIds'][] = $r['id'];
			}
			if (!empty($r['updated'])) {
				$result['updatedIds'][] = $r['id'];
			}
			if (!empty($r['duplicate'])) {
				$result['duplicateIds'][] = $r['id'];
			}		
		}
		print_r($result);
		die;
		
		return $result;	
	}
	
	public function importRow($scope, array $fields, array $row, array $params = array())
	{
		$entity = $this->getEntityManager()->getEntity($scope);
		$fieldsDefs = $entity->getFields();

		foreach ($fields as $i => $field) {
			if (!empty($field)) {
				$value = $row[$i];
			
				if (array_key_exists($field, $fieldsDefs)) {
					$entity->set($field, $value);		
				}
			}		
		}

		$result = array();
		
		$a = $entity->toArray();
		
		if ($this->getEntityManager()->saveEntity($entity)) {	
			$result['id'] = $entity->id;
			$result['imported'] = true;	
		}
		return $result;
	}
}

