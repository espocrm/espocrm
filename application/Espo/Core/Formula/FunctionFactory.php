<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

namespace Espo\Core\Formula;

use \Espo\Core\Exceptions\Error;
use \Espo\ORM\Entity;

class FunctionFactory
{
    private $container;

    private $classNameMap;

    public function __construct($container, AttributeFetcher $attributeFetcher, $classNameMap = null)
    {
        $this->container = $container;
        $this->attributeFetcher = $attributeFetcher;
        $this->classNameMap = $classNameMap;
    }

    public function create(\StdClass $item, $entity, \StdClass $variables)
    {
        if (!isset($item->type)) {
            throw new Error('Missing type');
        }

        if (!is_string($item->type)) {
            throw new Error('Bad type');
        }

        $name = $item->type;

        if ($this->classNameMap && array_key_exists($name, $this->classNameMap)) {
            $className = $this->classNameMap[$name];
        } else {
            $arr = explode('\\', $name);
            foreach ($arr as $i => $part) {
                if ($i < count($arr) - 1) {
                    $part = $part . 'Group';
                }
                $arr[$i] = ucfirst($part);
            }
            $name = implode('\\', $arr);
            $className = '\\Espo\\Core\\Formula\\Functions\\' . $name . 'Type';
        }

        if (!class_exists($className)) {
            throw new Error('Class ' . $className . ' was not found.');
        }

        $object = new $className($this, $entity, $variables);

        $dependencyList = $object->getDependencyList();
        foreach ($dependencyList as $name) {
            if (!$this->container) {
                throw new Error('Container required but not passed.');
            }
            $object->inject($name, $this->container->get($name));
        }

        if (property_exists($className, 'hasAttributeFetcher')) {
            $object->setAttributeFetcher($this->attributeFetcher);
        }
        return $object;
    }
}
