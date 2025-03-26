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

namespace Espo\Core\Traits;

/**
 * @deprecated
 * @todo Remove in v10.0.
 * @phpstan-ignore-next-line
 */
trait Injectable
{
    protected $injections = []; /** @phpstan-ignore-line */

    /**
     * @param string $name
     * @param object $object
     * @return void
     */
    public function inject($name, $object)
    {
        $this->injections[$name] = $object;
    }

    /**
     * @return string[]
     */
    public function getDependencyList(): array
    {
        return $this->dependencyList;
    }

    /**
     * @return ?object
     */
    protected function getInjection(string $name)
    {
        return $this->injections[$name] ?? $this->$name ?? null;
    }

    /**
     * @return void
     */
    protected function addDependency(string $name)
    {
        $this->dependencyList[] = $name;
    }

    /**
     * @param string[] $list
     * @return void
     */
    protected function addDependencyList(array $list)
    {
        foreach ($list as $item) {
            $this->addDependency($item);
        }
    }
}
