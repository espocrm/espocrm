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

namespace Espo\Modules\Crm\Jobs;

use \Espo\Core\Exceptions;

class CheckInboundEmails extends \Espo\Core\Jobs\Base
{
	public function run()
	{	
		$service = $this->getServiceFactory()->create('InboundEmail');		
		$collection = $this->getEntityManager()->getRepository('InboundEmail')->where(array('status' => 'Active'))->find();
		foreach ($collection as $entity) {
			try {
				$service->fetchFromMailServer($entity);
			} catch (\Exception $e) {}
		}
		
		$service = $this->getServiceFactory()->create('EmailAccount');		
		$collection = $this->getEntityManager()->getRepository('EmailAccount')->where(array('status' => 'Active'))->find();
		foreach ($collection as $entity) {
			try {
				$service->fetchFromMailServer($entity);
			} catch (\Exception $e) {}
		}
		
		return true;
	}	
}

