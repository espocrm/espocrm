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

namespace Espo\Modules\Crm\Hooks\Opportunity;

use Espo\Core\Hook\Hook\AfterSave;
use Espo\Core\ORM\Repository\Option\SaveContext;
use Espo\Modules\Crm\Entities\Contact;
use Espo\Modules\Crm\Entities\Opportunity;
use Espo\ORM\Entity;
use Espo\ORM\EntityManager;
use Espo\ORM\Repository\Option\SaveOptions;

/**
 * @implements AfterSave<Opportunity>
 */
class Contacts implements AfterSave
{
    public function __construct(private EntityManager $entityManager) {}

    /**
     * @param Opportunity $entity
     */
    public function afterSave(Entity $entity, SaveOptions $options): void
    {
        if (!$entity->isAttributeChanged('contactId')) {
            return;
        }

        /** @var ?string $contactId */
        $contactId = $entity->get('contactId');
        $contactIdList = $entity->get('contactsIds') ?? [];
        $fetchedContactId = $entity->getFetched('contactId');

        $relation = $this->entityManager
            ->getRDBRepositoryByClass(Opportunity::class)
            ->getRelation($entity, 'contacts');

        if (!$contactId) {
            if ($fetchedContactId && $relation->isRelatedById($fetchedContactId)) {
                $relation->unrelateById($fetchedContactId, [
                    SaveContext::NAME => $options->get(SaveContext::NAME),
                ]);
            }

            return;
        }

        if (in_array($contactId, $contactIdList)) {
            return;
        }

        $contact = $this->entityManager
            ->getRDBRepositoryByClass(Contact::class)
            ->getById($contactId);

        if (!$contact) {
            return;
        }

        if ($relation->isRelated($contact)) {
            return;
        }

        $relation->relateById($contactId, [
            SaveContext::NAME => $options->get(SaveContext::NAME),
        ]);
    }
}
