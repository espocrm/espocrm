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

namespace Espo\Entities;

use StdClass;

class Integration extends \Espo\Core\ORM\Entity
{
    public function get(string $name, $params = [])
    {
        if ($name == 'id') {
            return $this->id;
        }

        if ($this->hasAttribute($name)) {
            if (array_key_exists($name, $this->valuesContainer)) {
                return $this->valuesContainer[$name];
            }
        } else {
            if ($this->get('data')) {
                $data = $this->get('data');
            } else {
                $data = new StdClass();
            }

            if (isset($data->$name)) {
                return $data->$name;
            }
        }

        return null;
    }

    public function clear(string $name) : void
    {
        parent::clear($name);

        $data = $this->get('data');

        if (empty($data)) {
            $data = new StdClass();
        }

        unset($data->$name);

        $this->set('data', $data);
    }

    public function set($p1, $p2 = null) : void
    {
        if (is_object($p1)) {
            $p1 = get_object_vars($p1);
        }

        if (is_array($p1)) {
            if ($p2 === null) {
                $p2 = false;
            }

            $this->populateFromArray($p1, $p2);

            return;
        }

        $name = $p1;
        $value = $p2;

        if ($name == 'id') {
            $this->id = $value;

            return;
        }

        if ($this->hasAttribute($name)) {
            $this->valuesContainer[$name] = $value;
        }
        else {
            $data = $this->get('data');

            if (empty($data)) {
                $data = new StdClass();
            }

            $data->$name = $value;
            $this->set('data', $data);
        }
    }

    public function isAttributeChanged(string $name) : bool
    {
        if ($name === 'data') {
            return true;
        }

        return parent::isAttributeChanged($name);
    }

    /**
     * @deprecated
     * @todo Make protected.
     */
    public function populateFromArray(array $array, bool $onlyAccessible = true, bool $reset = false) : void
    {
        if ($reset) {
            $this->reset();
        }

        foreach ($array as $attribute => $value) {
            if (!is_string($attribute)) {
                continue;
            }

            if ($this->hasAttribute($attribute)) {
                $attributes = $this->getAttributes();

                $defs = $attributes[$attribute];

                if (!is_null($value)) {
                    switch ($defs['type']) {
                        case self::VARCHAR:

                            break;

                        case self::BOOL:
                            $value = ($value === 'true' || $value === '1' || $value === true);

                            break;

                        case self::INT:
                            $value = intval($value);

                            break;

                        case self::FLOAT:
                            $value = floatval($value);

                            break;

                        case self::JSON_ARRAY:
                            $value = is_string($value) ? json_decode($value) : $value;

                            if (!is_array($value)) {
                                $value = null;
                            }
                            break;

                        case self::JSON_OBJECT:
                            $value = is_string($value) ? json_decode($value) : $value;

                            if (!($value instanceof StdClass) && !is_array($value)) {
                                $value = null;
                            }

                            break;

                        default:

                            break;
                    }
                }
            }

            $this->set($attribute, $value);
        }
    }

    public function toArray()
    {
        $arr = [];

        if (isset($this->id)) {
            $arr['id'] = $this->id;
        }

        foreach ($this->getAttributeList() as $attribute) {
            if ($attribute === 'id') {
                continue;
            }

            if ($attribute === 'data') {
                continue;
            }

            if ($this->has($attribute)) {
                $arr[$attribute] = $this->get($attribute);
            }
        }

        $data = $this->get('data');

        if (empty($data)) {
            $data = new StdClass();
        }

        $dataArr = get_object_vars($data);

        return array_merge($arr, $dataArr);
    }

    public function getValueMap() : StdClass
    {
        $arr = $this->toArray();

        return (object) $arr;
    }
}
