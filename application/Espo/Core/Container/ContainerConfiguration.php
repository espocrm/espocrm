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

use Espo\Core\Utils\Log;
use Espo\Core\Utils\Metadata;

use ReflectionClass;
use Exception;

class ContainerConfiguration implements Configuration
{
    /**
     * Log must be loaded before anything.
     * @noinspection PhpPropertyOnlyWrittenInspection
     * @phpstan-ignore-next-line
     */
    private Log $log;

    /**
     * Must be protected.
     */
    protected Metadata $metadata;

    public function __construct(Log $log, Metadata $metadata)
    {
        $this->log = $log;
        $this->metadata = $metadata;
    }

    /**
     * @return ?class-string
     */
    public function getLoaderClassName(string $name): ?string
    {
        $className = null;

        try {
            $className = $this->metadata->get(['app', 'containerServices', $name, 'loaderClassName']);
        } catch (Exception) {}

        if ($className && class_exists($className)) {
            return $className;
        }

        $className = 'Espo\Custom\Core\Loaders\\' . ucfirst($name);

        if (!class_exists($className)) {
            $className = 'Espo\Core\Loaders\\' . ucfirst($name);
        }

        if (class_exists($className)) {
            $class = new ReflectionClass($className);

            if ($class->isInstantiable()) {
                return $className;
            }
        }

        return null;
    }

    /**
     * @return ?class-string
     */
    public function getServiceClassName(string $name): ?string
    {
        return $this->metadata->get(['app', 'containerServices', $name, 'className']) ?? null;
    }

    /**
     * @return ?string[]
     */
    public function getServiceDependencyList(string $name): ?array
    {
        return $this->metadata->get(['app', 'containerServices', $name, 'dependencyList']) ?? null;
    }

    public function isSettable(string $name): bool
    {
        return $this->metadata->get(['app', 'containerServices', $name, 'settable']) ?? false;
    }
}
