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

class Evaluator
{
    private $functionFactory;

    private $formula;

    private $parser;

    private $attributeFetcher;

    private $parsedHash;

    public function __construct($container = null, array $functionClassNameMap = [], array $parsedHash = [])
    {
        $this->attributeFetcher = new AttributeFetcher();
        $this->functionFactory = new FunctionFactory($container, $this->attributeFetcher, $functionClassNameMap);
        $this->formula = new Formula($this->functionFactory);
        $this->parser = new Parser();
        $this->parsedHash = [];
    }

    public function process($expression, $entity = null, $variables = null)
    {
        if (!array_key_exists($expression, $this->parsedHash)) {
            $item = $this->parser->parse($expression);
            $this->parsedHash[$expression] = $item;
        } else {
            $item = $this->parsedHash[$expression];
        }

        if (!$item || !($item instanceof \StdClass)) {
            throw new Error();
        }

        $result = $this->formula->process($item, $entity, $variables);

        $this->attributeFetcher->resetRuntimeCache();

        return $result;
    }
}
