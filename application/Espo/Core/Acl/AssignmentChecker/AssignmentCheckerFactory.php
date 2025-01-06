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

namespace Espo\Core\Acl\AssignmentChecker;

use Espo\Core\Acl\AssignmentChecker;
use Espo\Core\Acl\DefaultAssignmentChecker;
use Espo\Core\Acl\Exceptions\NotImplemented;
use Espo\Core\InjectableFactory;
use Espo\Core\ORM\Entity as CoreEntity;
use Espo\Core\Utils\Metadata;
use Espo\ORM\Entity;

class AssignmentCheckerFactory
{
    /** @var class-string<AssignmentChecker<CoreEntity>> */
    private string $defaultClassName = DefaultAssignmentChecker::class;

    public function __construct(
        private Metadata $metadata,
        private InjectableFactory $injectableFactory
    ) {}

    /**
     * Create an access checker.
     *
     * @return AssignmentChecker<Entity>
     * @throws NotImplemented
     */
    public function create(string $scope): AssignmentChecker
    {
        $className = $this->getClassName($scope);

        return $this->injectableFactory->create($className);
    }

    /**
     * @return class-string<AssignmentChecker<Entity>>
     * @throws NotImplemented
     */
    private function getClassName(string $scope): string
    {
        /** @var ?class-string<AssignmentChecker<Entity>> $className */
        $className = $this->metadata->get(['aclDefs', $scope, 'assignmentCheckerClassName']);

        if ($className) {
            return $className;
        }

        if (!$this->metadata->get(['scopes', $scope])) {
            throw new NotImplemented();
        }

        /** @var class-string<AssignmentChecker<Entity>> */
        return $this->defaultClassName;
    }
}
