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

namespace Espo\Tools\LeadCapture;

use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\ForbiddenSilent;
use Espo\Core\Exceptions\NotFound;
use Espo\Core\Record\ServiceContainer;
use Espo\Core\Utils\Util;
use Espo\Entities\InboundEmail;
use Espo\Entities\LeadCapture as LeadCaptureEntity;
use Espo\Entities\User;
use Espo\ORM\Entity;
use Espo\ORM\EntityManager;
use stdClass;

class Service
{
    private EntityManager $entityManager;
    private ServiceContainer $recordServiceContainer;
    private User $user;

    public function __construct(
        EntityManager $entityManager,
        ServiceContainer $recordServiceContainer,
        User $user
    ) {
        $this->entityManager = $entityManager;
        $this->recordServiceContainer = $recordServiceContainer;
        $this->user = $user;
    }

    public function isApiKeyValid(string $apiKey): bool
    {
        $leadCapture = $this->entityManager
            ->getRDBRepositoryByClass(LeadCaptureEntity::class)
            ->where([
                'apiKey' => $apiKey,
                'isActive' => true,
            ])
            ->findOne();

        if ($leadCapture) {
            return true;
        }

        return false;
    }

    /**
     * @throws ForbiddenSilent
     * @throws NotFound
     */
    public function generateNewApiKeyForEntity(string $id): Entity
    {
        $service = $this->recordServiceContainer->get(LeadCaptureEntity::ENTITY_TYPE);

        $entity = $service->getEntity($id);

        if (!$entity) {
            throw new NotFound();
        }

        $entity->set('apiKey', $this->generateApiKey());

        $this->entityManager->saveEntity($entity);

        $service->prepareEntityForOutput($entity);

        return $entity;
    }

    public function generateApiKey(): string
    {
        return Util::generateApiKey();
    }

    /**
     * @return stdClass[]
     * @throws Forbidden
     */
    public function getSmtpAccountDataList(): array
    {
        if (!$this->user->isAdmin()) {
            throw new Forbidden();
        }

        $dataList = [];

        $inboundEmailList = $this->entityManager
            ->getRDBRepositoryByClass(InboundEmail::class)
            ->where([
                'useSmtp' => true,
                'status' => InboundEmail::STATUS_ACTIVE,
                ['emailAddress!=' => ''],
                ['emailAddress!=' => null],
            ])
            ->find();

        foreach ($inboundEmailList as $inboundEmail) {
            $item = (object) [];

            $key = 'inboundEmail:' . $inboundEmail->getId();

            $item->key = $key;
            $item->emailAddress = $inboundEmail->getEmailAddress();
            $item->fromName = $inboundEmail->getFromName();

            $dataList[] = $item;
        }

        return $dataList;
    }
}
