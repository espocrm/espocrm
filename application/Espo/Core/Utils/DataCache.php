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

use Espo\Core\{
    Exceptions\Error,
    Utils\File\Manager as FileManager,
};

use InvalidArgumentException;
use stdClass;

class DataCache
{
    protected $fileManager;

    protected $cacheDir = 'data/cache/application/';

    public function __construct(FileManager $fileManager)
    {
        $this->fileManager = $fileManager;
    }

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
     * @throws Error if is not cached.
     *
     * @return array|stdClass|null
     */
    public function get(string $key)
    {
        $cacheFile = $this->getCacheFile($key);

        $data = $this->fileManager->getPhpSafeContents($cacheFile);

        if ($data === false) {
            throw new Error("Could not get '{$key}'.");
        }

        if (! $this->checkDataIsValid($data)) {
            throw new Error("Bad data fetched from cache by key '{$key}'.");
        }

        return $data;
    }

    /**
     * Store in cache.
     *
     * @param array|stdClass|null $data
     */
    public function store(string $key, $data): void
    {
        if (! $this->checkDataIsValid($data)) {
            throw new InvalidArgumentException("Bad cache data type.");
        }

        $cacheFile = $this->getCacheFile($key);

        $result = $this->fileManager->putPhpContents($cacheFile, $data, true, true);

        if ($result === false) {
            throw new Error("Could not store '{$key}'.");
        }
    }

    protected function checkDataIsValid($data)
    {
        $isInvalid =
            !is_array($data) &&
            !$data instanceof stdClass;

        return ! $isInvalid;
    }

    protected function getCacheFile(string $key): string
    {
        if (
            $key === '' ||
            preg_match('/[^a-zA-Z0-9_\/\-]/i', $key) ||
            $key[0] === '/' ||
            substr($key, -1) === '/'
        ) {
            throw new InvalidArgumentException("Bad cache key.");
        }

        return $this->cacheDir . $key . '.php';
    }
}
