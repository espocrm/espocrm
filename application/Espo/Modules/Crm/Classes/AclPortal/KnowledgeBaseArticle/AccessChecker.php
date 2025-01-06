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

namespace Espo\Modules\Crm\Classes\AclPortal\KnowledgeBaseArticle;

use Espo\Core\Utils\Metadata;
use Espo\Entities\User;
use Espo\Modules\Crm\Entities\KnowledgeBaseArticle;
use Espo\ORM\Entity;
use Espo\Core\ORM\Entity as CoreEntity;
use Espo\Core\Acl\AccessEntityCREDChecker;
use Espo\Core\Acl\ScopeData;
use Espo\Core\Portal\Acl\DefaultAccessChecker;
use Espo\Core\Portal\Acl\Traits\DefaultAccessCheckerDependency;

/**
 * @implements AccessEntityCREDChecker<KnowledgeBaseArticle>
 */
class AccessChecker implements AccessEntityCREDChecker
{
    use DefaultAccessCheckerDependency;

    public function __construct(
        DefaultAccessChecker $defaultAccessChecker,
        private Metadata $metadata
    ) {
        $this->defaultAccessChecker = $defaultAccessChecker;
    }

    public function checkEntityRead(User $user, Entity $entity, ScopeData $data): bool
    {
        if (!$this->defaultAccessChecker->checkEntityRead($user, $entity, $data)) {
            return false;
        }

        $statusList = $this->metadata->get("entityDefs.KnowledgeBaseArticle.fields.status.activeOptions") ??
            [KnowledgeBaseArticle::STATUS_PUBLISHED];

        if (!in_array($entity->getStatus(), $statusList)) {
            return false;
        }

        assert($entity instanceof CoreEntity);

        $portalIdList = $entity->getLinkMultipleIdList('portals');

        $portalId = $user->get('portalId');

        if (!$portalId) {
            return false;
        }

        return in_array($portalId, $portalIdList);
    }
}
