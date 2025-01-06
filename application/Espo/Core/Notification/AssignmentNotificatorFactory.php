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

namespace Espo\Core\Notification;

use Espo\Core\InjectableFactory;
use Espo\Core\Utils\ClassFinder;
use Espo\Core\Utils\Metadata;
use Espo\ORM\Entity;
use Espo\ORM\Repository\Util as RepositoryUtil;

class AssignmentNotificatorFactory
{
    /** @var class-string<AssignmentNotificator<Entity>> */
    protected string $defaultClassName = DefaultAssignmentNotificator::class;

    public function __construct(
        private InjectableFactory $injectableFactory,
        private ClassFinder $classFinder,
        private Metadata $metadata
    ) {}

    /**
     * @template T of Entity
     * @param class-string<T> $className An entity class name.
     * @return AssignmentNotificator<T>
     */
    public function createByClass(string $className): AssignmentNotificator
    {
        $entityType = RepositoryUtil::getEntityTypeByClass($className);

        /** @var AssignmentNotificator<T> */
        return $this->create($entityType);
    }

    /**
     * @todo Change return type to AssignmentNotificator.
     *
     * @return AssignmentNotificator<Entity>
     */
    public function create(string $entityType): object // AssignmentNotificator
    {
        $className = $this->getClassName($entityType);

        return $this->injectableFactory->create($className);
    }

    /**
     * @return class-string<AssignmentNotificator<Entity>>
     */
    private function getClassName(string $entityType): string
    {
        /** @var ?class-string<AssignmentNotificator<Entity>> $className1 */
        $className1 = $this->metadata->get(['notificationDefs', $entityType, 'assignmentNotificatorClassName']);

        if ($className1) {
            return $className1;
        }

        /* For backward compatibility. */
        /** @var ?class-string<AssignmentNotificator<Entity>> $className2 */
        $className2 = $this->classFinder->find('Notificators', $entityType);

        if ($className2) {
            return $className2;
        }

        return $this->defaultClassName;
    }
}
