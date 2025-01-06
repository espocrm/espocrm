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

namespace Espo\Core\Select;

use Espo\Core\Utils\Acl\UserAclManagerProvider;

use Espo\Core\Acl;
use Espo\Core\InjectableFactory;
use Espo\Core\Utils\ClassFinder;

use Espo\Entities\User;

/**
 * @deprecated Use SelectBuilder instead.
 *
 * Creates select managers for specific entity types. You can specify a user whose ACL will be applied to queries.
 * If user is not specified, then the current one will be used.
 */
class SelectManagerFactory
{
    /**
     * @var class-string
     */
    protected string $defaultClassName = SelectManager::class;

    private $user;

    private $acl;

    private $aclManagerProvider;

    private $injectableFactory;

    private $classFinder;

    public function __construct(
        User $user,
        Acl $acl,
        UserAclManagerProvider $aclManagerProvider,
        InjectableFactory $injectableFactory,
        ClassFinder $classFinder
    ) {
        $this->user = $user;
        $this->acl = $acl;
        $this->aclManagerProvider = $aclManagerProvider;
        $this->injectableFactory = $injectableFactory;
        $this->classFinder = $classFinder;
    }

    public function create(string $entityType, ?User $user = null): SelectManager
    {
        $className = $this->classFinder->find('SelectManagers', $entityType);

        if (!$className || !class_exists($className)) {
            $className = $this->defaultClassName;
        }

        /** @var class-string<SelectManager> $className */

        if ($user) {
            $acl = $this->aclManagerProvider->get($user)->createUserAcl($user);
        } else {
            $acl = $this->acl;
            $user = $this->user;
        }

        $selectManager = $this->injectableFactory->createWith($className, [
            'user' => $user,
            'acl' => $acl,
        ]);

        $selectManager->setEntityType($entityType);

        return $selectManager;
    }
}
