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

namespace Espo\Tools\OAuthServer\Record\MassActions;

use Espo\Core\Acl;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Conflict;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\MassAction\Data;
use Espo\Core\MassAction\MassAction;
use Espo\Core\MassAction\Params;
use Espo\Core\MassAction\QueryBuilder;
use Espo\Core\MassAction\Result;
use Espo\Entities\User;
use Espo\ORM\Entity;
use Espo\ORM\EntityManager;
use Espo\Tools\OAuthServer\Entities\AccessToken;
use Espo\Tools\OAuthServer\Entities\AuthorizationCode;
use Espo\Tools\OAuthServer\Entities\RefreshToken;
use Espo\Tools\OAuthServer\Record\TokenService;
use RuntimeException;

/**
 * @noinspection PhpUnused
 */
class RevokeMassAction implements MassAction
{
    /**
     * @var string[]
     */
    private array $entityTypes = [
        AccessToken::ENTITY_TYPE,
        RefreshToken::ENTITY_TYPE,
        AuthorizationCode::ENTITY_TYPE,
    ];

    public function __construct(
        private TokenService $service,
        private User $user,
        private QueryBuilder $queryBuilder,
        private Acl $acl,
        private EntityManager $entityManager,
    ) {}

    public function process(Params $params, Data $data): Result
    {
        $entityType = $params->getEntityType();

        if (!in_array($entityType, $this->entityTypes)) {
            throw new BadRequest();
        }

        $this->assertAccess($entityType);

        $query = $this->queryBuilder->build($params);

        $collection = $this->entityManager->getRDBRepository($entityType)
            ->clone($query)
            ->sth()
            ->find();

        $ids = [];
        $count = 0;

        foreach ($collection as $entity) {
            try {
                $this->assertEntity($entity);

                /** @noinspection PhpParamsInspection */
                $this->service->revoke($entity);

                $ids[] = $entity->getId();
                $count ++;
            } catch (Conflict|Forbidden) {
                continue;
            }
        }

        return new Result($count, $ids);
    }

    /**
     * @throws Forbidden
     */
    private function assertAccess(string $entityType): void
    {
        if (!$this->user->isAdmin()) {
            throw new Forbidden();
        }

        if (!$this->acl->check($entityType, Acl\Table::ACTION_EDIT)) {
            throw new Forbidden("No edit access.");
        }
    }

    /**
     * @phpstan-assert AccessToken|RefreshToken|AuthorizationCode $entity
     */
    private function assertEntity(Entity $entity): void
    {
        if (
            !$entity instanceof AccessToken &&
            !$entity instanceof RefreshToken &&
            !$entity instanceof AuthorizationCode
        ) {
            throw new RuntimeException();
        }
    }
}
