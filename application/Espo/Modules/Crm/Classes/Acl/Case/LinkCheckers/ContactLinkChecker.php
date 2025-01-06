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

namespace Espo\Modules\Crm\Classes\Acl\Case\LinkCheckers;

use Espo\Core\Acl\LinkChecker;
use Espo\Core\AclManager;
use Espo\Entities\Email;
use Espo\Entities\User;
use Espo\Modules\Crm\Entities\CaseObj;
use Espo\Modules\Crm\Entities\Contact;
use Espo\ORM\Entity;
use Espo\ORM\EntityManager;

/**
 * @implements LinkChecker<CaseObj, Contact>
 * @noinspection PhpUnused
 */
class ContactLinkChecker implements LinkChecker
{
    public function __construct(
        private AclManager $aclManager,
        private EntityManager $entityManager
    ) {}

    public function check(User $user, Entity $entity, Entity $foreignEntity): bool
    {
        if ($this->aclManager->checkEntityRead($user, $foreignEntity)) {
            return true;
        }

        if (!$entity->isNew()) {
            return false;
        }

        $emailId = $entity->get('originalEmailId');

        if (!$emailId) {
            return false;
        }

        $email = $this->entityManager->getRepositoryByClass(Email::class)->getById($emailId);

        if (!$email) {
            return false;
        }

        $parent = $email->getParent();

        if (!$parent) {
            return false;
        }

        if (
            $parent->getEntityType() !== Contact::ENTITY_TYPE ||
            $parent->getId() !== $foreignEntity->getId()
        ) {
            return false;
        }

        return $this->aclManager->checkEntityRead($user, $email);
    }
}
