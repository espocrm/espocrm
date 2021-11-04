<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2021 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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
    MassAction\Actions\MassUpdate as MassUpdateOriginal,
    MassAction\QueryBuilder,
    MassAction\Params,
    MassAction\Result,
    MassAction\Data,
    MassAction\MassAction,
    Utils\File\Manager as FileManager,
    DataManager,
    Acl,
    ORM\EntityManager,
    Exceptions\Forbidden,
};

use Espo\{
    Entities\User,
    ORM\Entity,
};

class MassUpdate implements MassAction
{
    private $massUpdateOriginal;

    private $queryBuilder;

    private $entityManager;

    private $acl;

    private $user;

    private $fileManager;

    private $dataManager;

    public function __construct(
        MassUpdateOriginal $massUpdateOriginal,
        QueryBuilder $queryBuilder,
        EntityManager $entityManager,
        Acl $acl,
        User $user,
        FileManager $fileManager,
        DataManager $dataMaanger
    ) {
        $this->massUpdateOriginal = $massUpdateOriginal;
        $this->queryBuilder = $queryBuilder;
        $this->entityManager = $entityManager;
        $this->acl = $acl;
        $this->user = $user;
        $this->fileManager = $fileManager;
        $this->dataManager = $dataMaanger;
    }

    public function process(Params $params, Data $data): Result
    {
        $entityType = $params->getEntityType();

        if (!$this->acl->check($entityType, 'edit')) {
            throw new Forbidden("No edit access for '{$entityType}'.");
        }

        if ($this->acl->get('massUpdatePermission') !== 'yes') {
            throw new Forbidden("No mass-update permission.");
        }

        if (
            $data->has('type') ||
            $data->has('password') ||
            $data->has('emailAddress') ||
            $data->has('isAdmin') ||
            $data->has('isSuperAdmin') ||
            $data->has('isPortalUser')
        ) {
            throw new Forbidden("Not allowed fields.");
        }

        $query = $this->queryBuilder->build($params);

        $collection = $this->entityManager
            ->getRDBRepository('User')
            ->clone($query)
            ->sth()
            ->select(['id'])
            ->find();

        foreach ($collection as $entity) {
            $this->checkEntity($entity, $data);
        }

        $result = $this->massUpdateOriginal->process($params, $data);

        $this->afterProcess($result, $data);

        return $result;
    }

    protected function checkEntity(Entity $entity, Data $data): void
    {
        if ($entity->getId() === 'system') {
            throw new Forbidden("Can't update 'system' user.");
        }

        if ($entity->getId() === $this->user->getId()) {
            if ($data->has('isActive')) {
                throw new Forbidden("Can't change 'isActive' field for own user.");
            }
        }
    }

    protected function afterProcess(Result $result, Data $dataWrapped): void
    {
        $data = $dataWrapped->getRaw();

        if (
            property_exists($data, 'rolesIds') ||
            property_exists($data, 'teamsIds') ||
            property_exists($data, 'type') ||
            property_exists($data, 'portalRolesIds') ||
            property_exists($data, 'portalsIds')
        ) {
            foreach ($result->getIds() as $id) {
                $this->clearRoleCache($id);
            }

            $this->dataManager->updateCacheTimestamp();
        }

        if (
            property_exists($data, 'portalRolesIds') ||
            property_exists($data, 'portalsIds') ||
            property_exists($data, 'contactId') ||
            property_exists($data, 'accountsIds')
        ) {
            $this->clearPortalRolesCache();

            $this->dataManager->updateCacheTimestamp();
        }
    }

    protected function clearRoleCache(string $id): void
    {
        $this->fileManager->removeFile('data/cache/application/acl/' . $id . '.php');
    }

    protected function clearPortalRolesCache(): void
    {
        $this->fileManager->removeInDir('data/cache/application/aclPortal');
    }
}
