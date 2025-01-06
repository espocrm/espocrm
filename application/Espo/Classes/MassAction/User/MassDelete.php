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

namespace Espo\Classes\MassAction\User;

use Espo\Core\Acl;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Error;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\MassAction\Actions\MassDelete as MassDeleteOriginal;
use Espo\Core\MassAction\Data;
use Espo\Core\MassAction\MassAction;
use Espo\Core\MassAction\Params;
use Espo\Core\MassAction\QueryBuilder;
use Espo\Core\MassAction\Result;
use Espo\Core\ORM\EntityManager;

use Espo\Core\Utils\SystemUser;
use Espo\Entities\User;
use Espo\ORM\Name\Attribute;

/**
 * Extended to forbid removal of own and system users.
 */
class MassDelete implements MassAction
{
    public function __construct(
        private MassDeleteOriginal $massDeleteOriginal,
        private QueryBuilder $queryBuilder,
        private EntityManager $entityManager,
        private Acl $acl,
        private User $user
    ) {}

    /**
     * @throws Forbidden
     * @throws BadRequest
     * @throws Error
     */
    public function process(Params $params, Data $data): Result
    {
        $entityType = $params->getEntityType();

        if (!$this->acl->check($entityType, Acl\Table::ACTION_DELETE)) {
            throw new Forbidden("No delete access for '$entityType'.");
        }

        if (
            !$params->hasIds() &&
            $this->acl->getPermissionLevel(Acl\Permission::MASS_UPDATE) !== Acl\Table::LEVEL_YES
        ) {
            throw new Forbidden("No mass-update permission.");
        }

        $query = $this->queryBuilder->build($params);

        $collection = $this->entityManager
            ->getRDBRepository(User::ENTITY_TYPE)
            ->clone($query)
            ->sth()
            ->select([Attribute::ID, 'userName'])
            ->find();

        foreach ($collection as $entity) {
            $this->checkEntity($entity);
        }

        return $this->massDeleteOriginal->process($params, $data);
    }

    /**
     * @throws Forbidden
     */
    private function checkEntity(User $entity): void
    {
        if ($entity->getUserName() === SystemUser::NAME) {
            throw new Forbidden("Can't delete 'system' user.");
        }

        if ($entity->getId() === $this->user->getId()) {
            throw new Forbidden("Can't delete own user.");
        }
    }
}
