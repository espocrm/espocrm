<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: https://www.espocrm.com
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
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

namespace Espo\Services;

use \Espo\ORM\Entity;

use \Espo\Core\Exceptions\Error;
use \Espo\Core\Exceptions\Forbidden;
use \Espo\Core\Exceptions\NotFound;

class ExternalAccount extends Record
{
    protected function init()
    {
        parent::init();
        $this->addDependency('hookManager');
    }

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

        $factory = new \Espo\Core\ExternalAccount\ClientManager($this->getEntityManager(), $this->getMetadata(), $this->getConfig());
        return $factory->create($integration, $id);
    }

    public function getExternalAccountEntity($integration, $userId)
    {
        return $this->getEntityManager()->getEntity('ExternalAccount', $integration . '__' . $userId);
    }

    public function ping($integration, $userId)
    {
        $entity = $this->getExternalAccountEntity($integration, $userId);
        try {
            $client = $this->getClient($integration, $userId);
            if ($client) {
                return $client->ping();
            }
        } catch (\Exception $e) {}
    }

    public function authorizationCode($integration, $userId, $code)
    {
        $entity = $this->getExternalAccountEntity($integration, $userId);
        if (!$entity) {
            throw new NotFound();
        }
        $entity->set('enabled', true);
        $this->getEntityManager()->saveEntity($entity);

        $client = $this->getClient($integration, $userId);
        if ($client instanceof \Espo\Core\ExternalAccount\Clients\OAuth2Abstract) {
            $result = $client->getAccessTokenFromAuthorizationCode($code);
            if (!empty($result) && !empty($result['accessToken'])) {
                $entity->clear('accessToken');
                $entity->clear('refreshToken');
                $entity->clear('tokenType');
                foreach ($result as $name => $value) {
                    $entity->set($name, $value);
                }
                $this->getEntityManager()->saveEntity($entity);
                $this->getInjection('hookManager')->process('ExternalAccount', 'afterConnect', $entity, [
                    'integration' => $integration,
                    'userId' => $userId,
                    'code' => $code,
                ]);
                return true;
            } else {
                throw new Error("Could not get access token for {$integration}.");
            }
        } else {
            throw new Error("Could not load client for {$integration}.");
        }
    }
}
