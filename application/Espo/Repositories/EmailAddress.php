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
}

