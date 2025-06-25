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

namespace Espo\Modules\Crm\Classes\RecordHooks\Case;

use Espo\Core\Acl;
use Espo\Core\Name\Field;
use Espo\Core\Record\Hook\SaveHook;
use Espo\Entities\Email;
use Espo\Modules\Crm\Entities\Account;
use Espo\Modules\Crm\Entities\CaseObj;
use Espo\Modules\Crm\Entities\Contact;
use Espo\Modules\Crm\Entities\Lead;
use Espo\ORM\Entity;
use Espo\ORM\EntityManager;

use RuntimeException;

/**
 * @implements SaveHook<CaseObj>
 * @noinspection PhpUnused
 */
class AfterCreate implements SaveHook
{
    private const EMAIL_REPLY_LEVEL = 3;
    private const EMAIL_REPLY_LIMIT = 2;
    private const EMAIL_REPLY_LIMIT_SECOND = 1;

    public function __construct(
        private EntityManager $entityManager,
        private Acl $acl,
    ) {}

    public function process(Entity $entity): void
    {
        /** @var ?string $emailId */
        $emailId = $entity->get('originalEmailId');

        if (!$emailId) {
            return;
        }

        $email = $this->entityManager->getRDBRepositoryByClass(Email::class)->getById($emailId);

        if (!$email) {
            return;
        }

        $this->changeEmailParent($email, $entity);
    }

    private function changeEmailParent(Email $email, CaseObj $entity, int $level = 0): void
    {
        if (!$this->acl->checkEntityRead($email)) {
            return;
        }

        if (
            $email->getParentId() &&
            !in_array($email->getParentType(), [
                Account::ENTITY_TYPE,
                Contact::ENTITY_TYPE,
                Lead::ENTITY_TYPE,
            ])
        ) {
            return;
        }

        $email->setParent($entity);
        $this->entityManager->saveEntity($email);

        if ($level === self::EMAIL_REPLY_LEVEL) {
            return;
        }

        $limit = $level === 0 ? self::EMAIL_REPLY_LIMIT : self::EMAIL_REPLY_LIMIT_SECOND;

        $replies = $this->entityManager
            ->getRelation($email, Email::LINK_REPLIES)
            ->limit(0, $limit)
            ->order(Field::CREATED_AT)
            ->find();

        foreach ($replies as $reply) {
            if (!$reply instanceof Email) {
                throw new RuntimeException();
            }

            $this->changeEmailParent($reply, $entity, $level + 1);
        }
    }
}
