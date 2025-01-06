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

namespace Espo\Core\Utils;

use Espo\Core\Utils\File\ClassMap;

/**
 * Finds classes of a specific category. Category examples: Services, Controllers.
 * First it checks in the `custom` folder, then modules, then the internal folder.
 * Available as 'classFinder' service.
 */
class ClassFinder
{

    /** @var array<string, array<string, class-string>> */
    private $dataHashMap = [];

    public function __construct(private ClassMap $classMap)
    {}

    /**
     * Reset runtime cache.
     *
     * @internal
     * @since 8.4.0
     */
    public function resetRuntimeCache(): void
    {
        $this->dataHashMap = [];
    }

    /**
     * Find class name by a category and name.
     *
     * @return ?class-string
     */
    public function find(string $category, string $name, bool $subDirs = false): ?string
    {
        $map = $this->getMap($category, $subDirs);

        return $map[$name] ?? null;
    }

    /**
     * Get a name => class name map.
     *
     * @return array<string, class-string>
     */
    public function getMap(string $category, bool $subDirs = false): array
    {
        if (!array_key_exists($category, $this->dataHashMap)) {
            $this->load($category, $subDirs);
        }

        return $this->dataHashMap[$category] ?? [];
    }

    private function load(string $category, bool $subDirs = false): void
    {
        $cacheFile = $this->buildCacheKey($category);

        $this->dataHashMap[$category] = $this->classMap->getData($category, $cacheFile, null, $subDirs);
    }

    private function buildCacheKey(string $category): string
    {
        return 'classmap' . str_replace('/', '', $category);
    }
}
