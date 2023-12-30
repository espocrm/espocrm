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

use Espo\ORM\Entity;

use Espo\Services\Record;

/**
 * @extends Record<\Espo\Modules\Crm\Entities\Contact>
 */
class Contact extends Record
{
    protected $readOnlyAttributeList = [
        'inboundEmailId',
        'portalUserId'
    ];

    protected $linkMandatorySelectAttributeList = [
        'targetLists' => ['isOptedOut'],
    ];

    protected $mandatorySelectAttributeList = [
        'accountId',
        'accountName',
    ];

    protected function afterCreateEntity(Entity $entity, $data)
    {
        if (!empty($data->emailId)) {
            $email = $this->getEntityManager()->getEntity('Email', $data->emailId);

            if ($email && !$email->get('parentId') && $this->getAcl()->check($email)) {
                if ($this->getConfig()->get('b2cMode') || !$entity->get('accountId')) {
                    $email->set([
                        'parentType' => 'Contact',
                        'parentId' => $entity->getId(),
                    ]);
                }
                else {
                    if ($entity->get('accountId')) { /** @phpstan-ignore-line */
                        $email->set([
                            'parentType' => 'Account',
                            'parentId' => $entity->get('accountId')
                        ]);
                    }
                }

                $this->getEntityManager()->saveEntity($email);
            }
        }
    }
}
