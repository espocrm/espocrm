<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2020 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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


use Espo\ORM\Entity;
use Espo\Core\Exceptions\Error;

use Espo\Core\Formula\FunctionFactory;

use StdClass;

abstract class FunctionBase
{
    protected $itemFactory;

    private $entity;

    private $variables;

    protected function getVariables() : StdClass
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

    public function __construct(FunctionFactory $itemFactory, ?Entity $entity = null, ?StdClass $variables = null)
    {
        $this->itemFactory = $itemFactory;
        $this->entity = $entity;
        $this->variables = $variables;
    }

    protected function getFactory() : FunctionFactory
    {
        return $this->itemFactory;
    }

    protected function evaluate(StdClass $item)
    {
        $function = $this->getFactory()->create($item, $this->entity, $this->variables);
        return $function->process($item);
    }

    public abstract function process(StdClass $item);

    protected function fetchArguments(StdClass $item) : array
    {
        $args = $item->value ?? [];

        $eArgs = [];
        foreach ($args as $item) {
            $eArgs[] = $this->evaluate($item);
        }

        return $eArgs;
    }

    protected function fetchRawArguments(StdClass $item) : array
    {
        return $item->value ?? [];
    }
}
