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

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;
use RecursiveRegexIterator;

/**
 * Scans all PHP files and loads them. Used for a preloading.
 *
 * @see https://www.php.net/manual/en/opcache.preloading.php
 */
class Preload
{
    /**
     * @var string[]
     */
    protected $dirList = [
        'application',
        'vendor/slim',
        'vendor/nikic/fast-route',
    ];

    private int $counter = 0;

    /**
     * @var string[]
     */
    protected $ignoreList = [
        'application/Espo/Core/Mail/Parsers/PhpMimeMailParser/',
        'vendor/nikic/fast-route/test/',
        'vendor/slim/psr7/tests/',
    ];

    public function process(): void
    {
        foreach ($this->dirList as $dir) {
            $this->processForDir($dir);
        }
    }

    public function getCount(): int
    {
        return $this->counter;
    }

    private function processForDir(string $dir): void
    {
        $directory = new RecursiveDirectoryIterator($dir);
        $fullTree = new RecursiveIteratorIterator($directory);
        $phpFiles = new RegexIterator($fullTree, '/.+((?<!Test)+\.php$)/i', RecursiveRegexIterator::GET_MATCH);

        foreach ($phpFiles as $key => $file) {
            $this->processFile($file[0]);
        }
    }

    private function processFile(string $file): void
    {
        if ($this->isFileToBeIgnored($file)) {
            return;
        }

        require_once($file);

        $this->counter++;
    }

    private function isFileToBeIgnored(string $file): bool
    {
        $file = str_replace('\\', '/', $file);

        foreach ($this->ignoreList as $item) {
            if (str_starts_with($file, $item)) {
                return true;
            }
        }

        if (str_contains($file, 'vendor/composer/ClassLoader.php')) {
            return true;
        }

        return false;
    }
}
