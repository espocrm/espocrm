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

namespace Espo\Hooks\LayoutSet;

use Espo\ORM\Entity;
use Espo\ORM\EntityManager;

use Espo\Entities\LayoutSet;
use Espo\Entities\Team;
use Espo\Entities\Portal;

class Removal
{
    private EntityManager $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param LayoutSet $entity
     */
    public function afterRemove(Entity $entity): void
    {
        $updateQuery1 = $this->entityManager
            ->getQueryBuilder()
            ->update()
            ->in(Team::ENTITY_TYPE)
            ->set([
                'layoutSetId' => null,
            ])
            ->where([
                'layoutSetId' => $entity->getId(),
            ])
            ->build();

        $this->entityManager
            ->getQueryExecutor()
            ->execute($updateQuery1);

        $updateQuery2 = $this->entityManager
            ->getQueryBuilder()
            ->update()
            ->in(Portal::ENTITY_TYPE)
            ->set([
                'layoutSetId' => null,
            ])
            ->where([
                'layoutSetId' => $entity->getId(),
            ])
            ->build();

        $this->entityManager
            ->getQueryExecutor()
            ->execute($updateQuery2);
    }
}
