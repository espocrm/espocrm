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

namespace Espo\Entities;

class Email extends \Espo\Core\ORM\Entity
{

	protected function getSubject()
	{
		return $this->get('name');
	}
	
	protected function setSubject($value)
	{
		return $this->set('name', $value);
	}
	
	public function addAttachment(\Espo\Entities\Attachment $attachment)
	{
		if (!empty($this->id)) {
			$attachment->set('parentId', $this->id);
			$attachment->set('parentType', 'Email');
			if ($this->entityManager->saveEntity($attachment)) {
				return true;
			}
		}
	}
}

