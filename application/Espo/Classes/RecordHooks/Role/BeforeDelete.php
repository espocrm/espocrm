<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2026 EspoCRM, Inc.
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

namespace Espo\Classes\RecordHooks\Role;

use Espo\Core\Exceptions\Conflict;
use Espo\Core\Exceptions\Error\Body;
use Espo\Core\Record\DeleteParams;
use Espo\Core\Record\Hook\DeleteHook;
use Espo\Core\Utils\Config;
use Espo\Entities\Role;
use Espo\ORM\Entity;
use Espo\ORM\EntityManager;

/**
 * @implements DeleteHook<Role>
 */
class BeforeDelete implements DeleteHook
{
    private const string PARAM_BASELINE_ROLE_ID = 'baselineRoleId';

    public function __construct(
        private EntityManager $entityManager,
        private Config $config,
    ) {}

    public function process(Entity $entity, DeleteParams $params): void
    {
        $this->assertNotReferenced($entity);
    }

    private function hasRelated(Role $role, string $link): bool
    {
        return (bool) $this->entityManager
            ->getRelation($role, $link)
            ->findOne();
    }

    /**
     * @throws Conflict
     */
    private function assertNotReferenced(Role $entity): void
    {
        if ($this->config->get(self::PARAM_BASELINE_ROLE_ID) === $entity->getId()) {
            throw Conflict::createWithBody(
                'Cannot remove the baseline role.',
                Body::create()->withMessageTranslation('cannotRemoveBaseline', Role::ENTITY_TYPE)
            );
        }

        if ($this->hasRelated($entity, Role::LINK_USERS)) {
            throw Conflict::createWithBody(
                'Cannot remove a role assigned to users.',
                Body::create()->withMessageTranslation('cannotRemoveUsedByUsers', Role::ENTITY_TYPE)
            );
        }

        if ($this->hasRelated($entity, Role::LINK_TEAMS)) {
            throw Conflict::createWithBody(
                'Cannot remove a role assigned to teams.',
                Body::create()->withMessageTranslation('cannotRemoveUsedByTeams', Role::ENTITY_TYPE)
            );
        }
    }
}
