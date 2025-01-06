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

namespace Espo\Core\Container;

use Espo\Core\Container\Exceptions\NotSettableException;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionClass;

/**
 * DI container for services. Lazy initialization is used. Services are instantiated only once.
 * @see https://docs.espocrm.com/development/di/.
 */
interface Container extends ContainerInterface
{
    /**
     * Obtain a service object.
     *
     * @throws NotFoundExceptionInterface If not gettable.
     */
    public function get(string $id): object;

    /**
     * Check whether a service can be obtained.
     */
    public function has(string $id): bool;

    /**
     * Set a service object. Must be configured as settable.
     *
     * @throws NotSettableException Is not settable or already set.
     */
    public function set(string $id, object $object): void;

    /**
     * Get a class of a service.
     *
     * @return ReflectionClass<object>
     * @throws NotFoundExceptionInterface If not gettable.
     */
    public function getClass(string $id): ReflectionClass;

    /**
     * Get a service by a class name. A service should be bound to a class or interface.
     *
     * @template T of object
     * @param class-string<T> $className A class name or interface name.
     * @return T A service instance.
     * @throws NotFoundExceptionInterface If not gettable.
     */
    public function getByClass(string $className): object;
}
