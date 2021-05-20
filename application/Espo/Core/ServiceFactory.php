<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2021 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

namespace Espo\Core;

use Espo\Core\{
    Exceptions\Error,
    Utils\ClassFinder,
    InjectableFactory,
};

/**
 * Creates services. Services are intended for a business logic. Do not confuse with container services.
 *
 * @deprecated Use DI to pass a specific service class to a constructor.
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

    private function getClassName(string $name)
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

    public function create(string $name): object
    {
        $className = $this->getClassName($name);
        if (!$className) {
            throw new Error("Service '{$name}' was not found.");
        }

        $obj = $this->injectableFactory->create($className);

        // deprecated
        if (method_exists($obj, 'prepare')) {
            $obj->prepare();
        }

        return $obj;
    }
}
