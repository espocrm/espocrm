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

namespace Espo\Classes\RecordHooks\User;

use Espo\Core\Acl\Cache\Clearer;
use Espo\Core\DataManager;
use Espo\Core\Record\Hook\SaveHook;
use Espo\Modules\Crm\Entities\Contact;
use Espo\ORM\Entity;
use Espo\Entities\User;
use Espo\ORM\EntityManager;

/**
 * @implements SaveHook<User>
 * @noinspection PhpUnused
 */
class AfterUpdate implements SaveHook
{
    public function __construct(
        private EntityManager $entityManager,
        private Clearer $clearer,
        private DataManager $dataManager
    ) {}

    public function process(Entity $entity): void
    {
        $this->processCache($entity);
        $this->processContactName($entity);
    }

    private function processCache(User $entity): void
    {
        if (
            $entity->isAttributeChanged('rolesIds') ||
            $entity->isAttributeChanged('teamsIds') ||
            $entity->isAttributeChanged('type') ||
            $entity->isAttributeChanged('portalRolesIds') ||
            $entity->isAttributeChanged('portalsIds')
        ) {
            $this->clearer->clearForUser($entity);
            $this->dataManager->updateCacheTimestamp();
        }

        if (
            $entity->isAttributeChanged('portalRolesIds') ||
            $entity->isAttributeChanged('portalsIds') ||
            $entity->isAttributeChanged('contactId') ||
            $entity->isAttributeChanged('accountsIds')
        ) {
            $this->clearer->clearForAllPortalUsers();
            $this->dataManager->updateCacheTimestamp();
        }
    }

    private function processContactName(User $entity): void
    {
        if (
            !$entity->isPortal() ||
            !$entity->getContactId() ||
            !$entity->isAttributeChanged('firstName') &&
            !$entity->isAttributeChanged('lastName') &&
            !$entity->isAttributeChanged('salutationName')
        ) {
            return;
        }

        $contact = $this->entityManager->getEntityById(Contact::ENTITY_TYPE, $entity->getContactId());

        if (!$contact) {
            return;
        }

        if ($entity->isAttributeChanged('firstName')) {
            $contact->set('firstName', $entity->get('firstName'));
        }

        if ($entity->isAttributeChanged('lastName')) {
            $contact->set('lastName', $entity->get('lastName'));
        }

        if ($entity->isAttributeChanged('salutationName')) {
            $contact->set('salutationName', $entity->get('salutationName'));
        }

        $this->entityManager->saveEntity($contact);
    }
}
