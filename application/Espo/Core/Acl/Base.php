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

namespace Espo\Core\Acl;

use Espo\Core\Interfaces\Injectable;

use Espo\ORM\Entity;
use Espo\Entities\User;
use Espo\Core\Acl\AccessChecker\ScopeChecker;
use Espo\Core\Acl\AccessChecker\ScopeCheckerData;
use Espo\Core\AclManager;
use Espo\Core\ORM\EntityManager;
use Espo\Core\Utils\Config;

/**
 * @deprecated As of v6.0. Use AccessChecker interfaces instead.
 * @see https://docs.espocrm.com/development/metadata/acl-defs/
 */
class Base implements AccessChecker, Injectable
{
    protected $dependencyList = []; /** @phpstan-ignore-line */

    protected $dependencies = []; /** @phpstan-ignore-line */

    protected $injections = []; /** @phpstan-ignore-line */

    protected $entityManager; /** @phpstan-ignore-line */

    protected $aclManager; /** @phpstan-ignore-line */

    protected $config; /** @phpstan-ignore-line */

    protected $defaultChecker; /** @phpstan-ignore-line */

    protected $scopeChecker; /** @phpstan-ignore-line */

    public function __construct(
        EntityManager $entityManager,
        AclManager $aclManager,
        Config $config,
        DefaultAccessChecker $defaultChecker,
        ScopeChecker $scopeChecker
    ) {
        $this->entityManager = $entityManager;
        $this->aclManager = $aclManager;
        $this->config = $config;
        $this->defaultChecker = $defaultChecker;
        $this->scopeChecker = $scopeChecker;

        $this->init();
    }

    public function check(User $user, ScopeData $data): bool
    {
        return $this->defaultChecker->check($user, $data);
    }

    public function checkEntity(User $user, Entity $entity, ScopeData $data, string $action): bool
    {
        $checkerData = ScopeCheckerData
            ::createBuilder()
            ->setIsOwnChecker(
                function () use ($user, $entity): bool {
                    return (bool) $this->checkIsOwner($user, $entity);
                }
            )
            ->setInTeamChecker(
                function () use ($user, $entity): bool {
                    return (bool) $this->checkInTeam($user, $entity);
                }
            )
            ->build();

        return $this->scopeChecker->check($data, $action, $checkerData);
    }

    public function checkScope(User $user, ScopeData $data, ?string $action = null): bool
    {
        if (!$action) {
            return $this->defaultChecker->check($user, $data);
        }

        if ($action === Table::ACTION_CREATE) {
            return $this->defaultChecker->checkCreate($user, $data);
        }

        if ($action === Table::ACTION_READ) {
            return $this->defaultChecker->checkRead($user, $data);
        }

        if ($action === Table::ACTION_EDIT) {
            return $this->defaultChecker->checkEdit($user, $data);
        }

        if ($action === Table::ACTION_DELETE) {
            return $this->defaultChecker->checkDelete($user, $data);
        }

        if ($action === Table::ACTION_STREAM) {
            return $this->defaultChecker->checkStream($user, $data);
        }

        return false;
    }

    public function checkIsOwner(User $user, Entity $entity) /** @phpstan-ignore-line */
    {
        return $this->aclManager->checkOwnershipOwn($user, $entity);
    }

    public function checkInTeam(User $user, Entity $entity) /** @phpstan-ignore-line */
    {
        return $this->aclManager->checkOwnershipTeam($user, $entity);
    }

    public function inject($name, $object) /** @phpstan-ignore-line */
    {
        $this->injections[$name] = $object;
    }

    protected function init() /** @phpstan-ignore-line */
    {
    }

    protected function getInjection($name) /** @phpstan-ignore-line */
    {
        return $this->injections[$name] ?? $this->$name ?? null;
    }

    protected function addDependencyList(array $list) /** @phpstan-ignore-line */
    {
        foreach ($list as $item) {
            $this->addDependency($item);
        }
    }

    protected function addDependency($name) /** @phpstan-ignore-line */
    {
        $this->dependencyList[] = $name;
    }

    public function getDependencyList() /** @phpstan-ignore-line */
    {
        return array_merge($this->dependencyList, $this->dependencies);
    }

    protected function getConfig(): Config
    {
        return $this->config;
    }

    protected function getEntityManager(): EntityManager
    {
        return $this->entityManager;
    }

    protected function getAclManager(): AclManager
    {
        return $this->aclManager;
    }
}
