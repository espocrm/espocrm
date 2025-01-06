<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

use Espo\Core\Exceptions\Error\Body;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Name\Field;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\DateTime;
use Espo\Core\Utils\Metadata;
use Espo\Entities\StarSubscription;
use Espo\Entities\User;
use Espo\ORM\Entity;
use Espo\ORM\EntityManager;
use Espo\ORM\Name\Attribute;
use PDOException;

class StarService
{
    public function __construct(
        private EntityManager $entityManager,
        private Metadata $metadata,
        private Config $config
    ) {}

    public function isStarred(Entity $entity, User $user): bool
    {
        return (bool) $this->entityManager
            ->getRDBRepository(StarSubscription::ENTITY_TYPE)
            ->select([Attribute::ID])
            ->where([
                'userId' => $user->getId(),
                'entityType' => $entity->getEntityType(),
                'entityId' => $entity->getId(),
            ])
            ->findOne();
    }

    public function isEnabled(string $entityType): bool
    {
        return (bool) $this->metadata->get("scopes.$entityType.stars");
    }

    /**
     * @throws Forbidden
     */
    public function star(Entity $entity, User $user): void
    {
        if (!$this->isEnabled($entity->getEntityType())) {
            throw new Forbidden();
        }

        if ($this->isStarred($entity, $user)) {
            return;
        }

        $this->checkLimit($entity->getEntityType(), $user);

        try {
            $this->entityManager->createEntity(StarSubscription::ENTITY_TYPE, [
                'entityId' => $entity->getId(),
                'entityType' => $entity->getEntityType(),
                'userId' => $user->getId(),
                Field::CREATED_AT => DateTime::getSystemNowString(),
            ]);
        } catch (PDOException $e) {
            if ((int) $e->getCode() === 23000) {
                // Duplicate.
                return;
            }

            throw $e;
        }
    }

    /**
     * @throws Forbidden
     */
    public function unstar(Entity $entity, User $user): void
    {
        if (!$this->isEnabled($entity->getEntityType())) {
            throw new Forbidden();
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
    }

    /**
     * @throws Forbidden
     */
    private function checkLimit(string $entityType, User $user): void
    {
        $limit = $this->config->get('starsLimit');

        if ($limit === null) {
            return;
        }

        $count = $this->entityManager
            ->getRDBRepositoryByClass(StarSubscription::class)
            ->where([
                'userId' => $user->getId(),
                'entityType' => $entityType,
            ])
            ->count();

        if ($count >= $limit) {
            throw Forbidden::createWithBody(
                'starsLimitExceeded',
                Body::create()->withMessageTranslation('starsLimitExceeded')
            );
        }
    }
}
