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

namespace Espo\Modules\Crm\Classes\RecordHooks\Lead;

use Espo\Core\Acl;
use Espo\Core\Record\Hook\SaveHook;
use Espo\Core\Utils\DateTime;
use Espo\Entities\Email;
use Espo\Modules\Crm\Entities\Campaign as Campaign;
use Espo\Modules\Crm\Entities\CampaignLogRecord as CampaignLogRecord;
use Espo\Modules\Crm\Entities\Lead;
use Espo\ORM\Entity;
use Espo\ORM\EntityManager;

/**
 * @implements SaveHook<Lead>
 */
class AfterCreate implements SaveHook
{
    public function __construct(
        private EntityManager $entityManager,
        private Acl $acl
    ) {}

    public function process(Entity $entity): void
    {
        $this->processOriginalEmail($entity);
        $this->processCampaignLog($entity);
    }

    private function processOriginalEmail(Lead $entity): void
    {
        $emailId = $entity->get('originalEmailId');

        if (!$emailId) {
            return;
        }

        /** @var ?Email $email */
        $email = $this->entityManager->getEntityById(Email::ENTITY_TYPE, $emailId);

        if (!$email || $email->getParentId() || !$this->acl->check($email)) {
            return;
        }

        $email->set([
            'parentType' => Lead::ENTITY_TYPE,
            'parentId' => $entity->getId(),
        ]);

        $this->entityManager->saveEntity($email);
    }

    private function processCampaignLog(Lead $entity): void
    {
        $campaign = $entity->getCampaign();

        if (!$campaign) {
            return;
        }

        $log = $this->entityManager->getNewEntity(CampaignLogRecord::ENTITY_TYPE);

        $log->set([
            'action' => CampaignLogRecord::ACTION_LEAD_CREATED,
            'actionDate' => DateTime::getSystemNowString(),
            'parentType' => Lead::ENTITY_TYPE,
            'parentId' => $entity->getId(),
            'campaignId' => $campaign->getId(),
        ]);

        $this->entityManager->saveEntity($log);
    }
}
