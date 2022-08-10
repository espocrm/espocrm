<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2022 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

namespace Espo\Core\Notification;

use Espo\Core\{
    InjectableFactory,
    Utils\ClassFinder,
    Utils\Metadata,
    Notification\DefaultAssignmentNotificator,
    Notification\AssignmentNotificator,
};

class AssignmentNotificatorFactory
{
    /**
     * @var class-string<AssignmentNotificator>
     */
    protected string $defaultClassName = DefaultAssignmentNotificator::class;

    private InjectableFactory $injectableFactory;

    private ClassFinder $classFinder;

    private Metadata $metadata;

    public function __construct(InjectableFactory $injectableFactory, ClassFinder $classFinder, Metadata $metadata)
    {
        $this->injectableFactory = $injectableFactory;
        $this->classFinder = $classFinder;
        $this->metadata = $metadata;
    }

    /**
     * @todo Change return type to AssignmentNotificator.
     *
     * @return AssignmentNotificator
     */
    public function create(string $entityType): object // AssignmentNotificator
    {
        $className = $this->getClassName($entityType);

        return $this->injectableFactory->create($className);
    }

    /**
     * @return class-string<AssignmentNotificator>
     */
    private function getClassName(string $entityType): string
    {
        /** @var ?class-string<AssignmentNotificator> $className1 */
        $className1 = $this->metadata->get(['notificationDefs', $entityType, 'assignmentNotificatorClassName']);

        if ($className1) {
            return $className1;
        }

        /* For backward compatibility. */
        /** @var ?class-string<AssignmentNotificator> $className2 */
        $className2 = $this->classFinder->find('Notificators', $entityType);

        if ($className2) {
            return $className2;
        }

        return $this->defaultClassName;
    }
}
