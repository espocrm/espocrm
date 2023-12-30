<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2024 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

use Espo\ORM\BaseEntity;
use Espo\ORM\Entity;
use Espo\ORM\EntityManager;

use Espo\Entities\User;

use Espo\Core\Acl\OwnershipOwnChecker;

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
    private const ENTITY_ACCOUNT = 'Account';
    private const ENTITY_CONTACT = 'Contact';

    private const ATTR_CREATED_BY_ID = 'createdById';
    private const ATTR_ACCOUNT_ID = 'accountId';
    private const ATTR_CONTACT_ID = 'contactId';
    private const ATTR_PARENT_ID = 'parentId';
    private const ATTR_PARENT_TYPE = 'parentType';
    private const FIELD_CONTACT = 'contact';
    private const FIELD_CONTACTS = 'contacts';
    private const FIELD_ACCOUNT = 'account';
    private const FIELD_ACCOUNTS = 'accounts';
    private const FIELD_PARENT = 'parent';

    public function __construct(private EntityManager $entityManager)
    {}

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
        /** @var string[] $accountIdList */
        $accountIdList = $user->getLinkMultipleIdList(self::FIELD_ACCOUNTS);

        if (!count($accountIdList)) {
            return false;
        }

        if (
            $entity->hasAttribute(self::ATTR_ACCOUNT_ID) &&
            $this->getRelationParam($entity, self::FIELD_ACCOUNT, 'entity') === self::ENTITY_ACCOUNT
        ) {
            if (in_array($entity->get(self::ATTR_ACCOUNT_ID), $accountIdList)) {
                return true;
            }
        }

        if (
            $entity->hasRelation(self::FIELD_ACCOUNTS) &&
            $this->getRelationParam($entity, self::FIELD_ACCOUNTS, 'entity') === self::ENTITY_ACCOUNT
        ) {
            $repository = $this->entityManager->getRDBRepository($entity->getEntityType());

            foreach ($accountIdList as $accountId) {
                if (
                    $repository
                        ->getRelation($entity, self::FIELD_ACCOUNTS)
                        ->isRelatedById($accountId)
                ) {
                    return true;
                }
            }
        }

        if ($entity->hasAttribute(self::ATTR_PARENT_ID) && $entity->hasRelation(self::FIELD_PARENT)) {
            if (
                $entity->get(self::ATTR_PARENT_TYPE) === self::ENTITY_ACCOUNT &&
                in_array($entity->get(self::ATTR_PARENT_ID), $accountIdList)
            ) {
                return true;
            }
        }

        return false;
    }

    public function checkContact(User $user, Entity $entity): bool
    {
        $contactId = $user->get(self::ATTR_CONTACT_ID);

        if (!$contactId) {
            return false;
        }

        if (
            $entity->hasAttribute(self::ATTR_CONTACT_ID) &&
            $this->getRelationParam($entity, self::FIELD_CONTACT, 'entity') === self::ENTITY_CONTACT
        ) {
            if ($entity->get(self::ATTR_CONTACT_ID) === $contactId) {
                return true;
            }
        }

        if (
            $entity->hasRelation(self::FIELD_CONTACTS) &&
            $this->getRelationParam($entity, self::FIELD_CONTACTS, 'entity') === self::ENTITY_CONTACT
        ) {
            $repository = $this->entityManager->getRDBRepository($entity->getEntityType());

            if (
                $repository
                    ->getRelation($entity, self::FIELD_CONTACTS)
                    ->isRelatedById($contactId)
            ) {
                return true;
            }
        }

        if ($entity->hasAttribute(self::ATTR_PARENT_ID) && $entity->hasRelation(self::FIELD_PARENT)) {
            if (
                $entity->get(self::ATTR_PARENT_TYPE) === self::ENTITY_CONTACT &&
                $entity->get(self::ATTR_PARENT_ID) === $contactId
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return mixed
     */
    private function getRelationParam(Entity $entity, string $relation, string $param)
    {
        if ($entity instanceof BaseEntity) {
            return $entity->getRelationParam($relation, $param);
        }

        $entityDefs = $this->entityManager
            ->getDefs()
            ->getEntity($entity->getEntityType());

        if (!$entityDefs->hasRelation($relation)) {
            return null;
        }

        return $entityDefs->getRelation($relation)->getParam($param);
    }
}
