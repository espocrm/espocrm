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

namespace Espo\Core\Container;

use ReflectionClass;
use RuntimeException;

/**
 * DI container for services. Lazy initialization is used. Services are instantiated only once.
 *
 * See https://docs.espocrm.com/development/di/.
 */
interface Container
{
    /**
     * Obtain a service object.
     *
     * @throws RuntimeException If not gettable.
     */
    public function get(string $name): object;

    /**
     * Check whether a service can be obtained.
     */
    public function has(string $name): bool;

    /**
     * Set a service object. Must be configured as settable.
     *
     * @throws RuntimeException Is not settable or already set.
     */
    public function set(string $name, object $object): void;

    /**
     * Get a class of a service.
     *
     * @throws RuntimeException If not gettable.
     */
    public function getClass(string $name): ReflectionClass;
}
