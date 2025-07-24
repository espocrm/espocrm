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

namespace Espo\Core\Console\Commands;

use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\NotFound;
use Espo\Entities\User;
use Espo\ORM\EntityManager;
use Espo\Core\AclManager;
use Espo\Core\Console\Command;
use Espo\Core\Console\Command\Params;
use Espo\Core\Console\IO;
use Espo\Core\Container;
use Espo\Core\Portal\Application as PortalApplication;
use Espo\Core\Acl\Table;

/**
 * Checks access for websocket topic subscription. Prints `true` if access allowed.
 *
 * @noinspection PhpUnused
 */
class AclCheck implements Command
{
    public function __construct(private Container $container)
    {}

    public function run(Params $params, IO $io): void
    {
        $userId = $params->getOption('userId');
        $scope = $params->getOption('scope');
        $id = $params->getOption('id');
        /** @var Table::ACTION_*|null $action */
        $action = $params->getOption('action');

        $io->setExitStatus(1);

        if (!$userId || !$scope) {
            return;
        }

        if ($params->hasOption('id') && !$id) {
            return;
        }

        $entityManager = $this->container->getByClass(EntityManager::class);

        $user = $entityManager->getRDBRepositoryByClass(User::class)->getById($userId);

        if (!$user) {
            return;
        }

        if ($user->isPortal()) {
            $this->processPortal(
                io: $io,
                userId: $userId,
                scope: $scope,
                action: $action,
                id: $id,
                user: $user,
            );
        }

        if (!$this->check($user, $scope, $id, $action, $this->container)) {
            return;
        }

        $io->setExitStatus(0);
        $io->write('true');
    }

    /**
     * @param Table::ACTION_*|null $action
     * @noinspection PhpDocSignatureInspection
     */
    private function check(
        User $user,
        string $scope,
        ?string $id,
        ?string $action,
        Container $container
    ): bool {

        if (!$id) {
            $aclManager = $container->getByClass(AclManager::class);

            return $aclManager->check($user, $scope, $action);
        }

        $entityManager = $container->getByClass(EntityManager::class);

        $entity = $entityManager->getEntityById($scope, $id);

        if (!$entity) {
            return false;
        }

        $aclManager = $container->getByClass(AclManager::class);

        return $aclManager->check($user, $entity, $action);
    }

    /**
     * @param Table::ACTION_*|null $action
     * @noinspection PhpDocSignatureInspection
     */
    private function processPortal(
        IO $io,
        string $userId,
        string $scope,
        ?string $action,
        ?string $id,
        User $user,
    ): void {

        $portalIds = $user->getLinkMultipleIdList('portals');

        foreach ($portalIds as $portalId) {
            try {
                $application = new PortalApplication($portalId);
            } catch (Forbidden|NotFound) {
                return;
            }

            $containerPortal = $application->getContainer();
            $entityManager = $containerPortal->getByClass(EntityManager::class);

            $user = $entityManager->getRDBRepositoryByClass(User::class)->getById($userId);

            if (!$user) {
                return;
            }

            $result = $this->check($user, $scope, $id, $action, $containerPortal);

            if (!$result) {
                continue;
            }

            $io->setExitStatus(0);
            $io->write('true');

            return;
        }
    }
}
