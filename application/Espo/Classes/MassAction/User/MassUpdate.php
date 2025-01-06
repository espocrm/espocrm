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

use Espo\Core\Exceptions\BadRequest;
use Espo\Core\MassAction\Actions\MassUpdate as MassUpdateOriginal;
use Espo\Core\MassAction\QueryBuilder;
use Espo\Core\MassAction\Params;
use Espo\Core\MassAction\Result;
use Espo\Core\MassAction\Data;
use Espo\Core\MassAction\MassAction;
use Espo\Core\Utils\File\Manager as FileManager;
use Espo\Core\DataManager;
use Espo\Core\Acl;
use Espo\Core\Acl\Table;

use Espo\Core\Exceptions\Forbidden;

use Espo\Core\Utils\SystemUser;
use Espo\Entities\User;
use Espo\ORM\EntityManager;

use Espo\ORM\Name\Attribute;
use Espo\Tools\MassUpdate\Data as MassUpdateData;

class MassUpdate implements MassAction
{
    private const PERMISSION = Acl\Permission::MASS_UPDATE;

    /** @var string[] */
    private array $notAllowedAttributeList = [
        'type',
        'password',
        'emailAddress',
        'isAdmin',
        'isSuperAdmin',
        'isPortalUser',
    ];

    public function __construct(
        private MassUpdateOriginal $massUpdateOriginal,
        private QueryBuilder $queryBuilder,
        private EntityManager $entityManager,
        private Acl $acl,
        private User $user,
        private FileManager $fileManager,
        private DataManager $dataManager
    ) {}

    /**
     * @throws Forbidden
     * @throws BadRequest
     */
    public function process(Params $params, Data $data): Result
    {
        $entityType = $params->getEntityType();

        if (!$this->user->isAdmin()) {
            throw new Forbidden("Only admin can mass-update users.");
        }

        if (!$this->acl->check($entityType, Table::ACTION_EDIT)) {
            throw new Forbidden("No edit access for '{$entityType}'.");
        }

        if ($this->acl->getPermissionLevel(self::PERMISSION) !== Table::LEVEL_YES) {
            throw new Forbidden("No mass-update permission.");
        }

        $massUpdateData = MassUpdateData::fromMassActionData($data);

        $this->checkAccess($massUpdateData);

        $query = $this->queryBuilder->build($params);

        $collection = $this->entityManager
            ->getRDBRepository(User::ENTITY_TYPE)
            ->clone($query)
            ->sth()
            ->select([Attribute::ID, 'userName'])
            ->find();

        foreach ($collection as $entity) {
            $this->checkEntity($entity, $massUpdateData);
        }

        $result = $this->massUpdateOriginal->process($params, $data);

        $this->afterProcess($result, $massUpdateData);

        return $result;
    }

    /**
     * @throws Forbidden
     */
    private function checkAccess(MassUpdateData $data): void
    {
        foreach ($this->notAllowedAttributeList as $attribute) {
            if ($data->has($attribute)) {
                throw new Forbidden("Attribute '{$attribute}' not allowed for mass-update.");
            }
        }
    }

    /**
     * @throws Forbidden
     */
    private function checkEntity(User $entity, MassUpdateData $data): void
    {
        if ($entity->getUserName() === SystemUser::NAME) {
            throw new Forbidden("Can't update 'system' user.");
        }

        if ($entity->getId() === $this->user->getId()) {
            if ($data->has('isActive')) {
                throw new Forbidden("Can't change 'isActive' field for own user.");
            }
        }
    }

    private function afterProcess(Result $result, MassUpdateData $dataWrapped): void
    {
        $data = $dataWrapped->getValues();

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

    private function clearRoleCache(string $id): void
    {
        $this->fileManager->removeFile('data/cache/application/acl/' . $id . '.php');
    }

    private function clearPortalRolesCache(): void
    {
        $this->fileManager->removeInDir('data/cache/application/aclPortal');
    }
}
