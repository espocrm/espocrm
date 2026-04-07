<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2026 EspoCRM, Inc.
 * Website: https://www.espocrm.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU Affero General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU Affero General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

namespace Espo\Tools\ExternalAccount;

use Espo\Core\Acl;
use Espo\Core\Exceptions\Error;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\NotFound;
use Espo\Core\Utils\Config\ApplicationConfig;
use Espo\Core\Utils\Metadata;
use Espo\Entities\ExternalAccount;
use Espo\Entities\Integration;
use Espo\Entities\User;
use Espo\ORM\EntityManager;
use stdClass;

/**
 * @since 10.0.0
 */
class Service
{
    public function __construct(
        private Metadata $metadata,
        private EntityManager $entityManager,
        private Acl $acl,
        private User $user,
        private ApplicationConfig $config,
        private OAuthService $oAuthService,
    ) {}

    /**
     * @internal
     */
    public function getList(): stdClass
    {
        $integrations = $this->entityManager
            ->getRDBRepositoryByClass(Integration::class)
            ->find();

        $list = [];

        foreach ($integrations as $entity) {
            if (
                !$entity->isEnabled() ||
                !$this->metadata->get("integrations.{$entity->getId()}.allowUserAccounts")
            ) {
                continue;
            }

            $id = $entity->getId();

            $userAccountAclScope = $this->metadata->get(['integrations', $id, 'userAccountAclScope']);

            if ($userAccountAclScope && !$this->acl->checkScope($userAccountAclScope)) {
                continue;
            }

            $list[] = [
                'id' => $id,
            ];
        }

        return (object) [
            'list' => $list
        ];
    }

    /**
     * @throws Forbidden
     * @internal
     */
    public function getActionGetOAuth2Info(string $id): ?stdClass
    {
        [$integration, $userId] = explode('__', $id);

        if ($this->user->getId() != $userId && !$this->user->isAdmin()) {
            throw new Forbidden();
        }

        $entity = $this->entityManager->getRDBRepositoryByClass(Integration::class)->getById($integration);

        if (!$entity) {
            return null;
        }

        return (object) [
            'clientId' => $entity->get('clientId'),
            'redirectUri' => $this->config->getSiteUrl() . '?entryPoint=oauthCallback',
            'isConnected' => $this->oAuthService->ping($integration, $userId)
        ];
    }

    /**
     * @throws Forbidden
     * @throws NotFound
     */
    public function update(string $id, stdClass $data): stdClass
    {
        [, $userId] = explode('__', $id);

        if ($this->user->getId() !== $userId && !$this->user->isAdmin()) {
            throw new Forbidden();
        }

        if (isset($data->enabled) && !$data->enabled) {
            $data->data = null;
        }

        $entity = $this->entityManager->getRDBRepositoryByClass(ExternalAccount::class)->getById($id);

        if (!$entity) {
            throw new NotFound();
        }

        $entity->setMultiple($data);

        $this->entityManager->saveEntity($entity);

        return $entity->getValueMap();
    }

    /**
     * @internal
     * @throws Forbidden
     * @throws NotFound
     * @throws Error
     */
    public function authorizationCode(string $id, string $code): void
    {
        [$integration, $userId] = explode('__', $id);

        if ($this->user->getId() !== $userId && !$this->user->isAdmin()) {
            throw new Forbidden();
        }

        $this->oAuthService->authorizationCode($integration, $userId, $code);
    }
}
