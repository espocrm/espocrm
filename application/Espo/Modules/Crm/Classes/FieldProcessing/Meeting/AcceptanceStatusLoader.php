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

namespace Espo\Modules\Crm\Classes\FieldProcessing\Meeting;

use Espo\Entities\User;
use Espo\ORM\Entity;
use Espo\ORM\EntityManager;

use Espo\Core\FieldProcessing\Loader;
use Espo\Core\FieldProcessing\Loader\Params;

/**
 * @implements Loader<\Espo\Core\ORM\Entity>
 */
class AcceptanceStatusLoader implements Loader
{
    private EntityManager $entityManager;
    private User $user;

    private const ATTR_ACCEPTANCE_STATUS = 'acceptanceStatus';

    public function __construct(EntityManager $entityManager, User $user)
    {
        $this->entityManager = $entityManager;
        $this->user = $user;
    }

    public function process(Entity $entity, Params $params): void
    {
        if (!$params->hasInSelect(self::ATTR_ACCEPTANCE_STATUS)) {
            return;
        }

        if ($entity->has(self::ATTR_ACCEPTANCE_STATUS)) {
            return;
        }

        $attribute = self::ATTR_ACCEPTANCE_STATUS;

        $user = $this->entityManager
            ->getRDBRepository($entity->getEntityType())
            ->getRelation($entity, 'users')
            ->where([
                'id' => $this->user->getId(),
            ])
            ->select([$attribute])
            ->findOne();

        $value = null;

        if ($user) {
            $value = $user->get($attribute);
        }

        $entity->set(self::ATTR_ACCEPTANCE_STATUS, $value);
    }
}
