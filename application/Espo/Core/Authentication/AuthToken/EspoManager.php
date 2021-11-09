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

namespace Espo\Core\Authentication\AuthToken;

use Espo\ORM\EntityManager;
use Espo\Entities\AuthToken as AuthTokenEntity;

use RuntimeException;

use const MCRYPT_DEV_URANDOM;

class EspoManager implements Manager
{
    private $entityManager;

    private $repository;

    private const TOKEN_RANDOM_LENGTH = 16;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;

        $this->repository = $entityManager->getRDBRepository(AuthTokenEntity::ENTITY_TYPE);
    }

    public function get(string $token): ?AuthToken
    {
        /** @var ?AuthTokenEntity */
        $authToken = $this->entityManager
            ->getRDBRepository(AuthTokenEntity::ENTITY_TYPE)
            ->select([
                'id',
                'isActive',
                'token',
                'secret',
                'userId',
                'portalId',
                'hash',
                'createdAt',
                'lastAccess',
                'modifiedAt',
            ])
            ->where([
                'token' => $token,
            ])
            ->findOne();

        return $authToken;
    }

    public function create(Data $data): AuthToken
    {
        /** @var ?AuthTokenEntity */
        $authToken = $this->repository->getNew();

        $authToken->set([
            'userId' => $data->getUserId(),
            'portalId' => $data->getPortalId(),
            'hash' => $data->getHash(),
            'ipAddress' => $data->getIpAddress(),
            'lastAccess' => date('Y-m-d H:i:s'),
            'token' => $this->generateToken(),
        ]);

        if ($data->toCreateSecret()) {
            $authToken->set('secret', $this->generateToken());
        }

        $this->validate($authToken);

        $this->repository->save($authToken);

        return $authToken;
    }

    public function inactivate(AuthToken $authToken): void
    {
        assert($authToken instanceof AuthTokenEntity);

        $this->validateNotChanged($authToken);

        $authToken->set('isActive', false);

        $this->repository->save($authToken);
    }

    public function renew(AuthToken $authToken): void
    {
        assert($authToken instanceof AuthTokenEntity);

        $this->validateNotChanged($authToken);

        if ($authToken->isNew()) {
            throw new RuntimeException("Can renew only not new auth token.");
        }

        $authToken->set('lastAccess', date('Y-m-d H:i:s'));

        $this->repository->save($authToken);
    }

    protected function validate(AuthToken $authToken): void
    {
        if (!$authToken->getToken()) {
            throw new RuntimeException("Empty token.");
        }

        if (!$authToken->getUserId()) {
            throw new RuntimeException("Empty user ID.");
        }
    }

    protected function validateNotChanged(AuthTokenEntity $authToken): void
    {
        if (
            $authToken->isAttributeChanged('token') ||
            $authToken->isAttributeChanged('secret') ||
            $authToken->isAttributeChanged('hash') ||
            $authToken->isAttributeChanged('userId') ||
            $authToken->isAttributeChanged('portalId')
        ) {
            throw new RuntimeException("Auth token was changed.");
        }
    }

    protected function generateToken(): string
    {
        $length = self::TOKEN_RANDOM_LENGTH;

        if (function_exists('random_bytes')) {
            return bin2hex(random_bytes($length));
        }

        if (function_exists('mcrypt_create_iv')) {
            return bin2hex(mcrypt_create_iv($length, MCRYPT_DEV_URANDOM));
        }

        if (function_exists('openssl_random_pseudo_bytes')) {
            return bin2hex(openssl_random_pseudo_bytes($length));
        }

        throw new RuntimeException("Could not generate token.");
    }
}
