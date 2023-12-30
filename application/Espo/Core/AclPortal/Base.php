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

namespace Espo\Core\AclPortal;

use Espo\Core\Interfaces\Injectable;
use Espo\ORM\Entity;
use Espo\Entities\User;
use Espo\Core\AclManager;
use Espo\Core\Acl\AccessChecker;
use Espo\Core\Acl\ScopeData;
use Espo\Core\ORM\EntityManager;
use Espo\Core\Portal\Acl\AccessChecker\ScopeChecker;
use Espo\Core\Portal\Acl\AccessChecker\ScopeCheckerData;
use Espo\Core\Portal\Acl\DefaultAccessChecker;
use Espo\Core\Portal\Acl\Table;
use Espo\Core\Portal\AclManager as PortalAclManager;
use Espo\Core\Utils\Config;

/**
 * @deprecated Use AccessChecker interfaces instead.
 */
class Base implements AccessChecker, Injectable
{
    protected $dependencyList = []; /** @phpstan-ignore-line */

    protected $dependencies = []; /** @phpstan-ignore-line */

    protected $injections = []; /** @phpstan-ignore-line */

    protected $entityManager; /** @phpstan-ignore-line */

    protected $aclManager; /** @phpstan-ignore-line */

    protected $config; /** @phpstan-ignore-line */

    protected $scopeChecker; /** @phpstan-ignore-line */

    protected $defaultChecker; /** @phpstan-ignore-line */

    public function __construct(
        EntityManager $entityManager,
        PortalAclManager $aclManager,
        Config $config,
        ScopeChecker $scopeChecker,
        DefaultAccessChecker $defaultChecker
    ) {
        $this->entityManager = $entityManager;
        $this->aclManager = $aclManager;
        $this->config = $config;
        $this->scopeChecker = $scopeChecker;
        $this->defaultChecker = $defaultChecker;

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
            ->setInAccountChecker(
                function () use ($user, $entity): bool {
                    return (bool) $this->checkInAccount($user, $entity);
                }
            )
            ->setInContactChecker(
                function () use ($user, $entity): bool {
                    return (bool) $this->checkIsOwnContact($user, $entity);
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

    public function checkInAccount(User $user, Entity $entity) /** @phpstan-ignore-line */
    {
        return $this->aclManager->checkOwnershipAccount($user, $entity);
    }

    public function checkIsOwnContact(User $user, Entity $entity) /** @phpstan-ignore-line */
    {
        return $this->aclManager->checkOwnershipContact($user, $entity);
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
}
