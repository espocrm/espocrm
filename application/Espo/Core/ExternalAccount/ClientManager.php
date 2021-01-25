<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2021 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

namespace Espo\Core\ExternalAccount;

use Espo\Core\Exceptions\Error;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\NotFound;

use Espo\Core\{
    ORM\EntityManager,
    Utils\Metadata,
    Utils\Config,
    InjectableFactory,
};

class ClientManager
{
    protected $entityManager;
    protected $metadata;
    protected $config;
    protected $injectableFactory = null;

    protected $clientMap = [];

    public function __construct(
        EntityManager $entityManager,
        Metadata $metadata,
        Config $config,
        ?InjectableFactory $injectableFactory = null
    ) {
        $this->entityManager = $entityManager;
        $this->metadata = $metadata;
        $this->config = $config;
        $this->injectableFactory = $injectableFactory;
    }

    protected function getMetadata()
    {
        return $this->metadata;
    }

    protected function getEntityManager()
    {
        return $this->entityManager;
    }

    protected function getConfig()
    {
        return $this->config;
    }

    public function storeAccessToken($hash, $data)
    {
        if (!empty($this->clientMap[$hash]) && !empty($this->clientMap[$hash]['externalAccountEntity'])) {
            $externalAccountEntity = $this->clientMap[$hash]['externalAccountEntity'];
            $externalAccountEntity->set('accessToken', $data['accessToken']);
            $externalAccountEntity->set('tokenType', $data['tokenType']);
            $externalAccountEntity->set('expiresAt', $data['expiresAt'] ?? null);

            if ($data['refreshToken'] ?? null) {
                $externalAccountEntity->set('refreshToken', $data['refreshToken']);
            }

            $copy = $this->getEntityManager()->getEntity('ExternalAccount', $externalAccountEntity->id);
            if ($copy) {
                if (!$copy->get('enabled')) {
                    throw new Error("External Account Client Manager: Account got disabled.");
                }

                $copy->set('accessToken', $data['accessToken']);
                $copy->set('tokenType', $data['tokenType']);
                $copy->set('expiresAt', $data['expiresAt'] ?? null);
                if ($data['refreshToken'] ?? null) {
                    $copy->set('refreshToken', $data['refreshToken'] ?? null);
                }
                $this->getEntityManager()->saveEntity($copy, ['isTokenRenewal' => true, 'skipHooks' => true]);
            }
        }
    }

    public function create(string $integration, string $userId)
    {
        $authMethod = $this->getMetadata()->get("integrations.{$integration}.authMethod");
        $methodName = 'create' . ucfirst($authMethod);

        if (method_exists($this, $methodName)) {
            return $this->$methodName($integration, $userId);
        }

        if (!$this->injectableFactory) {
            throw new Error();
        }

        $integrationEntity = $this->getEntityManager()->getEntity('Integration', $integration);
        $externalAccountEntity = $this->getEntityManager()->getEntity('ExternalAccount', $integration . '__' . $userId);

        if (!$externalAccountEntity) {
            throw new Error("External Account {$integration} not found for {$userId}");
        }

        if (!$integrationEntity->get('enabled')) return null;
        if (!$externalAccountEntity->get('enabled')) return null;

        $className = $this->getMetadata()->get("integrations.{$integration}.clientClassName");
        $client = $this->injectableFactory->create($className);

        $client->setup(
            $userId,
            $integrationEntity,
            $externalAccountEntity,
            $this
        );

        $this->addToClientMap($client, $integrationEntity, $externalAccountEntity, $userId);

        return $client;
    }

