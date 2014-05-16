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

namespace Espo\Repositories;

use Espo\ORM\Entity;

class EmailAddress extends \Espo\Core\ORM\Repositories\RDB
{
	public function getIds($arr = array())
	{		
		$ids = array();		
		if (!empty($arr)) {
			$a = array_map(function ($item) {
					return strtolower($item);
				}, $arr);
			$eas = $this->where(array(
				'lower' => array_map(function ($item) {
					return strtolower($item);
				}, $arr)
			))->find();
			$ids = array();
			$exist = array();
			foreach ($eas as $ea) {
				$ids[] = $ea->id;
				$exist[] = $ea->get('lower');
			}
			foreach ($arr as $address) {
				if (empty($address) || !filter_var($address, FILTER_VALIDATE_EMAIL)) {
					continue;
				}
				if (!in_array(strtolower($address), $exist)) {
					$ea = $this->get();
					$ea->set('name', $address);
					$this->save($ea);
					$ids[] = $ea->id;
				}
			}
		}
		return $ids;
	}
	
	public function getEmailAddressData(Entity $entity)
	{
		$data = array();
		
		$pdo = $this->getEntityManager()->getPDO();		
		$sql = "
			SELECT email_address.name, email_address.invalid, email_address.opt_out AS optOut, entity_email_address.primary 
			FROM entity_email_address
			JOIN email_address ON email_address.id = entity_email_address.email_address_id AND email_address.deleted = 0
			WHERE 
			entity_email_address.entity_id = ".$pdo->quote($entity->id)." AND 
			entity_email_address.entity_type = ".$pdo->quote($entity->getEntityName())." AND 
			entity_email_address.deleted = 0
			ORDER BY entity_email_address.primary DESC
		";
		$sth = $pdo->prepare($sql);
		$sth->execute();
		if ($rows = $sth->fetchAll()) {
			foreach ($rows as $row) {
				$obj = new \StdClass();
				$obj->emailAddress = $row['name'];
				$obj->primary = ($row['primary'] == '1') ? true : false;
				$obj->optOut = ($row['optOut'] == '1') ? true : false;
				$obj->invalid = ($row['invalid'] == '1') ? true : false;				
				$data[] = $obj;
			}
		}
		
		return $data;
	}
}

