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

namespace Espo\Core\FieldProcessing\Reminder;

use Espo\Entities\User;
use Espo\Modules\Crm\Entities\Reminder;
use Espo\ORM\Entity;
use Espo\Core\FieldProcessing\Loader as LoaderInterface;
use Espo\Core\FieldProcessing\Loader\Params;
use Espo\Core\ORM\EntityManager;

/**
 * @internal This class should not be removed as it's used by custom entities.
 * @implements LoaderInterface<Entity>
 */
class Loader implements LoaderInterface
{
    public function __construct(
        private EntityManager $entityManager,
        private User $user
    ) {}

    public function process(Entity $entity, Params $params): void
    {
        $hasReminder = $this->entityManager
            ->getDefs()
            ->getEntity($entity->getEntityType())
            ->hasField('reminders');

        if (!$hasReminder) {
            return;
        }

        if ($params->hasSelect() && !$params->hasInSelect('reminders')) {
            return;
        }

        $entity->set('reminders', $this->fetchReminderDataList($entity));
    }

    /**
     * @return object{seconds: int, type: string}[]
     */
    private function fetchReminderDataList(Entity $entity): array
    {
        $list = [];

        /** @var iterable<Reminder> $collection */
        $collection = $this->entityManager
            ->getRDBRepository(Reminder::ENTITY_TYPE)
            ->select(['seconds', 'type'])
            ->where([
                'entityType' => $entity->getEntityType(),
                'entityId' => $entity->getId(),
                'userId' => $this->user->getId(),
            ])
            ->distinct()
            ->order('seconds')
            ->find();

        foreach ($collection as $reminder) {
            $list[] = (object) [
                'seconds' => $reminder->getSeconds(),
                'type' => $reminder->getType(),
            ];
        }

        return $list;
    }
}
