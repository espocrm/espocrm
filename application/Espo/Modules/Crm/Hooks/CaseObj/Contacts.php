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

namespace Espo\Modules\Crm\Hooks\CaseObj;

use Espo\Core\FieldProcessing\Stream\FollowersLoader;
use Espo\Core\Hook\Hook\AfterSave;
use Espo\Entities\User;
use Espo\Modules\Crm\Entities\CaseObj;
use Espo\ORM\Entity;
use Espo\ORM\EntityManager;
use Espo\ORM\Name\Attribute;
use Espo\ORM\Repository\Option\SaveOptions;
use Espo\Tools\Stream\Service as StreamService;

/**
 * @implements AfterSave<CaseObj>
 */
class Contacts implements AfterSave
{
    private const ATTR_CONTACT_ID = 'contactId';
    private const RELATION_CONTACTS = 'contacts';

    public function __construct(
        private EntityManager $entityManager,
        private FollowersLoader $followersLoader,
        private StreamService $streamService,
    ) {}

    public function afterSave(Entity $entity, SaveOptions $options): void
    {
        if (!$entity->isAttributeChanged(self::ATTR_CONTACT_ID)) {
            return;
        }

        $contact = $entity->getContact();

        /** @var ?string $fetchedContactId */
        $fetchedContactId = $entity->getFetched(self::ATTR_CONTACT_ID);

        $contactsRelation = $this->entityManager->getRelation($entity, self::RELATION_CONTACTS);

        if ($fetchedContactId) {
            $previousPortalUser = $this->entityManager
                ->getRDBRepositoryByClass(User::class)
                ->select([Attribute::ID])
                ->where([
                    'contactId' => $fetchedContactId,
                    'type' => User::TYPE_PORTAL,
                ])
                ->findOne();

            if ($previousPortalUser) {
                $this->streamService->unfollowEntity($entity, $previousPortalUser->getId());

                $this->followersLoader->processFollowers($entity);
            }
        }

        if (!$contact && $fetchedContactId) {
            $contactsRelation->unrelateById($fetchedContactId);

            return;
        }

        if (!$contact) {
            return;
        }

        $portalUser = $this->getPortalUser($contact->getId());

        if ($portalUser && !$entity->isInternal()) {
            // @todo Solve ACL check issue when a user is in multiple portals.
            $this->streamService->followEntity($entity, $portalUser->getId());

            if (!$entity->isNew()) {
                $this->followersLoader->processFollowers($entity);
            }
        }

        if (in_array($contact->getId(), $entity->getContacts()->getIdList())) {
            return;
        }

        if ($contactsRelation->isRelatedById($contact->getId())) {
            return;
        }

        $contactsRelation->relateById($contact->getId());
    }

    private function getPortalUser(?string $contactId): ?User
    {
        return $this->entityManager
            ->getRDBRepositoryByClass(User::class)
            ->select([Attribute::ID])
            ->where([
                'contactId' => $contactId,
                'type' => User::TYPE_PORTAL,
                'isActive' => true,
            ])
            ->findOne();
    }
}
