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

namespace Espo\Core\Formula;

use Espo\Core\Formula\Exceptions\UnknownFunction;

use Espo\ORM\Entity;

use Espo\Core\InjectableFactory;

use StdClass;

class FunctionFactory
{
    private $processor;

    private $injectableFactory;

    private $attributeFetcher;

    private $classNameMap;

    public function __construct(
        Processor $processor,
        InjectableFactory $injectableFactory,
        AttributeFetcher $attributeFetcher,
        ?array $classNameMap = null
    ) {
        $this->processor = $processor;
        $this->injectableFactory = $injectableFactory;
        $this->attributeFetcher = $attributeFetcher;
        $this->classNameMap = $classNameMap;
    }

    public function create(string $name, ?Entity $entity = null, ?StdClass $variables = null): object
    {
        if ($this->classNameMap && array_key_exists($name, $this->classNameMap)) {
            $className = $this->classNameMap[$name];
        }
        else {
            $arr = explode('\\', $name);

            foreach ($arr as $i => $part) {
                if ($i < count($arr) - 1) {
                    $part = $part . 'Group';
                }
                $arr[$i] = ucfirst($part);
            }

            $typeName = implode('\\', $arr);

            $className = 'Espo\\Core\\Formula\\Functions\\' . $typeName . 'Type';
        }

        if (!class_exists($className)) {
            throw new UnknownFunction("Unknown function: " . $name);
        }

        $object = $this->injectableFactory->createWith($className, [
            'name' => $name,
            'processor' => $this->processor,
            'entity' => $entity,
            'variables' => $variables,
            'attributeFetcher' => $this->attributeFetcher,
        ]);

        if (
            property_exists($className, 'hasAttributeFetcher') ||
            method_exists($className, 'setAttributeFetcher')
        ) {
            $object->setAttributeFetcher($this->attributeFetcher);
        }

        return $object;
    }
}
