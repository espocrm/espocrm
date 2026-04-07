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

namespace Espo\Services;

use Espo\Core\Record\ReadResult;
use Espo\Core\Exceptions\Error;
use Espo\Core\Exceptions\NotFound;
use Espo\Core\Exceptions\NotFoundSilent;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Record\ReadParams;
use Espo\Entities\ExternalAccount as ExternalAccountEntity;
use Espo\Tools\ExternalAccount\OAuthService;
use Exception;

/**
 * @extends Record<ExternalAccountEntity>
 */
class ExternalAccount extends Record
{
    /**
     * @return bool
     * @deprecated As of v10.0. Use `Espo\Tools\OAuthService`.
     * @todo Fix all usages.
     * @todo Remove in v11.0.
     */
    public function ping(string $integration, string $userId)
    {
        return $this->injectableFactory->create(OAuthService::class)->ping($integration, $userId);
    }

    /**
     * @throws NotFound
     * @throws Error
     * @throws Exception
     * @deprecated As of v10.0. Use `Espo\Tools\OAuthService`.
     * @todo Fix all usages.
     * @todo Remove in v11.0.
     */
    public function authorizationCode(string $integration, string $userId, string $code): void
    {
        $this->injectableFactory->create(OAuthService::class)->authorizationCode($integration, $userId, $code);
    }

    public function read(string $id, ReadParams $params = new ReadParams()): ReadResult
    {
        [, $userId] = explode('__', $id);

        if ($this->user->getId() !== $userId && !$this->user->isAdmin()) {
            throw new Forbidden();
        }

        $entity = $this->entityManager->getRDBRepositoryByClass(ExternalAccountEntity::class)->getById($id);

        if (!$entity) {
            throw new NotFoundSilent();
        }

        [$integration,] = explode('__', $entity->getId());

        $secretAttributeList =
            $this->metadata->get(['integrations', $integration, 'externalAccountSecretAttributeList']) ?? [];

        foreach ($secretAttributeList as $a) {
            $entity->clear($a);
        }

        return new ReadResult($entity);
    }
}
