<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2024 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

namespace Espo\Tools\Stars;

use Espo\Core\Utils\DateTime;
use Espo\Core\Utils\Metadata;
use Espo\Entities\StarSubscription;
use Espo\Entities\User;
use Espo\ORM\Entity;
use Espo\ORM\EntityManager;

class StarService
{
    public function __construct(
        private EntityManager $entityManager,
        private User $user,
        private Metadata $metadata,
    ) {}

    public function checkIsStarred(Entity $entity, ?string $userId = null): bool
    {
        $userId ??= $this->user->getId();

        return (bool) $this->entityManager
            ->getRDBRepository(StarSubscription::ENTITY_TYPE)
            ->select(['id'])
            ->where([
                'userId' => $userId,
                'entityType' => $entity->getEntityType(),
                'entityId' => $entity->getId(),
            ])
            ->findOne();
    }

    public function isEnabled(string $entityType): bool
    {
        return (bool) $this->metadata->get("scopes.$entityType.stars");
    }

    public function star(Entity $entity, User $user): bool
    {
        if ($this->checkIsStarred($entity, $user->getId())) {
            return true;
        }

        $this->entityManager->createEntity(StarSubscription::ENTITY_TYPE, [
            'entityId' => $entity->getId(),
            'entityType' => $entity->getEntityType(),
            'userId' => $user->getId(),
            'createdAt' => DateTime::getSystemNowString(),
        ]);

        return true;
    }

    public function unstar(Entity $entity, User $user): bool
    {
        if (!$this->isEnabled($entity->getEntityType())) {
            return false;
        }

        $delete = $this->entityManager->getQueryBuilder()
            ->delete()
            ->from(StarSubscription::ENTITY_TYPE)
            ->where([
                'userId' => $user->getId(),
                'entityId' => $entity->getId(),
                'entityType' => $entity->getEntityType(),
            ])
            ->build();

        $this->entityManager->getQueryExecutor()->execute($delete);

        return true;
    }
}
