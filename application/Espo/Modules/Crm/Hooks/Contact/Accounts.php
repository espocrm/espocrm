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

namespace Espo\Modules\Crm\Hooks\Contact;

use Espo\Core\Field\Link;
use Espo\Core\Hook\Hook\AfterSave;
use Espo\Core\Hook\Hook\BeforeSave;
use Espo\Modules\Crm\Entities\Contact;
use Espo\ORM\Entity;
use Espo\ORM\EntityManager;
use Espo\ORM\Name\Attribute;
use Espo\ORM\Repository\Option\SaveOptions;

/**
 * @implements BeforeSave<Contact>
 * @implements AfterSave<Contact>
 */
class Accounts implements BeforeSave, AfterSave
{
    private const string COLUMN_ROLE = Contact::COLUMN_ACCOUNTS_ROLE;
    private const string ATTR_TITLE = 'title';

    public function __construct(private EntityManager $entityManager) {}

    public function beforeSave(Entity $entity, SaveOptions $options): void
    {
        if (
            !$entity->isAttributeChanged(Contact::ATTR_ACCOUNT_ID) &&
            !$entity->isAttributeChanged(Contact::FIELD_ACCOUNTS . 'Ids')
        ) {
            return;
        }

        if (!$entity->getAccount() && $entity->getAccountsLinkMultiple()->getList()) {
            $first = $entity->getAccountsLinkMultiple()->getList()[0];

            $entity->setAccount(Link::create($first->getId(), $first->getName()));
        }
    }

    public function afterSave(Entity $entity, SaveOptions $options): void
    {
        $accountIdChanged = $entity->isAttributeChanged(Contact::ATTR_ACCOUNT_ID);
        $titleChanged = $entity->isAttributeChanged(self::ATTR_TITLE);

        /** @var ?string $fetchedAccountId */
        $fetchedAccountId = $entity->getFetched(Contact::ATTR_ACCOUNT_ID);
        $accountId = $entity->getAccount()?->getId();
        $title = $entity->getTitle();

        $relation = $this->entityManager
            ->getRDBRepositoryByClass(Contact::class)
            ->getRelation($entity, Contact::FIELD_ACCOUNTS);

        if (!$accountId && $fetchedAccountId) {
            $relation->unrelateById($fetchedAccountId);

            return;
        }

        if (!$accountIdChanged && !$titleChanged) {
            return;
        }

        if (!$accountId) {
            return;
        }

        $accountContact = $this->entityManager
            ->getRDBRepository(Contact::RELATIONSHIP_ACCOUNT_CONTACT)
            ->select([self::COLUMN_ROLE])
            ->where([
                'accountId' => $accountId,
                'contactId' => $entity->getId(),
                Attribute::DELETED => false,
            ])
            ->findOne();

        if (!$accountContact && $accountIdChanged) {
            $relation->relateById($accountId, [self::COLUMN_ROLE => $title]);

            return;
        }

        if ($titleChanged && $accountContact && $title !== $accountContact->get(self::COLUMN_ROLE)) {
            $relation->updateColumnsById($accountId, [self::COLUMN_ROLE => $title]);
        }
    }
}
