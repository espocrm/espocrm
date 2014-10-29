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
namespace Espo\Core\ExternalAccount;

use Espo\Core\Exceptions\Error;
use Espo\Core\ExternalAccount\OAuth2\Client;

class ClientManager
{

    protected $entityManager;

    protected $metadata;

    protected $clientMap = array();

    public function __construct($entityManager, $metadata, $config)
    {
        $this->entityManager = $entityManager;
        $this->metadata = $metadata;
        $this->config = $config;
    }

    public function storeAccessToken($hash, $data)
    {
        if (!empty($this->clientMap[$hash]) && !empty($this->clientMap[$hash]['externalAccountEntity'])) {
            $externalAccountEntity = $this->clientMap[$hash]['externalAccountEntity'];
            $externalAccountEntity->set('accessToken', $data['accessToken']);
            $externalAccountEntity->set('tokenType', $data['tokenType']);
            $this->getEntityManager()->saveEntity($externalAccountEntity);
        }
    }

    protected function getEntityManager()
    {
        return $this->entityManager;
    }

    public function create($integration, $userId)
    {
        $authMethod = $this->getMetadata()->get("integrations.{$integration}.authMethod");
        $methodName = 'create' . ucfirst($authMethod);
        return $this->$methodName($integration, $userId);
    }

    protected function getMetadata()
    {
        return $this->metadata;
    }

    protected function createOAuth2($integration, $userId)
    {
        $integrationEntity = $this->getEntityManager()->getEntity('Integration', $integration);
        $externalAccountEntity = $this->getEntityManager()->getEntity('ExternalAccount', $integration . '__' . $userId);
        $className = $this->getMetadata()->get("integrations.{$integration}.clientClassName");
        $redirectUri = $this->getConfig()->get('siteUrl') . '/oauthcallback'; // TODO move to client class
        if (!$externalAccountEntity) {
            throw new Error("External Account {$integration} not found for {$userId}");
        }
        if (!$integrationEntity->get('enabled')) {
            return null;
        }
        if (!$externalAccountEntity->get('enabled')) {
            return null;
        }
        $oauth2Client = new Client();
        $client = new $className($oauth2Client, array(
            'endpoint' => $this->getMetadata()->get("integrations.{$integration}.params.endpoint"),
            'tokenEndpoint' => $this->getMetadata()->get("integrations.{$integration}.params.tokenEndpoint"),
            'clientId' => $integrationEntity->get('clientId'),
            'clientSecret' => $integrationEntity->get('clientSecret'),
            'redirectUri' => $redirectUri,
            'accessToken' => $externalAccountEntity->get('accessToken'),
            'refreshToken' => $externalAccountEntity->get('refreshToken'),
            'tokenType' => $externalAccountEntity->get('tokenType'),
        ), $this);
        $this->addToClientMap($client, $integrationEntity, $externalAccountEntity, $userId);
        return $client;
    }

    protected function getConfig()
    {
        return $this->config;
    }

    protected function addToClientMap($client, $integrationEntity, $externalAccountEntity, $userId)
    {
        $this->clientMap[spl_object_hash($client)] = array(
            'client' => $client,
            'userId' => $userId,
            'integration' => $integrationEntity->id,
            'integrationEntity' => $integrationEntity,
            'externalAccountEntity' => $externalAccountEntity,
        );
    }
}

