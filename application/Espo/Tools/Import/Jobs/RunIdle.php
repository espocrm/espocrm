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

namespace Espo\Tools\Import\Jobs;

use Espo\Core\Job\Job;
use Espo\Core\Job\Job\Data;
use Espo\Core\Exceptions\Error;

use Espo\Tools\Import\ImportFactory;
use Espo\Tools\Import\Params as ImportParams;

use Espo\ORM\EntityManager;

use Espo\Entities\User;

class RunIdle implements Job
{
    private $factory;

    private $entityManager;

    public function __construct(ImportFactory $factory, EntityManager $entityManager)
    {
        $this->factory = $factory;
        $this->entityManager = $entityManager;
    }

    public function run(Data $data): void
    {
        $raw = $data->getRaw();

        $entityType = $raw->entityType;
        $attachmentId = $raw->attachmentId;
        $importId = $raw->importId;
        $importAttributeList = $raw->importAttributeList;
        $userId = $raw->userId;

        $params = ImportParams::fromRaw($raw->params);

        $user = $this->entityManager->getEntity(User::ENTITY_TYPE, $userId);

        if (!$user) {
            throw new Error("Import: User not found.");
        }

        if (!$user->get('isActive')) {
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
