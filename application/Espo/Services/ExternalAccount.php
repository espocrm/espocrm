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

use \Espo\ORM\Entity;

use \Espo\Core\Exceptions\Error;
use \Espo\Core\Exceptions\Forbidden;
use \Espo\Core\Exceptions\NotFound;

class ExternalAccount extends Record
{	
	protected function getClient($integration, $id)
	{		
		$integrationEntity = $this->getEntityManager()->getEntity('Integration', $integration);
		
		if (!$integrationEntity) {
			throw new NotFound();
		}
		$d = $integrationEntity->toArray();
		
		if (!$integrationEntity->get('enabled')) {
			throw new Error("{$integration} is disabled.");
		}
		
		$factory = new \Espo\Core\ExternalAccount\ClientFactory($this->getEntityManager(), $this->getMetadata(), $this->getConfig());		
		return $factory->create($integration, $id);
	}
	
	public function authorizationCode($integration, $userId, $code)
	{
		$entity = $this->getEntityManager()->getEntity('ExternalAccount', $integration . '__' . $userId);
		

		
		$client = $this->getClient($integration, $userId);
		if ($client instanceof \Espo\Core\ExternalAccount\Clients\OAuth2Abstract) {		
			$result = $client->getAccessTokenFromAuthorizationCode($code);
		
			print_r($result);
			die;
			
			return $result;
		} else {
			throw new Error("Could not load client for {$integration}.");
		}
	}
}

