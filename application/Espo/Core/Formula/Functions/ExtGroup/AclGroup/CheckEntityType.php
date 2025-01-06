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

namespace Espo\Core\Formula\Functions\ExtGroup\AclGroup;

use Espo\Core\Acl\Table;
use Espo\Core\Formula\EvaluatedArgumentList;
use Espo\Core\Formula\Exceptions\BadArgumentType;
use Espo\Core\Formula\Exceptions\TooFewArguments;
use Espo\Core\Formula\Func;
use Espo\Core\Utils\Acl\UserAclManagerProvider;
use Espo\Entities\User;
use Espo\ORM\EntityManager;

/**
 * @noinspection PhpUnused
 */
class CheckEntityType implements Func
{
    public function __construct(
        private UserAclManagerProvider $userAclManagerProvider,
        private EntityManager $entityManager,
    ) {}

    public function process(EvaluatedArgumentList $arguments): bool
    {
        if (count($arguments) < 3) {
            throw TooFewArguments::create(3);
        }

        $userId = $arguments[0];
        $entityType = $arguments[1];
        $id = $arguments[2];

        /** @var Table::ACTION_*|null $action */
        $action = $arguments[3] ?? Table::ACTION_READ;

        if (!is_string($userId)) {
            throw BadArgumentType::create(1, 'string');
        }

        if (!is_string($entityType)) {
            throw BadArgumentType::create(2, 'string');
        }

        if (!is_string($id)) {
            throw BadArgumentType::create(3, 'string');
        }

        if (!is_string($action)) {
            throw BadArgumentType::create(4, 'string');
        }

        $user = $this->entityManager->getRDBRepositoryByClass(User::class)->getById($userId);

        if (!$user) {
            return false;
        }

        $entity = $this->entityManager->getEntityById($entityType, $id);

        if (!$entity) {
            return false;
        }

        return $this->userAclManagerProvider->get($user)->checkEntity($user, $entity, $action);
    }
}
