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

namespace Espo\Core\MassAction;

use Espo\Entities\User;

use Espo\Core\Exceptions\NotFound;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Utils\Metadata;
use Espo\Core\InjectableFactory;
use Espo\Core\AclManager;
use Espo\Core\Acl;
use Espo\Core\Binding\BindingContainerBuilder;

class MassActionFactory
{
    public function __construct(
        private Metadata $metadata,
        private InjectableFactory $injectableFactory,
        private AclManager $aclManager
    ) {}

    /**
     * @throws Forbidden
     * @throws NotFound
     */
    public function create(string $action, string $entityType): MassAction
    {
        $className = $this->getClassName($action, $entityType);

        if (!$className) {
            throw new NotFound("Mass action '{$action}' not found.");
        }

        if ($this->isDisabled($action, $entityType)) {
            throw new Forbidden("Mass action '{$action}' is disabled for '{$entityType}'.");
        }

        return $this->injectableFactory->create($className);
    }

    public function createForUser(string $action, string $entityType, User $user): MassAction
    {
        $className = $this->getClassName($action, $entityType);

        if (!$className) {
            throw new NotFound("Mass action '{$action}' not found.");
        }

        $bindingContainer = BindingContainerBuilder::create()
            ->bindInstance(User::class, $user)
            ->bindInstance(Acl::class, $this->aclManager->createUserAcl($user))
            ->build();

        return $this->injectableFactory->createWithBinding($className, $bindingContainer);
    }

    /**
     * @param array<string, mixed> $with
     * @throws NotFound
     * @todo Remove.
     * @deprecated As of v7.1. Use create.
     */
    public function createWith(string $action, string $entityType, array $with): MassAction
    {
        $className = $this->getClassName($action, $entityType);

        if (!$className) {
            throw new NotFound("Mass action '{$action}' not found.");
        }

        return $this->injectableFactory->createWith($className, $with);
    }

    /**
     * @return ?class-string<MassAction>
     */
    private function getClassName(string $action, string $entityType): ?string
    {
        /** @var ?class-string<MassAction> $className */
        $className = $this->getEntityTypeClassName($action, $entityType);

        if ($className) {
            return $className;
        }

        /** @var ?class-string<MassAction> */
        return $this->metadata->get(
            ['app', 'massActions', $action, 'implementationClassName']
        );
    }

    /**
     * @return ?class-string<MassAction>
     */
    private function getEntityTypeClassName(string $action, string $entityType): ?string
    {
        /** @var ?class-string<MassAction> */
        return $this->metadata->get(
            ['recordDefs', $entityType, 'massActions', $action, 'implementationClassName']
        );
    }

    private function isDisabled(string $action, string $entityType): bool
    {
        $actionsDisabled = $this->metadata
            ->get(['recordDefs', $entityType, 'actionsDisabled']) ?? false;

        if ($actionsDisabled) {
            return true;
        }

        $massActionsDisabled = $this->metadata
            ->get(['recordDefs', $entityType, 'massActionsDisabled']) ?? false;

        if ($massActionsDisabled) {
            return true;
        }

        if ($this->needsToBeAllowed($entityType)) {
            if (!$this->isAllowed($action, $entityType)) {
                return true;
            }
        }

        return $this->metadata
            ->get(['recordDefs', $entityType, 'massActions', $action, 'disabled']) ?? false;
    }

    private function needsToBeAllowed(string $entityType): bool
    {
        $isObject = $this->metadata->get(['scopes', $entityType, 'object']) ?? false;

        if (!$isObject) {
            return true;
        }

        return $this->metadata
            ->get(['recordDefs', $entityType, 'notAllowedActionsDisabled']) ?? false;
    }

    private function isAllowed(string $action, string $entityType): bool
    {
        return $this->metadata
            ->get(['recordDefs', $entityType, 'massActions', $action, 'allowed']) ?? false;
    }
}
