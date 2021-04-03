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

namespace Espo\ORM\Defs;

use Espo\ORM\{
    Metadata,
};

use RuntimeException;

class DefsData
{
    private $cache = [];

    private $metadata;

    public function __construct(Metadata $metadata)
    {
        $this->metadata = $metadata;
    }

    public function clearCache(): void
    {
        $this->cache = [];
    }

    public function getEntityTypeList(): array
    {
        return $this->metadata->getEntityTypeList();
    }

    public function hasEntity(string $name): bool
    {
        $this->cacheEntity($name);

        return !is_null($this->cache[$name]);
    }

    public function getEntity(string $name): EntityDefs
    {
        $this->cacheEntity($name);

        if (!$this->hasEntity($name)) {
            throw new RuntimeException("Entity type '{$name}' does not exist.");
        }

        return $this->cache[$name];
    }

    private function cacheEntity(string $name): void
    {
        if (array_key_exists($name, $this->cache)) {
            return;
        }

        $this->cache[$name] = $this->loadEntity($name);
    }

    private function loadEntity(string $name): ?EntityDefs
    {
        $raw = $this->metadata->get($name) ?? null;

        if (!$raw) {
            return null;
        }

        return EntityDefs::fromRaw($raw, $name);
    }
}
