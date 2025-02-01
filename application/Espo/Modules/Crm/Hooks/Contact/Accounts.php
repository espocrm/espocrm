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

namespace Espo\Modules\Crm\Hooks\Contact;

use Espo\Core\Hook\Hook\AfterSave;
use Espo\Modules\Crm\Entities\Contact;
use Espo\ORM\Entity;
use Espo\ORM\EntityManager;
use Espo\ORM\Name\Attribute;
use Espo\ORM\Repository\Option\SaveOptions;

/**
 * @implements AfterSave<Contact>
 */
class Accounts implements AfterSave
{
    public function __construct(private EntityManager $entityManager) {}

    /**
     * @param Contact $entity
     */
    public function afterSave(Entity $entity, SaveOptions $options): void
    {
        $accountIdChanged = $entity->isAttributeChanged('accountId');
        $titleChanged = $entity->isAttributeChanged('title');

        /** @var ?string $fetchedAccountId */
        $fetchedAccountId = $entity->getFetched('accountId');
        $accountId = $entity->getAccount()?->getId();
        $title = $entity->getTitle();

        $relation = $this->entityManager
            ->getRDBRepositoryByClass(Contact::class)
            ->getRelation($entity, 'accounts');

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
            ->getRDBRepository('AccountContact')
            ->select(['role'])
            ->where([
                'accountId' => $accountId,
                'contactId' => $entity->getId(),
                Attribute::DELETED => false,
            ])
            ->findOne();

        if (!$accountContact && $accountIdChanged) {
            $relation->relateById($accountId, ['role' => $title]);

            return;
        }

        if ($titleChanged && $accountContact && $title !== $accountContact->get('role')) {
            $relation->updateColumnsById($accountId, ['role' => $title]);
        }
    }
}
