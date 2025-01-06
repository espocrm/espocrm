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

namespace Espo\Core\Portal;

use Espo\ORM\Entity;

use Espo\Entities\User;

use Espo\Core\Acl as BaseAcl;

class Acl extends BaseAcl
{
    public function __construct(AclManager $aclManager, User $user)
    {
        parent::__construct($aclManager, $user);
    }

    /**
     * Whether 'read' access is set to 'account' for a specific scope.
     */
    public function checkReadOnlyAccount(string $scope): bool
    {
        /** @var AclManager $aclManager */
        $aclManager = $this->aclManager;

        return $aclManager->checkReadOnlyAccount($this->user, $scope);
    }

    /**
     * Whether 'read' access is set to 'contact' for a specific scope.
     */
    public function checkReadOnlyContact(string $scope): bool
    {
        /** @var AclManager $aclManager */
        $aclManager = $this->aclManager;

        return $aclManager->checkReadOnlyContact($this->user, $scope);
    }

    /**
     * Check whether an entity belongs to a user account.
     */
    public function checkOwnershipAccount(Entity $entity): bool
    {
        /** @var AclManager $aclManager */
        $aclManager = $this->aclManager;

        return $aclManager->checkOwnershipAccount($this->user, $entity);
    }

    /**
     * Check whether an entity belongs to a user contact.
     */
    public function checkOwnershipContact(Entity $entity): bool
    {
        /** @var AclManager $aclManager */
        $aclManager = $this->aclManager;

        return $aclManager->checkOwnershipContact($this->user, $entity);
    }

    /**
     * @deprecate
     */
    public function checkInAccount(Entity $entity): bool
    {
        return $this->checkOwnershipAccount($entity);
    }

    /**
     * @deprecate
     */
    public function checkIsOwnContact(Entity $entity): bool
    {
        return $this->checkOwnershipContact($entity);
    }
}
