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

namespace Espo\Core\Hooks;

use Espo\Core\Interfaces\Injectable;

/**
 * @deprecated As of v6.0. Not to be extended. Create plain classes with needed dependencies.
 */
abstract class Base implements Injectable
{
    protected $injections = []; /** @phpstan-ignore-line */

    public static $order = 9; /** @phpstan-ignore-line */

    /** @phpstan-ignore-next-line */
    protected $dependencyList = [
        'container',
        'entityManager',
        'config',
        'metadata',
        'aclManager',
        'user',
        'serviceFactory',
    ];

    protected $dependencies = []; /** @phpstan-ignore-line */

    public function __construct()
    {
        $this->init();
    }

    protected function init() /** @phpstan-ignore-line */
    {
    }

    public function getDependencyList() /** @phpstan-ignore-line */
    {
        return array_merge($this->dependencyList, $this->dependencies);
    }

    protected function addDependencyList(array $list) /** @phpstan-ignore-line */
    {
        foreach ($list as $item) {
            $this->addDependency($item);
        }
    }

    protected function addDependency($name) /** @phpstan-ignore-line */
    {
        $this->dependencyList[] = $name;
    }

    protected function getInjection($name) /** @phpstan-ignore-line */
    {
        return $this->injections[$name] ?? $this->$name ?? null;
    }

    public function inject($name, $object) /** @phpstan-ignore-line */
    {
        $this->injections[$name] = $object;
    }

    protected function getContainer() /** @phpstan-ignore-line */
    {
        return $this->getInjection('container');
    }

    protected function getEntityManager() /** @phpstan-ignore-line */
    {
        return $this->getInjection('entityManager');
    }

    protected function getUser() /** @phpstan-ignore-line */
    {
        return $this->getInjection('user');
    }

    protected function getAcl() /** @phpstan-ignore-line */
    {
        return $this->getContainer()->get('acl');
    }

    protected function getAclManager() /** @phpstan-ignore-line */
    {
        return $this->getInjection('aclManager');
    }

    protected function getConfig() /** @phpstan-ignore-line */
    {
        return $this->getInjection('config');
    }

    protected function getMetadata() /** @phpstan-ignore-line */
    {
        return $this->getInjection('metadata');
    }

    protected function getServiceFactory() /** @phpstan-ignore-line */
    {
        return $this->getInjection('serviceFactory');
    }
}
