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

namespace Espo\Modules\Crm\Hooks\Task;

use Espo\Core\Hook\Hook\BeforeSave;
use Espo\Core\Name\Field;
use Espo\Core\ORM\Entity as CoreEntity;
use Espo\Modules\Crm\Entities\Account;
use Espo\Modules\Crm\Entities\Contact;
use Espo\Modules\Crm\Entities\Lead;
use Espo\Modules\Crm\Entities\Task;
use Espo\ORM\Defs\Params\RelationParam;
use Espo\ORM\Entity;
use Espo\ORM\EntityManager;
use Espo\ORM\Name\Attribute;
use Espo\ORM\Repository\Option\SaveOptions;

/**
 * @implements BeforeSave<Task>
 */
class ParentLink implements BeforeSave
{
    public function __construct(private EntityManager $entityManager) {}

    /**
     * @param Task $entity
     */
    public function beforeSave(Entity $entity, SaveOptions $options): void
    {
        if (!$entity->isNew() && $entity->isAttributeChanged('parentId')) {
            $entity->set('accountId', null);
            $entity->set('contactId', null);
            $entity->set('accountName', null);
            $entity->set('contactName', null);
        }

        if (!$entity->isAttributeChanged('parentId') && !$entity->isAttributeChanged('parentType')) {
            return;
        }

        $parent = null;

        $parentId = $entity->get('parentId');
        $parentType = $entity->get('parentType');

        if ($parentId && $parentType && $this->entityManager->hasRepository($parentType)) {
            $columnList = ['id', 'name'];

            $defs = $this->entityManager->getMetadata()->getDefs();

            if ($defs->getEntity($parentType)->hasAttribute('accountId')) {
                $columnList[] = 'accountId';
            }

            if ($defs->getEntity($parentType)->hasAttribute('contactId')) {
                $columnList[] = 'contactId';
            }

            if ($parentType === Lead::ENTITY_TYPE) {
                $columnList[] = 'status';
                $columnList[] = 'createdAccountId';
                $columnList[] = 'createdAccountName';
                $columnList[] = 'createdContactId';
                $columnList[] = 'createdContactName';
            }

            $parent = $this->entityManager
                ->getRDBRepository($parentType)
                ->select($columnList)
                ->where([Attribute::ID => $parentId])
                ->findOne();
        }

        $accountId = null;
        $contactId = null;
        $accountName = null;
        $contactName = null;

        if ($parent) {
            if ($parent instanceof Account) {
                $accountId = $parent->getId();
                $accountName = $parent->get(Field::NAME);
            } else if (
                $parent instanceof Lead &&
                $parent->getStatus() == Lead::STATUS_CONVERTED
            ) {
                if ($parent->get('createdAccountId')) {
                    $accountId = $parent->get('createdAccountId');
                    $accountName = $parent->get('createdAccountName');
                }

                if ($parent->get('createdContactId')) {
                    $contactId = $parent->get('createdContactId');
                    $contactName = $parent->get('createdContactName');
                }
            } else if ($parent instanceof Contact) {
                $contactId = $parent->getId();
                $contactName = $parent->get(Field::NAME);
            }

            if (
                !$accountId &&
                $parent->get('accountId') &&
                $parent instanceof CoreEntity &&
                $parent->getRelationParam('account', RelationParam::ENTITY) === Account::ENTITY_TYPE
            ) {
                $accountId = $parent->get('accountId');
            }

            if (
                !$contactId &&
                $parent->get('contactId') &&
                $parent instanceof CoreEntity &&
                $parent->getRelationParam('contact', RelationParam::ENTITY) === Contact::ENTITY_TYPE
            ) {
                $contactId = $parent->get('contactId');
            }
        }

        $entity->set('accountId', $accountId);
        $entity->set('accountName', $accountName);

        $entity->set('contactId', $contactId);
        $entity->set('contactName', $contactName);

        if (
            $entity->get('accountId') &&
            !$entity->get('accountName')
        ) {
            $account = $this->entityManager
                ->getRDBRepository(Account::ENTITY_TYPE)
                ->select([Attribute::ID, 'name'])
                ->where([Attribute::ID => $entity->get('accountId')])
                ->findOne();

            if ($account) {
                $entity->set('accountName', $account->get(Field::NAME));
            }
        }

        if (
            $entity->get('contactId') &&
            !$entity->get('contactName')
        ) {
            $contact = $this->entityManager
                ->getRDBRepository(Contact::ENTITY_TYPE)
                ->select([Attribute::ID, 'name'])
                ->where([Attribute::ID => $entity->get('contactId')])
                ->findOne();

            if ($contact) {
                $entity->set('contactName', $contact->get(Field::NAME));
            }
        }
    }
}
