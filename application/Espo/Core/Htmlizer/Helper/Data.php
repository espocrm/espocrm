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

namespace Espo\Core\Htmlizer\Helper;

use stdClass;

class Data
{
    private $argumentList;

    private $options;

    private $blockParams;

    private $context;

    private $name;

    private $rootContext;

    private $func;

    private $inverseFunc;

    public function __construct(
        string $name,
        array $argumentList,
        stdClass $options,
        array $context,
        array $rootContext,
        int $blockParams,
        ?callable $func,
        ?callable $inverseFunc
    ) {
        $this->name = $name;
        $this->argumentList = $argumentList;
        $this->options = $options;
        $this->context = $context;
        $this->rootContext = $rootContext;
        $this->blockParams = $blockParams;
        $this->func = $func;
        $this->inverseFunc = $inverseFunc;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function getRootContext(): array
    {
        return $this->rootContext;
    }

    public function getOptions(): stdClass
    {
        return $this->options;
    }

    /**
     * @return mixed[]
     */
    public function getArgumentList(): array
    {
        return $this->argumentList;
    }

    public function hasOption(string $name): bool
    {
        return property_exists($this->options, $name);
    }

    /**
     * @return mixed
     */
    public function getOption(string $name)
    {
        return $this->options->$name ?? null;
    }

    public function getFunction(): ?callable
    {
        return $this->func;
    }

    public function getInverseFunction(): ?callable
    {
        return $this->inverseFunc;
    }
}
