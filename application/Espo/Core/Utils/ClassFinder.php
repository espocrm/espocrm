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

namespace Espo\Core\Utils;

/**
 * Finds classes of a specific category. Category examples: Services, Controllers.
 * First it checks ib the custom folder, then modules, then the internal folder.
 * Available as 'classFinder' service.
 */
class ClassFinder
{
    protected $classParser;

    protected $pathsTemplate = [
        'corePath' => 'application/Espo/{category}',
        'modulePath' => 'application/Espo/Modules/{*}/{category}',
        'customPath' => 'custom/Espo/Custom/{category}',
    ];

    protected $dataHash = [];

    public function __construct(File\ClassParser $classParser)
    {
        $this->classParser = $classParser;
    }

    /**
     * Find class name by a category and name.
     */
    public function find(string $category, string $name, bool $subDirs = false) : ?string
    {
        $map = $this->getMap($category, $subDirs);
        $className = $map[$name] ?? null;

        return $className;
    }

    /**
     * Get [name => class-name] map.
     */
    public function getMap(string $category, bool $subDirs = false) : array
    {
        if (!array_key_exists($category, $this->dataHash)) {
            $this->load($category, $subDirs);
        }

        return $this->dataHash[$category] ?? [];
    }

    protected function load(string $category, bool $subDirs = false)
    {
        $path = $this->buildPaths($category);
        $cacheFile = $this->buildCacheKey($category);

        $this->dataHash[$category] = $this->classParser->getData($path, $cacheFile, null, $subDirs);
    }

    protected function buildPaths(string $category) : array
    {
        $paths = [];

        foreach ($this->pathsTemplate as $key => $value) {
            $path[$key] = str_replace('{category}', $category, $value);
        }

        return $path;
    }

    protected function buildCacheKey(string $category) : string
    {
        return 'classmap' . str_replace('/', '', $category);
    }
}
