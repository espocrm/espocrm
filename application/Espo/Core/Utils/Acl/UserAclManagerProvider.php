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

namespace Espo\Core\Utils\Acl;

use Espo\Core\Acl\Exceptions\NotAvailable;
use Espo\Entities\Portal;
use Espo\Entities\User;
use Espo\ORM\EntityManager;
use Espo\Core\AclManager;
use Espo\Core\Portal\AclManagerContainer as PortalAclManagerContainer;
use Espo\Core\ApplicationState;

/**
 * @todo Use WeakMap (User as a key).
 */
class UserAclManagerProvider
{
    /** @var array<string, AclManager> */
    private $map = [];

    public function __construct(
        private EntityManager $entityManager,
        private AclManager $aclManager,
        private PortalAclManagerContainer $portalAclManagerContainer,
        private ApplicationState $applicationState
    ) {}

    /**
     * @throws NotAvailable
     */
    public function get(User $user): AclManager
    {
        $key = $user->hasId() ? $user->getId() : spl_object_hash($user);

        if (!isset($this->map[$key])) {
            $this->map[$key] = $this->load($user);
        }

        return $this->map[$key];
    }

    /**
     * @throws NotAvailable
     */
    private function load(User $user): AclManager
    {
        $aclManager = $this->aclManager;

        if ($user->isPortal() && !$this->applicationState->isPortal()) {
            /** @var ?Portal $portal */
            $portal = $this->entityManager
                ->getRDBRepository(User::ENTITY_TYPE)
                ->getRelation($user, 'portals')
                ->findOne();

            if (!$portal) {
                throw new NotAvailable("No portal for portal user '" . $user->getId() . "'.");
            }

            $aclManager = $this->portalAclManagerContainer->get($portal);
        }

        return $aclManager;
    }
}
