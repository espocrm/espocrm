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

namespace Espo\Tools\Import\Jobs;

use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Job\Job;
use Espo\Core\Job\Job\Data;
use Espo\Core\Exceptions\Error;
use Espo\Tools\Import\ImportFactory;
use Espo\Tools\Import\Params as ImportParams;
use Espo\ORM\EntityManager;
use Espo\Entities\User;

class RunIdle implements Job
{
    public function __construct(
        private ImportFactory $factory,
        private EntityManager $entityManager
    ) {}

    /**
     * @throws Forbidden
     * @throws Error
     */
    public function run(Data $data): void
    {
        $raw = $data->getRaw();

        $entityType = $raw->entityType;
        $attachmentId = $raw->attachmentId;
        $importId = $raw->importId;
        $importAttributeList = $raw->importAttributeList;
        $userId = $raw->userId;

        $params = ImportParams::fromRaw($raw->params);

        /** @var ?User $user */
        $user = $this->entityManager->getEntityById(User::ENTITY_TYPE, $userId);

        if (!$user) {
            throw new Error("Import: User not found.");
        }

        if (!$user->isActive()) {
            throw new Error("Import: User is not active.");
        }

        $this->factory
            ->create()
            ->setEntityType($entityType)
            ->setAttributeList($importAttributeList)
            ->setAttachmentId($attachmentId)
            ->setParams($params)
            ->setId($importId)
            ->setUser($user)
            ->run();
    }
}
