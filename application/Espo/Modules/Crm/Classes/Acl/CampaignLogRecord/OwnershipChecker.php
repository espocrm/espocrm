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

namespace Espo\Modules\Crm\Classes\Acl\CampaignLogRecord;

use Espo\Entities\User;
use Espo\Modules\Crm\Entities\CampaignLogRecord;
use Espo\ORM\Entity;
use Espo\Core\Acl\OwnershipOwnChecker;
use Espo\Core\Acl\OwnershipTeamChecker;
use Espo\Core\AclManager;
use Espo\Core\ORM\EntityManager;

/**
 * @implements OwnershipOwnChecker<CampaignLogRecord>
 * @implements OwnershipTeamChecker<CampaignLogRecord>
 */
class OwnershipChecker implements OwnershipOwnChecker, OwnershipTeamChecker
{
    public function __construct(private AclManager $aclManager, private EntityManager $entityManager)
    {}

    public function checkOwn(User $user, Entity $entity): bool
    {
        $campaignId = $entity->get('campaignId');

        if (!$campaignId) {
            return false;
        }

        $campaign = $this->entityManager->getEntityById('Campaign', $campaignId);

        if ($campaign && $this->aclManager->checkOwnershipOwn($user, $campaign)) {
            return true;
        }

        return false;
    }

    public function checkTeam(User $user, Entity $entity): bool
    {
        $campaignId = $entity->get('campaignId');

        if (!$campaignId) {
            return false;
        }

        $campaign = $this->entityManager->getEntityById('Campaign', $campaignId);

        if ($campaign && $this->aclManager->checkOwnershipTeam($user, $campaign)) {
            return true;
        }

        return false;
    }
}
