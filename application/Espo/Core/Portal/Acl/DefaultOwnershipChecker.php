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

namespace Espo\Core\Portal\Acl;

use Espo\Core\Field\Link;
use Espo\Core\Field\LinkParent;
use Espo\Core\Name\Field;
use Espo\Core\ORM\Type\FieldType;
use Espo\Core\Portal\Acl\OwnershipChecker\MetadataProvider;
use Espo\Modules\Crm\Entities\Account;
use Espo\Modules\Crm\Entities\Contact;
use Espo\ORM\Entity;
use Espo\ORM\EntityManager;
use Espo\Entities\User;
use Espo\Core\Acl\OwnershipOwnChecker;
use Espo\ORM\Type\RelationType;

/**
 * A default implementation for ownership checking for portal.
 *
 * @implements OwnershipOwnChecker<\Espo\Core\ORM\Entity>
 * @implements OwnershipAccountChecker<\Espo\Core\ORM\Entity>
 * @implements OwnershipContactChecker<\Espo\Core\ORM\Entity>
 */
class DefaultOwnershipChecker implements
    OwnershipOwnChecker,
    OwnershipAccountChecker,
    OwnershipContactChecker
{
    private const ATTR_CREATED_BY_ID = Field::CREATED_BY . 'Id';

    public function __construct(
        private EntityManager $entityManager,
        private MetadataProvider $metadataProvider,
    ) {}

    public function checkOwn(User $user, Entity $entity): bool
    {
        if ($entity->hasAttribute(self::ATTR_CREATED_BY_ID)) {
            if (
                $entity->has(self::ATTR_CREATED_BY_ID) &&
                $user->getId() === $entity->get(self::ATTR_CREATED_BY_ID)
            ) {
                return true;
            }
        }

        return false;
    }

    public function checkAccount(User $user, Entity $entity): bool
    {
        $linkDefs = $this->metadataProvider->getAccountLink($entity->getEntityType());

        if (!$linkDefs) {
            return false;
        }

        $link = $linkDefs->getName();

        $accountIds = $user->getAccounts()->getIdList();

        if ($accountIds === []) {
            return false;
        }

        $fieldDefs = $this->entityManager
            ->getDefs()
            ->getEntity($entity->getEntityType())
            ->tryGetField($link);

        if (
            $linkDefs->getType() === RelationType::BELONGS_TO &&
            $fieldDefs?->getType() === FieldType::LINK
        ) {
            $setAccountLink = $entity->getValueObject($link);

            if (!$setAccountLink instanceof Link) {
                return false;
            }

            return in_array($setAccountLink->getId(), $accountIds);
        }

        if (
            $linkDefs->getType() === RelationType::BELONGS_TO_PARENT &&
            $fieldDefs?->getType() === FieldType::LINK_PARENT
        ) {
            $setLink = $entity->getValueObject($link);

            if (!$setLink instanceof LinkParent || $setLink->getEntityType() !== Account::ENTITY_TYPE) {
                return false;
            }

            return in_array($setLink->getId(), $accountIds);
        }

        foreach ($accountIds as $accountId) {
            $isRelated = $this->entityManager
                ->getRelation($entity, $link)
                ->isRelatedById($accountId);

            if ($isRelated) {
                return true;
            }
        }

        return false;
    }

    public function checkContact(User $user, Entity $entity): bool
    {
        $linkDefs = $this->metadataProvider->getContactLink($entity->getEntityType());

        if (!$linkDefs) {
            return false;
        }

        $link = $linkDefs->getName();

        $contactId = $user->getContactId();

        if (!$contactId) {
            return false;
        }

        $fieldDefs = $this->entityManager
            ->getDefs()
            ->getEntity($entity->getEntityType())
            ->tryGetField($link);

        if (
            $linkDefs->getType() === RelationType::BELONGS_TO &&
            $fieldDefs?->getType() === FieldType::LINK
        ) {
            $setContactLink = $entity->getValueObject($link);

            if (!$setContactLink instanceof Link) {
                return false;
            }

            return $setContactLink->getId() === $contactId;
        }

        if (
            $linkDefs->getType() === RelationType::BELONGS_TO_PARENT &&
            $fieldDefs?->getType() === FieldType::LINK_PARENT
        ) {
            $setLink = $entity->getValueObject($link);

            if (!$setLink instanceof LinkParent || $setLink->getEntityType() !== Contact::ENTITY_TYPE) {
                return false;
            }

            return $setLink->getId() === $contactId;
        }

        return$this->entityManager
            ->getRelation($entity, $link)
            ->isRelatedById($contactId);
    }
}
