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

use stdClass;

class Integration extends Entity
{
    public const ENTITY_TYPE = 'Integration';

    public function get(string $attribute): mixed
    {
        if ($attribute == 'id') {
            return $this->id;
        }

        if ($this->hasAttribute($attribute)) {
            if ($this->hasInContainer($attribute)) {
                return $this->getFromContainer($attribute);
            }
        } else {
            if ($this->get('data')) {
                $data = $this->get('data');
            } else {
                $data = new stdClass();
            }

            if (isset($data->$attribute)) {
                return $data->$attribute;
            }
        }

        return null;
    }

    public function clear(string $name): void
    {
        parent::clear($name);

        $data = $this->get('data');

        if (empty($data)) {
            $data = new stdClass();
        }

        unset($data->$name);

        $this->set('data', $data);
    }

    public function set($p1, $p2 = null): static
    {
        if (is_object($p1)) {
            $p1 = get_object_vars($p1);
        }

        if (is_array($p1)) {
            if ($p2 === null) {
                $p2 = false;
            }

            $this->populateFromArray($p1, $p2);

            return $this;
        }

        $name = $p1;
        $value = $p2;

        if ($name == 'id') {
            $this->id = $value;

            return $this;
        }

        if ($this->hasAttribute($name)) {
            $this->setInContainer($name, $value);
        } else {
            $data = $this->get('data') ?? (object) [];

            $data->$name = $value;

            $this->set('data', $data);
        }

        return $this;
    }

    public function isAttributeChanged(string $name): bool
    {
        if ($name === 'data') {
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
        $array = [];

        if (isset($this->id)) {
            $array['id'] = $this->id;
        }

        foreach ($this->getAttributeList() as $attribute) {
            if ($attribute === 'id') {
                continue;
            }

            if ($attribute === 'data') {
                continue;
            }

            if ($this->has($attribute)) {
                $array[$attribute] = $this->get($attribute);
            }
        }

        $data = $this->get('data') ?? (object) [];

        $array = array_merge(
            $array,
            get_object_vars($data)
        );

        return (object) $array;
    }

    public function isEnabled(): bool
    {
        return (bool) $this->get('enabled');
    }
}
