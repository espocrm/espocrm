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

namespace Espo\Core\ORM;

use Espo\ORM\{
    Entity,
    Repository\Repository,
    Repository\RDBRepository,
};

use Espo\Core\{
    Container,
};

class EntityManagerProxy
{
    private $entityManager = null;

    private $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    private function getEntityManager(): EntityManager
    {
        if (!$this->entityManager) {
            $this->entityManager = $this->container->get('entityManager');
        }

        return $this->entityManager;
    }

    public function getEntity(string $entityType, ?string $id = null): ?Entity
    {
        return $this->getEntityManager()->getEntity($entityType, $id);
    }

    public function saveEntity(Entity $entity, array $options = [])
    {
        /** Return for backward compatibility. */
        /** @phpstan-ignore-next-line */
        return $this->getEntityManager()->saveEntity($entity, $options);
    }

    public function getRepository(string $entityType): Repository
    {
        return $this->getEntityManager()->getRepository($entityType);
    }

    public function getRDBRepository(string $entityType): RDBRepository
    {
        return $this->getEntityManager()->getRDBRepository($entityType);
    }
}
