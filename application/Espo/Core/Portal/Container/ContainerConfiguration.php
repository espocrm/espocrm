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

namespace Espo\Core\Portal\Container;

use Espo\Core\Container\ContainerConfiguration as BaseContainerConfiguration;

class ContainerConfiguration extends BaseContainerConfiguration
{
    /**
     * @return ?class-string
     */
    public function getLoaderClassName(string $name): ?string
    {
        $className = null;

        try {
            $className = $this->metadata->get(['app', 'portalContainerServices', $name, 'loaderClassName']);
        } catch (\Exception) {}

        if ($className && class_exists($className)) {
            return $className;
        }

        $className = 'Espo\Custom\Core\Portal\Loaders\\' . ucfirst($name);
        if (!class_exists($className)) {
            $className = 'Espo\Core\Portal\Loaders\\' . ucfirst($name);
        }

        if (class_exists($className)) {
            return $className;
        }

        return parent::getLoaderClassName($name);
    }

    /**
     * @return ?class-string
     */
    public function getServiceClassName(string $name): ?string
    {
        return $this->metadata->get(['app', 'portalContainerServices', $name, 'className']) ??
            parent::getServiceClassName($name);
    }

    /**
     * @return ?string[]
     */
    public function getServiceDependencyList(string $name): ?array
    {
        return
            $this->metadata->get(['app', 'portalContainerServices', $name, 'dependencyList']) ??
            parent::getServiceDependencyList($name);
    }

    public function isSettable(string $name): bool
    {
        return
            $this->metadata->get(['app', 'portalContainerServices', $name, 'settable']) ??
            parent::isSettable($name);
    }
}
