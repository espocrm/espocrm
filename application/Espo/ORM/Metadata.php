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

namespace Espo\ORM;

use Espo\ORM\Defs\DefsData;

use InvalidArgumentException;

/**
 * Metadata.
 */
class Metadata
{
    /** @var array<string, mixed> */
    private array $data;

    private Defs $defs;
    private DefsData $defsData;
    private EventDispatcher $eventDispatcher;

    public function __construct(
        private MetadataDataProvider $dataProvider,
        ?EventDispatcher $eventDispatcher = null
    ) {
        $this->data = $dataProvider->get();
        $this->defsData = new DefsData($this);
        $this->defs = new Defs($this->defsData);
        $this->eventDispatcher = $eventDispatcher ?? new EventDispatcher();
    }

    /**
     * Update data from the data provider.
     */
    public function updateData(): void
    {
        $this->data = $this->dataProvider->get();

        $this->defsData->clearCache();

        $this->eventDispatcher->dispatchMetadataUpdate();
    }

    /**
     * Get definitions.
     */
    public function getDefs(): Defs
    {
        return $this->defs;
    }

    /**
     * Get a parameter or parameters by key. Key can be a string or array path.
     *
     * @param string $entityType An entity type.
     * @param string[]|string|null $key A Key.
     * @param mixed $default A default value.
     * @return mixed
     */
    public function get(string $entityType, $key = null, $default = null)
    {
        if (!$this->has($entityType)) {
            return null;
        }

        $data = $this->data[$entityType];

        if ($key === null) {
            return $data;
        }

        return self::getValueByKey($data, $key, $default);
    }

    /**
     * Whether an entity type is available.
     */
    public function has(string $entityType): bool
    {
        return array_key_exists($entityType, $this->data);
    }

    /**
     * Get a list of entity types.
     *
     * @return string[]
     */
    public function getEntityTypeList(): array
    {
        return array_keys($this->data);
    }

    /**
     * @param array<string, mixed> $data
     * @param string[]|string|null $key
     * @param mixed $default A default value.
     * @return mixed
     */
    private static function getValueByKey(array $data, $key = null, $default = null)
    {
        if (!is_string($key) && !is_array($key) && !is_null($key)) { /** @phpstan-ignore-line */
            throw new InvalidArgumentException();
        }

        if (is_null($key) || empty($key)) {
            return $data;
        }

        $path = $key;

        if (is_string($key)) {
            $path = explode('.', $key);
        }

        /** @var string[] $path */

        $item = $data;

        foreach ($path as $k) {
            if (!array_key_exists($k, $item)) {
                return $default;
            }

            $item = $item[$k];
        }

        return $item;
    }
}
