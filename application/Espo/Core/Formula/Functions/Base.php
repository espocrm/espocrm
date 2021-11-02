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

namespace Espo\Core\Formula\Functions;

use Espo\Core\Exceptions\Error;

use Espo\Core\Interfaces\Injectable;

use Espo\ORM\Entity;

use Espo\Core\Formula\Processor;
use Espo\Core\Formula\Argument;

use stdClass;

/**
 * @deprecated Use BaseFormula instead.
 */
abstract class Base implements Injectable
{
    protected $name;

    protected $processor;

    private $entity;

    private $variables;

    protected $dependencyList = [];

    protected $injections = [];

    public function inject($name, $object)
    {
        $this->injections[$name] = $object;
    }

    protected function getInjection($name)
    {
        return $this->injections[$name] ?? $this->$name ?? null;
    }

    protected function addDependency($name)
    {
        $this->dependencyList[] = $name;
    }

    protected function addDependencyList(array $list)
    {
        foreach ($list as $item) {
            $this->addDependency($item);
        }
    }

    public function getDependencyList()
    {
        return $this->dependencyList;
    }

    public function __construct(string $name, Processor $processor, ?Entity $entity = null, ?stdClass $variables = null)
    {
        $this->name = $name;
        $this->processor = $processor;
        $this->entity = $entity;
        $this->variables = $variables;

        $this->init();
    }

    protected function init()
    {
    }

    protected function getVariables(): stdClass
    {
        return $this->variables;
    }

    protected function getEntity()
    {
        if (!$this->entity) {
            throw new Error('Formula: Entity required but not passed.');
        }
        return $this->entity;
    }

    public abstract function process(stdClass $item);

    protected function evaluate($item)
    {
        $item = new Argument($item);
        return $this->processor->process($item);
    }

    protected function fetchArguments(stdClass $item): array
    {
        $args = $item->value ?? [];

        $eArgs = [];
        foreach ($args as $item) {
            $eArgs[] = $this->evaluate($item);
        }

        return $eArgs;
    }

    protected function fetchRawArguments(stdClass $item): array
    {
        return $item->value ?? [];
    }
}
