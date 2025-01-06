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

namespace Espo\Core;

use Espo\Core\Utils\ClassFinder;

use RuntimeException;

/**
 * @deprecated As of v6.1. For business logic, use plain classes. Inject them via constructor.
 * To access record services of specific entity types use `Espo\Core\Record\ServiceContainer`.
 */
class ServiceFactory
{
    private $classFinder;
    private $injectableFactory;

    public function __construct(ClassFinder $classFinder, InjectableFactory $injectableFactory)
    {
        $this->classFinder = $classFinder;
        $this->injectableFactory = $injectableFactory;
    }

    /**
     * @return ?class-string
     */
    private function getClassName(string $name): ?string
    {
        return $this->classFinder->find('Services', $name);
    }

    public function checkExists(string $name): bool
    {
        $className = $this->getClassName($name);

        if (!$className) {
            return false;
        }

        return true;
    }

    /**
     * @param array<string, mixed> $with
     */
    public function createWith(string $name, array $with): object
    {
        $className = $this->getClassName($name);

        if (!$className) {
            throw new RuntimeException("Service '{$name}' was not found.");
        }

        $obj = $this->injectableFactory->createWith($className, $with);

        // For backward compatibility.
        if (method_exists($obj, 'prepare')) {
            $obj->prepare();
        }

        return $obj;
    }

    public function create(string $name): object
    {
        return $this->createWith($name, []);
    }
}
