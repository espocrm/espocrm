<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2022 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
 * Website: https://www.espocrm.com
 *
 * EspoCRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * EspoCRM is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with EspoCRM. If not, see http://www.gnu.org/licenses/.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

namespace Espo\Modules\Crm\Services;

use Espo\ORM\Entity;
use Espo\Services\Record;

/**
 * @extends Record<\Espo\Modules\Crm\Entities\CaseObj>
 */
class CaseObj extends Record
{
    protected $noEditAccessRequiredLinkList = [
        'articles',
    ];

    public function beforeCreateEntity(Entity $entity, $data)
    {
        parent::beforeCreateEntity($entity, $data);

        if ($this->user->isPortal()) {
            if (!$entity->has('accountId')) {
                if ($this->user->get('contactId')) {
                    $contact = $this->entityManager->getEntity('Contact', $this->user->get('contactId'));

                    if ($contact && $contact->get('accountId')) {
                        $entity->set('accountId', $contact->get('accountId'));
                    }
                }
            }
            if (!$entity->has('contactId')) {
                if ($this->user->get('contactId')) {
                    $entity->set('contactId', $this->user->get('contactId'));
                }
            }
        }
    }

    public function afterCreateEntity(Entity $entity, $data)
    {
        parent::afterCreateEntity($entity, $data);

        if (!empty($data->emailId)) {
            $email = $this->entityManager->getEntity('Email', $data->emailId);

            if ($email && !$email->get('parentId') && $this->getAcl()->check($email)) {
                $email->set([
                    'parentType' => 'Case',
                    'parentId' => $entity->getId(),
                ]);

                $this->entityManager->saveEntity($email);
            }
        }
    }
}
