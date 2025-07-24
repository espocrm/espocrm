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

namespace Espo\Tools\Notification;

use Espo\Core\Record\Collection as RecordCollection;
use Espo\Entities\Notification;
use Espo\Entities\User;
use Espo\ORM\Collection;
use Espo\ORM\EntityManager;
use Espo\ORM\Name\Attribute;

class GroupService
{
    private const LIMIT = 100;

    public function __construct(
        private EntityManager $entityManager,
        private User $user,
        private RecordService $recordService,
    ) {}

    /**
     * @return RecordCollection<Notification>
     */
    public function get(Notification $notification): RecordCollection
    {
        if (!$notification->getActionId()) {
            /** @var Collection<Notification> $collection */
            $collection = $this->entityManager->getCollectionFactory()->create(Notification::ENTITY_TYPE);

            return RecordCollection::create($collection, 0);
        }

        $collection = $this->entityManager
            ->getRDBRepositoryByClass(Notification::class)
            ->where([
                Attribute::ID . '!=' => $notification->getId(),
                Notification::ATTR_ACTION_ID => $notification->getActionId(),
                Notification::ATTR_USER_ID => $this->user->getId(),
            ])
            ->limit(0, self::LIMIT)
            ->order(Notification::ATTR_NUMBER)
            ->find();

        $collection = $this->recordService->prepareCollection($collection, $this->user);

        return RecordCollection::create($collection, count($collection));
    }
}
