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

namespace Espo\Modules\Crm\Classes\RecordHooks\Contact;

use Espo\Core\Acl;
use Espo\Core\Record\Hook\SaveHook;
use Espo\Core\Utils\Config;
use Espo\Entities\Email;
use Espo\Modules\Crm\Entities\Account;
use Espo\Modules\Crm\Entities\Contact;
use Espo\ORM\Entity;
use Espo\ORM\EntityManager;

/**
 * @implements SaveHook<Contact>
 */
class AfterCreate implements SaveHook
{
    public function __construct(
        private EntityManager $entityManager,
        private Config $config,
        private Acl $acl
    ) {}

    public function process(Entity $entity): void
    {
        $emailId = $entity->get('originalEmailId');

        if (!$emailId) {
            return;
        }

        /** @var ?Email $email */
        $email = $this->entityManager->getEntityById(Email::ENTITY_TYPE, $emailId);

        if (!$email || $email->getParentId() || !$this->acl->check($email)) {
            return;
        }

        if ($this->config->get('b2cMode') || !$entity->getAccount()) {
            $email->set([
                'parentType' => Contact::ENTITY_TYPE,
                'parentId' => $entity->getId(),
            ]);
        } else if ($entity->getAccount()) {
            $email->set([
                'parentType' => Account::ENTITY_TYPE,
                'parentId' => $entity->getAccount()->getId(),
            ]);
        }

        $this->entityManager->saveEntity($email);
    }
}
