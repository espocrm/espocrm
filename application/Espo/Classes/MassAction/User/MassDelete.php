<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2023 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

namespace Espo\Classes\MassAction\User;

use Espo\Core\{
    ApplicationUser,
    MassAction\Actions\MassDelete as MassDeleteOriginal,
    MassAction\QueryBuilder,
    MassAction\Params,
    MassAction\Result,
    MassAction\Data,
    MassAction\MassAction,
    Acl,
    ORM\EntityManager,
    Exceptions\Forbidden,
};

use Espo\{
    Entities\User,
    ORM\Entity,
};

class MassDelete implements MassAction
{
    private MassDeleteOriginal $massDeleteOriginal;
    private QueryBuilder $queryBuilder;
    private EntityManager $entityManager;
    private Acl $acl;
    private User $user;

    public function __construct(
        MassDeleteOriginal $massDeleteOriginal,
        QueryBuilder $queryBuilder,
        EntityManager $entityManager,
        Acl $acl,
        User $user
    ) {
        $this->massDeleteOriginal = $massDeleteOriginal;
        $this->queryBuilder = $queryBuilder;
        $this->entityManager = $entityManager;
        $this->acl = $acl;
        $this->user = $user;
    }

    /**
     * @throws Forbidden
     */
    public function process(Params $params, Data $data): Result
    {
        $entityType = $params->getEntityType();

        if (!$this->acl->check($entityType, Acl\Table::ACTION_DELETE)) {
            throw new Forbidden("No delete access for '{$entityType}'.");
        }

        if (
            !$params->hasIds() &&
            $this->acl->getPermissionLevel('massUpdatePermission') !== Acl\Table::LEVEL_YES
        ) {
            throw new Forbidden("No mass-update permission.");
        }

        $query = $this->queryBuilder->build($params);

        $collection = $this->entityManager
            ->getRDBRepository(User::ENTITY_TYPE)
            ->clone($query)
            ->sth()
            ->select(['id'])
            ->find();

        foreach ($collection as $entity) {
            $this->checkEntity($entity);
        }

        return $this->massDeleteOriginal->process($params, $data);
    }

    /**
     * @throws Forbidden
     */
    protected function checkEntity(Entity $entity): void
    {
        if ($entity->getId() === ApplicationUser::SYSTEM_USER_ID) {
            throw new Forbidden("Can't delete 'system' user.");
        }

        if ($entity->getId() === $this->user->getId()) {
            throw new Forbidden("Can't delete own user.");
        }
    }
}
