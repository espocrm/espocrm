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

namespace Espo\Core\Services;

use Espo\Core\Interfaces\Injectable;

/**
 * @deprecated As of v6.0. Create plain classes with dependencies passed via constrictor.
 */
abstract class Base implements Injectable
{
    protected $dependencyList = [ /** @phpstan-ignore-line */
        'config',
        'entityManager',
        'user',
        'serviceFactory',
    ];

    protected $injections = []; /** @phpstan-ignore-line */

    public function inject($name, $object) /** @phpstan-ignore-line */
    {
        $this->injections[$name] = $object;
    }

    public function __construct() /** @phpstan-ignore-line */
    {
        $this->init();
    }

    protected function init() /** @phpstan-ignore-line */
    {
    }

    public function prepare() /** @phpstan-ignore-line */
    {
    }

    protected function getInjection($name) /** @phpstan-ignore-line */
    {
        return $this->injections[$name] ?? $this->$name ?? null;
    }

    protected function addDependency($name) /** @phpstan-ignore-line */
    {
        $this->dependencyList[] = $name;
    }

    protected function addDependencyList(array $list) /** @phpstan-ignore-line */
    {
        foreach ($list as $item) {
            $this->addDependency($item);
        }
    }

    public function getDependencyList() /** @phpstan-ignore-line */
    {
        return $this->dependencyList;
    }

    protected function getEntityManager() /** @phpstan-ignore-line */
    {
        return $this->getInjection('entityManager');
    }

    protected function getConfig() /** @phpstan-ignore-line */
    {
        return $this->getInjection('config');
    }

    protected function getUser() /** @phpstan-ignore-line */
    {
        return $this->getInjection('user');
    }

    protected function getServiceFactory() /** @phpstan-ignore-line */
    {
        return $this->getInjection('serviceFactory');
    }
}
