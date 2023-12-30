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

namespace Espo\Modules\Crm\Services;

use Espo\Entities\Email;
use Espo\Modules\Crm\Entities\CaseObj as CaseEntity;
use Espo\Modules\Crm\Entities\Contact as ContactEntity;
use Espo\ORM\Entity;
use Espo\Services\Record;

/**
 * @extends Record<CaseEntity>
 */
class CaseObj extends Record
{
    /**
     * @param CaseEntity $entity
     */
    public function beforeCreateEntity(Entity $entity, $data)
    {
        parent::beforeCreateEntity($entity, $data);

        if ($this->user->isPortal()) {
            if (!$entity->has('accountId')) {
                if ($this->user->getContactId()) {
                    /** @var ?ContactEntity $contact */
                    $contact = $this->entityManager
                        ->getEntityById(ContactEntity::ENTITY_TYPE, $this->user->getContactId());

                    if ($contact && $contact->getAccount()) {
                        $entity->set('accountId', $contact->getAccount()->getId());
                    }
                }
            }

            if (!$entity->has('contactId')) {
                if ($this->user->getContactId()) {
                    $entity->set('contactId', $this->user->getContactId());
                }
            }
        }
    }

    public function afterCreateEntity(Entity $entity, $data)
    {
        parent::afterCreateEntity($entity, $data);

        if (!empty($data->emailId)) {
            /** @var ?Email $email */
            $email = $this->entityManager->getEntityById(Email::ENTITY_TYPE, $data->emailId);

            if ($email && !$email->getParentId() && $this->acl->check($email)) {
                $email->set([
                    'parentType' => CaseEntity::ENTITY_TYPE,
                    'parentId' => $entity->getId(),
                ]);

                $this->entityManager->saveEntity($email);
            }
        }
    }
}
