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
    protected $dirList = [
        'application',
        'vendor/slim',
        'vendor/nikic/fast-route',
    ];

    private $counter = 0;

    protected $ignoreList = [
        'application/Espo/Core/Mail/Parsers/PhpMimeMailParser/',
        'vendor/nikic/fast-route/test/',
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
            if (strpos($file, $item) === 0) {
                return true;
            }
        }

        if (strpos($file, 'vendor/composer/ClassLoader.php') !== false) {
            return true;
        }

        return false;
    }
}