    protected function createOAuth2(string $integration, string $userId)
    {
        $integrationEntity = $this->getEntityManager()->getEntity('Integration', $integration);
        $externalAccountEntity = $this->getEntityManager()->getEntity('ExternalAccount', $integration . '__' . $userId);

        $className = $this->getMetadata()->get("integrations.{$integration}.clientClassName");

        $redirectUri = $this->getConfig()->get('siteUrl') . '?entryPoint=oauthCallback';

        $redirectUriPath = $this->getMetadata()->get(['integrations', $integration, 'params', 'redirectUriPath']);
        if ($redirectUriPath) {
            $redirectUri = rtrim($this->getConfig()->get('siteUrl'), '/') . '/' . $redirectUriPath;
        }

        if (!$externalAccountEntity) {
            throw new Error("External Account {$integration} not found for {$userId}");
        }

        if (!$integrationEntity->get('enabled')) {
            return null;
        }
        if (!$externalAccountEntity->get('enabled')) {
            return null;
        }

        $oauth2Client = new \Espo\Core\ExternalAccount\OAuth2\Client();

        $params = [
            'endpoint' => $this->getMetadata()->get("integrations.{$integration}.params.endpoint"),
            'tokenEndpoint' => $this->getMetadata()->get("integrations.{$integration}.params.tokenEndpoint"),
            'clientId' => $integrationEntity->get('clientId'),
            'clientSecret' => $integrationEntity->get('clientSecret'),
            'redirectUri' => $redirectUri,
            'accessToken' => $externalAccountEntity->get('accessToken'),
            'refreshToken' => $externalAccountEntity->get('refreshToken'),
            'tokenType' => $externalAccountEntity->get('tokenType'),
            'expiresAt' => $externalAccountEntity->get('expiresAt'),
        ];

        foreach (get_object_vars($integrationEntity->getValueMap()) as $k => $v) {
            if (array_key_exists($k, $params)) continue;
            if ($integrationEntity->hasAttribute($k)) continue;
            $params[$k] = $v;
        }

        $client = new $className($oauth2Client, $params, $this);

        $this->addToClientMap($client, $integrationEntity, $externalAccountEntity, $userId);

        return $client;
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

    protected function getClientRecord($client) : \Espo\ORM\Entity
    {
        $data = $this->clientMap[spl_object_hash($client)];

        if (!$data) {
            throw new Error("External Account Client Manager: Client not found in hash.");
        }

        return $data['externalAccountEntity'];
    }

    public function isClientLocked($client) : bool
    {
        $externalAccountEntity = $this->getClientRecord($client);
        $id = $externalAccountEntity->id;

        $e = $this->getEntityManager()->getRepository('ExternalAccount')
            ->select(['id', 'isLocked'])->where(['id' => $id])->findOne();

        if (!$e) {
            throw new Error("External Account Client Manager: Client {$id} not found in DB.");
        }

        return $e->get('isLocked');
    }

    public function lockClient($client)
    {
        $externalAccountEntity = $this->getClientRecord($client);
        $id = $externalAccountEntity->id;

        $e = $this->getEntityManager()->getRepository('ExternalAccount')
            ->select(['id', 'isLocked'])->where(['id' => $id])->findOne();

        if (!$e) {
            throw new Error("External Account Client Manager: Client {$id} not found in DB.");
        }

        $e->set('isLocked', true);

        $this->getEntityManager()->saveEntity($e, ['skipHooks' => true, 'silent' => true]);
    }

    public function unlockClient($client)
    {
        $externalAccountEntity = $this->getClientRecord($client);
        $id = $externalAccountEntity->id;

        $e = $this->getEntityManager()->getRepository('ExternalAccount')
            ->select(['id', 'isLocked'])->where(['id' => $id])->findOne();

        if (!$e) {
            throw new Error("External Account Client Manager: Client {$id} not found in DB.");
        }

        $e->set('isLocked', false);

        $this->getEntityManager()->saveEntity($e, ['skipHooks' => true, 'silent' => true]);
    }

    public function reFetchClient($client)
    {
        $externalAccountEntity = $this->getClientRecord($client);
        $id = $externalAccountEntity->id;

        $e = $this->getEntityManager()->getEntity('ExternalAccount', $id);

        if (!$e) {
            throw new Error("External Account Client Manager: Client {$id} not found in DB.");
        }

        $data = $e->getValueMap();

        $externalAccountEntity->set($data);

        $client->setParams(get_object_vars($data));
    }
}
