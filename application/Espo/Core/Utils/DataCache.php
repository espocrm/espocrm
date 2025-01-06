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

use Espo\Core\Utils\File\Manager as FileManager;

use InvalidArgumentException;
use RuntimeException;
use stdClass;

class DataCache
{
    protected string $cacheDir = 'data/cache/application/';

    public function __construct(protected FileManager $fileManager)
    {}

    /**
     * Whether is cached.
     */
    public function has(string $key): bool
    {
        $cacheFile = $this->getCacheFile($key);

        return $this->fileManager->isFile($cacheFile);
    }

    /**
     * Get a stored value.
     *
     * @return array<int|string, mixed>|stdClass
     */
    public function get(string $key)
    {
        $cacheFile = $this->getCacheFile($key);

        return $this->fileManager->getPhpSafeContents($cacheFile);
    }

    /**
     * Store in cache.
     *
     * @param array<int|string, mixed>|stdClass $data
     */
    public function store(string $key, $data): void
    {
        /** @phpstan-var mixed $data */

        if (!$this->checkDataIsValid($data)) {
            throw new InvalidArgumentException("Bad cache data type.");
        }

        $cacheFile = $this->getCacheFile($key);

        $result = $this->fileManager->putPhpContents($cacheFile, $data, true, true);

        if ($result === false) {
            throw new RuntimeException("Could not store '$key'.");
        }
    }

    /**
     * Removes in cache.
     */
    public function clear(string $key): void
    {
        $cacheFile = $this->getCacheFile($key);

        $this->fileManager->removeFile($cacheFile);
    }

    /**
     * @param mixed $data
     * @return bool
     */
    private function checkDataIsValid($data)
    {
        $isInvalid =
            !is_array($data) &&
            !$data instanceof stdClass;

        return !$isInvalid;
    }

    private function getCacheFile(string $key): string
    {
        if (
            $key === '' ||
            preg_match('/[^a-zA-Z0-9_\/\-]/i', $key) ||
            $key[0] === '/' ||
            str_ends_with($key, '/')
        ) {
            throw new InvalidArgumentException("Bad cache key.");
        }

        return $this->cacheDir . $key . '.php';
    }
}
