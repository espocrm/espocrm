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

namespace Espo\Entities;

use Espo\Core\ORM\Entity;
use Espo\ORM\Name\Attribute;
use stdClass;

class Integration extends Entity
{
    public const ENTITY_TYPE = 'Integration';

    private const ATTR_DATA = 'data';
    private const ATTR_ENABLED = 'enabled';

    public function has(string $attribute): bool
    {
        if ($attribute === Attribute::ID) {
            return (bool) $this->id;
        }

        if ($this->hasAttribute($attribute)) {
            return $this->hasInContainer($attribute);
        }

        return property_exists($this->getData(), $attribute);
    }

    public function get(string $attribute): mixed
    {
        if ($attribute === Attribute::ID) {
            return $this->id;
        }

        if ($this->hasAttribute($attribute)) {
            if ($this->hasInContainer($attribute)) {
                return $this->getFromContainer($attribute);
            }

            return null;
        }

        return $this->getData()->$attribute ?? null;
    }

    public function clear(string $attribute): void
    {
        parent::clear($attribute);

        $data = $this->getData();

        unset($data->$attribute);

        $this->set(self::ATTR_DATA, $data);
    }

    public function set($attribute, $value = null): static
    {
        if (is_object($attribute)) {
            $attribute = get_object_vars($attribute);
        }

        if (is_array($attribute)) {
            $this->populateFromArray($attribute, false);

            return $this;
        }

        $name = $attribute;

        if ($name === Attribute::ID) {
            $this->id = $value;

            return $this;
        }

        if ($this->hasAttribute($name)) {
            $this->setInContainer($name, $value);

            return $this;
        }

        $data = $this->getData();

        $data->$name = $value;

        $this->set(self::ATTR_DATA, $data);

        return $this;
    }

    public function isAttributeChanged(string $name): bool
    {
        if ($name === self::ATTR_DATA) {
            return true;
        }

        return parent::isAttributeChanged($name);
    }

    protected function populateFromArray(array $data, bool $onlyAccessible = true, bool $reset = false): void
    {
        if ($reset) {
            $this->reset();
        }

        foreach ($data as $attribute => $value) {
            if (!is_string($attribute)) {
                continue;
            }

            if ($this->hasAttribute($attribute)) {
                $value = $this->prepareAttributeValue($attribute, $value);
            }

            $this->set($attribute, $value);
        }
    }

    public function getValueMap(): stdClass
    {
        $map = [];

        if (isset($this->id)) {
            $map[Attribute::ID] = $this->id;
        }

        foreach ($this->getAttributeList() as $attribute) {
            if ($attribute === Attribute::ID) {
                continue;
            }

            if ($attribute === self::ATTR_DATA) {
                continue;
            }

            if ($this->has($attribute)) {
                $map[$attribute] = $this->get($attribute);
            }
        }

        $data = $this->getData();

        $map = array_merge($map, get_object_vars($data));

        return (object) $map;
    }

    public function isEnabled(): bool
    {
        return (bool) $this->get(self::ATTR_ENABLED);
    }

    public function getData(): stdClass
    {
        /** @var stdClass */
        return $this->get(self::ATTR_DATA) ?? (object) [];
    }
}
